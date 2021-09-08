<?php
/**
 * This file is a part of ChessBrowser.
 *
 * ChessBrowser is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * This file contains code originally a part of PgnParser
 *
 * PgnParser is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @file ChessParser
 * @ingroup ChessBrowser
 * @author Wugapodes
 * @author DannyS712
 * @author Alf Magne Kalleland
 */

namespace MediaWiki\Extension\ChessBrowser;

use MediaWiki\Extension\ChessBrowser\PgnParser\GameParser;
use MediaWiki\Extension\ChessBrowser\PgnParser\PgnGameParser;

class ChessParser {

	/** @var string[] */
	private $pgnGames;

	/**
	 * Construct a new ChesParser
	 *
	 * @param string $pgnContent
	 */
	public function __construct( $pgnContent ) {
		$clean = $pgnContent;

		$clean = preg_replace( '/"\]\s{0,10}\[/s', "]\n[", $clean );
		$clean = preg_replace( '/"\]\s{0,10}([\.\d{])/s', "\"]\n\n$1", $clean );

		$clean = preg_replace( "/{\s{0,6}\[%emt[^\}]*?\}/", "", $clean );

		$clean = preg_replace( '/\s(\$\d{1,3})/', '\1', $clean );
		$clean = str_replace( "({", "( {", $clean );
		$clean = preg_replace( "/{([^\[]*?)\[([^}]?)}/s", '{$1-SB-$2}', $clean );
		$clean = preg_replace( "/\r/s", "", $clean );
		$clean = preg_replace( "/\t/s", "", $clean );
		$clean = preg_replace( "/\]\s+\[/s", "]\n[", $clean );
		$clean = str_replace( " [", "[", $clean );
		$clean = preg_replace( "/([^\]])(\n+)\[/si", "$1\n\n[", $clean );
		$clean = preg_replace( "/\n{3,}/s", "\n\n", $clean );
		$clean = str_replace( "-SB-", "[", $clean );
		$clean = str_replace( "0-0-0", "O-O-O", $clean );
		$clean = str_replace( "0-0", "O-O", $clean );

		$clean = preg_replace( '/^([^\[])*?\[/', '[', $clean );

		$clean = NagTable::replaceNag( $clean );

		$pgnGames = [];
		$content = "\n\n" . $clean;
		$games = preg_split( "/\n\n\[/s", $content, -1, PREG_SPLIT_DELIM_CAPTURE );

		for ( $i = 1, $count = count( $games ); $i < $count; $i++ ) {
			$gameContent = trim( "[" . $games[$i] );
			if ( strlen( $gameContent ) > 10 ) {
				$pgnGames[] = $gameContent;
			}
		}

		$this->pgnGames = $pgnGames;
	}

	/**
	 * Get the game at an index
	 *
	 * @param int $index
	 * @return array|null
	 */
	private function getGameByIndex( $index ) {
		$games = $this->pgnGames;
		if ( $games && count( $games ) > $index ) {
			$pgnGameParser = new PgnGameParser( $games[$index] );
			$parsedData = $pgnGameParser->getParsedData();

			$gameParser = new GameParser( $parsedData );
			return $gameParser->getParsedGame();
		}
		return null;
	}

	/**
	 * To move up a rank, add 1; down a rank subtract 1
	 * To move a file towards queenside, subtract 8; kingside a file add 8
	 * Example: a1 to c5 = 0(a1) + 8(b1) + 8(c1) + 4(c5) = 20
	 *
	 * @param string $square
	 * @return int
	 */
	private static function squareToInt( string $square ): int {
		if ( $square === '-' ) {
			// Special handling
			return -1;
		}

		$chessSquare = ChessSquare::newFromCoords( $square );
		return $chessSquare->getAsVertical64();
	}

	/**
	 * Returns a JSON string for the javascript module
	 * @since 0.2.0
	 * @param int $gameNum Passed directly to the PGN parsing library
	 * @return array
	 */
	public function createOutputJson( $gameNum = 0 ) {
		$gameObject = $this->getPgnParserOutput( $gameNum );
		$moves = array_pop( $gameObject );

		# The parser guarantees us the initial board state as FEN
		$fenParts = $this->getFenParts( $gameObject['metadata']['fen'] );
		$gameObject = $this->convertParserOutput( $moves, $fenParts, $gameObject );

		return $gameObject;
	}

	/**
	 * Convert the parser output into the board, token, ply object used in
	 * the javascript module.
	 * @param array $moves The list of moves output by the PgnParser
	 * @param array $fenParts The fen string broken down by getFenParts
	 * @param array $moveObject The movereferences from the PgnParser
	 * @return array
	 */
	public function convertParserOutput( $moves, $fenParts, $moveObject ) {
		$index = 0;
		foreach ( $moves as $move ) {
			$token = $move['m'];

			$fen = $move['fen'];
			$from = self::squareToInt( $move['from'] );
			$to = self::squareToInt( $move['to'] );

			$special = $this->checkSpecialMove( $to, $from, $token, $fenParts );
			if ( array_key_exists( 'comment', $move ) ) {
				$special[] = $move['comment'];
			} else {
				$special[] = null;
			}
			if ( array_key_exists( 'variations', $move ) ) {
				if ( !array_key_exists( 'variations', $moveObject ) ) {
					$moveObject['variations'] = [];
				}
				$moveObject['variations'][] = $this->createAnnotationJson( $move['variations'], $fenParts, $index );
			}

			$moveObject['boards'][] = $fen;
			$moveObject['plys'][] = [ $from, $to, $special ];
			$moveObject['tokens'][] = $token;

			$fenParts = $this->getFenParts( $fen );
			$index++;
		}
		return $moveObject;
	}

	/**
	 * Will pass variations to the JS module
	 * to insert into the game.
	 * @param array $variationObj list of variations associated with a move. Organized
	 *   as a list of lists of moves, so 1. e4 (1. c4 c5) (1.d4) would be:
	 *   [
	 *     [
	 *       {
	 *        'm': 'c4',
	 *	  'from': 'c2',
	 *	  'to': 'c4',
	 *	  'fen': '...'
	 *	 },
	 *       {
	 *        'm': 'c5',
	 *	  'from': 'c7',
	 *	  'to': 'c5',
	 *	  'fen': '...'
	 *	 }
	 *     ],
	 *     [
	 *       {
	 *        'm': 'd4',
	 *        'from': 'd2',
	 *        'to': 'd4',
	 *        'fen': '...'
	 *       }
	 *     ]
	 *   ]
	 * @param array $fenParts The starting fen broken down into semantic chunks
	 * @param int $index Ply of the variation's parent move.
	 * @return array
	 */
	public function createAnnotationJson( $variationObj, $fenParts, $index ) {
		$variations = [ $index, [] ];
		foreach ( $variationObj as $variation ) {
			$moveObject = [
				'boards' => [],
				'plys' => [],
				'tokens' => []
			];
			$moves = $variation;
			$variations[1][] = $this->convertParserOutput( $moves, $fenParts, $moveObject );
		}
		return $variations;
		// Will pass variations and notation to the JS module
		// to insert into the game.
	}

	/**
	 * Sets the special move field of the JSON "ply" section
	 * @since 0.2.0
	 * @param int $to
	 * @param int $from
	 * @param string $token
	 * @param array $fenParts
	 * @return array
	 */
	public function checkSpecialMove( $to, $from, $token, $fenParts ) {
		if ( $to === $fenParts['enPassantTarget'] ) {
			$specialType = "en passant";
			if ( $fenParts['toMove'] === 'w' ) {
				$specialAction = $to - 1;
			} else {
				$specialAction = $to + 1;
			}
		} elseif ( $token === 'O-O' || $token === 'O-O-O' ) {
			$specialType = "castle";
			$rookSource = null;
			$rookTarget = null;
			if ( $token === 'O-O' && $fenParts['toMove'] === 'w' ) {
				$rookSource = 56;
				$rookTarget = 40;
			} elseif ( $token === 'O-O' && $fenParts['toMove'] === 'b' ) {
				$rookSource = 63;
				$rookTarget = 47;
			} elseif ( $token === 'O-O-O' && $fenParts['toMove'] === 'w' ) {
				$rookSource = 0;
				$rookTarget = 24;
			} elseif ( $token === 'O-O-O' && $fenParts['toMove'] === 'b' ) {
				$rookSource = 7;
				$rookTarget = 31;
			}
			$specialAction = [ $rookSource, $rookTarget ];
		} elseif ( strpos( $token, '=' ) ) {
			$specialType = "promotion";
			# Get first char after =; ignore rest
			$promotedTo = explode( '=', $token )[1][0];
			if ( $fenParts['toMove'] === 'b' ) {
				$specialAction = strtolower( $promotedTo );
			} else {
				$specialAction = strtoupper( $promotedTo );
			}
		} else {
			$specialType = "move";
			$specialAction = null;
		}
		return [ $specialType, $specialAction ];
	}

	/**
	 * Extracts en passant target and color to play from FEN
	 * @since 0.2.0
	 * @param string $fen
	 * @return array
	 */
	public function getFenParts( $fen ) {
		$fenParts = explode( ' ', $fen );
		$toMove = $fenParts[1];
		$enPassantTarget = self::squareToInt( $fenParts[3] );
		return [
			'toMove' => $toMove,
			'enPassantTarget' => $enPassantTarget
		];
	}

	/**
	 * Extract needed data from the PGN parser output
	 * @since 0.2.0
	 * @param int $gameNum Passed directly to the PGN parsing library
	 * @return array
	 */
	public function getPgnParserOutput( $gameNum = 0 ) {
		$gameObject = $this->getGameByIndex( $gameNum );
		// What is in the game Object?

		// Need to document
		$metadata = [];
		$moves = [];
		foreach ( $gameObject as $key => $obj ) {
			if ( $key === 'metadata' ) {
				continue;
			} elseif ( $key === 'moves' ) {
				$moves = $obj;
			} elseif ( $key === 'comment' ) {
				continue;
			} else {
				$metadata[$key] = $obj;
			}
		}
		return [
			'boards' => [],
			'plys' => [],
			'tokens' => [],
			'variations' => [],
			'metadata' => $metadata,
			'moves' => $moves
		];
	}
}

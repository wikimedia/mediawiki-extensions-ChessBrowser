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
 * @file ChessParser
 * @ingroup ChessBrowser
 * @author Wugapodes
 */

class ChessParser extends PgnParser {
	/**
	 * To move up a rank, add 1; down a rank subtract 1
	 * To move a file towards queenside, subtract 8; kingside a file add 8
	 * Example: a1 to c5 = 0(a1) + 8(b1) + 8(c1) + 4(c5) = 20
	 *
	 * @param string $square
	 * @return int
	 */
	private static function squareToInt( string $square ) : int {
		if ( $square === '-' ) {
			// Special handling
			return -1;
		}

		$chessSquare = ChessSquare::newFromCoords( $square );
		$int = $chessSquare->getAs64();

		return $int;
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

		foreach ( $moves as $move ) {
			$token = $move['m'];

			$fen = $move['fen'];
			$from = self::squareToInt( $move['from'] );
			$to = self::squareToInt( $move['to'] );

			$special = $this->checkSpecialMove( $to, $from, $token, $fenParts );

			array_push( $gameObject['boards'], $fen );
			array_push( $gameObject['plys'], [ $from, $to, $special ] );
			array_push( $gameObject['tokens'], $token );

			$fenParts = $this->getFenParts( $fen );
		}
		return $gameObject;
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

		// Need to document
		$boards = [];
		$plys = [];
		$tokens = [];
		$metadata = [];
		$moves = [];
		foreach ( array_keys( $gameObject ) as $key ) {
			if ( $key === 'metadata' ) {
				continue;
			} elseif ( $key === 'moves' ) {
				$moves = $gameObject[$key];
			} else {
				$metadata[$key] = $gameObject[$key];
			}
		}
		return [
			'boards' => [],
			'plys' => [],
			'tokens' => [],
			'metadata' => $metadata,
			'moves' => $moves
		];
	}
}

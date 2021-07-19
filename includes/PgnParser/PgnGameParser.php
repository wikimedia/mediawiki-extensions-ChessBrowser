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
 * This file is a part of PgnParser
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
 * @file PgnGameParser
 * @ingroup ChessBrowser
 * @author Alf Magne Kalleland
 */

namespace MediaWiki\Extension\ChessBrowser\PgnParser;

class PgnGameParser {

	private $pgnGame;

	private $defaultFen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

	private $specialMetadata = [
		'event',
		'site',
		'white',
		'black',
		'result',
		'plycount',
		'eco',
		'fen',
		'timecontrol',
		'round',
		'date',
		'annotator',
		'termination'
	];

	/**
	 * Set the parser's pgn
	 *
	 * @param string $pgnGame
	 */
	public function __construct( $pgnGame ) {
		$this->pgnGame = trim( $pgnGame );
	}

	/**
	 * Get the parsed data
	 *
	 * @return array
	 */
	public function getParsedData() {
		$gameData = $this->getMetadata();
		$moveReferences = $this->getMoves();
		$gameData[ChessJson::MOVE_MOVES] = $moveReferences[0];
		$gameData[ChessJson::MOVE_COMMENT] = $moveReferences;
		return $gameData;
	}

	/**
	 * Get the metadata
	 *
	 * @return array
	 */
	private function getMetadata() {
		$ret = [
			ChessJson::GAME_METADATA => []
		];
		// TODO set lastmoves property by reading last 3-4 moves in moves array
		$lines = explode( "\n", $this->pgnGame );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( substr( $line, 0, 1 ) === '[' && substr( $line, strlen( $line ) - 1, 1 ) === ']' ) {
				$metadata = $this->getMetadataKeyAndValue( $line );
				if ( in_array( $metadata['key'], $this->specialMetadata ) ) {
					$ret[$metadata['key']] = $metadata['value'];
				} else {
					$ret[ChessJson::GAME_METADATA][$metadata['key']] = $metadata['value'];
				}
			}
		}
		if ( !isset( $ret[ChessJson::FEN] ) ) {
			$ret[ChessJson::FEN] = $this->defaultFen;
		}

		return $ret;
	}

	/**
	 * Get the metadata key and value from a string
	 *
	 * @param string $metadataString
	 * @return array
	 */
	private function getMetadataKeyAndValue( $metadataString ) {
		$metadataString = preg_replace( "/[\[\]]/s", "", $metadataString );
		$metadataString = str_replace( '"', '', $metadataString );
		$tokens = explode( " ", $metadataString );

		$key = $tokens[0];
		$value = implode( " ", array_slice( $tokens, 1 ) );
		return [
			'key' => strtolower( $key ),
			'value' => $value
		];
	}

	/**
	 * Get the moves
	 *
	 * @return array
	 */
	private function getMoves() {
		$moveBuilder = new MoveBuilder();

		$parts = $this->getMovesAndComments();
		for ( $i = 0, $count = count( $parts ); $i < $count; $i++ ) {
			$move = trim( $parts[$i] );

			switch ( $move ) {
				case '{':
					if ( $i == 0 ) {
						$moveBuilder->addCommentBeforeFirstMove( $parts[$i + 1] );
					} else {
						$moveBuilder->addComment( $parts[$i + 1] );
					}
					$i += 2;
					break;
				default:
					$moves = $this->getMovesAndVariationFromString( $move );
					foreach ( $moves as $move ) {
						switch ( $move ) {
							case '(':
								$moveBuilder->startVariation();
								break;
							case ')':
								$moveBuilder->endVariation();
								break;
							default:
								$moveBuilder->addMoves( $move );
						}
					}
					break;
			}
		}

		return $moveBuilder->getMoves();
	}

	/**
	 * Get the moves and comments
	 *
	 * @return array
	 */
	private function getMovesAndComments() {
		$ret = preg_split( "/({|})/s", $this->getMoveString(), 0, PREG_SPLIT_DELIM_CAPTURE );
		if ( !$ret[0] ) {
			$ret = array_slice( $ret, 1 );
		}
		return $ret;
	}

	/**
	 * Get the moves and variations from a string
	 *
	 * TODO make static
	 *
	 * @param string $string
	 * @return array
	 */
	private function getMovesAndVariationFromString( $string ) {
		$string = " " . $string;

		$string = preg_replace( "/\d+?\./s", "", $string );
		$string = str_replace( " ..", "", $string );
		$string = str_replace( "  ", " ", $string );
		$string = trim( $string );

		return preg_split( "/(\(|\))/s", $string, 0, PREG_SPLIT_DELIM_CAPTURE );
	}

	/**
	 * Get a move string
	 *
	 * @return string
	 */
	private function getMoveString() {
		$tokens = preg_split( "/\]\n\n/s", $this->pgnGame );
		if ( !isset( $tokens[1] ) ) {
			return "";
		}
		$gameData = $tokens[1];
		$gameData = str_replace( "\n", " ", $gameData );
		$gameData = preg_replace( "/(\s+)/", " ", $gameData );
		return trim( $gameData );
	}
}

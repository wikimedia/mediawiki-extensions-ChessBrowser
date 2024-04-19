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
	 * Determine how many elements are part of the comment
	 *
	 * getMovesAndComments() Takes the PGN move string and splits it on the
	 * special characters `{`, `}`, `;`, and `\n`. These characters *sometimes*
	 * delimit a comment. From the PGN Standard section 5
	 *
	 * > Brace comments do not nest; a left brace character appearing in a brace
	 * > comment loses its special meaning and is ignored. A semicolon appearing
	 * > inside of a brace comment loses its special meaning and is ignored.
	 * > Braces appearing inside of a semicolon comments lose their special meaning
	 * > and are ignored.
	 *
	 * The result is that a single comment might span multiple elements of
	 * $moveStringParts if it contains characters that lost their special meaning. This
	 * function implements a context-sensitive sub-parser to determine how far to move
	 * the main buffer when it runs into a comment start character.
	 *
	 * This function is called whenever getMoves() encounters a comment start character,
	 * so `{` or `;` and receives the whole $moveStringParts array and the main buffer
	 * position ($bufferPos) to start sub-parsing the comment. The element at that index
	 * will be `{` or `;` and the first for loop iteration sets the proper context flag.
	 * These flags are used to determine which characters lose special meaning. When
	 * the appropriate comment end character for the context is hit, the function returns
	 * an integer ($idx) specifying how far forward to move the main buffer.
	 *
	 * See T363230
	 *
	 * @param array $moveStringParts Output of getMovesAndComments()
	 * @param int $bufferPos Index of where in $moveStringParts the comment starts
	 * @return int
	 */
	private function mergeAdjacentComments( array $moveStringParts, int $bufferPos ): int {
		// Context flags
		$inBraceComment = false;
		$inEOLComment = false;

		$endIdx = count( $moveStringParts ) - $bufferPos;
		for ( $idx = 0; $idx < $endIdx; $idx++ ) {
			$move = $moveStringParts[ $bufferPos + $idx ];

			/**
			 * The element following a comment start character or a '}' without
			 * its special meaning will always be a comment string, so we can
			 * save some time and potential parsing bugs by incrementing $idx
			 * past them and skipping the iteration.
			 *
			 * See getMovesAndComments for more info on $moveStringParts
			 */
			switch ( $move ) {
				case '{':
					// Set context flag if not in EOL comment context
					// '{' in a brace comment context loses its special meaning
					if ( !$inEOLComment ) {
						$inBraceComment = true;
					}
					// Skip past following comment string
					$idx++;
					break;
				case ';':
					// Set context flag if not in brace comment context
					// ';' in brace and EOL comments loses special meaning
					if ( !$inBraceComment ) {
						$inEOLComment = true;
					}
					// Skip past following comment string
					$idx++;
					break;
				case '}':
					// The first '}' ALWAYS has special meaning under the PGN
					// standard and ends a brace comment.
					if ( $inBraceComment ) {
						// Don't include the '}' itself in the buffer increment
						return $idx - 1;
					}
					// '}' in EOL comment loses special meaning
					// Skip past following comment string
					$idx++;
					break;
				case "\n":
					// \n ALWAYS ends an EOL comment.
					if ( $inEOLComment ) {
						// Include the newline in the buffer increment, it gets
						// removed later as whitespace
						return $idx - 0;
					}
					break;
			}
		}
		// Reached EOF so return a buffer increment past EOF
		return $idx + 1;
	}

	/**
	 * Process tokens in the move string
	 *
	 * @return array
	 */
	private function getMoves() {
		$moveBuilder = new MoveBuilder();

		$moveStringParts = $this->getMovesAndComments();
		$lenMSP = count( $moveStringParts );
		for ( $bufferPos = 0; $bufferPos < $lenMSP; $bufferPos++ ) {
			$move = trim( $moveStringParts[$bufferPos] );

			switch ( $move ) {
				case '{':
				case ';':
					$commentBufferIncrement = $this->mergeAdjacentComments(
						$moveStringParts,
						$bufferPos
					);
					$commentSlice = array_slice(
						$moveStringParts,
						$bufferPos + 1,
						$commentBufferIncrement
					);
					$comment = implode( '', $commentSlice );
					if ( $bufferPos == 0 ) {
						$moveBuilder->addCommentBeforeFirstMove( $comment );
					} else {
						$moveBuilder->addComment( $comment );
					}
					$bufferPos += $commentBufferIncrement;
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
	 * Split the move string based on comment indicators
	 *
	 * $moveSectionParts is an array split by the PGN special comment characters `{`, `}`, `;`, and `\n`. These
	 * splitting characters are also included in the array to aid parsing later. The structure for a string like:
	 *
	 * ```
	 * $inputString = "e4 e5 { King's pawn opening } Nf3 ; Interesting! {Not really}\nNc6 ; A common response";
	 * ```
	 *
	 * Would result in the array:
	 * ```
	 * $moveSectionParts = [
	 *     "e4 e5",
	 *     "{"
	 *     "King's pawn opening",
	 *     "}",
	 *     "Nf3",
	 *     ";",
	 *     "Interesting!",
	 *     "{",
	 *     "Not really",
	 *     "}",
	 *     "",
	 *     "\n",
	 *     "Nc6",
	 *     ";",
	 *     "A common response"
	 * ];
	 * ```
	 *
	 * Notice that even though the split characters `}` and `\n` are adjacent, the split results in an empty string
	 * being inserted between them.
	 *
	 * @return array
	 */
	private function getMovesAndComments() {
		$moveSectionParts = preg_split( "/({|}|;|\n)/s", $this->getMoveString(), 0, PREG_SPLIT_DELIM_CAPTURE );
		if ( !$moveSectionParts[0] ) {
			$moveSectionParts = array_slice( $moveSectionParts, 1 );
		}
		return $moveSectionParts;
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
		// \n is meaningful so don't trim them
		return trim( $gameData, " \r\t\v\x00" );
	}
}

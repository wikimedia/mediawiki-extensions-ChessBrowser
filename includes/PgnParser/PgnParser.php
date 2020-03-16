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
 * @file PgnParser
 * @ingroup ChessBrowser
 * @author Alf Magne Kalleland
 */

class PgnParser {

	private $pgnFile;
	private $pgnContent;
	private $pgnGames;
	private $gameParser;
	private $fullParsing = true;

	/**
	 * Construct a new PgnParser
	 *
	 * @param string $pgnFile
	 * @param bool $fullParsing
	 */
	public function __construct( $pgnFile = '', $fullParsing = true ) {
		if ( $pgnFile ) {
			$this->pgnFile = $this->sanitize( $pgnFile );

			if ( !file_exists( $this->pgnFile ) ) {
				throw new ChessBrowserException( 'File not found: ' . $this->pgnFile );
			}
		}

		$this->fullParsing = $fullParsing;
		$this->gameParser = new GameParser();
	}

	/**
	 * Sanitize a file path
	 *
	 * TODO make static
	 *
	 * @param string $filePath
	 * @return string|null
	 */
	private function sanitize( $filePath ) {
		if ( substr( $filePath, 0, 1 ) === "/" ) {
			return null;
		}

		$extension = $this->getExtension( $filePath );
		if ( $extension != 'pgn' ) {
			return null;
		}

		$filePath = preg_replace( "/[^\.\w\-\/]/si", "", $filePath );
		if ( !file_exists( $filePath ) ) {
			return null;
		}

		return $filePath;
	}

	/**
	 * Get a file extension
	 *
	 * TODO make static
	 *
	 * @param string $filePath
	 * @return string
	 */
	private function getExtension( $filePath ) {
		$tokens = explode( ".", $filePath );
		return strtolower( array_pop( $tokens ) );
	}

	/**
	 * Set the pgn content
	 *
	 * @param string $content
	 */
	public function setPgnContent( $content ) {
		$this->pgnContent = $content;
	}

	/**
	 * Get a clean version of the pgn content
	 *
	 * @return string
	 */
	private function cleanPgn() {
		$c = $this->pgnContent;

		$c = preg_replace( '/"\]\s{0,10}\[/s', "]\n[", $c );
		$c = preg_replace( '/"\]\s{0,10}([\.\d{])/s', "\"]\n\n$1", $c );

		$c = preg_replace( "/{\s{0,6}\[%emt[^\}]*?\}/", "", $c );

		$c = preg_replace( "/\\$\d+/s", "", $c );
		$c = str_replace( "({", "( {", $c );
		$c = preg_replace( "/{([^\[]*?)\[([^}]?)}/s", '{$1-SB-$2}', $c );
		$c = preg_replace( "/\r/s", "", $c );
		$c = preg_replace( "/\t/s", "", $c );
		$c = preg_replace( "/\]\s+\[/s", "]\n[", $c );
		$c = str_replace( " [", "[", $c );
		$c = preg_replace( "/([^\]])(\n+)\[/si", "$1\n\n[", $c );
		$c = preg_replace( "/\n{3,}/s", "\n\n", $c );
		$c = str_replace( "-SB-", "[", $c );
		$c = str_replace( "0-0-0", "O-O-O", $c );
		$c = str_replace( "0-0", "O-O", $c );

		$c = preg_replace( '/^([^\[])*?\[/', '[', $c );

		return $c;
	}

	/**
	 * Get the array of games from a pgn
	 *
	 * TODO this and splitPgnIntoGames are identical
	 *
	 * @param string $pgn
	 * @return array
	 */
	public static function getArrayOfGames( $pgn ) {
		return self::getPgnGamesAsArray( $pgn );
	}

	/**
	 * Get the array of games from a pgn
	 *
	 * TODO this and getArrayOfGames are identical
	 *
	 * @param string $pgnString
	 * @return array
	 */
	private function splitPgnIntoGames( $pgnString ) {
		return $this->getPgnGamesAsArray( $pgnString );
	}

	/**
	 * Get the array of pgn games
	 *
	 * TODO is this static or not
	 *
	 * @param string $pgn
	 * @return array
	 */
	private function getPgnGamesAsArray( $pgn ) {
		$ret = [];
		$content = "\n\n" . $pgn;
		$games = preg_split( "/\n\n\[/s", $content, -1, PREG_SPLIT_DELIM_CAPTURE );

		file_put_contents( "parsed.pgn", $content );

		for ( $i = 1, $count = count( $games ); $i < $count; $i++ ) {
			$gameContent = trim( "[" . $games[$i] );
			if ( strlen( $gameContent ) > 10 ) {
				array_push( $ret, $gameContent );
			}
		}

		return $ret;
	}

	/**
	 * Get games encoded as json
	 *
	 * @return string
	 */
	public function getGamesAsJSON() {
		return json_encode( $this->getGames() );
	}

	/**
	 * Get games that aren't parsed
	 *
	 * TODO document
	 *
	 * @return mixed
	 */
	public function getUnparsedGames() {
		if ( !isset( $this->pgnGames ) ) {
			if ( $this->pgnFile && !isset( $this->pgnContent ) ) {
				$this->pgnContent = file_get_contents( $this->pgnFile );
			}
			$this->pgnGames = $this->splitPgnIntoGames( $this->cleanPgn( $this->pgnContent ) );
		}

		return $this->pgnGames;
	}

	/**
	 * Get count of games that aren't parsed
	 *
	 * @return int
	 */
	public function countGames() {
		return count( $this->getUnparsedGames() );
	}

	/**
	 * Get a clean pgn
	 *
	 * @return string
	 */
	public function getCleanPgn() {
		return $this->cleanPgn( $this->pgnContent );
	}

	/**
	 * Get the first game
	 *
	 * @return array|null
	 */
	public function getFirstGame() {
		return $this->getGameByIndex( 0 );
	}

	/**
	 * Get the game at an index, with the moves shortened
	 *
	 * TODO make a wrapper for getGameByIndex
	 *
	 * @param int $index
	 * @return array|null
	 */
	public function getGameByIndexShort( $index ) {
		$games = $this->getUnparsedGames();
		if ( count( $games ) && count( $games ) > $index ) {
			$game = $this->getParsedGame( $games[$index] );
			$game["moves"] = $this->toShortVersion( $game["moves"] );
			return $game;
		}
		return null;
	}

	/**
	 * Get the game at an index
	 *
	 * @param int $index
	 * @return array|null
	 */
	public function getGameByIndex( $index ) {
		$games = $this->getUnparsedGames();
		if ( count( $games ) && count( $games ) > $index ) {
			return $this->getParsedGame( $games[$index] );
		}
		return null;
	}

	/**
	 * Get the games
	 *
	 * @return array
	 */
	public function getGames() {
		return $this->getParsedGames( false );
	}

	/**
	 * Get the games, with moves shortened
	 *
	 * @return array
	 */
	public function getGamesShort() {
		return $this->getParsedGames( true );
	}

	/**
	 * Get the games
	 *
	 * @param bool $short
	 * @return array
	 */
	private function getParsedGames( $short = false ) {
		$games = $this->getUnparsedGames();
		$ret = [];
		for ( $i = 0, $count = count( $games ); $i < $count; $i++ ) {
			try {
				$g = $short ? $this->getParsedGameShort( $games[$i] ) : $this->getParsedGame( $games[$i] );
				$ret[] = $g;
			} catch ( Exception $e ) {
				// Do nothing
			}
		}
		return $ret;
	}

	/**
	 * Convert to shortversion
	 *
	 * TODO document
	 *
	 * @param array $branch
	 * @return array
	 */
	private function toShortVersion( $branch ) {
		foreach ( $branch as &$move ) {
			if ( isset( $move["from"] ) ) {
				$move["n"] = $move["from"] . $move["to"];
				unset( $move["fen"] );
				unset( $move["from"] );
				unset( $move["to"] );
				if ( isset( $move["variations"] ) ) {
					$move["v"] = [];
					foreach ( $move["variations"] as $variation ) {
						$move["v"][] = $this->toShortVersion( $variation );
					}
				}
				unset( $move["variations"] );
			}
		}
		return $branch;
	}

	/**
	 * Parse a game
	 *
	 * @param string $unParsedGame
	 * @return array
	 */
	private function getParsedGame( $unParsedGame ) {
		$ret = ( new PgnGameParser( $unParsedGame ) )->getParsedData();
		if ( $this->fullParsing ) {
			$ret = $this->gameParser->getParsedGame( $ret );
		}
		return $ret;
	}

	/**
	 * Parse a game with shortened moves
	 *
	 * @param string $unParsedGame
	 * @return array
	 */
	private function getParsedGameShort( $unParsedGame ) {
		$ret = ( new PgnGameParser( $unParsedGame ) )->getParsedData();
		if ( $this->fullParsing ) {
			$ret = $this->gameParser->getParsedGame( $ret, true );
			$moves = &$ret["moves"];
			$moves = $this->toShortVersion( $moves );
		}
		return $ret;
	}
}

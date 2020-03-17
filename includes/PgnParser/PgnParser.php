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

	private $pgnContent;
	private $pgnGames;
	private $gameParser;

	/**
	 * Construct a new PgnParser
	 *
	 * @param string $pgnContent
	 */
	public function __construct( $pgnContent ) {
		$this->pgnContent = $pgnContent;
		// TODO relocate this
		$this->gameParser = new GameParser();
	}

	/**
	 * Get games encoded as json
	 *
	 * @return string
	 */
	public function getGamesAsJSON() {
		return json_encode( $this->getParsedGames() );
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
			$clean = $this->pgnContent;

			$clean = preg_replace( '/"\]\s{0,10}\[/s', "]\n[", $clean );
			$clean = preg_replace( '/"\]\s{0,10}([\.\d{])/s', "\"]\n\n$1", $clean );

			$clean = preg_replace( "/{\s{0,6}\[%emt[^\}]*?\}/", "", $clean );

			$clean = preg_replace( "/\\$\d+/s", "", $clean );
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

			$ret = [];
			$content = "\n\n" . $clean;
			$games = preg_split( "/\n\n\[/s", $content, -1, PREG_SPLIT_DELIM_CAPTURE );

			for ( $i = 1, $count = count( $games ); $i < $count; $i++ ) {
				$gameContent = trim( "[" . $games[$i] );
				if ( strlen( $gameContent ) > 10 ) {
					array_push( $ret, $gameContent );
				}
			}

			$this->pgnGames = $ret;
		}

		return $this->pgnGames;
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
	private function getParsedGames() {
		$games = $this->getUnparsedGames();
		$ret = [];
		for ( $i = 0, $count = count( $games ); $i < $count; $i++ ) {
			try {
				$ret[] = $this->getParsedGame( $games[$i] );
			} catch ( Exception $e ) {
				// Do nothing
			}
		}
		return $ret;
	}

	/**
	 * Parse a game
	 *
	 * @param string $unParsedGame
	 * @return array
	 */
	private function getParsedGame( $unParsedGame ) {
		$ret = ( new PgnGameParser( $unParsedGame ) )->getParsedData();
		$ret = $this->gameParser->getParsedGame( $ret );
		return $ret;
	}
}

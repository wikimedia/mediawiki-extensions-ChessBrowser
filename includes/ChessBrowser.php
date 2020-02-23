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
 * @file ChessBrowser
 * @ingroup ChessBrowser
 * @author Wugapodes
 */

class ChessBrowser {

	/**
	 * Show the chess game
	 *
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string
	 */
	public static function pollRender( $input, array $args, Parser $parser, PPFrame $frame ) {
		$parser->getOutput()->setExtensionData( 'ChessViewerTrigger', 'true' );
		$pgnParser = new PgnParser( $input );
		$ret = '<div class="pgn-source-wrapper"><div class="pgn-sourcegame">';
		$ret .= $pgnParser->parseMovetext();
		$ret .= '</div></div>';
		return $ret;
	}
}

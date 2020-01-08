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
	 * @since 0.1.0
	 * @param string $input The wikitext placed between pgn tags
	 * @param array $args Arguments passed as xml attributes
	 * @param Parser $parser The MediaWiki parser object
	 * @param PPFrame $frame Parent frame, provides context of the tage placement
	 * @return string
	 */
	public static function newGame( $input, array $args, Parser $parser, PPFrame $frame ) {
		$parser->getOutput()->setExtensionData( 'ChessViewerTrigger', 'true' );
		$chessParser = new ChessParser();
		$chessParser->setPgnContent( $input );
		$ret = '<div class="pgn-source-wrapper"><div class="pgn-sourcegame">';
		$ret .= $chessParser->createOutputJson();
		$ret .= '</div></div>';
		return $ret;
	}
}

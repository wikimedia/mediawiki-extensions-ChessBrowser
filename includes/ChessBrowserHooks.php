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
 * @file ChessBrowserHooks
 * @ingroup ChessBrowser
 * @author Wugapodes
 */

class ChessBrowserHooks {

	/**
	 * Register parser hook
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'pgn', [ ChessBrowser::class, 'newGame' ] );
	}

	/**
	 * Update OutputPage after ParserOutput is added, if it includes a chess board
	 *
	 * @param OutputPage &$out
	 * @param ParserOutput $parserOutput
	 */
	public static function onOutputPageParserOutput( OutputPage &$out, ParserOutput $parserOutput ) {
		$trigger = $parserOutput->getExtensionData( 'ChessViewerTrigger' );
		if ( $trigger ) {
			$out->addModules( 'ext.chessViewer' );
			$numberOfGames = $parserOutput->getExtensionData( 'ChessViewerNumGames' );
			$gameIdentifiers = array_map(
				function ( $index ) {
					$id = 'chess-browser-div-' . $index;
					return $id;
				},
				range( 1, $numberOfGames )
			);
			$out->addJsConfigVars( 'wgChessBrowserDivIdentifiers', $gameIdentifiers );
		}
	}
}

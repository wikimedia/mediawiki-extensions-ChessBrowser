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

namespace MediaWiki\Extension\ChessBrowser;

use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Output\Hook\OutputPageParserOutputHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;

class ChessBrowserHooks implements
	ParserFirstCallInitHook,
	OutputPageParserOutputHook
{

	/**
	 * Register parser hooks
	 *
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'pgn', [ ChessBrowser::class, 'newGame' ] );
		$parser->setHook( 'fen', [ ChessBrowser::class, 'newPosition' ] );
	}

	/**
	 * Update OutputPage after ParserOutput is added, if it includes a chess board
	 *
	 * @param OutputPage $out
	 * @param ParserOutput $parserOutput
	 */
	public function onOutputPageParserOutput( $out, $parserOutput ): void {
		if ( $parserOutput->getExtensionData( 'ChessViewerFEN' ) ) {
			$out->addModuleStyles( 'ext.chessViewer.styles' );
		}
		if ( $parserOutput->getExtensionData( 'ChessViewerTrigger' ) ) {
			$out->addModuleStyles( [ 'ext.chessViewer.styles', 'jquery.makeCollapsible.styles' ] );
			$out->addModules( [ 'ext.chessViewer', 'jquery.makeCollapsible' ] );
		}
	}
}

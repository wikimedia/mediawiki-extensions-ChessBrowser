<?php

class ChessBrowserHooks {

	/**
	 * Register parser hook
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'pgn', [ ChessBrowser::class, 'pollRender' ] );
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
		}
	}
}

<?php

class ChessBrowserHooks {
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'pgn', [ ChessBrowser::class, 'pollRender' ] );
	}

	public static function onOutputPageParserOutput( OutputPage &$out, ParserOutput $parserOutput ) {
		$trigger = $parserOutput->getExtensionData( 'ChessViewerTrigger' );
		if ( $trigger ) {
			$out->addModules( 'ext.chessViewer' );
		}
	}
}

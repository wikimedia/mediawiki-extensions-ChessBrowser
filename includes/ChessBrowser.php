<?php

class ChessBrowser {
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'pgn', [ self::class, 'pollRender' ] );
	}

	public static function pollRender( $input, array $args, Parser $parser, PPFrame $frame ) {
		$parser->getOutput()->setExtensionData( 'ChessViewerTrigger', 'true' );
		$pgnParser = new PgnParser( $input );
		$ret = '<div class="pgn-source-wrapper"><div class="pgn-sourcegame">';
		$ret .= $pgnParser->parseMovetext();
		$ret .= '</div></div>';
		return $ret;
	}

	public static function onOutputPageParserOutput( OutputPage &$out, ParserOutput $parserOutput ) {
		$trigger = $parserOutput->getExtensionData( 'ChessViewerTrigger' );
		if ( $trigger ) {
			$out->addModules( 'ext.chessViewer' );
		}
	}
}

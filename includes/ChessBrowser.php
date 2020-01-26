<?php

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

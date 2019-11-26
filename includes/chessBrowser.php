<?php

use OutputPage;
use PPFrame;
use Skin;
use Parser;

class ChessBrowser {
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'pgn', [ self::class, 'pollRender' ] ); 
        #$parser->getOutput()->setExtensionData('ChessViewerTrigger','false');
		return true;
	}

	public static function pollRender( $input, array $args, Parser $parser, PPFrame $frame ) {
        $parser->getOutput()->setExtensionData('ChessViewerTrigger','true');
        $ret = '<div class="pgn-source-wrapper">';
        $ret .= '<div class="pgn-sourcegame">';
        $ret .= htmlspecialchars($input);
		$ret .= '</div>';
        $ret .= '</div>';
		return $ret;
	}

    public static function onOutputPageParserOutput( OutputPage &$out, ParserOutput $parserOutput ) {
        $out->addInlineScript('console.log("Outside php")');
        $trigger = $parserOutput->getExtensionData('ChessViewerTrigger');
        if ($trigger) {
            $out->addInlineScript('console.log("Inside php")');        
            $out->addModules('ext.chessViewer');
        }
    }
}

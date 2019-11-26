<?php
class Chess {
	static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'pgn', array( __CLASS__, 'pollRender' ) ); 
        #$parser->getOutput()->setExtensionData('ChessViewerTrigger','false');
		return true;
	}

	static function pollRender( $input, array $args, Parser $parser, PPFrame $frame ) {
        $parser->getOutput()->setExtensionData('ChessViewerTrigger','true');
        $ret = '<div class="pgn-source-wrapper">';
        $ret .= '<div class="pgn-sourcegame">';
        $ret .= $input;
		$ret .= '</div>';
        $ret .= '</div>';
		return $ret;
	}

    static function onOutputPageParserOutput( OutputPage &$out, ParserOutput $parserOutput ) {
        $out->addInlineScript('console.log("Outside php")');
        $trigger = $parserOutput->getExtensionData('ChessViewerTrigger');
        $out->addModules('ext.myExtension');
        if ($trigger) {
            $out->addInlineScript('console.log("Inside php")');        
            $out->addModules('ext.chessViewer');
        }
    }
}
$wgResourceModules['ext.myExtension'] = array(
	// JavaScript and CSS styles. To combine multiple files, just list them as an array.
	'scripts' => array( 'modules/ext.chessViewer.core.js' ),
	'styles' => 'modules/ext.chessViewer.css',
	
	// If your scripts need code from other modules, list their identifiers as dependencies.
	// Note that 'mediawiki' and 'jquery' are always available and cannot
	// be explicitly depended on.
	'dependencies' => array( 'oojs' ),
	
	// You need to declare the base path of the file paths in 'scripts' and 'styles'
	'localBasePath' => __DIR__,
	// ... and the base from the browser as well. For extensions this is made easy,
	// you can use the 'remoteExtPath' property to declare it relative to where the wiki
	// has $wgExtensionAssetsPath configured:
	'remoteExtPath' => 'MyExtension',
);
#$wgAutoloadClasses['Chess'] = $IP . '/extensions/Poll/poll_body.php';
$wgChessBrowser = new Chess();
$wgHooks['ParserFirstCallInit'][] = $wgChessBrowser;
$wgHooks['OutputPageParserOutput'][] = $wgChessBrowser;

<?php

class ChessJson {

	public const MOVE_FROM = 'from';
	public const MOVE_TO = 'to';
	public const MOVE_NOTATION = 'm';
	public const FEN = 'fen';
	public const MOVE_COMMENT = 'comment';
	public const MOVE_CLOCK = 'clk';
	public const MOVE_ACTIONS = 'actions';
	public const MOVE_VARIATIONS = 'variations';
	public const MOVE_MOVES = 'moves';
	public const MOVE_CAPTURE = 'capture';
	public const MOVE_PROMOTE_TO = 'promoteTo';
	public const MOVE_CASTLE = 'castle';
	public const MOVE_PARSED = 'castle';

	public const GAME_METADATA = 'metadata';
	public const GAME_EVENT = 'event';
	public const GAME_WHITE = 'white';
	public const GAME_BLACK = 'black';
	public const GAME_ECO = 'black';

	public const PGN_KEY_ACTION_ARROW = "ar";
	public const PGN_KEY_ACTION_HIGHLIGHT = "sq";

	public const PGN_KEY_ACTION_CLR_HIGHLIGHT = "csl";
	public const PGN_KEY_ACTION_CLR_ARROW = "cal";

	protected static $jsKeys = [ 'MOVE_FROM', 'MOVE_TO', 'MOVE_NOTATION', 'FEN','MOVE_COMMENT',
		'MOVE_ACTION', 'MOVE_VARIATIONS', 'MOVE_MOVES','MOVE_CAPTURE','MOVE_PROMOTE_TO','MOVE_CASTLE',
		'GAME_METADATA', 'GAME_EVENT', 'GAME_WHITE','GAME_BLACK', 'GAME_ECO',

	];

	public static function toJavascript() {
		$ret = [];
		foreach ( self::$jsKeys as $key ) {
			$ret[$key] = constant( "ChessJson::" . $key );
		}
		return 'ludo.ChessJson_KEY = ' . json_encode( $ret ) . ';';
	}
}

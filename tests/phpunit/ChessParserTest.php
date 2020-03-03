<?php
/**
 * @group ChessBrowser
 * @covers ChessParser
 */
class ChessParserTest extends MediaWikiTestCase {

	/**
	 * @var ChessParser $emptyChessParser
	 * An instance of the ChessParser class without any PGN input.
	 */
	private $emptyChessParser;

	protected function setUp() : void {
		parent::setUp();
		$this->emptyChessParser = new ChessParser();
	}

	/**
	 * @dataProvider provideCheckSpecialMove
	 * @param string $message
	 * @param array $data
	 * @param array $expected
	 */
	public function testCheckSpecialMove( $message, $data, $expected ) {
		$to = $data[0];
		$from = $data[1];
		$token = $data[2];
		$fenParts = $data[3];
		$special = $this->emptyChessParser->checkSpecialMove( $to, $from, $token, $fenParts );
		$this->assertEquals( $expected, $special, $message );
	}

	/**
	 * @dataProvider provideGetFenParts
	 * @param string $message
	 * @param string $fen
	 * @param array $expected
	 */
	public function testGetFenParts( $message, $fen, $expected ) {
		$fenParts = $this->emptyChessParser->getFenParts( $fen );
		$this->assertEquals( $expected, $fenParts );
	}

	public static function provideGetFenParts() {
		return [
			[
				'Initial board state without escaped slashes',
				'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
				[
					'toMove' => 'w',
					'enPassantTarget' => -1
				]
			],
			[
				'Initial board state with escaped slashes',
				'rnbqkbnr\/pppppppp\/8\/8\/8\/8\/PPPPPPPP\/RNBQKBNR w KQkq - 0 1',
				[
					'toMove' => 'w',
					'enPassantTarget' => -1
				]
			]
		];
	}

	public static function provideCheckSpecialMove() {
		return [
			[
				"en passant by white",
				[
					29,
					36,
					'exd6',
					[
						'toMove' => 'w',
						'enPassantTarget' => 29
					]
				],
				[ "en passant", 28 ]
			],
			[
				"white promotes to knight with no en passant target",
				[
					22,
					23,
					'c8=N+',
					[
						'toMove' => 'w',
						'enPassantTarget' => '-'
					]
				],
				[ "promotion", 'N' ]
			],
			[
				"black castles kingside with no en passant target",
				[
					39,
					55,
					'O-O',
					[
						'toMove' => 'b',
						'enPassantTarget' => '-'
					]
				],
				[ "castle", [ 63, 47 ] ]
			],
			[
				"white castles kingside with no en passant target",
				[
					32,
					48,
					'O-O',
					[
						'toMove' => 'w',
						'enPassantTarget' => '-'
					]
				],
				[ "castle", [ 56, 40 ] ]
			],
			[
				"pawn capture",
				[
					29,
					36,
					'exd6',
					[
						'toMove' => 'w',
						'enPassantTarget' => '-'
					]
				],
				[ "move", null ]
			]
		];
	}
}

<?php
/**
 * @group Chess
 * @covers PgnParser
 */
class PgnParserTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	/**
	 * @dataProvider provideCut
	 * @param string $text Input text
	 * @param integer $start Index to start cut
	 * @param integer $end Index to end cut
	 * @param string $expected String expected from cut
	 */
	public function testCut( $text, $start, $end, $expected ) {
		$pgnParser = new PgnParser($text);
		$this->assertEquals( $expected, $pgnParser->cut($start,$end));
	}

	/**
	 * @dataProvider provideInvalidCut
	 * @param string $text Input text
	 * @param integer $start Index to start cut
	 * @param integer $end Index to end cut
	 */
	public function testInvalidCut( $text, $start, $end ) {
		$pgnParser = new PgnParser($text);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage( 'End index is before start index.' );
		$pgnParser->cut($start,$end);
	}

	/**
	 * @dataProvider provideGetChar
	 * @param string $text Input text
	 * @param integer $index
	 * @param string $expected Character expected
	 */
	public function testGetChar( $text, $index, $expected ) {
		$pgnParser = new PgnParser($text);
		$this->assertEquals( $expected, $pgnParser->getChar($index));
	}

	/**
	 * @dataProvider provideInvalidGetChar
	 * @param string $text Input text
	 * @param integer $index
	 */
	public function testInvalidGetChar( $text, $index ) {
		$pgnParser = new PgnParser($text);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage( 'Invalid index' );
		$pgnParser->getChar($index);
	}

	/**
	 * @dataProvider provideGetChar
	 * @param string $text Input text
	 * @param integer $cursor
	 * @param string $expected Character expected
	 */
	public function testGetCharWithoutIndexWithCursor( $text, $cursor, $expected ) {
		$pgnParser = new PgnParser($text, $cursor);
		$this->assertEquals( $expected, $pgnParser->getChar());
	}

	/**
	 * @dataProvider provideEndOfGamePosition
	 * @param string $text Input text
	 * @param string $expected Character expected
	 */
	public function testEndOfGamePosition( $text, $expected ) {
		$pgnParser = new PgnParser($text);
		$this->assertEquals( $expected, $pgnParser->getChar($pgnParser->EOG));
	}

	/**
	 * @dataProvider provideVariation
	 * @param string $text Input text
	 * @param integer $index
	 * @param string $expected String expected
	 */
	public function testParseVariation( $text, $index, $expected ) {
		$pgnParser = new PgnParser($text);
		$this->assertEquals( $expected, $pgnParser->parseVariation($index));
	}

	/**
	 * @dataProvider provideInvalidVariation
	 * @param string $text Input text
	 * @param integer $index
	 */
	public function testInvalidParseVariation( $text, $index ) {
		$pgnParser = new PgnParser($text);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage( 'Variation does not terminate' );
		$pgnParser->parseVariation($index);
	}

	/**
	 * @dataProvider provideComment
	 * @param string $text Input text
	 * @param integer $index
	 * @param string $expected String expected
	 */
	public function testParseComment( $text, $index, $expected ) {
		$pgnParser = new PgnParser($text);
		$this->assertEquals( $expected, $pgnParser->parseComment($index));
	}

	/**
	 * @dataProvider provideInvalidComment
	 * @param string $text Input text
	 * @param integer $index
	 */
	public function testInvalidParseComment( $text, $index ) {
		$pgnParser = new PgnParser($text);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage( 'Comment not terminated' );
		$pgnParser->parseComment($index);
	}

	/**
	 * @dataProvider provideString
	 * @param string $text Input text
	 * @param integer $index
	 * @param string $expected String expected
	 */
	public function testParseString( $text, $index, $expected ) {
		$pgnParser = new PgnParser($text);
		$this->assertEquals( $expected, $pgnParser->parseString($index));
	}

	/**
	 * @dataProvider provideInvalidString
	 * @param string $text Input text
	 * @param integer $index
	 * @param string $message Expected error message
	 */
	public function testInvalidParseString( $text, $index, $message ) {
		$pgnParser = new PgnParser($text);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage( $message );
		$pgnParser->parseString($index);
	}

	/**
	 * @dataProvider provideNumericAnnotationGlyph
	 * @param string $text Input text
	 * @param integer $index
	 * @param string $expected String expected
	 */
	public function testParseNumericAnnotationGlyph( $text, $index, $expected ) {
		$pgnParser = new PgnParser($text);
		$this->assertEquals( $expected, $pgnParser->parseNumericAnnotationGlyph($index));
	}

	/**
	 * It may not actually be possible to throw the error in
	 * parseNumericAnnotationGlyph so no test is written for invalid NAGs yet
	 */

	/**
	 * @dataProvider provideStandardAlgebraicNotation
	 * @param string $text Input text
	 * @param integer $index
	 * @param string $expected String expected
	 */
	public function testParseStandardAlgebraicNotation( $text, $index, $expected ) {
		$pgnParser = new PgnParser($text);
		$this->assertEquals( $expected, $pgnParser->parseStandardAlgebraicNotation($index) );
	}

	/**
	 * parseStandardAlgebraicNotation does not throw any errors yet so no
	 * test is written for invalid SAN tokens.
	 */

	/**
	 * @dataProvider provideTagPair
	 * @param string $text Input text
	 * @param integer $index
	 * @param string $expected String expected
	 */
	public function testParseTagPair( $text, $index, $expected ) {
		$pgnParser = new PgnParser($text);
		$this->assertEquals( $expected, $pgnParser->parseTagPair($index));
	}

	/**
	 * @dataProvider provideInvalidTagPair
	 * @param string $text Input text
	 * @param integer $index
	 */
	public function testInvalidTagPair( $text, $index ) {
		$pgnParser = new PgnParser($text);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage( 'Tag does not terminate' );
		$pgnParser->parseTagPair($index);
	}

	/**
	 * @dataProvider provideCheckEscape
	 * @param string $text Input text
	 * @param integer $index
	 * @param bool $escaped Whether character is escaped or not
	 */
	public function testParseCheckEscape( $text, $index, $escaped ) {
		$pgnParser = new PgnParser($text);
		$result = $pgnParser->checkEscape($index);
		if ($escaped) {
			$this->assertTrue( $result );
		} else {
			$this->assertFalse( $result );
		}
	}

	/**
	 * @dataProvider providePgnGame
	 * @param string $text Input text
	 * @param string $expected String expected
	 */
	public function testParseGame( $text, $expected ) {
		$pgnParser = new PgnParser($text);
		$this->assertEquals( $expected, $pgnParser->parseMovetext());
	}

	public static function provideCut() {
		return [
			[
				"Lorem ipsum dolor sit amet",
				0,
				4,
				"Lorem"
			],
			[
				"Lorem ipsum dolor sit amet",
				5,
				5,
				" "
			]
		];
	}

	public static function provideInvalidCut() {
		return [
			[
				"Lorem ipsum dolor sit amet",
				7,
				5
			]
		];
	}

	public static function provideGetChar() {
		return [
			[
				"Lorem ipsum dolor sit amet",
				0,
				"L"
			],
			[
				"Lorem ipsum dolor sit amet",
				25,
				"t"
			],
			[
				"Lorem ipsum dolor sit amet",
				8,
				"s"
			]
		];
	}

	public static function provideInvalidGetChar() {
		return [
			[
				"Lorem ipsum dolor sit amet",
				-1
			],
			[
				"Lorem ipsum dolor sit amet",
				30
			]
		];
	}

	public static function provideEndOfGamePosition() {
		return [
			[
				"Lorem ipsum dolor sit amet",
				"t"
			],
			[
				"L",
				"L"
			]
		];
	}

	public static function provideVariation() {
		return [
			[
				"(2. e4 e5) 2... h6",
				0,
				"(2. e4 e5)"
			],
			[
				"(2... e5) 3. h3",
				0,
				"(2... e5)"
			]
		];
	}

	public static function provideInvalidVariation() {
		return [
			[
				"(2. e4 e5 2... h6",
				0
			],
			[
				"(2... e5 3. h3",
				0
			]
		];
	}

	public static function provideComment() {
		return [
			[
				"{Foo bar baz} 2... h6",
				0,
				"{Foo bar baz}"
			],
			[
				"(2... e5) {foo bar} 3. h3",
				10,
				"{foo bar}"
			]
		];
	}

	public static function provideInvalidComment() {
		return [
			[
				"{foo 2. e4 e5 3.h6",
				0
			],
			[
				"2... e5 {foo bar 3. h3",
				8
			]
		];
	}

	public static function provideString() {
		return [
			[
				'"Foo bar baz" ',
				0,
				'"Foo bar baz"'
			],
			[
				'(2... e5) "foo bar" 3. h3',
				10,
				'"foo bar"'
			]
		];
	}

	public static function provideInvalidString() {
		return [
			[
				'"Foo bar baz ',
				0,
				'String token starting at 0 not terminated'
			],
			[
				'(2... e5) "foo bar 3. h3',
				10,
				'String token starting at 10 not terminated'
			]
		];
	}

	public static function provideNumericAnnotationGlyph() {
		return [
			[
				'$123 3. e4',
				0,
				'$123'
			],
			[
				'(2... e5) $12 3. h3',
				10,
				'$12'
			]
		];
	}

	public static function provideStandardAlgebraicNotation() {
		return [
			[
				'3. e4 e5',
				0,
				'3'
			],
			[
				'3. e4 e5',
				3,
				'e4'
			],
			[
				'3. e4!! e5',
				3,
				'e4!!'
			],
			[
				'3. e8=Q+ Ka7',
				3,
				'e8=Q+'
			],
			[
				'25. e8=Q# 1-0',
				4,
				'e8=Q#'
			],
			[
				'25. e8=Q# 1-0',
				10,
				'1-0'
			],
			[
				'25. e8=Q 1/2-1/2',
				9,
				'1/2-1/2'
			],
			[
				'25. h3 e1=Q# 0-1',
				13,
				'0-1'
			]
		];
	}

	public static function provideTagPair() {
		return [
			[
				'[Event "Wikipedia Chess-a-thon"][ Date "2001.01.15" ]',
				0,
				['Event','Wikipedia Chess-a-thon']
			],
			[
				'[Foo "Bar"]' . "\n" . '[Biz "Baz"] 1.e4',
				12,
				['Biz','Baz']
			]
		];
	}

	public static function provideInvalidTagPair() {
		return [
			[
				'[Event "Wikipedia Chess-a-thon" 1.e4',
				0
			],
			[
				'[Foo "Bar"]' . "\n" . '[Biz "Baz" 1.e4',
				12
			]
		];
	}

	public static function provideCheckEscape() {
		return [
			[
				"{foo bar biz baz }",
				17,
				false
			],
			[
				"{foo bar \} biz baz }",
				10,
				true
			]
		];
	}

	public static function providePgnGame() {
		return [
					[
						'[Event "Berlin"]
						[Site "Berlin GER"]
						[EventDate "?"]
						[Round "?"]
						[Result "1-0"]
						[White "Adolf Anderssen"]
						[Black "Jean Dufresne"]
						[ECO "C52"]
						[WhiteElo "?"]
						[BlackElo "?"]
						[PlyCount "47"]
						[Date "1852.??.??"]

						1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 4.b4 Bxb4 5.c3 Ba5 6.d4 exd4 7.O-O
						d3 8.Qb3 Qf6 9.e5 Qg6 10.Re1 Nge7 11.Ba3 b5 12.Qxb5 Rb8 13.Qa4
						Bb6 14.Nbd2 Bb7 15.Ne4 Qf5 16.Bxd3 Qh5 17.Nf6+ gxf6 18.exf6
						Rg8 19.Rad1 Qxf3 20.Rxe7+ Nxe7 21.Qxd7+ Kxd7 22.Bf5+ Ke8
						23.Bd7+ Kf8 24.Bxe7# 1-0',
						'[Event "Berlin"]' . "\n"
						. '[Site "Berlin GER"]' . "\n"
						. '[Date "1852.??.??"]' . "\n"
						. '[Round "?"]' . "\n"
						. '[White "Adolf Anderssen"]' . "\n"
						. '[Black "Jean Dufresne"]' . "\n"
						. '[Result "1-0"]' . "\n"
						. '[BlackElo "?"]' . "\n"
						. '[ECO "C52"]' . "\n"
						. '[EventDate "?"]' . "\n"
						. '[PlyCount "47"]' . "\n"
						. '[WhiteElo "?"]' . "\n"
						. "\n"
						. '1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 4.b4 Bxb4 5.c3 Ba5 6.d4 exd4 7.O-O d3 8.Qb3 Qf6 9.e5' . "\n"
						. 'Qg6 10.Re1 Nge7 11.Ba3 b5 12.Qxb5 Rb8 13.Qa4 Bb6 14.Nbd2 Bb7 15.Ne4 Qf5 16.Bxd3 Qh5' . "\n"
						. '17.Nf6+ gxf6 18.exf6 Rg8 19.Rad1 Qxf3 20.Rxe7+ Nxe7 21.Qxd7+ Kxd7 22.Bf5+ Ke8 23.' . "\n"
						. 'Bd7+ Kf8 24.Bxe7# 1-0 '
				],
				[
						'[Event "Berlin"]' . "\n"
						. '[Site "Berlin GER"]' . "\n"
						. '[Date "1852.??.??"]' . "\n"
						. '[EventDate "?"]' . "\n"
						. '[Round "?"]' . "\n"
						. '[Result "1-0"]' . "\n"
						. '[White "Adolf Anderssen"]' . "\n"
						. '[Black "Jean Dufresne"]' . "\n"
						. '[ECO "C52"]' . "\n"
						. '[WhiteElo "?"]' . "\n"
						. '[BlackElo "?"]' . "\n"
						. '[PlyCount "47"]' . "\n"
						. '' . "\n"
						. '1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 4.b4 Bxb4 5.c3 Ba5 6.d4 exd4 7.O-O' . "\n"
						. 'd3 8.Qb3 Qf6 9.e5 Qg6 10.Re1 Nge7 11.Ba3 b5 12.Qxb5 Rb8 13.Qa4' . "\n"
						. 'Bb6 14.Nbd2 Bb7 15.Ne4 Qf5 16.Bxd3 Qh5 17.Nf6+ gxf6 18.exf6' . "\n"
						. 'Rg8 19.Rad1 Qxf3 20.Rxe7+ Nxe7 21.Qxd7+ Kxd7 22.Bf5+ Ke8' . "\n"
						. '23.Bd7+ Kf8 24.Bxe7# 1-0',
						'[Event "Berlin"]' . "\n"
						. '[Site "Berlin GER"]' . "\n"
						. '[Date "1852.??.??"]' . "\n"
						. '[Round "?"]' . "\n"
						. '[White "Adolf Anderssen"]' . "\n"
						. '[Black "Jean Dufresne"]' . "\n"
						. '[Result "1-0"]' . "\n"
						. '[BlackElo "?"]' . "\n"
						. '[ECO "C52"]' . "\n"
						. '[EventDate "?"]' . "\n"
						. '[PlyCount "47"]' . "\n"
						. '[WhiteElo "?"]' . "\n"
						. "\n"
						. '1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 4.b4 Bxb4 5.c3 Ba5 6.d4 exd4 7.O-O d3 8.Qb3 Qf6 9.e5' . "\n"
						. 'Qg6 10.Re1 Nge7 11.Ba3 b5 12.Qxb5 Rb8 13.Qa4 Bb6 14.Nbd2 Bb7 15.Ne4 Qf5 16.Bxd3 Qh5' . "\n"
						. '17.Nf6+ gxf6 18.exf6 Rg8 19.Rad1 Qxf3 20.Rxe7+ Nxe7 21.Qxd7+ Kxd7 22.Bf5+ Ke8 23.' . "\n"
						. 'Bd7+ Kf8 24.Bxe7# 1-0 '
				],
				[
						'[Event "Berlin"]' . "\n"
						. '[Site "Berlin GER"]' . "\n"
						. '[Date "1852.??.??"]' . "\n"
						. '[EventDate "?"]' . "\n"
						. '[Round "?"]' . "\n"
						. '[Result "1-0"]' . "\n"
						. '[White "Adolf Anderssen"]' . "\n"
						. '[Black "Jean Dufresne"]' . "\n"
						. '[ECO "C52"]' . "\n"
						. '[WhiteElo "?"]' . "\n"
						. '[BlackElo "?"]' . "\n"
						. '[PlyCount "47"]' . "\n"
						. '' . "\n"
						. '1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 4.b4 Bxb4 5.c3 Ba5 6.d4 exd4 7.O-O' . "\n"
						. 'd3 8.Qb3 Qf6 9.e5 {foo bar baz} Qg6 10.Re1 Nge7 11.Ba3 b5 12.Qxb5 Rb8 13.Qa4' . "\n"
						. 'Bb6 14.Nbd2 Bb7 15.Ne4 Qf5 16.Bxd3 Qh5 17.Nf6+ gxf6 18.exf6' . "\n"
						. 'Rg8 19.Rad1 Qxf3 20.Rxe7+ Nxe7 21.Qxd7+ Kxd7 22.Bf5+ Ke8' . "\n"
						. '23.Bd7+ Kf8 24.Bxe7# 1-0',
						'[Event "Berlin"]' . "\n"
						. '[Site "Berlin GER"]' . "\n"
						. '[Date "1852.??.??"]' . "\n"
						. '[Round "?"]' . "\n"
						. '[White "Adolf Anderssen"]' . "\n"
						. '[Black "Jean Dufresne"]' . "\n"
						. '[Result "1-0"]' . "\n"
						. '[BlackElo "?"]' . "\n"
						. '[ECO "C52"]' . "\n"
						. '[EventDate "?"]' . "\n"
						. '[PlyCount "47"]' . "\n"
						. '[WhiteElo "?"]' . "\n"
						. "\n"
						. '1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 4.b4 Bxb4 5.c3 Ba5 6.d4 exd4 7.O-O d3 8.Qb3 Qf6 9.e5' . "\n"
						. '{foo bar baz} Qg6 10.Re1 Nge7 11.Ba3 b5 12.Qxb5 Rb8 13.Qa4 Bb6 14.Nbd2 Bb7 15.Ne4' . "\n"
						. 'Qf5 16.Bxd3 Qh5 17.Nf6+ gxf6 18.exf6 Rg8 19.Rad1 Qxf3 20.Rxe7+ Nxe7 21.Qxd7+ Kxd7' . "\n"
						. '22.Bf5+ Ke8 23.Bd7+ Kf8 24.Bxe7# 1-0 '
				],
				[
						'[Event "Berlin"]' . "\n"
						. '[Site "Berlin GER"]' . "\n"
						. '[Date "1852.??.??"]' . "\n"
						. '[EventDate "?"]' . "\n"
						. '[Round "?"]' . "\n"
						. '[Result "1-0"]' . "\n"
						. '[White "Adolf Anderssen"]' . "\n"
						. '[Black "Jean Dufresne"]' . "\n"
						. '[ECO "C52"]' . "\n"
						. '[WhiteElo "?"]' . "\n"
						. '[BlackElo "?"]' . "\n"
						. '[PlyCount "47"]' . "\n"
						. '' . "\n"
						. '1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 4.b4 Bxb4 5.c3 Ba5 6.d4 (6.d3) exd4 7.O-O' . "\n"
						. 'd3 8.Qb3 Qf6 9.e5 Qg6 10.Re1 Nge7 11.Ba3 b5 12.Qxb5 Rb8 13.Qa4' . "\n"
						. 'Bb6 14.Nbd2 Bb7 15.Ne4 Qf5 16.Bxd3 Qh5 17.Nf6+ gxf6 18.exf6' . "\n"
						. 'Rg8 19.Rad1 Qxf3 20.Rxe7+ Nxe7 21.Qxd7+ Kxd7 22.Bf5+ Ke8' . "\n"
						. '23.Bd7+ Kf8 24.Bxe7# 1-0',
						'[Event "Berlin"]' . "\n"
						. '[Site "Berlin GER"]' . "\n"
						. '[Date "1852.??.??"]' . "\n"
						. '[Round "?"]' . "\n"
						. '[White "Adolf Anderssen"]' . "\n"
						. '[Black "Jean Dufresne"]' . "\n"
						. '[Result "1-0"]' . "\n"
						. '[BlackElo "?"]' . "\n"
						. '[ECO "C52"]' . "\n"
						. '[EventDate "?"]' . "\n"
						. '[PlyCount "47"]' . "\n"
						. '[WhiteElo "?"]' . "\n"
						. "\n"
						. '1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5 4.b4 Bxb4 5.c3 Ba5 6.d4 (6.d3) exd4 7.O-O d3 8.Qb3 Qf6' . "\n"
						. '9.e5 Qg6 10.Re1 Nge7 11.Ba3 b5 12.Qxb5 Rb8 13.Qa4 Bb6 14.Nbd2 Bb7 15.Ne4 Qf5 16.Bxd3' . "\n"
						. 'Qh5 17.Nf6+ gxf6 18.exf6 Rg8 19.Rad1 Qxf3 20.Rxe7+ Nxe7 21.Qxd7+ Kxd7 22.Bf5+ Ke8' . "\n"
						. '23.Bd7+ Kf8 24.Bxe7# 1-0 '
				]
		];
	}

}

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
 * @file ChessBrowserTest
 * @ingroup ChessBrowser
 * @author Wugapodes
 */

namespace MediaWiki\Extension\ChessBrowser\Tests;

use MediaWiki\Extension\ChessBrowser\ChessBrowser;
use MediaWiki\Extension\ChessBrowser\ChessBrowserException;
use MediaWikiIntegrationTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group ChessBrowser
 * @coversDefaultClass \MediaWiki\Extension\ChessBrowser\ChessBrowser
 */
class ChessBrowserTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::getLocalizedLabels
	 * @dataProvider provideGetLocalizedLabels
	 * @param array $expected
	 */
	public function testGetLocalizedLabels( array $expected ) {
		$labels = ChessBrowser::getLocalizedLabels();
		$this->assertEquals( $expected, $labels );
	}

	/**
	 * @covers ::getMetadata
	 * @dataProvider provideGetMetadata
	 * @param array $tagPairs
	 * @param array $expected
	 */
	public function testGetMetadata( array $tagPairs, array $expected ) {
		$labels = ChessBrowser::getMetadata( $tagPairs );
		$this->assertEquals( $expected, $labels );
	}

	/**
	 * @covers ::createPiece
	 * @dataProvider provideTestCreatePiece
	 * @param string $symbol
	 * @param int|string $rank
	 * @param int|string $file
	 * @param array $expected
	 */
	public function testCreatePiece( string $symbol, $rank, $file, array $expected ) {
		$piece = ChessBrowser::createPiece( $symbol, $rank, $file );
		$this->assertEquals( $expected, $piece );
	}

	/**
	 * @covers ::createPiece
	 * @dataProvider provideTestThrowsProperException
	 * @param string $expectedMessage
	 * @param string $symbol
	 * @param string|int $rank
	 * @param string|int $file
	 */
	public function testThrowsProperException(
		string $expectedMessage,
		string $symbol,
		$rank,
		$file
	) {
		$this->expectException( ChessBrowserException::class );
		$this->expectExceptionMessage( $expectedMessage );
		ChessBrowser::createPiece( $symbol, $rank, $file );
	}

	/**
	 * @covers ::assertValidPGN
	 * @dataProvider provideAssertValidPGN
	 * @param array $pgnLines
	 */
	public function testAssertValidPGN( array $pgnLines ) {
		$browser = new ChessBrowser();
		$browser = TestingAccessWrapper::newFromObject( $browser );
		$pgn = implode( "\n", $pgnLines );
		$pgnTest = $browser->assertValidPGN( $pgn );
		$this->assertNull( $pgnTest );
	}

	public static function provideTestThrowsProperException() {
		return [
			[ "Impossible rank (8) or file (0)", 'p', 8, 0 ],
			[ "Impossible rank (-1) or file (0)", 'p', -1, 0 ],
			[ "Impossible rank (0) or file (8)", 'p', 0, 8 ],
			[ "Impossible rank (0) or file (-1)", 'p', 0, -1 ],
			[ "Invalid piece type 0", '0', 0, 0 ],
		];
	}

	public static function provideGetMetadata() {
		return [
			[
				[
					'event' => 'Test match',
					'site' => 'Computer',
					'date' => '1852.??.??',
					'round' => '1',
					'white' => 'John Weiss',
					'black' => 'Jane Schwartz',
					'result' => '1-0',
					'whiteelo' => '1200',
					'blackelo' => '1201'
				],
				[
					'event' => 'Test match',
					'site' => 'Computer',
					'date' => '1852.??.??',
					'round' => '1',
					'white' => 'John Weiss',
					'black' => 'Jane Schwartz',
					'result' => '1-0',
					'other-metadata' => [
						[
							'label' => 'whiteelo',
							'value' => '1200'
						],
						[
							'label' => 'blackelo',
							'value' => '1201'
						]
					]
				]
			],
			[
				[
					'event' => 'Test match 2',
					'site' => 'Berlin',
					'date' => '1923.3.15',
					'round' => '2',
					'white' => 'Jack White',
					'black' => 'Jessica Blackmun',
					'result' => '1/2-1/2'
				],
				[
					'event' => 'Test match 2',
					'site' => 'Berlin',
					'date' => '1923.3.15',
					'round' => '2',
					'white' => 'Jack White',
					'black' => 'Jessica Blackmun',
					'result' => '1/2-1/2',
					'other-metadata' => []
				]
			]

		];
	}

	public static function provideTestCreatePiece() {
		return [
			[
				'p',
				0,
				1,
				[
					'piece-type' => 'p',
					'piece-color' => 'd',
					'piece-rank' => '0',
					'piece-file' => '1',
				]
			],
			[
				'p',
				'0',
				'1',
				[
					'piece-type' => 'p',
					'piece-color' => 'd',
					'piece-rank' => '0',
					'piece-file' => '1',
				]
			],
			[
				'P',
				'0',
				'1',
				[
					'piece-type' => 'p',
					'piece-color' => 'l',
					'piece-rank' => '0',
					'piece-file' => '1',
				]
			],
			[
				'N',
				1,
				1,
				[
					'piece-type' => 'n',
					'piece-color' => 'l',
					'piece-rank' => '1',
					'piece-file' => '1',
				]
			],
			[
				'q',
				'0',
				'1',
				[
					'piece-type' => 'q',
					'piece-color' => 'd',
					'piece-rank' => '0',
					'piece-file' => '1',
				]
			],
			[
				'b',
				'0',
				'1',
				[
					'piece-type' => 'b',
					'piece-color' => 'd',
					'piece-rank' => '0',
					'piece-file' => '1',
				]
			]
		];
	}

	public static function provideGetLocalizedLabels() {
		return [
			[
				[
					'expand-button' => 'Expand',
					'game-detail' => 'Game details',
					'event-label' => 'Event',
					'site-label' => 'Site',
					'date-label' => 'Date',
					'round-label' => 'Round',
					'white-label' => 'White',
					'black-label' => 'Black',
					'result-label' => 'Result',
					'rank-1' => '1',
					'rank-2' => '2',
					'rank-3' => '3',
					'rank-4' => '4',
					'rank-5' => '5',
					'rank-6' => '6',
					'rank-7' => '7',
					'rank-8' => '8',
					'a' => 'a',
					'b' => 'b',
					'c' => 'c',
					'd' => 'd',
					'e' => 'e',
					'f' => 'f',
					'g' => 'g',
					'h' => 'h',
					'no-javascript' => 'JavaScript is not enabled on this page. '
						. 'To view the game interactively, please enable JavaScript.'
				]
			]
		];
	}

	public static function provideAssertValidPGN() {
		return [
			[
				[
				'[Event "London Chess Classic 2016"]',
				'[Site "London"]',
				'[Date "2016.12.18"]',
				'[Round "9.1"]',
				'[White "So, Wesley"]',
				'[Black "Vachier-Lagrave, Maxime"]',
				'[Result "1/2-1/2"]',
				'[BlackElo "2804"]',
				'[WhiteElo "2794"]',
				'[LiveChessVersion "1.4.8"]',
				'[ECO "A04"]',
				'',
				'1. Nf3  c5  2. c4  Nc6',
				'3. Nc3  e5  4. e3',
				'Nf6  5. Be2  d5',
				'6. d4  cxd4  7. exd4  e4',
				'8. Ne5  dxc4  9. Bxc4',
				'Nxe5  10. dxe5  Qxd1+',
				'11. Kxd1  Ng4  12. e6',
				'fxe6  13. Nxe4  Bd7',
				'14. f3  Ne5  15. Bb3',
				'Rd8  16. Bd2  Nd3',
				'17. Kc2  Nb4+  18. Bxb4',
				'Bxb4  19. Nc3  Ke7',
				'20. Rhe1  Bxc3  21. Kxc3',
				'Rc8+  22. Kd2  Rhd8',
				'23. Ke3  e5  24. Rad1',
				'Bc6  25. h4  h6',
				'26. a3  Rxd1  27. Rxd1  Rf8',
				'28. Rf1  Rf4  29. g3',
				'Rd4  30. Rd1  Rxd1',
				'31. Bxd1  g5  32. hxg5',
				'hxg5  33. f4  gxf4+',
				'34. gxf4  exf4+  35. Kxf4',
				'1/2-1/2'
				]
			],
			[
				[
				'[Event "London Chess Classic 2016"]',
				'[Site "London"]',
				'[Date "2016.12.18"]',
				'[Round "9.1"]',
				'[White "So, Wesley"]',
				'[Black "Vachier-Lagrave, Maxime"]',
				'[Result "1/2-1/2"]',
				'[BlackElo "2804"]',
				'[WhiteElo "2794"]',
				'[LiveChessVersion "1.4.8"]',
				'[ECO "A04"]',
				'',
				'1. Nf3  c5  2. c4  Nc6',
				'3. Nc3  e5  4. e3',
				'Nf6  5. Be2  d5',
				'6. d4  cxd4  7. exd4  e4',
				'8. Ne5  dxc4  9. Bxc4',
				'Nxe5  10. dxe5  Qxd1+',
				'11. Kxd1  Ng4  12. e6',
				'fxe6  13. Nxe4  Bd7',
				'14. f3  Ne5  15. Bb3',
				'Rd8  16. Bd2  Nd3',
				'17. Kc2  Nb4+  18. Bxb4',
				'Bxb4  19. Nc3  Ke7',
				'20. Rhe1  Bxc3  21. Kxc3',
				'Rc8+  22. Kd2  Rhd8',
				'23. Ke3  e5  24. Rad1',
				'Bc6  25. h4  h6',
				'26. a3  Rxd1  27. Rxd1  Rf8',
				'28. Rf1  Rf4  29. g3',
				'Rd4  30. Rd1  Rxd1',
				'31. Bxd1  g5  32. hxg5',
				'hxg5  33. f4  gxf4+',
				'34. gxf4  exf4+  35. Kxf4',
				'1-0'
				]
			],
			[ [ 'e4' ] ]
		];
	}
}

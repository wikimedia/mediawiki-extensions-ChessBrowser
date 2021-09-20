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
 * @file FenParser0x88Test
 * @ingroup ChessBrowser
 * @author Wugapodes
 */

namespace MediaWiki\Extension\ChessBrowser\Tests\Unit;

use MediaWiki\Extension\ChessBrowser\ChessPiece;
use MediaWiki\Extension\ChessBrowser\PgnParser\FenParser0x88;
use MediaWikiUnitTestCase;
use ReflectionClass;

/**
 * @coversDefaultClass \MediaWiki\Extension\ChessBrowser\PgnParser\FenParser0x88
 */
class FenParser0x88Test extends MediaWikiUnitTestCase {

	/**
	 * @covers ::getNotationForAMove
	 */
	public function testGetNotationForAMove() {
		$fenParser = new FenParser0x88(
			'r1bq1rk1/pp1pppbp/4n1p1/2pNP3/3nQ1PP/5N2/PPPP1P2/R1B1KB1R b KQ - 2 11'
		);
		$mirror = new ReflectionClass( FenParser0x88::class );
		$method = $mirror->getMethod( 'getNotationForAMove' );
		$method->setAccessible( true );
		$move = [
			'from' => 'd4',
			'to' => 'f3'
		];
		$notation = $method->invokeArgs( $fenParser, [ $move ] );
		$this->assertSame( 'Nxf3', $notation );
	}

	/**
	 * @covers ::getCaptureAndProtectiveMoves
	 */
	public function testGetCountChecks() {
		$fenParser = new FenParser0x88(
			'r1bq1rk1/pp1pppbp/4n1p1/2pNP3/4Q1PP/5n2/PPPP1P2/R1B1KB1R w KQ - 0 12'
		);
		$this->assertSame( 'white', $fenParser->getColor() );
		$protectiveMoves = $fenParser->getCaptureAndProtectiveMoves( 'black' );

		$checks = $fenParser->getCountChecks( 'white', $protectiveMoves );
		$this->assertSame( 1, $checks );
	}

	/**
	 * @covers ::move
	 * @dataProvider provideMove
	 * @param string $fen
	 * @param mixed $move
	 * @param string $expected
	 */
	public function testMove( string $fen, $move, string $expected ) {
		$fenParser = new FenParser0x88( $fen );
		$mirror = new ReflectionClass( FenParser0x88::class );
		$fenParser->move( $move );
		$notationProperty = $mirror->getProperty( 'notation' );
		$notationProperty->setAccessible( true );
		$notation = $notationProperty->getValue( $fenParser );
		$this->assertSame( $expected, $notation );
	}

	public static function provideMove() {
		return [
			[
				'r1bq1rk1/pp1pppbp/4n1p1/2pNP3/3nQ1PP/5N2/PPPP1P2/R1B1KB1R b KQ - 2 11',
				'Nf3',
				'Nxf3+'
			],
			[
				'r1bq1rk1/pp1pppbp/4n1p1/2pNP3/3nQ1PP/5N2/PPPP1P2/R1B1KB1R b KQ - 2 11',
				[
					'from' => 'd4',
					'to' => 'f3'
				],
				'Nxf3+'
			],
			[
				'r4rk1/pp3pbp/6p1/2pB1n2/7P/2P5/PP1P1P2/R1B1K2R b KQ - 0 22',
				[
					'from' => 'a8',
					'to' => 'e8'
				],
				'Rae8+'
			],
			[
				'6k1/p6p/P7/7p/8/7r/1r6/2b2K2 b - - 2 54',
				'Rh1',
				'Rh1#'
			],
			[
				'1r4k1/p6p/3r4/P4B1p/5b2/8/2p5/4RK2 b - - 0 48',
				[
					'from' => 'c2',
					'to' => 'c1',
					'promoteTo' => 'q'
				],
				'c1=Q'
			],
			[
				'r1bqk2r/pp1pppbp/2n1n1p1/2p1P3/4Q1P1/2N2N2/PPPP1P1P/R1B1KB1R b KQkq - 6 9',
				'O-O',
				'O-O'
			],
			[
				'r1bqk2r/pp1pppbp/2n1n1p1/2p1P3/4Q1P1/2N2N2/PPPP1P1P/R1B1KB1R b KQkq - 6 9',
				[
					'from' => 'e8',
					'to' => 'g8'
				],
				'O-O'
			]
		];
	}

	/**
	 * @covers ::getFromAndToByNotation
	 * @dataProvider provideTestGetFromAndToByNotation
	 * @param string $fen
	 * @param string $move
	 * @param array $expected
	 */
	public function testGetFromAndToByNotation( string $fen, string $move, array $expected ) {
		$fenParser = new FenParser0x88( $fen );
		$moveOutput = $fenParser->getFromAndToByNotation( $move );
		$this->assertArrayEquals( $expected, $moveOutput );
	}

	public static function provideTestGetFromAndToByNotation() {
		return [
			[
				'r1bq1rk1/pp1pppbp/4n1p1/2pNP3/3nQ1PP/5N2/PPPP1P2/R1B1KB1R b KQ - 2 11',
				'Nf3',
				[
					'promoteTo' => '',
					'from' => 'd4',
					'to' => 'f3'
				]
			]
		];
	}

	/**
	 * @covers ::getValidMovesAndResult
	 * @dataProvider provideGetValidMovesAndResultCheckResults
	 * @param string $fen
	 * @param int $expected
	 */
	public function testGetValidMovesAndResultCheckResults(
		string $fen,
		int $expected
	) {
		$fenParser = new FenParser0x88( $fen );
		$config = $fenParser->getValidMovesAndResult();
		$checks = $config['check'];
		$this->assertSame( $expected, $checks );
	}

	public static function provideGetValidMovesAndResultCheckResults() {
		return [
			[
				'r1bq1rk1/pp1pppbp/4n1p1/2pNP3/3nQ1PP/5N2/PPPP1P2/R1B1KB1R b KQ - 2 11',
				0
			],
			[
				'r1bq1rk1/pp1pppbp/4n1p1/2pNP3/4Q1PP/5n2/PPPP1P2/R1B1KB1R w KQ - 0 12',
				1
			],
			[
				'2k1r2r/1p4p1/p5Np/5p2/b1P1P3/P1bPP1P1/4n1BP/2B2RK1 w - - 1 28',
				1
			],
			[
				'2k1r1r1/1B4p1/p5Np/5P2/b1P5/b2PP1P1/4n2P/1R5K b - - 0 32',
				1
			],
			[
				'8/3k4/7b/8/2n5/8/3K4/8 w - - 0 1',
				2
			],
			[
				'8/3k4/8/8/2n5/8/3K4/8 w - - 0 1',
				1
			],
			[
				'8/3k4/8/4n3/8/8/3K4/8 w - - 0 1',
				0
			],
			[
				'8/8/8/8/8/1k6/8/1K2q3 w - - 0 1',
				1
			]
		];
	}

	/**
	 * @covers ::getValidMovesAndResult
	 * @dataProvider provideGetValidMovesAndResults
	 * @param string $fen
	 * @param array $expected
	 */
	public function testGetValidMovesAndResults(
		string $fen,
		array $expected
	) {
		$fenParser = new FenParser0x88( $fen );
		$config = $fenParser->getValidMovesAndResult();
		$this->assertSame( $expected, $config );
	}

	public static function provideGetValidMovesAndResults() {
		return [
			[
				'r5k1/P7/8/8/7P/2P5/PP1P1P2/R1B1K2R b KQ - 2 11',
				[
				'moves' => [
					112 => [ 113, 114, 115, 116, 117, 96 ],
					118 => [ 101, 102, 103, 117, 119 ]
				],
				'result' => 0,
				'check' => 0
				]
			],
			[
				'r1bq1rk1/pp1pppbp/4n1p1/2pNP3/3nQ1PP/5N2/PPPP1P2/R1B1KB1R b KQ - 2 11',
				[
					'moves' => [
						112 => [ 113 ],
						114 => [],
						115 => [ 98, 81, 64, 116 ],
						117 => [ 116 ],
						118 => [ 119 ],
						96 => [ 80, 64 ],
						97 => [ 81, 65 ],
						99 => [ 83 ],
						100 => [],
						101 => [ 85, 69 ],
						102 => [ 87, 85, 68, 119 ],
						103 => [ 87, 71 ],
						84 => [ 53, 70, 98 ],
						86 => [ 70 ],
						66 => [ 50 ],
						51 => [ 18, 20, 33, 37, 65, 69, 82 ]
					],
					'result' => 0,
					'check' => 0
				]
			],
			[
				'6k1/8/8/3NP3/3nQ1PP/5N2/PPPP1P2/R1B1KB1R b KQ - 2 11',
				[
					'moves' => [
						118 => [ 101, 102, 117, 119 ],
						51 => [ 18, 20, 33, 37, 65, 69, 82, 84 ]
					],
					'result' => 0,
					'check' => 0
				]
			],
			[
				'4k2r/8/8/4P3/4Q1P1/2N2N2/PPPP1P1P/R1B1KB1R b KQk - 6 9',
				[
					'moves' => [
						116 => [ 99, 100, 101, 115, 117, 118 ],
						119 => [ 118, 117, 103, 87, 71, 55, 39, 23 ]
					],
					'result' => 0,
					'check' => 0
				]
			],
			[
				'r1bqk2r/pp1pppbp/2n1n1p1/2p1P3/4Q1P1/2N2N2/PPPP1P1P/R1B1KB1R b KQkq - 6 9',
				[
					'moves' => [
						112 => [ 113 ],
						114 => [],
						115 => [ 98, 81, 64 ],
						116 => [ 117, 118 ],
						119 => [ 118, 117 ],
						96 => [ 80, 64 ],
						97 => [ 81, 65 ],
						99 => [ 83, 67 ],
						100 => [],
						101 => [ 85, 69 ],
						102 => [ 87, 85, 68, 117 ],
						103 => [ 87, 71 ],
						82 => [ 49, 51, 64, 68, 113 ],
						84 => [ 51, 53, 70, 98, 117 ],
						86 => [ 70 ],
						66 => [ 50 ],
					],
					'result' => 0,
					'check' => 0
				]
			]
		];
	}

	/**
	 * @covers ::getValidMovePathsForPiece
	 * @dataProvider provideGetValidMovePathsForPiece
	 */
	public function testGetValidMovePathsForPiece(
		$fen,
		$args,
		$expected
	) {
		$fenParser = new FenParser0x88(
			$fen
		);
		$mirror = new ReflectionClass( FenParser0x88::class );
		$method = $mirror->getMethod( 'getValidMovePathsForPiece' );
		$method->setAccessible( true );
		$path = $method->invokeArgs( $fenParser, $args );
		$this->assertSame( $expected, $path );
	}

	public static function provideGetValidMovePathsForPiece() {
		return [
			[
				'r5k1/P7/8/8/7P/2P5/PP1P1P2/R1B1K2R b KQ - 2 11',
				[
					[
						't' => ChessPiece::BLACK_ROOK,
						's' => 0x70
					],
					[],
					false,
					'',
					false,
					false,
					null
				],
				[ 0x71, 0x72, 0x73, 0x74, 0x75, 0x60 ]
			],
			[
				'r5k1/P7/8/8/7P/2P5/PP1P1P2/R1B1K2R w KQ - 2 11',
				[
					[
						't' => ChessPiece::WHITE_PAWN,
						's' => 0x37
					],
					[],
					true,
					'',
					false,
					false,
					null
				],
				[ 0x47 ]
			],
			[
				'rnbqkbnr/3p4/ppp2p1p/8/8/PPP2P1P/3P4/RNBQKBNR b - - 0 1',
				[
					[
						't' => ChessPiece::BLACK_KNIGHT,
						's' => 0x71
					],
					[],
					false,
					'',
					false,
					false,
					null
				],
				[]
			],
			[
				'rnbqkbnr/3p4/ppp2p1p/8/8/PPP2P1P/3P4/RNBQKBNR b - - 0 1',
				[
					[
						't' => ChessPiece::BLACK_KNIGHT,
						's' => 0x76
					],
					[],
					false,
					'',
					false,
					false,
					null
				],
				[ 0x64 ]
			],
			[
				'rnbqkbnr/3p4/ppp2p1p/8/8/PPP2P1P/3P4/RNBQKBNR b - - 0 1',
				[
					[
						't' => ChessPiece::BLACK_BISHOP,
						's' => 0x72
					],
					[],
					false,
					'',
					false,
					false,
					null
				],
				[ 0x61 ]
			],
			[
				'rnbqkbnr/3p4/ppp2p1p/8/8/PPP2P1P/3P4/RNBQKBNR b - - 0 1',
				[
					[
						't' => ChessPiece::BLACK_BISHOP,
						's' => 0x75
					],
					[],
					false,
					'',
					false,
					false,
					null
				],
				[ 0x66, 0x64, 0x53, 0x42, 0x31, 0x20 ]
			],
			[
				'rnbqkbnr/3p4/ppp2p1p/8/8/PPP2P1P/3P4/RNBQKBNR b - - 0 1',
				[
					[
						't' => ChessPiece::BLACK_QUEEN,
						's' => 0x73
					],
					[],
					false,
					'',
					false,
					false,
					null
				],
				[ 0x64, 0x62 ]
			],
			[
				'rnbqkbnr/3p4/ppp2p1p/8/8/PPP2P1P/3P4/RNBQKBNR b - - 0 1',
				[
					[
						't' => ChessPiece::BLACK_KING,
						's' => 0x74
					],
					[],
					false,
					'',
					false,
					false,
					null
				],
				[ 0x64, 0x65 ]
			],
			[
				'rnbqkbnr/3p4/ppp2p1p/8/8/PPP2P1P/3P4/RNBQKBNR w - - 0 1',
				[
					[
						't' => ChessPiece::WHITE_KING,
						's' => 0x04
					],
					[],
					true,
					'',
					false,
					false,
					null
				],
				[ 0x14, 0x15 ]
			],
			[
				'rnbqkbnr/3p4/ppp2p1p/8/8/PPP2P1P/3P4/RNBQKBNR w - - 0 1',
				[
					[
						't' => ChessPiece::WHITE_KNIGHT,
						's' => 0x01
					],
					[],
					true,
					'',
					false,
					false,
					null
				],
				[]
			],
			[
				'rnbqkbnr/3p4/ppp2p1p/8/8/PPP2P1P/3P4/RNBQKBNR w - - 0 1',
				[
					[
						't' => ChessPiece::WHITE_KNIGHT,
						's' => 0x06
					],
					[],
					true,
					'',
					false,
					false,
					null
				],
				[ 0x14 ]
			],
			[
				'rnbqkbnr/3p4/ppp2p1p/8/8/PPP2P1P/3P4/RNBQKBNR w - - 0 1',
				[
					[
						't' => ChessPiece::WHITE_BISHOP,
						's' => 0x02
					],
					[],
					true,
					'',
					false,
					false,
					null
				],
				[ 0x11 ]
			],
			[
				'rnbqkbnr/3p4/ppp2p1p/8/8/PPP2P1P/3P4/RNBQKBNR w - - 0 1',
				[
					[
						't' => ChessPiece::WHITE_BISHOP,
						's' => 0x05
					],
					[],
					true,
					'',
					false,
					false,
					null
				],
				[ 0x14, 0x23, 0x32, 0x41, 0x50, 0x16 ]
			],
			[
				'rnbqkbnr/3p4/ppp2p1p/8/8/PPP2P1P/3P4/RNBQKBNR w - - 0 1',
				[
					[
						't' => ChessPiece::WHITE_QUEEN,
						's' => 0x03
					],
					[],
					true,
					'',
					false,
					false,
					null
				],
				[ 0x12, 0x14 ]
			],
			[
				'r3k2r/8/8/8/8/8/8/R3K2R b KQkq - 0 1',
				[
					[
						't' => ChessPiece::BLACK_KING,
						's' => 0x74
					],
					[],
					false,
					'',
					true,
					true,
					null
				],
				[ 0x63, 0x64, 0x65, 0x73, 0x75, 0x76, 0x72 ]
			],
			[
				'r3k2r/8/8/8/8/8/8/R3K2R w KQkq - 0 1',
				[
					[
						't' => ChessPiece::WHITE_KING,
						's' => 0x04
					],
					[],
					true,
					'',
					true,
					true,
					null
				],
				[ 0x03, 0x05, 0x13, 0x14, 0x15, 0x06, 0x02 ]
			],
		];
	}
}

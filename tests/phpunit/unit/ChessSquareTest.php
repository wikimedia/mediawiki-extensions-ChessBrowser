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
 * @file ChessSquareTest
 * @ingroup ChessBrowser
 * @author DannyS712
 */

namespace MediaWiki\Extension\ChessBrowser\Tests\Unit;

use MediaWiki\Extension\ChessBrowser\ChessBrowserException;
use MediaWiki\Extension\ChessBrowser\ChessSquare;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\ChessBrowser\ChessSquare
 */
class ChessSquareTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::newFromCoords
	 * @covers ::getNumber
	 * @dataProvider provideCoordsAndNumbers
	 * @param string $coords
	 * @param int $expectedNumber
	 */
	public function testCoordsToNumber( string $coords, int $expectedNumber ) {
		$square = ChessSquare::newFromCoords( $coords );
		$this->assertNotFalse( $square );
		$number = $square->getNumber();
		$this->assertSame( $expectedNumber, $number );
	}

	/**
	 * @covers ::newFromNumber
	 * @covers ::getCoords
	 * @dataProvider provideCoordsAndNumbers
	 * @param string $expectedCoords
	 * @param int $number
	 */
	public function testNumberToCoords( string $expectedCoords, int $number ) {
		$square = ChessSquare::newFromNumber( $number );
		$coords = $square->getCoords();
		$this->assertSame( $expectedCoords, $coords );
	}

	public static function provideCoordsAndNumbers() {
		return [
			[ 'a1', 0 ],
			[ 'b1', 1 ],
			[ 'c1', 2 ],
			[ 'd1', 3 ],
			[ 'e1', 4 ],
			[ 'f1', 5 ],
			[ 'g1', 6 ],
			[ 'h1', 7 ],
			[ 'a2', 16 ],
			[ 'b2', 17 ],
			[ 'c2', 18 ],
			[ 'd2', 19 ],
			[ 'e2', 20 ],
			[ 'f2', 21 ],
			[ 'g2', 22 ],
			[ 'h2', 23 ],
			[ 'a3', 32 ],
			[ 'b3', 33 ],
			[ 'c3', 34 ],
			[ 'd3', 35 ],
			[ 'e3', 36 ],
			[ 'f3', 37 ],
			[ 'g3', 38 ],
			[ 'h3', 39 ],
			[ 'a4', 48 ],
			[ 'b4', 49 ],
			[ 'c4', 50 ],
			[ 'd4', 51 ],
			[ 'e4', 52 ],
			[ 'f4', 53 ],
			[ 'g4', 54 ],
			[ 'h4', 55 ],
			[ 'a5', 64 ],
			[ 'b5', 65 ],
			[ 'c5', 66 ],
			[ 'd5', 67 ],
			[ 'e5', 68 ],
			[ 'f5', 69 ],
			[ 'g5', 70 ],
			[ 'h5', 71 ],
			[ 'a6', 80 ],
			[ 'b6', 81 ],
			[ 'c6', 82 ],
			[ 'd6', 83 ],
			[ 'e6', 84 ],
			[ 'f6', 85 ],
			[ 'g6', 86 ],
			[ 'h6', 87 ],
			[ 'a7', 96 ],
			[ 'b7', 97 ],
			[ 'c7', 98 ],
			[ 'd7', 99 ],
			[ 'e7', 100 ],
			[ 'f7', 101 ],
			[ 'g7', 102 ],
			[ 'h7', 103 ],
			[ 'a8', 112 ],
			[ 'b8', 113 ],
			[ 'c8', 114 ],
			[ 'd8', 115 ],
			[ 'e8', 116 ],
			[ 'f8', 117 ],
			[ 'g8', 118 ],
			[ 'h8', 119 ]
		];
	}

	/**
	 * @covers ::newFromCoords
	 * @dataProvider provideTestThrowsProperException
	 * @param string $coords
	 * @param string $expectedMessage
	 */
	public function testNewFromCoords_bad(
		string $coords,
		string $expectedMessage
	) {
		$this->expectException( ChessBrowserException::class );
		$this->expectExceptionMessage( $expectedMessage );
		ChessSquare::newFromCoords( $coords );
	}

	public static function provideTestThrowsProperException() {
		return [
			[ 'xyz', 'Coordinates (xyz) too long' ],
			[ 'q1', 'No such file: q' ]
		];
	}

	/**
	 * @covers ::newFromCoords
	 */
	public function testNewFromCoords_good() {
		$result = ChessSquare::newFromCoords( 'f3' );
		$this->assertInstanceOf(
			ChessSquare::class,
			$result
		);
	}

	public static function convert64( int $num ) {
		$file = ( $num - $num % 8 ) / 8;
		$rank = 1 + $num % 8;
		return $file + 8 * ( $rank - 1 );
	}

	/**
	 * @covers ::newFromLateral64
	 */
	public function test64() {
		foreach ( range( 0, 63 ) as $num ) {
			$square = ChessSquare::newFromLateral64( $num );
			$this->assertSame(
				$this->convert64( $num ),
				$square->getAsVertical64()
			);
		}
	}

}

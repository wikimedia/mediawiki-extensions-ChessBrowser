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
 * @file SquareRelationsTest
 * @ingroup ChessBrowser
 * @author Wugapodes
 */

namespace MediaWiki\Extension\ChessBrowser\Tests\Unit;

use MediaWiki\Extension\ChessBrowser\SquareRelations;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\ChessBrowser\SquareRelations
 */
class SquareRelationsTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::getDistance
	 * @dataProvider provideGetDistance
	 * @param int $sq1
	 * @param int $sq2
	 * @param int $expected
	 * @param string $message
	 */
	public function testGetDistance( int $sq1, int $sq2, int $expected, string $message ) {
		$dist = SquareRelations::new( $sq1, $sq2 )->getDistance();
		$this->assertEquals( $expected, $dist, $message );
	}

	public static function provideGetDistance() {
		return [
			[ 0x00, 0x01, 1, 'Test a1 to b1 has distance 1.' ],
			[ 0x00, 0x02, 2, 'Test a1 to c1 has distance 2.' ],
			[ 0x00, 0x07, 7, 'Test a1 to h1 has distance 7.' ],
			[ 0x00, 0x77, 7, 'Test a1 to h8 has distance 7.' ],
			[ 0x40, 0x07, 7, 'Test a5 to h1 has distance 7.' ],
			[ 0x40, 0x00, 4, 'Test a5 to a1 has distance 4.' ],
			[ 0x47, 0x00, 7, 'Test h5 to a1 has distance 7.' ],
			[ 0x47, 0x07, 4, 'Test h5 to h1 has distance 4.' ],
			[ 0x42, 0x34, 2, 'Test b5 to d4 has distance 2.' ],
			[ 0x43, 0x54, 1, 'Test d5 to e6 has distance 1.' ],
			[ 0x01, 0x22, 2, 'Test b1 to c3 has distance 2.' ],
			[ 0x70, 0x07, 7, 'Test a8 to h1 has distance 7.' ]
		];
	}

	/**
	 * @covers ::haveSameRank
	 * @dataProvider provideSameRank
	 * @param int $sq1
	 * @param int $sq2
	 * @param bool $expected
	 * @param string $message
	 */
	public function testhaveSameRank( int $sq1, int $sq2, bool $expected, string $message ) {
		$rankBool = SquareRelations::new( $sq1, $sq2 )->haveSameRank();
		$this->assertEquals( $expected, $rankBool, $message );
	}

	public static function provideSameRank() {
		return [
			[ 0x00, 0x07, true, 'Test a1 and h1 on same rank.' ],
			[ 0x70, 0x77, true, 'Test a8 and h8 on same rank.' ],
			[ 0x50, 0x5C, true, 'Test a6 and off-board square on same rank.' ],
			[ 0x00, 0x70, false, 'Test a1 and a8 not on same rank.' ]
		];
	}

	/**
	 * @covers ::haveSameFile
	 * @dataProvider provideSameFile
	 * @param int $sq1
	 * @param int $sq2
	 * @param bool $expected
	 * @param string $message
	 */
	public function testhaveSameFile( int $sq1, int $sq2, bool $expected, string $message ) {
		$rankBool = SquareRelations::new( $sq1, $sq2 )->haveSameFile();
		$this->assertEquals( $expected, $rankBool, $message );
	}

	public static function provideSameFile() {
		return [
			[ 0x00, 0x70, true, 'Test a1 and a8 on same file.' ],
			[ 0x07, 0x77, true, 'Test h1 and h8 on same file.' ],
			[ 0x00, 0x07, false, 'Test a1 and a8 not on same file.' ]
		];
	}
}

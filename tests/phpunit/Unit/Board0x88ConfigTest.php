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
 * @file Board0x88ConfigTest
 * @ingroup ChessBrowser
 * @author Wugapodes
 *
 * @covers Board0x88Config
 */
class Board0x88ConfigTest extends \MediaWikiUnitTestCase {

	/**
	 * @dataProvider provideMapNumber
	 * @param int $from
	 * @param int $expectedTo
	 */
	public function testMapNumber( int $from, int $expectedTo ) {
		$this->assertSame( $expectedTo, Board0x88Config::mapNumber( $from ) );
	}

	public static function provideMapNumber() {
		return [
			[ 0, 0 ],
			[ 1, 1 ],
			[ 2, 2 ],
			[ 3, 3 ],
			[ 4, 4 ],
			[ 5, 5 ],
			[ 6, 6 ],
			[ 7, 7 ],
			[ 8, 16 ],
			[ 9, 17 ],
			[ 10, 18 ],
			[ 11, 19 ],
			[ 12, 20 ],
			[ 13, 21 ],
			[ 14, 22 ],
			[ 15, 23 ],
			[ 16, 32 ],
			[ 17, 33 ],
			[ 18, 34 ],
			[ 19, 35 ],
			[ 20, 36 ],
			[ 21, 37 ],
			[ 22, 38 ],
			[ 23, 39 ],
			[ 24, 48 ],
			[ 25, 49 ],
			[ 26, 50 ],
			[ 27, 51 ],
			[ 28, 52 ],
			[ 29, 53 ],
			[ 30, 54 ],
			[ 31, 55 ],
			[ 32, 64 ],
			[ 33, 65 ],
			[ 34, 66 ],
			[ 35, 67 ],
			[ 36, 68 ],
			[ 37, 69 ],
			[ 38, 70 ],
			[ 39, 71 ],
			[ 40, 80 ],
			[ 41, 81 ],
			[ 42, 82 ],
			[ 43, 83 ],
			[ 44, 84 ],
			[ 45, 85 ],
			[ 46, 86 ],
			[ 47, 87 ],
			[ 48, 96 ],
			[ 49, 97 ],
			[ 50, 98 ],
			[ 51, 99 ],
			[ 52, 100 ],
			[ 53, 101 ],
			[ 54, 102 ],
			[ 55, 103 ],
			[ 56, 112 ],
			[ 57, 113 ],
			[ 58, 114 ],
			[ 59, 115 ],
			[ 60, 116 ],
			[ 61, 117 ],
			[ 62, 118 ],
			[ 63, 119 ]
		];
	}

}

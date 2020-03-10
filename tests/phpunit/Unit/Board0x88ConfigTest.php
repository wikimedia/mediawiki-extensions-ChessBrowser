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

	public function testGetDefaultBoard() {
		$expectedBoard = [
			0 => 0,
			1 => 0,
			2 => 0,
			3 => 0,
			4 => 0,
			5 => 0,
			6 => 0,
			7 => 0,
			8 => 0,
			9 => 0,
			10 => 0,
			11 => 0,
			12 => 0,
			13 => 0,
			14 => 0,
			15 => 0,
			16 => 0,
			17 => 0,
			18 => 0,
			19 => 0,
			20 => 0,
			21 => 0,
			22 => 0,
			23 => 0,
			24 => 0,
			25 => 0,
			26 => 0,
			27 => 0,
			28 => 0,
			29 => 0,
			30 => 0,
			31 => 0,
			32 => 0,
			33 => 0,
			34 => 0,
			35 => 0,
			36 => 0,
			37 => 0,
			38 => 0,
			39 => 0,
			40 => 0,
			41 => 0,
			42 => 0,
			43 => 0,
			44 => 0,
			45 => 0,
			46 => 0,
			47 => 0,
			48 => 0,
			49 => 0,
			50 => 0,
			51 => 0,
			52 => 0,
			53 => 0,
			54 => 0,
			55 => 0,
			56 => 0,
			57 => 0,
			58 => 0,
			59 => 0,
			60 => 0,
			61 => 0,
			62 => 0,
			63 => 0,
			64 => 0,
			65 => 0,
			66 => 0,
			67 => 0,
			68 => 0,
			69 => 0,
			70 => 0,
			71 => 0,
			72 => 0,
			73 => 0,
			74 => 0,
			75 => 0,
			76 => 0,
			77 => 0,
			78 => 0,
			79 => 0,
			80 => 0,
			81 => 0,
			82 => 0,
			83 => 0,
			84 => 0,
			85 => 0,
			86 => 0,
			87 => 0,
			88 => 0,
			89 => 0,
			90 => 0,
			91 => 0,
			92 => 0,
			93 => 0,
			94 => 0,
			95 => 0,
			96 => 0,
			97 => 0,
			98 => 0,
			99 => 0,
			100 => 0,
			101 => 0,
			102 => 0,
			103 => 0,
			104 => 0,
			105 => 0,
			106 => 0,
			107 => 0,
			108 => 0,
			109 => 0,
			110 => 0,
			111 => 0,
			112 => 0,
			113 => 0,
			114 => 0,
			115 => 0,
			116 => 0,
			117 => 0,
			118 => 0,
			119 => 0
		];

		$actualBoard = Board0x88Config::getDefaultBoard();
		$this->assertArrayEquals(
			$expectedBoard,
			$actualBoard,
			'The getDefaultBoard function returns the same array that was hardcoded'
		);
	}

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

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
	 * @dataProvider provideMapSquareToNumber
	 * @param string $square
	 * @param int $expected
	 */
	public function testMapSquareToNumber( string $square, int $expected ) {
		$number = Board0x88Config::mapSquareToNumber( $square );
		$this->assertEquals( $expected, $number );
	}

	public static function provideMapSquareToNumber() {
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
}

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
 * This file is a part of PgnParser
 *
 * PgnParser is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @file Board0x88Config
 * @ingroup ChessBrowser
 * @author Alf Magne Kalleland
 */

class Board0x88Config {

	public static $fenSquares = [
		'a8', 'b8', 'c8', 'd8', 'e8', 'f8', 'g8', 'h8',
		'a7', 'b7', 'c7', 'd7', 'e7', 'f7', 'g7', 'h7',
		'a6', 'b6', 'c6', 'd6', 'e6', 'f6', 'g6', 'h6',
		'a5', 'b5', 'c5', 'd5', 'e5', 'f5', 'g5', 'h5',
		'a4', 'b4', 'c4', 'd4', 'e4', 'f4', 'g4', 'h4',
		'a3', 'b3', 'c3', 'd3', 'e3', 'f3', 'g3', 'h3',
		'a2', 'b2', 'c2', 'd2', 'e2', 'f2', 'g2', 'h2',
		'a1', 'b1', 'c1', 'd1', 'e1', 'f1', 'g1', 'h1'
	];

	// intval( $num ) <= 8
	public static $numbers = [
		'0' => 1,
		'1' => 1,
		'2' => 1,
		'3' => 1,
		'4' => 1,
		'5' => 1,
		'6' => 1,
		'7' => 1,
		'8' => 1,
		'9' => 0
	];

}

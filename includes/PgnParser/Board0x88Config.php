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

// TODO figure out where these are used / why these are public, and clean up duplication
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

	/**
	 * TODO find a better name for this
	 *
	 * @param int $number
	 * @return int
	 */
	public static function mapNumber( $number ) : int {
		$ret = ( floor( $number / 8 ) * 16 ) + ( $number % 8 );
		return $ret;
	}

	// array_map( function( $key ) { return ",$key,"; }, array_values( $mapping ) )
	public static $keySquares = [
		',0,',
		',1,',
		',2,',
		',3,',
		',4,',
		',5,',
		',6,',
		',7,',
		',8,',
		',9,',
		',10,',
		',11,',
		',12,',
		',13,',
		',14,',
		',15,',
		',16,',
		',17,',
		',18,',
		',19,',
		',20,',
		',21,',
		',22,',
		',23,',
		',24,',
		',25,',
		',26,',
		',27,',
		',28,',
		',29,',
		',30,',
		',31,',
		',32,',
		',33,',
		',34,',
		',35,',
		',36,',
		',37,',
		',38,',
		',39,',
		',40,',
		',41,',
		',42,',
		',43,',
		',44,',
		',45,',
		',46,',
		',47,',
		',48,',
		',49,',
		',50,',
		',51,',
		',52,',
		',53,',
		',54,',
		',55,',
		',56,',
		',57,',
		',58,',
		',59,',
		',60,',
		',61,',
		',62,',
		',63,',
		',64,',
		',65,',
		',66,',
		',67,',
		',68,',
		',69,',
		',70,',
		',71,',
		',72,',
		',73,',
		',74,',
		',75,',
		',76,',
		',77,',
		',78,',
		',79,',
		',80,',
		',81,',
		',82,',
		',83,',
		',84,',
		',85,',
		',86,',
		',87,',
		',88,',
		',89,',
		',90,',
		',91,',
		',92,',
		',93,',
		',94,',
		',95,',
		',96,',
		',97,',
		',98,',
		',99,',
		',100,',
		',101,',
		',102,',
		',103,',
		',104,',
		',105,',
		',106,',
		',107,',
		',108,',
		',109,',
		',110,',
		',111,',
		',112,',
		',113,',
		',114,',
		',115,',
		',116,',
		',117,',
		',118,',
		',119,'
	];

	// array_flip( $pieces )
	public static $pieceMapping = [
		0x01 => 'P',
		0x02 => 'N',
		0x03 => 'K',
		0x05 => 'B',
		0x06 => 'R',
		0x07 => 'Q',
		0x09 => 'p',
		0x0A => 'n',
		0x0B => 'k',
		0x0D => 'b',
		0x0E => 'r',
		0x0F => 'q'
	];

	public static $notationMapping = [
		0x01 => '',
		0x02 => 'N',
		0x03 => 'K',
		0x05 => 'B',
		0x06 => 'R',
		0x07 => 'Q',
		0x09 => '',
		0x0A => 'N',
		0x0B => 'K',
		0x0D => 'B',
		0x0E => 'R',
		0x0F => 'Q'
	];

	public static $typeToNumberMapping = [
		'p' => 0x01,
		'n' => 0x02,
		'k' => 0x03,
		'b' => 0x05,
		'r' => 0x06,
		'q' => 0x07

	];

	public static $castle = [
		'-' => 0,
		'K' => 8,
		'Q' => 4,
		'k' => 2,
		'q' => 1
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
	public static $movePatterns = [
		0X01 => [ 16, 32, 15, 17 ],
		0X09 => [ -16, -32, -15, -17 ],
		0x05 => [ -15, -17, 15, 17 ],
		0x0D => [ -15, -17, 15, 17 ],
		0x06 => [ -1, 1, -16, 16 ],
		0x0E => [ -1, 1, -16, 16 ],
		0x07 => [ -15, -17, 15, 17, -1, 1, -16, 16 ],
		0x0F => [ -15, -17, 15, 17, -1, 1, -16, 16 ],
		0X02 => [ -33, -31, -18, -14, 14, 18, 31, 33 ],
		0x0A => [ -33, -31, -18, -14, 14, 18, 31, 33 ],
		0X03 => [ -17, -16, -15, -1, 1, 15, 16, 17 ],
		0X0B => [ -17, -16, -15, -1, 1, 15, 16, 17 ]
	];

	// What is this?
	public static $distances = [
		'241' => 1,
		'242' => 2,
		'243' => 3,
		'244' => 4,
		'245' => 5,
		'246' => 6,
		'247' => 7,
		'272' => 1,
		'273' => 1,
		'274' => 2,
		'275' => 3,
		'276' => 4,
		'277' => 5,
		'278' => 6,
		'279' => 7,
		'304' => 2,
		'305' => 2,
		'306' => 2,
		'307' => 3,
		'308' => 4,
		'309' => 5,
		'310' => 6,
		'311' => 7,
		'336' => 3,
		'337' => 3,
		'338' => 3,
		'339' => 3,
		'340' => 4,
		'341' => 5,
		'342' => 6,
		'343' => 7,
		'368' => 4,
		'369' => 4,
		'370' => 4,
		'371' => 4,
		'372' => 4,
		'373' => 5,
		'374' => 6,
		'375' => 7,
		'400' => 5,
		'401' => 5,
		'402' => 5,
		'403' => 5,
		'404' => 5,
		'405' => 5,
		'406' => 6,
		'407' => 7,
		'432' => 6,
		'433' => 6,
		'434' => 6,
		'435' => 6,
		'436' => 6,
		'437' => 6,
		'438' => 6,
		'439' => 7,
		'464' => 7,
		'465' => 7,
		'466' => 7,
		'467' => 7,
		'468' => 7,
		'469' => 7,
		'470' => 7,
		'471' => 7,
		'239' => 1,
		'271' => 1,
		'303' => 2,
		'335' => 3,
		'367' => 4,
		'399' => 5,
		'431' => 6,
		'463' => 7,
		'238' => 2,
		'270' => 2,
		'302' => 2,
		'334' => 3,
		'366' => 4,
		'398' => 5,
		'430' => 6,
		'462' => 7,
		'237' => 3,
		'269' => 3,
		'301' => 3,
		'333' => 3,
		'365' => 4,
		'397' => 5,
		'429' => 6,
		'461' => 7,
		'236' => 4,
		'268' => 4,
		'300' => 4,
		'332' => 4,
		'364' => 4,
		'396' => 5,
		'428' => 6,
		'460' => 7,
		'235' => 5,
		'267' => 5,
		'299' => 5,
		'331' => 5,
		'363' => 5,
		'395' => 5,
		'427' => 6,
		'459' => 7,
		'234' => 6,
		'266' => 6,
		'298' => 6,
		'330' => 6,
		'362' => 6,
		'394' => 6,
		'426' => 6,
		'458' => 7,
		'233' => 7,
		'265' => 7,
		'297' => 7,
		'329' => 7,
		'361' => 7,
		'393' => 7,
		'425' => 7,
		'457' => 7,
		'208' => 1,
		'209' => 1,
		'210' => 2,
		'211' => 3,
		'212' => 4,
		'213' => 5,
		'214' => 6,
		'215' => 7,
		'207' => 1,
		'206' => 2,
		'205' => 3,
		'204' => 4,
		'203' => 5,
		'202' => 6,
		'201' => 7,
		'176' => 2,
		'177' => 2,
		'178' => 2,
		'179' => 3,
		'180' => 4,
		'181' => 5,
		'182' => 6,
		'183' => 7,
		'175' => 2,
		'174' => 2,
		'173' => 3,
		'172' => 4,
		'171' => 5,
		'170' => 6,
		'169' => 7,
		'144' => 3,
		'145' => 3,
		'146' => 3,
		'147' => 3,
		'148' => 4,
		'149' => 5,
		'150' => 6,
		'151' => 7,
		'143' => 3,
		'142' => 3,
		'141' => 3,
		'140' => 4,
		'139' => 5,
		'138' => 6,
		'137' => 7,
		'112' => 4,
		'113' => 4,
		'114' => 4,
		'115' => 4,
		'116' => 4,
		'117' => 5,
		'118' => 6,
		'119' => 7,
		'111' => 4,
		'110' => 4,
		'109' => 4,
		'108' => 4,
		'107' => 5,
		'106' => 6,
		'105' => 7,
		'80' => 5,
		'81' => 5,
		'82' => 5,
		'83' => 5,
		'84' => 5,
		'85' => 5,
		'86' => 6,
		'87' => 7,
		'79' => 5,
		'78' => 5,
		'77' => 5,
		'76' => 5,
		'75' => 5,
		'74' => 6,
		'73' => 7,
		'48' => 6,
		'49' => 6,
		'50' => 6,
		'51' => 6,
		'52' => 6,
		'53' => 6,
		'54' => 6,
		'55' => 7,
		'47' => 6,
		'46' => 6,
		'45' => 6,
		'44' => 6,
		'43' => 6,
		'42' => 6,
		'41' => 7,
		'16' => 7,
		'17' => 7,
		'18' => 7,
		'19' => 7,
		'20' => 7,
		'21' => 7,
		'22' => 7,
		'23' => 7,
		'15' => 7,
		'14' => 7,
		'13' => 7,
		'12' => 7,
		'11' => 7,
		'10' => 7,
		'9' => 7
	];

	/**
	 * @return array
	 */
	public static function getDefaultBoard() {
		$squares = [];
		for ( $square = 0; $square <= 119; $square++ ) {
			$squares[ $square ] = 0;
		}
		return $squares;
	}

	// array_keys( $files )
	public static $fileMapping = [
		'a',
		'b',
		'c',
		'd',
		'e',
		'f',
		'g',
		'h'
	];

	// num / 16 + 1
	public static $rankMapping = [
		0 => 1,
		16 => 2,
		32 => 3,
		48 => 4,
		64 => 5,
		80 => 6,
		96 => 7,
		112 => 8
	];

	public static $files = [
		'a' => 0,
		'b' => 1,
		'c' => 2,
		'd' => 3,
		'e' => 4,
		'f' => 5,
		'g' => 6,
		'h' => 7
	];
}

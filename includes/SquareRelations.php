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
 * @file SquareRelations
 * @ingroup ChessBrowser
 * @author DannyS712
 */

class SquareRelations {

	// TODO calculate these instead of storing an array
	private const DISTANCES = [
		'9' => 7,
		'10' => 7,
		'11' => 7,
		'12' => 7,
		'13' => 7,
		'14' => 7,
		'15' => 7,
		'16' => 7,
		'17' => 7,
		'18' => 7,
		'19' => 7,
		'20' => 7,
		'21' => 7,
		'22' => 7,
		'23' => 7,
		'41' => 7,
		'42' => 6,
		'43' => 6,
		'44' => 6,
		'45' => 6,
		'46' => 6,
		'47' => 6,
		'48' => 6,
		'49' => 6,
		'50' => 6,
		'51' => 6,
		'52' => 6,
		'53' => 6,
		'54' => 6,
		'55' => 7,
		'73' => 7,
		'74' => 6,
		'75' => 5,
		'76' => 5,
		'77' => 5,
		'78' => 5,
		'79' => 5,
		'80' => 5,
		'81' => 5,
		'82' => 5,
		'83' => 5,
		'84' => 5,
		'85' => 5,
		'86' => 6,
		'87' => 7,
		'105' => 7,
		'106' => 6,
		'107' => 5,
		'108' => 4,
		'109' => 4,
		'110' => 4,
		'111' => 4,
		'112' => 4,
		'113' => 4,
		'114' => 4,
		'115' => 4,
		'116' => 4,
		'117' => 5,
		'118' => 6,
		'119' => 7,
		'137' => 7,
		'138' => 6,
		'139' => 5,
		'140' => 4,
		'141' => 3,
		'142' => 3,
		'143' => 3,
		'144' => 3,
		'145' => 3,
		'146' => 3,
		'147' => 3,
		'148' => 4,
		'149' => 5,
		'150' => 6,
		'151' => 7,
		'169' => 7,
		'170' => 6,
		'171' => 5,
		'172' => 4,
		'173' => 3,
		'174' => 2,
		'175' => 2,
		'176' => 2,
		'177' => 2,
		'178' => 2,
		'179' => 3,
		'180' => 4,
		'181' => 5,
		'182' => 6,
		'183' => 7,
		'201' => 7,
		'202' => 6,
		'203' => 5,
		'204' => 4,
		'205' => 3,
		'206' => 2,
		'207' => 1,
		'208' => 1,
		'209' => 1,
		'210' => 2,
		'211' => 3,
		'212' => 4,
		'213' => 5,
		'214' => 6,
		'215' => 7,
		'233' => 7,
		'234' => 6,
		'235' => 5,
		'236' => 4,
		'237' => 3,
		'238' => 2,
		'239' => 1,
		'241' => 1,
		'242' => 2,
		'243' => 3,
		'244' => 4,
		'245' => 5,
		'246' => 6,
		'247' => 7,
		'265' => 7,
		'266' => 6,
		'267' => 5,
		'268' => 4,
		'269' => 3,
		'270' => 2,
		'271' => 1,
		'272' => 1,
		'273' => 1,
		'274' => 2,
		'275' => 3,
		'276' => 4,
		'277' => 5,
		'278' => 6,
		'279' => 7,
		'297' => 7,
		'298' => 6,
		'299' => 5,
		'300' => 4,
		'301' => 3,
		'302' => 2,
		'303' => 2,
		'304' => 2,
		'305' => 2,
		'306' => 2,
		'307' => 3,
		'308' => 4,
		'309' => 5,
		'310' => 6,
		'311' => 7,
		'329' => 7,
		'330' => 6,
		'331' => 5,
		'332' => 4,
		'333' => 3,
		'334' => 3,
		'335' => 3,
		'336' => 3,
		'337' => 3,
		'338' => 3,
		'339' => 3,
		'340' => 4,
		'341' => 5,
		'342' => 6,
		'343' => 7,
		'361' => 7,
		'362' => 6,
		'363' => 5,
		'364' => 4,
		'365' => 4,
		'366' => 4,
		'367' => 4,
		'368' => 4,
		'369' => 4,
		'370' => 4,
		'371' => 4,
		'372' => 4,
		'373' => 5,
		'374' => 6,
		'375' => 7,
		'393' => 7,
		'394' => 6,
		'395' => 5,
		'396' => 5,
		'397' => 5,
		'398' => 5,
		'399' => 5,
		'400' => 5,
		'401' => 5,
		'402' => 5,
		'403' => 5,
		'404' => 5,
		'405' => 5,
		'406' => 6,
		'407' => 7,
		'425' => 7,
		'426' => 6,
		'427' => 6,
		'428' => 6,
		'429' => 6,
		'430' => 6,
		'431' => 6,
		'432' => 6,
		'433' => 6,
		'434' => 6,
		'435' => 6,
		'436' => 6,
		'437' => 6,
		'438' => 6,
		'439' => 7,
		'457' => 7,
		'458' => 7,
		'459' => 7,
		'460' => 7,
		'461' => 7,
		'462' => 7,
		'463' => 7,
		'464' => 7,
		'465' => 7,
		'466' => 7,
		'467' => 7,
		'468' => 7,
		'469' => 7,
		'470' => 7,
		'471' => 7,
	];

	/** @var int */
	private $square1;

	/** @var int */
	private $square2;

	/**
	 * Can be used, but for chaining ::new is probably better
	 *
	 * @param int $square1
	 * @param int $square2
	 */
	public function __construct( int $square1, int $square2 ) {
		$this->square1 = $square1;
		$this->square2 = $square2;
	}

	/**
	 * For chaining
	 *
	 * @param int $square1
	 * @param int $square2
	 * @return SquareRelations
	 */
	public static function new( int $square1, int $square2 ) : SquareRelations {
		return new SquareRelations( $square1, $square2 );
	}

	/**
	 * Get the distance between 2 squares
	 * @return int
	 */
	public function getDistance() {
		$sq1 = $this->square1;
		$sq2 = $this->square2;
		return self::DISTANCES[$sq2 - $sq1 + ( $sq2 | 7 ) - ( $sq1 | 7 ) + 240];
	}

	/**
	 * Returns whether two squares are on the same rank.
	 * @return bool
	 */
	public function haveSameRank() : bool {
		return ( $this->square1 & 240 ) === ( $this->square2 & 240 );
	}

	/**
	 * Returns whether two squares are on the same file
	 * @return bool
	 */
	public function haveSameFile() : bool {
		return ( $this->square1 & 15 ) === ( $this->square2 & 15 );
	}

}

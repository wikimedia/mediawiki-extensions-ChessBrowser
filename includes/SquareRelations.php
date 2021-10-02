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

namespace MediaWiki\Extension\ChessBrowser;

class SquareRelations {

	/** @var int */
	private $square1;

	/** @var int */
	private $square2;

	/**
	 * Can be used, but for chaining ::new is probably better
	 *
	 * @param int $square1 Square as byte number where in Hex notation
	 *                     the first four bits (0xF0) are rank [1-8]
	 *                     and the second 4 bits (0x0F) are file [a-h]
	 * @param int $square2 Square as byte number where in Hex notation
	 *                     the first four bits (0xF0) are rank [1-8]
	 *                     and the second 4 bits (0x0F) are file [a-h]
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
	public static function new( int $square1, int $square2 ): SquareRelations {
		return new SquareRelations( $square1, $square2 );
	}

	/**
	 * Get the distance between 2 squares
	 * @return int
	 */
	public function getDistance(): int {
		$sq1 = $this->square1;
		$sq2 = $this->square2;

		$rankDiff = abs( ( $sq1 & 0xF0 ) - ( $sq2 & 0XF0 ) ) >> 4;
		$fileDiff = abs( ( $sq1 & 0x07 ) - ( $sq2 & 0x07 ) );

		return max( $rankDiff, $fileDiff );
	}

	/**
	 * Returns whether two squares are on the same rank.
	 * @return bool
	 */
	public function haveSameRank(): bool {
		return ( $this->square1 & 0xF0 ) === ( $this->square2 & 0xF0 );
	}

	/**
	 * Returns whether two squares are on the same file
	 * @return bool
	 */
	public function haveSameFile(): bool {
		return ( $this->square1 & 0x0F ) === ( $this->square2 & 0x0F );
	}

}

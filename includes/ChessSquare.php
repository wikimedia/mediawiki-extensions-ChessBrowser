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
 * @file ChessSquare
 * @ingroup ChessBrowser
 * @author DannyS712
 */

class ChessSquare {

	/** @var string */
	private $fileLetter;

	/** @var int */
	private $rankNumber;

	/** @var int */
	private $number;

	/**
	 * Doesn't apply validation, should not be called directly
	 *
	 * @param string $fileLetter
	 * @param int $rankNumber
	 * @param int $number
	 */
	private function __construct( string $fileLetter, int $rankNumber, int $number ) {
		wfDebugLog(
			'ChessBrowser',
			'Square constructed: ' . $fileLetter . (string)$rankNumber . " ($number)"
		);

		$this->fileLetter = $fileLetter;
		$this->rankNumber = $rankNumber;
		$this->number = $number;
	}

	/**
	 * @param int $number
	 * @return ChessSquare
	 */
	public static function newFromNumber( int $number ) {
		$files = [
			0 => 'a',
			1 => 'b',
			2 => 'c',
			3 => 'd',
			4 => 'e',
			5 => 'f',
			6 => 'g',
			7 => 'h'
		];

		return new ChessSquare(
			$files[ $number & 0b00000111 ],
			( ( $number & 0b01110000 ) / 16 ) + 1,
			$number
		);
	}

	/**
	 * @param string $coords
	 * @return ChessSquare|false false if invalid
	 */
	public static function newFromCoords( string $coords ) {
		if ( strlen( $coords ) !== 2 ) {
			return false;
		}

		list( $fileLetter, $rankNumber ) = str_split( $coords );
		$rankNumber = intval( $rankNumber );

		$files = [
			'a' => 0,
			'b' => 1,
			'c' => 2,
			'd' => 3,
			'e' => 4,
			'f' => 5,
			'g' => 6,
			'h' => 7
		];

		if ( !isset( $files[ $fileLetter ] ) ) {
			return false;
		}

		$number = $files[ $fileLetter ] + ( 16 * ( $rankNumber - 1 ) );

		return new ChessSquare(
			$fileLetter,
			$rankNumber,
			$number
		);
	}

	/**
	 * Get the coordinates
	 *
	 * @return string
	 */
	public function getCoords() : string {
		return ( $this->fileLetter . (string)$this->rankNumber );
	}

	/**
	 * Get the 0x88 location
	 *
	 * @return int
	 */
	public function getNumber() : int {
		return $this->number;
	}

}

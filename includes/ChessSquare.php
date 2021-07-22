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

namespace MediaWiki\Extension\ChessBrowser;

class ChessSquare {

	/** @var string */
	private $fileLetter;

	/** @var int */
	private $rankNumber;

	/** @var int */
	private $number;

	public const NUMBER_TO_FILE = [
		0 => 'a',
		1 => 'b',
		2 => 'c',
		3 => 'd',
		4 => 'e',
		5 => 'f',
		6 => 'g',
		7 => 'h'
	];

	public const FILE_TO_NUMBER = [
		'a' => 0,
		'b' => 1,
		'c' => 2,
		'd' => 3,
		'e' => 4,
		'f' => 5,
		'g' => 6,
		'h' => 7
	];

	/**
	 * Doesn't apply validation, should not be called directly
	 *
	 * @param string $fileLetter
	 * @param int $rankNumber
	 * @param int $number
	 */
	private function __construct( string $fileLetter, int $rankNumber, int $number ) {
		$this->fileLetter = $fileLetter;
		$this->rankNumber = $rankNumber;
		$this->number = $number;
	}

	/**
	 * @param int $number
	 * @return ChessSquare
	 */
	public static function newFromNumber( int $number ): ChessSquare {
		return new ChessSquare(
			self::NUMBER_TO_FILE[ $number & 0b00000111 ],
			( ( $number & 0b01110000 ) / 16 ) + 1,
			$number
		);
	}

	/**
	 * For conversion from a 0-63 representation which counted across rows
	 *
	 * @param int $number
	 * @return ChessSquare
	 */
	public static function newFromLateral64( int $number ): ChessSquare {
		$inHex = (int)( floor( $number / 8 ) * 16 ) + ( $number % 8 );
		return self::newFromNumber( $inHex );
	}

	/**
	 * @param string $coords
	 * @return ChessSquare
	 * @throws ChessBrowserException if invalid
	 */
	public static function newFromCoords( string $coords ): ChessSquare {
		if ( strlen( $coords ) !== 2 ) {
			throw new ChessBrowserException( "Coordinates ($coords) too long" );
		}

		list( $fileLetter, $rankNumber ) = str_split( $coords );
		$rankNumber = intval( $rankNumber );

		if ( !isset( self::FILE_TO_NUMBER[ $fileLetter ] ) ) {
			throw new ChessBrowserException( "No such file: $fileLetter" );
		}

		$number = self::FILE_TO_NUMBER[ $fileLetter ] + ( 16 * ( $rankNumber - 1 ) );

		return new ChessSquare(
			$fileLetter,
			$rankNumber,
			$number
		);
	}

	/**
	 * Get the 0-63 representation going up files
	 * A1=0; B1=1; etc
	 *
	 * @return int
	 */
	public function getAsVertical64(): int {
		return ( self::FILE_TO_NUMBER[$this->fileLetter] * 8 ) + ( $this->rankNumber - 1 );
	}

	/**
	 * Get the coordinates
	 *
	 * @return string
	 */
	public function getCoords(): string {
		return ( $this->fileLetter . $this->rankNumber );
	}

	/**
	 * Get the 0x88 location
	 *
	 * @return int
	 */
	public function getNumber(): int {
		return $this->number;
	}

	/**
	 * Get the rank number
	 *
	 * @return int
	 */
	public function getRank(): int {
		return $this->rankNumber;
	}

	/**
	 * Get the file letter
	 *
	 * @return string
	 */
	public function getFile(): string {
		return $this->fileLetter;
	}

}

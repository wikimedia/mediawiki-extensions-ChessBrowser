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
 * @file ChessPiece
 * @ingroup ChessBrowser
 * @author DannyS712
 */

class ChessPiece {

	/** @var string */
	private $type;

	/** @var string */
	private $color;

	/** @var string */
	private $symbol;

	public const COLOR_WHITE = 'white';
	public const COLOR_BLACK = 'black';

	/**
	 * @param string $symbol
	 * @throws ChessBrowserException
	 */
	public function __construct( string $symbol ) {
		wfDebugLog(
			'ChessBrowser',
			'Piece constructed: ' . $symbol
		);

		$type = strtolower( $symbol );

		$validTypes = [ 'p', 'b', 'n', 'r', 'q', 'k' ];

		if ( !in_array( $type, $validTypes ) ) {
			throw new ChessBrowserException( "Unkown type for '$symbol'" );
		}

		if ( $type === $symbol ) {
			// Piece was already lowercase, so black
			$color = self::COLOR_BLACK;
		} else {
			// Piece was uppercase, so white
			$color = self::COLOR_WHITE;
		}

		$this->symbol = $symbol;
		$this->type = $type;
		$this->color = $color;
	}

	/**
	 * Create from the hex representation, for use in FenParser0x88
	 *
	 * @param int $hex
	 * @return ChessPiece
	 * @throws ChessBrowserException
	 */
	public static function newFromHex( int $hex ) : ChessPiece {
		$mappings = [
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
			0x0F => 'q',
		];

		if ( !array_key_exists( $hex, $mappings ) ) {
			throw new ChessBrowserException( "Unknown hex representation '$hex'" );
		}

		$piece = new ChessPiece( $mappings[$hex] );
		return $piece;
	}

	/**
	 * Get the symbol for the piece
	 *
	 * @return string
	 */
	public function getSymbol() : string {
		return $this->symbol;
	}

	/**
	 * Get the type
	 *
	 * @return string
	 */
	public function getType() : string {
		return $this->type;
	}

	/**
	 * Get the color
	 *
	 * @return string
	 */
	public function getColor() : string {
		return $this->color;
	}

	/**
	 * Get the hex representation of the piece
	 *
	 * @return int
	 */
	public function getAsHex() : int {
		$mappings = [
			'P' => 0x01,
			'N' => 0x02,
			'K' => 0x03,
			'B' => 0x05,
			'R' => 0x06,
			'Q' => 0x07,
			'p' => 0x09,
			'n' => 0x0A,
			'k' => 0x0B,
			'b' => 0x0D,
			'r' => 0x0E,
			'q' => 0x0F
		];

		return $mappings[$this->symbol];
	}

	/**
	 * Get the possible moves
	 *
	 * @return array
	 */
	public function getMovePatterns() : array {
		switch ( $this->symbol ) {
			case 'P':
				return [ 16, 32, 15, 17 ];
			case 'p':
				return [ -16, -32, -15, -17 ];
			case 'N':
			case 'n':
				return [ -33, -31, -18, -14, 14, 18, 31, 33 ];
			case 'K':
			case 'k':
				return [ -17, -16, -15, -1, 1, 15, 16, 17 ];
			case 'B':
			case 'b':
				return [ -15, -17, 15, 17 ];
			case 'R':
			case 'r':
				return [ -1, 1, -16, 16 ];
			case 'Q':
			case 'q':
				return [ -15, -17, 15, 17, -1, 1, -16, 16 ];
		}
	}

}

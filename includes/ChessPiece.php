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

namespace MediaWiki\Extension\ChessBrowser;

use LogicException;

class ChessPiece {

	/** @var string */
	private $type;

	/** @var string */
	private $color;

	/** @var string */
	private $symbol;

	public const COLOR_WHITE = 'white';
	public const COLOR_BLACK = 'black';

	public const WHITE_PAWN = 0x01;
	public const WHITE_KNIGHT = 0x02;
	public const WHITE_KING = 0x03;
	public const WHITE_BISHOP = 0x05;
	public const WHITE_ROOK = 0x06;
	public const WHITE_QUEEN = 0x07;

	public const BLACK_PAWN = 0x09;
	public const BLACK_KNIGHT = 0x0A;
	public const BLACK_KING = 0x0B;
	public const BLACK_BISHOP = 0x0D;
	public const BLACK_ROOK = 0x0E;
	public const BLACK_QUEEN = 0x0F;

	/**
	 * @param string $symbol
	 * @throws ChessBrowserException
	 */
	public function __construct( string $symbol ) {
		$type = strtolower( $symbol );

		$validTypes = [ 'p', 'b', 'n', 'r', 'q', 'k' ];

		if ( !in_array( $type, $validTypes ) ) {
			throw new ChessBrowserException( "Unknown type for '$symbol'" );
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
	public static function newFromHex( int $hex ): ChessPiece {
		$mappings = [
			self::WHITE_PAWN => 'P',
			self::WHITE_KNIGHT => 'N',
			self::WHITE_KING => 'K',
			self::WHITE_BISHOP => 'B',
			self::WHITE_ROOK => 'R',
			self::WHITE_QUEEN => 'Q',
			self::BLACK_PAWN => 'p',
			self::BLACK_KNIGHT => 'n',
			self::BLACK_KING => 'k',
			self::BLACK_BISHOP => 'b',
			self::BLACK_ROOK => 'r',
			self::BLACK_QUEEN => 'q',
		];

		if ( !array_key_exists( $hex, $mappings ) ) {
			throw new ChessBrowserException( "Unknown hex representation '$hex'" );
		}

		return new ChessPiece( $mappings[$hex] );
	}

	/**
	 * Get the symbol for the piece
	 *
	 * @return string
	 */
	public function getSymbol(): string {
		return $this->symbol;
	}

	/**
	 * Get the type
	 *
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * Get the color
	 *
	 * @return string
	 */
	public function getColor(): string {
		return $this->color;
	}

	/**
	 * Get the hex representation of the piece
	 *
	 * @return int
	 */
	public function getAsHex(): int {
		$mappings = [
			'P' => self::WHITE_PAWN,
			'N' => self::WHITE_KNIGHT,
			'K' => self::WHITE_KING,
			'B' => self::WHITE_BISHOP,
			'R' => self::WHITE_ROOK,
			'Q' => self::WHITE_QUEEN,
			'p' => self::BLACK_PAWN,
			'n' => self::BLACK_KNIGHT,
			'k' => self::BLACK_KING,
			'b' => self::BLACK_BISHOP,
			'r' => self::BLACK_ROOK,
			'q' => self::BLACK_QUEEN,
		];

		return $mappings[$this->symbol];
	}

	/**
	 * Get the possible moves
	 *
	 * @return array
	 */
	public function getMovePatterns(): array {
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
			default:
				throw new LogicException( "Unexpected symbol: $this->symbol" );
		}
	}

	/**
	 * Get the symbol to use for notation
	 *
	 * @return string
	 */
	public function getNotation(): string {
		if ( $this->type === 'p' ) {
			return '';
		}
		return strtoupper( $this->type );
	}

}

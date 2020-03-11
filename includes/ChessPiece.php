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
	 */
	public function __construct( string $symbol ) {
		wfDebugLog(
			'ChessBrowser',
			'Piece constructed: ' . $symbol
		);

		$type = strtolower( $symbol );

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

}

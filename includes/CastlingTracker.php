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
 * @file CastlingTracker
 * @ingroup ChessBrowser
 * @author DannyS712
 */

namespace MediaWiki\Extension\ChessBrowser;

class CastlingTracker {

	/** @var string */
	private $castle;

	/** @var int */
	private $castleCode;

	private const CODES = [
		'K' => 8,
		'Q' => 4,
		'k' => 2,
		'q' => 1
	];

	/**
	 * @param string $fromFen
	 */
	public function __construct( string $fromFen ) {
		$this->castle = $fromFen ?: '-';
		$this->updateCode();
	}

	/**
	 * Is a castle currently valid (solely based on moved pieces, not the current board)
	 *
	 * @param string $option
	 * @return bool whether this castle is valid
	 */
	public function checkCastle( string $option ): bool {
		$optionCode = self::CODES[$option] ?? 0;
		return (bool)( $this->castleCode & $optionCode );
	}

	/**
	 * Set the castleCode
	 */
	private function updateCode() {
		$totalCode = 0;
		foreach ( str_split( $this->castle ) as $option ) {
			$totalCode += self::CODES[$option] ?? 0;
		}
		$this->castleCode = $totalCode;
	}

	/**
	 * Update the status after a move, and return the new castle status
	 *
	 * @param int $movedPiece
	 * @param int $from
	 * @return string
	 */
	public function updateForMove( int $movedPiece, int $from ): string {
		$currentCastle = $this->castle;
		$newCastle = $currentCastle;
		switch ( $movedPiece ) {
			case ChessPiece::WHITE_KING:
				$newCastle = str_replace( [ 'K', 'Q' ], "", $currentCastle );
				break;
			case ChessPiece::BLACK_KING:
				$newCastle = str_replace( [ 'k', 'q' ], "", $currentCastle );
				break;
			case ChessPiece::WHITE_ROOK:
				if ( $from === 0 ) {
					$newCastle = str_replace( 'Q', "", $currentCastle );
				} elseif ( $from === 7 ) {
					$newCastle = str_replace( 'K', "", $currentCastle );
				}
				break;
			case ChessPiece::BLACK_ROOK:
				if ( $from === 112 ) {
					$newCastle = str_replace( 'q', "", $currentCastle );
				} elseif ( $from === 119 ) {
					$newCastle = str_replace( 'k', "", $currentCastle );
				}
				break;
		}
		$newCastle = $newCastle ?: '-';
		if ( $newCastle !== $currentCastle ) {
			$this->castle = $newCastle;
			$this->updateCode();
		}
		return $newCastle;
	}

}

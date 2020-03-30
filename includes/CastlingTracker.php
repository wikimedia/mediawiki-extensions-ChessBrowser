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

class CastlingTracker {

	/** @var string */
	private $castle;

	/** @var int */
	private $castleCode;

	private const CODES = [
		'-' => 0,
		'K' => 8,
		'Q' => 4,
		'k' => 2,
		'q' => 1
	];

	/**
	 * @param mixed $fromFen
	 */
	public function __construct( $fromFen ) {
		if ( !$fromFen ) {
			$fromFen = '-';
		}
		$this->castle = $fromFen;
		$this->updateCode();
	}

	/**
	 * Is a castle currently valid (solely based on moved pieces, not the current board)
	 *
	 * @param string $option
	 * @return bool whether this castle is valid
	 */
	public function checkCastle( string $option ) : bool {
		$optionCode = self::CODES[$option];
		$possible = (bool)( $this->castleCode & $optionCode );
		return $possible;
	}

	/**
	 * Set the castleCode
	 */
	private function updateCode() {
		$options = str_split( $this->castle );
		$totalCode = 0;
		foreach ( $options as $option ) {
			// FIXME why does this happen?
			if ( $option !== "" ) {
				$totalCode += self::CODES[$option];
			}
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
	public function updateForMove( int $movedPiece, int $from ) : string {
		$currentCastle = $this->castle;
		$newCastle = $currentCastle;
		switch ( $movedPiece ) {
			case ChessPiece::WHITE_KING:
				$newCastle = preg_replace( "/[KQ]/s", "", $currentCastle );
				break;
			case ChessPiece::BLACK_KING:
				$newCastle = preg_replace( "/[kq]/s", "", $currentCastle );
				break;
			case ChessPiece::WHITE_ROOK:
				if ( $from === 0 ) {
					$newCastle = preg_replace( "/Q/s", "", $currentCastle );
				} elseif ( $from === 7 ) {
					$newCastle = preg_replace( "/K/s", "", $currentCastle );
				}
				break;
			case ChessPiece::BLACK_ROOK:
				if ( $from === 112 ) {
					$newCastle = preg_replace( "/q/s", "", $currentCastle );
				} elseif ( $from === 119 ) {
					$newCastle = preg_replace( "/k/s", "", $currentCastle );
				}
				break;
		}
		if ( $newCastle !== $currentCastle ) {
			$this->castle = $newCastle;
			$this->updateCode();
		}
		return $newCastle;
	}

}

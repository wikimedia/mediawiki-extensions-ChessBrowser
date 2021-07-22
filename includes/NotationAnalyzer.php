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

class NotationAnalyzer {

	/** @var string */
	private $notation;

	/**
	 * @param string $notation
	 */
	public function __construct( string $notation ) {
		$this->notation = $notation;
	}

	/**
	 * Get the rank the piece started on
	 *
	 * @return int|null
	 */
	public function getFromRank() {
		$notation = preg_replace( "/^.+(\d).+\d.*$/s", '$1', $this->notation );
		if ( strlen( $notation ) > 1 ) {
			return null;
		}
		return ( (int)$notation - 1 ) * 16;
	}

	/**
	 * Get the file the piece started on
	 *
	 * @return int|null
	 */
	public function getFromFile() {
		$notation = preg_replace( "/^.*([a-h]).*[a-h].*$/s", '$1', $this->notation );
		if ( strlen( $notation ) > 1 ) {
			return null;
		}
		return ChessSquare::FILE_TO_NUMBER[$notation];
	}

	/**
	 * Get the target square
	 *
	 * @return int|string
	 */
	public function getTargetSquare() {
		$notation = preg_replace( "/.*([a-h][1-8]).*/s", '$1', $this->notation );
		try {
			$square = ChessSquare::newFromCoords( $notation );
			return $square->getNumber();
		} catch ( ChessBrowserException $e ) {
			return '';
		}
	}

	/**
	 * Get the piece type making the move
	 *
	 * @param string $color
	 * @return int
	 */
	public function getPieceType( string $color ): int {
		$notation = $this->notation;
		if ( $notation === 'O-O-O' || $notation === 'O-O' ) {
			$pieceType = 'K';
		} else {
			$token = $notation[0];
			$pieceType = preg_match( "/[NRBQK]/", $token ) ? $token : 'P';
		}
		$pieceType = ( new ChessPiece( $pieceType ) )->getAsHex();
		if ( $color === 'black' ) {
			$pieceType += 8;
		}
		return $pieceType;
	}

	/**
	 * Get the promotion
	 *
	 * If the notation token contains an equal sign then it's a promotion
	 *
	 * @return string
	 */
	public function getPromotion(): string {
		$notation = $this->notation;
		if ( strpos( $notation, '=' ) !== false ) {
			$piece = preg_replace( "/^.*?=([QRBN]).*$/", '$1', $notation );
			return strtolower( $piece );
		}

		if ( preg_match( "/[a-h][18][NBRQ]/", $notation ) ) {
			$notation = preg_replace( "/[^a-h18NBRQ]/s", "", $notation );
			return strtolower( $notation[-1] );
		}
		return '';
	}

}

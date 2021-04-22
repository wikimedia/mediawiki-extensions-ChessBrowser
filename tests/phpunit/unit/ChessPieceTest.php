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
 * @file ChessPieceTest
 * @ingroup ChessBrowser
 * @author DannyS712
 */

namespace MediaWiki\Extension\ChessBrowser\Tests\Unit;

use MediaWiki\Extension\ChessBrowser\ChessBrowserException;
use MediaWiki\Extension\ChessBrowser\ChessPiece;
use MediaWikiUnitTestCase;

/**
 * @covers MediaWiki\Extension\ChessBrowser\ChessPiece
 */
class ChessPieceTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider provideTestConstants
	 * @param string $symbol
	 * @param string $hexConstant
	 */
	public function testConstants(
		string $symbol,
		string $hexConstant
	) {
		$piece = new ChessPiece( $symbol );
		$hex = $piece->getAsHex();
		$expectedHex = constant( ChessPiece::class . '::' . $hexConstant );
		$this->assertSame( $expectedHex, $hex );
	}

	public function provideTestConstants() {
		return [
			[ 'P', 'WHITE_PAWN' ],
			[ 'N', 'WHITE_KNIGHT' ],
			[ 'K', 'WHITE_KING' ],
			[ 'B', 'WHITE_BISHOP' ],
			[ 'R', 'WHITE_ROOK' ],
			[ 'Q', 'WHITE_QUEEN' ],
			[ 'p', 'BLACK_PAWN' ],
			[ 'n', 'BLACK_KNIGHT' ],
			[ 'k', 'BLACK_KING' ],
			[ 'b', 'BLACK_BISHOP' ],
			[ 'r', 'BLACK_ROOK' ],
			[ 'q', 'BLACK_QUEEN' ],
		];
	}

	/**
	 * @dataProvider provideTestGeneral
	 * @param string $symbol
	 * @param string $type
	 * @param string $color
	 * @param int $hex
	 * @param array $moves
	 * @param string $notation
	 */
	public function testGeneral(
		string $symbol,
		string $type,
		string $color,
		int $hex,
		array $moves,
		string $notation
	) {
		$piece = new ChessPiece( $symbol );

		$this->assertSame( $symbol, $piece->getSymbol() );
		$this->assertSame( $type, $piece->getType() );
		$this->assertSame( $color, $piece->getColor() );
		$this->assertSame( $hex, $piece->getAsHex() );
		$this->assertArrayEquals( $moves, $piece->getMovePatterns() );
		$this->assertSame( $notation, $piece->getNotation() );
	}

	public function provideTestGeneral() {
		return [
			'P' => [ 'P', 'p', 'white', 0x01, [ 16, 32, 15, 17 ], '' ],
			'N' => [ 'N', 'n', 'white', 0x02, [ -33, -31, -18, -14, 14, 18, 31, 33 ], 'N' ],
			'K' => [ 'K', 'k', 'white', 0x03, [ -17, -16, -15, -1, 1, 15, 16, 17 ], 'K' ],
			'B' => [ 'B', 'b', 'white', 0x05, [ -15, -17, 15, 17 ], 'B' ],
			'R' => [ 'R', 'r', 'white', 0x06, [ -1, 1, -16, 16 ], 'R' ],
			'Q' => [ 'Q', 'q', 'white', 0x07, [ -15, -17, 15, 17, -1, 1, -16, 16 ], 'Q' ],
			'p' => [ 'p', 'p', 'black', 0x09, [ -16, -32, -15, -17 ], '' ],
			'n' => [ 'n', 'n', 'black', 0x0A, [ -33, -31, -18, -14, 14, 18, 31, 33 ], 'N' ],
			'k' => [ 'k', 'k', 'black', 0x0B, [ -17, -16, -15, -1, 1, 15, 16, 17 ], 'K' ],
			'b' => [ 'b', 'b', 'black', 0x0D, [ -15, -17, 15, 17 ], 'B' ],
			'r' => [ 'r', 'r', 'black', 0x0E, [ -1, 1, -16, 16 ], 'R' ],
			'q' => [ 'q', 'q', 'black', 0x0F, [ -15, -17, 15, 17, -1, 1, -16, 16 ], 'Q' ],
		];
	}

	public function testConstructor_bad() {
		$this->expectException( ChessBrowserException::class );
		$this->expectExceptionMessage( "Unknown type for 'x'" );
		$piece = new ChessPiece( 'x' );
	}

	/**
	 * @dataProvider provideTestHex
	 * @param int $hex
	 */
	public function testHex( int $hex ) {
		$piece = ChessPiece::newFromHex( $hex );
		$this->assertSame( $hex, $piece->getAsHex() );
	}

	public function provideTestHex() {
		return [
			[ 0x01 ],
			[ 0x02 ],
			[ 0x03 ],
			[ 0x05 ],
			[ 0x06 ],
			[ 0x07 ],
			[ 0x09 ],
			[ 0x0A ],
			[ 0x0B ],
			[ 0x0D ],
			[ 0x0E ],
			[ 0x0F ]
		];
	}

	public function testHex_bad() {
		$this->expectException( ChessBrowserException::class );
		$this->expectExceptionMessage( "Unknown hex representation '4'" );
		$piece = ChessPiece::newFromHex( 0x04 );
	}

}

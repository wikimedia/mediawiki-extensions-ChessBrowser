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
 * @file NotationAnalyzerTest
 * @ingroup ChessBrowser
 * @author DannyS712
 */

namespace MediaWiki\Extension\ChessBrowser\Tests\Unit;

use MediaWiki\Extension\ChessBrowser\NotationAnalyzer;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\ChessBrowser\NotationAnalyzer
 */
class NotationAnalyzerTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::getFromRank
	 * @dataProvider provideTestFromRank
	 * @param string $notation
	 * @param int|null $rank
	 */
	public function testFromRank( string $notation, $rank ) {
		$notationAnalyzer = new NotationAnalyzer( $notation );
		$this->assertSame( $rank, $notationAnalyzer->getFromRank() );
	}

	public static function provideTestFromRank() {
		return [
			[ 'exd4', null ],
			[ 'Ngxe7', null ],
			[ 'Qa4', null ],
			[ 'O-O', null ],
			[ 'N4d5', 48 ],
			[ 'Nf4d5', 48 ],
			[ 'Nb4xd5', 48 ],
		];
	}

	/**
	 * @covers ::getFromFile
	 * @dataProvider provideTestFromFile
	 * @param string $notation
	 * @param int|null $file
	 */
	public function testFromFile( string $notation, $file ) {
		$notationAnalyzer = new NotationAnalyzer( $notation );
		$this->assertSame( $file, $notationAnalyzer->getFromFile() );
	}

	public static function provideTestFromFile() {
		return [
			[ 'exd4', 4 ],
			[ 'Nbxd2', 1 ],
			[ 'Ndxf6', 3 ],
			[ 'Nd4', null ],
			[ 'Qxe3', null ],
			[ 'O-O', null ],
		];
	}

	/**
	 * @covers ::getTargetSquare
	 * @dataProvider provideGetTargetSquare
	 * @param string $notation
	 * @param int|string $target
	 */
	public function testGetTargetSquare( string $notation, $target ) {
		$notationAnalyzer = new NotationAnalyzer( $notation );
		$this->assertSame( $target, $notationAnalyzer->getTargetSquare() );
	}

	public static function provideGetTargetSquare() {
		return [
			[ 'exd4', 0x33 ],
			[ 'Qb7', 0x61 ],
			[ 'Nf3d4', 0x33 ],
			[ 'exd', '' ],
			[ 'O-O-O', '' ],
		];
	}

	/**
	 * @covers ::getPieceType
	 * @dataProvider provideGetPieceType
	 * @param string $notation
	 * @param string $color
	 * @param int $piece
	 */
	public function testGetPieceType( string $notation, string $color, int $piece ) {
		$notationAnalyzer = new NotationAnalyzer( $notation );
		$this->assertSame( $piece, $notationAnalyzer->getPieceType( $color ) );
	}

	public static function provideGetPieceType() {
		return [
			[ 'exd4', 'white', 0x1 ],
			[ 'exd4', 'black', 0x9 ],
			[ 'Qb7', 'white', 0x7 ],
			[ 'Qb7', 'black', 0xF ],
			[ 'Nf3d4', 'white', 0x2 ],
			[ 'Nf3d4', 'black', 0xA ],
			[ 'O-O-O', 'white', 0x3 ],
			[ 'O-O-O', 'black', 0xB ],
			[ 'O-O', 'white', 0x3 ],
			[ 'O-O', 'black', 0xB ],
			[ 'Ke2', 'white', 0x3 ],
			[ 'Ke7', 'black', 0xB ],
			[ 'Bg2', 'white', 0x5 ],
			[ 'Bh7', 'black', 0xD ],
			[ 'Rh2', 'white', 0x6 ],
			[ 'Rg7', 'black', 0xE ],
		];
	}

	/**
	 * @covers ::getPromotion
	 * @dataProvider provideGetPromotion
	 * @param string $notation
	 * @param string $piece
	 */
	public function testGetPromotion( string $notation, string $piece ) {
		$notationAnalyzer = new NotationAnalyzer( $notation );
		$this->assertSame( $piece, $notationAnalyzer->getPromotion() );
	}

	public static function provideGetPromotion() {
		return [
			[ 'exd8=Q', 'q' ],
			[ 'd8=R', 'r' ],
			[ 'c8=B', 'b' ],
			[ 'a1=N', 'n' ],
		];
	}
}

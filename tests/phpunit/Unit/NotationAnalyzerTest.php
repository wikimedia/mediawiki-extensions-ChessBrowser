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

use MediaWiki\Extension\ChessBrowser\NotationAnalyzer;

/**
 * @coversDefaultClass \MediaWiki\Extension\ChessBrowser\NotationAnalyzer
 */
class NotationAnalyzerTest extends \MediaWikiUnitTestCase {

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
		];
	}

}

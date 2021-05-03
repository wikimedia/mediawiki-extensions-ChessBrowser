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
 * @file ChessBrowserTest
 * @ingroup ChessBrowser
 * @author Wugapodes
 */

namespace MediaWiki\Extension\ChessBrowser\Tests;

use MediaWiki\Extension\ChessBrowser\ChessBrowser;
use MediaWiki\Extension\ChessBrowser\ChessBrowserException;
use MediaWikiIntegrationTestCase;

/**
 * @group ChessBrowser
 * @coversDefaultClass \MediaWiki\Extension\ChessBrowser\ChessBrowser
 */
class ChessBrowserTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::getLocalizedLabels
	 * @dataProvider provideGetLocalizedLabels
	 * @param array $expected
	 */
	public function testGetLocalizedLabels( array $expected ) {
		$labels = ChessBrowser::getLocalizedLabels();
		$this->assertEquals( $expected, $labels );
	}

	/**
	 * @covers ::getMetadata
	 * @dataProvider provideGetMetadata
	 * @param array $tagPairs
	 * @param array $expected
	 */
	public function testGetMetadata( array $tagPairs, array $expected ) {
		$labels = ChessBrowser::getMetadata( $tagPairs );
		$this->assertEquals( $expected, $labels );
	}

	/**
	 * @covers ::createPiece
	 * @dataProvider provideTestCreatePiece
	 * @param string $symbol
	 * @param int|string $rank
	 * @param int|string $file
	 * @param array $expected
	 */
	public function testCreatePiece( string $symbol, $rank, $file, array $expected ) {
		$piece = ChessBrowser::createPiece( $symbol, $rank, $file );
		$this->assertEquals( $expected, $piece );
	}

	/**
	 * @covers ::createPiece
	 * @dataProvider provideTestThrowsProperException
	 * @param string $expectedMessage
	 * @param string $symbol
	 * @param string|int $rank
	 * @param string|int $file
	 */
	public function testThrowsProperException(
		string $expectedMessage,
		string $symbol,
		$rank,
		$file
	) {
		$this->expectException( ChessBrowserException::class );
		$this->expectExceptionMessage( $expectedMessage );
		ChessBrowser::createPiece( $symbol, $rank, $file );
	}

	public static function provideTestThrowsProperException() {
		return [
			[ "Impossible rank (8) or file (0)", 'p', 8, 0 ],
			[ "Impossible rank (-1) or file (0)", 'p', -1, 0 ],
			[ "Impossible rank (0) or file (8)", 'p', 0, 8 ],
			[ "Impossible rank (0) or file (-1)", 'p', 0, -1 ],
			[ "Invalid piece type 0", '0', 0, 0 ],
		];
	}

	public static function provideGetMetadata() {
		return [
			[
				[
					'event' => 'Test match',
					'site' => 'Computer',
					'date' => '1852.??.??',
					'round' => '1',
					'white' => 'John Weiss',
					'black' => 'Jane Schwartz',
					'result' => '1-0',
					'whiteelo' => '1200',
					'blackelo' => '1201'
				],
				[
					'event' => 'Test match',
					'site' => 'Computer',
					'date' => '1852.??.??',
					'round' => '1',
					'white' => 'John Weiss',
					'black' => 'Jane Schwartz',
					'result' => '1-0',
					'other-metadata' => [
						[
							'label' => 'whiteelo',
							'value' => '1200'
						],
						[
							'label' => 'blackelo',
							'value' => '1201'
						]
					]
				]
			]
		];
	}

	public static function provideTestCreatePiece() {
		return [
			[
				'p',
				0,
				1,
				[
					'piece-type' => 'p',
					'piece-color' => 'd',
					'piece-rank' => '0',
					'piece-file' => '1'
				]
			],
			[
				'p',
				'0',
				'1',
				[
					'piece-type' => 'p',
					'piece-color' => 'd',
					'piece-rank' => '0',
					'piece-file' => '1'
				]
			],
			[
				'P',
				'0',
				'1',
				[
					'piece-type' => 'p',
					'piece-color' => 'l',
					'piece-rank' => '0',
					'piece-file' => '1'
				]
			],
			[
				'N',
				1,
				1,
				[
					'piece-type' => 'n',
					'piece-color' => 'l',
					'piece-rank' => '1',
					'piece-file' => '1'
				]
			],
			[
				'q',
				'0',
				'1',
				[
					'piece-type' => 'q',
					'piece-color' => 'd',
					'piece-rank' => '0',
					'piece-file' => '1'
				]
			],
			[
				'b',
				'0',
				'1',
				[
					'piece-type' => 'b',
					'piece-color' => 'd',
					'piece-rank' => '0',
					'piece-file' => '1'
				]
			]
		];
	}

	public static function provideGetLocalizedLabels() {
		return [
			[
				[
					'expand-button' => 'Expand',
					'game-detail' => 'Game details',
					'event-label' => 'Event',
					'site-label' => 'Site',
					'date-label' => 'Date',
					'round-label' => 'Round',
					'white-label' => 'White',
					'black-label' => 'Black',
					'result-label' => 'Result',
					'rank-1' => '1',
					'rank-2' => '2',
					'rank-3' => '3',
					'rank-4' => '4',
					'rank-5' => '5',
					'rank-6' => '6',
					'rank-7' => '7',
					'rank-8' => '8',
					'a' => 'a',
					'b' => 'b',
					'c' => 'c',
					'd' => 'd',
					'e' => 'e',
					'f' => 'f',
					'g' => 'g',
					'h' => 'h',
					'beginning' => 'Go to first move',
					'previous' => 'Previous move',
					'next' => 'Next move',
					'final' => 'Go to last move',
					'flip' => 'Flip board perspective',
					'no-javascript' => 'JavaScript is not enabled on this page. '
						. 'To view the game interactively, please enable JavaScript.'
				]
			]
		];
	}
}

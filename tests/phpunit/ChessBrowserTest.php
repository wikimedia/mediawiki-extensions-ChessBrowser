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
 *
 * @group ChessBrowser
 * @covers ChessBrowser
 */
class ChessBrowserTest extends MediaWikiTestCase {

	/**
	 * @dataProvider provideGetLocalizedLabels
	 * @param array $expected
	 */
	public function testGetLocalizedLabels( array $expected ) {
		$labels = ChessBrowser::getLocalizedLabels();
		$this->assertEquals( $expected, $labels );
	}

	/**
	 * @dataProvider provideGetMetadata
	 * @param array $tagPairs
	 * @param array $expected
	 */
	public function testGetMetadata( array $tagPairs, array $expected ) {
		$labels = ChessBrowser::getMetadata( $tagPairs );
		$this->assertEquals( $expected, $labels );
	}

	/**
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
	 * @dataProvider provideTestThrowsProperException
	 * @param function $testFunction
	 * @param array $args
	 * @param class $expectedException
	 * @param string $expectedMessage
	 */
	public function testThrowsProperException(
		callable $testFunction,
		array $args,
		$expectedException,
		$expectedMessage
	) {
		$this->expectException( $expectedException );
		$this->expectExceptionMessage( $expectedMessage );
		$testFunction( ...$args );
	}

	public static function provideCreatePieceTests() {
		$class = ChessBrowserException::class;
		$callback = function ( ...$args ) {
			return ChessBrowser::createPiece( ...$args );
		};
		$message = 'Impossible rank or file';
		$badRankOrFile = [
			[
				$callback,
				[
					'p',
					8,
					0
				],
				$class,
				$message
			],
			[
				$callback,
				[
					'p',
					-1,
					0
				],
				$class,
				$message
			],
			[
				$callback,
				[
					'p',
					0,
					8
				],
				$class,
				$message
			],
			[
				$callback,
				[
					'p',
					0,
					-1
				],
				$class,
				$message
			]
		];
		$badPieceType = [
			[
				$callback,
				[
					'0',
					0,
					0
				],
				ChessBrowserException::class,
				'Invalid piece type 0'
			]
		];
		return array_merge( $badRankOrFile, $badPieceType );
	}

	public static function provideTestThrowsProperException() {
		$createPieceTests = self::provideCreatePieceTests();
		return $createPieceTests;
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
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
					'7' => '7',
					'8' => '8',
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
					'slower' => 'Slower',
					'play' => 'Play/Pause',
					'faster' => 'Faster',
					'next' => 'Next move',
					'final' => 'Go to last move',
					'flip' => 'Flip board perspective',
					'no-javascript' => 'JavaScript is not enabled on this page. '
						. 'To view the game interactively, please enable Javascript.'
				]
			]
		];
	}
}

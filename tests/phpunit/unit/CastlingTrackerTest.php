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
 * @file CastlingTrackerTest
 * @ingroup ChessBrowser
 * @author DannyS712
 */

namespace MediaWiki\Extension\ChessBrowser\Tests\Unit;

use MediaWiki\Extension\ChessBrowser\CastlingTracker;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\ChessBrowser\CastlingTracker
 */
class CastlingTrackerTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::checkCastle
	 * @dataProvider provideTestCheck
	 * @param string $starting
	 * @param array $options
	 */
	public function testCheck(
		string $starting,
		array $options
	) {
		$tracker = new CastlingTracker( $starting );
		foreach ( $options as $option => $expected ) {
			$this->assertSame(
				$expected,
				$tracker->checkCastle( $option )
			);
		}
	}

	public function provideTestCheck() {
		return [
			[
				'KQkq',
				[
					'K' => true,
					'Q' => true,
					'k' => true,
					'q' => true,
				]
			],
			[
				'-',
				[
					'K' => false,
					'Q' => false,
					'k' => false,
					'q' => false,
				]
			],
			[
				'KQ',
				[
					'K' => true,
					'Q' => true,
					'k' => false,
					'q' => false,
				]
			],
			[
				'',
				[
					'K' => false,
					'Q' => false,
					'k' => false,
					'q' => false,
				]
			],
		];
	}

}

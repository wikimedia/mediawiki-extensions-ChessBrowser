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
 * @file ChessBrowserHooksTest
 * @ingroup ChessBrowser
 * @author DannyS712
 */

namespace MediaWiki\Extension\ChessBrowser\Tests\Unit;

use MediaWiki\Extension\ChessBrowser\ChessBrowserHooks;
use MediaWikiUnitTestCase;
use OutputPage;
use Parser;
use ParserOutput;

/**
 * @covers MediaWiki\Extension\ChessBrowser\ChessBrowserHooks
 */
class ChessBrowserHooksTest extends MediaWikiUnitTestCase {

	public function testParserFirstCallInit() {
		$mock = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( $this->once() )
			->method( 'setHook' )
			->with(
				$this->equalTo( 'pgn' ),
				$this->callback( function ( $param ) {
					 return is_callable( $param );
				} )
			);

		ChessBrowserHooks::onParserFirstCallInit( $mock );
	}

	public function testOutputPageParserOutput() {
		$mockOP = $this->createNoOpMock( OutputPage::class );

		$mockPO = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();
		$mockPO->expects( $this->once() )
			->method( 'getExtensionData' )
			->with( 'ChessViewerTrigger' )
			->willReturn( false );

		// if $trigger is false, no methods on outputpage are called
		ChessBrowserHooks::onOutputPageParserOutput( $mockOP, $mockPO );

		$mockOP = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$mockOP->expects( $this->once() )
			->method( 'addModules' )
			->with( 'ext.chessViewer' );
		$mockOP->expects( $this->once() )
			->method( 'addJsConfigVars' )
			->with(
				$this->equalTo( 'wgChessBrowserDivIdentifiers' ),
				$this->callback( function ( $param ) {
					return is_array( $param );
				} )
			);

		$mockPO = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();
		$mockPO->expects( $this->exactly( 2 ) )
			->method( 'getExtensionData' )
			->withConsecutive(
				[ $this->equalTo( 'ChessViewerTrigger' ) ],
				[ $this->equalTo( 'ChessViewerNumGames' ) ]
			)
			->will(
				$this->onConsecutiveCalls( true, 5 )
			);

		// if $trigger is true, outputpage methods are called
		ChessBrowserHooks::onOutputPageParserOutput( $mockOP, $mockPO );
	}

}

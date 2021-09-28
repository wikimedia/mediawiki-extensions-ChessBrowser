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

		$mock->expects( $this->exactly( 2 ) )
			->method( 'setHook' )
			->withConsecutive(
				[
					$this->equalTo( 'pgn' ),
					$this->callback( static function ( $param ) {
						 return is_callable( $param );
					} )
				],
				[
					$this->equalTo( 'fen' ),
					$this->callback( static function ( $param ) {
						 return is_callable( $param );
					} )
				]
			);

		ChessBrowserHooks::onParserFirstCallInit( $mock );
	}

	public function testOutputPageParserOutputNone() {
		$mockOP = $this->createNoOpMock( OutputPage::class );
		$mockPO = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();
		$mockPO->expects( $this->exactly( 2 ) )
			->method( 'getExtensionData' )
			->withConsecutive(
				[ $this->equalTo( 'ChessViewerFEN' ) ],
				[ $this->equalTo( 'ChessViewerTrigger' ) ]
			)
			->will(
				$this->onConsecutiveCalls( false, false )
			);

		// if $trigger is false, no methods on outputpage are called
		ChessBrowserHooks::onOutputPageParserOutput( $mockOP, $mockPO );
	}

	public function testOutputPageParserOutputPGN() {
		$mockOP = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$mockOP->expects( $this->once() )
			->method( 'addModuleStyles' )
			->with( [ 'ext.chessViewer.styles', 'jquery.makeCollapsible.styles' ] );

		$mockOP->expects( $this->once() )
			->method( 'addModules' )
			->with( [ 'ext.chessViewer', 'jquery.makeCollapsible' ] );

		$mockPO = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();
		$mockPO->expects( $this->exactly( 3 ) )
			->method( 'getExtensionData' )
			->withConsecutive(
				[ $this->equalTo( 'ChessViewerFEN' ) ],
				[ $this->equalTo( 'ChessViewerTrigger' ) ],
				[ $this->equalTo( 'ChessViewerNumGames' ) ]
			)
			->will(
				$this->onConsecutiveCalls( false, true, 5 )
			);

		// if $trigger is true, outputpage methods are called
		ChessBrowserHooks::onOutputPageParserOutput( $mockOP, $mockPO );
	}

	public function testOutputPageParserOutputFEN() {
		$mockOP = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$mockOP->expects( $this->once() )
			->method( 'addModuleStyles' )
			->with( 'ext.chessViewer.styles' );

		$mockPO = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();
		$mockPO->expects( $this->exactly( 2 ) )
			->method( 'getExtensionData' )
			->withConsecutive(
				[ $this->equalTo( 'ChessViewerFEN' ) ],
				[ $this->equalTo( 'ChessViewerTrigger' ) ]
			)
			->will(
				$this->onConsecutiveCalls( true, false )
			);

		// if $trigger is true, outputpage methods are called
		ChessBrowserHooks::onOutputPageParserOutput( $mockOP, $mockPO );
	}

}

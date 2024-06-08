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
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWikiUnitTestCase;

/**
 * @covers MediaWiki\Extension\ChessBrowser\ChessBrowserHooks
 */
class ChessBrowserHooksTest extends MediaWikiUnitTestCase {

	public function testParserFirstCallInit() {
		$mock = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$expectedTags = [
			'pgn' => true,
			'fen' => true,
		];
		$mock->expects( $this->exactly( 2 ) )
			->method( 'setHook' )
			->willReturnCallback( function ( $tag ) use ( &$expectedTags ) {
				$this->assertArrayHasKey( $tag, $expectedTags );
				unset( $expectedTags[$tag] );
			} );

		( new ChessBrowserHooks )->onParserFirstCallInit( $mock );
	}

	public function testOutputPageParserOutputNone() {
		$mockOP = $this->createNoOpMock( OutputPage::class );
		$mockPO = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();
		$mockPO->expects( $this->exactly( 2 ) )
			->method( 'getExtensionData' )
			->willReturnMap( [
				[ 'ChessViewerFEN', false ],
				[ 'ChessViewerTrigger', false ],
			] );

		// if $trigger is false, no methods on outputpage are called
		( new ChessBrowserHooks )->onOutputPageParserOutput( $mockOP, $mockPO );
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
		$mockPO->expects( $this->exactly( 2 ) )
			->method( 'getExtensionData' )
			->willReturnMap( [
				[ 'ChessViewerFEN', false ],
				[ 'ChessViewerTrigger', true ],
			] );

		// if $trigger is true, outputpage methods are called
		( new ChessBrowserHooks )->onOutputPageParserOutput( $mockOP, $mockPO );
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
			->willReturnMap( [
				[ 'ChessViewerFEN', true ],
				[ 'ChessViewerTrigger', false ],
			] );

		// if $trigger is true, outputpage methods are called
		( new ChessBrowserHooks )->onOutputPageParserOutput( $mockOP, $mockPO );
	}

}

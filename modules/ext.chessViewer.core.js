/**
 * This file is a part of ChessBrowser.
 *
 * This file is in the public domain. It is licensed under the CC0 1.0 license.
 *
 * Original source taken from https://www.mediawiki.org/w/index.php?title=User:קיפודנחש/chess-animator.js
 *
 * @ingroup ChessBrowser
 * @author Kipod
 * @author Wugapodes
 */

( function () {
	var chessPage;

	function Game( identifier ) {
		var me = this;
		this.id = identifier;
		this.$div = $( '#' + this.id );
		this.$pgnBoardImg = this.$div.find( '.pgn-board-img' );
		this.data = this.$div.data( 'chess' );
		this.boards = this.data.boards;
		this.plys = this.data.plys;
		this.tokens = this.data.tokens;
		this.metadata = this.data.metadata;
		this.boardStates = [];
		this.pieces = [];
		this.currentPlyNumber = 1;
		this.timer = null;
		this.delay = 800;
		this.allPositionClasses = '01234567'
			.split( '' )
			.map( function ( r ) { return 'pgn-prow-' + r + ' pgn-pfile-' + r; } )
			.join( ' ' );

		this.makeBoard = function ( display ) {
			var board,
				plyIndex;
			// display is an optional argument; defaults to last board state if undefined
			display = display || me.plys.length;
			// the parser put its own pieces for "noscript" viewers, remove those first
			me.$div.find( '.pgn-chessPiece' ).remove();
			board = me.processFen( me.metadata.fen );
			me.boardStates.push( board );
			for ( plyIndex in me.plys ) {
				board = me.processPly( board, me.plys[ plyIndex ] );
				me.boardStates.push( board );
			}
			me.connectButtons();
			me.goToBoard( display );
		};

		this.processFen = function ( fen ) {
			var fenArray = fen.split( '/' ),
				fenLine,
				fenTokenList,
				fenToken,
				file,
				piecePosition,
				i,
				j,
				board = [],
				rank;
			for ( i in fenArray ) {
				file = 0;
				rank = 7 - i;
				fenLine = fenArray[ i ];
				fenTokenList = fenLine.split( '' );
				for ( j in fenTokenList ) {
					fenToken = fenTokenList[ j ];
					if ( file > 7 ) {
						break;
					}
					if ( /[prnbqk]/i.test( fenToken ) ) {
						piecePosition = file * 8 + rank;
						board[ piecePosition ] = me.createPiece( fenToken, rank, file );
						file++;
					} else {
						file += parseInt( fenToken );
					}
				}
			}
			return board;
		};

		this.processPly = function ( board, ply ) {
			var newBoard = board.slice(),
				source = ply[ 0 ],
				destination = ply[ 1 ],
				special = ply[ 2 ],
				specialType = special[ 0 ];

			newBoard[ destination ] = newBoard[ source ];
			delete newBoard[ source ];
			switch ( specialType ) {
				case 'en passant':
					delete newBoard[ special[ 1 ] ];
					break;
				case 'castle':
					newBoard[ special[ 1 ][ 1 ] ] = newBoard[ special[ 1 ][ 0 ] ];
					delete newBoard[ special[ 1 ][ 0 ] ];
					break;
				case 'promotion':
					newBoard[ destination ] = me.createPiece(
						special[ 1 ],
						source[ 0 ],
						source[ 1 ]
					);
					break;
			}
			return newBoard;
		};

		this.createPiece = function ( symbol, rank, file ) {
			var lowerSymbol = symbol.toLowerCase(),
				color = symbol === lowerSymbol ? 'd' : 'l',
				$pieceObject = $( '<div>' )
					.data( 'piece', symbol )
					.addClass( 'pgn-chessPiece' )
					.addClass( 'pgn-ptype-color-' + lowerSymbol + color )
					.addClass( 'pgn-prow-' + rank )
					.addClass( 'pgn-pfile-' + file );
			me.pieces.push( $pieceObject );
			$pieceObject.appendTo( me.$pgnBoardImg );
			return $pieceObject;
		};

		this.isOnBoard = function ( board ) {
			return function ( $piece ) {
				return board.indexOf( $piece ) !== -1;
			};
		};

		this.goToBoard = function ( index ) {
			var $piece,
				pieceIndex,
				$notation,
				board = me.boardStates[ index ],
				piecesToAppear = board.filter(
					me.isOnBoard( board )
				),
				toHide = me.pieces.filter(
					me.isOnBoard( me.boardStates[ me.currentPlyNumber ] )
				);

			for ( pieceIndex in toHide ) {
				toHide[ pieceIndex ].addClass( 'pgn-piece-hidden' );
			}

			for ( pieceIndex in board ) {
				$piece = board[ pieceIndex ];
				if ( typeof $piece === 'undefined' ) {
					continue;
				}
				$piece.removeClass( me.allPositionClasses )
					.removeClass( 'pgn-piece-hidden' )
					.toggleClass(
						'pgn-transition-immediate',
						piecesToAppear.indexOf( $piece ) > -1
					)
					.addClass(
						'pgn-prow-' +
						parseInt( pieceIndex % 8 )
					)
					.addClass(
						'pgn-pfile-' +
						parseInt( Math.floor( pieceIndex / 8 ) )
					);
			}

			if ( index === me.boards.length ) {
				me.stopAutoplay();
			}

			$( '.pgn-current-move', me.$div ).removeClass( 'pgn-current-move' );
			me.currentPlyNumber = index;
			$notation = $( "[data-ply='" + ( me.currentPlyNumber ) + "']", me.$div );
			$notation.addClass( 'pgn-current-move' );
			me.scrollNotationToView( $notation );
		};

		this.scrollNotationToView = function ( $notation ) {
			var $parent = $notation.closest( '.pgn-notations' ),
				parentsHeight = $parent.height(),
				notationHeight = $notation.height(),
				notationTop = $notation.position().top,
				toMove,
				scrollTop;

			if ( notationTop < 0 || notationTop + notationHeight > parentsHeight ) {
				toMove = ( parentsHeight - notationHeight ) / 2 - notationTop;
				scrollTop = $parent.prop( 'scrollTop' );
				$parent.prop( {
					scrollTop: scrollTop - toMove
				} );
			}
		};

		this.connectButtons = function () {
			$( '.pgn-button-advance', me.$div ).on( 'click', me.advance );
			$( '.pgn-button-retreat', me.$div ).on( 'click', me.retreat );
			$( '.pgn-button-tostart', me.$div ).on( 'click', me.goToStart );
			$( '.pgn-button-toend', me.$div ).on( 'click', me.goToEnd );
			$( '.pgn-button-play', me.$div ).on( 'click', me.clickPlay );
			$( '.pgn-button-faster', me.$div ).on( 'click', me.faster );
			$( '.pgn-button-slower', me.$div ).on( 'click', me.slower );
			$( '.pgn-button-flip', me.$div ).on( 'click', me.flipBoard );
			$( '.pgn-movelink', me.$div ).on( 'click', me.clickNotation );
		};

		this.advance = function () {
			if ( me.currentPlyNumber < me.boards.length ) {
				me.goToBoard( me.currentPlyNumber + 1 );
			}
		};

		this.retreat = function () {
			if ( me.currentPlyNumber > 0 ) {
				me.goToBoard( me.currentPlyNumber - 1 );
			}
		};

		this.goToStart = function () {
			me.goToBoard( 0 );
			me.stopAutoplay();
		};

		this.goToEnd = function () {
			me.goToBoard( me.boards.length );
		};

		this.clickPlay = function () {
			if ( me.currentPlyNumber === me.boards.length - 1 ) {
				me.goToBoard( 0 );
			}
			if ( me.timer ) {
				me.stopAutoplay();
			} else {
				me.startAutoplay();
			}
		};

		this.faster = function () {
			me.delay = me.delay > 3200 ? me.delay - 1600 : me.delay / 2;
			me.changeDelay();
		};

		this.slower = function () {
			me.delay += Math.min( me.delay, 1600 );
			me.changeDelay();
		};

		this.flipBoard = function () {
			// eslint-disable-next-line no-jquery/no-class-state
			me.$div.toggleClass( 'pgn-flip' );
			// eslint-disable-next-line no-jquery/no-class-state
			$( '.pgn-button-flip', me.$div ).toggleClass( 'pgn-image-button-on' );
		};

		this.clickNotation = function () {
			me.stopAutoplay();
			// Importantly, 'this' is the object which was clicked, NOT the object instance
			me.goToBoard( $( this ).data( 'ply' ) );
		};

		this.startAutoplay = function () {
			me.timer = setInterval( me.advance, me.delay );
			$( '.pgn-button-play', me.$div ).addClass( 'pgn-image-button-on' );
		};

		this.stopAutoplay = function () {
			clearTimeout( me.timer );
			$( '.pgn-button-play', me.$div ).removeClass( 'pgn-image-button-on' );
			me.timer = null;
		};

		this.changeDelay = function () {
			if ( me.delay < 400 ) {
				me.delay = 400;
			}
			if ( me.timer ) {
				me.stopAutoplay();
				me.startAutoplay();
			}
		};
	}

	function ChessPage() {
		var me = this;
		this.gameInstances = [];
		// eslint-disable-next-line no-undef
		this.identifierList = mw.config.get( 'wgChessBrowserDivIdentifiers' );

		this.gameFactory = function () {
			var index,
				newGameInstance;
			for ( index in me.identifierList ) {
				newGameInstance = new Game( me.identifierList[ index ] );
				newGameInstance.makeBoard();
				me.gameInstances.push( newGameInstance );
			}
		};

		this.gameFactory();
	}

	// eslint-disable-next-line
	chessPage = new ChessPage();
	$( chessPage.gameInstances ).each( function ( game ) {
		$( '.pgn-nojs-message', game.$div ).hide();
	} );
	// eslint-disable-next-line no-undef
	mw.config.set( 'wgChessBrowserPage', chessPage );
}() );

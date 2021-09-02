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
	var gameInstances = [];

	function Game( $elem ) {
		var me = this;

		this.$div = $elem;
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
			// the parser put its own pieces for "noscript" viewers, remove those first
			me.$div.find( '.pgn-chessPiece' ).remove();
			board = me.processFen( me.metadata.fen );
			me.boardStates.push( board );
			for ( plyIndex in me.plys ) {
				board = me.processPly( board, me.plys[ plyIndex ] );
				me.boardStates.push( board );
			}
			me.loadButtons();
			me.connectButtons();

			// display is an optional argument; defaults to last board state if undefined
			me.goToBoard( display || me.plys.length );
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
					if ( file > 7 ) {
						break;
					}

					fenToken = fenTokenList[ j ];
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
					// The following classes are used here:
					// * pgn-ptype-color-bd
					// * pgn-ptype-color-bl
					// * pgn-ptype-color-kd
					// * pgn-ptype-color-kl
					// * pgn-ptype-color-nd
					// * pgn-ptype-color-nl
					// * pgn-ptype-color-pd
					// * pgn-ptype-color-pl
					// * pgn-ptype-color-qd
					// * pgn-ptype-color-ql
					// * pgn-ptype-color-rd
					// * pgn-ptype-color-rl
					.addClass( 'pgn-ptype-color-' + lowerSymbol + color )
					// The following classes are used here:
					// * pgn-prow-0
					// * pgn-prow-1
					// * pgn-prow-2
					// * pgn-prow-3
					// * pgn-prow-4
					// * pgn-prow-5
					// * pgn-prow-6
					// * pgn-prow-7
					.addClass( 'pgn-prow-' + rank )
					// The following classes are used here:
					// * pgn-pfile-0
					// * pgn-pfile-1
					// * pgn-pfile-2
					// * pgn-pfile-3
					// * pgn-pfile-4
					// * pgn-pfile-5
					// * pgn-pfile-6
					// * pgn-pfile-7
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
				// The following classes are used here:
				// * pgn-pfile-0
				// * pgn-pfile-1
				// * pgn-pfile-2
				// * pgn-pfile-3
				// * pgn-pfile-4
				// * pgn-pfile-5
				// * pgn-pfile-6
				// * pgn-pfile-7
				// * pgn-prow-0
				// * pgn-prow-1
				// * pgn-prow-2
				// * pgn-prow-3
				// * pgn-prow-4
				// * pgn-prow-5
				// * pgn-prow-6
				// * pgn-prow-7
				$piece.removeClass( me.allPositionClasses )
					.removeClass( 'pgn-piece-hidden' )
					.toggleClass(
						'pgn-transition-immediate',
						piecesToAppear.indexOf( $piece ) > -1
					)
					// The following classes are used here:
					// * pgn-prow-0
					// * pgn-prow-1
					// * pgn-prow-2
					// * pgn-prow-3
					// * pgn-prow-4
					// * pgn-prow-5
					// * pgn-prow-6
					// * pgn-prow-7
					.addClass(
						'pgn-prow-' +
						parseInt( pieceIndex % 8 )
					)
					// The following classes are used here:
					// * pgn-pfile-0
					// * pgn-pfile-1
					// * pgn-pfile-2
					// * pgn-pfile-3
					// * pgn-pfile-4
					// * pgn-pfile-5
					// * pgn-pfile-6
					// * pgn-pfile-7
					.addClass(
						'pgn-pfile-' +
						parseInt( Math.floor( pieceIndex / 8 ) )
					);
			}

			if ( index === me.boards.length ) {
				me.stopAutoplay();
			}

			$( '.pgn-button-retreat, .pgn-button-tostart', me.$div )
				.prop( 'disabled', index === 0 );
			$( '.pgn-button-advance, .pgn-button-toend', me.$div )
				.prop( 'disabled', index === me.boards.length );

			me.currentPlyNumber = index;
			$( '.pgn-current-move', me.$div ).removeClass( 'pgn-current-move' );
			$notation = $( "[data-ply='" + ( me.currentPlyNumber ) + "']", me.$div )
				.addClass( 'pgn-current-move' );
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

		this.loadButtons = function () {
			var buttonsTemplate = mw.template.get( 'ext.chessViewer', 'ChessControls.mustache' );
			var data = {
				beginning: mw.msg( 'chessbrowser-beginning-of-game' ),
				previous: mw.msg( 'chessbrowser-previous-move' ),
				slower: mw.msg( 'chessbrowser-slow-autoplay' ),
				play: mw.msg( 'chessbrowser-play-pause-button' ),
				faster: mw.msg( 'chessbrowser-fast-autoplay' ),
				next: mw.msg( 'chessbrowser-next-move' ),
				final: mw.msg( 'chessbrowser-end-of-game' ),
				flip: mw.msg( 'chessbrowser-flip-board' )
			};
			var $html = buttonsTemplate.render( data );
			me.$div.find( '.pgn-controls' ).append( $html );
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
			$( '.pgn-movelink', me.$div ).attr( {
				tabindex: 0,
				role: 'button'
			} ).on( 'click keydown', me.notationHandler );
		};

		this.advance = function ( e ) {
			if ( me.currentPlyNumber < me.boards.length ) {
				me.goToBoard( me.currentPlyNumber + 1 );
			}
			if ( e ) {
				/* Only when triggerd by mouseclick */
				e.preventDefault();
			}
		};

		this.retreat = function ( e ) {
			if ( me.currentPlyNumber > 0 ) {
				me.goToBoard( me.currentPlyNumber - 1 );
			}
			e.preventDefault();
		};

		this.goToStart = function ( e ) {
			me.goToBoard( 0 );
			me.stopAutoplay();
			e.preventDefault();
		};

		this.goToEnd = function ( e ) {
			me.goToBoard( me.boards.length );
			e.preventDefault();
		};

		this.clickPlay = function ( e ) {
			if ( me.currentPlyNumber === me.boards.length - 1 ) {
				me.goToBoard( 0 );
			}
			if ( me.timer ) {
				me.stopAutoplay();
			} else {
				me.startAutoplay();
			}
			e.preventDefault();
		};

		this.faster = function ( e ) {
			me.delay = me.delay > 3200 ? me.delay - 1600 : me.delay / 2;
			me.changeDelay();
			e.preventDefault();
		};

		this.slower = function ( e ) {
			me.delay += Math.min( me.delay, 1600 );
			me.changeDelay();
			e.preventDefault();
		};

		this.flipBoard = function ( e ) {
			// eslint-disable-next-line no-jquery/no-class-state
			me.$div.toggleClass( 'pgn-flip' );
			var $button = $( '.pgn-button-flip', me.$div );
			$button.attr( 'aria-checked', !( $button.attr( 'aria-checked' ) === 'true' ) );
			e.preventDefault();
		};

		this.notationHandler = function ( e ) {
			if ( e.type === 'keydown' ) {
				if ( e.which !== 13 && e.which !== 32 ) {
					return;
				}
			}
			// Handle click, return and space keys
			me.updateToNotation( e.target );
			e.preventDefault();
		};

		this.updateToNotation = function ( target ) {
			me.stopAutoplay();
			me.goToBoard( $( target ).data( 'ply' ) );
		};

		this.startAutoplay = function () {
			me.timer = setInterval( me.advance, me.delay );
			$( '.pgn-button-play', me.$div ).attr( 'aria-checked', true );
		};

		this.stopAutoplay = function () {
			clearTimeout( me.timer );
			$( '.pgn-button-play', me.$div ).attr( 'aria-checked', false );
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

	mw.hook( 'wikipage.content' ).add( function ( $content ) {
		var newGameInstance;
		$( '.pgn-viewer', $content ).each( function ( index, elem ) {
			newGameInstance = new Game( $( elem ) );
			newGameInstance.makeBoard();
			/* Add CSS class to indicate that the loading phase is complete */
			newGameInstance.$div.addClass( 'pgn-loaded' );

			gameInstances.push( newGameInstance );
		} );
	} );

}() );

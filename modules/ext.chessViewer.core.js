/**
 * This file is a part of ChessBrowser.
 *
 * This file is in the public domain. It is licensed under the CC0 1.0 license.
 *
 * Original source taken from https://www.mediawiki.org/w/index.php?title=User:קיפודנחש/chess-animator.js
 *
 * @author Kipod
 * @author Wugapodes
 */

( function () {
	const gameInstances = [];

	/**
	 * Represents a single chess game.
	 *
	 * @class
	 * @param {jQuery} $elem - The jQuery element containing the game data.
	 */
	function Game( $elem ) {
		const me = this;

		this.$div = $elem;
		this.$pgnBoardImg = this.$div.find( '.pgn-board-img' );
		this.data = this.$div.data( 'chess' );
		this.$variations = this.$div.find( '.pgn-variation' );
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
			.map( ( r ) => 'pgn-prow-' + r + ' pgn-pfile-' + r )
			.join( ' ' );

		/**
		 * Initializes the board and loads initial data.
		 *
		 * @param {number} [display] - Optional argument to specify the initial board state
		 * to display.
		 */
		this.makeBoard = function ( display ) {
			let board,
				plyIndex;
			// the parser put its own pieces for "noscript" viewers, remove those first
			me.$div.find( '.pgn-chessPiece' ).remove();

			// Build all the different boardstates
			board = me.processFen( me.metadata.fen );
			me.boardStates.push( board );
			for ( plyIndex in me.plys ) {
				board = me.processPly( board, me.plys[ plyIndex ] );
				me.boardStates.push( board );
			}
			me.loadButtons();
			me.connectButtons();
			me.makeAccessibleBoard();

			display = Number( me.data.init );
			// display is an optional argument; defaults to last board state if undefined
			me.goToBoard( display || me.plys.length );
			// After loading everything, remove .notransition so that transitions work
			me.$div.removeClass( 'notransition' );
		};

		/**
		 * Makes the chessboard accessible for screen readers.
		 */
		this.makeAccessibleBoard = function () {
			let files = [ 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H' ],
				rank,
				file,
				$row,
				$cell,
				$grid;

			// Hide the legend, as the invidual chess squares have labels
			me.$div.find( '.pgn-row-legend' ).attr( 'aria-hidden', true );
			me.$div.find( '.pgn-file-legend' ).attr( 'aria-hidden', true );

			// Build the accessible version of chessboard
			$grid = $( '<div>' )
				.addClass( 'pgn-grid' )
				.attr( {
					role: 'grid',
					'aria-label': mw.msg( 'chessbrowser-chessboard-label' )
				} );

			for ( let i = 0; i < 64; i++ ) {
				rank = 8 - Math.floor( i / 8 );
				file = files[ i % 8 ];
				if ( i % 8 === 0 ) {
					$row = $( '<div>' ).attr( 'role', 'row' );
					$grid.append( $row );
				}
				$cell = $( '<div>' )
					.addClass( 'pgn-grid-cell' )
					.attr( 'role', 'gridcell' )
					.data( {
						file: file,
						rank: rank
					} )
					.text( me.fileToMsg( file ) + me.rankToMsg( rank ) );
				$row.append( $cell );
			}
			$( '.pgn-board-div', me.$div ).append( $grid );

			// Captioning div to announce the moves
			me.$div.append( $( '<div>' )
				.addClass( 'pgn-captioning' )
				.attr( 'aria-live', 'polite' )
			);
		};

		/**
		 * Processes a FEN string and returns the initial board state.
		 *
		 * @param {string} fen - The FEN string representing the initial board state.
		 * @return {Array} The board state array.
		 */
		this.processFen = function ( fen ) {
			let fenArray = fen.split( '/' ),
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

		/**
		 * Processes a single move (ply) and updates the board state.
		 *
		 * @param {Array} board - The current board state.
		 * @param {Array} ply - The move data.
		 * @return {Array} The updated board state.
		 */
		this.processPly = function ( board, ply ) {
			const newBoard = board.slice(),
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

		/**
		 * Creates a piece element.
		 *
		 * @param {string} symbol - The symbol representing the piece.
		 * @param {number} rank - The rank position of the piece.
		 * @param {number} file - The file position of the piece.
		 * @return {jQuery} The jQuery element representing the piece.
		 */
		this.createPiece = function ( symbol, rank, file ) {
			const lowerSymbol = symbol.toLowerCase(),
				color = symbol === lowerSymbol ? 'd' : 'l',
				$pieceObject = $( '<div>' )
					.data( {
						piece: lowerSymbol,
						rank: rank,
						file: file,
						color: color
					} )
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

		/**
		 * Checks if a piece is on the board.
		 *
		 * @param {Array} board - The current board state.
		 * @return {Function} A function that checks if a piece is on the board.
		 */
		this.isOnBoard = function ( board ) {
			return function ( $piece ) {
				return board.includes( $piece );
			};
		};

		/**
		 * Moves to the specified board state.
		 *
		 * @param {number} index - The index of the board state to display.
		 */
		this.goToBoard = function ( index ) {
			let $piece,
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
						piecesToAppear.includes( $piece )
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

			// a11y updates of grid labels
			me.updateAccessibleBoard( board );

			// State updates, incl. for a11y move announcements
			if ( index === 0 ) {
				me.announce( mw.msg( 'chessbrowser-boardstate-initial' ) );
			} else {
				me.announceMove( index - 1 );
			}
			if ( index === me.boards.length ) {
				me.stopAutoplay();
				me.announceAppend( mw.msg( 'chessbrowser-boardstate-final' ) );
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

		/**
		 * Updates the accessible board labels.
		 *
		 * @param {Array} board - The current board state.
		 */
		this.updateAccessibleBoard = function ( board ) {
			let i,
				color,
				offset = 8,
				offset2,
				$cell;
			for ( i = 0; i < 64; i++ ) {
				$cell = me.$div.find( '.pgn-grid-cell' ).eq( i );
				if ( i % 8 === 0 ) {
					offset--;
				}
				offset2 = offset + ( ( i % 8 ) * 8 );
				if ( typeof board[ offset2 ] !== 'undefined' ) {
					color = board[ offset2 ].data( 'color' ) === 'd' ? 'black' : 'white';
					$cell.text(
						// Messages that can be used here:
						// * chessbrowser-occupied-black
						// * chessbrowser-occupied-white
						mw.message(
							'chessbrowser-occupied-' + color,
							me.pieceToMsg( board[ offset2 ].data( 'piece' ), color ),
							me.positionToMsg(
								$cell.data( 'file' ),
								$cell.data( 'rank' )
							).toUpperCase()
						)
					);
					continue;
				}
				$cell.text(
					mw.message(
						'chessbrowser-empty-square',
						me.positionToMsg(
							$cell.data( 'file' ),
							$cell.data( 'rank' )
						).toUpperCase()
					)
				);
			}
		};

		/**
		 * Scrolls a notation element into view within its parent container.
		 *
		 * @param {Object} $notation - The jQuery object representing the
		 * notation element to scroll into view.
		 */
		this.scrollNotationToView = function ( $notation ) {
			let $parent = $notation.closest( '.pgn-notations' ),
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

		/**
		 * Loads navigation buttons.
		 */
		this.loadButtons = function () {
			const buttonsTemplate = mw.template.get( 'ext.chessViewer', 'ChessControls.mustache' );
			const data = {
				beginning: mw.msg( 'chessbrowser-beginning-of-game' ),
				previous: mw.msg( 'chessbrowser-previous-move' ),
				slower: mw.msg( 'chessbrowser-slow-autoplay' ),
				play: mw.msg( 'chessbrowser-play-pause-button' ),
				faster: mw.msg( 'chessbrowser-fast-autoplay' ),
				next: mw.msg( 'chessbrowser-next-move' ),
				final: mw.msg( 'chessbrowser-end-of-game' ),
				flip: mw.msg( 'chessbrowser-flip-board' )
			};
			const $html = buttonsTemplate.render( data );
			me.$div.find( '.pgn-controls' ).append( $html );
		};

		/**
		 * Attaches navigation buttons to their respective event listeners.
		 */
		this.connectButtons = function () {
			$( '.pgn-button-advance', me.$div ).on( 'click', me.advance );
			$( '.pgn-button-retreat', me.$div ).on( 'click', me.retreat );
			$( '.pgn-button-tostart', me.$div ).on( 'click', me.goToStart );
			$( '.pgn-button-toend', me.$div ).on( 'click', me.goToEnd );
			$( '.pgn-button-play', me.$div ).on( 'click', me.clickPlay );
			$( '.pgn-button-faster', me.$div ).on( 'click', me.faster );
			$( '.pgn-button-slower', me.$div ).on( 'click', me.slower );
			$( '.pgn-button-flip', me.$div ).on( 'click', me.flipBoard );
			$( me.$div ).on( 'keydown', me.keyboardNav );
			$( '.pgn-movelink', me.$div ).attr( {
				tabindex: 0,
				role: 'button'
			} ).on( 'click keydown', me.notationHandler );
		};

		/**
		 * Handle keydown events for active board.
		 *
		 * @param {event} e The keydown event passed
		 *
		 * TODO: Handle variation navigation once that is implemented.
		 */
		this.keyboardNav = function ( e ) {
			switch ( e.key ) {
				case 'ArrowRight':
					me.advance( e );
					break;
				case 'ArrowLeft':
					me.retreat( e );
					break;
				case 'ArrowUp':
					me.goToStart( e );
					break;
				case 'ArrowDown':
					me.goToEnd( e );
					break;
				default:
					return;
			}
		};

		/**
		 * Converts piece element to message for i18n purposes.
		 *
		 * @param {string} piece - The piece parameter.
		 * @param {string} color - The color parameter.
		 * @return {string} The corresponding message.
		 */
		this.pieceToMsg = function ( piece, color ) {
			// Messages that can be used here:
			// * chessbrowser-piece-black-king
			// * chessbrowser-piece-black-queen
			// * chessbrowser-piece-black-rook
			// * chessbrowser-piece-black-bishop
			// * chessbrowser-piece-black-knight
			// * chessbrowser-piece-black-pawn
			// * chessbrowser-piece-white-king
			// * chessbrowser-piece-white-queen
			// * chessbrowser-piece-white-rook
			// * chessbrowser-piece-white-bishop
			// * chessbrowser-piece-white-knight
			// * chessbrowser-piece-white-pawn
			switch ( piece.toUpperCase() ) {
				case 'K':
					return mw.msg( 'chessbrowser-piece-' + color + '-king' );
				case 'Q':
					return mw.msg( 'chessbrowser-piece-' + color + '-queen' );
				case 'R':
					return mw.msg( 'chessbrowser-piece-' + color + '-rook' );
				case 'B':
					return mw.msg( 'chessbrowser-piece-' + color + '-bishop' );
				case 'N':
					return mw.msg( 'chessbrowser-piece-' + color + '-knight' );
				case 'P':
				default:
					return mw.msg( 'chessbrowser-piece-' + color + '-pawn' );
			}
		};

		/**
		 * Converts rank number to message for i18n purposes.
		 *
		 * @param {number} rank - The rank number.
		 * @return {string} The corresponding message.
		 */
		this.rankToMsg = function ( rank ) {
			const rankToMsg = {
				1: mw.msg( 'chessbrowser-first-rank' ),
				2: mw.msg( 'chessbrowser-second-rank' ),
				3: mw.msg( 'chessbrowser-third-rank' ),
				4: mw.msg( 'chessbrowser-fourth-rank' ),
				5: mw.msg( 'chessbrowser-fifth-rank' ),
				6: mw.msg( 'chessbrowser-sixth-rank' ),
				7: mw.msg( 'chessbrowser-seventh-rank' ),
				8: mw.msg( 'chessbrowser-eighth-rank' )
			};
			return rankToMsg[ rank ];
		};

		/**
		 * Converts file character to message for i18n purposes.
		 *
		 * @param {string} file - The file character.
		 * @return {string} The corresponding message.
		 */
		this.fileToMsg = function ( file ) {
			// Messages that can be used here:
			// * chessbrowser-a-file
			// * chessbrowser-b-file
			// * chessbrowser-c-file
			// * chessbrowser-d-file
			// * chessbrowser-e-file
			// * chessbrowser-f-file
			// * chessbrowser-g-file
			// * chessbrowser-h-file
			return mw.msg( 'chessbrowser-' + file.toLowerCase() + '-file' );
		};

		/**
		 * Converts file and rank coordinates to a message format for i18n purposes.
		 *
		 * @param {number} file - The file coordinate.
		 * @param {number} rank - The rank coordinate.
		 * @return {string} The message representation of the position.
		 */
		this.positionToMsg = function ( file, rank ) {
			return me.fileToMsg( file ) + me.rankToMsg( rank );
		};

		/**
		 * Announces a message to screen readers.
		 *
		 * @param {string} text - The text to announce.
		 */
		this.announce = function ( text ) {
			me.$div.find( '.pgn-captioning' ).text( text );
		};

		/**
		 * Appends text to the announcement caption.
		 *
		 * @param {string} text - The text to append.
		 */
		this.announceAppend = function ( text ) {
			me.$div.find( '.pgn-captioning' ).text( me.$div.find( '.pgn-captioning' ).text() + ' ' + text );
		};

		/**
		 * Announce a move to screenreader users.
		 *
		 * @param {number} index The play as offset of the PGN tokens
		 *
		 * Note: Invididual parts of the announcement should end with . to
		 * make sure the pronunciation separates the words. Similarly,
		 * use capitals for the files to have them pronounced separately (much like abbreviations)
		 */
		this.announceMove = function ( index ) {
			let move = me.tokens[ index ],
				colorMsg = ( index % 2 ) === 0 ? 'chessbrowser-white-moves' : 'chessbrowser-black-moves',
				piece = 'P',
				pieceSpecifier = '',
				moveTypeMsg = 'chessbrowser-move',
				position = '',
				promotion = '',
				special = '',
				matches;
			if ( move === 'O-O-O' ) {
				me.announce( [
					// eslint-disable-next-line mediawiki/msg-doc
					mw.msg( colorMsg ),
					mw.msg( 'chessbrowser-castling-queenside' )
				].join( ' ' ) );
				return;
			}
			if ( move === 'O-O' ) {
				me.announce( [
					// eslint-disable-next-line mediawiki/msg-doc
					mw.msg( colorMsg ),
					mw.msg( 'chessbrowser-castling-kingside' )
				].join( ' ' ) );
				return;
			}

			matches = move.match( /^([KQRBNP]?)(([abcdefgh]?[12345678]?)(x?))([abcdefgh][12345678])(=([KQRBNP]))?([#+])?/ );
			if ( matches ) {
				piece = matches[ 1 ] || 'P';
			}
			piece = me.pieceToMsg( piece );
			if ( matches[ 3 ] ) {
				// In case we need a finer descriptor of the piece we are moving
				pieceSpecifier = matches[ 3 ].toUpperCase();
				// TODO: get full position always, maybe from previous board ?
				// Not everyone knows valid chess moves
			}
			if ( matches[ 4 ] === 'x' ) {
				moveTypeMsg = 'chessbrowser-capture';
			}
			position = matches[ 5 ].toUpperCase();
			if ( matches.length > 5 && matches[ 6 ] ) {
				promotion = mw.message( 'chessbrowser-promote', me.pieceToMsg( matches[ 7 ] ) );
			}
			if ( matches.length > 7 ) {
				switch ( matches[ 8 ] ) {
					case '+':
						special = mw.msg( 'chessbrowser-boardstate-check' );
						break;
					case '#':
						special = mw.msg( 'chessbrowser-boardstate-checkmate' );
						break;
				}
			}

			me.announce( [
				// eslint-disable-next-line mediawiki/msg-doc
				mw.msg( colorMsg ),
				// eslint-disable-next-line mediawiki/msg-doc
				mw.message( moveTypeMsg, piece, pieceSpecifier, position ),
				promotion,
				special
			].join( ' ' ) );
		};

		/**
		 * Advances to the next board position.
		 *
		 * @param {Event} e - The event object (optional).
		 */
		this.advance = function ( e ) {
			if ( me.currentPlyNumber < me.boards.length ) {
				me.goToBoard( me.currentPlyNumber + 1 );
			}
			if ( e ) {
				/* Only when triggerd by mouseclick */
				e.preventDefault();
			}
		};

		/**
		 * Retreats to the previous board position.
		 *
		 * @param {Event} e - The event object.
		 */
		this.retreat = function ( e ) {
			if ( me.currentPlyNumber > 0 ) {
				me.goToBoard( me.currentPlyNumber - 1 );
			}
			e.preventDefault();
		};

		/**
		 * Goes to the start of the board.
		 *
		 * @param {Event} e - The event object.
		 */
		this.goToStart = function ( e ) {
			me.goToBoard( 0 );
			me.stopAutoplay();
			e.preventDefault();
		};

		/**
		 * Goes to the end of the board.
		 *
		 * @param {Event} e - The event object.
		 */
		this.goToEnd = function ( e ) {
			me.goToBoard( me.boards.length );
			e.preventDefault();
		};

		/**
		 * Toggles autoplay on click.
		 *
		 * @param {Event} e - The event object.
		 */
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

		/**
		 * Increases the autoplay speed.
		 *
		 * @param {Event} e - The event object.
		 */
		this.faster = function ( e ) {
			me.delay = me.delay > 3200 ? me.delay - 1600 : me.delay / 2;
			me.changeDelay();
			e.preventDefault();
		};

		/**
		 * Decreases the autoplay speed.
		 *
		 * @param {Event} e - The event object.
		 */
		this.slower = function ( e ) {
			me.delay += Math.min( me.delay, 1600 );
			me.changeDelay();
			e.preventDefault();
		};

		/**
		 * Flips the board view.
		 *
		 * @param {Event} e - The event object.
		 */
		this.flipBoard = function ( e ) {
			// eslint-disable-next-line no-jquery/no-class-state
			me.$div.toggleClass( 'pgn-flip' );
			const $button = $( '.pgn-button-flip', me.$div );
			$button.attr( 'aria-checked', !( $button.attr( 'aria-checked' ) === 'true' ) );
			e.preventDefault();
		};

		/**
		 * Handles notation events.
		 *
		 * @param {Event} e - The event object.
		 */
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

		/**
		 * Updates the board to a specific notation.
		 *
		 * @param {HTMLElement} target - The target element.
		 */
		this.updateToNotation = function ( target ) {
			me.stopAutoplay();
			me.goToBoard( $( target ).data( 'ply' ) );
		};

		/**
		 * Starts autoplay.
		 */
		this.startAutoplay = function () {
			me.timer = setInterval( me.advance, me.delay );
			$( '.pgn-button-play', me.$div ).attr( 'aria-checked', true );
		};

		/**
		 * Stops autoplay.
		 */
		this.stopAutoplay = function () {
			clearTimeout( me.timer );
			$( '.pgn-button-play', me.$div ).attr( 'aria-checked', false );
			me.timer = null;
		};

		/**
		 * Changes the autoplay delay.
		 */
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

	mw.hook( 'wikipage.content' ).add( ( $content ) => {
		let newGameInstance;
		$( '.pgn-viewer', $content ).each( ( index, elem ) => {
			newGameInstance = new Game( $( elem ) );
			newGameInstance.makeBoard();
			/* Add CSS class to indicate that the loading phase is complete */
			newGameInstance.$div.addClass( 'pgn-loaded' );

			gameInstances.push( newGameInstance );
		} );
	} );

}() );

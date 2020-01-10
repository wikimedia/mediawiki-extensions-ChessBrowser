// Licensed under CC-BY-SA-4.0
// Written by קיפודנחש (Kipod)
// Original source taken from https://www.mediawiki.org/w/index.php?title=User:קיפודנחש/chess-animator.js

// Still some lint 12 Jan 2020
// Doesn't work with PHP output yet, likely because output format
//   is slightly different than JavaScript is expecting --12 Jan 2020
$( function () {

	var allPositionClasses = '01234567'
		.split( '' )
		.map( function ( r ) { return 'pgn-prow-' + r + ' pgn-pfile-' + r; } )
		.join( ' ' );

	function processOneDiv() {
		var $div = $( this ),
			data = $div.data( 'chess' ),
			boards,
			pieces = [],
			timer,
			delay = 800,
			$boardDiv = $div.find( '.pgn-board-img' ),
			currentPlyNum,
			board,
			ply,
			display = data.display || data.plys.length,
			ind;

		function createPiece( letter ) {
			var ll = letter.toLowerCase(),
				color = letter === ll ? 'd' : 'l',
				$piece = $( '<div>' )
					.data( 'piece', letter )
					.addClass( 'pgn-chessPiece pgn-ptype-color-' + ll + color )
					.appendTo( $boardDiv );
			pieces.push( $piece );
			return $piece;
		}

		function stopAutoplay() {
			clearTimeout( timer );
			$( '.pgn-button-play', $div ).removeClass( 'pgn-image-button-on' );
			timer = null;
		}

		function scrollNotationToView( $notation ) {
			var $daddy = $notation.closest( '.pgn-notations' ),
				daddysHeight = $daddy.height(),
				notationHeight = $notation.height(),
				notationTop = $notation.position().top,
				toMove,
				scrollTop;

			if ( notationTop < 0 || notationTop + notationHeight > daddysHeight ) {
				toMove = ( daddysHeight - notationHeight ) / 2 - notationTop;
				scrollTop = $daddy.prop( 'scrollTop' );
				$daddy.prop( {
					scrollTop: scrollTop - toMove
				} );
			}
		}

		function gotoBoard( plyNum ) {
			var previous = currentPlyNum,
				board = boards[ plyNum ],
				hiddenPieces = pieces.filter( function ( piece ) {
					return board.indexOf( piece ) === -1;
				} ),
				appearNow = board.filter( function ( piece ) {
					return typeof ( previous ) === 'number' && boards[ previous ].indexOf( piece ) === -1;
				} ),
				$notation,
				i,
				j;

			currentPlyNum = plyNum;
			for ( i in hiddenPieces ) {
				hiddenPieces[ i ].addClass( 'pgn-piece-hidden' );
			}
			for ( j in board ) {
				board[ j ]
					.removeClass( allPositionClasses + ' pgn-piece-hidden' )
					.toggleClass( 'pgn-transition-immediate', appearNow.indexOf( board[ j ] ) > -1 )
					.addClass( 'pgn-prow-' + parseInt( j / 8 ) + ' pgn-pfile-' + j % 8 );
			}
			if ( plyNum === boards.length - 1 ) {
				stopAutoplay();
			}
			$( '.pgn-movelink', $div ).removeClass( 'pgn-current-move' );
			$notation = $( '.pgn-movelink[data-ply=' + plyNum + ']', $div );
			if ( $notation.length ) {
				$notation.addClass( 'pgn-current-move' );
				scrollNotationToView( $notation );
			}
		}

		function advance() {
			if ( currentPlyNum < boards.length - 1 ) {
				gotoBoard( currentPlyNum + 1 );
			}
		}

		function startAutoplay() {
			timer = setInterval( advance, delay );
			$( '.pgn-button-play', $div ).addClass( 'pgn-image-button-on' );
		}

		function retreat() {
			if ( currentPlyNum > 0 ) {
				gotoBoard( currentPlyNum - 1 );
			}
		}

		function gotoStart() {
			gotoBoard( 0 );
			stopAutoplay();
		}

		function gotoEnd() {
			gotoBoard( boards.length - 1 );
		}

		function clickPlay() {
			if ( currentPlyNum === boards.length - 1 ) {
				gotoBoard( 0 );
			}
			if ( timer ) {
				stopAutoplay();
			} else {
				startAutoplay();
			}
		}

		function changeDelay() {
			delay = Math.min( delay, 400 );
			if ( timer ) {
				stopAutoplay();
				startAutoplay();
			}
		}

		function slower() {
			delay += Math.min( delay, 1600 );
			changeDelay();
		}

		function faster() {
			delay = delay > 3200 ? delay - 1600 : delay / 2;
			changeDelay();
		}

		function flipBoard() {
			// eslint-disable-next-line no-jquery/no-class-state
			$div.toggleClass( 'pgn-flip' );

			// eslint-disable-next-line no-jquery/no-class-state
			$( '.pgn-button-flip', $div ).toggleClass( 'pgn-image-button-on' );
		}

		function clickNotation() {
			stopAutoplay();
			gotoBoard( $( this ).data( 'ply' ) );
		}

		function connectButtons() {
			$( '.pgn-button-advance', $div ).on( 'click', advance );
			$( '.pgn-button-retreat', $div ).on( 'click', retreat );
			$( '.pgn-button-tostart', $div ).on( 'click', gotoStart );
			$( '.pgn-button-toend', $div ).on( 'click', gotoEnd );
			$( '.pgn-button-play', $div ).on( 'click', clickPlay );
			$( '.pgn-button-faster', $div ).on( 'click', faster );
			$( '.pgn-button-slower', $div ).on( 'click', slower );
			$( '.pgn-button-flip', $div ).on( 'click', flipBoard );
			$( '.pgn-movelink', $div ).on( 'click', clickNotation );
		}

		function processFen( fen ) {
			var fenAr = fen.split( '/' ),
				board = [],
				l,
				i,
				j,
				li,
				letters;
			for ( i in fenAr ) {
				j = 0;
				letters = fenAr[ i ].split( '' );
				for ( li in letters ) {
					l = letters[ li ];
					if ( /[prnbqk]/i.test( l ) ) {
						board[ ( 7 - i ) * 8 + j ] = createPiece( l );
						j++;
					} else {
						j += parseInt( l );
					}
				}
			}
			return board;
		}

		function processPly( board, ply ) {
			var newBoard = board.slice(),
				source = ply[ 0 ],
				destination = ply[ 1 ],
				special = ply[ 2 ];
			if ( typeof ( source ) === typeof ( ply ) ) { // castling. 2 source/dest pairs
				newBoard = processPly( newBoard, source );
				newBoard = processPly( newBoard, destination );
			} else {
				newBoard[ destination ] = newBoard[ source ];
				delete newBoard[ source ];
				if ( special ) {
					if ( typeof ( special ) === 'string' ) {
						newBoard[ destination ] = createPiece( special ); // promotion
					} else {
						delete newBoard[ special ]; // en passant
					}
				}
			}
			return newBoard;
		}

		if ( data ) {
			$div.find( '.pgn-chessPiece' ).remove(); // the parser put its own pieces for "noscript" viewers
			board = processFen( data.boards[0] );
			boards = [ board ];
			for ( ind in data.plys ) {
				ply = data.plys[ ind ];
				board = processPly( board, ply );
				boards.push( board );
			}
			connectButtons();
			gotoBoard( display );
		}
	}

	// eslint-disable-next-line no-jquery/no-global-selector
	$( '.pgnviewer' ).each( processOneDiv );

} );

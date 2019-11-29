/*
this work is placed by its authors in the public domain.
it was created from scratch, and no part of it was copied from elsewhere.
it can be used, copied, modified, redistributed, as-is or modified,
	whole or in part, without restrictions.
it can be embedded in a copyright protected work, as long as it's clear
	that the copyright does not apply to the embedded parts themselves.
please do not claim for yourself copyrights for this work or parts of it.
the work comes with no warranty or guarantee, stated or implied, including
	fitness for a particular purpose.
*/
'use strict';
window.mw.hook( 'wikipage.content' ).add( function ( $content ) {
	var // const, really, but linter...
		WHITE = 'l',
		BLACK = 'd',
		acode = 'a'.charCodeAt( 0 ),
		minBlockSize = 20,
		maxBlockSize = 60,
		boardPadding = 20,
		wrapperSelector = 'div.pgn-source-wrapper',
		defaultBlockSize = 36,
		sides = [ 'n', 'e', 's', 'w' ], // used for legends
		rowClassPrefix = 'pgn-prow-',
		fileClassPrefix = 'pgn-pfile-',
		allFilesAndColumnClasses = '01234567'
			.split( '' )
			.map( function ( i ) { return rowClassPrefix + i + ' ' + fileClassPrefix + i; } )
			.join( ' ' ),
		hiddenPiece = 'pgn-piece-hidden',
		chessboardClass = 'pgn-board-img',
		resetGameButtonClass = 'pgn-button-tostart',
		ffButtonClass = 'pgn-button-toend',
		advanceButtonClass = 'pgn-button-advance',
		retreatButtonClass = 'pgn-button-retreat',
		playButtonClass = 'pgn-button-play',
		fasterButtonClass = 'pgn-button-faster',
		slowerButtonClass = 'pgn-button-slower',
		flipBoardButtonClass = 'pgn-button-flip',
		ccButtonClass = 'pgn-button-cc',

		mw = window.mw,
		$ = window.$,
		mobile = mw.config.get( 'skin' ) === 'minerva';

	// some global, utility functions.
	function bindex( file, row ) {
		return row === undefined ? file : 8 * file + row;
	}
	function file( ind ) {
		return Math.floor( ind / 8 );
	}
	function row( ind ) {
		return ind % 8;
	}
	function sign( a, b ) {
		return a === b ? 0 : ( a < b ? 1 : -1 );
	}
	function fileOfStr( file ) {
		return file && file.charCodeAt( 0 ) - acode;
	}
	function rowOfStr( row ) {
		return row && ( row - 1 );
	}
	function indexOfMoveNotation( notation ) {
		var match = notation.match( /(\d+)([ld])/ );
		if ( match ) {
			return ( parseInt( match[ 1 ] ) - 1 ) * 2 + ( match[ 2 ] === 'l' ? 1 : 2 );
		}
		return 0;
	}
	function boardToFen( board ) {
		var res = [],
			len = function ( s ) {
				return s.length;
			};

		for ( var r = 0; r < 8; r++ ) {
			var row = '';
			for ( var f = 0; f < 8; f++ ) {
				row += board[ bindex( f, r ) ] ? board[ bindex( f, r ) ].fen() : ' ';
			}
			res.push( row.replace( /(\s+)/g, len ) );
		}
		return res.reverse().join( '/' ); // fen begins with row 8 file a - go figure...
	}

	// Classes
	function Button( className, action, stateful ) {
		var button = this;
		$.extend( button, {
			state: 0,
			setState: function ( state ) {
				var oldState = this.state;
				state = 0 + !!state;
				this.state = state;
				if ( stateful ) {
					this.elem
						.toggleClass( 'pgn-image-button-on', !!state )
						.toggleClass( 'pgn-image-button-off', !state );
				}
				if ( ( !stateful || state !== oldState ) && typeof ( action ) === 'function' ) {
					action( state );
				}
			},
			setVisible: function ( visible ) {
				this.elem.toggle( !!visible );
			},
			elem: $( '<div>' )
				.addClass( 'pgn-image-button pgn-image-button-off ' + className )
				.on( 'click', function () {
					button.setState( !button.state );
				} )
		} );
	}

	function Gameset( wrapperDiv ) { // set of functions and features that depend on blocksize, and currentGame.
		var gameSet = this;

		$.extend( gameSet, {
			wrapperDiv: wrapperDiv,
			tabberDiv: null,
			blockSize: defaultBlockSize,
			allGames: [],
			currentGame: null,
			showDetails: false,
			timer: null,
			autoPlayDelay: 750,
			// actions
			faster: function () {
				gameSet.autoPlayDelay -= 500;
				if ( gameSet.autoPlayDelay < 500 ) {
					gameSet.autoPlayDelay = 500;
				}
				gameSet.reportDelay();
			},
			slower: function () {
				gameSet.autoPlayDelay += 500;
				gameSet.reportDelay();
			},
			reportDelay: function () {
				gameSet.toggleAutoPlay(); // no param means keep state, but use new delay
				var message = gameSet.config.delay_msg ?
					gameSet.config.delay_msg.replace( '$sec$', 0.001 * gameSet.autoPlayDelay ) :
					0.001 * gameSet.autoPlayDelay;
				mw.notify( message, { tag: 'delay' } );
				// eventurally use config and format better message.
			},
			// 6 is half letter size (assuming font-size 0.875em)
			top: function ( row, l ) {
				return ( ( ( this.isFlipped ? row : ( 7 - row ) ) + ( l ? 0.3 : 0 ) ) * this.blockSize + ( l ? boardPadding - 6 : boardPadding ) ) + 'px';
			},
			left: function ( file, l ) {
				return ( ( ( this.isFlipped ? 7 - file : file ) + ( l ? 0.5 : 0 ) ) * this.blockSize + ( l ? boardPadding - 6 : boardPadding ) ) + 'px';
			},
			legendLocation: function ( side, num ) {
				switch ( side ) {
					case 'n':
						return { top: 0, left: this.left( num, true ) };
					case 'e':
						return { top: this.top( num, true ), left: this.blockSize * 8 + boardPadding + 5 };
					case 's':
						return { top: this.blockSize * 8 + 20, left: this.left( num, true ) };
					case 'w':
						return { top: this.top( num, true ), left: 5 };
				}
			},
			relocateLegends: function () {
				for ( var si in sides ) {
					for ( var n = 0; n < 8; n++ ) {
						this[ sides[ si ] ][ n ].css( this.legendLocation( sides[ si ], n ) );
					}
				}
			},
			selectGame: function ( val ) {
				var game = this.allGames[ val ];
				if ( game ) {
					game.analyzePgn();
					this.currentGame = game;
					this.ccButton.setVisible( game.hasComments() );
					game.show();
				}
			},
			refreshFEN: function () {
				var board = this.currentGame.boards[ this.currentGame.index ];
				this.fenDiv.text( boardToFen( board ) );
			},
			drawIfNeedRefresh: function () {
				if ( this.currentGame ) {
					this.currentGame.drawBoard();
				}
			},
			changeAppearance: function () {
				this.currentGame.drawBoard();
				this.relocateLegends();
			},
			setWidth: function ( width ) {
				width = width || this.blockSize;
				var
					widthPx = width * 8,
					widthPxPlus = widthPx + 40;
				this.tabberDiv // disgusting, but i could not get heightStyle of jquery tabs to do what i need.
					.css( { height: widthPxPlus } )
					.find( 'div' ).css( { height: mobile ? widthPxPlus : widthPx - 20 } );
				this.blockSize = width;
				this.piecesDiv.css( { width: widthPx, height: widthPx } );
				this.boardDiv.css( { width: widthPxPlus, height: widthPxPlus } );
				this.changeAppearance();
			},
			hideComments: function ( state ) {
				this.wrapperDiv.toggleClass( 'pgn-comments-hidden', state );
			},
			isFlipped: false,
			doFlip: function ( state ) {
				this.isFlipped = state;
				this.changeAppearance();
			},
			playing: false,
			toggleAutoPlay: function ( state ) {
				clearInterval( this.timer );
				if ( state === undefined ) {
					state = this.playing;
				}
				this.playing = state;
				if ( state ) {
					this.currentGame.wrapAround();
					this.timer = setInterval( function () {
						gameSet.currentGame.advance();
					}, gameSet.autoPlayDelay );
				}
			},
			stopAutoPlay: function () {
				gameSet.autoPlayButton.setState( false );
			}
		} );
	}

	function ChessPiece( type, color, game ) {
		this.game = game;
		this.type = type;
		this.color = color;
		this.avatar = $( '<div>' )
			.addClass( 'pgn-chessPiece pgn-ptype-color-' + type + color )
			.on( 'transitionstart', function () { $( this ).addClass( 'moving' ); } ) // supposedly elevates z-index
			.on( 'transitionend', function () { $( this ).removeClass( 'moving' ); } );
		var piece = this;
		$.extend( piece, {
			appear: function ( file, row ) {
				if ( game.gs.isFlipped ) {
					file = 7 - file;
					row = 7 - row;
				}
				this.avatar
					.removeClass( hiddenPiece )
					.removeClass( allFilesAndColumnClasses ) // remove them all
					.addClass( rowClassPrefix + row )
					.addClass( fileClassPrefix + file );
			},
			disappear: function () {
				return this.avatar.addClass( hiddenPiece );
			},
			setSquare: function ( file, row ) {
				this.file = file;
				this.row = row;
				this.onBoard = true;
			},
			capture: function ( file, row ) {
				if ( this.type === 'p' && !this.game.pieceAt( file, row ) ) { // en passant
					this.game.clearPieceAt( file, this.row );
				} else {
					this.game.clearPieceAt( file, row );
				}
				this.move( file, row );
			},
			move: function ( file, row ) {
				// with chess960 castling, we sometimes have to test.
				if ( this.game.pieceAt( this.file, this.row ) === this ) {
					this.game.clearSquare( this.file, this.row );
				}
				this.game.pieceAt( file, row, this ); // place it on the board)
			},
			pawnDirection: function () {
				return this.color === WHITE ? 1 : -1;
			},
			toString: function () {
				return this.type + this.color;
			},
			fen: function () {
				return this.color === WHITE ? this.type.toUpperCase() : this.type;
			},
			pawnStart: function () {

				return this.color === WHITE ? 1 : 6;
			},
			remove: function () {
				this.onBoard = false;
			},
			canMoveTo: function ( file, row, capture ) {

				if ( !this.onBoard ) {

					return false;
				}
				var rd = Math.abs( this.row - row ),
					fd = Math.abs( this.file - file );

				switch ( this.type ) {
					case 'n':
						return rd * fd === 2; // how nice that 2 is prime: its only factors are 2 and 1....
					case 'p':
						var dir = this.pawnDirection();

						return ( ( this.row === this.pawnStart() && row === this.row + dir * 2 && !fd && this.game.roadIsClear( this.file, file, this.row, row ) && !capture ) ||
							( this.row + dir === row && ( ( fd === 0 ) === ( !capture ) ) ) ); // advance 1, and either stay in file and no capture, or move exactly one
					case 'k':
						// Technical debt incurred Dec 2019
						// eslint-disable-next-line no-bitwise
						return ( rd | fd ) === 1; // we'll accept 1 and 1 or 1 and 0.
					case 'q':
						return ( rd - fd ) * rd * fd === 0 && this.game.roadIsClear( this.file, file, this.row, row ); // same row, same file or same diagonal.
					case 'r':
						return rd * fd === 0 && this.game.roadIsClear( this.file, file, this.row, row );
					case 'b':
						return rd === fd && this.game.roadIsClear( this.file, file, this.row, row );
				}

			}, // function canMoveTo
			matches: function ( oldFile, oldRow, isCapture, file, row ) {

				if ( typeof oldFile === 'number' && oldFile !== this.file ) {

					return false;
				}
				if ( typeof oldRow === 'number' && oldRow !== this.row ) {

					return false;
				}

				return this.canMoveTo( file, row, isCapture );
			}
		} ); // extend
	}

	function Game( gameSet ) {
		$.extend( this, {
			board: [],
			boards: [],
			pieces: [],
			notations: [],
			moves: [],
			index: 0,
			piecesByTypeCol: {},
			descriptions: {},
			comments: [],
			analyzed: false,
			gs: gameSet
		} );
	}

	Game.prototype.moveLinkText = function ( notation, color ) {
		var config = this.gs.config,
			tpiece = config && config.translate && config.translate.piece,
			tfile = config && config.translate && config.translate.file,
			trow = config && config.translate && config.translate.row;
		if ( tpiece ) {
			tpiece = color === WHITE && tpiece.white ||
				color === BLACK && tpiece.black ||
				tpiece;
			try {
				var regex = new RegExp( '(' + Object.keys( tpiece ).join( '|' ) + ')', 'g' );
				notation = notation.replace( regex, function ( c ) {
					return tpiece[ c ] || c;
				} );
			} catch ( e ) {
				mw.log( 'bad config.translate.pieces' );
				throw e;
			}
		}

		if ( tfile ) {
			try {
				notation = notation.replace( /[abcdefgh]/g, function ( c ) {
					return tfile[ c ] || c;
				} );
			} catch ( e ) {
				mw.log( 'bad config.translate.file' );
				throw e;
			}
		}
		if ( trow ) {
			try {
				notation = notation.replace( /[12345678]/g, function ( c ) {
					return trow[ c ] || c;
				} );
			} catch ( e ) {
				mw.log( 'bad config.translate.row' );
				throw e;
			}
		}

		// Technical debt incurred Dec 2019
		// eslint-disable-next-line no-jquery/no-trim
		return $.trim( notation.replace( /-/g, '\u2011' ) ) + ' ';
	};

	Game.prototype.show = function () {
		var desc = $.extend( {}, this.descriptions ),
			rtl = desc.Direction === 'rtl',
			gs = this.gs;

		// cleanup from previous game.
		gs.stopAutoPlay();

		// setup descriptions
		delete desc.Direction;
		gs.descriptionsDiv
			.empty()
			.css( { direction: rtl ? 'rtl' : 'ltr', textAlign: rtl ? 'right' : 'left' } );
		// Technical debt incurred Dec 2019
		// eslint-disable-next-line no-jquery/no-each-util
		$.each( desc, function ( key, val ) {
			gs.descriptionsDiv.append( key + ': ' + val + '<br />' );
		} );

		// setup pgn section
		gs.pgnDiv.empty().append( this.notations );

		// set the board.
		var hiddenAvatars = this.pieces.map( function ( piece ) {
			return piece.disappear();
		} );
		this.gs.piecesDiv.empty().append( hiddenAvatars );

		this.drawBoard();
	};

	Game.prototype.done = function () {
		return this.boards.length - 1 <= this.index;
	};

	Game.prototype.pieceAt = function ( file, row, piece ) {
		var i = bindex( file, row );
		if ( piece ) {
			this.board[ i ] = piece;
			piece.setSquare( file, row );
		}
		return this.board[ i ];
	};

	Game.prototype.clearSquare = function ( file, row ) {
		delete this.board[ bindex( file, row ) ];
	};

	Game.prototype.clearPieceAt = function ( file, row ) {
		var piece = this.pieceAt( file, row );

		if ( piece ) {
			piece.remove();
		}
		this.clearSquare( file, row );
	};

	Game.prototype.roadIsClear = function ( file1, file2, row1, row2 ) {

		var file = file1,
			row = row1,
			steps = 0,
			dfile = sign( file1, file2 ),
			drow = sign( row1, row2 );
		while ( true ) {
			file += dfile;
			row += drow;
			if ( file === file2 && row === row2 ) {
				return true;
			}
			if ( this.pieceAt( file, row ) ) {
				return false;
			}
			if ( steps++ > 10 ) {

				throw new Error( 'something is wrong in function roadIsClear.' +
					' file=' + file + ' file1=' + file1 + ' file2=' + file2 +
					' row=' + row + ' row1=' + row1 + ' row2=' + row2 +
					' dfile=' + dfile + ' drow=' + drow );
			}
		}
	};

	Game.prototype.addPieceToDicts = function ( piece ) {
		this.pieces.push( piece );
		var type = piece.type,
			color = piece.color,
			byType = this.piecesByTypeCol[ type ];
		if ( !byType ) {
			byType = this.piecesByTypeCol[ type ] = {};
		}
		var byTypeCol = byType[ color ];
		if ( !byTypeCol ) {
			byTypeCol = byType[ color ] = [];
		}
		byTypeCol.push( piece );
	};

	Game.prototype.advance = function ( delta ) {
		var m = this.index + ( delta || 1 ); // no param means 1 forward.
		if ( m >= 0 && m < this.boards.length ) {
			this.drawBoard( m );
		} else {
			this.gs.autoPlayButton.setState( false );
		}
	};

	Game.prototype.showCurrentMoveLink = function () {
		var moveLink = this.moves[ this.index ];
		if ( moveLink ) {
			moveLink.addClass( 'pgn-current-move' ).siblings().removeClass( 'pgn-current-move' );
			var wannabe = moveLink.parent().height() / 2,
				isNow = moveLink.position().top,
				newScrolltop = moveLink.parent()[ 0 ].scrollTop + isNow - wannabe;
			moveLink.parent().stop().animate( { scrollTop: newScrolltop }, 500 );
		}
	};

	Game.prototype.drawBoard = function ( index ) {
		if ( index === undefined ) {
			index = this.index;
		}
		if ( index < 0 ) {
			index += this.boards.length;
		}

		this.index = index;

		var board = this.boards[ index ];

		for ( var i in this.pieces ) {
			this.pieces[ i ].disappear();
		}
		for ( var b in board ) {
			if ( board[ b ] ) {
				board[ b ].appear( file( b ), row( b ) );
			}
		}
		this.showCurrentMoveLink();
		this.gs.refreshFEN();
	};

	Game.prototype.wrapAround = function () {
		if ( this.index >= this.boards.length - 1 ) {
			this.drawBoard( 0 );
		}
	};

	Game.prototype.castle = function ( color, side, kingTargetFile, rookTargetFile ) {
		var king = this.piecesByTypeCol.k[ color ][ 0 ],
			rook = this.piecesByTypeCol.r[ color ][ side ];
		if ( !rook || rook.type !== 'r' ) {
			throw new Error( 'attempt to castle without rook on appropriate square' );
		}
		king.move( fileOfStr( kingTargetFile ), king.row );
		rook.move( fileOfStr( rookTargetFile ), rook.row );
	};

	Game.prototype.kingSideCastle = function ( color ) {
		this.castle( color, 1, 'g', 'f' );
	};

	Game.prototype.queenSideCastle = function ( color ) {
		this.castle( color, 0, 'c', 'd' );
	};

	Game.prototype.promote = function ( piece, type, file, row, capture ) {
		piece[ capture ? 'capture' : 'move' ]( file, row );
		this.clearPieceAt( file, row );
		// Technical debt incurred Dec 2019
		// eslint-ignore-next-line no-unused-vars
		var newPiece = this.createPiece( type, piece.color, file, row );
	};

	Game.prototype.createPiece = function ( type, color, file, row ) {
		var piece = new ChessPiece( type, color, this );
		this.pieceAt( file, row, piece );
		this.addPieceToDicts( piece );
		return piece;
	};

	Game.prototype.createMove = function ( color, moveStr ) {

		moveStr = moveStr.replace( /^\s+|[!?+# ]*(\$\d{1,3})?$/g, '' ); // check, mate, comments, glyphs.
		if ( !moveStr.length ) {
			return false;
		}
		if ( moveStr === 'O-O' || moveStr === '0-0' ) {
			return this.kingSideCastle( color );
		}
		if ( moveStr === 'O-O-O' || moveStr === '0-0-0' ) {
			return this.queenSideCastle( color );
		}
		if ( moveStr === '1-0' || moveStr === '0-1' || moveStr === '1/2-1/2' || moveStr === '*' ) {
			return moveStr; // end of game - white wins, black wins, draw, game halted/abandoned/unknown.
		}

		var match = moveStr.match( /([RNBKQ])?([a-h])?([1-8])?(x)?([a-h])([1-8])(=[RNBKQ])?/ );

		if ( !match ) {
			return false;
		}

		var type = match[ 1 ] ? match[ 1 ].toLowerCase() : 'p',
			oldFile = fileOfStr( match[ 2 ] ),
			oldRow = rowOfStr( match[ 3 ] ),
			isCapture = !!match[ 4 ],
			file = fileOfStr( match[ 5 ] ),
			row = rowOfStr( match[ 6 ] ),
			promotion = match[ 7 ],
			thePiece;

		thePiece = $( this.piecesByTypeCol[ type ][ color ] ).filter( function () {
			return this.matches( oldFile, oldRow, isCapture, file, row );
		} );

		if ( thePiece.length !== 1 ) {
			var ok = false;
			if ( thePiece.length === 2 ) { // maybe one of them can't move because it protects the king?
				var king = this.piecesByTypeCol.k[ color ][ 0 ];
				for ( var i = 0; i < 2; i++ ) {
					var piece = thePiece[ i ];
					// lift the piece, check if the king is under threat
					delete this.board[ bindex( piece.file, piece.row ) ];
					for ( var j in this.board ) {
						var threat = this.board[ j ];
						if ( threat && threat.color !== color && threat.canMoveTo( king.file, king.row, true ) ) { // found that this piece can't move, so it's the other one...
							ok = true;
							thePiece = thePiece[ 1 - i ];
							break;
						}
					}
					// put the piece back in place
					this.board[ bindex( piece.file, piece.row ) ] = piece;
					if ( ok ) {
						break;
					}
				}
			}

			if ( !ok ) {

				throw new Error( 'could not find matching pieces. type="' + type + ' color=' + color + ' moveAGN="' + moveStr + '". found ' + thePiece.length + ' matching pieces' );
			}
		} else {
			thePiece = thePiece[ 0 ];
		}
		if ( promotion ) {
			this.promote( thePiece, promotion.toLowerCase().charAt( 1 ), file, row, isCapture );
		} else if ( isCapture ) {
			thePiece.capture( file, row );
		} else {
			thePiece.move( file, row );
		}
		return moveStr;
	};

	Game.prototype.addComment = function ( str ) {
		if ( !str ) {
			return;
		}
		this.notations.push( $( '<p>' )
			.addClass( 'pgn-comment' )
			.text( str.replace( /[{}()]/g, '' ) )
		);
	};

	Game.prototype.addDescription = function ( description ) {
		// Technical debt incurred Dec 2019
		// eslint-disable-next-line no-jquery/no-trim
		description = $.trim( description );
		var match = description.match( /\[([^"]+)"(.*)"\]/ );
		if ( match ) {
			// Technical debt incurred Dec 2019
			// eslint-disable-next-line no-jquery/no-trim
			this.descriptions[ $.trim( match[ 1 ] ) ] = match[ 2 ];
		}
	};

	Game.prototype.description = function () {
		var d = this.descriptions,
			round = d.Round ? ' (' + d.Round + ')' : '',
			s = d.Name || d[ 'שם' ] || (
				( d.Event || d[ 'אירוע' ] || '' ) + ': ' +
				( d.White || d[ 'לבן' ] || '' ) + ' - ' +
				( d.Black || d[ 'שחור' ] || '' ) + round
			);
		return s;
	};

	Game.prototype.preAnalyzePgn = function ( pgn ) {
		function tryMatch( regex ) {
			var match = pgn.match( regex );
			if ( match ) {
				pgn = pgn.replace( match, '' );
			}
			return match && match[ 0 ];
		}

		var match;
		while ( ( match = tryMatch( /^\s*\[[^\]]*\]/ ) ) !== null ) {
			this.addDescription( match );
		}
		this.pgn = pgn;
	};

	Game.prototype.analyzePgn = function () {

		if ( this.analyzed ) {
			return;
		}
		this.analyzed = true;
		var
			match,
			turn,
			moveNum = '',
			game = this,
			pgn = this.pgn;

		function removeHead( match ) {
			var ind = pgn.indexOf( match ) + match.length;
			pgn = pgn.substring( ind );
			return match;
		}

		function tryMatch( regex ) {
			var rmatch = pgn.match( regex );
			if ( rmatch ) {
				removeHead( rmatch[ 0 ] );
				moveNum = rmatch[ 1 ] || moveNum;
			}
			return rmatch && rmatch[ 0 ];
		}

		function addMoveLink( str, isMove, color ) {

			var notation = $( '<span>' )
				.addClass( isMove ? 'pgn-movelink' : 'pgn-steplink' )
				.text( game.moveLinkText( str, color ) );
			game.notations.push( notation );

			if ( isMove ) {
				game.boards.push( game.board.slice() );
				game.moves.push( notation );
			} else if ( game.moves.length === 0 ) {
				game.moves.push( notation );
			}

			var index = game.boards.length - 1;
			notation.on( 'click', function () {
				game.gs.stopAutoPlay();
				game.drawBoard( index );
			} );
		}

		pgn = pgn.replace( /;(.*)\n/g, ' {$1} ' ).replace( /\s+/g, ' ' ); // replace to-end-of-line comments with block comments, remove newlines and noramlize spaces to 1
		this.populateBoard( this.descriptions.FEN || 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR' );
		this.boards.push( this.board.slice() );
		var prevLen = -1;
		while ( pgn.length ) {

			if ( prevLen === pgn.length ) {
				throw new Error( 'analysePgn encountered a problem. pgn is: ' + pgn );
			}
			prevLen = pgn.length;
			this.addComment( tryMatch( /^\s*\{[^\}]*\}\s*/ ) );
			this.addComment( tryMatch( /^\s*\([^\)]*\)\s*/ ) );

			if ( ( match = tryMatch( /^\s*(\d+)\.+/ ) ) !== null ) {

				turn = /\.{3}/.test( match ) ? BLACK : WHITE;
				addMoveLink( match, false, turn );
				continue;
			}
			if ( ( match = tryMatch( /^\s*[^ ]+ ?/ ) ) !== null ) {

				this.createMove( turn, match );
				addMoveLink( match, true, turn );
				turn = BLACK;
			}
		}

		this.index = this.descriptions.FirstMove ?
			indexOfMoveNotation( this.descriptions.FirstMove ) :
			this.descriptions.FEN ? 0 : this.boards.length - 1;
	};

	Game.prototype.populateBoard = function ( fen ) {
		var fenar = fen.split( /[\/\s]/ );
		if ( fenar.length < 8 ) {
			throw new Error( 'illegal fen: "' + fen + '"' );
		}
		for ( var row = 0; row < 8; row++ ) {
			var file = 0,
				filear = fenar[ row ].split( '' );
			for ( var i in filear ) {
				var p = filear[ i ],
					lp = p.toLowerCase();
				if ( /[1-8]/.test( p ) ) {
					file += parseInt( p, 10 );
				} else if ( /[prnbkq]/.test( lp ) ) {
					this.createPiece( lp, ( p === lp ? BLACK : WHITE ), file++, 7 - row );
				} else {
					throw new Error( 'illegal fen: "' + fen + '"' );
				}
			}
		}
	};

	Game.prototype.hasComments = function () {
		var hasComment = function ( n ) {
			return n.hasClass( 'pgn-comment' );
		};
		return !!this.notations.filter( hasComment ).length;
	};

	function buildBoardDiv( container, selector, gameSet, ind ) {
		var
			id = container.attr( 'id' ) || 'pgn-viewer-' + ind,
			config = gameSet.config = container.data( 'config' ) || {},
			notationId = 'pgn-notation-' + id,
			infoId = 'pgn-info-' + id,
			fenId = 'pgn-fen-' + id,
			controlsDiv,
			createBotton = function ( className, param, todraw ) {
				return new Button( className,
					function () {
						gameSet.stopAutoPlay();
						if ( todraw ) {
							gameSet.currentGame.drawBoard( param );
						} else {
							gameSet.currentGame.advance( param );
						}
					} );
			},
			gotoend = createBotton( ffButtonClass, -1, true ),
			forward = createBotton( advanceButtonClass, 1 ),
			backstep = createBotton( retreatButtonClass, -1 ),
			gotostart = createBotton( resetGameButtonClass, 0, true ),
			flip = new Button( flipBoardButtonClass, function ( state ) { gameSet.doFlip( state ); }, true ),
			slower = new Button( slowerButtonClass, function () { gameSet.slower(); } ),
			faster = new Button( fasterButtonClass, function () { gameSet.faster(); } ),
			autoplay = new Button( playButtonClass, function ( state ) { gameSet.toggleAutoPlay( state ); }, true ),
			commentsToggle = new Button( ccButtonClass, function ( state ) { gameSet.hideComments( state ); }, true ),
			tabnames = $.extend( {
				notation: 'Game Notation',
				metadata: 'Information',
				fen: 'FEN' },
			config.tab_names );
		gameSet.autoPlayButton = autoplay;
		gameSet.ccButton = commentsToggle;
		gameSet.descriptionsDiv = $( '<div>', { class: 'pgn-descriptions', id: infoId } );
		gameSet.fixedDelay = 'delay' in config;
		if ( gameSet.fixedDelay ) {
			gameSet.autoPlayDelay = Math.max( 500, config.delay );
		}

		if ( $( '#' + notationId ) ) {
			notationId += '_1';
		}
		gameSet.pgnDiv = $( '<div>', { class: 'pgn-pgndiv', id: notationId } );
		gameSet.blockSize = Math.max( minBlockSize, Math.min( maxBlockSize, config.squareSize || defaultBlockSize ) );
		gameSet.fenDiv = $( '<div>', { id: fenId } )
			.css( { 'word-wrap': 'break-word' } );
		gameSet.tabberDiv = $( '<div>', { class: 'pgn-tabber' } );
		if ( mobile ) {
			gameSet.tabberDiv
				.append( gameSet.pgnDiv );
		} else {
			gameSet.tabberDiv
				.append( $( '<ul>' )
					.append( $( '<li>' ).append( $( '<a>', { href: '#' + notationId } ).text( tabnames.notation ) ) )
					.append( $( '<li>' ).append( $( '<a>', { href: '#' + infoId } ).text( tabnames.metadata ) ) )
					.append( $( '<li>' ).append( $( '<a>', { href: '#' + fenId } ).text( tabnames.fen ) ) )
				)
				.append( gameSet.pgnDiv )
				.append( gameSet.descriptionsDiv )
				.append( gameSet.fenDiv )
				.tabs();
		}
		var buttons = gameSet.fixedDelay ?
			[ gotostart, backstep, autoplay, forward, gotoend, flip, commentsToggle ] :
			[ gotostart, backstep, slower, autoplay, faster, forward, gotoend, flip, commentsToggle ];

		controlsDiv = $( '<div>', { class: 'pgn-controls' } )
			.css( { textAlign: 'center' } ) // todo: move to css
			.append( buttons.map( function ( x ) { return x.elem; } ) );

		gameSet.boardDiv = $( '<div>' )
			.addClass( 'pgn-board-div' );

		gameSet.piecesDiv = $( '<div>' ).css( { position: 'absolute', left: '20px', top: '20px' } )
			.addClass( chessboardClass )
			.appendTo( gameSet.boardDiv );

		var fl = 'abcdefgh'.split( '' ),
			fileCaption = config && config.translate && config.translate.file;
		if ( fileCaption ) {
			fl = fl.map( function ( c ) {
				return fileCaption[ c ] || '';
			} );
		}
		var rl = '12345678'.split( '' ),
			rowCaption = config && config.translate && config.translate.row;
		if ( rowCaption ) {
			rl = rl.map( function ( c ) {
				return rowCaption[ c ] || '';
			} );
		}

		for ( var side in sides ) {
			var
				s = sides[ side ],
				isFile = /n|s/.test( s );
			gameSet[ s ] = [];
			for ( var i = 0; i < 8; i++ ) {
				var sp = $( '<span>', { class: isFile ? 'pgn-file-legend' : 'pgn-row-legend' } )
					.text( isFile ? fl[ i ] : rl[ i ] )
					.appendTo( gameSet.boardDiv )
					.css( gameSet.legendLocation( s, i ) );
				gameSet[ s ][ i ] = sp;
			}
		}

		container
			.append( selector || '' )
			.append( $( '<div>' )
				.append( gameSet.boardDiv )
				.append( gameSet.tabberDiv )
				.append( controlsDiv )
			);
	}

	function doIt() {
		$( wrapperSelector ).each( function ( ind ) {
			var
				wrapperDiv = $( this ),
				initial = wrapperDiv.text(),
				pgnSource = $( 'div.pgn-sourcegame', wrapperDiv ),
				selector,
				gameSet = new Gameset( wrapperDiv );
			try {
				if ( pgnSource.length > 1 ) {
					selector = $( '<select>', { class: 'pgn-selector' } )
						.on( 'change', function () { gameSet.selectGame( this.value ); } );
				}

				buildBoardDiv( wrapperDiv, selector, gameSet, ind );
				var game;
				ind = 0;
				pgnSource.each( function () {
					try {
						game = new Game( gameSet );
						game.preAnalyzePgn( $( this ).text() );
						wrapperDiv.data( { currentGame: game } );
						ind++;
						gameSet.allGames.push( game );
						if ( selector ) {
							selector.append( $( '<option>', { value: gameSet.allGames.length - 1, text: game.description() } )
								.css( 'direction', game.descriptions.Direction || 'ltr' )
							);
						}
					} catch ( e ) {
						mw.log( 'exception in game ' + ind + ' problem is: "' + e + '"' );
						if ( game && game.descriptions ) {
							for ( var d in game.descriptions ) {
								mw.log( d + ':' + game.descriptions[ d ] );
							}
						}
					}
				} );
				gameSet.selectGame( 0 );
				gameSet.setWidth();
			} catch ( e ) {
				mw.log( e );
				mw.log( 'exception analyzing game :', initial );
				wrapperDiv.empty();
			}
		} );
	}

	if ( $( wrapperSelector, $content ).length ) {
		if ( mobile ) {
			doIt();
		} else {
			mw.loader.using( 'jquery.ui.tabs' ).done( doIt() );
		}
	}
} );

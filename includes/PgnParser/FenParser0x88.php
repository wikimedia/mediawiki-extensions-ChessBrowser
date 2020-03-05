<?php

class FenParser0x88 {
	private $fen;
	private $previousFen;
	private $cache;

	private $piecesInvolved;
	private $notation;
	private $validMoves = null;
	private $fenParts = [];
	private $longNotation;

	/**
	 * Create a new FenParser
	 *
	 * @param string|null $fen
	 */
	public function __construct( $fen = null ) {
		if ( isset( $fen ) ) {
			$this->setFen( $fen );
		}
	}

	/**
	 * Start? a new game
	 *
	 * @param string|null $fen
	 */
	public function newGame( $fen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1' ) {
		$this->validMoves = null;
		$this->setFen( $fen );
	}

	/**
	 * Set new fen position
	 * Example:
	 * $parser = new FenParser0x88();
	 * $parser->setFen('8/7P/8/8/1k15/8/P7/K7 w - - 0 1');
	 *
	 * @param string $fen
	 */
	public function setFen( $fen ) {
		$this->cache = [
			'board' => [],
			'white' => [],
			'black' => [],
			'whiteSliding' => [],
			'blackSliding' => [],
			'king' => [ 'white' => null, 'black' => null ]
		];
		if ( $this->fen ) {
			$this->previousFen = $this->fen;
		}
		$this->fen = $fen;
		$this->updateFenArray();
		$this->parseFen();
	}

	/**
	 * Get the long notation
	 *
	 * @return string
	 */
	public function getLongNotation() {
		return $this->longNotation;
	}

	/**
	 * Get the long notation for a specific move
	 *
	 * @param array $move
	 * @param bool $shortNotation
	 * @return string
	 */
	public function getLongNotationForAMove( $move, $shortNotation ) {
		if ( strstr( $shortNotation, 'O-' ) ) {
			return $shortNotation;
		}
		$fromSquare = $move['from'];
		$toSquare = $move['to'];

		$type = $this->cache['board'][Board0x88Config::mapSquareToNumber( $move['from'] )];
		$type = Board0x88Config::$typeMapping[$type];
		$separator = strstr( $shortNotation, 'x' ) >= 0 ? 'x' : '-';

		$ret = $type . $fromSquare . $separator . $toSquare;

		if ( isset( $move['promoteTo'] ) ) {
			$ret .= '=' . $move['promoteTo'];
		}
		return $ret;
	}

	/**
	 * Update fenParts from fen
	 */
	private function updateFenArray() {
		$fenParts = explode( " ", $this->fen );
		$castleCode = 0;
		for ( $i = 0, $count = strlen( $fenParts[2] ); $i < $count; $i++ ) {
			$castleCode += Board0x88Config::$castle[substr( $fenParts[2], $i, 1 )];
		}

		$this->fenParts = [
			'pieces' => $fenParts[0],
			'color' => $fenParts[1],
			'castle' => $fenParts[2],
			'castleCode' => $castleCode,
			'enPassant' => $fenParts[3],
			'halfMoves' => $fenParts[4],
			'fullMoves' => $fenParts[5]
		];
	}

	/**
	 * Parse the stored fenParts
	 */
	private function parseFen() {
		$pos = 0;
		$this->cache['board'] = Board0x88Config::getDefaultBoard();
		$squares = Board0x88Config::$fenSquares;
		for ( $i = 0, $len = strlen( $this->fenParts['pieces'] ); $i < $len; $i++ ) {
			$token = $this->fenParts['pieces'][$i];

			if ( isset( Board0x88Config::$fenPieces[$token] ) ) {
				$index = Board0x88Config::mapSquareToNumber( $squares[$pos] );
				$type = Board0x88Config::$pieces[$token];
				$piece = [
					't' => $type,
					's' => $index
				];
				// Board array
				$this->cache['board'][$index] = $type;

				// White and black array
				// Black tokens are already lowercase
				$color = ( $token === strtolower( $token ) ? 'black' : 'white' );
				$this->cache[$color][] = $piece;

				// King array
				if ( Board0x88Config::$typeMapping[$type] == 'king' ) {
					$this->cache['king' . ( $piece['t'] & 0x8 ? 'black' : 'white' )] = $piece;
				}
				$pos++;
			} elseif ( $i < $len - 1 && isset( Board0x88Config::$numbers[$token] ) ) {
				$pos += intval( $token );
			}
		}
	}

	/**
	 * Returns the piece on a given square
	 *
	 * Example:
	 * $fenBishopOnB3CheckingKingOnG7 = '6k1/6pp/8/8/8/1B6/8/6K1 b - - 0 1';
	 * $parser = new FenParser0x88($fenBishopOnB3CheckingKingOnG7);
	 * $bishop = $parser->getPieceOnSquare(Board0x88Config::mapSquareToNumber( 'b3' ));
	 * var_dump($bishop).
	 *
	 * Returns an array
	 * {
	 *   "square" : "b3",
	 *   "s" : 33,
	 *   "t" : 5,
	 *   "type" : "bishop",
	 *   "color": "white",
	 *   "sliding" : 4
	 * }
	 *
	 * sliding is greater than 0 for bishop, rook and queen.
	 *
	 * @param int $square
	 * @return array|null
	 */
	public function getPieceOnSquare( $square ) {
		$piece = $this->cache['board'][$square];
		if ( isset( $piece ) ) {
			return [
				'square' => Board0x88Config::mapNumberToSquare( $square ),
				's' => $square,
				't' => $piece,
				'type' => Board0x88Config::$typeMapping[$piece],
				'color' => $piece & 0x8 ? 'black' : 'white',
				'sliding' => $piece & 0x4
			];
		}
		return null;
	}

	/**
	 * Check if a move is valid
	 *
	 * @param array $move
	 * @param string $fen
	 * @return bool
	 */
	public function isValid( $move, $fen ) {
		$this->setFen( $fen );
		if ( !isset( $move['from'] ) ) {
			$fromAndTo = $this->getFromAndToByNotation( $move[ChessJson::MOVE_NOTATION] );
			$move['from'] = $fromAndTo['from'];
			$move['to'] = $fromAndTo['to'];

		}
		$from = Board0x88Config::mapSquareToNumber( $move['from'] );
		$to = Board0x88Config::mapSquareToNumber( $move['to'] );

		$obj = $this->getValidMovesAndResult();
		$moves = $obj['moves'];
		if ( isset( $moves[$from] ) && in_array( $to, $moves[$from] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Returns king square in numeric format.
	 *
	 * Example:
	 * $fenBishopOnB3CheckingKingOnG7 = '6k1/6pp/8/8/8/1B6/8/6K1 b - - 0 1';
	 * $parser = new FenParser0x88($fenBishopOnB3CheckingKingOnG7);
	 * $king = $parser->getKing("black");
	 *
	 * returns array("t" : 11, "s" : 128).
	 *
	 * where "t" is type, and "s" is square. Square can be converted to board coordinates using
	 * Board0x88Config::mapNumberToSquare( $array["s"] )
	 *
	 * @param string $color
	 * @return array
	 */
	public function getKing( $color ) {
		return $this->cache['king' . $color];
	}

	/**
	 * Returns en passant square or null
	 *
	 * @return string|null
	 */
	public function getEnPassantSquare() {
		return ( $this->fenParts['enPassant'] != '-' ) ? $this->fenParts['enPassant'] : null;
	}

	/**
	 * Return boolean true if king side castling is possible for a color
	 *
	 * Example:
	 * $fen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
	 * $parser = new FenParser0x88($fen);
	 * $whiteCanCastle = $parser->canCastleKingSide("white");
	 * $blackCanCastle = $parser->canCastleKingSide("black");
	 *
	 * @param string $color
	 * @return bool
	 */
	public function canCastleKingSide( $color ) {
		$code = $color === 'white' ? Board0x88Config::$castle['K'] : Board0x88Config::$castle['k'];
		return ( $this->fenParts['castleCode'] & $code ) ? true : false;
	}

	/**
	 * Return color to move, "white" or "black"
	 *
	 * @return string
	 * @throws FenParser0x88Exception
	 */
	public function getColor() {
		$color = $this->fenParts['color'];
		switch ( $color ) {
			case 'w':
				return 'white';
			case 'b':
				return 'black';
			default:
				throw new FenParser0x88Exception( "Unknown color: $color" );
		}
	}

	/**
	 * Switch the active color (which player's turn it is)
	 */
	public function switchColor() {
		$this->fenParts['color'] = $this->fenParts['color'] == 'w' ? 'b' : 'w';
	}

	/**
	 * Returns whether queen side castle for given color is possible (based on
	 * fen only, i.e. no checks or obstructions are checked).
	 *
	 * @param string $color
	 * @return bool
	 */
	public function canCastleQueenSide( $color ) {
		$code = $color === 'white' ? Board0x88Config::$castle['Q'] : Board0x88Config::$castle['q'];
		return ( $this->fenParts['castleCode'] & $code ) ? true : false;
	}

	/**
	 * Returns whether two squares are on the same rank.
	 *
	 * @param int $square1
	 * @param int $square2
	 * @return bool
	 */
	public function isOnSameRank( $square1, $square2 ) {
		return ( $square1 & 240 ) === ( $square2 & 240 );
	}

	/**
	 * Returns whether two squares are on the same file
	 *
	 * @param int $square1
	 * @param int $square2
	 * @return bool
	 */
	public function isOnSameFile( $square1, $square2 ) {
		return ( $square1 & 15 ) === ( $square2 & 15 );
	}

	/**
	 * Returns array of valid moves for given color in real board coordinates.
	 *
	 * Example:
	 * $parser = new FenParser0x88('6k1/6p1/4n3/8/8/8/B7/6K1 b - - 0 1');
	 * $validBlackMoves = $parser->getValidMovesBoardCoordinates("black");
	 *
	 * returns {"g8":["f7","h7","f8","h8"],"g7":["g6","g5"],"e6":[]}
	 *
	 * where the array key(example "g8") is from square and ["f7","h7","f8","h8"] are valid square for
	 * the piece on "g8"
	 *
	 * @param string|null $color
	 * @return array
	 */
	public function getValidMovesBoardCoordinates( $color = null ) {
		$movesAndResult = $this->getValidMovesAndResult( $color );
		$moves = $movesAndResult["moves"];

		$ret = [];
		foreach ( $moves as $from => $toSquares ) {
			$fromSquare = Board0x88Config::mapNumberToSquare( $from );

			$squares = [];
			foreach ( $toSquares as $square ) {
				$squares[] = Board0x88Config::mapNumberToSquare( $square );
			}

			$ret[$fromSquare] = $squares;
		}

		return $ret;
	}

	/**
	 * Returns valid moves in 0x88 numeric format and result
	 *
	 * TODO document $color doesn't have to be null
	 *
	 * @param null $color
	 * @return array|null
	 */
	public function getValidMovesAndResult( $color = null ) {
		if ( !$color ) {
			$color = $this->getColor();
		}

		$ret = [];
		$enPassantSquare = $this->getEnPassantSquare();
		if ( $enPassantSquare ) {
			$enPassantSquare = Board0x88Config::mapSquareToNumber( $enPassantSquare );
		}

		$kingSideCastle = $this->canCastleKingSide( $color );
		$queenSideCastle = $this->canCastleQueenSide( $color );
		$oppositeColor = $color === 'white' ? 'black' : 'white';

		$WHITE = $color === 'white' ? true : false;

		$protectiveMoves = $this->getCaptureAndProtectiveMoves( $oppositeColor );

		$checks = $this->getCountChecks( $color, $protectiveMoves );
		$validSquares = null;
		$pinned = [];
		if ( $checks === 2 ) {
			$pieces = [ $this->getKing( $color ) ];
		} else {
			$pieces = $this->cache[$color];
			$pinned = $this->getPinned( $color );
			if ( $checks === 1 ) {
				$validSquares = $this->getValidSquaresOnCheck( $color );
			}
		}

		$totalCountMoves = 0;
		for ( $i = 0, $count = count( $pieces ); $i < $count; $i++ ) {
			$piece = $pieces[$i];
			$paths = [];

			switch ( $piece['t'] ) {
				// pawns
				case 0x01:
					if (
						!isset( $pinned[$piece['s']] )
						|| (
							$pinned[$piece['s']]
							&& $this->isOnSameFile( $piece['s'], $pinned[$piece['s']]['by'] )
						)
					) {
						if ( !$this->cache['board'][$piece['s'] + 16] ) {
							$paths[] = $piece['s'] + 16;
							if ( $piece['s'] < 32 ) {
								if ( !$this->cache['board'][$piece['s'] + 32] ) {
									$paths[] = $piece['s'] + 32;
								}
							}
						}
					}
					if (
						!isset( $pinned[$piece['s']] )
						|| (
							$pinned[$piece['s']]
							&& $pinned[$piece['s']]['by'] === $piece['s'] + 15
						)
					) {
						if ( $enPassantSquare == $piece['s'] + 15 || $this->cache['board'][$piece['s'] + 15] & 0x8 ) {
							$paths[] = $piece['s'] + 15;
						}
					}
					if (
						isset( $this->cache['board'][$piece['s'] + 17] )
						&& (
							!isset( $pinned[$piece['s']] )
							|| ( $pinned[$piece['s']] && $pinned[$piece['s']]['by'] === $piece['s'] + 17 )
						)
					) {
						if (
							$enPassantSquare == $piece['s'] + 17
							|| ( $this->cache['board'][$piece['s'] + 17] )
							&& $this->cache['board'][$piece['s'] + 17] & 0x8
						) {
							$paths[] = $piece['s'] + 17;
						}
					}
					break;
				case 0x09:
					if (
						!isset( $pinned[$piece['s']] )
						|| (
							$pinned[$piece['s']]
							&& $this->isOnSameFile( $piece['s'], $pinned[$piece['s']]['by'] )
						)
					) {
						if ( !$this->cache['board'][$piece['s'] - 16] ) {
							$paths[] = $piece['s'] - 16;
							if ( $piece['s'] > 87 ) {
								if ( !$this->cache['board'][$piece['s'] - 32] ) {
									$paths[] = $piece['s'] - 32;
								}
							}
						}
					}
					if (
						!isset( $pinned[$piece['s']] )
						|| ( $pinned[$piece['s']]
						&& $pinned[$piece['s']]['by'] === $piece['s'] - 15 )
					) {
						if (
							$enPassantSquare == $piece['s'] - 15
							|| ( $this->cache['board'][$piece['s'] - 15] )
							&& !( $this->cache['board'][$piece['s'] - 15] & 0x8 )
						) {
							$paths[] = $piece['s'] - 15;
						}
					}
					if ( $piece['s'] - 17 >= 0 ) {
						if (
							!isset( $pinned[$piece['s']] )
							|| ( $pinned[$piece['s']] && $pinned[$piece['s']]['by'] === $piece['s'] - 17 )
						) {
							if (
								$enPassantSquare == $piece['s'] - 17
								|| ( $this->cache['board'][$piece['s'] - 17] )
								&& !( $this->cache['board'][$piece['s'] - 17] & 0x8 )
							) {
								$paths[] = $piece['s'] - 17;
							}
						}
					}

					break;
				// Sliding pieces
				case 0x05:
				case 0x07:
				case 0x06:
				case 0x0D:
				case 0x0E:
				case 0x0F:
					$directions = Board0x88Config::$movePatterns[$piece['t']];
					if ( isset( $pinned[$piece['s']] ) ) {
						if ( array_search( $pinned[$piece['s']]['direction'], $directions ) !== false ) {
							$directions = [ $pinned[$piece['s']]['direction'], $pinned[$piece['s']]['direction'] * -1 ];
						} else {
							$directions = [];
						}
					}
					for ( $a = 0, $len = count( $directions ); $a < $len; $a++ ) {
						$square = $piece['s'] + $directions[$a];
						while ( ( $square & 0x88 ) === 0 ) {
							if ( $this->cache['board'][$square] ) {
								if ( (
									$WHITE && $this->cache['board'][$square] & 0x8 )
									|| ( !$WHITE && !( $this->cache['board'][$square] & 0x8 )
								) ) {
									$paths[] = $square;
								}
								break;
							}
							$paths[] = $square;
							$square += $directions[$a];
						}
					}
					break;
				// Knight
				case 0x02:
				case 0x0A:
					if ( isset( $pinned[$piece['s']] ) ) {
						break;
					}
					$directions = Board0x88Config::$movePatterns[$piece['t']];
					for ( $a = 0, $lenD = count( $directions ); $a < $lenD; $a++ ) {
						$square = $piece['s'] + $directions[$a];

						if ( ( $square & 0x88 ) === 0 ) {
							if ( $this->cache['board'][$square] ) {
								if ( (
									$WHITE && $this->cache['board'][$square] & 0x8 )
									|| ( !$WHITE && !( $this->cache['board'][$square] & 0x8 )
								) ) {
									$paths[] = $square;
								}
							} else {
								$paths[] = $square;
							}
						}
					}
					break;
				// White king
				// Black king
				case 0X03:
				case 0X0B:
					$directions = Board0x88Config::$movePatterns[$piece['t']];
					for ( $a = 0, $lenD = count( $directions ); $a < $lenD; $a++ ) {
						$square = $piece['s'] + $directions[$a];
						if ( ( $square & 0x88 ) === 0 ) {
							if ( !strstr( $protectiveMoves, Board0x88Config::$keySquares[$square] ) ) {
								# if ($protectiveMoves.indexOf(Board0x88Config::$keySquares[$square]) == -1) {
								if ( $this->cache['board'][$square] ) {
									if ( (
										$WHITE && $this->cache['board'][$square] & 0x8 )
										|| ( !$WHITE && !( $this->cache['board'][$square] & 0x8 )
									) ) {
										$paths[] = $square;
									}
								} else {
									$paths[] = $square;
								}
							}
						}
					}

					if ( $kingSideCastle
						&& !( $this->cache['board'][$piece['s'] + 1] )
						&& !( $this->cache['board'][$piece['s'] + 2] )
						&& ( $this->cache['board'][$piece['s'] + 3] )
						&& !strstr( $protectiveMoves, Board0x88Config::$keySquares[$piece['s']] )
						&& (
							$piece['s'] < 118
							&& !strstr( $protectiveMoves, Board0x88Config::$keySquares[$piece['s'] + 1] )
						)
						&& (
							$piece['s'] < 117
							&& !strstr( $protectiveMoves, Board0x88Config::$keySquares[$piece['s'] + 2] )
						)
					) {
						$paths[] = $piece['s'] + 2;
					}

					if ( $queenSideCastle && $piece['s'] - 2 != -1
						&& !( $this->cache['board'][$piece['s'] - 1] )
						&& !( $this->cache['board'][$piece['s'] - 2] )
						&& !( $this->cache['board'][$piece['s'] - 3] )
						&& ( $this->cache['board'][$piece['s'] - 4] )
						&& !strstr(
							$protectiveMoves,
							Board0x88Config::$keySquares[$piece['s']]
						)
						&& !strstr(
							$protectiveMoves,
							Board0x88Config::$keySquares[$piece['s'] - 1]
						)
						&& !strstr(
							$protectiveMoves,
							Board0x88Config::$keySquares[$piece['s'] - 2]
						)
					) {
						$paths[] = $piece['s'] - 2;
					}
					break;
			}
			if ( $validSquares && $piece['t'] != 0x03 && $piece['t'] != 0x0B ) {
				$paths = $this->excludeInvalidSquares( $paths, $validSquares );
			}
			$ret[$piece['s']] = $paths;
			$totalCountMoves += count( $paths );
		}
		$result = 0;
		if ( $checks && !$totalCountMoves ) {
			$result = $color === 'black' ? 1 : -1;
		} elseif ( !$checks && !$totalCountMoves ) {
			$result = 0.5;
		}
		$this->validMoves = [ 'moves' => $ret, 'result' => $result, 'check' => $checks ];
		return $this->validMoves;
	}

	/**
	 * Get the valid moves
	 *
	 * @return array
	 */
	private function validMoves() {
		$validMovesAndResult = $this->getValidMovesAndResult();
		return $validMovesAndResult["moves"];
	}

	/**
	 * From a list of squares and valid squares, return the valid ones
	 *
	 * TODO document how is this different from using $validSquares?
	 *
	 * @param array $squares
	 * @param array $validSquares
	 * @return array
	 */
	private function excludeInvalidSquares( $squares, $validSquares ) {
		$ret = [];
		for ( $i = 0, $len = count( $squares ); $i < $len; $i++ ) {
			if ( in_array( $squares[$i], $validSquares ) ) {
				$ret[] = $squares[$i];
			}
		}
		return $ret;
	}

	/**
	 * Returns comma-separated string of moves (since it's faster to work with than arrays).
	 *
	 * @param string $color
	 * @return string
	 */
	public function getCaptureAndProtectiveMoves( $color ) {
		$ret = [];

		$pieces = $this->cache[$color];

		$oppositeKing = $this->getKing( $color === 'white' ? 'black' : 'white' );
		$oppositeKingSquare = $oppositeKing['s'];
		for ( $i = 0, $len = count( $pieces ); $i < $len; $i++ ) {
			$piece = $pieces[$i];
			switch ( $piece['t'] ) {
				// pawns
				case 0x01:
					if ( ( ( $piece['s'] + 15 ) & 0x88 ) === 0 ) {
						$ret[] = $piece['s'] + 15;
					}
					if ( ( ( $piece['s'] + 17 ) & 0x88 ) === 0 ) {
						$ret[] = $piece['s'] + 17;
					}
					break;
				case 0x09:
					if ( ( ( $piece['s'] - 15 ) & 0x88 ) === 0 ) {
						$ret[] = $piece['s'] - 15;
					}
					if ( ( ( $piece['s'] - 17 ) & 0x88 ) === 0 ) {
						$ret[] = $piece['s'] - 17;
					}
					break;
				// Sliding pieces
				case 0x05:
				case 0x07:
				case 0x06:
				case 0x0D:
				case 0x0E:
				case 0x0F:
					$directions = Board0x88Config::$movePatterns[$piece['t']];
					for ( $a = 0, $lenA = count( $directions ); $a < $lenA; $a++ ) {
						$square = $piece['s'] + $directions[$a];
						while ( ( $square & 0x88 ) === 0 ) {
							if ( $this->cache['board'][$square] && $square !== $oppositeKingSquare ) {
								$ret[] = $square;
								break;
							}
							$ret[] = $square;
							$square += $directions[$a];
						}
					}
					break;
				// knight
				case 0x02:
				case 0x0A:
					// White knight
					$directions = Board0x88Config::$movePatterns[$piece['t']];
					for ( $a = 0, $lenA = count( $directions ); $a < $lenA; $a++ ) {
						$square = $piece['s'] + $directions[$a];

						if ( ( $square & 0x88 ) === 0 ) {
							$ret[] = $square;
						}
					}
					break;
				// king
				case 0X03:
				case 0X0B:
					$directions = Board0x88Config::$movePatterns[$piece['t']];
					for ( $a = 0, $lenD = count( $directions ); $a < $lenD; $a++ ) {
						$square = $piece['s'] + $directions[$a];
						if ( ( $square & 0x88 ) === 0 ) {
							$ret[] = $square;
						}
					}
					break;
			}

		}
		return ',' . implode( ",", $ret ) . ',';
	}

	/**
	 * Get a list of sliding pieces attacking the king of a color
	 *
	 * @param string $color
	 * @return array
	 */
	public function getSlidingPiecesAttackingKing( $color ) {
		$ret = [];
		$king = $this->cache['king' . ( $color === 'white' ? 'black' : 'white' )];
		$pieces = $this->cache[$color];
		for ( $i = 0, $len = count( $pieces ); $i < $len; $i++ ) {
			$piece = $pieces[$i];
			if ( $piece['t'] & 0x4 ) {
				$numericDistance = $king['s'] - $piece['s'];
				$boardDistance = $numericDistance / $this->getDistance( $king['s'], $piece['s'] );

				switch ( $piece['t'] ) {
					// Bishop
					case 0x05:
					case 0x0D:
						if ( $numericDistance % 15 === 0 || $numericDistance % 17 === 0 ) {
							$ret[] = ( [ 's' => $piece['s'], 'p' => $boardDistance ] );
						}
						break;
					// Rook
					case 0x06:
					case 0x0E:
						if ( $numericDistance % 16 === 0 ) {
							$ret[] = [ 's' => $piece['s'], 'p' => $boardDistance ];
						} elseif ( ( $piece['s'] & 240 ) == ( $king['s'] & 240 ) ) {
							$ret[] = [ 's' => $piece['s'], 'p' => $numericDistance > 0 ? 1 : -1 ];
						}
						break;
					// Queen
					case 0x07:
					case 0x0F:
						if (
							$numericDistance % 15 === 0
							|| $numericDistance % 16 === 0
							|| $numericDistance % 17 === 0
						) {
							$ret[] = [ 's' => $piece['s'], 'p' => $boardDistance ];
						} elseif ( ( $piece['s'] & 240 ) == ( $king['s'] & 240 ) ) {
							$ret[] = ( [ 's' => $piece['s'], 'p' => $numericDistance > 0 ? 1 : -1 ] );
						}
						break;
				}
			}
		}
		return $ret;
	}

	/**
	 * Returns array of pinned pieces in standard chess coordinate system.
	 *
	 * Example:
	 * // pawn on g2 pinned by rook on a2
	 * $parser = new FenParser0x88('5k2/8/8/8/8/8/r5PK/8 w - - 0 1');
	 * // find pinned white pieces
	 * $pinned = $parser->getPinnedBoardCoordinates('white');
	 * var_dump($pinned);
	 *
	 * returns
	 * array(1) {
	 * [0]=>
	 * array(3) {
	 * ["square"]=>
	 * string(2) "g2"
	 * ["pinnedBy"]=>
	 * string(2) "a2"
	 * ["direction"]=>
	 * int(1)
	 * }
	 * }
	 *
	 * @param string $color
	 * @return array
	 */
	public function getPinnedBoardCoordinates( $color ) {
		$pinned = $this->getPinned( $color );

		$ret = [];
		foreach ( $pinned as $square => $by ) {
			$ret[] = [
				"square" => Board0x88Config::mapNumberToSquare( $square ),
				"pinnedBy" => Board0x88Config::mapNumberToSquare( $by["by"] ),
				"direction" => $by["direction"]
			];
		}

		return $ret;
	}

	/**
	 * Return numeric squares(0x88) of pinned pieces
	 *
	 * @param string $color
	 * @return array|null
	 */
	public function getPinned( $color ) {
		$ret = [];
		$WHITE = $color === 'white';
		$pieces = $this->getSlidingPiecesAttackingKing( $WHITE ? 'black' : 'white' );
		$king = $this->cache['king' . $color];
		$i = 0;
		$countPieces = count( $pieces );
		while ( $i < $countPieces ) {
			$piece = $pieces[$i];
			$square = $piece['s'] + $piece['p'];
			$countOpposite = 0;

			$squares = [ $piece['s'] ];
			$pinning = '';
			while ( $square !== $king['s'] && $countOpposite < 2 ) {
				$squares[] = $square;
				if ( $this->cache['board'][$square] ) {
					$countOpposite++;
					// TODO XOR?
					if (
						( !$WHITE && $this->cache['board'][$square] & 0x8 )
						|| (
							$WHITE
							&& !( $this->cache['board'][$square] & 0x8 )
						)
					) {
						$pinning = $square;
					} else {
						break;
					}
				}
				$square += $piece['p'];
			}
			if ( $countOpposite === 1 ) {
				$ret[$pinning] = [ 'by' => $piece['s'], 'direction' => $piece['p'] ];
			}
			$i++;
		}
		if ( count( $ret ) === 0 ) {
			return null;
		}
		return $ret;
	}

	/**
	 * Get valid squares for other pieces than king to move to when in check
	 *
	 * i.e. squares which avoids the check.
	 *
	 * Example: if white king on g1 is checked by rook on g8, then valid squares for other pieces
	 * are the squares g2,g3,g4,g5,g6,g7,g8.
	 * Squares are returned in numeric format
	 *
	 * @param string $color
	 * @return array|null
	 */
	public function getValidSquaresOnCheck( $color ) {
		$king = $this->cache['king' . $color];
		$pieces = $this->cache[$color === 'white' ? 'black' : 'white'];

		$enPassantSquare = $this->getEnPassantSquare();
		if ( $enPassantSquare ) {
			$enPassantSquare = Board0x88Config::mapSquareToNumber( $enPassantSquare );
		}

		for ( $i = 0, $len = count( $pieces ); $i < $len; $i++ ) {
			$piece = $pieces[$i];

			switch ( $piece['t'] ) {
				case 0x01:
					if ( $king['s'] === $piece['s'] + 15 || $king['s'] === $piece['s'] + 17 ) {
						if ( $enPassantSquare === $piece['s'] - 16 ) {
							return [ $piece['s'], $enPassantSquare ];
						}
						return [ $piece['s'] ];
					}
					break;
				case 0x09:
					if ( $king['s'] === $piece['s'] - 15 || $king['s'] === $piece['s'] - 17 ) {
						if ( $enPassantSquare === $piece['s'] + 16 ) {
							return [ $piece['s'], $enPassantSquare ];
						}
						return [ $piece['s'] ];
					}
					break;
				// knight
				case 0x02:
				case 0x0A:
					if ( $this->getDistance( $piece['s'], $king['s'] ) === 2 ) {
						$directions = Board0x88Config::$movePatterns[$piece['t']];
						for ( $a = 0, $lenD = count( $directions ); $a < $lenD; $a++ ) {
							$square = $piece['s'] + $directions[$a];
							if ( $square === $king['s'] ) {
								return [ $piece['s'] ];
							}
						}
					}
					break;
				// Bishop
				case 0x05:
				case 0x0D:
					$checks = $this->getBishopCheckPath( $piece, $king );
					if ( isset( $checks ) && is_array( $checks ) ) {
						return $checks;
					}
					break;
				// Rook
				case 0x06:
				case 0x0E:
					$checks = $this->getRookCheckPath( $piece, $king );
					if ( isset( $checks ) && is_array( $checks ) ) {
						return $checks;
					}
					break;
				case 0x07:
				case 0x0F:
					$checks = $this->getRookCheckPath( $piece, $king );
					if ( isset( $checks ) && is_array( $checks ) ) {
						return $checks;
					}
					$checks = $this->getBishopCheckPath( $piece, $king );
					if ( isset( $checks ) && is_array( $checks ) ) {
						return $checks;
					}
					break;
			}
		}

		return null;
	}

	/**
	 * Get the path by which a bishop is checking a king
	 *
	 * @param array $piece
	 * @param array $king
	 * @return array|null
	 */
	public function getBishopCheckPath( $piece, $king ) {
		if ( ( $king['s'] - $piece['s'] ) % 15 === 0 || ( $king['s'] - $piece['s'] ) % 17 === 0 ) {
			$direction = ( $king['s'] - $piece['s'] ) / $this->getDistance( $piece['s'], $king['s'] );
			$square = $piece['s'] + $direction;
			$pieceFound = false;
			$squares = [ $piece['s'] ];
			while ( $square !== $king['s'] && !$pieceFound ) {
				$squares[] = $square;
				if ( isset( $this->cache['board'][$square] ) && $this->cache['board'][$square] ) {
					$pieceFound = true;
				}
				$square += $direction;
			}
			if ( !$pieceFound ) {
				return $squares;
			}
		}
		return null;
	}

	/**
	 * Get the path by which a rook is chekcing a king
	 *
	 * @param array $piece
	 * @param array $king
	 * @return array|null
	 */
	public function getRookCheckPath( $piece, $king ) {
		$direction = null;
		if ( $this->isOnSameFile( $piece['s'], $king['s'] ) ) {
			$direction = ( $king['s'] - $piece['s'] ) / $this->getDistance( $piece['s'], $king['s'] );
		} elseif ( $this->isOnSameRank( $piece['s'], $king['s'] ) ) {
			$direction = $king['s'] > $piece['s'] ? 1 : -1;
		}

		if ( $direction ) {
			$square = $piece['s'] + $direction;
			$pieceFound = false;
			$squares = [ $piece['s'] ];
			while ( $square !== $king['s'] && !$pieceFound ) {
				$squares[] = $square;
				if ( $this->cache['board'][$square] ) {
					$pieceFound = true;
				}
				$square += $direction;
			}
			if ( !$pieceFound ) {
				return $squares;
			}
		}
		return null;
	}

	/**
	 * Get a count of the checks
	 *
	 * TODO document what that means, and what the params can be
	 *
	 * @param mixed $kingColor
	 * @param mixed $moves
	 * @return int
	 */
	public function getCountChecks( $kingColor, $moves ) {
		$king = $this->cache['king' . $kingColor];
		$index = strpos( $moves, Board0x88Config::$keySquares[$king['s']] );
		if ( $index > 0 ) {
			if ( strpos( $moves, Board0x88Config::$keySquares[$king['s']], $index + 1 ) > 0 ) {
				return 2;
			}
			return 1;
		}
		return 0;
	}

	/**
	 * Get the distance between 2 squares
	 *
	 * @param int $sq1
	 * @param int $sq2
	 * @return int
	 */
	public function getDistance( $sq1, $sq2 ) {
		return Board0x88Config::$distances[$sq2 - $sq1 + ( $sq2 | 7 ) - ( $sq1 | 7 ) + 240];
	}

	/**
	 * Get the pieces involved in a move
	 *
	 * @param array $move
	 * @return array
	 */
	public function getPiecesInvolvedInMove( $move ) {
		$ret = [
			[ 'from' => $move['from'], 'to' => $move['to'] ]
		];
		$move = [
			'from' => Board0x88Config::mapSquareToNumber( $move['from'] ),
			'to' => Board0x88Config::mapSquareToNumber( $move['to'] ),
			'promoteTo' => isset( $move['promoteTo'] ) ? $move['promoteTo'] : null
		];

		$color = ( $this->cache['board'][$move['from']] & 0x8 ) ? 'black' : 'white';

		if ( $this->isEnPassantMove( $move ) ) {
			if ( $color == 'black' ) {
				$square = $move['to'] + 16;

			} else {
				$square = $move['to'] - 16;
			}
			$ret[] = [ 'capture' => Board0x88Config::mapNumberToSquare( $square ) ];
		}

		if ( $this->isCastleMove( $move ) ) {
			if ( ( $move['from'] & 15 ) < ( $move['to'] & 15 ) ) {
				$ret[] = ( [
					'from' => 'h' . ( $color == 'white' ? 1 : 8 ),
					'to' => 'f' . ( $color == 'white' ? 1 : 8 )
				] );
			} else {
				$ret[] = ( [
					'from' => 'a' . ( $color == 'white' ? 1 : 8 ),
					'to' => 'd' . ( $color == 'white' ? 1 : 8 )
				] );
			}
		}

		if ( $move['promoteTo'] ) {
			$ret[] = ( [
				'promoteTo' => $move['promoteTo'],
				'square' => Board0x88Config::mapNumberToSquare( $move['to'] )
			] );
		}
		return $ret;
	}

	/**
	 * Check if a move is en passant
	 *
	 * TODO combine ifs
	 *
	 * @param array $move
	 * @return bool
	 */
	public function isEnPassantMove( $move ) {
		if (
			( $this->cache['board'][$move['from']] === 0x01
			|| $this->cache['board'][$move['from']] == 0x09 )
		) {
			if (
				!$this->cache['board'][$move['to']] &&
				(
					( $move['from'] - $move['to'] ) % 17 === 0
					|| ( $move['from'] - $move['to'] ) % 15 === 0
				)
			) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if a move is castling
	 *
	 * TODO combine ifs
	 *
	 * @param array $move
	 * @return bool
	 */
	public function isCastleMove( $move ) {
		if (
			( $this->cache['board'][$move['from']] === 0x03
			|| $this->cache['board'][$move['from']] == 0x0B )
		) {
			if ( $this->getDistance( $move['from'], $move['to'] ) === 2 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Make a new move from an array
	 *
	 * @param array $move
	 */
	public function makeMove( $move ) {
		$this->updateBoardData( $move );
		$this->fen = null;
	}

	/**
	 * Convert string notation to array
	 *
	 * TODO make static
	 *
	 * @param string $notation
	 * @return array
	 */
	private function getFromAndToByLongNotation( $notation ) {
		$notation = preg_replace( '/[^a-h0-8]/si', '', $notation );
		return [
			'from' => substr( $notation, 0, 2 ),
			'to' => substr( $notation, 2, 2 )
		];
	}

	/**
	 * Get extended move information
	 *
	 * TODO this is the same as getParsed
	 *
	 * @param string|array $move
	 * @return array
	 */
	public function getExtendedMoveInfo( $move ) {
		$move = $this->getParsed( $move );
		return $move;
	}

	/**
	 * Get the parsed form of a move
	 *
	 * @param string|array $move
	 * @return array
	 */
	public function getParsed( $move ) {
		if ( is_string( $move ) ) {
			$move = [ 'm' => $move ];
		}

		$move["m"] = preg_replace( "/([a-h])([a-h])([0-8])/s", "$1x$2$3", $move["m"] );

		if ( isset( $move['m'] ) ) {
			if ( $move['m'] == '--' ) {
				$this->fen = null;
				$this->switchColor();
				return [
					'm' => $move['m'],
					'fen' => $this->getFen()
				];
			}
			if ( is_string( $move['m'] ) && preg_match( '/^[a-h][0-8][a-h][0-8]$/', $move['m'] ) ) {
				$fromAndTo = $this->getFromAndToByLongNotation( $move['m'] );
			} else {
				$fromAndTo = $this->getFromAndToByNotation( $move['m'] );

			}
		} else {
			$fromAndTo = $move;
		}
		$this->makeMove( $fromAndTo );
		$newProperties = [
			'from' => $fromAndTo['from'],
			'to' => $fromAndTo['to'],
			'fen' => $this->getFen()
		];
		return array_merge( $move, $newProperties );
	}

	/**
	 * Get from and to by notation
	 *
	 * TODO document
	 *
	 * @param string $notation
	 * @return array
	 */
	public function getFromAndToByNotation( $notation ) {
		$notation = str_replace( ".", "", $notation );

		$ret = [ 'promoteTo' => $this->getPromoteByNotation( $notation ) ];
		$color = $this->getColor();

		$offset = 0;
		if ( $color === 'black' ) {
			$offset = 112;
		}

		$foundPieces = [];
		$fromRank = $this->getFromRankByNotation( $notation );
		$fromFile = $this->getFromFileByNotation( $notation );

		if ( strlen( $notation ) === 2 ) {
			$square = Board0x88Config::mapSquareToNumber( $notation );
			$ret['to'] = Board0x88Config::mapSquareToNumber( $notation );
			$direction = $color === 'white' ? -16 : 16;
			if ( $this->cache['board'][$square + $direction] ) {
				$foundPieces[] = $square + $direction;
			} else {
				$foundPieces[] = $square + ( $direction * 2 );
			}

		} else {
			$notation = preg_replace( "/=[QRBN]/", "", $notation );
			$notation = preg_replace( "/[\+#!\?]/s", "", $notation );
			$notation = preg_replace( "/^(.*?)[QRBN]$/s", "$1", $notation );
			$pieceType = $this->getPieceTypeByNotation( $notation, $color );

			$capture = strpos( $notation, "x" ) > 0;

			$ret['to'] = $this->getToSquareByNotation( $notation );
			switch ( $pieceType ) {
				case 0x01:
				case 0x09:
					if ( $color === 'black' ) {
						$offsets = $capture ? [ 15, 17 ] : [ 16 ];
						if ( $ret['to'] >= 64 ) {
							$offsets[] = 32;
						}
					} else {
						$offsets = $capture ? [ -15, -17 ] : [ -16 ];
						if ( $ret['to'] < 64 ) {
							$offsets[] = -32;
						}
					}

					for ( $i = 0, $lenO = count( $offsets ); $i < $lenO; $i++ ) {
						$sq = $ret['to'] + $offsets[$i];
						if ( $this->cache['board'][$sq] && $this->cache['board'][$sq] === $pieceType ) {
							$foundPieces[] = ( $sq );
						}
					}
					break;
				case 0x03:
				case 0x0B:

					if ( $notation === 'O-O' ) {
						$foundPieces[] = ( $offset + 4 );
						$ret['to'] = $offset + 6;
					} elseif ( $notation === 'O-O-O' ) {
						$foundPieces[] = ( $offset + 4 );
						$ret['to'] = $offset + 2;
					} else {
						$k = $this->getKing( $color );
						$foundPieces[] = $k['s'];
					}
					break;
				case 0x02:
				case 0x0A:
					$pattern = Board0x88Config::$movePatterns[$pieceType];
					for ( $i = 0, $len = count( $pattern ); $i < $len; $i++ ) {
						$sq = $ret['to'] + $pattern[$i];
						if ( !( $sq & 0x88 ) ) {
							if ( $this->cache['board'][$sq] && $this->cache['board'][$sq] === $pieceType ) {
								$foundPieces[] = ( $sq );
							}
						}
					}
					break;
				// Sliding pieces
				default:
					$patterns = Board0x88Config::$movePatterns[$pieceType];
					for ( $i = 0, $len = count( $patterns ); $i < $len; $i++ ) {
						$sq = $ret['to'] + $patterns[$i];
						while ( !( $sq & 0x88 ) ) {
							if ( $this->cache['board'][$sq] && $this->cache['board'][$sq] === $pieceType ) {
								$foundPieces[] = ( $sq );
							}
							if ( $this->cache['board'][$sq] ) {
								break;
							}
							$sq += $patterns[$i];
						}
					}
					break;
			}
		}

		if ( count( $foundPieces ) === 1 ) {
			$ret['from'] = $foundPieces[0];
		} else {
			if ( $fromRank !== null && $fromRank >= 0 ) {
				for ( $i = 0, $len = count( $foundPieces ); $i < $len; $i++ ) {
					if ( $this->isOnSameRank( $foundPieces[$i], $fromRank ) ) {
						$ret['from'] = $foundPieces[$i];
						break;
					}
				}
			} elseif ( $fromFile !== null && $fromFile >= 0 ) {
				for ( $i = 0, $len = count( $foundPieces ); $i < $len; $i++ ) {
					if ( $this->isOnSameFile( $foundPieces[$i], $fromFile ) ) {
						$ret['from'] = $foundPieces[$i];
						break;
					}
				}
			}

			if ( !isset( $ret['from'] ) ) {
				$config = $this->getValidMovesAndResult();
				$moves = $config['moves'];
				foreach ( $foundPieces as $piece ) {
					if ( in_array( $ret['to'], $moves[$piece] ) ) {
						$ret['from'] = $piece;
						break;
					}
				}
			}
		}
		// TODO some pgn files may not have correct notations for all moves.
		// Example Nd7 which may be from b2 or f6.
		// this may cause problems later on in the game. Figure out a way to handle this.
		#if (count($foundPieces) === 2){
		#$ret['from'] = $foundPieces[1];
		#throw new Exception("Unable to decide which move to take for notation: ". $notation);
		#}

		if ( !isset( $ret['from'] ) ) {
			$msg = "Fen: "
				. $this->fen
				. "\ncolor: "
				. $color
				. "\nnotation: "
				. $notation
				. "\nRank:"
				. $fromRank
				. "\nFile:"
				. $fromFile
				. "\n"
				. count( $foundPieces )
				. ", "
				. implode( ",", $foundPieces );
			throw new FenParser0x88Exception( $msg );
		}
		$ret['from'] = Board0x88Config::mapNumberToSquare( $ret['from'] );
		$ret['to'] = Board0x88Config::mapNumberToSquare( $ret['to'] );

		return $ret;
	}

	/**
	 * Check if fens include a threefold repetition
	 *
	 * @param array $fens
	 * @return bool
	 */
	public function hasThreeFoldRepetition( $fens = [] ) {
		if ( !count( $fens ) ) {
			return false;
		}
		$shortenedFens = [];
		foreach ( $fens as $fen ) {
			$fen = array_slice( explode( " ", $fen ), 0, 3 );
			$fen = implode( " ", $fen );
			$shortenedFens[] = $fen;
		}
		$lastFen = $shortenedFens[count( $shortenedFens ) - 1];
		$count = array_count_values( $shortenedFens );
		return $count[$lastFen] >= 2;
	}

	/**
	 * Get the promotion from notation
	 *
	 * If the notation token contains an equal sign then it's a promotion
	 *
	 * @param string $notation
	 * @return string
	 */
	public function getPromoteByNotation( $notation ) {
		if ( strstr( $notation, '=' ) ) {
			$piece = preg_replace( "/^.*?=([QRBN]).*$/", '$1', $notation );
			return strtolower( $piece );
		}

		if ( preg_match( "/[a-h][18][NBRQ]/", $notation ) ) {
			$notation = preg_replace( "/[^a-h18NBRQ]/s", "", $notation );
			return strtolower( substr( $notation, strlen( $notation ) - 1, 1 ) );
		}
		return '';
	}

	/**
	 * Get the rank of a notation
	 *
	 * TODO use \d
	 * TODO add an explicit cast of $notation from string to int
	 *
	 * @param string $notation
	 * @return null|int
	 */
	public function getFromRankByNotation( $notation ) {
		$notation = preg_replace( "/^.+([0-9]).+[0-9].*$/s", '$1', $notation );
		if ( strlen( $notation ) > 1 ) {
			return null;
		}
		return ( $notation - 1 ) * 16;
	}

	/**
	 * Get the file of a notation
	 *
	 * TODO use \d
	 *
	 * @param string $notation
	 * @return null|int
	 */
	public function getFromFileByNotation( $notation ) {
		$notation = preg_replace( "/^.*([a-h]).*[a-h].*$/s", '$1', $notation );
		if ( strlen( $notation ) > 1 ) {
			return null;
		}
		return Board0x88Config::$files[$notation];
	}

	/**
	 * Get the target square of notation
	 *
	 * TODO should the end be '' or something else
	 *
	 * @param string $notation
	 * @return int|string
	 */
	public function getToSquareByNotation( $notation ) {
		$notation = preg_replace( "/.*([a-h][1-8]).*/s", '$1', $notation );
		$ret = Board0x88Config::mapSquareToNumber( $notation );
		if ( $ret === false ) {
			$ret = '';
		}
		return $ret;
	}

	/**
	 * Get the type of a piece from notation
	 *
	 * @param string $notation
	 * @param string|null $color defaults to white
	 * @return int
	 */
	public function getPieceTypeByNotation( $notation, $color = null ) {
		if ( $notation === 'O-O-O' || $notation === 'O-O' ) {
			$pieceType = 'K';
		} else {
			$token = substr( $notation, 0, 1 );
			$pieceType = preg_match( "/[NRBQK]/", $token ) ? $token : 'P';
		}

		$pieceType = Board0x88Config::$pieces[$pieceType];
		if ( $color === 'black' ) {
			$pieceType += 8;
		}

		return $pieceType;
	}

	/**
	 * Make a move based on long notation
	 *
	 * @param string $notation
	 */
	public function moveByLongNotation( $notation ) {
		$fromAndTo = $this->getFromAndToByLongNotation( $notation );

		$this->move( $fromAndTo );
	}

	/**
	 * Make a move on the board
	 *
	 * Example:
	 *
	 * $parser = new FenParser0x88();
	 * $parser->newGame();
	 * $parser->move("Nf3");
	 * $notation =  $parser->getNotation();
	 *
	 * $move can be a string like Nf3, g1f3 or an array with from and to squares,
	 * like array("from" => "g1", "to"=>"f3")
	 *
	 * @param mixed $move
	 * @throws FenParser0x88Exception
	 */
	public function move( $move ) {
		if ( is_string( $move ) && strlen( $move ) == 4 ) {
			$move = $this->getFromAndToByLongNotation( $move );

		} elseif ( is_string( $move ) ) {
			$move = $this->getFromAndToByNotation( $move );
		}

		$validMoves = $this->validMoves();

		$from = Board0x88Config::mapSquareToNumber( $move['from'] );
		$to = Board0x88Config::mapSquareToNumber( $move['to'] );

		if ( empty( $validMoves[$from] ) || !in_array( $to, $validMoves[$from] ) ) {
			throw new FenParser0x88Exception(
				"Invalid move " . $this->getColor() . " - " . json_encode( $move )
			);
		}

		$this->fen = null;
		$this->validMoves = null;
		$this->piecesInvolved = $this->getPiecesInvolvedInMove( $move );
		$this->notation = $this->getNotationForAMove( $move );
		$this->updateBoardData( $move );

		$config = $this->getValidMovesAndResult();

		if ( $config['result'] === 1 || $config['result'] === -1 ) {
			$this->notation .= '#';
		} else {
			if ( $config['check'] > 0 ) {
				$this->notation .= '+';
			}
		}
	}

	/**
	 * Set the color to be the opposite
	 *
	 * TODO rename to switchActiveColor()
	 */
	public function setNewColor() {
		$this->fenParts['color'] = ( $this->fenParts['color'] == 'w' ) ? 'b' : 'w';
	}

	/**
	 * setCastle
	 *
	 * TODO document
	 *
	 * @param mixed $castle
	 */
	private function setCastle( $castle ) {
		if ( !$castle ) {
			$castle = '-';
		}
		$this->fenParts['castle'] = $castle;

		$castleCode = 0;
		for ( $i = 0, $count = strlen( $castle ); $i < $count; $i++ ) {
			$castleCode += Board0x88Config::$castle[substr( $castle, $i, 1 )];
		}
		$this->fenParts['castleCode'] = $castleCode;
	}

	/**
	 * Get the castle
	 *
	 * @return string
	 */
	public function getCastle() {
		return $this->fenParts['castle'];
	}

	/**
	 * updateBoardData based on passed move
	 *
	 * @param array $move
	 */
	private function updateBoardData( $move ) {
		$move = [
			'from' => Board0x88Config::mapSquareToNumber( $move['from'] ),
			'to' => Board0x88Config::mapSquareToNumber( $move['to'] ),
			'promoteTo' => isset( $move['promoteTo'] ) ? $move['promoteTo'] : ''
		];
		$movedPiece = $this->cache['board'][$move['from']];
		$color = ( $movedPiece & 0x8 ) ? 'black' : 'white';
		$enPassant = '-';

		if ( $this->cache['board'][$move['to']] ) {
			$incrementHalfMoves = false;
		} else {
			$incrementHalfMoves = true;
		}
		if (
			( $this->cache['board'][$move['from']] === 0x01
				|| $this->cache['board'][$move['from']] == 0x09 )
		) {
			$incrementHalfMoves = false;
			if ( $this->isEnPassantMove( $move ) ) {
				if ( $color == 'black' ) {
					$this->cache['board'][$move['to'] + 16] = null;
				} else {
					$this->cache['board'][$move['to'] - 16] = null;
				}
			}

			if (
				( $move['from'] & 15 ) == ( $move['to'] & 15 )
				&& $this->getDistance( $move['from'], $move['to'] ) == 2
			) {
				if ( $color === 'white' ) {
					$enPassant = Board0x88Config::mapNumberToSquare( $move['from'] + 16 );
				} else {
					$enPassant = Board0x88Config::mapNumberToSquare( $move['from'] - 16 );
				}
			}
		}

		$this->fenParts['enPassant'] = $enPassant;

		if ( $this->isCastleMove( [ 'from' => $move['from'], 'to' => $move['to'] ] ) ) {
			$castle = $this->getCastle();
			if ( $color == 'white' ) {
				$castleNotation = '/[KQ]/s';
				$pieceType = 0x06;
				$offset = 0;
			} else {
				$castleNotation = '/[kq]/s';
				$pieceType = 0x0E;
				$offset = 112;
			}

			if ( $move['from'] < $move['to'] ) {
				$this->cache['board'][7 + $offset] = null;
				$this->cache['board'][5 + $offset] = $pieceType;

			} else {
				$this->cache['board'][0 + $offset] = null;
				$this->cache['board'][3 + $offset] = $pieceType;
			}
			$castle = preg_replace( $castleNotation, '', $castle );
			$this->setCastle( $castle );
		} else {
			$this->updateCastleForMove( $movedPiece, $move['from'] );
		}

		if ( $color === 'black' ) {
			$this->fenParts['fullMoves']++;
		}
		if ( $incrementHalfMoves ) {
			$this->fenParts['halfMoves']++;
		} else {
			$this->fenParts['halfMoves'] = 0;
		}

		$this->cache['board'][$move['to']] = $this->cache['board'][$move['from']];
		$this->cache['board'][$move['from']] = null;
		if ( $move['promoteTo'] ) {
			$this->cache['board'][$move['to']] = Board0x88Config::$typeToNumberMapping[$move['promoteTo']];
			if ( $color === 'black' ) {
				$this->cache['board'][$move['to']] += 8;
			}
		}
		$this->setNewColor();
		$this->updatePieces();
	}

	/**
	 * Update the castle for a move
	 *
	 * @param int $movedPiece
	 * @param int $from
	 */
	private function updateCastleForMove( $movedPiece, $from ) {
		switch ( $movedPiece ) {
			case 0x03:
				$this->setCastle( preg_replace( "/[KQ]/s", "", $this->getCastle() ) );
				break;
			case 0x0B:
				$this->setCastle( preg_replace( "/[kq]/s", "", $this->getCastle() ) );
				break;
			case 0x06:
				if ( $from === 0 ) {
					$this->setCastle( preg_replace( "/[Q]/s", "", $this->getCastle() ) );
				}
				if ( $from === 7 ) {
					$this->setCastle( preg_replace( "/[K]/s", "", $this->getCastle() ) );
				}
				break;
			case 0x0E:
				if ( $from === 112 ) {
					$this->setCastle( preg_replace( "/[q]/s", "", $this->getCastle() ) );
				}
				if ( $from === 119 ) {
					$this->setCastle( preg_replace( "/[k]/s", "", $this->getCastle() ) );
				}
				break;
		}
	}

	/**
	 * updatePieces
	 *
	 * TODO document
	 */
	private function updatePieces() {
		$this->cache['white'] = [];
		$this->cache['black'] = [];
		$piece = null;
		for ( $i = 0; $i < 120; $i++ ) {
			if ( $i & 0x88 ) {
				$i += 8;
			}
			$piece = $this->cache['board'][$i];
			if ( $piece ) {
				$color = $piece & 0x8 ? 'black' : 'white';
				$obj = [
					't' => $piece,
					's' => $i
				];
				$this->cache[$color][] = $obj;

				if ( $piece == 0x03 || $piece == 0x0B ) {
					$this->cache['king' . $color] = $obj;
				}
			}
		}
	}

	/**
	 * Get the notation
	 *
	 * TODO document
	 *
	 * @return mixed
	 */
	public function getNotation() {
		return $this->notation;
	}

	/**
	 * Returns FEN for current position
	 *
	 * @return string
	 */
	public function getFen() {
		if ( !$this->fen ) {
			$this->fen = $this->getNewFen();
		}
		return $this->fen;
	}

	/**
	 * Convert a move to notation
	 *
	 * @param array $move
	 * @return string
	 */
	private function getNotationForAMove( $move ) {
		$move['from'] = Board0x88Config::mapSquareToNumber( $move['from'] );
		$move['to'] = Board0x88Config::mapSquareToNumber( $move['to'] );
		$type = $this->cache['board'][$move['from']];

		$ret = Board0x88Config::$notationMapping[$this->cache['board'][$move['from']]];

		switch ( $type ) {
			case 0x01:
			case 0x09:
				if ( $this->isEnPassantMove( $move ) || $this->cache['board'][$move['to']] ) {
					$ret .= Board0x88Config::$fileMapping[$move['from'] & 15] . 'x';
				}
				$ret .= Board0x88Config::$fileMapping[$move['to'] & 15]
					. ''
					. Board0x88Config::$rankMapping[$move['to'] & 240];
				if ( isset( $move['promoteTo'] ) && $move['promoteTo'] ) {
					$numType = Board0x88Config::$typeToNumberMapping[$move['promoteTo']];
					$ret .= '=' . Board0x88Config::$notationMapping[$numType];
				}
				break;
			case 0x02:
			case 0x05:
			case 0x06:
			case 0x07:
			case 0x0A:
			case 0x0D:
			case 0x0E:
			case 0x0F:
				$config = $this->getValidMovesAndResult();

				$configMoves = $config['moves'];
				foreach ( $configMoves as $square => $moves ) {
					if ( $square != $move['from'] && $this->cache['board'][$square] === $type ) {
						if ( array_search( $move['to'], $moves ) !== false ) {
							if ( ( $square & 15 ) != ( $move['from'] & 15 ) ) {
								$ret .= Board0x88Config::$fileMapping[$move['from'] & 15];
							} elseif ( ( $square & 240 ) != ( $move['from'] & 240 ) ) {
								$ret .= Board0x88Config::$rankMapping[$move['from'] & 240];
							}
						}
					}
				}

				if ( $this->cache['board'][$move['to']] ) {
					$ret .= 'x';
				}
				$ret .= Board0x88Config::$fileMapping[$move['to'] & 15];
				$ret .= Board0x88Config::$rankMapping[$move['to'] & 240];
				break;
			case 0x03:
			case 0x0B:
				if ( $this->isCastleMove( $move ) ) {
					if ( $move['to'] > $move['from'] ) {
						$ret = 'O-O';
					} else {
						$ret = 'O-O-O';
					}
				} else {
					if ( $this->cache['board'][$move['to']] ) {
						$ret .= 'x';
					}
					$ret .= Board0x88Config::$fileMapping[$move['to'] & 15]
						. ''
						. Board0x88Config::$rankMapping[$move['to'] & 240];
				}
				break;

		}

		return $ret;
	}

	/**
	 * Get a new fen
	 *
	 * @return string
	 */
	private function getNewFen() {
		$board = $this->cache['board'];
		$fen = '';
		$emptyCounter = 0;

		for ( $rank = 7; $rank >= 0; $rank-- ) {
			for ( $file = 0; $file < 8; $file++ ) {
				$index = ( $rank * 8 ) + $file;
				$mapped = Board0x88Config::mapNumber( $index );
				if ( $board[$mapped] ) {
					if ( $emptyCounter ) {
						$fen .= $emptyCounter;
					}
					$fen .= Board0x88Config::$pieceMapping[$board[$mapped]];
					$emptyCounter = 0;
				} else {
					$emptyCounter++;
				}
			}
			if ( $rank ) {
				if ( $emptyCounter ) {
					$fen .= $emptyCounter;
				}
				$fen .= '/';
				$emptyCounter = 0;
			}
		}

		if ( $emptyCounter ) {
			$fen .= $emptyCounter;
		}
		$returnValue = $fen
			. ' '
			. $this->fenParts['color']
			. ' '
			. $this->getCastle()
			. ' '
			. $this->fenParts['enPassant']
			. ' '
			. $this->fenParts['halfMoves']
			. ' '
			. $this->fenParts['fullMoves'];
		return $returnValue;
	}
}

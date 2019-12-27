<?php

/**
 * This class contains the PGN parsing logic for the extension. When a
 * <pgn></pgn> tag is encountered, the input is passed to this class and
 * is parsed into export format PGN for the JavaScript module.
 */
class PgnParser {
	/**
	 * @var string Text passed from <pgn></pgn> wikitext
	 */
	public $movetext = '';

	/**
	 * @var int Index for location in the movetext string
	 */
	public $cursor = 0;

	/**
	 * @var int End of game index marking the end of the movetext string
	 */
	public $EOG = 0;

	/**
	 * @var int When exporting, keeps track of the move number
	 */
	public $moveNum = 0;

	/**
	 * @var array Tag pairs from PGN tag section, of the form ["tag"=>value]
	 */
	public $tags = [];

	/**
	 * @param string $mt Text passed from ChessBrowser::pollRender
	 * @param int $index The position to start the cursor at, defaults to the
	 *   beginning of the string.
	 */
	public function __construct( $mt, $index = 0 ) {
		$this->movetext = $mt;
		$this->cursor = $index;
		$this->EOG = strlen( $mt ) - 1;
	}

	/**
	 * @return string Export format PGN
	 */
	public function parseMovetext() {
		$tokenSequence = [];
		while ( $this->cursor < $this->EOG ) {
			$char = $this->movetext[$this->cursor];
			switch ( $char ) {
				case '(':
					$token = $this->parseVariation( $this->cursor );
					break;
				case '{':
					$token = $this->parseComment( $this->cursor );
					break;
				case '"':
					$token = $this->parseString( $this->cursor );
					break;
				case '$':
					$token = $this->parseNumericAnnotationGlyph( $this->cursor );
					break;
				case '*':
					$token = $char;
					break;
				default:
					if ( ctype_digit( $char ) || ctype_alpha( $char ) ) {
						$token = $this->parseStandardAlgebraicNotation( $this->cursor );
					} elseif ( $char == '[' ) {
						$token = $this->parseTagPair( $this->cursor );
						$this->tags[$token[0]] = $token[1];
						continue 2;
					} else {
						$this->cursor += 1;
						continue 2;
					}
			}
			array_push( $tokenSequence, $token );
			$this->cursor += 1;
		}
		# return $this->cut(0,$this->cursor);
		#return sizeof($tokenSequence);
		return $this->exportPgn( $tokenSequence );
	}

	/**
	 * @param null|int $index Location of character in string. If null, defaults
	 *   to the current $cursor location.
	 * @return string Character at $index
	 */
	public function getChar( $index = null ) {
		$index = ( isset( $index ) ? $index : $this->cursor );
		if ( $index > $this->EOG || $index < 0 ) {
			throw new Exception( "Invalid index" );
		}
		$char = $this->movetext[$index];
		return $char;
	}

	/**
	 * @param int $start
	 * @param int $end
	 * @return string Substring of $movetext from $start to $end (inclusive)
	 */
	public function cut( $start, $end ) {
		# If $start == $end, cut is equivalent to getChar($start)
		$end += 1;
		if ( $start > $end ) {
			throw new Exception( 'End index is before start index.' );
		}
		return substr( $this->movetext, $start, $end - $start );
	}

	public function parseVariation( $index ) {
		$stack = 1;
		$start = $index;
		$index += 1;
		while ( $index < $this->EOG ) {
			$char = $this->getChar( $index );
			if ( $char == ')' ) {
				$stack -= 1;
			} elseif ( $char == '(' ) {
				$stack += 1;
			}
			if ( $stack < 1 ) {
				# This should probably return bool and have the open
				#   parenthesis be its own token so that the cursor
				#   doesn't have to jump any nested variations.
				$this->cursor = $index;
				return $this->cut( $start, $index );
			} else {
				$index += 1;
			}
		}
		throw new Exception( 'Variation does not terminate' );
	}

	public function parseComment( $index ) {
		# Does not handle "Rest of line" comments as
		#   specified in section 5 of the PGN standard
		$start = $index;
		$index += 1;
		while ( $index < $this->EOG ) {
			$char = $this->getChar( $index );
			if ( $char == '}' ) {
				if ( !$this->checkEscape( $index ) ) {
					$this->cursor = $index;
					return $this->cut( $start, $index );
				}
			}
			$index += 1;
		}
		throw new Exception( 'Comment not terminated' );
	}

	public function parseString( $index ) {
		$start = $index;
		$index += 1;
		while ( $index < $this->EOG ) {
			$char = $this->getChar( $index );
			if ( $char == '"' ) {
				if ( !$this->checkEscape( $index ) ) {
					$this->cursor = $index;
					return $this->cut( $start, $index );
				}
			}
			$index += 1;
		}
		throw new Exception( 'String token starting at '
							. $start
							. ' not terminated' );
	}

	public function parseNumericAnnotationGlyph( $index ) {
		$start = $index;
		$index += 1;
		while ( $index < $this->EOG ) {
			$char = $this->getChar( $index );
			if ( !is_numeric( $char ) ) {
				if ( !$this->checkEscape( $index ) ) {
					$index -= 1;
					$this->cursor = $index;
					return $this->cut( $start, $index );
				}
			}
			$index += 1;
		}
		throw new Exception( 'Numeric annotation glyph not terminated.' );
	}

	public function parseStandardAlgebraicNotation( $cur ) {
		$start = $cur;
		while ( $cur < $this->EOG ) {
			$char = $this->getChar( $cur );
			if ( ctype_digit( $char ) ) {
				$index = $cur;
				while ( $index < $this->EOG ) {
					$index += 1;
					$char = $this->getChar( $index );
					if ( ctype_space( $char ) || $char == '.' ) {
						$index -= 1;
						$this->cursor = $index;
						return $this->cut( $start, $index );
					} elseif ( $char === '/' || $char === '-' ) {
						return $this->cut( $start, $this->EOG );
					}
				}
			} else {
				$index = $cur;
				while ( $index < $this->EOG ) {
					$index += 1;
					$char = $this->getChar( $index );
					if ( ctype_space( $char ) ) {
						$index -= 1;
						$this->cursor = $index;
						return $this->cut( $start, $index );
					}
				}
			}
			$cur += 1;
		}
	}

	public function parseTagPair( $index ) {
		$start = $index;
		$index += 1;
		while ( $index < $this->EOG ) {
			if ( $this->getChar( $index ) == ']' ) {
				break;
			}
			$index += 1;
		}
		if ( $index == $this->EOG ) {
			throw new Exception( 'Tag does not terminate' );
		}
		$tagPair = $this->cut( $start + 1, $index - 1 );
		$tagArray = explode( '"', $tagPair );
		$key = trim( $tagArray[0] );
		$value = trim( $tagArray[1] );
		$this->cursor = $index;
		return [ $key,$value ];
	}

	public function checkEscape( $index ) {
		$stack = 0;
		while ( $index >= 0 ) {
			$index -= 1;
			$char = $this->movetext[$index];
			if ( $char == "\\" ) {
				$stack += 1;
			} else {
				break 1;
			}
		}
		return ( $stack % 2 == 1 );
	}

	public function exportPgn( $tokenList ) {
		$tags = $this->tags;
		$line = '';
		$out = '[Event "' . $tags['Event'] . '"]' . "\n"
				. '[Site "' . $tags['Site'] . '"]' . "\n"
				. '[Date "' . $tags['Date'] . '"]' . "\n"
				. '[Round "' . $tags['Round'] . '"]' . "\n"
				. '[White "' . $tags['White'] . '"]' . "\n"
				. '[Black "' . $tags['Black'] . '"]' . "\n"
				. '[Result "' . $tags['Result'] . '"]' . "\n";
		$NonRosterTags = array_diff_key( $tags, [
			'Event' => 0,
			'Site' => 0,
			'Date' => 0,
			'Round' => 0,
			'White' => 0,
			'Black' => 0,
			'Result' => 0,
		] );
		ksort( $NonRosterTags );
		foreach ( $NonRosterTags as $key => $value ) {
			$out .= '[' . $key . ' "' . $value . '"]' . "\n";
		}
		$out .= "\n";
		foreach ( $tokenList as $token ) {
			if ( strlen( $line ) > 80 ) {
				$out .= trim( $line ) . "\n";
				$line = '';
			}
			if ( is_numeric( $token ) ) {
				$line .= $token . '.';
			} else {
				$line .= $token . ' ';
			}
		}
		$out .= $line;
		return $out;
	}

}

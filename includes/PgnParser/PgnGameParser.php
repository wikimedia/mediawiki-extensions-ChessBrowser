<?php


class PgnGameParser {

	private $pgnGame;
	private $defaultFen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

	private $gameData = [];

	private $specialMetadata = [
		'event','site','white','black','result','plycount','eco','fen',
		'timecontrol','round','date','annotator','termination'
	];

	/**
	 * Create a new parser
	 *
	 * @param string|null $pgnGame
	 */
	public function __construct( $pgnGame = null ) {
		if ( isset( $pgnGame ) ) {
			$this->pgnGame = trim( $pgnGame );
			$this->moveBuilder = new MoveBuilder();
		}
	}

	/**
	 * Set or reset the parser's pgn
	 *
	 * @param string $pgnGame
	 */
	public function setPgn( $pgnGame ) {
		$this->pgnGame = trim( $pgnGame );
		$this->gameData = [];
		$this->moveBuilder = new MoveBuilder();
	}

	/**
	 * Get the parsed data
	 *
	 * @return array
	 */
	public function getParsedData() {
		$this->gameData = $this->getMetadata();
		$this->gameData[ChessJson::MOVE_MOVES] = $this->getMoves();
		return $this->gameData;
	}

	/**
	 * Get the metadata
	 *
	 * @return array
	 */
	private function getMetadata() {
		$ret = [
			ChessJson::GAME_METADATA => []
		];
		// TODO set lastmoves property by reading last 3-4 moves in moves array
		$lines = explode( "\n", $this->pgnGame );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( substr( $line, 0, 1 ) === '[' && substr( $line, strlen( $line ) - 1, 1 ) === ']' ) {
				$metadata = $this->getMetadataKeyAndValue( $line );
				if ( in_array( $metadata['key'], $this->specialMetadata ) ) {
					$ret[$metadata['key']] = $metadata['value'];
				} else {
					$ret[ChessJson::GAME_METADATA][$metadata['key']] = $metadata['value'];
				}
			}
		}
		if ( !isset( $ret[ChessJson::FEN] ) ) {
			$ret[ChessJson::FEN] = $this->defaultFen;
		}

		return $ret;
	}

	/**
	 * Get the metadata key and value from a string
	 *
	 * @param string $metadataString
	 * @return array
	 */
	private function getMetadataKeyAndValue( $metadataString ) {
		$metadataString = preg_replace( "/[\[\]]/s", "", $metadataString );
		$metadataString = str_replace( '"', '', $metadataString );
		$tokens = explode( " ", $metadataString );

		$key = $tokens[0];
		$value = implode( " ", array_slice( $tokens, 1 ) );
		$ret = [ 'key' => $this->getValidKey( $key ),  'value' => $value ];
		return $ret;
	}

	/**
	 * Convert a key to lowercase
	 *
	 * TODO is this really needed?
	 *
	 * @param string $key
	 * @return string
	 */
	private function getValidKey( $key ) {
		$key = strtolower( $key );
		return $key;
	}

	/**
	 * Get the moves
	 *
	 * @return array
	 */
	private function getMoves() {
		$parts = $this->getMovesAndComments();
		for ( $i = 0, $count = count( $parts ); $i < $count; $i++ ) {
			$move = trim( $parts[$i] );

			switch ( $move ) {
				case '{':
					if ( $i == 0 ) {
						$this->moveBuilder->addCommentBeforeFirstMove( $parts[$i + 1] );
					} else {
						$this->moveBuilder->addComment( $parts[$i + 1] );
					}
					$i += 2;
					break;
				default:
					$moves = $this->getMovesAndVariationFromString( $move );
					foreach ( $moves as $move ) {
						switch ( $move ) {
							case '(':
								$this->moveBuilder->startVariation();
								break;
							case ')':
								$this->moveBuilder->endVariation();
								break;
							default:
								$this->moveBuilder->addMoves( $move );
						}
					}
					break;
			}
		}

		return $this->moveBuilder->getMoves();
	}

	/**
	 * Add a game comment
	 *
	 * @param string $comment
	 */
	private function addGameComment( $comment ) {
		$this->gameData[ChessJson::GAME_METADATA][ChessJson::MOVE_COMMENT] = $comment;
	}

	/**
	 * Get the moves and comments
	 *
	 * @return array
	 */
	private function getMovesAndComments() {
		$ret = preg_split( "/({|})/s", $this->getMoveString(), 0, PREG_SPLIT_DELIM_CAPTURE );
		if ( !$ret[0] ) {
			$ret = array_slice( $ret, 1 );
		}
		return $ret;
	}

	/**
	 * Get the moves and variations from a string
	 *
	 * TODO make static
	 *
	 * @param string $string
	 * @return array
	 */
	private function getMovesAndVariationFromString( $string ) {
		$string = " " . $string;

		$string = preg_replace( "/[0-9]+?\./s", "", $string );
		$string = str_replace( " ..", "", $string );
		$string = str_replace( "  ", " ", $string );
		$string = trim( $string );

		return preg_split( "/(\(|\))/s", $string, 0, PREG_SPLIT_DELIM_CAPTURE );
	}

	/**
	 * Get a move string
	 *
	 * @return string
	 */
	private function getMoveString() {
		$tokens = preg_split( "/\]\n\n/s", $this->pgnGame );
		if ( count( $tokens ) < 2 ) {
			return "";
		}
		$gameData = $tokens[1];
		$gameData = str_replace( "\n", " ", $gameData );
		$gameData = preg_replace( "/(\s+)/", " ", $gameData );
		return trim( $gameData );
	}
}

<?php

/**
 * Originally by alfmagne1
 * Created 13/03/2017
 */

class JsonToPgnParser {

	private $games;

	public function __construct() {
		$this->games = [];
	}

	/**
	 * @param string $jsonString
	 */
	public function addGame( $jsonString ) {
		$this->addGameObject( json_decode( $jsonString, true ) );
	}

	/**
	 * @param array $json
	 */
	public function addGameObject( $json ) {
		$this->games[] = $json;
	}

	/**
	 * Get as pgn
	 *
	 * @return string
	 */
	public function asPgn() {
		$ret = [];
		foreach ( $this->games as $game ) {
			$ret[] = $this->gameToPgn( $game );
		}
		return implode( "\n\n", $ret );
	}

	/**
	 * Convert a game to pgn
	 *
	 * @param arary $game
	 * @return string
	 */
	private function gameToPgn( $game ) {
		$moves = [];
		$metadata = [];

		foreach ( $game as $key => $value ) {
			switch ( $key ) {
				case "moves":
					$moves = $this->movesToPgn( $value, $this->getStartMove( $game ) );
					break;
				default:
					if ( is_string( $value ) ) {
						$metadata[] = '[' . ucfirst( $key ) . ' "' . $value . '"]';
					}
					break;
			}

		}
		return implode( "\n", $metadata ) . "\n\n" . $moves;
	}

	/**
	 * Get the first move
	 *
	 * @param array $game
	 * @return int
	 */
	private function getStartMove( $game ) {
		if ( empty( $game["fen"] ) ) {
			return 1;
		}
		$tokens = explode( " ", $game["fen"] );
		$ret = array_pop( $tokens );
		if ( $tokens[1] == "b" ) {
			$ret += 0.5;
		}
		return $ret;
	}

	/**
	 * Convert moves to pgn
	 *
	 * @param array $moves
	 * @param int $startMove
	 * @return string
	 */
	private function movesToPgn( $moves, $startMove ) {
		$ret = [];

		if ( $startMove != floor( $startMove ) ) {
			$ret[] = floor( $startMove ) . "...";
		}

		foreach ( $moves as $move ) {
			if ( !empty( $move["m"] ) ) {
				if ( $startMove == floor( $startMove ) ) {
					$ret[] = $startMove . ".";
				}
				$ret[] = str_replace( "..", "", $move["m"] );
			}
			if ( !empty( $move["comment"] ) ) {
				$ret[] = '{' . $move["comment"] . "}";
			}

			if ( !empty( $move["variations"] ) ) {
				foreach ( $move["variations"] as $variation ) {
					if ( !empty( $variation ) ) {
						$ret[] = "(" . $this->movesToPgn( $variation, $startMove );
					}
				}
			}
			if ( !empty( $move["m"] ) ) {
				$startMove += 0.5;
			}
		}

		return implode( " ", $ret );
	}
}

<?php


class GameParser {

	private $game;
	private $fen;
	private $fenParser0x88;

	private $shortVersion;

	public function __construct() {
		$this->fenParser0x88 = new FenParser0x88();
	}

	/**
	 * @param array $game
	 * @param bool $short for only from and to squares
	 * @return mixed
	 */
	public function getParsedGame( $game, $short = false ) {
		$this->game = $game;
		$this->shortVersion = $short;
		$this->fen = $this->getStartFen();

		$this->fenParser0x88->newGame( $this->fen );
		$this->parseMoves( $this->game[ChessJson::MOVE_MOVES] );
		$this->addParsedProperty();
		return $this->game;
	}

	private function addParsedProperty() {
		$this->game[ChessJson::GAME_METADATA][ChessJson::MOVE_PARSED] = 1;
	}

	private function parseMoves( &$moves ) {
		foreach ( $moves as &$move ) {
			$this->parseAMove( $move );
		}
	}

	private function parseAMove( &$move ) {
		if (
			!isset( $move[ChessJson::MOVE_NOTATION] )
			|| (
				isset( $move[ChessJson::FEN] )
				&& isset( $move[ChessJson::MOVE_FROM] )
				&& isset( $move[ChessJson::MOVE_TO] )
			)
		) {
			return;
		}

		if ( strlen( $move[ChessJson::MOVE_NOTATION] ) < 2 ) { return;
		}
		if ( isset( $move[ChessJson::MOVE_VARIATIONS] ) ) {
			$fen = $this->fenParser0x88->getFen();
			$this->parseVariations( $move[ChessJson::MOVE_VARIATIONS] );
			$this->fenParser0x88->setFen( $fen );
		}
		$move = $this->fenParser0x88->getParsed( $move );
	}

	private function parseVariations( &$variations ) {
		foreach ( $variations as &$variation ) {
			$fen = $this->fenParser0x88->getFen();
			$this->parseMoves( $variation );
			$this->fenParser0x88->setFen( $fen );
		}
	}

	private function getStartFen() {
		return $this->game[ChessJson::FEN];
	}
}

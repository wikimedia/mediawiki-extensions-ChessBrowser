<?php
/**
 * This file is a part of ChessBrowser.
 *
 * ChessBrowser is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * This file is a part of PgnParser
 *
 * PgnParser is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @file GameParser
 * @ingroup ChessBrowser
 * @author Alf Magne Kalleland
 */

namespace MediaWiki\Extension\ChessBrowser\PgnParser;

class GameParser {

	private $game;
	private $fenParser0x88;

	/**
	 * @param array $game
	 */
	public function __construct( array $game ) {
		$this->game = $game;
	}

	/**
	 * @return array
	 */
	public function getParsedGame() {
		$game = $this->game;

		$this->fenParser0x88 = new FenParser0x88( $game[ChessJson::FEN] );
		$this->parseMoves( $game[ChessJson::MOVE_MOVES] );

		$game[ChessJson::GAME_METADATA][ChessJson::MOVE_PARSED] = 1;

		return $game;
	}

	/**
	 * Parse each move
	 *
	 * @param array &$moves
	 */
	private function parseMoves( &$moves ) {
		foreach ( $moves as &$move ) {
			$this->parseAMove( $move );
		}
	}

	/**
	 * Parse a move
	 *
	 * @param array &$move
	 */
	private function parseAMove( &$move ) {
		if (
			!isset( $move[ChessJson::MOVE_NOTATION] )
			|| (
				isset( $move[ChessJson::FEN] )
				&& isset( $move[ChessJson::MOVE_FROM] )
				&& isset( $move[ChessJson::MOVE_TO] )
			)
			|| strlen( $move[ChessJson::MOVE_NOTATION] ) < 2
		) {
			return;
		}

		if ( isset( $move[ChessJson::MOVE_VARIATIONS] ) ) {
			$fen = $this->fenParser0x88->getFen();
			$this->parseVariations( $move[ChessJson::MOVE_VARIATIONS] );
			$this->fenParser0x88->setFen( $fen );
		}
		$move = $this->fenParser0x88->getParsed( $move );
	}

	/**
	 * Parse variations
	 *
	 * @param array &$variations
	 */
	private function parseVariations( &$variations ) {
		foreach ( $variations as &$variation ) {
			$fen = $this->fenParser0x88->getFen();
			$this->parseMoves( $variation );
			$this->fenParser0x88->setFen( $fen );
		}
	}

}

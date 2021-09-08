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
 * @file MoveBuilder
 * @ingroup ChessBrowser
 * @author Alf Magne Kalleland
 */

namespace MediaWiki\Extension\ChessBrowser\PgnParser;

class MoveBuilder {

	private const PGN_KEY_ACTION_ARROW = "ar";
	private const PGN_KEY_ACTION_HIGHLIGHT = "sq";
	private const PGN_KEY_ACTION_CLR_HIGHLIGHT = "csl";
	private const PGN_KEY_ACTION_CLR_ARROW = "cal";

	private $moves = [];
	private $moveReferences = [];
	private $pointer = 0;
	private $currentIndex = 0;

	public function __construct() {
		$this->moveReferences[0] = &$this->moves;
	}

	/**
	 * Add moves, separated by spaces
	 *
	 * @param string $moveString
	 */
	public function addMoves( $moveString ) {
		$moves = explode( " ", $moveString );
		foreach ( $moves as $move ) {
			$this->addMove( $move );
		}
	}

	/**
	 * Add a single move
	 *
	 * @param string $move
	 */
	private function addMove( $move ) {
		if ( !$this->isChessMove( $move ) ) {
			return;
		}
		$move = preg_replace( "/^([a-h])([18])([QRNB])$/", "$1$2=$3", $move );
		$this->moveReferences[$this->pointer][] = [ ChessJson::MOVE_NOTATION => $move ];
		$this->currentIndex++;
	}

	/**
	 * Check if a string is a valid chess move
	 *
	 * @param string $move
	 * @return bool
	 */
	private function isChessMove( $move ) {
		if ( $move == '--' ) {
			return true;
		}
		$regex = "/([PNBRQK]?[a-h]?[1-8]?x?[a-h][1-8](?:\=[PNBRQK])?|O(-?O){1,2})[\+#]?(\s*[\!\?]+)?/s";
		return preg_match( $regex, $move );
	}

	/**
	 * Insert a comment before the first move
	 *
	 * @param string $comment
	 */
	public function addCommentBeforeFirstMove( $comment ) {
		$comment = trim( $comment );
		if ( !strlen( $comment ) ) {
			return;
		}
		$this->moveReferences[$this->pointer][] = [];
		$this->addComment( $comment );
	}

	/**
	 * Insert a comment at the current location
	 *
	 * @param string $comment
	 */
	public function addComment( $comment ) {
		$comment = trim( $comment );
		if ( !strlen( $comment ) ) {
			return;
		}
		# $index = max(0,count($this->moveReferences[$this->pointer])-1);
		$index = count( $this->moveReferences[$this->pointer] ) - 1;

		if ( strstr( $comment, '[%clk' ) ) {
			$clk = preg_replace( '/\[%clk\D*?([\d\:]+?)[\]]/si', '$1', $comment );
			$comment = str_replace( '[%clk ' . $clk . ']', '', $comment );
			$this->moveReferences[$this->pointer][$index][ChessJson::MOVE_CLOCK] = $clk;
		}

		$actions = $this->getActions( $comment );
		if ( !empty( $actions ) ) {
			if ( empty( $this->moveReferences[$this->pointer][$index][ChessJson::MOVE_ACTIONS] ) ) {
				$this->moveReferences[$this->pointer][$index][ChessJson::MOVE_ACTIONS] = [];
			}
			foreach ( $actions as $action ) {
				$this->moveReferences[$this->pointer][$index][ChessJson::MOVE_ACTIONS][] = $action;
			}
		}

		$comment = preg_replace(
			'/\[%'
			. self::PGN_KEY_ACTION_ARROW
			. '[^\]]+?\]/si', '', $comment
		);
		$comment = preg_replace(
			'/\[%'
			. self::PGN_KEY_ACTION_CLR_ARROW
			. '[^\]]+?\]/si', '', $comment
		);
		$comment = preg_replace(
			'/\[%'
			. self::PGN_KEY_ACTION_HIGHLIGHT
			. '[^\]]+?\]/si', '', $comment
		);
		$comment = preg_replace(
			'/\[%'
			. self::PGN_KEY_ACTION_CLR_HIGHLIGHT
			. '[^\]]+?\]/si', '', $comment
		);
		$comment = trim( $comment );

		if ( empty( $comment ) ) {
			return;
		}

		if ( $index === -1 ) {
			$index = 0;
			$this->moveReferences[$this->pointer][$index][ChessJson::MOVE_COMMENT] = $comment;
			$this->currentIndex++;
		} else {
			$this->moveReferences[$this->pointer][$index][ChessJson::MOVE_COMMENT] = $comment;
		}
	}

	/**
	 * getActions
	 *
	 * TODO document
	 *
	 * @param string $comment
	 * @return array
	 */
	private function getActions( $comment ) {
		$ret = [];
		if ( strstr( $comment, '[%' . self::PGN_KEY_ACTION_ARROW ) ) {
			$arrow = preg_replace(
				'/.*?\[%'
				. self::PGN_KEY_ACTION_ARROW
				. ' ([^\]]+?)\].*/si', '$1', $comment
			);
			$arrows = explode( ",", $arrow );

			foreach ( $arrows as $arrow ) {
				$tokens = explode( ";", $arrow );
				if ( strlen( $tokens[0] ) == 4 ) {
					$action = [
						'from' => substr( $arrow, 0, 2 ),
						'to' => substr( $arrow, 2, 2 ),
						'type' => 'arrow'
					];
					if ( isset( $tokens[1] ) ) {
						$action['color'] = $tokens[1];
					}
					$ret[] = $action;
				}
			}
		}

		if ( strstr( $comment, '[%' . self::PGN_KEY_ACTION_CLR_ARROW ) ) {
			$arrow = preg_replace(
				'/.*?\[%'
				. self::PGN_KEY_ACTION_CLR_ARROW
				. ' ([^\]]+?)\].*/si', '$1', $comment
			);
			$arrows = explode( ",", $arrow );

			foreach ( $arrows as $arrow ) {
				$len = strlen( $arrow );
				$color = "G";
				if ( $len === 5 ) {
					$color = substr( $arrow, 0, 1 );
					$arrow = substr( $arrow, 1 );

				}

				if ( strlen( $arrow ) === 4 ) {
					$action = [
						'from' => substr( $arrow, 0, 2 ),
						'to' => substr( $arrow, 2, 2 ),
						'color' => $color,
						'type' => 'arrow',
					];
					$ret[] = $action;
				}
			}
		}

		if ( strstr( $comment, '[%' . self::PGN_KEY_ACTION_HIGHLIGHT ) ) {
			$arrow = preg_replace(
				'/.*?\[%'
				. self::PGN_KEY_ACTION_HIGHLIGHT
				. ' ([^\]]+?)\].*/si', '$1', $comment
			);
			$arrows = explode( ",", $arrow );

			foreach ( $arrows as $arrow ) {
				$tokens = explode( ";", $arrow );
				if ( strlen( $tokens[0] ) == 2 ) {
					$action = [
						'square' => substr( $arrow, 0, 2 ),
						'type' => 'highlight',
					];
					if ( isset( $tokens[1] ) ) {
						$action["color"] = $tokens[1];
					}
					$ret[] = $action;
				}
			}
		}

		if ( strstr( $comment, '[%' . self::PGN_KEY_ACTION_CLR_HIGHLIGHT ) ) {
			$arrow = preg_replace(
				'/.*?\[%'
				. self::PGN_KEY_ACTION_CLR_HIGHLIGHT
				. ' ([^\]]+?)\].*/si', '$1', $comment
			);
			$arrows = explode( ",", $arrow );

			foreach ( $arrows as $arrow ) {
				$color = "G";
				if ( strlen( $arrow ) === 3 ) {
					$color = substr( $arrow, 0, 1 );
					$arrow = substr( $arrow, 1 );
				}

				if ( strlen( $arrow ) === 2 ) {
					$action = [
						'square' => substr( $arrow, 0, 2 ),
						'color' => $color,
						'type' => 'highlight',
					];
					$ret[] = $action;
				}
			}
		}

		return $ret;
	}

	/**
	 * Begin a variation at the current index
	 */
	public function startVariation() {
		$index = count( $this->moveReferences[$this->pointer] ) - 1;
		if ( !isset( $this->moveReferences[$this->pointer][$index][ChessJson::MOVE_VARIATIONS] ) ) {
			$this->moveReferences[$this->pointer][$index][ChessJson::MOVE_VARIATIONS] = [];
		}
		$moveVar = ChessJson::MOVE_VARIATIONS;
		$countVars = count( $this->moveReferences[$this->pointer][$index][$moveVar] );
		$this->moveReferences[$this->pointer][$index][$moveVar][$countVars] = [];
		// @phan-suppress-next-line PhanTypeArraySuspiciousNullable
		$this->moveReferences[] =& $this->moveReferences[$this->pointer][$index][$moveVar][$countVars];
		$this->pointer++;
	}

	/**
	 * End a variation
	 */
	public function endVariation() {
		array_pop( $this->moveReferences );
		$this->pointer--;
	}

	/**
	 * Get the moves
	 *
	 * @return array
	 */
	public function getMoves() {
		return $this->moveReferences;
	}
}

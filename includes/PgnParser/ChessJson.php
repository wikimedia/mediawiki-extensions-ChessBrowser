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
 * @file ChessJson
 * @ingroup ChessBrowser
 * @author Alf Magne Kalleland
 */

namespace MediaWiki\Extension\ChessBrowser\PgnParser;

class ChessJson {

	public const MOVE_FROM = 'from';
	public const MOVE_TO = 'to';
	public const MOVE_NOTATION = 'm';
	public const FEN = 'fen';
	public const MOVE_COMMENT = 'comment';
	public const MOVE_CLOCK = 'clk';
	public const MOVE_ACTIONS = 'actions';
	public const MOVE_VARIATIONS = 'variations';
	public const MOVE_MOVES = 'moves';
	public const MOVE_PARSED = 'parsed';

	public const GAME_METADATA = 'metadata';

}

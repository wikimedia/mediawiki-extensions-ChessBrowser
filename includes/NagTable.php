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
 * @file NagTable
 * @ingroup ChessBrowser
 * @author Wugapodes
 */

namespace MediaWiki\Extension\ChessBrowser;

class NagTable {
	// phpcs:disable MediaWiki.WhiteSpace.SpaceBeforeSingleLineComment.NewLineComment
	public const MAP = [
		'$1' => "!", // U+0021
		'$2' => "?", // U+003F
		'$3' => "‼", // U+203C
		'$4' => "⁇", // U+2047
		'$5' => "⁉", // U+2049
		'$6' => "⁈", // U+2048
		'$7' => "□", // U+25A1
		'$10' => "=", // U+003D
		'$13' => "∞", // U+221E
		'$14' => "⩲", // U+2A72
		'$15' => "⩱", // U+2A71
		'$16' => "±", // U+00B1
		'$17' => "∓", // U+2213
		'$18' => "+−", // U+002B U+002D
		'$19' => "−+", // U+002D U+002B
		'$22' => "⨀", // U+2A00
		'$23' => "⨀", // U+2A00
		'$26' => "○", // U+25CB
		'$27' => "○", // U+25CB
		'$32' => "⟳", // U+27F3
		'$33' => "⟳", // U+27F3
		'$36' => "↑", // U+2191
		'$37' => "↑", // U+2191
		'$40' => "→", // U+2192
		'$41' => "→", // U+2192
		'$45' => "=/∞", // U+2A73 (roughly)
		'$46' => "=/∞", // U+2A73 (roughly)
		'$132' => "⇆", // U+21C6
		'$133' => "⇆", // U+21C6
		'$138' => "⨁", // U+2A01
		'$139' => "⨁", // U+2A01
		/* The following are non-standard */
		'$140' => "∆", // U+2206
		'$141' => "∇", // U+2207
		'$142' => "⌓", // U+2313
		'$143' => "<=", // ??
		'$144' => "==", // U+003D U+003D
		'$145' => "RR", // ??
		'$146' => "N", // ??
		'$238' => "○", // U+25CB
		'$239' => "⇔", // U+21D4
		'$240' => "⇗", // U+21D7
		'$241' => "⊞", // U+229E
		'$242' => "⟫", // U+27EB
		'$243' => "⟪", // U+27EA
		'$244' => "✕", // U+2715
		'$245' => "⊥", // U+22A5
	];

	/**
	 * Replace NAGs in the PGN with their unicode equivalent.
	 *
	 * @param string $pgn The PGN to be replaced.
	 * @return string
	 */
	public static function replaceNag( string $pgn ): string {
		// Replacement respects array order, but an ascending
		// list is easier for humans to read and maintain so
		// the NagTable::MAP is reversed before replacement.
		$nags = array_keys( array_reverse( self::MAP ) );
		$symbols = array_values( array_reverse( self::MAP ) );
		return str_replace( $nags, $symbols, $pgn );
	}
}

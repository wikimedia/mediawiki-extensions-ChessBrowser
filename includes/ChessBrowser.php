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
 * @file ChessBrowser
 * @ingroup ChessBrowser
 * @author Wugapodes
 */

namespace MediaWiki\Extension\ChessBrowser;

use Exception;
use Parser;
use PPFrame;
use TemplateParser;

class ChessBrowser {
	/**
	 * @since 0.1.0
	 * @param string $input The wikitext placed between pgn tags
	 * @param array $args Arguments passed as xml attributes
	 * @param Parser $parser The MediaWiki parser object
	 * @param PPFrame $frame Parent frame, provides context of the tage placement
	 * @return array
	 */
	public static function newGame( $input, array $args, Parser $parser, PPFrame $frame ): array {
		try {
			self::assertValidPGN( $input );

			$out = $parser->getOutput();
			// Get number of games so div id property is unique
			$gameNum = $out->getExtensionData( 'ChessViewerNumGames' ) ?? 0;
			$gameNum++;

			$board = self::createBoard( $input, $gameNum );

			// Set after the parsing, etc. in case there is an error
			// Set variable so resource loader knows whether to send javascript
			$out->setExtensionData( 'ChessViewerTrigger', 'true' );
			// Increment number of games
			$out->setExtensionData( 'ChessViewerNumGames', $gameNum );

			return [ $board, "markerType" => "nowiki" ];
		} catch ( Exception $e ) {
			wfDebugLog(
				'ChessBrowser',
				'Unable to create a game: ' . $e
			);
			$parser->addTrackingCategory( 'chessbrowser-invalid-category' );
			$message = wfMessage( 'chessbrowser-invalid-message' )->escaped();
			return [ $message ];
		}
	}

	/**
	 * Check if tag cotains valid input format PGN
	 *
	 * The regular expression checks whether the string fits the general structure
	 *   of a PGN file and is divided into three main parts
	 *
	 * (?:
	 *    \[
	 *      \s*\S+\s*
	 *      "[^"\n]*"\s*
	 *    \]\s*
	 *  )*
	 * |    This non-capturing group checks for valid tag pairs by looking for patterns
	 * |      within square brackets. The content is pretty unimportant and only
	 * |      checked on a superficial level.
	 * |- \S
	 * |    checks that there is some non-whitespace as the first element of the tag
	 * |- "[^"\n]*"
	 * |    checks that the second element is a quote delimited string. It
	 * |      will match an empty string or a string of any length as long as it does
	 * |      not contain a newline or double quote marks.
	 * |- \s*
	 * |    Any amount of whitespace may separate items, and the validation is very
	 * |      permissive when it comes to whitespace.
	 * |- (?: ... )*
	 * |    The PGN will pass validation even if tag pairs are omitted.
	 *
	 * (?:
	 *    (?:\{.*?\})?
	 *    (?:\(.*?\))?
	 *    \d*\.*\s*
	 *    [a-hxOBNRKQ1-8=+#\-]+\s*
	 *  )+
	 * |    This non-capturing group checks that the rest of the PGN follows the
	 * |      general format of "1. d4 d5 2. c4 ...". Following the PGN input
	 * |      standard, it is highly permissive of variation.
	 * |
	 * |- (?:\{.*?\})?
	 * |    Check for comments which are delimited by curly braces and can appear
	 * |      just about anywhere in the movetext. The content of the comment is
	 * |      immaterial and can be pretty much anything.
	 * |- (?:\(.*?\))?
	 * |    Check for move variations which are delimited by parentheses and can
	 * |      appear just about anywhere in the mvoetext. The content can be pretty
	 * |      much anything and will frequently include recursion.
	 * | These two non-capturing groups precede actual checks of the move text so that
	 * |    comments and variations can match anywhere, including before the first
	 * |    move.
	 * |
	 * |- \d*\.*
	 * |    Moves may be preceded by a digit and this digit may be followed by any
	 * |      number of periods, including none at all. The PGN import format allows
	 * |      this to be omitted completely. Usually the move number only precedes
	 * |      white's move, but the import format allows them to precede black as well.
	 * |- [a-hxOBNRKQ1-8=+#\-]+
	 * |    A move token can be as simple as "d4" or as complex as "dxe8=R#". Despite this
	 * |      variation in length, a valid token is composed of a finite symbol set defined
	 * |      by this group. Valid symbols are file letters (a-h), rank numbers (1-8), the
	 * |      capture symbol (x), the piece symbols (BNRKQ), the promotion symbol (=), the
	 * |      check symbol (+), the checkmate symbol (#), and the components of the castling
	 * |      notation (O-O).
	 * |- \s*
	 * |    Any amount of whitespace may separate items, including none at all.
	 * |- (?: ... )+
	 * |    The validator requires at least one move to be present. This differs from the
	 * |      PGN format which defines the empty string as a valid PGN. Still, this
	 * |      validation is extremely permissive with a string as simple as "e4" passing.
	 *
	 * [0-2\/-]{0,7}
	 * |  Check terminal notation. Typical values are 1-0 (white wins), 0-1 (black wins)
	 * |    and 1/2-1/2 (draw), but it may be omited. This gives us a limited character set
	 * |    and limited range of possible lengths. Any 0 to 7 character string made up
	 * |    of the given character set will match,
	 * @param string $input
	 * @throws ChessBrowserException if invalid
	 */
	private static function assertValidPGN( string $input ) {
		// phpcs:ignore Generic.Files.LineLength.TooLong
		$likeValidPGN = '/^\s*(?:\[\s*\S+\s*"[^"\n]*"\s*\]\s*)*\s*(?:(?:\{.*?\})?(?:\(.*?\))?\d*\.*\s*[a-hxOBNRKQ1-8=+#\-]+\s*)+\s*[0-2\/-]{0,7}\s*$/';
		$couldBeValid = preg_match( $likeValidPGN, $input );
		if ( $couldBeValid !== 1 ) {
			throw new ChessBrowserException( 'Invalid PGN' );
		}
	}

	/**
	 * Handle creating the board to show
	 *
	 * @param string $input
	 * @param int $gameNum
	 * @return string
	 * @throws ChessBrowserException
	 */
	private static function createBoard( string $input, int $gameNum ): string {
		// Initialize parsers
		$chessParser = new ChessParser( $input );
		$chessObject = $chessParser->createOutputJson();
		$annotationObject = $chessObject['variations'];
		unset( $chessObject['variations'] );
		if ( !( $chessObject && $chessObject['boards'] && $chessObject['boards'][0] ) ) {
			throw new ChessBrowserException( 'No board available' );
		}
		// Set up template arguments
		$templateParser = new TemplateParser( __DIR__ . '/../templates' );
		$templateParser->enableRecursivePartials( true );
		$templateArgs = [
			'data-chess' => json_encode( $chessObject ),
			'data-chess-annotations' => json_encode( $annotationObject ),
			'div-number' => $gameNum,
			// TODO One day these dimensions will be determined by the user
			'board-height' => '248px',
			'board-width' => '248px',
			'label-height' => '208px',
			'label-width' => '208px',
			'move-set' => self::getMoveSet( $chessObject, $annotationObject ),
			'piece-set' => self::generatePieces( $chessObject['boards'][0] )
		];
		$localizedLabels = self::getLocalizedLabels();
		$metadata = self::getMetadata( $chessObject['metadata'] );
		$templateArgs = array_merge( $templateArgs, $localizedLabels, $metadata );
		return $templateParser->processTemplate(
			'ChessGame',
			$templateArgs
		);
	}

	/**
	 * Create array of mustache arguments for chess-piece.mustache from a given FEN string
	 * @since 0.2.0
	 * @param string $fen
	 * @return array
	 */
	public static function generatePieces( $fen ): array {
		$pieceArray = [];
		$rankIndex = 0;
		$fileIndex = 0;
		$fenArray = str_split( $fen );
		foreach ( $fenArray as $fenChar ) {
			if ( is_numeric( $fenChar ) ) {
				$fileIndex += $fenChar;
			} elseif ( $fenChar === '/' ) {
				$rankIndex++;
				$fileIndex = 0;
			} else {
				if ( $fileIndex > 7 ) {
					continue;
				}
				$pieceArray[] = self::createPiece( $fenChar, $rankIndex, $fileIndex );
				$fileIndex++;
			}
		}
		return $pieceArray;
	}

	/**
	 * Retrieve the interface text for the correct locale
	 * @since 0.2.0
	 * @return array
	 */
	public static function getLocalizedLabels(): array {
		return [
			'expand-button' => wfMessage( 'chessbrowser-expand-button' )->text(),
			'game-detail' => wfMessage( 'chessbrowser-game-detail' )->text(),
			'event-label' => wfMessage( 'chessbrowser-event-label' )->text(),
			'site-label' => wfMessage( 'chessbrowser-site-label' )->text(),
			'date-label' => wfMessage( 'chessbrowser-date-label' )->text(),
			'round-label' => wfMessage( 'chessbrowser-round-label' )->text(),
			'white-label' => wfMessage( 'chessbrowser-white-label' )->text(),
			'black-label' => wfMessage( 'chessbrowser-black-label' )->text(),
			'result-label' => wfMessage( 'chessbrowser-result-label' )->text(),
			'rank-1' => wfMessage( 'chessbrowser-first-rank' )->text(),
			'rank-2' => wfMessage( 'chessbrowser-second-rank' )->text(),
			'rank-3' => wfMessage( 'chessbrowser-third-rank' )->text(),
			'rank-4' => wfMessage( 'chessbrowser-fourth-rank' )->text(),
			'rank-5' => wfMessage( 'chessbrowser-fifth-rank' )->text(),
			'rank-6' => wfMessage( 'chessbrowser-sixth-rank' )->text(),
			'rank-7' => wfMessage( 'chessbrowser-seventh-rank' )->text(),
			'rank-8' => wfMessage( 'chessbrowser-eighth-rank' )->text(),
			'a' => wfMessage( 'chessbrowser-a-file' )->text(),
			'b' => wfMessage( 'chessbrowser-b-file' )->text(),
			'c' => wfMessage( 'chessbrowser-c-file' )->text(),
			'd' => wfMessage( 'chessbrowser-d-file' )->text(),
			'e' => wfMessage( 'chessbrowser-e-file' )->text(),
			'f' => wfMessage( 'chessbrowser-f-file' )->text(),
			'g' => wfMessage( 'chessbrowser-g-file' )->text(),
			'h' => wfMessage( 'chessbrowser-h-file' )->text(),
			'no-javascript' => wfMessage( 'chessbrowser-no-javascript' )->text()
		];
	}

	/**
	 * Create array of mustache arguments for move-span.mustache from a given
	 * array of ply tokens.
	 * @since 0.2.0
	 * @param array $gameObject Game representation loaded into data-chess
	 *   and output from ChessParser::createOutputJson
	 * @param array $annotationObject representation loaded into data-chess-annotations
	 * @return array
	 */
	public static function getMoveSet( $gameObject, $annotationObject ): array {
		$tokens = $gameObject['tokens'];
		$plys = $gameObject['plys'];
		$moveSet = [];
		$variationIndices = array_map(
			static function ( $x ) {
				return $x[0];
			},
			$annotationObject
		);
		foreach ( $tokens as $i => $token ) {
			$span = [
				'step-link' => false,
				'annotations' => []
			];
			if ( in_array( $i, $variationIndices ) ) {
				$span['variations'] = [];
				$j = array_search( $i, $variationIndices );
				$variationList = $annotationObject[$j][1];
				foreach ( $variationList as $variation ) {
					$span['variations'][] = [
						'debug' => json_encode( $variation ),
						'variation-moves' => self::getVariationSet( $variation, $i )
					];
					// $span['variation-set'][] = self::getVariationSet( $variation, [], $i );
				}
			}
			$ply = $plys[$i];
			$comment = $ply[2][2];
			if ( $comment !== null ) {
				$span['annotations'][] = [ 'comment' => $comment ];
			}
			if ( $i % 2 === 0 ) {
				$moveNumber = ( $i / 2 ) + 1;
				$span['step-link'] = true;
				$span['move-number'] = $moveNumber;
			}
			$plyNumber = $i + 1;
			$span['move-token'] = $token;
			$span['move-ply'] = $plyNumber;
			$moveSet[] = $span;
		}

		return $moveSet;
	}

	/**
	 * Create template parameters for move variation strings
	 * @param array $variation Object listing tokens, boards, and plys for
	 *   the variation moves.
	 * @param int $index The ply of the parent move
	 * @return array
	 */
	public static function getVariationSet( $variation, $index ) {
		$tokens = $variation['tokens'];
		$plys = $variation['plys'];
		$spanList = [];
		foreach ( $tokens as $i => $token ) {
			$span = [
				'step-link' => false,
				'annotations' => []
			];
			$ply = $plys[$i];
			$comment = $ply[2][2];
			if ( $comment !== null ) {
				$span['annotations'][] = [ 'comment' => $comment ];
			}
			if ( ( $index + $i ) % 2 === 0 ) {
				$moveNumber = ( ( $index + $i ) / 2 ) + 1;
				$span['step-link'] = true;
				$span['move-number'] = $moveNumber;
			}
			$span['variation-ply'] = $i;
			$span['variation-token'] = $token;
			$spanList[] = $span;
		}
		return $spanList;
	}

	/**
	 * Create array of mustache arguments for ChessBoard.mustache from a given
	 * associative array of tag pairs
	 * @since 0.2.0
	 * @param array $tagPairs
	 * @return array
	 */
	public static function getMetadata( $tagPairs ): array {
		// TODO localize the defaults
		$metadata = [
			'event' => 'Unknown event',
			'site' => 'Unknown site',
			'date' => 'Unknown date',
			'round' => 'Unkown round',
			'white' => 'Unknown white',
			'black' => 'Unknown black',
			'result' => 'Unknown result',
			'other-metadata' => []
		];

		foreach ( $tagPairs as $key => $value ) {
			if ( array_key_exists( $key, $metadata ) ) {
				$metadata[$key] = $value;
				continue;
			}
			$metadata['other-metadata'][] = [
				'label' => $key,
				'value' => $value
			];
		}
		return $metadata;
	}

	/**
	 * Create an array of arguments for chess-piece.mustache for a single piece
	 * at a given location on the board
	 * @since 0.2.0
	 * @param string $symbol The FEN symbol for the piece
	 * @param string|int $rank Preserves input type on output
	 * @param string|int $file Preserves input type on output
	 * @return array
	 */
	public static function createPiece( $symbol, $rank, $file ): array {
		if ( $rank > 7 || $file > 7 || $rank < 0 || $file < 0 ) {
			throw new ChessBrowserException( "Impossible rank ($rank) or file ($file)" );
		}

		$validTypes = [ 'b', 'k', 'n', 'p', 'q', 'r' ];
		$type = strtolower( $symbol );

		if ( !in_array( $type, $validTypes ) ) {
			throw new ChessBrowserException( "Invalid piece type $type" );
		}

		$color = ( $type === $symbol ? 'd' : 'l' );

		return [
			'piece-type' => $type,
			'piece-color' => $color,
			'piece-rank' => $rank,
			'piece-file' => $file
		];
	}
}

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
use MediaWiki\Extension\ChessBrowser\PgnParser\FenParser0x88;
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

			$board = self::createBoard( $input, $gameNum, $args );

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
	 * The input PGN is checked by various regular expressions to get a rough
	 *  sense of whether the PGN is valid before devoting the parsing resources.
	 *
	 * The validation is a series of filters which remove sections that the
	 *  parser can understand. The PGN is valid if the entire input gets
	 *  filtered and is invalid if there is some part of the string that
	 *  remains after the filtering.
	 *
	 * @param string $input
	 * @throws ChessBrowserException if invalid
	 */
	private static function assertValidPGN( string $input ) {
		// Validate escaped lines (PGN Standard 6) identified by a % sign at
		//  start of line and ignoring all following text on the line. A %
		//  anywhere else but the start of line has no meaning.
		$escapedLine = '/^%.*?\n/m';
		$input = preg_replace( $escapedLine, "", $input );

		// Validate tag pairs (PGN Standard 8.1). Composed of four tokens:
		//   left bracket, ie: [
		//   symbol token, a sequence comprising only alphanumeric chars and underscore
		//   string token, a symbol token delimited by double quotes
		//   right bracket, ie: ]
		// Input format allows any ammount of whitespace to separate these tokens.
		$tagPairs = '/\[\s*[a-zA-Z0-9_]+\s*".*"\s*\]/';
		$input = preg_replace( $tagPairs, "", $input );

		// Validate comments (PGN Standard 5) and move variations (idem 8.2.5).
		//   Inline comments are delimited by braces
		//   Rest-of-line comments are delimited by ; and a newline
		//   Variations are delimited by parentheses
		$annotations = '/({.*?}|\(.*?\)|;.*?\n)/';
		$input = preg_replace( $annotations, "", $input );

		// Validate Numeric Annotation Glyphs (NAGs; PGN Standard 10). Composed of:
		//   a leading dollar sign ($)
		//   a non-negative decimal integer between 0 and 255
		// We do not check that the NAG is proper, only that it has the same number of
		//  digits as a proper NAG, i.e., not greater than a thousand.
		$NAGs = '/\$\d{1,3}/';
		$input = preg_replace( $NAGs, "", $input );

		// Validate game termination markers (PGN Standard 8.2.6). One of four values:
		//   1-0
		//   0-1
		//   1/2-1/2
		//   *
		// We allow a great deal of leeway in the separator accepting any non-alphanumeric
		//  value (\W). This is to accomodate en- or em-dashes, periods, spaces, etc
		//  without a complex pre-processing overhead.
		// The game will not validate if there is more than one termination marker.
		$termCount = 0;
		$limit = -1;
		$termination = '/(1\W0|0\W1|1\/2\W1\/2|\*)/';
		$input = preg_replace( $termination, "", $input, $limit, $termCount );
		if ( $termCount > 1 ) {
			throw new ChessBrowserException( 'Too many termination tokens.' );
		}

		// Validate move number indicators (PGN Standard 8.2.2) if they exist. Move
		// numbers are composed of:
		//   a leading non-alphanumeric character (\W) which terminates the previous token
		//   one or more digits (\d+)
		//   any amount of whitespace (\s*)
		//   any number of periods (\.*)
		// Input PGN format is extremely forgiving with regards to the last two criteria
		$moveNumbers = '/\W\d+\s*\.*/';
		// Make sure SAN starts with a space so that first move number is matched
		$input = " " . $input;
		$input = preg_replace( $moveNumbers, "", $input );

		// Validate standard algebraic notation (SAN; PGN Standard 8.2.3). As these denote
		// moves on a chess board, their composition is restricted to:
		//   files (a-h)
		//   ranks (1-8)
		//   the capture indicator (x)
		//   piece indicators (BNRKQ)
		//   promotion indictor (=)
		//   check (+) and checkmate(#)
		//   components of a castling marker (O-O and O-O-O)
		// The minimum length of a SAN token is 2 characters, denoting a pawn move.
		// The theoretical maximum is 7, but we do not check the upper bound.
		$SAN = '/[a-hxOBNRKQ1-8=+#\-]{2,}/';
		$input = preg_replace( $SAN, "", $input );

		// Moves may be annotated by some number of glyphs. Check all known by NagTable.
		$glyphs = array_values( NagTable::MAP );
		$input = str_replace( $glyphs, "", $input );

		// If the PGN is valid, we should have either an empty string or a string containing
		// only white space. If, after removing the white space, we have anything left in
		// the string, we know that the PGN is not valid.
		$whitespace = '/\s+/';
		$input = preg_replace( $whitespace, "", $input );

		if ( strlen( $input ) > 0 ) {
			throw new ChessBrowserException( 'Invalid PGN.' );
		}
	}

	/**
	 * Handle creating the board to show
	 *
	 * @param string $input
	 * @param int $gameNum
	 * @param array $args The XML arguments from MediaWiki
	 * @return string
	 * @throws ChessBrowserException
	 */
	private static function createBoard( string $input, int $gameNum, array $args ): string {
		$attr = self::parseArguments( $args );
		$swap = $attr['side'] === 'black';
		$initialPosition = $attr['ply'];

		// Initialize parsers
		$chessParser = new ChessParser( $input );

		$chessObject = $chessParser->createOutputJson();
		$annotationObject = $chessObject['variations'];
		unset( $chessObject['variations'] );
		$chessObject['init'] = $initialPosition;
		if ( !$chessObject['boards'][0] ) {
			throw new ChessBrowserException( 'No board available' );
		}

		// Set up template arguments
		$templateParser = new TemplateParser( __DIR__ . '/../templates' );
		$templateParser->enableRecursivePartials( true );
		$templateArgs = [
			'data-chess' => json_encode( $chessObject ),
			'data-chess-annotations' => json_encode( $annotationObject ),
			'div-number' => $gameNum,
			// The JS toggles a class to flip games, so unlike FEN we only need
			// to add the class in order to flip the board to black's view.
			// Include notransition class so that readers don't get FOUC and
			// watch all the boards spin on load
			'swap' => $swap ? ' pgn-flip notransition' : '',
			'move-set' => self::getMoveSet( $chessObject, $annotationObject ),
			'piece-set' => self::generatePieces( $chessObject['boards'][0] )
		];
		$localizedLabels = self::getLocalizedLabels();
		$metadata = self::getMetadata( $chessObject['metadata'] );
		$templateArgs = array_merge( $templateArgs, $localizedLabels, $metadata );
		$game = $templateParser->processTemplate(
			'ChessGame',
			$templateArgs
		);
		return $game;
	}

	/**
	 * @since 0.3.0
	 * @param string $input The wikitext placed between fen tags
	 * @param array $args Arguments passed as xml attributes
	 * @param Parser $parser The MediaWiki parser object
	 * @param PPFrame $frame Parent frame, provides context of the tage placement
	 * @return array
	 */
	public static function newPosition( string $input, array $args, Parser $parser, PPFrame $frame ): array {
		try {
			$attr = self::parseArguments( $args );
			$swap = $attr['side'] === 'black';

			$input = trim( $input );
			self::assertValidFEN( $input );
			$fenParser = new FenParser0x88( $input );
			$fenOut = $fenParser->getFen();

			// Set up template arguments
			$templateParser = new TemplateParser( __DIR__ . '/../templates' );
			$templateArgs = [
				'data-chess' => json_encode( $fenOut ),
				'piece-set' => self::generatePieces( $fenOut, $swap )
			];
			$localizedLegendLabels = self::getLocalizedLegendLabels( $swap );
			$templateArgs = array_merge( $templateArgs, $localizedLegendLabels );
			$board = $templateParser->processTemplate(
				'ChessBoard',
				$templateArgs
			);
			$parser->getOutput()->setExtensionData( 'ChessViewerFEN', 'true' );
			return [ $board , 'markerType' => 'nowiki' ];
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
	 * Check if tag contains valid input format FEN
	 *
	 * The input string is checked with a regex to make sure we only have the expected
	 * characters and spacing of FEN. We do not check if it is a valid game.
	 *
	 * @param string $fenInput
	 * @throws ChessBrowserException if invalid
	 */
	private static function assertValidFEN( string $fenInput ) {
		$fenRegex = '/^([prnbqk1-8]{1,8}\/){7}[prnbqk1-8]{1,8}\s[wb]\s([kq]{1,4}|-)\s([abcdefgh][36]|-)\s\d+\s\d+/i';
		$valid = preg_match( $fenRegex, $fenInput );
		if ( $valid !== 1 ) {
			throw new ChessBrowserException( 'Invalid FEN.' );
		}
	}

	/**
	 * Return associative array with argument defaults
	 *
	 * @param array $args Arguments passed as xml attributes through MediaWiki parser
	 * @return array
	 */
	public static function parseArguments( array $args ): array {
		$attr = [
			'side' => 'white',
			'ply' => 1
		];
		foreach ( $args as $name => $value ) {
			if ( array_key_exists( $name, $attr ) ) {
				$attr[$name] = $value;
			}
		}
		if ( !in_array( $attr['side'], [ 'white','black' ] ) ) {
			$attr['side'] = 'white';
		}
		// Ensure that an integer is always returned
		$attr['ply'] = (int)$attr['ply'];
		// Setting display to 0 results in the last ply being displayed, not
		// the initial board state which is counterintuitive. Rewrite 0 to 1
		// to prevent this from happening.
		// TODO: Add some kind of warning about this behavior or fix it in JS
		if ( $attr['ply'] === 0 ) {
			$attr['ply'] = 1;
		}
		return $attr;
	}

	/**
	 * Create array of mustache arguments for chess-piece.mustache from a given FEN string
	 * @since 0.2.0
	 * @param string $fen
	 * @param bool $swap Display from black's perspective if true
	 * @return array
	 */
	public static function generatePieces( $fen, $swap = false ): array {
		$pieceArray = [];
		$rankIndex = 7;
		$fileIndex = 0;
		$fenArray = str_split( $fen );
		foreach ( $fenArray as $fenChar ) {
			if ( is_numeric( $fenChar ) ) {
				$fileIndex += $fenChar;
			} elseif ( $fenChar === '/' ) {
				$rankIndex--;
				$fileIndex = 0;
			} else {
				if ( $fileIndex > 7 ) {
					continue;
				}
				if ( $swap ) {
					$piece = self::createPiece( $fenChar, 7 - $rankIndex, $fileIndex );
				} else {
					$piece = self::createPiece( $fenChar, $rankIndex, $fileIndex );
				}

				$pieceArray[] = $piece;
				$fileIndex++;
			}
		}
		return $pieceArray;
	}

	/**
	 * Retrieve the interface text for the correct locale
	 * @since 0.2.0
	 * @param bool $swap
	 * @return array
	 */
	public static function getLocalizedLabels( bool $swap = false ): array {
		$legend = self::getLocalizedLegendLabels( $swap );
		$other = [
			'expand-button' => wfMessage( 'chessbrowser-expand-button' )->text(),
			'game-detail' => wfMessage( 'chessbrowser-game-detail' )->text(),
			'event-label' => wfMessage( 'chessbrowser-event-label' )->text(),
			'site-label' => wfMessage( 'chessbrowser-site-label' )->text(),
			'date-label' => wfMessage( 'chessbrowser-date-label' )->text(),
			'round-label' => wfMessage( 'chessbrowser-round-label' )->text(),
			'white-label' => wfMessage( 'chessbrowser-white-label' )->text(),
			'black-label' => wfMessage( 'chessbrowser-black-label' )->text(),
			'result-label' => wfMessage( 'chessbrowser-result-label' )->text(),
			'notations-label' => wfMessage( 'chessbrowser-notations-label' )->text(),
			'no-javascript' => wfMessage( 'chessbrowser-no-javascript' )->text()
		];
		$allLabels = array_merge( $legend, $other );
		return $allLabels;
	}

	/**
	 * Retrieve the interface text for the correct locale for the legend only
	 * @since 0.3.0
	 * @param bool $swap
	 * @return array
	 */
	private static function getLocalizedLegendLabels( bool $swap ): array {
		if ( $swap ) {
			$ranks = [
				'rank-8' => wfMessage( 'chessbrowser-first-rank' )->text(),
				'rank-7' => wfMessage( 'chessbrowser-second-rank' )->text(),
				'rank-6' => wfMessage( 'chessbrowser-third-rank' )->text(),
				'rank-5' => wfMessage( 'chessbrowser-fourth-rank' )->text(),
				'rank-4' => wfMessage( 'chessbrowser-fifth-rank' )->text(),
				'rank-3' => wfMessage( 'chessbrowser-sixth-rank' )->text(),
				'rank-2' => wfMessage( 'chessbrowser-seventh-rank' )->text(),
				'rank-1' => wfMessage( 'chessbrowser-eighth-rank' )->text(),
			];
		} else {
			$ranks = [
				'rank-1' => wfMessage( 'chessbrowser-first-rank' )->text(),
				'rank-2' => wfMessage( 'chessbrowser-second-rank' )->text(),
				'rank-3' => wfMessage( 'chessbrowser-third-rank' )->text(),
				'rank-4' => wfMessage( 'chessbrowser-fourth-rank' )->text(),
				'rank-5' => wfMessage( 'chessbrowser-fifth-rank' )->text(),
				'rank-6' => wfMessage( 'chessbrowser-sixth-rank' )->text(),
				'rank-7' => wfMessage( 'chessbrowser-seventh-rank' )->text(),
				'rank-8' => wfMessage( 'chessbrowser-eighth-rank' )->text(),
			];
		}
		$files = [
			'a' => wfMessage( 'chessbrowser-a-file' )->text(),
			'b' => wfMessage( 'chessbrowser-b-file' )->text(),
			'c' => wfMessage( 'chessbrowser-c-file' )->text(),
			'd' => wfMessage( 'chessbrowser-d-file' )->text(),
			'e' => wfMessage( 'chessbrowser-e-file' )->text(),
			'f' => wfMessage( 'chessbrowser-f-file' )->text(),
			'g' => wfMessage( 'chessbrowser-g-file' )->text(),
			'h' => wfMessage( 'chessbrowser-h-file' )->text(),
		];

		return array_merge( $ranks, $files );
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

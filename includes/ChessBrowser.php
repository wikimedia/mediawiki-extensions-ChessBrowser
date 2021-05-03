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
	public static function newGame( $input, array $args, Parser $parser, PPFrame $frame ) : array {
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
	 * @param string $input
	 * @throws ChessBrowserException if invalid
	 */
	private static function assertValidPGN( string $input ) {
		// phpcs:ignore Generic.Files.LineLength.TooLong
		$likeValidPGN = '/^\s*(?:\[\s*\S+\s*"[^"\n]*"\s*\]\s*)*\s*(?:\d*\.*\s*[a-hxOBNRKQ1-8=+#\-]+\s*[a-hxOBNRKQ01-8=+#\-]+\s*)+\s*$/';
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
	private static function createBoard( string $input, int $gameNum ) : string {
		// Initialize parsers
		$chessParser = new ChessParser( $input );
		$chessObject = $chessParser->createOutputJson();
		if ( !( $chessObject && $chessObject['boards'] && $chessObject['boards'][0] ) ) {
			throw new ChessBrowserException( 'No board available' );
		}
		// Set up template arguments
		$templateParser = new TemplateParser( __DIR__ . '/../templates' );
		$templateArgs = [
			'data-chess' => json_encode( $chessObject ),
			'div-number' => $gameNum,
			// TODO One day these dimensions will be determined by the user
			'board-height' => '248px',
			'board-width' => '248px',
			'label-height' => '208px',
			'label-width' => '208px',
			'move-set' => self::getMoveSet( $chessObject['tokens'] ),
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
	public static function generatePieces( $fen ) : array {
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
	public static function getLocalizedLabels() : array {
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
			'beginning' => wfMessage( 'chessbrowser-beginning-of-game' )->text(),
			'previous' => wfMessage( 'chessbrowser-previous-move' )->text(),
			'next' => wfMessage( 'chessbrowser-next-move' )->text(),
			'final' => wfMessage( 'chessbrowser-end-of-game' )->text(),
			'flip' => wfMessage( 'chessbrowser-flip-board' )->text(),
			'no-javascript' => wfMessage( 'chessbrowser-no-javascript' )->text()
		];
	}

	/**
	 * Create array of mustache arguments for move-span.mustache from a given
	 * array of ply tokens.
	 * @since 0.2.0
	 * @param array $tokens List of moves in Standard Algebraic Notation
	 * @return array
	 */
	public static function getMoveSet( $tokens ) : array {
		$moveSet = [];
		foreach ( $tokens as $i => $token ) {
			$span = [
				'step-link' => false
			];
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
	 * Create array of mustache arguments for ChessBoard.mustache from a given
	 * associative array of tag pairs
	 * @since 0.2.0
	 * @param array $tagPairs
	 * @return array
	 */
	public static function getMetadata( $tagPairs ) : array {
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
	 * @param string|int $rank
	 * @param string|int $file
	 * @return array
	 */
	public static function createPiece( $symbol, $rank, $file ) : array {
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

<?php

/**
 * \file
 * \brief Text parsing and processing utilities (facade).
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Shared\Infrastructure\Database;

use Lwt\Shared\Infrastructure\Exception\DatabaseException;

/**
 * Text parsing and processing utilities (facade).
 *
 * Delegates tokenization to JapaneseTextParser / StandardTextParser and
 * persistence to TokenPersistence. Parsing happens entirely in PHP — there are
 * no scratch tables involved.
 *
 * @since 3.0.0
 */
class TextParsing
{
    /**
     * Split text into sentences without database operations.
     *
     * @param string $text Text to parse
     * @param int    $lid  Language ID
     *
     * @return string[] Array of sentences
     *
     * @psalm-return non-empty-list<string>
     */
    public static function splitIntoSentences(string $text, int $lid): array
    {
        $pre = self::preprocess($text, $lid);
        if ($pre === null) {
            return [''];
        }
        [$ptext, $isMecab] = $pre;
        if ($isMecab) {
            return JapaneseTextParser::splitJapaneseSentences($ptext);
        }
        return StandardTextParser::splitSentences($ptext, $lid);
    }

    /**
     * Parse text and display preview HTML for validation.
     *
     * Outputs HTML directly to show parsed sentences and word statistics.
     *
     * @param string $text Text to parse
     * @param int    $lid  Language ID
     *
     * @return void
     */
    public static function parseAndDisplayPreview(string $text, int $lid): void
    {
        $record = QueryBuilder::table('languages')
            ->select(['LgRightToLeft'])
            ->where('LgID', '=', $lid)
            ->firstPrepared();

        if ($record === null) {
            throw DatabaseException::recordNotFound('languages', 'LgID', $lid);
        }
        $rtlScript = (bool)$record['LgRightToLeft'];

        $pre = self::preprocess($text, $lid);
        if ($pre === null) {
            return;
        }
        [$ptext, $isMecab] = $pre;

        // Preview HTML is shown before word splitting.
        if ($isMecab) {
            JapaneseTextParser::displayJapanesePreview($ptext);
            $tokens = JapaneseTextParser::tokenize($ptext);
        } else {
            StandardTextParser::echoPreview($ptext, $lid);
            $tokens = StandardTextParser::tokenize($ptext, $lid);
        }

        TokenPersistence::echoCheckValid($tokens, $lid);
        TokenPersistence::echoStatistics($tokens, $lid, $rtlScript);
    }

    /**
     * Parse text and save to database.
     *
     * @param string $text   Text to parse
     * @param int    $lid    Language ID
     * @param int    $textId Text ID (must be positive)
     *
     * @return void
     *
     * @throws \InvalidArgumentException If textId is not positive
     */
    public static function parseAndSave(string $text, int $lid, int $textId): void
    {
        if ($textId <= 0) {
            throw new \InvalidArgumentException(
                "Text ID must be positive, got: $textId"
            );
        }

        $record = QueryBuilder::table('languages')
            ->select(['LgID'])
            ->where('LgID', '=', $lid)
            ->firstPrepared();

        if ($record === null) {
            throw DatabaseException::recordNotFound('languages', 'LgID', $lid);
        }

        $tokens = self::tokenize($text, $lid);
        TokenPersistence::save($tokens, $lid, $textId);
    }

    /**
     * Check/preview text and return parsing statistics without saving.
     *
     * Does not output any HTML or save to database.
     *
     * @param string $text Text to parse
     * @param int    $lid  Language ID
     *
     * @return array{sentences: int, words: int, unknownPercent: float, preview: string}
     */
    public static function checkText(string $text, int $lid): array
    {
        $tokens = self::tokenize($text, $lid);
        return TokenPersistence::stats($tokens, $lid);
    }

    /**
     * Tokenize a text into ParsedToken objects (no database writes).
     *
     * @param string $text Text to parse
     * @param int    $lid  Language ID
     *
     * @return ParsedToken[]
     */
    private static function tokenize(string $text, int $lid): array
    {
        $pre = self::preprocess($text, $lid);
        if ($pre === null) {
            return [];
        }
        [$ptext, $isMecab] = $pre;
        return $isMecab
            ? JapaneseTextParser::tokenize($ptext)
            : StandardTextParser::tokenize($ptext, $lid);
    }

    /**
     * Apply the language's text preprocessing (escaping + character
     * substitutions) and report whether it uses the MeCab parser.
     *
     * @param string $text Raw text
     * @param int    $lid  Language ID
     *
     * @return array{0: string, 1: bool}|null [preprocessed text, isMecab] or null if language missing
     */
    private static function preprocess(string $text, int $lid): ?array
    {
        $record = QueryBuilder::table('languages')
            ->where('LgID', '=', $lid)
            ->firstPrepared();

        if ($record === null) {
            return null;
        }

        $termchar = (string)$record['LgRegexpWordCharacters'];
        $replace = explode("|", (string) $record['LgCharacterSubstitutions']);
        $text = Escaping::prepareTextdata($text);

        // because of sentence special characters
        $text = str_replace(array('}', '{'), array(']', '['), $text);
        foreach ($replace as $value) {
            $fromto = explode("=", trim($value));
            if (count($fromto) >= 2) {
                $text = str_replace(trim($fromto[0]), trim($fromto[1]), $text);
            }
        }

        return [$text, 'MECAB' === strtoupper(trim($termchar))];
    }
}

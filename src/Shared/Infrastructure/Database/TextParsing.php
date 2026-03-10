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
 * Delegates to JapaneseTextParser, StandardTextParser, and TextParsingPersistence.
 *
 * @since 3.0.0
 */
class TextParsing
{
    /**
     * Split text into sentences without database operations.
     *
     * Use this method when you only need to split text into sentences
     * without saving to the database (e.g., for long text splitting).
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
        $result = self::prepare($text, -2, $lid);
        return $result ?? [''];
    }

    /**
     * Parse text and display preview HTML for validation.
     *
     * Use this method for the text checking UI. Outputs HTML directly
     * to show parsed sentences and word statistics.
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

        // Parse text and display preview HTML (id=-1 triggers preview display in prepare)
        self::prepare($text, -1, $lid);

        // Display sentences and word statistics
        TextParsingPersistence::checkValid($lid);

        // Get multi-word expressions
        $wl = TextParsingPersistence::getMultiWordLengths($lid);

        // Process multi-word expressions if any exist
        if (!empty($wl)) {
            TextParsingPersistence::checkExpressions($wl);
        }

        // Display statistics
        TextParsingPersistence::displayStatistics($lid, $rtlScript, !empty($wl));

        // Clean up
        QueryBuilder::table('temp_word_occurrences')->truncate();
    }

    /**
     * Parse text and save to database.
     *
     * Use this method when creating or updating texts. Parses the text
     * and inserts sentences and text items into the database.
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

        // Parse text into temp_word_occurrences (id>0 uses MAX(SeID)+1 for sentence IDs)
        self::prepare($text, $textId, $lid);

        // Get multi-word expressions
        $wl = TextParsingPersistence::getMultiWordLengths($lid);

        // Process multi-word expressions if any exist
        if (!empty($wl)) {
            TextParsingPersistence::checkExpressions($wl);
        }

        // Register sentences and text items in database
        TextParsingPersistence::registerSentencesTextItems($textId, $lid, !empty($wl));

        // Clean up
        QueryBuilder::table('temp_word_occurrences')->truncate();
    }

    /**
     * Check/preview text and return parsing statistics without saving.
     *
     * Use this method to get text statistics for preview purposes.
     * Does not output any HTML or save to database.
     *
     * @param string $text Text to parse
     * @param int    $lid  Language ID
     *
     * @return array{sentences: int, words: int, unknownPercent: float, preview: string}
     */
    public static function checkText(string $text, int $lid): array
    {
        $settings = StandardTextParser::getLanguageSettings($lid);

        if ($settings === null) {
            return [
                'sentences' => 0,
                'words' => 0,
                'unknownPercent' => 100.0,
                'preview' => ''
            ];
        }

        // Prepare text into temp_word_occurrences
        self::prepare($text, -1, $lid);

        // Get sentence count
        $sentences = Connection::fetchAll(
            'SELECT GROUP_CONCAT(TiText ORDER BY TiOrder SEPARATOR "")
            AS Sent FROM temp_word_occurrences GROUP BY TiSeID'
        );
        $sentenceCount = count($sentences);

        // Build preview from first few sentences
        $preview = '';
        $previewSentences = array_slice($sentences, 0, 3);
        foreach ($previewSentences as $record) {
            if ($preview !== '') {
                $preview .= ' ';
            }
            $preview .= (string) ($record['Sent'] ?? '');
        }
        if (count($sentences) > 3) {
            $preview .= '...';
        }

        // Get word statistics
        $bindings = [$lid];
        $rows = Connection::preparedFetchAll(
            "SELECT COUNT(`TiOrder`) AS cnt, IF(0=TiWordCount,0,1) AS len,
            LOWER(TiText) AS word, WoTranslation
            FROM temp_word_occurrences
            LEFT JOIN words ON LOWER(TiText)=WoTextLC AND WoLgID=?"
            . UserScopedQuery::forTablePrepared('words', $bindings, '')
            . " GROUP BY LOWER(TiText)",
            $bindings
        );

        $totalWords = 0;
        $unknownWords = 0;

        foreach ($rows as $record) {
            if ($record['len'] == 1) {
                $totalWords += (int) $record['cnt'];
                // Word is unknown if it has no translation
                if (empty($record['WoTranslation'])) {
                    $unknownWords += (int) $record['cnt'];
                }
            }
        }

        $unknownPercent = $totalWords > 0
            ? round(($unknownWords / $totalWords) * 100, 1)
            : 100.0;

        // Clean up temp_word_occurrences
        QueryBuilder::table('temp_word_occurrences')->truncate();

        return [
            'sentences' => $sentenceCount,
            'words' => $totalWords,
            'unknownPercent' => $unknownPercent,
            'preview' => $preview
        ];
    }

    /**
     * Pre-parse the input text before a definitive parsing by a specialized parser.
     *
     * @param string $text Text to parse
     * @param int    $id   Text ID
     * @param int    $lid  Language ID
     *
     * @return null|string[] If $id = -2 return a splitted version of the text
     *
     * @psalm-return non-empty-list<string>|null
     *
     * @internal Use splitIntoSentences(), parseAndDisplayPreview(), or parseAndSave() instead.
     */
    private static function prepare(string $text, int $id, int $lid): ?array
    {
        $record = QueryBuilder::table('languages')
            ->where('LgID', '=', $lid)
            ->firstPrepared();

        // Return null if language not found
        if ($record === null) {
            return null;
        }

        $termchar = (string)$record['LgRegexpWordCharacters'];
        $replace = explode("|", (string) $record['LgCharacterSubstitutions']);
        $text = Escaping::prepareTextdata($text);
        QueryBuilder::table('temp_word_occurrences')->truncate();

        // because of sentence special characters
        $text = str_replace(array('}', '{'), array(']', '['), $text);
        foreach ($replace as $value) {
            $fromto = explode("=", trim($value));
            if (count($fromto) >= 2) {
                $text = str_replace(trim($fromto[0]), trim($fromto[1]), $text);
            }
        }

        if ('MECAB' == strtoupper(trim($termchar))) {
            return JapaneseTextParser::parseJapanese($text, $id);
        }
        return StandardTextParser::parseStandard($text, $id, $lid);
    }
}

<?php

/**
 * \file
 * \brief Standard (non-Japanese) text parsing with sentence splitting.
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

use Lwt\Shared\Infrastructure\Globals;
use Lwt\Shared\Infrastructure\Utilities\StringUtils;
use Lwt\Modules\Language\Application\Services\TextParsingService;

/**
 * Standard text parsing with sentence splitting.
 *
 * Handles language settings retrieval, text transformations,
 * splitting, previewing, and database insertion for non-Japanese text.
 *
 * @since 3.0.0
 */
class StandardTextParser
{
    /**
     * Build the Unicode quotation-mark character class fragment used in regex patterns.
     *
     * Contains: RIGHT DOUBLE QUOTE, close-paren, LEFT/RIGHT SINGLE QUOTE,
     * single angle quotes, LEFT DOUBLE QUOTE, DOUBLE LOW-9 QUOTE,
     * guillemets, CJK brackets.
     *
     * @return string Character class content (without surrounding brackets)
     */
    private static function quoteChars(): string
    {
        return "\u{201D})\u{2018}\u{2019}\u{2039}\u{203A}\u{201C}\u{201E}\u{00AB}\u{00BB}\u{300F}\u{300D}";
    }

    /**
     * Get language settings for parsing.
     *
     * @param int $lid Language ID
     *
     * @return array{
     *     removeSpaces: string,
     *     splitSentence: string,
     *     noSentenceEnd: string,
     *     termchar: string,
     *     rtlScript: mixed,
     *     splitEachChar: bool
     * }|null Language settings or null if not found
     */
    public static function getLanguageSettings(int $lid): ?array
    {
        $record = QueryBuilder::table('languages')
            ->where('LgID', '=', $lid)
            ->firstPrepared();

        if ($record === null) {
            return null;
        }

        return [
            'removeSpaces' => (string)$record['LgRemoveSpaces'],
            'splitSentence' => (string)$record['LgRegexpSplitSentences'],
            'noSentenceEnd' => (string)$record['LgExceptionsSplitSentences'],
            'termchar' => (string)$record['LgRegexpWordCharacters'],
            'rtlScript' => $record['LgRightToLeft'],
            'splitEachChar' => ((int)$record['LgSplitEachChar'] === 1),
        ];
    }

    /**
     * Apply initial text transformations (before display preview).
     *
     * @param string $text          Raw text
     * @param bool   $splitEachChar Whether to split each character
     *
     * @return string Text after initial transformations
     */
    public static function applyInitialTransformations(
        string $text,
        bool $splitEachChar
    ): string {
        // Split text paragraphs using " ¶" symbol
        $text = str_replace("\n", " \xC2\xB6", $text);
        $text = trim($text);
        if ($splitEachChar) {
            $text = preg_replace('/([^\s])/u', "$1\t", $text) ?? $text;
        }
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return $text;
    }

    /**
     * Apply word-splitting transformations (after display preview).
     *
     * @param string $text          Text after initial transformations
     * @param string $splitSentence Sentence split regex
     * @param string $noSentenceEnd Exception patterns
     * @param string $termchar      Word character regex
     *
     * @return string Preprocessed text ready for parsing
     *
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement, PossiblyNullArgument
     */
    public static function applyWordSplitting(
        string $text,
        string $splitSentence,
        string $noSentenceEnd,
        string $termchar
    ): string {
        $qc = self::quoteChars();
        // "\r" => Sentence delimiter, "\t" and "\n" => Word delimiter
        $service = new TextParsingService();
        /** @psalm-suppress TooFewArguments, MissingClosureReturnType, MissingClosureParamType, MixedArgument */
        $text = preg_replace_callback(
            "/(\S+)\s*((\.+)|([$splitSentence]))([]'`\"$qc]*)(?=(\s*)(\S+|$))/u",
            fn ($matches) => $service->findLatinSentenceEnd($matches, $noSentenceEnd),
            $text
        ) ?? $text;
        // Paragraph delimiters become a combination of ¶ and carriage return \r
        $text = str_replace(
            array("\xC2\xB6", " \xC2\xB6"),
            array("\xC2\xB6\r", "\r\xC2\xB6"),
            $text
        );
        $text = preg_replace(
            array(
                '/([^' . $termchar . '])/u',
                '/\n([' . $splitSentence . "]['`\"$qc]*)\n\t/u",
                '/([0-9])[\n]([:.,])[\n]([0-9])/u'
            ),
            array("\n$1\n", "$1", "$1$2$3"),
            $text
        ) ?? $text;

        return $text;
    }

    /**
     * Split standard text into sentences (split-only mode).
     *
     * @param string $text         Preprocessed text
     * @param string $removeSpaces Space removal setting
     *
     * @return string[] Array of sentences
     *
     * @psalm-return non-empty-list<string>
     */
    public static function splitStandardSentences(string $text, string $removeSpaces): array
    {
        $text = StringUtils::removeSpaces(
            str_replace(
                array("\r\r", "\t", "\n"),
                array("\r", "", ""),
                $text
            ),
            $removeSpaces
        );
        return explode("\r", $text);
    }

    /**
     * Display preview HTML for standard text.
     *
     * @param string $text      Preprocessed text (after initial transformations)
     * @param bool   $rtlScript Whether text is right-to-left
     *
     * @return void
     */
    public static function displayStandardPreview(string $text, bool $rtlScript): void
    {
        echo "<div id=\"check_text\" style=\"margin-right:50px;\">
        <h4>Text</h4>
        <p " . ($rtlScript ? 'dir="rtl"' : '') . ">" .
        str_replace("\xC2\xB6", "<br /><br />", \htmlspecialchars($text, ENT_QUOTES, 'UTF-8')) .
        "</p>";
    }

    /**
     * Parse standard text and insert into temp_word_occurrences.
     *
     * @param string $text         Preprocessed text
     * @param string $termchar     Word character regex
     * @param string $removeSpaces Space removal setting
     * @param bool   $useMaxSeID   Whether to query for max sentence ID
     *
     * @return void
     *
     * @psalm-suppress MixedArgument
     */
    public static function parseStandardToDatabase(
        string $text,
        string $termchar,
        string $removeSpaces,
        bool $useMaxSeID
    ): void {
        $qc = self::quoteChars();
        $replaced = preg_replace(
            array(
                "/\r(?=[]'`\"$qc ]*\r)/u",
                '/[\n]+\r/u',
                '/\r([^\n])/u',
                "/\n[.](?![]'`\"$qc]*\r)/u",
                "/(\n|^)(?=.?[$termchar][^\n]*(\n|$))/u"
            ),
            array(
                "",
                "\r",
                "\r\n$1",
                ".\n",
                "\n1\t"
            ),
            str_replace(array("\t", "\n\n"), array("\n", ""), $text)
        );
        $text = trim($replaced ?? $text);
        $text = StringUtils::removeSpaces(
            preg_replace("/(\n|^)(?!1\t)/u", "\n0\t", $text) ?? $text,
            $removeSpaces
        );

        // It is faster to write to a file and let SQL do its magic, but may run into
        // security restrictions
        $use_local_infile = in_array(
            Connection::fetchValue("SELECT @@GLOBAL.local_infile as value"),
            array(1, '1', 'ON')
        );
        // For database mode, we use a positive ID placeholder (1) since saveWithSql
        // only checks if id > 0 for sentence ID calculation
        $idForSql = $useMaxSeID ? 1 : 0;
        if ($use_local_infile) {
            TextParsingPersistence::saveWithSql($text, $idForSql);
        } else {
            $order = 0;
            $sid = 1;
            if ($useMaxSeID) {
                // Get next auto-increment value from table status
                // This is more reliable than MAX(SeID)+1 when there are gaps
                $dbname = Globals::getDatabaseName();
                $sentencesTable = Globals::table('sentences');
                $sid = (int)Connection::preparedFetchValue(
                    "SELECT AUTO_INCREMENT as value FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                    [$dbname, $sentencesTable]
                );
                // Fall back to MAX+1 if AUTO_INCREMENT is not available
                if ($sid <= 0) {
                    $sid = (int)Connection::fetchValue(
                        "SELECT IFNULL(MAX(`SeID`)+1,1) as value FROM sentences"
                        . UserScopedQuery::forTable('sentences')
                    );
                }
            }
            $count = 0;
            $rows = array();
            foreach (explode("\n", $text) as $line) {
                if (trim($line) == "") {
                    continue;
                }
                list($word_count, $term) = explode("\t", $line);
                $tiSeID = $sid; // TiSeID
                $tiCount = $count + 1; // TiCount
                $count += mb_strlen($term);
                if (str_ends_with($term, "\r")) {
                    $term = str_replace("\r", '', $term);
                    $sid++;
                    $count = 0;
                }
                $tiOrder = ++$order; // TiOrder
                $tiWordCount = (int)$word_count; // TiWordCount
                $rows[] = array($tiSeID, $tiCount, $tiOrder, $term, $tiWordCount);
            }

            // Build multi-row INSERT with prepared statement
            if (!empty($rows)) {
                $placeholders = array();
                $flatParams = array();
                foreach ($rows as $row) {
                    $placeholders[] = "(?, ?, ?, ?, ?)";
                    $flatParams = array_merge($flatParams, $row);
                }

                Connection::preparedExecute(
                    "INSERT INTO temp_word_occurrences (
                        TiSeID, TiCount, TiOrder, TiText, TiWordCount
                    ) VALUES " . implode(',', $placeholders),
                    $flatParams
                );
            }
        }
    }

    /**
     * Parse a text using the default tools. It is a not-japanese text.
     *
     * @param string $text Text to parse
     * @param int    $id   Text ID. If $id == -2, only split the text.
     * @param int    $lid  Language ID.
     *
     * @return null|string[] If $id == -2 return a splitted version of the text.
     *
     * @psalm-return non-empty-list<string>|null
     *
     * @internal Use TextParsing::splitIntoSentences(), parseAndDisplayPreview(), or parseAndSave() instead.
     */
    public static function parseStandard(string $text, int $id, int $lid): ?array
    {
        $settings = self::getLanguageSettings($lid);

        // Return null if language not found
        if ($settings === null) {
            return null;
        }

        // Apply initial transformations (paragraph markers, trim, collapse spaces)
        $text = self::applyInitialTransformations(
            $text,
            $settings['splitEachChar']
        );

        // Preview mode - display HTML BEFORE word splitting
        if ($id == -1) {
            self::displayStandardPreview($text, (bool)$settings['rtlScript']);
        }

        // Apply word-splitting transformations
        $text = self::applyWordSplitting(
            $text,
            $settings['splitSentence'],
            $settings['noSentenceEnd'],
            $settings['termchar']
        );

        // Split-only mode
        if ($id == -2) {
            return self::splitStandardSentences($text, $settings['removeSpaces']);
        }

        // Database insertion (for both preview mode -1 and actual save mode > 0)
        self::parseStandardToDatabase(
            $text,
            $settings['termchar'],
            $settings['removeSpaces'],
            $id > 0
        );

        return null;
    }
}

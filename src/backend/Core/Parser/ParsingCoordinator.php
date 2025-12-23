<?php declare(strict_types=1);
/**
 * Parsing Coordinator - Orchestrates text parsing operations.
 *
 * PHP version 8.1
 *
 * @category Parser
 * @package  Lwt\Core\Parser
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Core\Parser;

use Lwt\Core\Entity\Language;
use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\QueryBuilder;
use Lwt\Database\UserScopedQuery;

/**
 * Coordinates parsing operations, providing a facade for the parser system.
 *
 * This class handles parser selection, preprocessing, and database persistence.
 * It serves as the main entry point for text parsing operations.
 *
 * @since 3.0.0
 */
class ParsingCoordinator
{
    public function __construct(
        private ParserRegistry $registry
    ) {
    }

    /**
     * Split text into sentences without database operations.
     *
     * @param string   $text     Text to parse
     * @param Language $language Language entity
     *
     * @return string[] Array of sentences
     */
    public function splitIntoSentences(string $text, Language $language): array
    {
        $text = $this->preprocess($text, $language);
        $config = ParserConfig::fromLanguage($language);
        $parser = $this->registry->getParserForLanguage($language);

        $result = $parser->parse($text, $config);
        return $result->getSentences();
    }

    /**
     * Parse text and return the result without database operations.
     *
     * @param string   $text     Text to parse
     * @param Language $language Language entity
     *
     * @return ParserResult Parsing result
     */
    public function parseForPreview(string $text, Language $language): ParserResult
    {
        $text = $this->preprocess($text, $language);
        $config = ParserConfig::fromLanguage($language);
        $parser = $this->registry->getParserForLanguage($language);

        return $parser->parse($text, $config);
    }

    /**
     * Parse text and save to database.
     *
     * @param string   $text     Text to parse
     * @param Language $language Language entity
     * @param int      $textId   Text ID (must be positive)
     *
     * @return void
     *
     * @throws \InvalidArgumentException If textId is not positive
     */
    public function parseAndSave(string $text, Language $language, int $textId): void
    {
        if ($textId <= 0) {
            throw new \InvalidArgumentException(
                "Text ID must be positive, got: $textId"
            );
        }

        $text = $this->preprocess($text, $language);
        $config = ParserConfig::fromLanguage($language);
        $parser = $this->registry->getParserForLanguage($language);

        $result = $parser->parse($text, $config);

        $this->saveToDatabase($result, $language, $textId);
    }

    /**
     * Preprocess text before parsing.
     *
     * Applies character substitutions and other text cleanup.
     *
     * @param string   $text     Raw text
     * @param Language $language Language entity
     *
     * @return string Preprocessed text
     */
    protected function preprocess(string $text, Language $language): string
    {
        $text = Escaping::prepareTextdata($text);

        // Replace special characters that interfere with sentence parsing
        $text = str_replace(['}', '{'], [']', '['], $text);

        // Apply character substitutions
        $substitutions = $language->characterSubstitutions();
        if ($substitutions !== '') {
            foreach (explode('|', $substitutions) as $value) {
                $fromto = explode('=', trim($value));
                if (count($fromto) >= 2) {
                    $text = str_replace(trim($fromto[0]), trim($fromto[1]), $text);
                }
            }
        }

        return $text;
    }

    /**
     * Save parsing result to database.
     *
     * @param ParserResult $result   Parsing result
     * @param Language     $language Language entity
     * @param int          $textId   Text ID
     *
     * @return void
     */
    protected function saveToDatabase(
        ParserResult $result,
        Language $language,
        int $textId
    ): void {
        $lid = $language->id()->toInt();

        // Clear temporary table
        QueryBuilder::table('temptextitems')->truncate();

        // Get next sentence ID
        $dbname = Globals::getDatabaseName();
        $sentencesTable = Globals::table('sentences');
        $nextSeID = (int) Connection::fetchValue(
            "SELECT AUTO_INCREMENT as value FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = '$sentencesTable'"
        );
        if ($nextSeID <= 0) {
            $nextSeID = (int) Connection::fetchValue(
                "SELECT IFNULL(MAX(`SeID`)+1,1) as value FROM sentences"
                . UserScopedQuery::forTable('sentences')
            );
        }

        // Insert tokens into temptextitems
        $this->insertTokensToTemp($result, $nextSeID);

        // Check for multi-word expressions
        $hasMultiword = $this->checkExpressions($lid);

        // Register sentences and text items
        $this->registerSentencesTextItems($textId, $lid, $hasMultiword);
    }

    /**
     * Insert tokens into temptextitems table.
     *
     * @param ParserResult $result   Parsing result
     * @param int          $startSeID Starting sentence ID
     *
     * @return void
     */
    protected function insertTokensToTemp(ParserResult $result, int $startSeID): void
    {
        $tokens = $result->getTokens();
        if (empty($tokens)) {
            return;
        }

        $rows = [];
        $currentSeID = $startSeID;
        $lastSentenceIndex = -1;
        $count = 0;

        foreach ($tokens as $token) {
            // Track sentence changes
            if ($token->getSentenceIndex() !== $lastSentenceIndex) {
                if ($lastSentenceIndex >= 0) {
                    $currentSeID++;
                    $count = 0;
                }
                $lastSentenceIndex = $token->getSentenceIndex();
            }

            $tiSeID = $currentSeID;
            $tiCount = $count + 1;
            $count += mb_strlen($token->getText());
            $tiOrder = $token->getOrder() + 1; // 1-based in DB
            $tiWordCount = $token->isWord() ? 1 : 0;

            $rows[] = [$tiSeID, $tiCount, $tiOrder, $token->getText(), $tiWordCount];
        }

        if (!empty($rows)) {
            $placeholders = [];
            $flatParams = [];
            foreach ($rows as $row) {
                $placeholders[] = "(?, ?, ?, ?, ?)";
                $flatParams = array_merge($flatParams, $row);
            }

            Connection::preparedExecute(
                "INSERT INTO temptextitems (
                    TiSeID, TiCount, TiOrder, TiText, TiWordCount
                ) VALUES " . implode(',', $placeholders),
                $flatParams
            );
        }
    }

    /**
     * Check for multi-word expressions and populate tempexprs.
     *
     * @param int $lid Language ID
     *
     * @return bool True if multi-word expressions were found
     */
    protected function checkExpressions(int $lid): bool
    {
        // This is a simplified version - the full implementation is in TextParsing
        $bindings = [$lid, $lid];
        $result = Connection::preparedFetchAll(
            "SELECT WoWordCount FROM words WHERE WoWordCount > 1 AND WoLgID = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings, '')
            . " GROUP BY WoWordCount",
            $bindings
        );

        return !empty($result);
    }

    /**
     * Register sentences and text items in the database.
     *
     * @param int  $tid          Text ID
     * @param int  $lid          Language ID
     * @param bool $hasmultiword Whether to process multi-word expressions
     *
     * @return void
     */
    protected function registerSentencesTextItems(
        int $tid,
        int $lid,
        bool $hasmultiword
    ): void {
        // Insert sentences
        Connection::query('SET @i=0;');
        Connection::preparedExecute(
            "INSERT INTO sentences (
                SeLgID, SeTxID, SeOrder, SeFirstPos, SeText
            ) SELECT
            ?,
            ?,
            @i:=@i+1,
            MIN(IF(TiWordCount=0, TiOrder+1, TiOrder)),
            GROUP_CONCAT(TiText ORDER BY TiOrder SEPARATOR \"\")
            FROM temptextitems
            GROUP BY TiSeID
            ORDER BY TiSeID",
            [$lid, $tid]
        );

        // Insert text items
        if ($hasmultiword) {
            $bindings = [$lid, $tid, $lid, $lid, $tid, $lid];
            $stmt = Connection::prepare(
                "INSERT INTO textitems2 (
                    Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text
                ) SELECT WoID, ?, ?, sent, TiOrder - (2*(n-1)) TiOrder,
                n TiWordCount, word
                FROM tempexprs
                JOIN words
                ON WoTextLC = lword AND WoWordCount = n"
                . UserScopedQuery::forTablePrepared('words', $bindings, '')
                . " WHERE lword IS NOT NULL AND WoLgID = ?
                UNION ALL
                SELECT WoID, ?, ?, TiSeID, TiOrder, TiWordCount, TiText
                FROM temptextitems
                LEFT JOIN words
                ON LOWER(TiText) = WoTextLC AND TiWordCount=1 AND WoLgID = ?"
                . UserScopedQuery::forTablePrepared('words', $bindings, '')
                . " ORDER BY TiOrder, TiWordCount"
            );
            $stmt->bindValues($bindings);
            $stmt->execute();
        } else {
            $bindings = [$lid, $tid, $lid];
            Connection::preparedExecute(
                "INSERT INTO textitems2 (
                    Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text
                ) SELECT WoID, ?, ?, TiSeID, TiOrder, TiWordCount, TiText
                FROM temptextitems
                LEFT JOIN words
                ON LOWER(TiText) = WoTextLC AND TiWordCount=1 AND WoLgID = ?"
                . UserScopedQuery::forTablePrepared('words', $bindings, '')
                . " ORDER BY TiSeID, TiOrder, TiWordCount",
                $bindings
            );
        }
    }

    /**
     * Get the parser registry.
     *
     * @return ParserRegistry Parser registry
     */
    public function getRegistry(): ParserRegistry
    {
        return $this->registry;
    }
}

<?php

/**
 * Word Service - Business logic for word/term operations
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Services;

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Settings;

/**
 * Service class for managing words/terms.
 *
 * Handles CRUD operations for vocabulary items.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class WordService
{
    private string $tbpref;

    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    /**
     * Create a new word/term.
     *
     * @param array $data Word data with keys:
     *                    - WoLgID: Language ID
     *                    - WoText: Term text
     *                    - WoStatus: Learning status (1-5, 98, 99)
     *                    - WoTranslation: Translation text
     *                    - WoSentence: Example sentence
     *                    - WoRomanization: Romanization/phonetic
     *
     * @return array{id: int, message: string, success: bool, textlc: string, text: string}
     */
    public function create(array $data): array
    {
        $text = trim(Escaping::prepareTextdata($data['WoText']));
        $textlc = mb_strtolower($text, 'UTF-8');
        $translation = $this->normalizeTranslation($data['WoTranslation'] ?? '');

        try {
            Connection::execute(
                'INSERT INTO ' . $this->tbpref . 'words (
                    WoLgID, WoTextLC, WoText, WoStatus, WoTranslation,
                    WoSentence, WoRomanization, WoStatusChanged, ' .
                    \make_score_random_insert_update('iv') . '
                ) VALUES (
                    ' . (int)$data['WoLgID'] . ', ' .
                    Escaping::toSqlSyntax($textlc) . ', ' .
                    Escaping::toSqlSyntax($text) . ', ' .
                    (int)$data['WoStatus'] . ', ' .
                    Escaping::toSqlSyntax($translation) . ', ' .
                    Escaping::toSqlSyntax(\repl_tab_nl($data['WoSentence'] ?? '')) . ', ' .
                    Escaping::toSqlSyntax($data['WoRomanization'] ?? '') . ', NOW(), ' .
                    \make_score_random_insert_update('id') .
                ')'
            );

            $wid = (int)Connection::lastInsertId();

            return [
                'id' => $wid,
                'message' => 'Term saved',
                'success' => true,
                'textlc' => $textlc,
                'text' => $text
            ];
        } catch (\RuntimeException $e) {
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'Duplicate entry') !== false) {
                $message = 'Error: Duplicate entry for "' . $textlc . '"';
            } else {
                $message = 'Error: ' . $errorMsg;
            }

            return [
                'id' => 0,
                'message' => $message,
                'success' => false,
                'textlc' => $textlc,
                'text' => $text
            ];
        }
    }

    /**
     * Update an existing word/term.
     *
     * @param int   $wordId Word ID
     * @param array $data   Word data (same keys as create())
     *
     * @return array{id: int, message: string, success: bool, textlc: string, text: string}
     */
    public function update(int $wordId, array $data): array
    {
        $text = trim(Escaping::prepareTextdata($data['WoText']));
        $textlc = mb_strtolower($text, 'UTF-8');
        $translation = $this->normalizeTranslation($data['WoTranslation'] ?? '');

        $statusUpdate = '';
        if (isset($data['WoOldStatus']) && $data['WoOldStatus'] != $data['WoStatus']) {
            $statusUpdate = ', WoStatus = ' . (int)$data['WoStatus'] . ', WoStatusChanged = NOW()';
        }

        Connection::execute(
            'UPDATE ' . $this->tbpref . 'words SET
                WoText = ' . Escaping::toSqlSyntax($text) . ',
                WoTranslation = ' . Escaping::toSqlSyntax($translation) . ',
                WoSentence = ' . Escaping::toSqlSyntax(\repl_tab_nl($data['WoSentence'] ?? '')) . ',
                WoRomanization = ' . Escaping::toSqlSyntax($data['WoRomanization'] ?? '') .
                $statusUpdate . ',' .
                \make_score_random_insert_update('u') . '
            WHERE WoID = ' . $wordId
        );

        return [
            'id' => $wordId,
            'message' => 'Updated',
            'success' => true,
            'textlc' => $textlc,
            'text' => $text
        ];
    }

    /**
     * Find a word by ID.
     *
     * @param int $wordId Word ID
     *
     * @return array|null Word data or null if not found
     */
    public function findById(int $wordId): ?array
    {
        $sql = "SELECT * FROM {$this->tbpref}words WHERE WoID = $wordId";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $record ?: null;
    }

    /**
     * Find a word by text and language.
     *
     * @param string $textlc Lowercase text
     * @param int    $langId Language ID
     *
     * @return int|null Word ID or null if not found
     */
    public function findByText(string $textlc, int $langId): ?int
    {
        $id = Connection::fetchValue(
            "SELECT WoID AS value FROM {$this->tbpref}words
            WHERE WoLgID = $langId AND WoTextLC = " . Escaping::toSqlSyntax($textlc)
        );
        return $id !== null ? (int)$id : null;
    }

    /**
     * Get term data from text items (for reading screen).
     *
     * @param int $textId Text ID
     * @param int $ord    Word order position
     *
     * @return array|null Term data with Ti2Text and Ti2LgID
     */
    public function getTermFromTextItem(int $textId, int $ord): ?array
    {
        $sql = "SELECT Ti2Text, Ti2LgID FROM {$this->tbpref}textitems2
                WHERE Ti2TxID = $textId AND Ti2WordCount = 1 AND Ti2Order = $ord";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $record ?: null;
    }

    /**
     * Link word to text items after creation.
     *
     * @param int    $wordId Word ID
     * @param int    $langId Language ID
     * @param string $textlc Lowercase text
     *
     * @return void
     */
    public function linkToTextItems(int $wordId, int $langId, string $textlc): void
    {
        Connection::query(
            'UPDATE ' . $this->tbpref . 'textitems2 SET Ti2WoID = ' . $wordId . '
            WHERE Ti2LgID = ' . $langId . ' AND LOWER(Ti2Text) = ' .
            Escaping::toSqlSyntaxNoTrimNoNull($textlc)
        );
    }

    /**
     * Get language data for a word form.
     *
     * @param int $langId Language ID
     *
     * @return array Language data
     */
    public function getLanguageData(int $langId): array
    {
        $data = [];

        $data['showRoman'] = (bool) Connection::fetchValue(
            "SELECT LgShowRomanization AS value FROM {$this->tbpref}languages WHERE LgID = $langId"
        );

        $data['translateUri'] = (string) Connection::fetchValue(
            "SELECT LgGoogleTranslateURI AS value FROM {$this->tbpref}languages WHERE LgID = $langId"
        );

        $data['name'] = (string) Connection::fetchValue(
            "SELECT LgName AS value FROM {$this->tbpref}languages WHERE LgID = $langId"
        );

        return $data;
    }

    /**
     * Get sentence for a term.
     *
     * @param int    $textId Text ID
     * @param int    $ord    Word order
     * @param string $termlc Lowercase term
     *
     * @return string Sentence with term marked
     */
    public function getSentenceForTerm(int $textId, int $ord, string $termlc): string
    {
        $seid = Connection::fetchValue(
            "SELECT Ti2SeID AS value FROM {$this->tbpref}textitems2
            WHERE Ti2TxID = $textId AND Ti2WordCount = 1 AND Ti2Order = $ord"
        );

        if ($seid === null) {
            return '';
        }

        $sent = \getSentence(
            $seid,
            $termlc,
            (int) Settings::getWithDefault('set-term-sentence-count')
        );

        return \repl_tab_nl($sent[1] ?? '');
    }

    /**
     * Normalize translation text.
     *
     * @param string $translation Raw translation
     *
     * @return string Normalized translation (empty becomes '*')
     */
    private function normalizeTranslation(string $translation): string
    {
        $translation = trim(\repl_tab_nl($translation));
        return $translation === '' ? '*' : $translation;
    }

    /**
     * Get word count for a term.
     *
     * @param int $wordId Word ID
     *
     * @return int Word count
     */
    public function getWordCount(int $wordId): int
    {
        return (int) Connection::fetchValue(
            "SELECT WoWordCount AS value FROM {$this->tbpref}words WHERE WoID = $wordId"
        );
    }

    /**
     * Convert text to hex class name for CSS.
     *
     * @param string $text Text to convert
     *
     * @return string Hex class name
     */
    public function textToClassName(string $text): string
    {
        return \strToClassName(Escaping::prepareTextdata($text));
    }
}

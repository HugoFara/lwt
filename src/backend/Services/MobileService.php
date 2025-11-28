<?php

/**
 * Mobile Service - Business logic for mobile interface
 *
 * Handles data retrieval and processing for the mobile version of LWT.
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

/**
 * Service class for mobile interface functionality.
 *
 * Provides methods to retrieve languages, texts, sentences, and terms
 * for the mobile reading interface.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class MobileService
{
    /**
     * Database table prefix.
     *
     * @var string
     */
    private string $tbpref;

    /**
     * Constructor - initialize table prefix.
     */
    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    /**
     * Get all languages with active texts.
     *
     * @return array Array of languages with LgID and LgName
     */
    public function getLanguages(): array
    {
        $sql = 'SELECT LgID, LgName FROM ' . $this->tbpref .
               'languages WHERE LgName <> "" ORDER BY LgName';
        $res = Connection::query($sql);

        $languages = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $languages[] = [
                'id' => (int) $record['LgID'],
                'name' => $record['LgName']
            ];
        }
        mysqli_free_result($res);

        return $languages;
    }

    /**
     * Get language name by ID.
     *
     * @param int $langId Language ID
     *
     * @return string|null Language name or null if not found
     */
    public function getLanguageName(int $langId): ?string
    {
        $sql = 'SELECT LgName AS value FROM ' . $this->tbpref .
               'languages WHERE LgID = ' . $langId;
        $result = Connection::fetchValue($sql);

        return $result !== null ? (string) $result : null;
    }

    /**
     * Get all texts for a language.
     *
     * @param int $langId Language ID
     *
     * @return array Array of texts with TxID and TxTitle
     */
    public function getTextsByLanguage(int $langId): array
    {
        $sql = 'SELECT TxID, TxTitle FROM ' . $this->tbpref .
               'texts WHERE TxLgID = ' . $langId . ' ORDER BY TxTitle';
        $res = Connection::query($sql);

        $texts = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $texts[] = [
                'id' => (int) $record['TxID'],
                'title' => $record['TxTitle']
            ];
        }
        mysqli_free_result($res);

        return $texts;
    }

    /**
     * Get text details by ID.
     *
     * @param int $textId Text ID
     *
     * @return array|null Text data or null if not found
     */
    public function getTextById(int $textId): ?array
    {
        $sql = 'SELECT TxID, TxTitle, TxAudioURI FROM ' . $this->tbpref .
               'texts WHERE TxID = ' . $textId;
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        if (!$record) {
            return null;
        }

        return [
            'id' => (int) $record['TxID'],
            'title' => $record['TxTitle'],
            'audioUri' => trim((string) ($record['TxAudioURI'] ?? ''))
        ];
    }

    /**
     * Get sentences for a text.
     *
     * @param int $textId Text ID
     *
     * @return array Array of sentences with SeID and SeText
     */
    public function getSentencesByText(int $textId): array
    {
        $sql = 'SELECT SeID, SeText FROM ' . $this->tbpref .
               'sentences WHERE SeTxID = ' . $textId . ' ORDER BY SeOrder';
        $res = Connection::query($sql);

        $sentences = [];
        while ($record = mysqli_fetch_assoc($res)) {
            // Skip paragraph markers
            if (trim((string) $record['SeText']) !== '¶') {
                $sentences[] = [
                    'id' => (int) $record['SeID'],
                    'text' => $record['SeText']
                ];
            }
        }
        mysqli_free_result($res);

        return $sentences;
    }

    /**
     * Get sentence by ID.
     *
     * @param int $sentenceId Sentence ID
     *
     * @return array|null Sentence data or null if not found
     */
    public function getSentenceById(int $sentenceId): ?array
    {
        $sql = 'SELECT SeID, SeText, SeTxID FROM ' . $this->tbpref .
               'sentences WHERE SeID = ' . $sentenceId;
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        if (!$record) {
            return null;
        }

        return [
            'id' => (int) $record['SeID'],
            'text' => $record['SeText'],
            'textId' => (int) $record['SeTxID']
        ];
    }

    /**
     * Get the next sentence ID after a given sentence.
     *
     * @param int $textId     Text ID
     * @param int $sentenceId Current sentence ID
     *
     * @return int|null Next sentence ID or null if none
     */
    public function getNextSentenceId(int $textId, int $sentenceId): ?int
    {
        $sql = "SELECT SeID AS value
                FROM " . $this->tbpref . "sentences
                WHERE SeTxID = " . $textId . "
                AND trim(SeText) != '¶'
                AND SeID > " . $sentenceId . "
                ORDER BY SeID
                LIMIT 1";
        $result = Connection::fetchValue($sql);

        return $result !== null ? (int) $result : null;
    }

    /**
     * Get terms for a sentence with their translations and status.
     *
     * @param int $sentenceId Sentence ID
     *
     * @return array Array of term data for display
     */
    public function getTermsBySentence(int $sentenceId): array
    {
        $sql = "SELECT
                CASE WHEN Ti2WordCount > 0 THEN Ti2WordCount ELSE 1 END AS Code,
                CASE WHEN CHAR_LENGTH(Ti2Text) > 0 THEN Ti2Text ELSE WoText END AS TiText,
                Ti2Order,
                CASE WHEN Ti2WordCount > 0 THEN 0 ELSE 1 END AS TiIsNotWord,
                WoID, WoTranslation, WoRomanization, WoStatus
                FROM (" . $this->tbpref . "textitems2
                    LEFT JOIN " . $this->tbpref . "words
                    ON (Ti2WoID = WoID) AND (Ti2LgID = WoLgID)
                )
                WHERE Ti2SeID = " . $sentenceId . "
                ORDER BY Ti2Order ASC, Ti2WordCount DESC";

        $res = Connection::query($sql);

        $terms = [];
        $saveterm = '';
        $savetrans = '';
        $saverom = '';
        $savestat = '';
        $until = 0;

        while ($record = mysqli_fetch_assoc($res)) {
            $actcode = (int) $record['Code'];
            $order = (int) $record['Ti2Order'];

            if ($order <= $until) {
                continue;
            }

            if ($order > $until) {
                if (trim($saveterm) !== '') {
                    $terms[] = $this->formatTerm(
                        $saveterm,
                        $savetrans !== '' ? $savetrans : null,
                        $saverom,
                        $savestat !== '' ? $savestat : null
                    );
                }
                $saveterm = '';
                $savetrans = '';
                $saverom = '';
                $savestat = '';
                $until = $order;
            }

            if ($record['TiIsNotWord'] != 0 && trim((string) $record['TiText']) !== '') {
                // Non-word item (punctuation, etc.)
                $terms[] = [
                    'type' => 'nonword',
                    'text' => $record['TiText']
                ];
            } else {
                // Word item
                $until = $order + 2 * ($actcode - 1);
                $saveterm = (string) $record['TiText'];
                $savetrans = '';

                if (isset($record['WoID'])) {
                    $savetrans = (string) ($record['WoTranslation'] ?? '');
                    if ($savetrans === '*') {
                        $savetrans = '';
                    }
                }

                $saverom = trim(
                    isset($record['WoRomanization']) ?
                    (string) $record['WoRomanization'] : ""
                );
                $savestat = isset($record['WoStatus']) ? (string) $record['WoStatus'] : '';
            }
        }

        mysqli_free_result($res);

        // Process final term
        if (trim($saveterm) !== '') {
            $terms[] = $this->formatTerm(
                $saveterm,
                $savetrans !== '' ? $savetrans : null,
                $saverom,
                $savestat !== '' ? $savestat : null
            );
        }

        return $terms;
    }

    /**
     * Format a term for display.
     *
     * @param string      $term        Term text
     * @param string|null $translation Translation text
     * @param string      $romanization Romanization
     * @param string|null $status      Word status
     *
     * @return array Formatted term data
     */
    private function formatTerm(
        string $term,
        ?string $translation,
        string $romanization,
        ?string $status
    ): array {
        $description = '';
        if ($romanization !== '') {
            $description .= '[' . $romanization . '] ';
        }
        if ($translation !== null && $translation !== '') {
            $description .= $translation;
        }
        $description = trim($description);

        return [
            'type' => 'word',
            'text' => $term,
            'description' => $description !== '' ? ' → ' . $description : '',
            'status' => $status
        ];
    }

    /**
     * Get the application version.
     *
     * @return string Version string
     */
    public function getVersion(): string
    {
        return get_version();
    }
}

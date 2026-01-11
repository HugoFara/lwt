<?php

/**
 * Word Context Service - Language and text context retrieval
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services;

require_once __DIR__ . '/../../../Text/Application/Services/SentenceService.php';
require_once __DIR__ . '/ExportService.php';

use Lwt\Modules\Text\Application\Services\SentenceService;
use Lwt\Core\StringUtils;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\Escaping;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;

/**
 * Service for language and text context retrieval.
 *
 * Provides methods for:
 * - Language configuration (romanization, dictionaries)
 * - Text-to-language mapping
 * - Sentence retrieval and formatting
 * - Term data export
 *
 * @since 3.0.0
 */
class WordContextService
{
    private SentenceService $sentenceService;

    /**
     * Constructor.
     *
     * @param SentenceService|null $sentenceService Sentence service
     */
    public function __construct(?SentenceService $sentenceService = null)
    {
        $this->sentenceService = $sentenceService ?? new SentenceService();
    }

    /**
     * Get language ID from a text ID.
     *
     * @param int $textId Text ID
     *
     * @return int|null Language ID or null if not found
     */
    public function getLanguageIdFromText(int $textId): ?int
    {
        $bindings = [$textId];
        /** @var int|null $langId */
        $langId = Connection::preparedFetchValue(
            "SELECT TxLgID FROM texts WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings,
            'TxLgID'
        );
        return $langId;
    }

    /**
     * Get language data for display settings.
     *
     * @param int $langId Language ID
     *
     * @return array{showRoman: bool, translateUri: string, name: string} Language data
     */
    public function getLanguageData(int $langId): array
    {
        $bindings = [$langId];
        $row = Connection::preparedFetchOne(
            "SELECT LgShowRomanization, LgGoogleTranslateURI, LgName
             FROM languages WHERE LgID = ?"
             . UserScopedQuery::forTablePrepared('languages', $bindings),
            $bindings
        );

        return [
            'showRoman' => (bool) ($row['LgShowRomanization'] ?? false),
            'translateUri' => (string) ($row['LgGoogleTranslateURI'] ?? ''),
            'name' => (string) ($row['LgName'] ?? '')
        ];
    }

    /**
     * Get language dictionaries for a text.
     *
     * @param int $textId Text ID
     *
     * @return array{name: string, dict1: string, dict2: string, translate: string}
     */
    public function getLanguageDictionaries(int $textId): array
    {
        $bindings = [$textId];
        $record = Connection::preparedFetchOne(
            "SELECT LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI
             FROM languages, texts
             WHERE LgID = TxLgID AND TxID = ?"
             . UserScopedQuery::forTablePrepared('languages', $bindings, 'languages')
             . UserScopedQuery::forTablePrepared('texts', $bindings, 'texts'),
            $bindings
        );

        return [
            'name' => (string) ($record['LgName'] ?? ''),
            'dict1' => (string) ($record['LgDict1URI'] ?? ''),
            'dict2' => (string) ($record['LgDict2URI'] ?? ''),
            'translate' => (string) ($record['LgGoogleTranslateURI'] ?? ''),
        ];
    }

    /**
     * Check if romanization should be shown for a text's language.
     *
     * @param int $textId Text ID
     *
     * @return bool Whether to show romanization
     */
    public function shouldShowRomanization(int $textId): bool
    {
        $bindings = [$textId];
        return (bool) Connection::preparedFetchValue(
            "SELECT LgShowRomanization
             FROM languages JOIN texts ON TxLgID = LgID
             WHERE TxID = ?"
             . UserScopedQuery::forTablePrepared('languages', $bindings, 'languages')
             . UserScopedQuery::forTablePrepared('texts', $bindings, 'texts'),
            $bindings,
            'LgShowRomanization'
        );
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
        /** @var int|null $seid */
        $seid = Connection::preparedFetchValue(
            "SELECT Ti2SeID FROM word_occurrences
             WHERE Ti2TxID = ? AND Ti2WordCount = 1 AND Ti2Order = ?",
            [$textId, $ord],
            'Ti2SeID'
        );

        if ($seid === null) {
            return '';
        }

        $sent = $this->sentenceService->formatSentence(
            $seid,
            $termlc,
            (int) Settings::getWithDefault('set-term-sentence-count')
        );

        return ExportService::replaceTabNewline($sent[1] ?? '');
    }

    /**
     * Get sentence ID at a text position.
     *
     * @param int $textId Text ID
     * @param int $ord    Position in text
     *
     * @return int|null Sentence ID or null if not found
     */
    public function getSentenceIdAtPosition(int $textId, int $ord): ?int
    {
        /** @var int|null $seid */
        $seid = Connection::preparedFetchValue(
            "SELECT Ti2SeID
             FROM word_occurrences
             WHERE Ti2TxID = ? AND Ti2Order = ?",
            [$textId, $ord],
            'Ti2SeID'
        );
        return $seid;
    }

    /**
     * Get sentence text at a text position.
     *
     * @param int $textId Text ID
     * @param int $ord    Position in text
     *
     * @return string|null Sentence text or null if not found
     */
    public function getSentenceTextAtPosition(int $textId, int $ord): ?string
    {
        return $this->sentenceService->getSentenceAtPosition($textId, $ord);
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
        return StringUtils::toClassName(Escaping::prepareTextdata($text));
    }

    /**
     * Export term data as JSON for JavaScript.
     *
     * @param int    $wordId      Word ID
     * @param string $text        Term text
     * @param string $roman       Romanization
     * @param string $translation Translation with tags
     * @param int    $status      Word status
     *
     * @return string JSON encoded data
     */
    public function exportTermAsJson(
        int $wordId,
        string $text,
        string $roman,
        string $translation,
        int $status
    ): string {
        $data = [
            "woid" => $wordId,
            "text" => $text,
            "romanization" => $roman,
            "translation" => $translation,
            "status" => $status
        ];

        $json = json_encode($data);
        if ($json === false) {
            $json = json_encode(["error" => "Unable to return data."]);
            if ($json === false) {
                throw new \RuntimeException("Unable to return data");
            }
        }
        return $json;
    }
}

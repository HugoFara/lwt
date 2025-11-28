<?php

/**
 * Text Print Service - Business logic for text printing functionality
 *
 * Handles print operations for both plain text and improved annotated text.
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
use Lwt\Database\Settings;
use Lwt\Services\TagService;

/**
 * Service class for managing text printing operations.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TextPrintService
{
    /**
     * Database table prefix.
     *
     * @var string
     */
    private string $tbpref;

    /**
     * Annotation options - show romanization.
     */
    public const ANN_SHOW_ROM = 2;

    /**
     * Annotation options - show translation.
     */
    public const ANN_SHOW_TRANS = 1;

    /**
     * Annotation options - show tags.
     */
    public const ANN_SHOW_TAGS = 4;

    /**
     * Annotation placement - behind the term.
     */
    public const ANN_PLACEMENT_BEHIND = 0;

    /**
     * Annotation placement - in front of the term.
     */
    public const ANN_PLACEMENT_INFRONT = 1;

    /**
     * Annotation placement - above the term (ruby).
     */
    public const ANN_PLACEMENT_RUBY = 2;

    /**
     * Constructor - initialize table prefix.
     */
    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    // ===========================
    // TEXT DATA METHODS
    // ===========================

    /**
     * Get basic text data for printing.
     *
     * @param int $textId Text ID
     *
     * @return array|null Text data or null if not found
     */
    public function getTextData(int $textId): ?array
    {
        $sql = "SELECT TxID, TxLgID, TxTitle, TxSourceURI, TxAudioURI
                FROM {$this->tbpref}texts
                WHERE TxID = {$textId}";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $record ?: null;
    }

    /**
     * Get language data for a text.
     *
     * @param int $langId Language ID
     *
     * @return array|null Language data or null if not found
     */
    public function getLanguageData(int $langId): ?array
    {
        $sql = "SELECT LgTextSize, LgRemoveSpaces, LgRightToLeft, LgGoogleTranslateURI
                FROM {$this->tbpref}languages
                WHERE LgID = {$langId}";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $record ?: null;
    }

    /**
     * Get annotated text for a text ID.
     *
     * @param int $textId Text ID
     *
     * @return string|null Annotated text or null if not found/empty
     */
    public function getAnnotatedText(int $textId): ?string
    {
        $ann = (string) Connection::fetchValue(
            "SELECT TxAnnotatedText AS value FROM {$this->tbpref}texts
            WHERE TxID = {$textId}"
        );
        return strlen($ann) > 0 ? $ann : null;
    }

    /**
     * Check if annotated text exists for a text.
     *
     * @param int $textId Text ID
     *
     * @return bool True if annotated text exists
     */
    public function hasAnnotation(int $textId): bool
    {
        $length = (int) Connection::fetchValue(
            "SELECT LENGTH(TxAnnotatedText) AS value FROM {$this->tbpref}texts
            WHERE TxID = {$textId}"
        );
        return $length > 0;
    }

    /**
     * Delete annotated text for a text.
     *
     * @param int $textId Text ID
     *
     * @return bool True if deletion was successful
     */
    public function deleteAnnotation(int $textId): bool
    {
        Connection::execute(
            "UPDATE {$this->tbpref}texts
            SET TxAnnotatedText = NULL
            WHERE TxID = {$textId}"
        );
        return !$this->hasAnnotation($textId);
    }

    // ===========================
    // PRINT SETTINGS METHODS
    // ===========================

    /**
     * Get current print annotation setting.
     *
     * @param string|null $requestValue Value from request
     *
     * @return int Annotation flags
     */
    public function getAnnotationSetting(?string $requestValue): int
    {
        if ($requestValue !== null && $requestValue !== '') {
            return (int) $requestValue;
        }
        $setting = Settings::get('currentprintannotation');
        return $setting !== '' ? (int) $setting : 3;
    }

    /**
     * Get current print status range setting.
     *
     * @param string|null $requestValue Value from request
     *
     * @return int Status range
     */
    public function getStatusRangeSetting(?string $requestValue): int
    {
        if ($requestValue !== null && $requestValue !== '') {
            return (int) $requestValue;
        }
        $setting = Settings::get('currentprintstatus');
        return $setting !== '' ? (int) $setting : 14;
    }

    /**
     * Get current annotation placement setting.
     *
     * @param string|null $requestValue Value from request
     *
     * @return int Placement code
     */
    public function getAnnotationPlacementSetting(?string $requestValue): int
    {
        if ($requestValue !== null && $requestValue !== '') {
            return (int) $requestValue;
        }
        $setting = Settings::get('currentprintannotationplacement');
        return $setting !== '' ? (int) $setting : 0;
    }

    /**
     * Save current print settings.
     *
     * @param int $textId      Text ID
     * @param int $annotation  Annotation flags
     * @param int $statusRange Status range
     * @param int $placement   Annotation placement
     *
     * @return void
     */
    public function savePrintSettings(
        int $textId,
        int $annotation,
        int $statusRange,
        int $placement
    ): void {
        Settings::save('currenttext', $textId);
        Settings::save('currentprintannotation', $annotation);
        Settings::save('currentprintstatus', $statusRange);
        Settings::save('currentprintannotationplacement', $placement);
    }

    /**
     * Save current text setting only.
     *
     * @param int $textId Text ID
     *
     * @return void
     */
    public function setCurrentText(int $textId): void
    {
        Settings::save('currenttext', $textId);
    }

    // ===========================
    // TEXT ITEMS METHODS
    // ===========================

    /**
     * Get text items for plain print display.
     *
     * @param int $textId Text ID
     *
     * @return array Array of text items with word data
     */
    public function getTextItems(int $textId): array
    {
        $sql = "SELECT
                    CASE WHEN Ti2WordCount>0 THEN Ti2WordCount ELSE 1 END AS Code,
                    CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN Ti2Text ELSE WoText END AS TiText,
                    Ti2Order,
                    CASE WHEN Ti2WordCount > 0 THEN 0 ELSE 1 END as TiIsNotWord,
                    WoID, WoTranslation, WoRomanization, WoStatus
                FROM (
                    {$this->tbpref}textitems2
                    LEFT JOIN {$this->tbpref}words ON (Ti2WoID = WoID) AND (Ti2LgID = WoLgID)
                )
                WHERE Ti2TxID = {$textId}
                ORDER BY Ti2Order asc, Ti2WordCount desc";

        $res = Connection::query($sql);
        $items = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $items[] = $record;
        }
        mysqli_free_result($res);
        return $items;
    }

    /**
     * Get word tags for a word ID.
     *
     * @param int $wordId Word ID
     *
     * @return string Tags list
     */
    public function getWordTags(int $wordId): string
    {
        return TagService::getWordTagListFormatted($wordId, '', true, false);
    }

    // ===========================
    // TTS (TEXT-TO-SPEECH) METHODS
    // ===========================

    /**
     * Extract TTS language code from Google Translate URI.
     *
     * @param string $googleTranslateUri Google Translate URI
     *
     * @return string|null TTS class suffix or null
     */
    public function getTtsClass(string $googleTranslateUri): ?string
    {
        if (empty($googleTranslateUri)) {
            return null;
        }
        $ttsLg = preg_replace(
            '/.*[?&]sl=([a-zA-Z\-]*)(&.*)*$/',
            '$1',
            $googleTranslateUri
        );
        if ($googleTranslateUri !== $ttsLg) {
            return 'tts_' . $ttsLg . ' ';
        }
        return null;
    }

    // ===========================
    // ANNOTATION PARSING METHODS
    // ===========================

    /**
     * Parse annotation string into structured items.
     *
     * @param string $annotation Annotation string
     *
     * @return array Array of parsed annotation items
     */
    public function parseAnnotation(string $annotation): array
    {
        $items = preg_split('/[\n]/u', $annotation);
        $parsed = [];
        foreach ($items as $item) {
            $vals = preg_split('/[\t]/u', $item);
            $parsed[] = [
                'order' => isset($vals[0]) ? (int) $vals[0] : -1,
                'text' => $vals[1] ?? '',
                'wordId' => isset($vals[2]) && ctype_digit($vals[2]) ? (int) $vals[2] : null,
                'translation' => $vals[3] ?? ''
            ];
        }
        return $parsed;
    }

    // ===========================
    // STATUS CHECK METHODS
    // ===========================

    /**
     * Check if a word status is within the given range.
     *
     * @param int $status      Word status
     * @param int $statusRange Status range flags
     *
     * @return bool True if status is in range
     */
    public function checkStatusInRange(int $status, int $statusRange): bool
    {
        return \checkStatusRange($status, $statusRange);
    }

    // ===========================
    // VIEW DATA PREPARATION
    // ===========================

    /**
     * Prepare data for plain text print view.
     *
     * @param int $textId Text ID
     *
     * @return array|null View data or null if text not found
     */
    public function preparePlainPrintData(int $textId): ?array
    {
        $textData = $this->getTextData($textId);
        if ($textData === null) {
            return null;
        }

        $langData = $this->getLanguageData((int) $textData['TxLgID']);
        if ($langData === null) {
            return null;
        }

        return [
            'textId' => $textId,
            'title' => (string) $textData['TxTitle'],
            'sourceUri' => (string) $textData['TxSourceURI'],
            'audioUri' => trim((string) ($textData['TxAudioURI'] ?? '')),
            'langId' => (int) $textData['TxLgID'],
            'textSize' => (int) $langData['LgTextSize'],
            'rtlScript' => (bool) $langData['LgRightToLeft'],
            'hasAnnotation' => $this->hasAnnotation($textId)
        ];
    }

    /**
     * Prepare data for improved/annotated text print view.
     *
     * @param int $textId Text ID
     *
     * @return array|null View data or null if text not found
     */
    public function prepareAnnotatedPrintData(int $textId): ?array
    {
        $textData = $this->getTextData($textId);
        if ($textData === null) {
            return null;
        }

        $langData = $this->getLanguageData((int) $textData['TxLgID']);
        if ($langData === null) {
            return null;
        }

        $annotation = $this->getAnnotatedText($textId);
        $ttsClass = $this->getTtsClass((string) ($langData['LgGoogleTranslateURI'] ?? ''));

        return [
            'textId' => $textId,
            'title' => (string) $textData['TxTitle'],
            'sourceUri' => (string) $textData['TxSourceURI'],
            'audioUri' => trim((string) ($textData['TxAudioURI'] ?? '')),
            'langId' => (int) $textData['TxLgID'],
            'textSize' => (int) $langData['LgTextSize'],
            'rtlScript' => (bool) $langData['LgRightToLeft'],
            'annotation' => $annotation,
            'hasAnnotation' => $annotation !== null,
            'ttsClass' => $ttsClass
        ];
    }
}

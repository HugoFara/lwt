<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Settings;
use Lwt\Services\WordService;

require_once __DIR__ . '/../../../Services/WordService.php';

/**
 * Handler for text-related API operations.
 *
 * Extracted from api_v1.php lines 262-373.
 */
class TextHandler
{
    private WordService $wordService;

    public function __construct()
    {
        $this->wordService = new WordService();
    }
    /**
     * Save the reading position of the text.
     *
     * @param int $textid   Text ID
     * @param int $position Position in text to save
     *
     * @return void
     */
    public function saveTextPosition(int $textid, int $position): void
    {
        $tbpref = Globals::getTablePrefix();
        Connection::preparedExecute(
            "UPDATE {$tbpref}texts SET TxPosition = ? WHERE TxID = ?",
            [$position, $textid]
        );
    }

    /**
     * Save the audio position in the text.
     *
     * @param int $textid        Text ID
     * @param int $audioposition Audio position
     *
     * @return void
     */
    public function saveAudioPosition(int $textid, int $audioposition): void
    {
        $tbpref = Globals::getTablePrefix();
        Connection::preparedExecute(
            "UPDATE {$tbpref}texts SET TxAudioPosition = ? WHERE TxID = ?",
            [$audioposition, $textid]
        );
    }

    /**
     * Save data from printed text.
     *
     * @param int    $textid Text ID
     * @param int    $line   Line number to save
     * @param string $val    Proposed new annotation for the term
     *
     * @return string Error message, or "OK" if success.
     */
    public function saveImprTextData(int $textid, int $line, string $val): string
    {
        $tbpref = Globals::getTablePrefix();

        $ann = (string) Connection::preparedFetchValue(
            "SELECT TxAnnotatedText AS value FROM {$tbpref}texts WHERE TxID = ?",
            [$textid]
        );

        $items = preg_split('/[\n]/u', $ann);
        if (count($items) <= $line) {
            return "Unreachable translation: line request is $line, but only " .
            count($items) . " translations were found";
        }

        $vals = preg_split('/[\t]/u', $items[$line]);
        if ((int)$vals[0] <= -1) {
            return "Term is punctation! Term position is {$vals[0]}";
        }
        if (count($vals) < 4) {
            return "Not enough columns: " . count($vals);
        }

        $items[$line] = implode("\t", array($vals[0], $vals[1], $vals[2], $val));

        Connection::preparedExecute(
            "UPDATE {$tbpref}texts SET TxAnnotatedText = ? WHERE TxID = ?",
            [implode("\n", $items), $textid]
        );

        return "OK";
    }

    /**
     * Save a text with improved annotations.
     *
     * @param int    $textid Text ID
     * @param string $elem   Element to select
     * @param object $data   Data element
     *
     * @return array{error?: string, success?: string}
     */
    public function saveImprText(int $textid, string $elem, object $data): array
    {
        $newAnnotation = $data->{$elem};
        $line = (int)substr($elem, 2);
        if (str_starts_with($elem, "rg") && $newAnnotation == "") {
            $newAnnotation = $data->{'tx' . $line};
        }
        $status = $this->saveImprTextData($textid, $line, $newAnnotation);
        if ($status != "OK") {
            return ["error" => $status];
        }
        return ["success" => $status];
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for setting text position.
     *
     * @param int $textId   Text ID
     * @param int $position Position
     *
     * @return array{text: string}
     */
    public function formatSetTextPosition(int $textId, int $position): array
    {
        $this->saveTextPosition($textId, $position);
        return ["text" => "Reading position set"];
    }

    /**
     * Format response for setting audio position.
     *
     * @param int $textId   Text ID
     * @param int $position Audio position
     *
     * @return array{audio: string}
     */
    public function formatSetAudioPosition(int $textId, int $position): array
    {
        $this->saveAudioPosition($textId, $position);
        return ["audio" => "Audio position set"];
    }

    /**
     * Format response for setting annotation.
     *
     * @param int    $textId Text ID
     * @param string $elem   Element selector
     * @param string $data   JSON-encoded data
     *
     * @return array{save_impr_text?: string, error?: string}
     */
    public function formatSetAnnotation(int $textId, string $elem, string $data): array
    {
        $result = $this->saveImprText($textId, $elem, json_decode($data));
        if (array_key_exists("error", $result)) {
            return ["error" => $result["error"]];
        }
        return ["save_impr_text" => $result["success"]];
    }

    // =========================================================================
    // New Phase 2 Methods
    // =========================================================================

    /**
     * Set display mode settings for a text.
     *
     * @param int        $textId      Text ID
     * @param int|null   $annotations Annotation mode (0=none, 1=translations, 2=romanization, 3=both)
     * @param bool|null  $romanization Whether to show romanization
     * @param bool|null  $translation  Whether to show translation
     *
     * @return array{updated: bool, error?: string}
     */
    public function setDisplayMode(int $textId, ?int $annotations, ?bool $romanization, ?bool $translation): array
    {
        $tbpref = Globals::getTablePrefix();

        // Validate text exists
        $exists = Connection::preparedFetchValue(
            "SELECT COUNT(TxID) AS value FROM {$tbpref}texts WHERE TxID = ?",
            [$textId]
        );

        if ((int)$exists === 0) {
            return ['updated' => false, 'error' => 'Text not found'];
        }

        // Save settings
        if ($annotations !== null) {
            Settings::save('set-text-h-annotations', (string)$annotations);
        }

        if ($romanization !== null) {
            Settings::save('set-display-romanization', $romanization ? '1' : '0');
        }

        if ($translation !== null) {
            Settings::save('set-display-translation', $translation ? '1' : '0');
        }

        return ['updated' => true];
    }

    /**
     * Mark all unknown words in a text as well-known.
     *
     * @param int $textId Text ID
     *
     * @return array{count: int, words?: array}
     */
    public function markAllWellKnown(int $textId): array
    {
        list($count, $wordsData) = $this->wordService->markAllWordsWithStatus($textId, 99);
        return [
            'count' => $count,
            'words' => $wordsData
        ];
    }

    /**
     * Mark all unknown words in a text as ignored.
     *
     * @param int $textId Text ID
     *
     * @return array{count: int, words?: array}
     */
    public function markAllIgnored(int $textId): array
    {
        list($count, $wordsData) = $this->wordService->markAllWordsWithStatus($textId, 98);
        return [
            'count' => $count,
            'words' => $wordsData
        ];
    }

    // =========================================================================
    // New API Response Formatters
    // =========================================================================

    /**
     * Format response for setting display mode.
     *
     * @param int   $textId Text ID
     * @param array $params Display mode parameters
     *
     * @return array{updated: bool, error?: string}
     */
    public function formatSetDisplayMode(int $textId, array $params): array
    {
        $annotations = isset($params['annotations']) ? (int)$params['annotations'] : null;
        $romanization = isset($params['romanization']) ? filter_var($params['romanization'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
        $translation = isset($params['translation']) ? filter_var($params['translation'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        return $this->setDisplayMode($textId, $annotations, $romanization, $translation);
    }

    /**
     * Format response for marking all words as well-known.
     *
     * @param int $textId Text ID
     *
     * @return array{count: int, words?: array}
     */
    public function formatMarkAllWellKnown(int $textId): array
    {
        return $this->markAllWellKnown($textId);
    }

    /**
     * Format response for marking all words as ignored.
     *
     * @param int $textId Text ID
     *
     * @return array{count: int, words?: array}
     */
    public function formatMarkAllIgnored(int $textId): array
    {
        return $this->markAllIgnored($textId);
    }
}

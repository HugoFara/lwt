<?php

namespace Lwt\Api\V1\Handlers;

use Lwt\Database\Connection;
use Lwt\Database\Escaping;

/**
 * Handler for text-related API operations.
 *
 * Extracted from api_v1.php lines 262-373.
 */
class TextHandler
{
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
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        Connection::execute(
            "UPDATE {$tbpref}texts
            SET TxPosition = $position
            WHERE TxID = $textid",
            ""
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
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        Connection::execute(
            "UPDATE {$tbpref}texts
            SET TxAudioPosition = $audioposition
            WHERE TxID = $textid",
            ""
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
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        $ann = (string) Connection::fetchValue(
            "SELECT TxAnnotatedText AS value
            FROM {$tbpref}texts
            WHERE TxID = $textid"
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
        Connection::execute(
            "UPDATE {$tbpref}texts
            SET TxAnnotatedText = " .
            Escaping::toSqlSyntax(implode("\n", $items)) . "
            WHERE TxID = $textid",
            ""
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
}

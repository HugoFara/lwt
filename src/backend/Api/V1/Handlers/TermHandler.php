<?php

namespace Lwt\Api\V1\Handlers;

use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Services\WordStatusService;

/**
 * Handler for term/word-related API operations.
 *
 * Extracted from api_v1.php lines 27-258.
 */
class TermHandler
{
    /**
     * Add the translation for a new term.
     *
     * @param string $text Associated text
     * @param int    $lang Language ID
     * @param string $data Translation
     *
     * @return array{0: int, 1: string}|string [new word ID, lowercase $text] if success, error message otherwise
     */
    public function addNewTermTranslation(string $text, int $lang, string $data): array|string
    {
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        $textlc = mb_strtolower($text, 'UTF-8');
        $dummy = Connection::execute(
            "INSERT INTO {$tbpref}words (
                WoLgID, WoTextLC, WoText, WoStatus, WoTranslation,
                WoSentence, WoRomanization, WoStatusChanged,
                " . WordStatusService::makeScoreRandomInsertUpdate('iv') . '
            ) VALUES( ' .
            $lang . ', ' .
            Escaping::toSqlSyntax($textlc) . ', ' .
            Escaping::toSqlSyntax($text) . ', 1, ' .
            Escaping::toSqlSyntax($data) . ', ' .
            Escaping::toSqlSyntax('') . ', ' .
            Escaping::toSqlSyntax('') . ', NOW(), ' .
            WordStatusService::makeScoreRandomInsertUpdate('id') . ')',
            ""
        );
        if (!is_numeric($dummy)) {
            return $dummy;
        }
        if ((int)$dummy != 1) {
            return "Error: $dummy rows affected, expected 1!";
        }
        $wid = Connection::lastInsertId();
        Connection::query(
            "UPDATE {$tbpref}textitems2
            SET Ti2WoID = $wid
            WHERE Ti2LgID = $lang AND LOWER(Ti2Text) = " .
            Escaping::toSqlSyntaxNoTrimNoNull($textlc)
        );
        return array($wid, $textlc);
    }

    /**
     * Edit the translation for an existing term.
     *
     * @param int    $wid       Word ID
     * @param string $newTrans New translation
     *
     * @return string WoTextLC, lowercase version of the word
     */
    public function editTermTranslation(int $wid, string $newTrans): string
    {
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        $oldtrans = (string) Connection::fetchValue(
            "SELECT WoTranslation AS value
            FROM {$tbpref}words
            WHERE WoID = $wid"
        );

        $oldtransarr = preg_split('/[' . \get_sepas() . ']/u', $oldtrans);
        if ($oldtransarr === false) {
            return (string)Connection::fetchValue(
                "SELECT WoTextLC AS value
                FROM {$tbpref}words
                WHERE WoID = $wid"
            );
        }
        array_walk($oldtransarr, '\trim_value');

        if (!in_array($newTrans, $oldtransarr)) {
            if (trim($oldtrans) == '' || trim($oldtrans) == '*') {
                $oldtrans = $newTrans;
            } else {
                $oldtrans .= ' ' . \get_first_sepa() . ' ' . $newTrans;
            }
            Connection::execute(
                "UPDATE {$tbpref}words
                SET WoTranslation = " . Escaping::toSqlSyntax($oldtrans) .
                " WHERE WoID = $wid",
                ""
            );
        }
        return (string)Connection::fetchValue(
            "SELECT WoTextLC AS value
            FROM {$tbpref}words
            WHERE WoID = $wid"
        );
    }

    /**
     * Edit term translation if it exists.
     *
     * @param int    $wid       Word ID
     * @param string $newTrans New translation
     *
     * @return string Term in lower case, or error message if term does not exist
     */
    public function checkUpdateTranslation(int $wid, string $newTrans): string
    {
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        $cntWords = (int)Connection::fetchValue(
            "SELECT COUNT(WoID) AS value
            FROM {$tbpref}words
            WHERE WoID = $wid"
        );
        if ($cntWords == 1) {
            return $this->editTermTranslation($wid, $newTrans);
        }
        return "Error: " . $cntWords . " word ID found!";
    }

    /**
     * Force a term to get a new status.
     *
     * @param int $wid    ID of the word to edit
     * @param int $status New status to set
     *
     * @return int|string Number of affected rows or error message
     */
    public function setWordStatus(int $wid, int $status): int|string
    {
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        $m1 = Connection::execute(
            "UPDATE {$tbpref}words
            SET WoStatus = $status, WoStatusChanged = NOW()," .
            WordStatusService::makeScoreRandomInsertUpdate('u') . "
            WHERE WoID = $wid",
            ''
        );
        return $m1;
    }

    /**
     * Check the consistency of the new status.
     *
     * @param int  $oldstatus Old status
     * @param bool $up        True if status should incremented, false if decrementation needed
     *
     * @return int New status in the good number range (1-5, 98, or 99)
     */
    public function getNewStatus(int $oldstatus, bool $up): int
    {
        $currstatus = $oldstatus;
        if ($up) {
            $currstatus++;
            if ($currstatus == 99) {
                $currstatus = 1;
            } elseif ($currstatus == 6) {
                $currstatus = 99;
            }
        } else {
            $currstatus--;
            if ($currstatus == 98) {
                $currstatus = 5;
            } elseif ($currstatus == 0) {
                $currstatus = 98;
            }
        }
        return $currstatus;
    }

    /**
     * Save the new word status to the database, return the controls.
     *
     * @param int $wid        Word ID
     * @param int $currstatus Current status in the good value range.
     *
     * @return string|null HTML-formatted string with plus/minus controls if a success.
     */
    public function updateWordStatus(int $wid, int $currstatus): ?string
    {
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        if (($currstatus >= 1 && $currstatus <= 5) || $currstatus == 99 || $currstatus == 98) {
            $m1 = (int)$this->setWordStatus($wid, $currstatus);
            if ($m1 == 1) {
                $currstatus = Connection::fetchValue(
                    "SELECT WoStatus AS value FROM {$tbpref}words WHERE WoID = $wid"
                );
                if (!isset($currstatus)) {
                    return null;
                }
                return \make_status_controls_test_table(1, (int)$currstatus, $wid);
            }
        }
        return null;
    }

    /**
     * Do a word status change.
     *
     * @param int  $wid Word ID
     * @param bool $up  Should the status be incremeted or decremented
     *
     * @return string HTML-formatted string for increments
     */
    public function incrementTermStatus(int $wid, bool $up): string
    {
        $tbpref = \Lwt\Core\Globals::getTablePrefix();

        $tempstatus = Connection::fetchValue(
            "SELECT WoStatus as value
            FROM {$tbpref}words
            WHERE WoID = $wid"
        );
        if (!isset($tempstatus)) {
            return '';
        }
        $currstatus = $this->getNewStatus((int)$tempstatus, $up);
        $formatted = $this->updateWordStatus($wid, $currstatus);
        if ($formatted === null) {
            return '';
        }
        return $formatted;
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for updating translation.
     *
     * @param int    $termId     Term ID
     * @param string $translation New translation
     *
     * @return array{update?: string, error?: string}
     */
    public function formatUpdateTranslation(int $termId, string $translation): array
    {
        $result = $this->checkUpdateTranslation($termId, trim($translation));
        if (str_starts_with($result, "Error")) {
            return ["error" => $result];
        }
        return ["update" => $result];
    }

    /**
     * Format response for adding translation.
     *
     * @param string $termText    Term text
     * @param int    $lgId        Language ID
     * @param string $translation Translation
     *
     * @return array{error?: string, add?: string, term_id?: int, term_lc?: string}
     */
    public function formatAddTranslation(string $termText, int $lgId, string $translation): array
    {
        $text = trim($termText);
        $result = $this->addNewTermTranslation($text, $lgId, trim($translation));

        if (is_array($result)) {
            return [
                "term_id" => $result[0],
                "term_lc" => $result[1]
            ];
        } elseif ($result == mb_strtolower($text, 'UTF-8')) {
            return ["add" => $result];
        }
        return ["error" => $result];
    }

    /**
     * Format response for incrementing term status.
     *
     * @param int  $termId   Term ID
     * @param bool $statusUp Whether to increment (true) or decrement (false)
     *
     * @return array{increment?: string, error?: string}
     */
    public function formatIncrementStatus(int $termId, bool $statusUp): array
    {
        $result = $this->incrementTermStatus($termId, $statusUp);
        if ($result == '') {
            return ["error" => ''];
        }
        return ["increment" => $result];
    }

    /**
     * Format response for setting term status.
     *
     * @param int $termId Term ID
     * @param int $status New status
     *
     * @return array{error?: string, set?: int}
     */
    public function formatSetStatus(int $termId, int $status): array
    {
        $result = $this->setWordStatus($termId, $status);
        if (is_numeric($result)) {
            return ["set" => (int)$result];
        }
        return ["error" => $result];
    }
}

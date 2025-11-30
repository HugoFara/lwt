<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Database\Connection;
use Lwt\Services\TestService;
use Lwt\Services\WordStatusService;

require_once __DIR__ . '/../../../Services/TestService.php';
require_once __DIR__ . '/../../../Services/WordStatusService.php';

/**
 * Handler for review/test-related API operations.
 *
 * Extracted from api_v1.php lines 1157-1259.
 */
class ReviewHandler
{
    private TestService $testService;

    public function __construct()
    {
        $this->testService = new TestService();
    }

    /**
     * Get the next word to test as structured data.
     *
     * @param string $testsql   SQL projection query
     * @param bool   $wordMode  Test is in word mode
     * @param int    $lgid      Language ID
     * @param string $wordregex Word selection regular expression
     * @param int    $testtype  Test type
     *
     * @return array{word_id: int|string, solution?: string, word_text: string, group: string}
     */
    public function getWordTestData(string $testsql, bool $wordMode, int $lgid, string $wordregex, int $testtype): array
    {
        $wordRecord = \do_test_get_word($testsql);
        if (empty($wordRecord)) {
            return [
                "word_id" => 0,
                "word_text" => '',
                "group" => ''
            ];
        }
        if ($wordMode) {
            $sent = "{" . $wordRecord['WoText'] . "}";
        } else {
            list($sent, ) = \do_test_test_sentence(
                $wordRecord['WoID'],
                $lgid,
                $wordRecord['WoTextLC']
            );
            if ($sent === null) {
                $sent = "{" . $wordRecord['WoText'] . "}";
            }
        }
        list($htmlSentence, $save) = \do_test_get_term_test(
            $wordRecord,
            $sent,
            $testtype,
            $wordMode,
            $wordregex
        );
        $solution = \get_test_solution($testtype, $wordRecord, $wordMode, $save);

        return [
            "word_id" => $wordRecord['WoID'],
            "solution" => $solution,
            "word_text" => $save,
            "group" => $htmlSentence
        ];
    }

    /**
     * Get the next word to test based on request parameters.
     *
     * @param array $params Array with the fields {
     *                      test_key: string, selection: string, word_mode: bool,
     *                      lg_id: int, word_regex: string, type: int
     *                      }
     *
     * @return array{word_id: int|string, solution?: string, word_text: string, group: string}
     */
    public function wordTestAjax(array $params): array
    {
        $testSql = $this->testService->getTestSql(
            $params['test_key'],
            $params['selection']
        );
        return $this->getWordTestData(
            $testSql,
            filter_var($params['word_mode'], FILTER_VALIDATE_BOOLEAN),
            (int)$params['lg_id'],
            $params['word_regex'],
            (int)$params['type']
        );
    }

    /**
     * Return the number of reviews for tomorrow.
     *
     * @param array $params Array with the fields "test_key" and "selection"
     *
     * @return array{count: int}
     */
    public function tomorrowTestCount(array $params): array
    {
        $testSql = $this->testService->getTestSql(
            $params['test_key'],
            $params['selection']
        );
        return [
            "count" => \do_test_get_tomorrow_tests_count($testSql)
        ];
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for getting next word test.
     *
     * @param array $params Request parameters
     *
     * @return array{word_id: int|string, solution?: string, word_text: string, group: string}
     */
    public function formatNextWord(array $params): array
    {
        return $this->wordTestAjax($params);
    }

    /**
     * Format response for tomorrow count.
     *
     * @param array $params Request parameters
     *
     * @return array{count: int}
     */
    public function formatTomorrowCount(array $params): array
    {
        return $this->tomorrowTestCount($params);
    }

    // =========================================================================
    // New Phase 2 Methods
    // =========================================================================

    /**
     * Update word status during review/test mode.
     *
     * Supports both explicit status setting and relative changes (+1/-1).
     *
     * @param int      $wordId Word ID
     * @param int|null $status Explicit status (1-5, 98, 99), null if using change
     * @param int|null $change Status change amount (+1 or -1), null if using explicit status
     *
     * @return array{status?: int, controls?: string, error?: string}
     */
    public function updateReviewStatus(int $wordId, ?int $status, ?int $change): array
    {
        $tbpref = \Lwt\Core\Globals::getTablePrefix();

        // Get current status
        $currentStatus = Connection::fetchValue(
            "SELECT WoStatus AS value FROM {$tbpref}words WHERE WoID = $wordId"
        );

        if ($currentStatus === null) {
            return ['error' => 'Word not found'];
        }

        $currentStatus = (int)$currentStatus;
        $newStatus = $currentStatus;

        if ($status !== null) {
            // Explicit status - validate it
            if (!in_array($status, [1, 2, 3, 4, 5, 98, 99])) {
                return ['error' => 'Invalid status value'];
            }
            $newStatus = $status;
        } elseif ($change !== null) {
            // Relative change
            if ($change > 0) {
                // Increment
                $newStatus = $currentStatus + 1;
                if ($newStatus == 6) {
                    $newStatus = 99;  // 5 -> 99 (well-known)
                } elseif ($newStatus == 100) {
                    $newStatus = 1;   // 99 -> 1 (wrap around)
                }
            } else {
                // Decrement
                $newStatus = $currentStatus - 1;
                if ($newStatus == 0) {
                    $newStatus = 98;  // 1 -> 98 (ignored)
                } elseif ($newStatus == 97) {
                    $newStatus = 5;   // 98 -> 5 (wrap around)
                }
            }
        } else {
            return ['error' => 'Must provide either status or change'];
        }

        // Update the status
        $result = Connection::execute(
            "UPDATE {$tbpref}words
             SET WoStatus = $newStatus, WoStatusChanged = NOW(), " .
            WordStatusService::makeScoreRandomInsertUpdate('u') . "
             WHERE WoID = $wordId",
            ''
        );

        if (!is_numeric($result) || (int)$result !== 1) {
            return ['error' => 'Failed to update status'];
        }

        // Return the new status and controls HTML
        $controls = \make_status_controls_test_table(1, $newStatus, $wordId);

        return [
            'status' => $newStatus,
            'controls' => $controls
        ];
    }

    /**
     * Format response for updating review status.
     *
     * @param array $params Request parameters with word_id, and either status or change
     *
     * @return array{status?: int, controls?: string, error?: string}
     */
    public function formatUpdateStatus(array $params): array
    {
        $wordId = (int)($params['word_id'] ?? 0);
        if ($wordId === 0) {
            return ['error' => 'word_id is required'];
        }

        $status = isset($params['status']) ? (int)$params['status'] : null;
        $change = isset($params['change']) ? (int)$params['change'] : null;

        return $this->updateReviewStatus($wordId, $status, $change);
    }
}

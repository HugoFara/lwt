<?php

namespace Lwt\Api\V1\Handlers;

/**
 * Handler for review/test-related API operations.
 *
 * Extracted from api_v1.php lines 1157-1259.
 */
class ReviewHandler
{
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
        $testSql = \do_test_test_get_projection(
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
        $testSql = \do_test_test_get_projection(
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
}

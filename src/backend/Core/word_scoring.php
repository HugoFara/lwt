<?php

/**
 * \file
 * \brief Word scoring SQL formulas.
 *
 * Functions for generating SQL formulas used in word scoring and review scheduling.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-word-scoring.html
 * @since    2.10.0-fork Split from kernel_utility.php
 */

/**
 * Make a random score for a new word.
 *
 * @param 'iv'|'id'|'u'|string $type Type of insertion
 *                                   * 'iv': Keys only (TodayScore, Tomorrow, Random)
 *                                   * 'id': Values only
 *                                   * 'u': Key = value pairs
 *
 * @return string SQL code to use
 */
function make_score_random_insert_update($type): string
{
    // $type='iv'/'id'/'u'
    if ($type == 'iv') {
        return ' WoTodayScore, WoTomorrowScore, WoRandom ';
    }
    if ($type == 'id') {
        return ' ' . getsqlscoreformula(2) . ', ' . getsqlscoreformula(3) . ', RAND() ';
    }
    if ($type == 'u') {
        return ' WoTodayScore = ' . getsqlscoreformula(2) . ', WoTomorrowScore = ' . getsqlscoreformula(3) . ', WoRandom = RAND() ';
    }
    return '';
}

/**
 * SQL formula for computing score.
 *
 * @param int $method Score for tomorrow (2), the day after it (3) or never (any value).
 *
 * @psalm-return '
        GREATEST(-125, CASE
            WHEN WoStatus > 5 THEN 100
            WHEN WoStatus = 1 THEN ROUND(-7 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 2 THEN ROUND(6.9 - 3.5 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 3 THEN ROUND(20 - 2.3 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 4 THEN ROUND(46.4 - 1.75 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 5 THEN ROUND(100 - 1.4 * DATEDIFF(NOW(),WoStatusChanged))
        END)'|'
        GREATEST(-125, CASE
            WHEN WoStatus > 5 THEN 100
            WHEN WoStatus = 1 THEN ROUND(-7 -7 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 2 THEN ROUND(3.4 - 3.5 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 3 THEN ROUND(17.7 - 2.3 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 4 THEN ROUND(44.65 - 1.75 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 5 THEN ROUND(98.6 - 1.4 * DATEDIFF(NOW(),WoStatusChanged))
        END)'|'0'
 */
function getsqlscoreformula($method): string
{
    //
    // Formula: {{{2.4^{Status}+Status-Days-1} over Status -2.4} over 0.14325248}

    if ($method == 3) {
        return '
        GREATEST(-125, CASE
            WHEN WoStatus > 5 THEN 100
            WHEN WoStatus = 1 THEN ROUND(-7 -7 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 2 THEN ROUND(3.4 - 3.5 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 3 THEN ROUND(17.7 - 2.3 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 4 THEN ROUND(44.65 - 1.75 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 5 THEN ROUND(98.6 - 1.4 * DATEDIFF(NOW(),WoStatusChanged))
        END)';
    }
    if ($method == 2) {
        return '
        GREATEST(-125, CASE
            WHEN WoStatus > 5 THEN 100
            WHEN WoStatus = 1 THEN ROUND(-7 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 2 THEN ROUND(6.9 - 3.5 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 3 THEN ROUND(20 - 2.3 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 4 THEN ROUND(46.4 - 1.75 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 5 THEN ROUND(100 - 1.4 * DATEDIFF(NOW(),WoStatusChanged))
        END)';
    }
    return '0';
}

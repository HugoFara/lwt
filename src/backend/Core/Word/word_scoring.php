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
 * @since    3.0.0 Split from kernel_utility.php
 */

/**
 * SQL formula for computing today's score.
 *
 * Formula: {{{2.4^{Status}+Status-Days-1} over Status -2.4} over 0.14325248}
 */
const SCORE_FORMULA_TODAY = '
    GREATEST(-125, CASE
        WHEN WoStatus > 5 THEN 100
        WHEN WoStatus = 1 THEN ROUND(-7 * DATEDIFF(NOW(),WoStatusChanged))
        WHEN WoStatus = 2 THEN ROUND(6.9 - 3.5 * DATEDIFF(NOW(),WoStatusChanged))
        WHEN WoStatus = 3 THEN ROUND(20 - 2.3 * DATEDIFF(NOW(),WoStatusChanged))
        WHEN WoStatus = 4 THEN ROUND(46.4 - 1.75 * DATEDIFF(NOW(),WoStatusChanged))
        WHEN WoStatus = 5 THEN ROUND(100 - 1.4 * DATEDIFF(NOW(),WoStatusChanged))
    END)';

/**
 * SQL formula for computing tomorrow's score.
 */
const SCORE_FORMULA_TOMORROW = '
    GREATEST(-125, CASE
        WHEN WoStatus > 5 THEN 100
        WHEN WoStatus = 1 THEN ROUND(-7 -7 * DATEDIFF(NOW(),WoStatusChanged))
        WHEN WoStatus = 2 THEN ROUND(3.4 - 3.5 * DATEDIFF(NOW(),WoStatusChanged))
        WHEN WoStatus = 3 THEN ROUND(17.7 - 2.3 * DATEDIFF(NOW(),WoStatusChanged))
        WHEN WoStatus = 4 THEN ROUND(44.65 - 1.75 * DATEDIFF(NOW(),WoStatusChanged))
        WHEN WoStatus = 5 THEN ROUND(98.6 - 1.4 * DATEDIFF(NOW(),WoStatusChanged))
    END)';

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
function make_score_random_insert_update(string $type): string
{
    return match ($type) {
        'iv' => ' WoTodayScore, WoTomorrowScore, WoRandom ',
        'id' => ' ' . SCORE_FORMULA_TODAY . ', ' . SCORE_FORMULA_TOMORROW . ', RAND() ',
        'u' => ' WoTodayScore = ' . SCORE_FORMULA_TODAY . ', WoTomorrowScore = ' . SCORE_FORMULA_TOMORROW . ', WoRandom = RAND() ',
        default => '',
    };
}

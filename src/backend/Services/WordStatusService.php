<?php

/**
 * Word Status Service - Word status definitions and scoring
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

/**
 * Service class for word status definitions and scoring.
 *
 * Contains status definitions and SQL formulas for word scoring.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class WordStatusService
{
    /**
     * SQL formula for computing today's score.
     *
     * Formula: {{{2.4^{Status}+Status-Days-1} over Status -2.4} over 0.14325248}
     */
    public const SCORE_FORMULA_TODAY = '
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
    public const SCORE_FORMULA_TOMORROW = '
        GREATEST(-125, CASE
            WHEN WoStatus > 5 THEN 100
            WHEN WoStatus = 1 THEN ROUND(-7 -7 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 2 THEN ROUND(3.4 - 3.5 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 3 THEN ROUND(17.7 - 2.3 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 4 THEN ROUND(44.65 - 1.75 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 5 THEN ROUND(98.6 - 1.4 * DATEDIFF(NOW(),WoStatusChanged))
        END)';

    /**
     * @var array<int, array{abbr: string, name: string}>|null Cached statuses
     */
    private static ?array $statuses = null;

    /**
     * Return an associative array of all possible statuses.
     *
     * @return array<int, array{abbr: string, name: string}>
     *         Statuses, keys are 1, 2, 3, 4, 5, 98, 99.
     *         Values are associative arrays of keys abbr and name
     */
    public static function getStatuses(): array
    {
        if (self::$statuses === null) {
            self::$statuses = [
                1 => ["abbr" => "1", "name" => "Learning"],
                2 => ["abbr" => "2", "name" => "Learning"],
                3 => ["abbr" => "3", "name" => "Learning"],
                4 => ["abbr" => "4", "name" => "Learning"],
                5 => ["abbr" => "5", "name" => "Learned"],
                99 => ["abbr" => "WKn", "name" => "Well Known"],
                98 => ["abbr" => "Ign", "name" => "Ignored"],
            ];
        }
        return self::$statuses;
    }

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
    public static function makeScoreRandomInsertUpdate(string $type): string
    {
        return match ($type) {
            'iv' => ' WoTodayScore, WoTomorrowScore, WoRandom ',
            'id' => ' ' . self::SCORE_FORMULA_TODAY . ', ' . self::SCORE_FORMULA_TOMORROW . ', RAND() ',
            'u' => ' WoTodayScore = ' . self::SCORE_FORMULA_TODAY . ', WoTomorrowScore = ' . self::SCORE_FORMULA_TOMORROW . ', WoRandom = RAND() ',
            default => '',
        };
    }

    /**
     * Check if a status is valid.
     *
     * @param int $status Status to check
     *
     * @return bool True if valid status
     */
    public static function isValidStatus(int $status): bool
    {
        return isset(self::getStatuses()[$status]);
    }

    /**
     * Get status name.
     *
     * @param int $status Status value
     *
     * @return string Status name or empty if not found
     */
    public static function getStatusName(int $status): string
    {
        $statuses = self::getStatuses();
        return $statuses[$status]['name'] ?? '';
    }

    /**
     * Get status abbreviation.
     *
     * @param int $status Status value
     *
     * @return string Status abbreviation or empty if not found
     */
    public static function getStatusAbbr(int $status): string
    {
        $statuses = self::getStatuses();
        return $statuses[$status]['abbr'] ?? '';
    }
}

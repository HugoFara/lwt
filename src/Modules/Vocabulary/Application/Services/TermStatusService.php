<?php

declare(strict_types=1);

/**
 * Term Status Service
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

namespace Lwt\Modules\Vocabulary\Application\Services;

use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus;

/**
 * Service class for term status definitions and scoring.
 *
 * Contains status definitions, SQL formulas for scoring,
 * and utility methods for status management.
 *
 * @since 3.0.0
 */
class TermStatusService
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
     * Decay rates per status level (score decrease per day).
     */
    private const DECAY_RATES = [
        1 => 7.0,
        2 => 3.5,
        3 => 2.3,
        4 => 1.75,
        5 => 1.4,
    ];

    /**
     * Base scores for each status level (day 0).
     */
    private const BASE_SCORES = [
        1 => 0.0,
        2 => 6.9,
        3 => 20.0,
        4 => 46.4,
        5 => 100.0,
    ];

    /**
     * @var array<int, array{abbr: string, name: string}>|null Cached statuses
     */
    private static ?array $statuses = null;

    /**
     * Return an associative array of all possible statuses.
     *
     * @return array<int, array{abbr: string, name: string}>
     *         Keys are 1, 2, 3, 4, 5, 98, 99.
     *         Values are associative arrays with abbr and name.
     */
    public static function getStatuses(): array
    {
        if (self::$statuses === null) {
            self::$statuses = [
                TermStatus::NEW => ["abbr" => "1", "name" => "Learning"],
                TermStatus::LEARNING_2 => ["abbr" => "2", "name" => "Learning"],
                TermStatus::LEARNING_3 => ["abbr" => "3", "name" => "Learning"],
                TermStatus::LEARNING_4 => ["abbr" => "4", "name" => "Learning"],
                TermStatus::LEARNED => ["abbr" => "5", "name" => "Learned"],
                TermStatus::WELL_KNOWN => ["abbr" => "WKn", "name" => "Well Known"],
                TermStatus::IGNORED => ["abbr" => "Ign", "name" => "Ignored"],
            ];
        }
        return self::$statuses;
    }

    /**
     * Generate SQL fragment for score columns in INSERT/UPDATE statements.
     *
     * @param 'iv'|'id'|'u'|string $type Type of SQL fragment:
     *                                   - 'iv': Column names for INSERT (WoTodayScore, WoTomorrowScore, WoRandom)
     *                                   - 'id': Values for INSERT (computed formulas)
     *                                   - 'u': SET clause for UPDATE (column = value pairs)
     *
     * @return string SQL code fragment
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

    /**
     * Calculate the current score for a term based on status and days since status change.
     *
     * @param int $status          Term status (1-5)
     * @param int $daysSinceChange Days since status was changed
     *
     * @return float Score value (-125 to 100)
     */
    public static function calculateScore(int $status, int $daysSinceChange): float
    {
        // Special statuses always return 100
        if ($status > 5) {
            return 100.0;
        }

        if (!isset(self::BASE_SCORES[$status]) || !isset(self::DECAY_RATES[$status])) {
            return 0.0;
        }

        $baseScore = self::BASE_SCORES[$status];
        $decayRate = self::DECAY_RATES[$status];

        $score = $baseScore - ($decayRate * $daysSinceChange);

        return max(-125.0, $score);
    }

    /**
     * Calculate tomorrow's score for a term.
     *
     * @param int $status          Term status (1-5)
     * @param int $daysSinceChange Days since status was changed
     *
     * @return float Tomorrow's score value (-125 to 100)
     */
    public static function calculateTomorrowScore(int $status, int $daysSinceChange): float
    {
        return self::calculateScore($status, $daysSinceChange + 1);
    }

    /**
     * Get the CSS color class for a status.
     *
     * @param int $status Status value
     *
     * @return string CSS color class name
     */
    public static function getStatusColor(int $status): string
    {
        return match ($status) {
            TermStatus::NEW => 'status1',
            TermStatus::LEARNING_2 => 'status2',
            TermStatus::LEARNING_3 => 'status3',
            TermStatus::LEARNING_4 => 'status4',
            TermStatus::LEARNED => 'status5',
            TermStatus::IGNORED => 'status98',
            TermStatus::WELL_KNOWN => 'status99',
            default => 'status0',
        };
    }

    /**
     * Get all learning statuses (1-5).
     *
     * @return int[]
     */
    public static function getLearningStatuses(): array
    {
        return [
            TermStatus::NEW,
            TermStatus::LEARNING_2,
            TermStatus::LEARNING_3,
            TermStatus::LEARNING_4,
            TermStatus::LEARNED,
        ];
    }

    /**
     * Get active learning statuses (1-4, not yet learned).
     *
     * @return int[]
     */
    public static function getActiveLearningStatuses(): array
    {
        return [
            TermStatus::NEW,
            TermStatus::LEARNING_2,
            TermStatus::LEARNING_3,
            TermStatus::LEARNING_4,
        ];
    }

    /**
     * Get known statuses (5 and 99).
     *
     * @return int[]
     */
    public static function getKnownStatuses(): array
    {
        return [
            TermStatus::LEARNED,
            TermStatus::WELL_KNOWN,
        ];
    }

    /**
     * Check if a status represents a learning term (not ignored, not well-known).
     *
     * @param int $status Status value
     *
     * @return bool
     */
    public static function isLearningStatus(int $status): bool
    {
        return $status >= 1 && $status <= 5;
    }

    /**
     * Check if a status represents a known term.
     *
     * @param int $status Status value
     *
     * @return bool
     */
    public static function isKnownStatus(int $status): bool
    {
        return $status === TermStatus::LEARNED || $status === TermStatus::WELL_KNOWN;
    }

    /**
     * Check if a status represents an ignored term.
     *
     * @param int $status Status value
     *
     * @return bool
     */
    public static function isIgnoredStatus(int $status): bool
    {
        return $status === TermStatus::IGNORED;
    }
}

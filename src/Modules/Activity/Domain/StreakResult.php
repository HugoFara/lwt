<?php

/**
 * Streak Result Value Object
 *
 * PHP version 8.2
 *
 * @category Lwt
 * @package  Lwt\Modules\Activity\Domain
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Activity\Domain;

/**
 * Value object for streak calculation results.
 *
 * @since 3.0.0
 */
final readonly class StreakResult
{
    /**
     * Constructor.
     *
     * @param int $currentStreak   Consecutive days ending today/yesterday
     * @param int $bestStreak      Longest consecutive run ever
     * @param int $totalActiveDays Total number of days with any activity
     */
    public function __construct(
        public int $currentStreak,
        public int $bestStreak,
        public int $totalActiveDays,
    ) {
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array{current_streak: int, best_streak: int, total_active_days: int}
     */
    public function toArray(): array
    {
        return [
            'current_streak' => $this->currentStreak,
            'best_streak' => $this->bestStreak,
            'total_active_days' => $this->totalActiveDays,
        ];
    }
}

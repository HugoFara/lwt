<?php

/**
 * Get Streak Statistics Use Case
 *
 * PHP version 8.2
 *
 * @category Lwt
 * @package  Lwt\Modules\Activity\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Activity\Application\UseCases;

use Lwt\Modules\Activity\Domain\ActivityRepositoryInterface;
use Lwt\Modules\Activity\Domain\StreakResult;

/**
 * Calculates current streak, best streak, and total active days.
 *
 * A streak is a run of consecutive calendar days with any activity.
 * The current streak only counts if the most recent active day
 * is today or yesterday.
 *
 * @since 3.0.0
 */
class GetStreakStatistics
{
    private ActivityRepositoryInterface $repository;

    /**
     * Constructor.
     *
     * @param ActivityRepositoryInterface $repository Activity repository
     */
    public function __construct(ActivityRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Execute the use case.
     *
     * @return StreakResult Streak statistics
     */
    public function execute(): StreakResult
    {
        $dates = $this->repository->getActiveDatesDescending();
        $totalActiveDays = count($dates);

        if ($totalActiveDays === 0) {
            return new StreakResult(0, 0, 0);
        }

        $today = new \DateTimeImmutable('today');
        $yesterday = $today->modify('-1 day');

        $currentStreak = 0;
        $bestStreak = 0;
        $streak = 0;
        /** @var \DateTimeImmutable|null $expectedDate */
        $expectedDate = null;

        foreach ($dates as $dateStr) {
            $date = new \DateTimeImmutable($dateStr);

            if ($expectedDate === null) {
                // First (most recent) date
                $streak = 1;
                $expectedDate = $date->modify('-1 day');
            } elseif ($date->format('Y-m-d') === $expectedDate->format('Y-m-d')) {
                $streak++;
                $expectedDate = $date->modify('-1 day');
            } else {
                // Gap found — save and reset
                $bestStreak = max($bestStreak, $streak);
                $streak = 1;
                $expectedDate = $date->modify('-1 day');
            }
        }
        $bestStreak = max($bestStreak, $streak);

        // Current streak only counts if the most recent day is today or yesterday
        $firstDate = new \DateTimeImmutable($dates[0]);
        if (
            $firstDate->format('Y-m-d') === $today->format('Y-m-d')
            || $firstDate->format('Y-m-d') === $yesterday->format('Y-m-d')
        ) {
            $currentStreak = $this->countConsecutiveFromStart($dates, $firstDate);
        }

        return new StreakResult($currentStreak, $bestStreak, $totalActiveDays);
    }

    /**
     * Count consecutive days starting from the first date in the list.
     *
     * @param list<string>       $dates     Dates descending
     * @param \DateTimeImmutable $firstDate The most recent date
     *
     * @return int Consecutive day count
     */
    private function countConsecutiveFromStart(array $dates, \DateTimeImmutable $firstDate): int
    {
        $count = 1;
        $expected = $firstDate->modify('-1 day');

        for ($i = 1; $i < count($dates); $i++) {
            $date = new \DateTimeImmutable($dates[$i]);
            if ($date->format('Y-m-d') !== $expected->format('Y-m-d')) {
                break;
            }
            $count++;
            $expected = $date->modify('-1 day');
        }

        return $count;
    }
}

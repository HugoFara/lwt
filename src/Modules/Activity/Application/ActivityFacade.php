<?php

/**
 * Activity Module Facade
 *
 * PHP version 8.2
 *
 * @category Lwt
 * @package  Lwt\Modules\Activity\Application
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Activity\Application;

use Lwt\Modules\Activity\Domain\ActivityRepositoryInterface;
use Lwt\Modules\Activity\Domain\StreakResult;
use Lwt\Modules\Activity\Application\UseCases\GetStreakStatistics;
use Lwt\Modules\Activity\Application\UseCases\GetCalendarHeatmapData;
use Lwt\Modules\Activity\Application\UseCases\GetTodaySummary;
use Lwt\Modules\Activity\Infrastructure\MySqlActivityRepository;

/**
 * Public API for the Activity module.
 *
 * @since 3.0.0
 */
class ActivityFacade
{
    private ActivityRepositoryInterface $repository;

    /**
     * Constructor.
     *
     * @param ActivityRepositoryInterface|null $repository Activity repository
     */
    public function __construct(?ActivityRepositoryInterface $repository = null)
    {
        $this->repository = $repository ?? new MySqlActivityRepository();
    }

    /**
     * Increment terms-created counter for today.
     *
     * @param int $count Number to add
     *
     * @return void
     */
    public function incrementTermsCreated(int $count = 1): void
    {
        $this->repository->incrementTermsCreated($count);
    }

    /**
     * Increment terms-reviewed counter for today.
     *
     * @param int $count Number to add
     *
     * @return void
     */
    public function incrementTermsReviewed(int $count = 1): void
    {
        $this->repository->incrementTermsReviewed($count);
    }

    /**
     * Increment texts-read counter for today.
     *
     * @param int $count Number to add
     *
     * @return void
     */
    public function incrementTextsRead(int $count = 1): void
    {
        $this->repository->incrementTextsRead($count);
    }

    /**
     * Get streak statistics (current, best, total active days).
     *
     * @return StreakResult
     */
    public function getStreakStatistics(): StreakResult
    {
        return (new GetStreakStatistics($this->repository))->execute();
    }

    /**
     * Get calendar heatmap data for the last 365 days.
     *
     * @return array<string, array{total: int}>
     */
    public function getCalendarHeatmapData(): array
    {
        return (new GetCalendarHeatmapData($this->repository))->execute();
    }

    /**
     * Get today's activity summary.
     *
     * @return array{terms_created: int, terms_reviewed: int, texts_read: int}
     */
    public function getTodaySummary(): array
    {
        return (new GetTodaySummary($this->repository))->execute();
    }
}

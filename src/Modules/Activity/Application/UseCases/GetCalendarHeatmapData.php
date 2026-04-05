<?php

/**
 * Get Calendar Heatmap Data Use Case
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

/**
 * Returns daily activity totals for the last 365 days.
 *
 * Output is keyed by date string, ready for the frontend calendar heatmap.
 *
 * @since 3.0.0
 */
class GetCalendarHeatmapData
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
     * @return array<string, array{total: int}> Date (Y-m-d) => total activity
     */
    public function execute(): array
    {
        $endDate = date('Y-m-d');
        $timestamp = strtotime('-364 days');
        $startDate = $timestamp !== false ? date('Y-m-d', $timestamp) : $endDate;

        $rows = $this->repository->getActivityForDateRange($startDate, $endDate);

        $result = [];
        foreach ($rows as $row) {
            $total = $row['terms_created'] + $row['terms_reviewed'] + $row['texts_read'];
            if ($total > 0) {
                $result[$row['date']] = ['total' => $total];
            }
        }

        return $result;
    }
}

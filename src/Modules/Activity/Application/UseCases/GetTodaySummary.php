<?php

/**
 * Get Today Summary Use Case
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
 * Returns today's activity counters.
 *
 * @since 3.0.0
 */
class GetTodaySummary
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
     * @return array{terms_created: int, terms_reviewed: int, texts_read: int}
     */
    public function execute(): array
    {
        return $this->repository->getTodaySummary();
    }
}

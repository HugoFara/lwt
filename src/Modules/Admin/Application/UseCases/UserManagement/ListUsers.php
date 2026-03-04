<?php

declare(strict_types=1);

namespace Lwt\Modules\Admin\Application\UseCases\UserManagement;

use Lwt\Modules\User\Infrastructure\MySqlUserRepository;

class ListUsers
{
    private const ALLOWED_SORT_COLUMNS = [
        'username' => 'UsUsername',
        'email' => 'UsEmail',
        'role' => 'UsRole',
        'active' => 'UsIsActive',
        'created' => 'UsCreated',
        'last_login' => 'UsLastLogin',
    ];

    private const DEFAULT_SORT = 'UsUsername';
    private const DEFAULT_DIRECTION = 'ASC';

    private MySqlUserRepository $userRepository;

    public function __construct(MySqlUserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * List users with pagination and optional search.
     *
     * @param int    $page      Page number (1-based)
     * @param int    $perPage   Items per page
     * @param string $sortBy    Sort column key (from ALLOWED_SORT_COLUMNS)
     * @param string $direction Sort direction (ASC or DESC)
     * @param string $search    Optional search query
     *
     * @return array{items: \Lwt\Modules\User\Domain\User[], total: int,
     *               page: int, per_page: int, total_pages: int, statistics: array}
     */
    public function execute(
        int $page = 1,
        int $perPage = 20,
        string $sortBy = 'username',
        string $direction = 'ASC',
        string $search = ''
    ): array {
        $orderBy = self::ALLOWED_SORT_COLUMNS[$sortBy] ?? self::DEFAULT_SORT;
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : self::DEFAULT_DIRECTION;

        if ($search !== '') {
            $users = $this->userRepository->search($search, 500);
            $total = count($users);
            $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;
            $page = max(1, min($page, max(1, $totalPages)));
            $offset = ($page - 1) * $perPage;
            $items = array_slice($users, $offset, $perPage);

            return [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
                'statistics' => $this->userRepository->getStatistics(),
            ];
        }

        $result = $this->userRepository->findPaginated($page, $perPage, $orderBy, $direction);
        $result['statistics'] = $this->userRepository->getStatistics();

        return $result;
    }
}

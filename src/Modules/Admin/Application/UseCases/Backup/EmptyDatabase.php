<?php declare(strict_types=1);
/**
 * Empty Database Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Admin\Application\UseCases\Backup
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Admin\Application\UseCases\Backup;

use Lwt\Modules\Admin\Domain\BackupRepositoryInterface;

/**
 * Use case for emptying the database.
 *
 * Truncates all user data tables while preserving settings.
 *
 * @since 3.0.0
 */
class EmptyDatabase
{
    private BackupRepositoryInterface $repository;

    /**
     * Constructor.
     *
     * @param BackupRepositoryInterface $repository Backup repository
     */
    public function __construct(BackupRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Execute the use case.
     *
     * @return array{success: bool}
     */
    public function execute(): array
    {
        $this->repository->truncateUserTables();
        return ['success' => true];
    }
}

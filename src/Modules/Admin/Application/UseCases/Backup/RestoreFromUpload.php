<?php

/**
 * Restore From Upload Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Admin\Application\UseCases\Backup
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Admin\Application\UseCases\Backup;

use Lwt\Shared\Infrastructure\Globals;
use Lwt\Modules\Admin\Domain\BackupRepositoryInterface;

/**
 * Use case for restoring database from uploaded file.
 *
 * @since 3.0.0
 */
class RestoreFromUpload
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
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int}|null $fileData
     *        Validated file data from InputValidator::getUploadedFile()
     *
     * @return array{success: bool, error: ?string}
     */
    public function execute(?array $fileData): array
    {
        // Check if restore is enabled
        if (!Globals::isBackupRestoreEnabled()) {
            return [
                'success' => false,
                'error' => "Database restore is disabled. Set BACKUP_RESTORE_ENABLED=true in .env to enable."
            ];
        }

        if ($fileData === null) {
            return ['success' => false, 'error' => "No Restore file specified"];
        }

        $handle = @gzopen($fileData["tmp_name"], "r");
        if ($handle === false) {
            return ['success' => false, 'error' => "Restore file could not be opened"];
        }

        $message = $this->repository->restoreFromHandle($handle, "Database");
        // Restore::restoreFile reports outcome via a prose string. Treat
        // any "Error:" prefix as failure so the controller surfaces it
        // to the admin instead of claiming a successful restore — the
        // multi-user safety guard relies on this to refuse a wipe of
        // every account.
        if (str_starts_with($message, 'Error:')) {
            return ['success' => false, 'error' => $message];
        }
        return ['success' => true, 'error' => null];
    }
}

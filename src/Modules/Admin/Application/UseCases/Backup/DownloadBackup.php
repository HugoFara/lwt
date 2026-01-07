<?php declare(strict_types=1);
/**
 * Download Backup Use Case
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
 * Use case for generating and downloading LWT backup.
 *
 * @since 3.0.0
 */
class DownloadBackup
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
     * Execute the use case - generate and output backup file.
     *
     * @return void Outputs file and exits
     */
    public function execute(): void
    {
        $fname = "lwt-backup-exp_version-" . date('Y-m-d-H-i-s') . ".sql.gz";
        $out = "-- " . $fname . "\n";
        $out .= $this->repository->generateBackupSql();

        header('Content-Type: application/plain');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        echo gzencode($out);
        exit();
    }

    /**
     * Generate backup content without outputting.
     *
     * Useful for testing or alternative output methods.
     *
     * @return array{filename: string, content: string} Backup data
     */
    public function generate(): array
    {
        $fname = "lwt-backup-exp_version-" . date('Y-m-d-H-i-s') . ".sql.gz";
        $out = "-- " . $fname . "\n";
        $out .= $this->repository->generateBackupSql();

        return [
            'filename' => $fname,
            'content' => $out
        ];
    }
}

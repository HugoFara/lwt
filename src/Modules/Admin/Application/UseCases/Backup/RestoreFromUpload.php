<?php declare(strict_types=1);
/**
 * Restore From Upload Use Case
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
     * @param array $fileData $_FILES data for uploaded file
     *
     * @return string Status message
     */
    public function execute(array $fileData): string
    {
        if (
            !isset($fileData["thefile"]) ||
            $fileData["thefile"]["tmp_name"] == "" ||
            $fileData["thefile"]["error"] != 0
        ) {
            return "Error: No Restore file specified";
        }

        $handle = @gzopen($fileData["thefile"]["tmp_name"], "r");
        if ($handle === false) {
            return "Error: Restore file could not be opened";
        }

        return $this->repository->restoreFromHandle($handle, "Database");
    }
}

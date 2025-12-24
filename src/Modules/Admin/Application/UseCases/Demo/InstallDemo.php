<?php declare(strict_types=1);
/**
 * Install Demo Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Admin\Application\UseCases\Demo
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Admin\Application\UseCases\Demo;

use Lwt\Database\Restore;

/**
 * Use case for installing demo database.
 *
 * @since 3.0.0
 */
class InstallDemo
{
    /**
     * Execute the use case.
     *
     * @return string Status message
     */
    public function execute(): string
    {
        $file = getcwd() . '/db/seeds/demo.sql';

        if (!file_exists($file)) {
            return "Error: File '" . $file . "' does not exist";
        }

        $handle = fopen($file, "r");
        if ($handle === false) {
            return "Error: File '" . $file . "' could not be opened";
        }

        return Restore::restoreFile($handle, "Demo Database");
    }
}

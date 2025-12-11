<?php declare(strict_types=1);
/**
 * Demo Service - Business logic for demo database installation
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Services;

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Restore;

/**
 * Service class for installing demo database.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class DemoService
{
    /**
     * Database name.
     *
     * @var string
     */
    private string $dbname;

    /**
     * Constructor - initialize database settings.
     */
    public function __construct()
    {
        $this->dbname = Globals::getDatabaseName();
    }

    /**
     * Get prefix info for display.
     *
     * @return string HTML-safe prefix info
     */
    public function getPrefixInfo(): string
    {
        $tbpref = Globals::getTablePrefix();
        if ($tbpref == '') {
            return "(Default Table Set)";
        }
        return "(Table Set: <i>" . htmlspecialchars(substr($tbpref, 0, -1) ?? '', ENT_QUOTES, 'UTF-8') . "</i>)";
    }

    /**
     * Get database name.
     *
     * @return string Database name
     */
    public function getDatabaseName(): string
    {
        return $this->dbname;
    }

    /**
     * Get count of existing languages.
     *
     * @return int Language count
     */
    public function getLanguageCount(): int
    {
        $tbpref = Globals::getTablePrefix();
        return (int)Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$tbpref}languages"
        );
    }

    /**
     * Install demo database from file.
     *
     * @return string Status message
     */
    public function installDemo(): string
    {
        $file = getcwd() . '/db/seeds/demo.sql';
        if (!file_exists($file)) {
            return "Error: File ' . $file . ' does not exist";
        }

        $handle = fopen($file, "r");
        if ($handle === false) {
            return "Error: File ' . $file . ' could not be opened";
        }

        return Restore::restoreFile($handle, "Demo Database");
    }
}

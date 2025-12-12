<?php declare(strict_types=1);
/**
 * Server Data Service - Business logic for server information
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

use function Lwt\Core\getVersionNumber;

/**
 * Service class for retrieving server and database information.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class ServerDataService
{
    /**
     * Database name.
     *
     * @var string
     */
    private string $dbname;

    /**
     * Constructor - initialize settings.
     */
    public function __construct()
    {
        $this->dbname = Globals::getDatabaseName();
    }

    /**
     * Get all server data.
     *
     * @return array{
     *   db_name: string,
     *   db_prefix: string,
     *   db_size: float,
     *   server_soft: string,
     *   apache: string,
     *   php: string|false,
     *   mysql: string,
     *   lwt_version: string,
     *   server_location: string
     * }
     */
    public function getServerData(): array
    {
        $data = [];
        $data["db_name"] = $this->dbname;
        $data["db_prefix"] = Globals::getTablePrefix();
        $data["db_size"] = $this->getDatabaseSize();
        $data["server_soft"] = $_SERVER['SERVER_SOFTWARE'];
        $data["apache"] = $this->parseApacheVersion($data["server_soft"]);
        $data["php"] = phpversion();
        $data["mysql"] = (string)Connection::fetchValue("SELECT VERSION() AS value");
        $data["lwt_version"] = getVersionNumber();
        $data["server_location"] = $_SERVER['HTTP_HOST'];

        return $data;
    }

    /**
     * Get database size in MB.
     *
     * @return float Database size in MB
     */
    private function getDatabaseSize(): float
    {
        // Get the prefixed table names for all LWT tables
        $tableNames = [
            'archivedtexts', 'archtexttags', 'feedlinks', 'languages',
            'newsfeeds', 'sentences', 'settings', 'tags', 'tags2',
            'textitems2', 'texts', 'texttags', 'words', 'wordtags'
        ];

        $prefix = Globals::getTablePrefix();
        $prefixedTables = array_map(
            fn($table) => $prefix . $table,
            $tableNames
        );

        $placeholders = implode(', ', array_fill(0, count($prefixedTables), '?'));
        $bindings = array_merge([$this->dbname], $prefixedTables);

        $temp_size = Connection::preparedFetchValue(
            "SELECT ROUND(SUM(data_length+index_length)/1024/1024, 1) AS value
            FROM information_schema.TABLES
            WHERE table_schema = ?
            AND table_name IN ($placeholders)",
            $bindings
        );

        if ($temp_size === null) {
            return 0.0;
        }
        return floatval($temp_size);
    }

    /**
     * Parse Apache version from server software string.
     *
     * @param string $serverSoft Server software string
     *
     * @return string Apache version string
     */
    private function parseApacheVersion(string $serverSoft): string
    {
        if (str_starts_with($serverSoft, "Apache/")) {
            $temp_soft = explode(' ', $serverSoft);
            return $temp_soft[0];
        }
        return "Apache/?";
    }
}

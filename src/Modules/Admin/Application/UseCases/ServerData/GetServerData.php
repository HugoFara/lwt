<?php

/**
 * Get Server Data Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Admin\Application\UseCases\ServerData
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Admin\Application\UseCases\ServerData;

use Lwt\Core\ApplicationInfo;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Connection;

/**
 * Use case for getting server and database information.
 *
 * @since 3.0.0
 */
class GetServerData
{
    /**
     * Execute the use case.
     *
     * @return array{
     *   db_name: string,
     *   db_size: float,
     *   server_soft: string,
     *   apache: string,
     *   php: string|false,
     *   mysql: string,
     *   lwt_version: string,
     *   server_location: string
     * }
     */
    public function execute(): array
    {
        return [
            'db_name' => Globals::getDatabaseName(),
            'db_size' => $this->getDatabaseSize(),
            'server_soft' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'apache' => $this->parseApacheVersion($_SERVER['SERVER_SOFTWARE'] ?? ''),
            'php' => phpversion(),
            'mysql' => (string) Connection::fetchValue("SELECT VERSION() AS version", 'version'),
            'lwt_version' => ApplicationInfo::getVersionNumber(),
            'server_location' => $_SERVER['HTTP_HOST'] ?? 'localhost',
        ];
    }

    /**
     * Get database size in MB.
     *
     * @return float Database size in MB
     */
    private function getDatabaseSize(): float
    {
        $dbname = Globals::getDatabaseName();

        $tableNames = [
            'feed_links', 'languages', 'news_feeds', 'sentences', 'settings',
            'tags', 'text_tags', 'word_occurrences', 'texts', 'text_tag_map',
            'words', 'word_tag_map'
        ];

        $prefixedTables = array_map(
            fn($table) => Globals::table($table),
            $tableNames
        );

        $placeholders = implode(', ', array_fill(0, count($prefixedTables), '?'));
        $bindings = array_merge([$dbname], $prefixedTables);

        /** @var float|int|string|null $temp_size */
        $temp_size = Connection::preparedFetchValue(
            "SELECT ROUND(SUM(data_length+index_length)/1024/1024, 1) AS size_mb
            FROM information_schema.TABLES
            WHERE table_schema = ?
            AND table_name IN ($placeholders)",
            $bindings,
            'size_mb'
        );

        if ($temp_size === null) {
            return 0.0;
        }

        return (float) $temp_size;
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

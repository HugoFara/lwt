<?php declare(strict_types=1);
/**
 * Table Set Service - Business logic for table set management
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
use Lwt\Database\Settings;

/**
 * Service class for managing table sets (prefixes).
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TableSetService
{
    /**
     * Database table prefix.
     *
     * @var string
     */
    private string $tbpref;

    /**
     * Whether table prefix is fixed.
     *
     * @var bool
     */
    private bool $fixedTbpref;

    /**
     * Tables that make up a table set.
     *
     * @var string[]
     */
    private const TABLE_SET_TABLES = [
        'archivedtexts', 'archtexttags', 'languages', 'sentences', 'tags',
        'tags2', 'temptextitems', 'tempwords', 'textitems2', 'texts',
        'texttags', 'words', 'newsfeeds', 'feedlinks', 'wordtags', 'settings'
    ];

    /**
     * Constructor - initialize settings.
     */
    public function __construct()
    {
        global $fixed_tbpref;
        $this->tbpref = Globals::getTablePrefix();
        $this->fixedTbpref = $fixed_tbpref ?? false;
    }

    /**
     * Check if table prefix is fixed.
     *
     * @return bool True if prefix is fixed
     */
    public function isFixedPrefix(): bool
    {
        return $this->fixedTbpref;
    }

    /**
     * Get the current table prefix.
     *
     * @return string Current prefix
     */
    public function getCurrentPrefix(): string
    {
        return $this->tbpref;
    }

    /**
     * Get all available table set prefixes.
     *
     * @return string[] List of prefixes
     *
     * @psalm-return list<string>
     */
    public function getPrefixes(): array
    {
        return self::getAllPrefixes();
    }

    /**
     * Get all different database prefixes that are in use.
     *
     * This static method scans for all tables ending with '_settings'
     * to find available table set prefixes.
     *
     * @return string[] A list of prefixes
     *
     * @psalm-return list<string>
     */
    public static function getAllPrefixes(): array
    {
        $prefix = [];
        $res = Connection::query(
            str_replace(
                '_',
                "\\_",
                "SHOW TABLES LIKE '%\\_settings'"
            )
        );
        while ($row = \mysqli_fetch_row($res)) {
            $prefix[] = substr((string) $row[0], 0, -9);
        }
        \mysqli_free_result($res);
        return $prefix;
    }

    /**
     * Delete a table set.
     *
     * @param string $prefix Prefix of the table set to delete
     *
     * @return string Status message
     */
    public function deleteTableSet(string $prefix): string
    {
        if ($prefix === '-') {
            return '';
        }

        foreach (self::TABLE_SET_TABLES as $table) {
            Connection::execute("DROP TABLE {$prefix}_{$table}");
        }

        $message = 'Table Set "' . $prefix . '" deleted';

        // If we deleted the current table set, switch to default
        if ($prefix == substr($this->tbpref, 0, -1)) {
            Settings::lwtTableSet("current_table_prefix", "");
        }

        return $message;
    }

    /**
     * Create a new table set.
     *
     * @param string $prefix New prefix to create
     *
     * @return array{success: bool, message: string, redirect: bool}
     */
    public function createTableSet(string $prefix): array
    {
        $existingPrefixes = $this->getPrefixes();

        if (in_array($prefix, $existingPrefixes)) {
            return [
                'success' => false,
                'message' => 'Table Set "' . $prefix . '" already exists',
                'redirect' => false
            ];
        }

        Settings::lwtTableSet("current_table_prefix", $prefix);

        return [
            'success' => true,
            'message' => '',
            'redirect' => true
        ];
    }

    /**
     * Select an existing table set.
     *
     * @param string $prefix Prefix to select
     *
     * @return array{success: bool, redirect: bool}
     */
    public function selectTableSet(string $prefix): array
    {
        if ($prefix === '-') {
            return ['success' => false, 'redirect' => false];
        }

        Settings::lwtTableSet("current_table_prefix", $prefix);

        return ['success' => true, 'redirect' => true];
    }
}

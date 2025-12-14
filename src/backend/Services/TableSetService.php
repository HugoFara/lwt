<?php declare(strict_types=1);
/**
 * Table Set Service - Business logic for table set management
 *
 * This service manages table prefixes for the legacy multi-instance system.
 * When multi-user mode is enabled, this functionality is replaced by
 * user_id-based data isolation and this service becomes deprecated.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @deprecated 3.0.0 Use user_id-based isolation instead when multi-user mode is enabled.
 *             This service will be removed in a future version.
 */

namespace Lwt\Services;

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Settings;

/**
 * Service class for managing table sets (prefixes).
 *
 * This is a legacy service for the table-prefix-based multi-instance system.
 * When multi-user mode is enabled (MULTI_USER_ENABLED=true), user isolation
 * is handled via user_id columns and this service should not be used.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @deprecated 3.0.0 Replaced by user_id-based isolation in multi-user mode
 */
class TableSetService
{
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
        $this->fixedTbpref = Globals::isTablePrefixFixed();
    }

    /**
     * Check if table set management is available.
     *
     * Table set management is NOT available when:
     * - Multi-user mode is enabled (uses user_id isolation instead)
     * - Table prefix is fixed in .env
     *
     * @return bool True if table set management is available
     */
    public function isAvailable(): bool
    {
        // In multi-user mode, table sets are replaced by user_id isolation
        if (Globals::isMultiUserEnabled()) {
            return false;
        }

        // If prefix is fixed, management is not available
        if ($this->fixedTbpref) {
            return false;
        }

        return true;
    }

    /**
     * Check if multi-user mode is enabled.
     *
     * When multi-user mode is enabled, table set management is deprecated
     * and replaced by user_id-based data isolation.
     *
     * @return bool True if multi-user mode is enabled
     */
    public function isMultiUserMode(): bool
    {
        return Globals::isMultiUserEnabled();
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
        return Globals::getTablePrefix();
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

        // Note: Using raw SQL with interpolation here because we're explicitly
        // managing arbitrary table prefixes (not the current user's prefix).
        // This is DROP TABLE DDL on cross-prefix table sets.
        foreach (self::TABLE_SET_TABLES as $table) {
            $tableName = $prefix . '_' . $table;
            Connection::execute("DROP TABLE IF EXISTS " . $tableName);
        }

        $message = 'Table Set "' . $prefix . '" deleted';

        // If we deleted the current table set, switch to default
        if ($prefix == substr(Globals::getTablePrefix(), 0, -1)) {
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

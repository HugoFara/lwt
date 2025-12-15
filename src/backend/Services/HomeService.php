<?php declare(strict_types=1);
/**
 * Home Service - Business logic for the home/dashboard page
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

require_once __DIR__ . '/TableSetService.php';

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Settings;

/**
 * Service class for the home/dashboard page.
 *
 * Provides business logic for:
 * - Table set management UI data
 * - Current text information
 * - Language selection data
 * - Server information for the home page
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class HomeService
{
    /**
     * Whether table prefix is fixed.
     *
     * @var bool
     *
     * @deprecated 3.0.0 Table prefix feature is deprecated. Multi-user isolation
     *             is now handled via user_id columns instead of table prefixes.
     */
    private bool $fixedTbpref;

    /**
     * Constructor - initialize settings.
     *
     * @deprecated 3.0.0 The $fixedTbpref property is deprecated.
     */
    public function __construct()
    {
        // Suppress the deprecation warning from isTablePrefixFixed during construction
        $this->fixedTbpref = @Globals::isTablePrefixFixed();
    }

    /**
     * Get SPAN opening tags for table set UI.
     *
     * Returns three span elements:
     * - span1: Manage Table Sets link (if not fixed prefix)
     * - span2: Current table set name
     * - span3: Select Table Set link (if not fixed prefix and other sets exist)
     *
     * Note: When multi-user mode is enabled, table set management is hidden
     * as user isolation is handled via user_id columns instead.
     *
     * @return array{span1: string, span2: string, span3: string}
     *
     * @deprecated 3.0.0 Table sets are replaced by user_id-based isolation in multi-user mode.
     *             Will be removed in a future version.
     */
    public function getTableSetSpanGroups(): array
    {
        @trigger_error(
            'HomeService::getTableSetSpanGroups() is deprecated since version 3.0.0 ' .
            'and will be removed in a future version.',
            E_USER_DEPRECATED
        );

        // In multi-user mode, table set UI is hidden - user isolation uses user_id columns
        if (Globals::isMultiUserEnabled()) {
            return [
                'span1' => '<span>',
                'span2' => '</span>',
                'span3' => '<span>'
            ];
        }

        // Suppress deprecation warnings for internal use
        $prefix = @Globals::getTablePrefix();
        if ($prefix == '') {
            $span2 = "<i>Default</i> Table Set</span>";
        } else {
            $span2 = "Table Set: <i>" . htmlspecialchars(substr($prefix, 0, -1) ?? '', ENT_QUOTES, 'UTF-8') . "</i></span>";
        }

        if ($this->fixedTbpref) {
            $span1 = '<span>';
            $span3 = '<span>';
        } else {
            $span1 = '<span title="Manage Table Sets" data-action="navigate" data-url="/admin/tables" class="click">';
            if (count(TableSetService::getAllPrefixes()) > 0) {
                $span3 = '<span title="Select Table Set" data-action="navigate" data-url="/admin/tables" class="click">';
            } else {
                $span3 = '<span>';
            }
        }

        return [
            'span1' => $span1,
            'span2' => $span2,
            'span3' => $span3
        ];
    }

    /**
     * Get current text information for the dashboard.
     *
     * @param int $textId Text ID to retrieve information for
     *
     * @return array{exists: bool, title?: string, language_id?: int, language_name?: string, annotated?: bool}|null
     *         Returns null if text doesn't exist, otherwise returns text data
     */
    public function getCurrentTextInfo(int $textId): ?array
    {
        $title = QueryBuilder::table('texts')
            ->where('TxID', '=', $textId)
            ->valuePrepared('TxTitle');

        if ($title === null) {
            return null;
        }

        $languageId = (int)QueryBuilder::table('texts')
            ->where('TxID', '=', $textId)
            ->valuePrepared('TxLgID');

        $languageName = $this->getLanguageName($languageId);

        $row = QueryBuilder::table('texts')
            ->select(['LENGTH(TxAnnotatedText) AS annotated_length'])
            ->where('TxID', '=', $textId)
            ->firstPrepared();
        $annotated = isset($row['annotated_length']) && (int)$row['annotated_length'] > 0;

        return [
            'exists' => true,
            'title' => (string)$title,
            'language_id' => $languageId,
            'language_name' => $languageName,
            'annotated' => $annotated
        ];
    }

    /**
     * Get language name by ID.
     *
     * @param int $languageId Language ID
     *
     * @return string Language name or empty string if not found
     */
    public function getLanguageName(int $languageId): string
    {
        $result = QueryBuilder::table('languages')
            ->where('LgID', '=', $languageId)
            ->valuePrepared('LgName');

        if ($result === null) {
            return '';
        }

        return (string)$result;
    }

    /**
     * Get the count of languages in the database.
     *
     * @return int Number of languages
     */
    public function getLanguageCount(): int
    {
        return QueryBuilder::table('languages')->count();
    }

    /**
     * Get the current language ID from settings.
     *
     * @return int|null Current language ID or null if not set
     */
    public function getCurrentLanguageId(): ?int
    {
        $currentLang = Settings::get('currentlanguage');
        if (is_numeric($currentLang)) {
            return (int)$currentLang;
        }
        return null;
    }

    /**
     * Get the current text ID from settings.
     *
     * @return int|null Current text ID or null if not set
     */
    public function getCurrentTextId(): ?int
    {
        $currentText = Settings::get('currenttext');
        if (is_numeric($currentText)) {
            return (int)$currentText;
        }
        return null;
    }

    /**
     * Check if user is on WordPress server with active session.
     *
     * @return bool True if WordPress session is active
     */
    public function isWordPressSession(): bool
    {
        return isset($_SESSION['LWT-WP-User']);
    }

    /**
     * Get database size in MB for the current table set.
     *
     * @return float Database size in MB
     */
    public function getDatabaseSize(): float
    {
        $dbname = Globals::getDatabaseName();

        // Get the prefixed table names for all LWT tables
        $tableNames = [
            'archivedtexts', 'archtexttags', 'feedlinks', 'languages',
            'newsfeeds', 'sentences', 'settings', 'tags', 'tags2',
            'textitems2', 'texts', 'texttags', 'words', 'wordtags'
        ];

        // Use Globals::table() to get properly prefixed table names
        $prefixedTables = array_map(
            fn($table) => Globals::table($table),
            $tableNames
        );

        $placeholders = implode(', ', array_fill(0, count($prefixedTables), '?'));
        $bindings = array_merge([$dbname], $prefixedTables);

        $size = Connection::preparedFetchValue(
            "SELECT ROUND(SUM(data_length+index_length)/1024/1024, 1) AS size_mb
            FROM information_schema.TABLES
            WHERE table_schema = ?
            AND table_name IN ($placeholders)",
            $bindings,
            'size_mb'
        );

        if ($size === null) {
            return 0.0;
        }

        return (float)$size;
    }

    /**
     * Get server data for the home page display.
     *
     * @return array{
     *   prefix: string,
     *   db_size: float,
     *   server_software: string[],
     *   apache: string,
     *   php: string|false,
     *   mysql: string
     * }
     *
     * @deprecated Use ServerDataService::getServerData() instead
     */
    public function getServerData(): array
    {
        $serverSoft = explode(' ', $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');
        $apache = "Apache/?";
        if (isset($serverSoft[0]) && str_starts_with($serverSoft[0], "Apache/")) {
            $apache = $serverSoft[0];
        }

        return [
            'prefix' => @Globals::getTablePrefix(),
            'db_size' => $this->getDatabaseSize(),
            'server_software' => $serverSoft,
            'apache' => $apache,
            'php' => phpversion(),
            'mysql' => (string)Connection::fetchValue("SELECT VERSION() AS version", 'version')
        ];
    }

    /**
     * Get the current table prefix.
     *
     * @return string Current table prefix
     *
     * @deprecated 3.0.0 Table prefix feature is deprecated. Multi-user isolation
     *             is now handled via user_id columns instead of table prefixes.
     *             Will be removed in a future version.
     */
    public function getTablePrefix(): string
    {
        @trigger_error(
            'HomeService::getTablePrefix() is deprecated since version 3.0.0 ' .
            'and will be removed in a future version.',
            E_USER_DEPRECATED
        );
        return @Globals::getTablePrefix();
    }

    /**
     * Check if the table prefix is fixed.
     *
     * @return bool True if fixed
     *
     * @deprecated 3.0.0 Table prefix feature is deprecated. Multi-user isolation
     *             is now handled via user_id columns instead of table prefixes.
     *             Will be removed in a future version.
     */
    public function isTablePrefixFixed(): bool
    {
        @trigger_error(
            'HomeService::isTablePrefixFixed() is deprecated since version 3.0.0 ' .
            'and will be removed in a future version.',
            E_USER_DEPRECATED
        );
        return $this->fixedTbpref;
    }

    /**
     * Check if table set management UI should be shown.
     *
     * Returns false when:
     * - Multi-user mode is enabled (uses user_id isolation instead)
     * - Table prefix is fixed in .env
     *
     * @return bool True if table set management should be available
     *
     * @deprecated 3.0.0 Table prefix feature is deprecated. Multi-user isolation
     *             is now handled via user_id columns instead of table prefixes.
     *             Will be removed in a future version.
     */
    public function shouldShowTableSetManagement(): bool
    {
        @trigger_error(
            'HomeService::shouldShowTableSetManagement() is deprecated since version 3.0.0 ' .
            'and will be removed in a future version.',
            E_USER_DEPRECATED
        );

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
     * Get dashboard data for rendering the home page.
     *
     * This is a convenience method that gathers all data needed
     * for the home page in a single call.
     *
     * @return array{
     *   language_count: int,
     *   current_language_id: int|null,
     *   current_text_id: int|null,
     *   current_text_info: array|null,
     *   table_prefix: string,
     *   is_fixed_prefix: bool,
     *   is_wordpress: bool,
     *   is_debug: bool,
     *   is_multi_user: bool,
     *   show_table_set_management: bool
     * }
     *
     * @deprecated 3.0.0 The table_prefix, is_fixed_prefix, and show_table_set_management
     *             fields are deprecated. Multi-user isolation is now handled via
     *             user_id columns instead of table prefixes.
     */
    public function getDashboardData(): array
    {
        $currentTextId = $this->getCurrentTextId();

        // Suppress deprecation warnings for internal use of deprecated methods
        $showTableSetManagement = false;
        if (!Globals::isMultiUserEnabled() && !$this->fixedTbpref) {
            $showTableSetManagement = true;
        }

        return [
            'language_count' => $this->getLanguageCount(),
            'current_language_id' => $this->getCurrentLanguageId(),
            'current_text_id' => $currentTextId,
            'current_text_info' => $currentTextId !== null
                ? $this->getCurrentTextInfo($currentTextId)
                : null,
            'table_prefix' => @Globals::getTablePrefix(),
            'is_fixed_prefix' => $this->fixedTbpref,
            'is_wordpress' => $this->isWordPressSession(),
            'is_debug' => Globals::isDebug(),
            'is_multi_user' => Globals::isMultiUserEnabled(),
            'show_table_set_management' => $showTableSetManagement
        ];
    }
}

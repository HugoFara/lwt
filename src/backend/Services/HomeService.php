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
use Lwt\Database\Escaping;
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
     * Constructor - initialize settings.
     */
    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
        $this->fixedTbpref = Globals::isTablePrefixFixed();
    }

    /**
     * Get SPAN opening tags for table set UI.
     *
     * Returns three span elements:
     * - span1: Manage Table Sets link (if not fixed prefix)
     * - span2: Current table set name
     * - span3: Select Table Set link (if not fixed prefix and other sets exist)
     *
     * @return array{span1: string, span2: string, span3: string}
     */
    public function getTableSetSpanGroups(): array
    {
        if ($this->tbpref == '') {
            $span2 = "<i>Default</i> Table Set</span>";
        } else {
            $span2 = "Table Set: <i>" . htmlspecialchars(substr($this->tbpref, 0, -1) ?? '', ENT_QUOTES, 'UTF-8') . "</i></span>";
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
        $title = Connection::fetchValue(
            'SELECT TxTitle AS value
            FROM ' . $this->tbpref . 'texts
            WHERE TxID=' . $textId
        );

        if ($title === null) {
            return null;
        }

        $languageId = (int)Connection::fetchValue(
            'SELECT TxLgID AS value FROM ' . $this->tbpref . 'texts WHERE TxID=' . $textId
        );

        $languageName = $this->getLanguageName($languageId);

        $annotated = (int)Connection::fetchValue(
            "SELECT LENGTH(TxAnnotatedText) AS value
            FROM " . $this->tbpref . "texts
            WHERE TxID = " . $textId
        ) > 0;

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
        $result = Connection::fetchValue(
            "SELECT LgName AS value
            FROM {$this->tbpref}languages
            WHERE LgID = $languageId"
        );

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
        return (int)Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$this->tbpref}languages"
        );
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
        $dbaccess_format = Escaping::toSqlSyntax($dbname);

        $size = Connection::fetchValue(
            "SELECT ROUND(SUM(data_length+index_length)/1024/1024, 1) AS value
            FROM information_schema.TABLES
            WHERE table_schema = $dbaccess_format
            AND table_name IN (
                '{$this->tbpref}archivedtexts', '{$this->tbpref}archtexttags',
                '{$this->tbpref}feedlinks', '{$this->tbpref}languages',
                '{$this->tbpref}newsfeeds', '{$this->tbpref}sentences',
                '{$this->tbpref}settings', '{$this->tbpref}tags', '{$this->tbpref}tags2',
                '{$this->tbpref}textitems2', '{$this->tbpref}texts', '{$this->tbpref}texttags',
                '{$this->tbpref}words', '{$this->tbpref}wordtags'
            )"
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
            'prefix' => Escaping::toSqlSyntaxNoNull($this->tbpref),
            'db_size' => $this->getDatabaseSize(),
            'server_software' => $serverSoft,
            'apache' => $apache,
            'php' => phpversion(),
            'mysql' => (string)Connection::fetchValue("SELECT VERSION() AS value")
        ];
    }

    /**
     * Get the current table prefix.
     *
     * @return string Current table prefix
     */
    public function getTablePrefix(): string
    {
        return $this->tbpref;
    }

    /**
     * Check if the table prefix is fixed.
     *
     * @return bool True if fixed
     */
    public function isTablePrefixFixed(): bool
    {
        return $this->fixedTbpref;
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
     *   is_debug: bool
     * }
     */
    public function getDashboardData(): array
    {
        $currentTextId = $this->getCurrentTextId();

        return [
            'language_count' => $this->getLanguageCount(),
            'current_language_id' => $this->getCurrentLanguageId(),
            'current_text_id' => $currentTextId,
            'current_text_info' => $currentTextId !== null
                ? $this->getCurrentTextInfo($currentTextId)
                : null,
            'table_prefix' => $this->tbpref,
            'is_fixed_prefix' => $this->fixedTbpref,
            'is_wordpress' => $this->isWordPressSession(),
            'is_debug' => Globals::isDebug()
        ];
    }
}

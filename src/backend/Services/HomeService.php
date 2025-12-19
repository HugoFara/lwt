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

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Settings;

/**
 * Service class for the home/dashboard page.
 *
 * Provides business logic for:
 * - Current text information
 * - Language selection data
 * - Database size information
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
     *   is_wordpress: bool,
     *   is_multi_user: bool
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
            'is_wordpress' => $this->isWordPressSession(),
            'is_multi_user' => Globals::isMultiUserEnabled()
        ];
    }
}

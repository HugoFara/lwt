<?php declare(strict_types=1);
/**
 * Tag Service - Business logic for tag management
 *
 * Handles both term tags (tags table) and text tags (tags2 table).
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
use Lwt\Core\Http\InputValidator;
use Lwt\Core\Http\UrlUtilities;
use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Settings;
use Lwt\Database\Maintenance;
use Lwt\Database\UserScopedQuery;
use Lwt\View\Helper\FormHelper;

/**
 * Service class for managing term tags and text tags.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TagService
{
    /**
     * Tag type: 'term' for term tags, 'text' for text tags.
     *
     * @var string
     */
    private string $tagType;

    /**
     * Table name based on tag type.
     *
     * @var string
     */
    private string $tableName;

    /**
     * ID column prefix (Tg for term tags, T2 for text tags).
     *
     * @var string
     */
    private string $colPrefix;

    /**
     * Constructor - initialize tag type.
     *
     * @param string $tagType 'term' for term tags, 'text' for text tags
     */
    public function __construct(string $tagType = 'term')
    {
        $this->tagType = $tagType;

        if ($tagType === 'text') {
            $this->tableName = 'tags2';
            $this->colPrefix = 'T2';
        } else {
            $this->tableName = 'tags';
            $this->colPrefix = 'Tg';
        }
    }

    /**
     * Build WHERE clause for query filtering.
     *
     * @param string $query Filter query string
     *
     * @return array{clause: string, params: array} Array with SQL clause and parameters
     */
    public function buildWhereClause(string $query): array
    {
        if ($query === '') {
            return ['clause' => '', 'params' => []];
        }

        $searchValue = str_replace("*", "%", $query);
        $clause = ' AND (' . $this->colPrefix . 'Text LIKE ? OR ' .
                  $this->colPrefix . 'Comment LIKE ?)';

        return ['clause' => $clause, 'params' => [$searchValue, $searchValue]];
    }

    /**
     * Delete multiple tags by IDs.
     *
     * @param array $tagIds Array of tag IDs to delete
     *
     * @return string Result message
     */
    public function deleteMultiple(array $tagIds): string
    {
        if (empty($tagIds)) {
            return "Multiple Actions: 0";
        }

        $affected = QueryBuilder::table($this->tableName)
            ->whereIn($this->colPrefix . 'ID', array_map('intval', $tagIds))
            ->deletePrepared();

        $message = $affected > 0 ? "Deleted" : "Deleted (0 rows affected)";

        $this->cleanupOrphanedLinks();
        Maintenance::adjustAutoIncrement($this->tableName, $this->colPrefix . 'ID');

        return $message;
    }

    /**
     * Delete all tags matching the filter.
     *
     * @param array{clause: string, params: array} $whereData WHERE clause data from buildWhereClause()
     *
     * @return string Result message
     */
    public function deleteAll(array $whereData = ['clause' => '', 'params' => []]): string
    {
        // Use raw SQL if WHERE clause provided, otherwise QueryBuilder
        if (!empty($whereData['clause'])) {
            $affected = Connection::preparedExecute(
                'DELETE FROM ' . $this->tableName .
                ' WHERE (1=1) ' . $whereData['clause'],
                $whereData['params']
            );
        } else {
            $affected = QueryBuilder::table($this->tableName)->deletePrepared();
        }
        $message = $affected > 0 ? "Deleted" : "Deleted (0 rows affected)";

        $this->cleanupOrphanedLinks();
        Maintenance::adjustAutoIncrement($this->tableName, $this->colPrefix . 'ID');

        return $message;
    }

    /**
     * Delete a single tag by ID.
     *
     * @param int $tagId Tag ID to delete
     *
     * @return string Result message
     */
    public function delete(int $tagId): string
    {
        $affected = QueryBuilder::table($this->tableName)
            ->where($this->colPrefix . 'ID', '=', $tagId)
            ->deletePrepared();

        $message = $affected > 0 ? "Deleted" : "Deleted (0 rows affected)";

        $this->cleanupOrphanedLinks();
        Maintenance::adjustAutoIncrement($this->tableName, $this->colPrefix . 'ID');

        return $message;
    }

    /**
     * Create a new tag.
     *
     * @param string $text    Tag text
     * @param string $comment Tag comment
     *
     * @return string Result message
     */
    public function create(string $text, string $comment): string
    {
        try {
            QueryBuilder::table($this->tableName)->insertPrepared([
                $this->colPrefix . 'Text' => $text,
                $this->colPrefix . 'Comment' => $comment
            ]);
            return "Saved";
        } catch (\mysqli_sql_exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    /**
     * Update an existing tag.
     *
     * @param int    $tagId   Tag ID to update
     * @param string $text    New tag text
     * @param string $comment New tag comment
     *
     * @return string Result message
     */
    public function update(int $tagId, string $text, string $comment): string
    {
        try {
            QueryBuilder::table($this->tableName)
                ->where($this->colPrefix . 'ID', '=', $tagId)
                ->updatePrepared([
                    $this->colPrefix . 'Text' => $text,
                    $this->colPrefix . 'Comment' => $comment
                ]);
            return "Updated";
        } catch (\mysqli_sql_exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    /**
     * Get a tag by ID.
     *
     * @param int $tagId Tag ID
     *
     * @return array|null Tag data or null if not found
     */
    public function getById(int $tagId): ?array
    {
        $row = Connection::preparedFetchOne(
            'SELECT * FROM ' . $this->tableName . ' WHERE ' . $this->colPrefix . 'ID = ?',
            [$tagId]
        );

        return $row ?: null;
    }

    /**
     * Get total count of tags matching filter.
     *
     * @param array{clause: string, params: array} $whereData WHERE clause data from buildWhereClause()
     *
     * @return int Number of tags
     */
    public function getCount(array $whereData = ['clause' => '', 'params' => []]): int
    {
        // Use raw SQL if WHERE clause provided, otherwise QueryBuilder
        if (!empty($whereData['clause'])) {
            return (int) Connection::preparedFetchValue(
                'SELECT COUNT(' . $this->colPrefix . 'ID) AS cnt FROM ' .
                $this->tableName . ' WHERE (1=1) ' . $whereData['clause'],
                $whereData['params'],
                'cnt'
            );
        } else {
            return (int) QueryBuilder::table($this->tableName)
                ->count($this->colPrefix . 'ID');
        }
    }

    /**
     * Get tags list with pagination.
     *
     * @param array{clause: string, params: array} $whereData WHERE clause data from buildWhereClause()
     * @param string $orderBy     Sort column name (without prefix)
     * @param int    $page        Page number (1-based)
     * @param int    $perPage     Items per page
     *
     * @return array Array of tag records with usage counts
     */
    public function getList(
        array $whereData = ['clause' => '', 'params' => []],
        string $orderBy = 'Text',
        int $page = 1,
        int $perPage = 20
    ): array {
        $validSorts = ['Text', 'Comment', 'ID desc', 'ID asc'];
        $sortColumn = in_array($orderBy, $validSorts) ?
            $this->colPrefix . $orderBy :
            $this->colPrefix . 'Text';

        $offset = ($page - 1) * $perPage;
        $limit = 'LIMIT ' . $offset . ',' . $perPage;

        // Use raw SQL due to dynamic WHERE clause and LIMIT syntax
        $rows = Connection::preparedFetchAll(
            'SELECT ' . $this->colPrefix . 'ID, ' .
            $this->colPrefix . 'Text, ' . $this->colPrefix . 'Comment ' .
            'FROM ' . $this->tableName .
            ' WHERE (1=1) ' . $whereData['clause'] .
            ' ORDER BY ' . $sortColumn . ' ' . $limit,
            $whereData['params']
        );

        $tags = [];

        foreach ($rows as $record) {
            $tagId = (int)$record[$this->colPrefix . 'ID'];
            $tag = [
                'id' => $tagId,
                'text' => $record[$this->colPrefix . 'Text'],
                'comment' => $record[$this->colPrefix . 'Comment'],
                'usageCount' => $this->getUsageCount($tagId)
            ];

            // For text tags, also get archived text count
            if ($this->tagType === 'text') {
                $tag['archivedUsageCount'] = $this->getArchivedUsageCount($tagId);
            }

            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * Get the number of items (words or texts) using this tag.
     *
     * @param int $tagId Tag ID
     *
     * @return int Usage count
     */
    public function getUsageCount(int $tagId): int
    {
        if ($this->tagType === 'text') {
            return (int) QueryBuilder::table('texttags')
                ->where('TtT2ID', '=', $tagId)
                ->count();
        } else {
            return (int) QueryBuilder::table('wordtags')
                ->where('WtTgID', '=', $tagId)
                ->count();
        }
    }

    /**
     * Get the number of archived texts using this text tag.
     *
     * @param int $tagId Tag ID
     *
     * @return int Archived usage count
     */
    public function getArchivedUsageCount(int $tagId): int
    {
        if ($this->tagType !== 'text') {
            return 0;
        }
        return (int) QueryBuilder::table('archtexttags')
            ->where('AgT2ID', '=', $tagId)
            ->count();
    }

    /**
     * Get the maximum items per page setting.
     *
     * @return int Items per page
     */
    public function getMaxPerPage(): int
    {
        return (int) Settings::getWithDefault('set-tags-per-page');
    }

    /**
     * Calculate pagination info.
     *
     * @param int $totalCount Total number of items
     * @param int $currentPage Current page number
     *
     * @return array Pagination info with 'pages', 'currentPage', 'perPage'
     */
    public function getPagination(int $totalCount, int $currentPage): array
    {
        $perPage = $this->getMaxPerPage();
        $pages = $totalCount == 0 ? 0 : (int) ceil($totalCount / $perPage);

        if ($currentPage < 1) {
            $currentPage = 1;
        }
        if ($currentPage > $pages && $pages > 0) {
            $currentPage = $pages;
        }

        return [
            'pages' => $pages,
            'currentPage' => $currentPage,
            'perPage' => $perPage
        ];
    }

    /**
     * Get sort options for the dropdown.
     *
     * @return array Array of sort options with 'value' and 'text'
     */
    public function getSortOptions(): array
    {
        return [
            ['value' => 1, 'text' => 'Tag Text (A-Z)'],
            ['value' => 2, 'text' => 'Tag Comment (A-Z)'],
            ['value' => 3, 'text' => 'Newest first'],
            ['value' => 4, 'text' => 'Oldest first']
        ];
    }

    /**
     * Convert sort index to column name.
     *
     * @param int $sortIndex Sort index (1-4)
     *
     * @return string Sort column name
     */
    public function getSortColumn(int $sortIndex): string
    {
        $sorts = ['Text', 'Comment', 'ID desc', 'ID asc'];
        $index = max(1, min($sortIndex, count($sorts))) - 1;
        return $sorts[$index];
    }

    /**
     * Format duplicate entry error message for display.
     *
     * @param string $message Original error message
     *
     * @return string Formatted error message
     */
    public function formatDuplicateError(string $message): string
    {
        $keyName = $this->colPrefix . 'Text';

        if (
            substr($message, 0, 24) == "Error: Duplicate entry '"
            && substr($message, -strlen("' for key '$keyName'")) == "' for key '$keyName'"
        ) {
            $tagName = substr($message, 24);
            $tagName = substr($tagName, 0, strlen($tagName) - strlen("' for key '$keyName'"));
            $tagTypeLabel = $this->tagType === 'text' ? 'Text Tag' : 'Term Tag';
            return "Error: $tagTypeLabel '" . $tagName .
                   "' already exists. Please go back and correct this!";
        }

        return $message;
    }

    /**
     * Get the tag type label for display.
     *
     * @return string "Term" or "Text"
     */
    public function getTagTypeLabel(): string
    {
        return $this->tagType === 'text' ? 'Text' : 'Term';
    }

    /**
     * Get the base URL for this tag type.
     *
     * @return string Base URL path
     */
    public function getBaseUrl(): string
    {
        return $this->tagType === 'text' ? '/tags/text' : '/tags';
    }

    /**
     * Get the link URL to items using this tag.
     *
     * @param int $tagId Tag ID
     *
     * @return string URL to view items with this tag
     */
    public function getItemsUrl(int $tagId): string
    {
        if ($this->tagType === 'text') {
            return '/texts?page=1&query=&tag12=0&tag2=&tag1=' . $tagId;
        }
        return '/words?page=1&query=&text=&status=&filterlang=&status=&tag12=0&tag2=&tag1=' . $tagId;
    }

    /**
     * Get the link URL to archived texts using this text tag.
     *
     * @param int $tagId Tag ID
     *
     * @return string URL to view archived texts with this tag
     */
    public function getArchivedItemsUrl(int $tagId): string
    {
        return 'edit_archivedtexts.php?page=1&query=&tag12=0&tag2=&tag1=' . $tagId;
    }

    /**
     * Cleanup orphaned tag links after tag deletion.
     *
     * @return void
     */
    private function cleanupOrphanedLinks(): void
    {
        if ($this->tagType === 'text') {
            Connection::execute(
                "DELETE texttags FROM (" .
                "texttags LEFT JOIN " .
                "tags2 on TtT2ID = T2ID) WHERE T2ID IS NULL",
                ''
            );
            Connection::execute(
                "DELETE archtexttags FROM (" .
                "archtexttags LEFT JOIN " .
                "tags2 on AgT2ID = T2ID) WHERE T2ID IS NULL",
                ''
            );
        } else {
            Connection::execute(
                "DELETE wordtags FROM (" .
                "wordtags LEFT JOIN " .
                "tags on WtTgID = TgID) WHERE TgID IS NULL",
                ''
            );
        }
    }

    // =========================================================================
    // Cache methods (migrated from tags.php)
    // =========================================================================

    /**
     * Get all term tags, with session caching.
     *
     * @param bool $refresh If true, refresh the cache
     *
     * @return array<string> All term tag texts
     */
    public static function getAllTermTags(bool $refresh = false): array
    {
        $cacheKey = self::getUrlBase();

        if (
            !$refresh
            && isset($_SESSION['TAGS'])
            && is_array($_SESSION['TAGS'])
            && isset($_SESSION['TBPREF_TAGS'])
            && $_SESSION['TBPREF_TAGS'] === $cacheKey
        ) {
            return $_SESSION['TAGS'];
        }

        $rows = QueryBuilder::table('tags')
            ->select(['TgText'])
            ->orderBy('TgText')
            ->getPrepared();

        $tags = array_map(fn($row) => (string) $row['TgText'], $rows);

        $_SESSION['TAGS'] = $tags;
        $_SESSION['TBPREF_TAGS'] = $cacheKey;

        return $tags;
    }

    /**
     * Get all text tags, with session caching.
     *
     * @param bool $refresh If true, refresh the cache
     *
     * @return array<string> All text tag texts
     */
    public static function getAllTextTags(bool $refresh = false): array
    {
        $cacheKey = self::getUrlBase();

        if (
            !$refresh
            && isset($_SESSION['TEXTTAGS'])
            && is_array($_SESSION['TEXTTAGS'])
            && isset($_SESSION['TBPREF_TEXTTAGS'])
            && $_SESSION['TBPREF_TEXTTAGS'] === $cacheKey
        ) {
            return $_SESSION['TEXTTAGS'];
        }

        $rows = QueryBuilder::table('tags2')
            ->select(['T2Text'])
            ->orderBy('T2Text')
            ->getPrepared();

        $tags = array_map(fn($row) => (string) $row['T2Text'], $rows);

        $_SESSION['TEXTTAGS'] = $tags;
        $_SESSION['TBPREF_TEXTTAGS'] = $cacheKey;

        return $tags;
    }

    /**
     * Get the URL base for cache key generation.
     *
     * @return string URL base
     */
    private static function getUrlBase(): string
    {
        return UrlUtilities::urlBase();
    }

    // =========================================================================
    // Save tag associations (migrated from tags.php)
    // =========================================================================

    /**
     * Save tags for a word from form input.
     *
     * @param int $wordId Word ID
     *
     * @return void
     */
    public static function saveWordTags(int $wordId): void
    {
        QueryBuilder::table('wordtags')
            ->where('WtWoID', '=', $wordId)
            ->delete();

        $termTags = InputValidator::getArray('TermTags');
        if (
            empty($termTags)
            || !isset($termTags['TagList'])
            || !is_array($termTags['TagList'])
        ) {
            return;
        }

        $tagList = $termTags['TagList'];
        self::getAllTermTags(true); // Refresh cache

        foreach ($tagList as $tag) {
            $tag = (string) $tag;
            if (!in_array($tag, $_SESSION['TAGS'])) {
                QueryBuilder::table('tags')->insertPrepared(['TgText' => $tag]);
            }
            // Use raw SQL for INSERT...SELECT subquery
            Connection::preparedExecute(
                "INSERT INTO wordtags (WtWoID, WtTgID)
                SELECT ?, TgID
                FROM tags
                WHERE TgText = ?",
                [$wordId, $tag]
            );
        }

        self::getAllTermTags(true); // Refresh cache
    }

    /**
     * Save tags for a text from form input.
     *
     * @param int $textId Text ID
     *
     * @return void
     */
    public static function saveTextTags(int $textId): void
    {
        QueryBuilder::table('texttags')
            ->where('TtTxID', '=', $textId)
            ->delete();

        $textTags = InputValidator::getArray('TextTags');
        if (
            empty($textTags)
            || !isset($textTags['TagList'])
            || !is_array($textTags['TagList'])
        ) {
            return;
        }

        $tagList = $textTags['TagList'];
        self::getAllTextTags(true); // Refresh cache

        foreach ($tagList as $tag) {
            $tag = (string) $tag;
            if (!in_array($tag, $_SESSION['TEXTTAGS'])) {
                QueryBuilder::table('tags2')->insertPrepared(['T2Text' => $tag]);
            }
            // Use raw SQL for INSERT...SELECT subquery
            Connection::preparedExecute(
                "INSERT INTO texttags (TtTxID, TtT2ID)
                SELECT ?, T2ID
                FROM tags2
                WHERE T2Text = ?",
                [$textId, $tag]
            );
        }

        self::getAllTextTags(true); // Refresh cache
    }

    /**
     * Save tags for an archived text from form input.
     *
     * @param int $textId Archived text ID
     *
     * @return void
     */
    public static function saveArchivedTextTags(int $textId): void
    {
        QueryBuilder::table('archtexttags')
            ->where('AgAtID', '=', $textId)
            ->delete();

        $textTags = InputValidator::getArray('TextTags');
        if (
            empty($textTags)
            || !isset($textTags['TagList'])
            || !is_array($textTags['TagList'])
        ) {
            return;
        }

        $tagList = $textTags['TagList'];
        self::getAllTextTags(true); // Refresh cache

        foreach ($tagList as $tag) {
            $tag = (string) $tag;
            if (!in_array($tag, $_SESSION['TEXTTAGS'])) {
                QueryBuilder::table('tags2')->insertPrepared(['T2Text' => $tag]);
            }
            // Use raw SQL for INSERT...SELECT subquery
            Connection::preparedExecute(
                "INSERT INTO archtexttags (AgAtID, AgT2ID)
                SELECT ?, T2ID
                FROM tags2
                WHERE T2Text = ?",
                [$textId, $tag]
            );
        }

        self::getAllTextTags(true); // Refresh cache
    }

    // =========================================================================
    // Get tag display HTML (migrated from tags.php)
    // =========================================================================

    /**
     * Get HTML list of tags for a word.
     *
     * @param int $wordId Word ID (0 for empty list)
     *
     * @return string HTML UL element with tags
     */
    public static function getWordTagsHtml(int $wordId): string
    {
        $html = '<ul id="termtags">';

        if ($wordId > 0) {
            // Use raw SQL for comma-separated table JOIN
            $rows = Connection::preparedFetchAll(
                'SELECT TgText
                FROM wordtags, tags
                WHERE TgID = WtTgID AND WtWoID = ?
                ORDER BY TgText',
                [$wordId]
            );
            foreach ($rows as $record) {
                $html .= '<li>' . htmlspecialchars($record['TgText'] ?? '', ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }

        return $html . '</ul>';
    }

    /**
     * Get HTML list of tags for a text.
     *
     * @param int $textId Text ID (0 for empty list)
     *
     * @return string HTML UL element with tags
     */
    public static function getTextTagsHtml(int $textId): string
    {
        $html = '<ul id="texttags" class="respinput">';

        if ($textId > 0) {
            // Use raw SQL for comma-separated table JOIN
            $rows = Connection::preparedFetchAll(
                "SELECT T2Text
                FROM texttags, tags2
                WHERE T2ID = TtT2ID AND TtTxID = ?
                ORDER BY T2Text",
                [$textId]
            );
            foreach ($rows as $record) {
                $html .= '<li>' . htmlspecialchars($record['T2Text'] ?? '', ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }

        return $html . '</ul>';
    }

    /**
     * Get HTML list of tags for an archived text.
     *
     * @param int $textId Archived text ID (0 for empty list)
     *
     * @return string HTML UL element with tags
     */
    public static function getArchivedTextTagsHtml(int $textId): string
    {
        $html = '<ul id="texttags">';

        if ($textId > 0) {
            // Use raw SQL for comma-separated table JOIN
            $rows = Connection::preparedFetchAll(
                'SELECT T2Text
                FROM archtexttags, tags2
                WHERE T2ID = AgT2ID AND AgAtID = ?
                ORDER BY T2Text',
                [$textId]
            );
            foreach ($rows as $record) {
                $html .= '<li>' . htmlspecialchars($record['T2Text'] ?? '', ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }

        return $html . '</ul>';
    }

    /**
     * Get comma-separated tag list string for a word.
     *
     * @param int  $wordId     Word ID
     * @param bool $escapeHtml Convert to HTML entities
     *
     * @return string Comma-separated tag list
     */
    public static function getWordTagList(int $wordId, bool $escapeHtml = true): string
    {
        // Use raw SQL for complex nested JOINs
        $result = Connection::preparedFetchValue(
            "SELECT IFNULL(
                GROUP_CONCAT(DISTINCT TgText ORDER BY TgText SEPARATOR ','),
                ''
            ) AS taglist
            FROM (
                (
                    words
                    LEFT JOIN wordtags ON WoID = WtWoID
                )
                LEFT JOIN tags ON TgID = WtTgID
            )
            WHERE WoID = ?",
            [$wordId],
            'taglist'
        );

        if ($escapeHtml && $result !== null) {
            $result = htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
        }

        return $result ?? '';
    }

    /**
     * Get formatted tag list as Bulma tag components for a word.
     *
     * @param int    $wordId Word ID
     * @param string $size   Bulma size class (e.g., 'is-small', 'is-normal')
     * @param string $color  Bulma color class (e.g., 'is-info', 'is-primary')
     * @param bool   $isLight Whether to use light variant
     *
     * @return string HTML for Bulma tags
     */
    public static function getWordTagListHtml(
        int $wordId,
        string $size = 'is-small',
        string $color = 'is-info',
        bool $isLight = true
    ): string {
        $tagList = self::getWordTagList($wordId, false);
        return \Lwt\View\Helper\TagHelper::renderInline($tagList, $size, $color, $isLight);
    }

    /**
     * Get formatted tag list string for a word.
     *
     * @param int    $wordId Word ID
     * @param string $before String to prepend if tags exist
     * @param bool   $brackets Wrap tags in brackets (deprecated, kept for compatibility)
     * @param bool   $escapeHtml Convert to HTML entities
     *
     * @return string Formatted tag list
     *
     * @deprecated Use getWordTagList() or getWordTagListHtml() instead
     */
    public static function getWordTagListFormatted(
        int $wordId,
        string $before = ' ',
        bool $brackets = true,
        bool $escapeHtml = true
    ): string {
        $lbrack = $brackets ? '[' : '';
        $rbrack = $brackets ? ']' : '';

        // Use raw SQL for complex nested JOINs
        $result = Connection::preparedFetchValue(
            "SELECT IFNULL(
                GROUP_CONCAT(DISTINCT TgText ORDER BY TgText SEPARATOR ', '),
                ''
            ) AS taglist
            FROM (
                (
                    words
                    LEFT JOIN wordtags ON WoID = WtWoID
                )
                LEFT JOIN tags ON TgID = WtTgID
            )
            WHERE WoID = ?",
            [$wordId],
            'taglist'
        );

        if ($result != '') {
            $result = $before . $lbrack . $result . $rbrack;
        }

        if ($escapeHtml) {
            $result = htmlspecialchars($result ?? '', ENT_QUOTES, 'UTF-8');
        }

        return $result;
    }

    // =========================================================================
    // Batch operations (migrated from tags.php)
    // =========================================================================

    /**
     * Add a tag to multiple words.
     *
     * @param string $tagText Tag text to add
     * @param string $idList  SQL list of word IDs, e.g. "(1,2,3)"
     *
     * @return string Result message
     */
    public static function addTagToWords(string $tagText, string $idList): string
    {
        if ($idList === '()') {
            return "Tag added in 0 Terms";
        }

        $tagId = self::getOrCreateTermTag($tagText);
        if ($tagId === null) {
            return "Failed to create tag";
        }

        // Use raw SQL for LEFT JOIN with dynamic IN clause
        // Add user scope to ensure we only modify current user's words
        $sql = 'SELECT WoID
            FROM words
            LEFT JOIN wordtags ON WoID = WtWoID AND WtTgID = ' . $tagId . '
            WHERE WtTgID IS NULL AND WoID IN ' . $idList
            . UserScopedQuery::forTable('words');
        $res = Connection::query($sql);

        $count = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $count += (int) Connection::execute(
                'INSERT IGNORE INTO wordtags (WtWoID, WtTgID)
                VALUES(' . $record['WoID'] . ', ' . $tagId . ')'
            );
        }
        mysqli_free_result($res);

        self::getAllTermTags(true);

        return "Tag added in {$count} Terms";
    }

    /**
     * Remove a tag from multiple words.
     *
     * @param string $tagText Tag text to remove
     * @param string $idList  SQL list of word IDs, e.g. "(1,2,3)"
     *
     * @return string Result message
     */
    public static function removeTagFromWords(string $tagText, string $idList): string
    {
        if ($idList === '()') {
            return "Tag removed in 0 Terms";
        }

        $tagId = Connection::preparedFetchValue(
            'SELECT TgID FROM tags WHERE TgText = ?',
            [$tagText],
            'TgID'
        );

        if (!isset($tagId)) {
            return "Tag " . $tagText . " not found";
        }

        // Use raw SQL for dynamic IN clause
        // Add user scope to ensure we only modify current user's words
        $sql = 'SELECT WoID FROM words WHERE WoID IN ' . $idList
            . UserScopedQuery::forTable('words');
        $res = Connection::query($sql);

        $count = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $count++;
            QueryBuilder::table('wordtags')
                ->where('WtWoID', '=', (int)$record['WoID'])
                ->where('WtTgID', '=', (int)$tagId)
                ->delete();
        }
        mysqli_free_result($res);

        return "Tag removed in {$count} Terms";
    }

    /**
     * Add a tag to multiple texts.
     *
     * @param string $tagText Tag text to add
     * @param string $idList  SQL list of text IDs, e.g. "(1,2,3)"
     *
     * @return string Result message
     */
    public static function addTagToTexts(string $tagText, string $idList): string
    {
        if ($idList === '()') {
            return "Tag added in 0 Texts";
        }

        $tagId = self::getOrCreateTextTag($tagText);
        if ($tagId === null) {
            return "Failed to create tag";
        }

        // Use raw SQL for LEFT JOIN with dynamic IN clause
        // Add user scope to ensure we only modify current user's texts
        $sql = 'SELECT TxID FROM texts
            LEFT JOIN texttags ON TxID = TtTxID AND TtT2ID = ' . $tagId . '
            WHERE TtT2ID IS NULL AND TxID IN ' . $idList
            . UserScopedQuery::forTable('texts');
        $res = Connection::query($sql);

        $count = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $count += (int) Connection::execute(
                'INSERT IGNORE INTO texttags (TtTxID, TtT2ID)
                VALUES(' . $record['TxID'] . ', ' . $tagId . ')'
            );
        }
        mysqli_free_result($res);

        self::getAllTextTags(true);

        return "Tag added in {$count} Texts";
    }

    /**
     * Remove a tag from multiple texts.
     *
     * @param string $tagText Tag text to remove
     * @param string $idList  SQL list of text IDs, e.g. "(1,2,3)"
     *
     * @return string Result message
     */
    public static function removeTagFromTexts(string $tagText, string $idList): string
    {
        if ($idList === '()') {
            return "Tag removed in 0 Texts";
        }

        $tagId = Connection::preparedFetchValue(
            'SELECT T2ID FROM tags2 WHERE T2Text = ?',
            [$tagText],
            'T2ID'
        );

        if (!isset($tagId)) {
            return "Tag " . $tagText . " not found";
        }

        // Use raw SQL for dynamic IN clause
        // Add user scope to ensure we only modify current user's texts
        $sql = 'SELECT TxID FROM texts WHERE TxID IN ' . $idList
            . UserScopedQuery::forTable('texts');
        $res = Connection::query($sql);

        $count = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $count++;
            QueryBuilder::table('texttags')
                ->where('TtTxID', '=', (int)$record['TxID'])
                ->where('TtT2ID', '=', (int)$tagId)
                ->delete();
        }
        mysqli_free_result($res);

        return "Tag removed in {$count} Texts";
    }

    /**
     * Add a tag to multiple archived texts.
     *
     * @param string $tagText Tag text to add
     * @param string $idList  SQL list of archived text IDs, e.g. "(1,2,3)"
     *
     * @return string Result message
     */
    public static function addTagToArchivedTexts(string $tagText, string $idList): string
    {
        if ($idList === '()') {
            return "Tag added in 0 Texts";
        }

        $tagId = self::getOrCreateTextTag($tagText);
        if ($tagId === null) {
            return "Failed to create tag";
        }

        // Use raw SQL for LEFT JOIN with dynamic IN clause
        // Add user scope to ensure we only modify current user's archived texts
        $sql = 'SELECT AtID FROM archivedtexts
            LEFT JOIN archtexttags ON AtID = AgAtID AND AgT2ID = ' . $tagId . '
            WHERE AgT2ID IS NULL AND AtID IN ' . $idList
            . UserScopedQuery::forTable('archivedtexts');
        $res = Connection::query($sql);

        $count = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $count += (int) Connection::execute(
                'INSERT IGNORE INTO archtexttags (AgAtID, AgT2ID)
                VALUES(' . $record['AtID'] . ', ' . $tagId . ')'
            );
        }
        mysqli_free_result($res);

        self::getAllTextTags(true);

        return "Tag added in {$count} Texts";
    }

    /**
     * Remove a tag from multiple archived texts.
     *
     * @param string $tagText Tag text to remove
     * @param string $idList  SQL list of archived text IDs, e.g. "(1,2,3)"
     *
     * @return string Result message
     */
    public static function removeTagFromArchivedTexts(
        string $tagText,
        string $idList
    ): string {
        if ($idList === '()') {
            return "Tag removed in 0 Texts";
        }

        $tagId = Connection::preparedFetchValue(
            'SELECT T2ID FROM tags2 WHERE T2Text = ?',
            [$tagText],
            'T2ID'
        );

        if (!isset($tagId)) {
            return "Tag " . $tagText . " not found";
        }

        // Use raw SQL for dynamic IN clause
        // Add user scope to ensure we only modify current user's archived texts
        $sql = 'SELECT AtID FROM archivedtexts WHERE AtID IN ' . $idList
            . UserScopedQuery::forTable('archivedtexts');
        $res = Connection::query($sql);

        $count = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $count++;
            QueryBuilder::table('archtexttags')
                ->where('AgAtID', '=', (int)$record['AtID'])
                ->where('AgT2ID', '=', (int)$tagId)
                ->delete();
        }
        mysqli_free_result($res);

        return "Tag removed in {$count} Texts";
    }

    // =========================================================================
    // Select options HTML (migrated from tags.php)
    // =========================================================================

    /**
     * Get term tag select options HTML for filtering.
     *
     * @param int|string|null $selected Currently selected value
     * @param int|string      $langId   Language ID filter ('' for all)
     *
     * @return string HTML options
     */
    public static function getTermTagSelectOptions(
        int|string|null $selected,
        int|string $langId
    ): string {
        $selected = $selected ?? '';

        $html = '<option value=""' . FormHelper::getSelected($selected, '') . '>';
        $html .= '[Filter off]</option>';

        // Use raw SQL for comma-separated table JOINs
        if ($langId === '') {
            $rows = Connection::preparedFetchAll(
                "SELECT TgID, TgText
                FROM words, tags, wordtags
                WHERE TgID = WtTgID AND WtWoID = WoID
                GROUP BY TgID
                ORDER BY UPPER(TgText)",
                []
            );
        } else {
            $rows = Connection::preparedFetchAll(
                "SELECT TgID, TgText
                FROM words, tags, wordtags
                WHERE TgID = WtTgID AND WtWoID = WoID AND WoLgID = ?
                GROUP BY TgID
                ORDER BY UPPER(TgText)",
                [$langId]
            );
        }

        $count = 0;
        foreach ($rows as $record) {
            $count++;
            $html .= '<option value="' . $record['TgID'] . '"' .
                FormHelper::getSelected($selected, (int) $record['TgID']) . '>' .
                htmlspecialchars($record['TgText'] ?? '', ENT_QUOTES, 'UTF-8') . '</option>';
        }

        if ($count > 0) {
            $html .= '<option disabled="disabled">--------</option>';
            $html .= '<option value="-1"' . FormHelper::getSelected($selected, -1) . '>UNTAGGED</option>';
        }

        return $html;
    }

    /**
     * Get text tag select options HTML for filtering.
     *
     * @param int|string|null $selected Currently selected value
     * @param int|string      $langId   Language ID filter ('' for all)
     *
     * @return string HTML options
     */
    public static function getTextTagSelectOptions(
        int|string|null $selected,
        int|string $langId
    ): string {
        $selected = $selected ?? '';

        $html = '<option value=""' . FormHelper::getSelected($selected, '') . '>';
        $html .= '[Filter off]</option>';

        // Use raw SQL for comma-separated table JOINs
        if ($langId === '') {
            $rows = Connection::preparedFetchAll(
                "SELECT T2ID, T2Text
                FROM texts, tags2, texttags
                WHERE T2ID = TtT2ID AND TtTxID = TxID
                GROUP BY T2ID
                ORDER BY UPPER(T2Text)",
                []
            );
        } else {
            $rows = Connection::preparedFetchAll(
                "SELECT T2ID, T2Text
                FROM texts, tags2, texttags
                WHERE T2ID = TtT2ID AND TtTxID = TxID AND TxLgID = ?
                GROUP BY T2ID
                ORDER BY UPPER(T2Text)",
                [$langId]
            );
        }

        $count = 0;
        foreach ($rows as $record) {
            $count++;
            $html .= '<option value="' . $record['T2ID'] . '"' .
                FormHelper::getSelected($selected, (int) $record['T2ID']) . '>' .
                htmlspecialchars($record['T2Text'] ?? '', ENT_QUOTES, 'UTF-8') . '</option>';
        }

        if ($count > 0) {
            $html .= '<option disabled="disabled">--------</option>';
            $html .= '<option value="-1"' . FormHelper::getSelected($selected, -1) . '>UNTAGGED</option>';
        }

        return $html;
    }

    /**
     * Get text tag select options with text IDs for word list filtering.
     *
     * @param int|string      $langId   Language ID filter
     * @param int|string|null $selected Currently selected value
     *
     * @return string HTML options
     */
    public static function getTextTagSelectOptionsWithTextIds(
        int|string $langId,
        int|string|null $selected
    ): string {
        $selected = $selected ?? '';
        $untaggedOption = '';

        $html = '<option value="&amp;texttag"' . FormHelper::getSelected($selected, '') . '>';
        $html .= '[Filter off]</option>';

        // Use raw SQL for LEFT JOINs and GROUP_CONCAT
        if ($langId) {
            $rows = Connection::preparedFetchAll(
                'SELECT IFNULL(T2Text, 1) AS TagName, TtT2ID AS TagID,
                GROUP_CONCAT(TxID ORDER BY TxID) AS TextID
                FROM texts
                LEFT JOIN texttags ON TxID = TtTxID
                LEFT JOIN tags2 ON TtT2ID = T2ID
                WHERE TxLgID = ?
                GROUP BY UPPER(TagName)',
                [$langId]
            );
        } else {
            $rows = Connection::preparedFetchAll(
                'SELECT IFNULL(T2Text, 1) AS TagName, TtT2ID AS TagID,
                GROUP_CONCAT(TxID ORDER BY TxID) AS TextID
                FROM texts
                LEFT JOIN texttags ON TxID = TtTxID
                LEFT JOIN tags2 ON TtT2ID = T2ID
                GROUP BY UPPER(TagName)',
                []
            );
        }

        foreach ($rows as $record) {
            if ($record['TagName'] == 1) {
                $untaggedOption = '<option disabled="disabled">--------</option>' .
                    '<option value="' . $record['TextID'] . '&amp;texttag=-1"' .
                    FormHelper::getSelected($selected, "-1") . '>UNTAGGED</option>';
            } else {
                $html .= '<option value="' . $record['TextID'] . '&amp;texttag=' .
                    $record['TagID'] . '"' . FormHelper::getSelected($selected, (int) $record['TagID']) .
                    '>' . $record['TagName'] . '</option>';
            }
        }

        return $html . $untaggedOption;
    }

    /**
     * Get archived text tag select options HTML for filtering.
     *
     * @param int|string|null $selected Currently selected value
     * @param int|string      $langId   Language ID filter ('' for all)
     *
     * @return string HTML options
     */
    public static function getArchivedTextTagSelectOptions(
        int|string|null $selected,
        int|string $langId
    ): string {
        $selected = $selected ?? '';

        $html = '<option value=""' . FormHelper::getSelected($selected, '') . '>';
        $html .= '[Filter off]</option>';

        // Use raw SQL for comma-separated table JOINs
        if ($langId === '') {
            $rows = Connection::preparedFetchAll(
                "SELECT T2ID, T2Text
                FROM archivedtexts, tags2, archtexttags
                WHERE T2ID = AgT2ID AND AgAtID = AtID
                GROUP BY T2ID
                ORDER BY UPPER(T2Text)",
                []
            );
        } else {
            $rows = Connection::preparedFetchAll(
                "SELECT T2ID, T2Text
                FROM archivedtexts, tags2, archtexttags
                WHERE T2ID = AgT2ID AND AgAtID = AtID AND AtLgID = ?
                GROUP BY T2ID
                ORDER BY UPPER(T2Text)",
                [$langId]
            );
        }

        $count = 0;
        foreach ($rows as $record) {
            $count++;
            $html .= '<option value="' . $record['T2ID'] . '"' .
                FormHelper::getSelected($selected, (int) $record['T2ID']) . '>' .
                htmlspecialchars($record['T2Text'] ?? '', ENT_QUOTES, 'UTF-8') . '</option>';
        }

        if ($count > 0) {
            $html .= '<option disabled="disabled">--------</option>';
            $html .= '<option value="-1"' . FormHelper::getSelected($selected, -1) . '>UNTAGGED</option>';
        }

        return $html;
    }

    // =========================================================================
    // Helper methods
    // =========================================================================

    /**
     * Get or create a term tag, returning its ID.
     *
     * @param string $tagText Tag text
     *
     * @return int|null Tag ID or null on failure
     */
    private static function getOrCreateTermTag(string $tagText): ?int
    {
        $tagId = Connection::preparedFetchValue(
            'SELECT TgID FROM tags WHERE TgText = ?',
            [$tagText],
            'TgID'
        );

        if (!isset($tagId)) {
            QueryBuilder::table('tags')->insertPrepared(['TgText' => $tagText]);
            $tagId = Connection::preparedFetchValue(
                'SELECT TgID FROM tags WHERE TgText = ?',
                [$tagText],
                'TgID'
            );
        }

        return isset($tagId) ? (int) $tagId : null;
    }

    /**
     * Save word tags from an array of tag names.
     *
     * This is an API-friendly alternative to saveWordTags() that takes an array
     * directly instead of reading from form input.
     *
     * @param int   $wordId   Word ID
     * @param array $tagNames Array of tag name strings
     *
     * @return void
     */
    public static function saveWordTagsFromArray(int $wordId, array $tagNames): void
    {
        // Delete existing tags for this word
        QueryBuilder::table('wordtags')
            ->where('WtWoID', '=', $wordId)
            ->delete();

        if (empty($tagNames)) {
            return;
        }

        // Refresh cache
        self::getAllTermTags(true);

        foreach ($tagNames as $tag) {
            $tag = trim((string) $tag);
            if ($tag === '') {
                continue;
            }

            // Create tag if it doesn't exist
            if (!in_array($tag, $_SESSION['TAGS'])) {
                QueryBuilder::table('tags')->insertPrepared(['TgText' => $tag]);
            }

            // Link tag to word using raw SQL for INSERT...SELECT
            Connection::preparedExecute(
                "INSERT INTO wordtags (WtWoID, WtTgID)
                SELECT ?, TgID
                FROM tags
                WHERE TgText = ?",
                [$wordId, $tag]
            );
        }

        // Refresh cache again after changes
        self::getAllTermTags(true);
    }

    /**
     * Get array of tag names for a word.
     *
     * @param int $wordId Word ID
     *
     * @return array<string> Array of tag name strings
     */
    public static function getWordTagsArray(int $wordId): array
    {
        // Use raw SQL for JOIN
        $result = Connection::preparedFetchAll(
            "SELECT TgText FROM wordtags
             JOIN tags ON TgID = WtTgID
             WHERE WtWoID = ?
             ORDER BY TgText",
            [$wordId]
        );

        return array_map(fn($row) => (string) $row['TgText'], $result);
    }

    /**
     * Get or create a text tag, returning its ID.
     *
     * @param string $tagText Tag text
     *
     * @return int|null Tag ID or null on failure
     */
    private static function getOrCreateTextTag(string $tagText): ?int
    {
        $tagId = Connection::preparedFetchValue(
            'SELECT T2ID FROM tags2 WHERE T2Text = ?',
            [$tagText],
            'T2ID'
        );

        if (!isset($tagId)) {
            QueryBuilder::table('tags2')->insertPrepared(['T2Text' => $tagText]);
            $tagId = Connection::preparedFetchValue(
                'SELECT T2ID FROM tags2 WHERE T2Text = ?',
                [$tagText],
                'T2ID'
            );
        }

        return isset($tagId) ? (int) $tagId : null;
    }
}

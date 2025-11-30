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
use Lwt\Database\Escaping;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Settings;
use Lwt\Database\Maintenance;
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
     * Database table prefix.
     *
     * @var string
     */
    private string $tbpref;

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
     * Constructor - initialize table prefix and tag type.
     *
     * @param string $tagType 'term' for term tags, 'text' for text tags
     */
    public function __construct(string $tagType = 'term')
    {
        $this->tbpref = Globals::getTablePrefix();
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
     * @return string SQL WHERE clause fragment
     */
    public function buildWhereClause(string $query): string
    {
        if ($query === '') {
            return '';
        }

        $sqlQuery = Escaping::toSqlSyntax(str_replace("*", "%", $query));
        return ' and (' . $this->colPrefix . 'Text like ' . $sqlQuery .
               ' or ' . $this->colPrefix . 'Comment like ' . $sqlQuery . ')';
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

        $list = "(" . implode(",", array_map('intval', $tagIds)) . ")";

        $message = Connection::execute(
            'delete from ' . $this->tbpref . $this->tableName .
            ' where ' . $this->colPrefix . 'ID in ' . $list,
            "Deleted"
        );

        $this->cleanupOrphanedLinks();
        Maintenance::adjustAutoIncrement($this->tableName, $this->colPrefix . 'ID');

        return $message;
    }

    /**
     * Delete all tags matching the filter.
     *
     * @param string $whereClause Additional WHERE clause
     *
     * @return string Result message
     */
    public function deleteAll(string $whereClause = ''): string
    {
        $message = Connection::execute(
            'delete from ' . $this->tbpref . $this->tableName .
            ' where (1=1) ' . $whereClause,
            "Deleted"
        );

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
        $message = Connection::execute(
            'delete from ' . $this->tbpref . $this->tableName .
            ' where ' . $this->colPrefix . 'ID = ' . $tagId,
            "Deleted"
        );

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
        return Connection::execute(
            'insert into ' . $this->tbpref . $this->tableName .
            ' (' . $this->colPrefix . 'Text, ' . $this->colPrefix . 'Comment) values(' .
            Escaping::toSqlSyntax($text) . ', ' .
            Escaping::toSqlSyntaxNoNull($comment) . ')',
            "Saved",
            false
        );
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
        return Connection::execute(
            'update ' . $this->tbpref . $this->tableName . ' set ' .
            $this->colPrefix . 'Text = ' . Escaping::toSqlSyntax($text) . ', ' .
            $this->colPrefix . 'Comment = ' . Escaping::toSqlSyntaxNoNull($comment) .
            ' where ' . $this->colPrefix . 'ID = ' . $tagId,
            "Updated",
            false
        );
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
        $sql = 'select * from ' . $this->tbpref . $this->tableName .
               ' where ' . $this->colPrefix . 'ID = ' . $tagId;
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        return $record ?: null;
    }

    /**
     * Get total count of tags matching filter.
     *
     * @param string $whereClause Additional WHERE clause
     *
     * @return int Number of tags
     */
    public function getCount(string $whereClause = ''): int
    {
        $sql = 'select count(' . $this->colPrefix . 'ID) as value from ' .
               $this->tbpref . $this->tableName . ' where (1=1) ' . $whereClause;
        return (int) Connection::fetchValue($sql);
    }

    /**
     * Get tags list with pagination.
     *
     * @param string $whereClause Additional WHERE clause
     * @param string $orderBy     Sort column name (without prefix)
     * @param int    $page        Page number (1-based)
     * @param int    $perPage     Items per page
     *
     * @return array Array of tag records with usage counts
     */
    public function getList(
        string $whereClause = '',
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

        $sql = 'select ' . $this->colPrefix . 'ID, ' .
               $this->colPrefix . 'Text, ' . $this->colPrefix . 'Comment ' .
               'from ' . $this->tbpref . $this->tableName .
               ' where (1=1) ' . $whereClause .
               ' order by ' . $sortColumn . ' ' . $limit;

        $res = Connection::query($sql);
        $tags = [];

        while ($record = mysqli_fetch_assoc($res)) {
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
        mysqli_free_result($res);

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
            $sql = 'select count(*) as value from ' . $this->tbpref .
                   'texttags where TtT2ID=' . $tagId;
        } else {
            $sql = 'select count(*) as value from ' . $this->tbpref .
                   'wordtags where WtTgID=' . $tagId;
        }
        return (int) Connection::fetchValue($sql);
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
        $sql = 'select count(*) as value from ' . $this->tbpref .
               'archtexttags where AgT2ID=' . $tagId;
        return (int) Connection::fetchValue($sql);
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
            return 'edit_texts.php?page=1&query=&tag12=0&tag2=&tag1=' . $tagId;
        }
        return 'edit_words.php?page=1&query=&text=&status=&filterlang=&status=&tag12=0&tag2=&tag1=' . $tagId;
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
                "DELETE " . $this->tbpref . "texttags FROM (" .
                $this->tbpref . "texttags LEFT JOIN " . $this->tbpref .
                "tags2 on TtT2ID = T2ID) WHERE T2ID IS NULL",
                ''
            );
            Connection::execute(
                "DELETE " . $this->tbpref . "archtexttags FROM (" .
                $this->tbpref . "archtexttags LEFT JOIN " . $this->tbpref .
                "tags2 on AgT2ID = T2ID) WHERE T2ID IS NULL",
                ''
            );
        } else {
            Connection::execute(
                "DELETE " . $this->tbpref . "wordtags FROM (" .
                $this->tbpref . "wordtags LEFT JOIN " . $this->tbpref .
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
        $tbpref = Globals::getTablePrefix();
        $cacheKey = $tbpref . self::getUrlBase();

        if (
            !$refresh
            && isset($_SESSION['TAGS'])
            && is_array($_SESSION['TAGS'])
            && isset($_SESSION['TBPREF_TAGS'])
            && $_SESSION['TBPREF_TAGS'] === $cacheKey
        ) {
            return $_SESSION['TAGS'];
        }

        $tags = [];
        $sql = 'SELECT TgText FROM ' . $tbpref . 'tags ORDER BY TgText';
        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $tags[] = (string) $record['TgText'];
        }
        mysqli_free_result($res);

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
        $tbpref = Globals::getTablePrefix();
        $cacheKey = $tbpref . self::getUrlBase();

        if (
            !$refresh
            && isset($_SESSION['TEXTTAGS'])
            && is_array($_SESSION['TEXTTAGS'])
            && isset($_SESSION['TBPREF_TEXTTAGS'])
            && $_SESSION['TBPREF_TEXTTAGS'] === $cacheKey
        ) {
            return $_SESSION['TEXTTAGS'];
        }

        $tags = [];
        $sql = 'SELECT T2Text FROM ' . $tbpref . 'tags2 ORDER BY T2Text';
        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $tags[] = (string) $record['T2Text'];
        }
        mysqli_free_result($res);

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
        $tbpref = Globals::getTablePrefix();
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
                Connection::execute(
                    "INSERT INTO {$tbpref}tags (TgText)
                    VALUES(" . Escaping::toSqlSyntax($tag) . ")"
                );
            }
            Connection::execute(
                "INSERT INTO {$tbpref}wordtags (WtWoID, WtTgID)
                SELECT {$wordId}, TgID
                FROM {$tbpref}tags
                WHERE TgText = " . Escaping::toSqlSyntax($tag)
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
        $tbpref = Globals::getTablePrefix();
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
                Connection::execute(
                    "INSERT INTO {$tbpref}tags2 (T2Text)
                    VALUES(" . Escaping::toSqlSyntax($tag) . ")"
                );
            }
            Connection::execute(
                "INSERT INTO {$tbpref}texttags (TtTxID, TtT2ID)
                SELECT {$textId}, T2ID
                FROM {$tbpref}tags2
                WHERE T2Text = " . Escaping::toSqlSyntax($tag)
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
        $tbpref = Globals::getTablePrefix();
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
                Connection::execute(
                    "INSERT INTO {$tbpref}tags2 (T2Text)
                    VALUES(" . Escaping::toSqlSyntax($tag) . ")"
                );
            }
            Connection::execute(
                "INSERT INTO {$tbpref}archtexttags (AgAtID, AgT2ID)
                SELECT {$textId}, T2ID
                FROM {$tbpref}tags2
                WHERE T2Text = " . Escaping::toSqlSyntax($tag)
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
        $tbpref = Globals::getTablePrefix();
        $html = '<ul id="termtags">';

        if ($wordId > 0) {
            $sql = 'SELECT TgText
                FROM ' . $tbpref . 'wordtags, ' . $tbpref . 'tags
                WHERE TgID = WtTgID AND WtWoID = ' . $wordId . '
                ORDER BY TgText';
            $res = Connection::query($sql);
            while ($record = mysqli_fetch_assoc($res)) {
                $html .= '<li>' . htmlspecialchars($record['TgText'] ?? '', ENT_QUOTES, 'UTF-8') . '</li>';
            }
            mysqli_free_result($res);
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
        $tbpref = Globals::getTablePrefix();
        $html = '<ul id="texttags" class="respinput">';

        if ($textId > 0) {
            $sql = "SELECT T2Text
                FROM {$tbpref}texttags, {$tbpref}tags2
                WHERE T2ID = TtT2ID AND TtTxID = {$textId}
                ORDER BY T2Text";
            $res = Connection::query($sql);
            while ($record = mysqli_fetch_assoc($res)) {
                $html .= '<li>' . htmlspecialchars($record['T2Text'] ?? '', ENT_QUOTES, 'UTF-8') . '</li>';
            }
            mysqli_free_result($res);
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
        $tbpref = Globals::getTablePrefix();
        $html = '<ul id="texttags">';

        if ($textId > 0) {
            $sql = 'SELECT T2Text
                FROM ' . $tbpref . 'archtexttags, ' . $tbpref . 'tags2
                WHERE T2ID = AgT2ID AND AgAtID = ' . $textId . '
                ORDER BY T2Text';
            $res = Connection::query($sql);
            while ($record = mysqli_fetch_assoc($res)) {
                $html .= '<li>' . htmlspecialchars($record['T2Text'] ?? '', ENT_QUOTES, 'UTF-8') . '</li>';
            }
            mysqli_free_result($res);
        }

        return $html . '</ul>';
    }

    /**
     * Get formatted tag list string for a word.
     *
     * @param int    $wordId Word ID
     * @param string $before String to prepend if tags exist
     * @param bool   $brackets Wrap tags in brackets
     * @param bool   $escapeHtml Convert to HTML entities
     *
     * @return string Formatted tag list
     */
    public static function getWordTagListFormatted(
        int $wordId,
        string $before = ' ',
        bool $brackets = true,
        bool $escapeHtml = true
    ): string {
        $tbpref = Globals::getTablePrefix();
        $lbrack = $brackets ? '[' : '';
        $rbrack = $brackets ? ']' : '';

        $result = Connection::fetchValue(
            "SELECT IFNULL(
                GROUP_CONCAT(DISTINCT TgText ORDER BY TgText SEPARATOR ', '),
                ''
            ) AS value
            FROM (
                (
                    {$tbpref}words
                    LEFT JOIN {$tbpref}wordtags ON WoID = WtWoID
                )
                LEFT JOIN {$tbpref}tags ON TgID = WtTgID
            )
            WHERE WoID = {$wordId}"
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
        $tbpref = Globals::getTablePrefix();

        if ($idList === '()') {
            return "Tag added in 0 Terms";
        }

        $tagId = self::getOrCreateTermTag($tagText);
        if ($tagId === null) {
            return "Failed to create tag";
        }

        $sql = 'SELECT WoID
            FROM ' . $tbpref . 'words
            LEFT JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID AND WtTgID = ' . $tagId . '
            WHERE WtTgID IS NULL AND WoID IN ' . $idList;
        $res = Connection::query($sql);

        $count = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $count += (int) Connection::execute(
                'INSERT IGNORE INTO ' . $tbpref . 'wordtags (WtWoID, WtTgID)
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
        $tbpref = Globals::getTablePrefix();

        if ($idList === '()') {
            return "Tag removed in 0 Terms";
        }

        $tagId = Connection::fetchValue(
            'SELECT TgID AS value FROM ' . $tbpref . 'tags
            WHERE TgText = ' . Escaping::toSqlSyntax($tagText)
        );

        if (!isset($tagId)) {
            return "Tag " . $tagText . " not found";
        }

        $sql = 'SELECT WoID FROM ' . $tbpref . 'words WHERE WoID IN ' . $idList;
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
        $tbpref = Globals::getTablePrefix();

        if ($idList === '()') {
            return "Tag added in 0 Texts";
        }

        $tagId = self::getOrCreateTextTag($tagText);
        if ($tagId === null) {
            return "Failed to create tag";
        }

        $sql = 'SELECT TxID FROM ' . $tbpref . 'texts
            LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID AND TtT2ID = ' . $tagId . '
            WHERE TtT2ID IS NULL AND TxID IN ' . $idList;
        $res = Connection::query($sql);

        $count = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $count += (int) Connection::execute(
                'INSERT IGNORE INTO ' . $tbpref . 'texttags (TtTxID, TtT2ID)
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
        $tbpref = Globals::getTablePrefix();

        if ($idList === '()') {
            return "Tag removed in 0 Texts";
        }

        $tagId = Connection::fetchValue(
            'SELECT T2ID AS value FROM ' . $tbpref . 'tags2
            WHERE T2Text = ' . Escaping::toSqlSyntax($tagText)
        );

        if (!isset($tagId)) {
            return "Tag " . $tagText . " not found";
        }

        $sql = 'SELECT TxID FROM ' . $tbpref . 'texts WHERE TxID IN ' . $idList;
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
        $tbpref = Globals::getTablePrefix();

        if ($idList === '()') {
            return "Tag added in 0 Texts";
        }

        $tagId = self::getOrCreateTextTag($tagText);
        if ($tagId === null) {
            return "Failed to create tag";
        }

        $sql = 'SELECT AtID FROM ' . $tbpref . 'archivedtexts
            LEFT JOIN ' . $tbpref . 'archtexttags ON AtID = AgAtID AND AgT2ID = ' . $tagId . '
            WHERE AgT2ID IS NULL AND AtID IN ' . $idList;
        $res = Connection::query($sql);

        $count = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $count += (int) Connection::execute(
                'INSERT IGNORE INTO ' . $tbpref . 'archtexttags (AgAtID, AgT2ID)
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
        $tbpref = Globals::getTablePrefix();

        if ($idList === '()') {
            return "Tag removed in 0 Texts";
        }

        $tagId = Connection::fetchValue(
            'SELECT T2ID AS value FROM ' . $tbpref . 'tags2
            WHERE T2Text = ' . Escaping::toSqlSyntax($tagText)
        );

        if (!isset($tagId)) {
            return "Tag " . $tagText . " not found";
        }

        $sql = 'SELECT AtID FROM ' . $tbpref . 'archivedtexts WHERE AtID IN ' . $idList;
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
        $tbpref = Globals::getTablePrefix();
        $selected = $selected ?? '';

        $html = '<option value=""' . FormHelper::getSelected($selected, '') . '>';
        $html .= '[Filter off]</option>';

        if ($langId === '') {
            $sql = "SELECT TgID, TgText
                FROM {$tbpref}words, {$tbpref}tags, {$tbpref}wordtags
                WHERE TgID = WtTgID AND WtWoID = WoID
                GROUP BY TgID
                ORDER BY UPPER(TgText)";
        } else {
            $sql = "SELECT TgID, TgText
                FROM {$tbpref}words, {$tbpref}tags, {$tbpref}wordtags
                WHERE TgID = WtTgID AND WtWoID = WoID AND WoLgID = {$langId}
                GROUP BY TgID
                ORDER BY UPPER(TgText)";
        }

        $res = Connection::query($sql);
        $count = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $count++;
            $html .= '<option value="' . $record['TgID'] . '"' .
                FormHelper::getSelected($selected, (int) $record['TgID']) . '>' .
                htmlspecialchars($record['TgText'] ?? '', ENT_QUOTES, 'UTF-8') . '</option>';
        }
        mysqli_free_result($res);

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
        $tbpref = Globals::getTablePrefix();
        $selected = $selected ?? '';

        $html = '<option value=""' . FormHelper::getSelected($selected, '') . '>';
        $html .= '[Filter off]</option>';

        if ($langId === '') {
            $sql = "SELECT T2ID, T2Text
                FROM {$tbpref}texts, {$tbpref}tags2, {$tbpref}texttags
                WHERE T2ID = TtT2ID AND TtTxID = TxID
                GROUP BY T2ID
                ORDER BY UPPER(T2Text)";
        } else {
            $sql = "SELECT T2ID, T2Text
                FROM {$tbpref}texts, {$tbpref}tags2, {$tbpref}texttags
                WHERE T2ID = TtT2ID AND TtTxID = TxID AND TxLgID = {$langId}
                GROUP BY T2ID
                ORDER BY UPPER(T2Text)";
        }

        $res = Connection::query($sql);
        $count = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $count++;
            $html .= '<option value="' . $record['T2ID'] . '"' .
                FormHelper::getSelected($selected, (int) $record['T2ID']) . '>' .
                htmlspecialchars($record['T2Text'] ?? '', ENT_QUOTES, 'UTF-8') . '</option>';
        }
        mysqli_free_result($res);

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
        $tbpref = Globals::getTablePrefix();
        $selected = $selected ?? '';
        $untaggedOption = '';

        $html = '<option value="&amp;texttag"' . FormHelper::getSelected($selected, '') . '>';
        $html .= '[Filter off]</option>';

        $sql = 'SELECT IFNULL(T2Text, 1) AS TagName, TtT2ID AS TagID,
            GROUP_CONCAT(TxID ORDER BY TxID) AS TextID
            FROM ' . $tbpref . 'texts
            LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID
            LEFT JOIN ' . $tbpref . 'tags2 ON TtT2ID = T2ID';
        if ($langId) {
            $sql .= ' WHERE TxLgID=' . $langId;
        }
        $sql .= ' GROUP BY UPPER(TagName)';

        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
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
        mysqli_free_result($res);

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
        $tbpref = Globals::getTablePrefix();
        $selected = $selected ?? '';

        $html = '<option value=""' . FormHelper::getSelected($selected, '') . '>';
        $html .= '[Filter off]</option>';

        if ($langId === '') {
            $sql = "SELECT T2ID, T2Text
                FROM {$tbpref}archivedtexts, {$tbpref}tags2, {$tbpref}archtexttags
                WHERE T2ID = AgT2ID AND AgAtID = AtID
                GROUP BY T2ID
                ORDER BY UPPER(T2Text)";
        } else {
            $sql = "SELECT T2ID, T2Text
                FROM {$tbpref}archivedtexts, {$tbpref}tags2, {$tbpref}archtexttags
                WHERE T2ID = AgT2ID AND AgAtID = AtID AND AtLgID = {$langId}
                GROUP BY T2ID
                ORDER BY UPPER(T2Text)";
        }

        $res = Connection::query($sql);
        $count = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $count++;
            $html .= '<option value="' . $record['T2ID'] . '"' .
                FormHelper::getSelected($selected, (int) $record['T2ID']) . '>' .
                htmlspecialchars($record['T2Text'] ?? '', ENT_QUOTES, 'UTF-8') . '</option>';
        }
        mysqli_free_result($res);

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
        $tbpref = Globals::getTablePrefix();

        $tagId = Connection::fetchValue(
            'SELECT TgID AS value FROM ' . $tbpref . 'tags
            WHERE TgText = ' . Escaping::toSqlSyntax($tagText)
        );

        if (!isset($tagId)) {
            Connection::execute(
                'INSERT INTO ' . $tbpref . 'tags (TgText)
                VALUES(' . Escaping::toSqlSyntax($tagText) . ')'
            );
            $tagId = Connection::fetchValue(
                'SELECT TgID AS value FROM ' . $tbpref . 'tags
                WHERE TgText = ' . Escaping::toSqlSyntax($tagText)
            );
        }

        return isset($tagId) ? (int) $tagId : null;
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
        $tbpref = Globals::getTablePrefix();

        $tagId = Connection::fetchValue(
            'SELECT T2ID AS value FROM ' . $tbpref . 'tags2
            WHERE T2Text = ' . Escaping::toSqlSyntax($tagText)
        );

        if (!isset($tagId)) {
            Connection::execute(
                'INSERT INTO ' . $tbpref . 'tags2 (T2Text)
                VALUES(' . Escaping::toSqlSyntax($tagText) . ')'
            );
            $tagId = Connection::fetchValue(
                'SELECT T2ID AS value FROM ' . $tbpref . 'tags2
                WHERE T2Text = ' . Escaping::toSqlSyntax($tagText)
            );
        }

        return isset($tagId) ? (int) $tagId : null;
    }
}

<?php

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
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Settings;
use Lwt\Database\Maintenance;

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
            $tagId = $record[$this->colPrefix . 'ID'];
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
}

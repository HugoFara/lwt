<?php declare(strict_types=1);
/**
 * Text Service - Business logic for text management
 *
 * Handles both active texts (texts table) and archived texts (archivedtexts table).
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
use Lwt\Core\Http\UrlUtilities;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Settings;
use Lwt\Database\Maintenance;
use Lwt\Database\TextParsing;
use Lwt\Database\UserScopedQuery;
use Lwt\Database\Validation;
use Lwt\Services\TagService;
use Lwt\Services\ExportService;
use Lwt\Services\SentenceService;

/**
 * Service class for managing texts (active and archived).
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TextService
{

    // =====================
    // ARCHIVED TEXT METHODS
    // =====================

    /**
     * Get count of archived texts matching filters.
     *
     * @param string $whLang  Language WHERE clause
     * @param string $whQuery Query WHERE clause
     * @param string $whTag   Tag HAVING clause
     *
     * @return int Number of matching archived texts
     */
    public function getArchivedTextCount(
        string $whLang,
        string $whQuery,
        string $whTag
    ): int {
        $sql = "SELECT COUNT(*) AS value FROM (
            SELECT AtID FROM (
                archivedtexts
                LEFT JOIN archtexttags ON AtID = AgAtID
            ) WHERE (1=1) {$whLang}{$whQuery}
            GROUP BY AtID {$whTag}
        ) AS dummy" . UserScopedQuery::forTable('archivedtexts');
        return (int) Connection::fetchValue($sql);
    }

    /**
     * Get archived texts list with pagination.
     *
     * @param string $whLang      Language WHERE clause
     * @param string $whQuery     Query WHERE clause
     * @param string $whTag       Tag HAVING clause
     * @param int    $sort        Sort index (1-based)
     * @param int    $page        Page number (1-based)
     * @param int    $perPage     Items per page
     *
     * @return array Array of archived text records
     */
    public function getArchivedTextsList(
        string $whLang,
        string $whQuery,
        string $whTag,
        int $sort,
        int $page,
        int $perPage
    ): array {
        $sorts = ['AtTitle', 'AtID desc', 'AtID'];
        $sortColumn = $sorts[max(0, min($sort - 1, count($sorts) - 1))];
        $offset = ($page - 1) * $perPage;
        $limit = "LIMIT {$offset},{$perPage}";

        $sql = "SELECT AtID, AtTitle, LgName, AtAudioURI, AtSourceURI,
            LENGTH(AtAnnotatedText) AS annotlen,
            IFNULL(GROUP_CONCAT(DISTINCT T2Text ORDER BY T2Text SEPARATOR ','), '') AS taglist
            FROM (
                (archivedtexts
                LEFT JOIN archtexttags ON AtID = AgAtID)
                LEFT JOIN tags2 ON T2ID = AgT2ID
            ), languages
            WHERE LgID=AtLgID {$whLang}{$whQuery}
            GROUP BY AtID {$whTag}
            ORDER BY {$sortColumn}
            {$limit}"
            . UserScopedQuery::forTable('archivedtexts')
            . UserScopedQuery::forTable('tags2')
            . UserScopedQuery::forTable('languages');

        $res = Connection::query($sql);
        $texts = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $texts[] = $record;
        }
        mysqli_free_result($res);
        return $texts;
    }

    /**
     * Get a single archived text by ID.
     *
     * @param int $textId Archived text ID
     *
     * @return array|null Archived text record or null
     */
    public function getArchivedTextById(int $textId): ?array
    {
        $bindings = [$textId];
        return Connection::preparedFetchOne(
            "SELECT AtLgID, AtTitle, AtText, AtAudioURI, AtSourceURI,
            LENGTH(AtAnnotatedText) AS annotlen
            FROM archivedtexts
            WHERE AtID = ?"
            . UserScopedQuery::forTablePrepared('archivedtexts', $bindings),
            $bindings
        );
    }

    /**
     * Delete an archived text.
     *
     * @param int $textId Archived text ID
     *
     * @return string Result message
     */
    public function deleteArchivedText(int $textId): string
    {
        $deleted = QueryBuilder::table('archivedtexts')
            ->where('AtID', '=', $textId)
            ->delete();
        $message = "Archived Texts deleted: $deleted";
        Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
        $this->cleanupArchivedTextTags();
        return $message;
    }

    /**
     * Delete multiple archived texts.
     *
     * @param array $textIds Array of archived text IDs
     *
     * @return string Result message
     */
    public function deleteArchivedTexts(array $textIds): string
    {
        if (empty($textIds)) {
            return "Multiple Actions: 0";
        }

        $affectedRows = QueryBuilder::table('archivedtexts')
            ->whereIn('AtID', array_map('intval', $textIds))
            ->delete();
        Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
        $this->cleanupArchivedTextTags();
        return "Archived Texts deleted: $affectedRows";
    }

    /**
     * Unarchive a text (move from archived to active).
     *
     * @param int $archivedId Archived text ID
     *
     * @return array{message: string, textId: int|null} Result with message and new text ID
     */
    public function unarchiveText(int $archivedId): array
    {
        // Get language ID first
        $bindings = [$archivedId];
        $lgId = Connection::preparedFetchValue(
            "SELECT AtLgID AS value FROM archivedtexts
            WHERE AtID = ?"
            . UserScopedQuery::forTablePrepared('archivedtexts', $bindings),
            $bindings
        );

        if ($lgId === null) {
            return ['message' => 'Archived text not found', 'textId' => null];
        }

        // Insert into active texts
        $bindings1 = [$archivedId];
        $inserted = Connection::preparedExecute(
            "INSERT INTO texts (
                TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
            ) SELECT AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI
            FROM archivedtexts
            WHERE AtID = ?"
            . UserScopedQuery::forTablePrepared('archivedtexts', $bindings1),
            $bindings1
        );
        $insertedMsg = "Texts added: $inserted";

        $textId = Connection::lastInsertId();

        // Copy tags
        $bindings2 = [$textId, $archivedId];
        Connection::preparedExecute(
            "INSERT INTO texttags (TtTxID, TtT2ID)
            SELECT ?, AgT2ID
            FROM archtexttags
            WHERE AgAtID = ?"
            . UserScopedQuery::forTablePrepared('archtexttags', $bindings2, '', 'archivedtexts'),
            $bindings2
        );

        // Parse the text
        $bindings3 = [$textId];
        $textContent = Connection::preparedFetchValue(
            "SELECT TxText AS value FROM texts WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings3),
            $bindings3
        );
        TextParsing::splitCheck($textContent, (int) $lgId, $textId);

        // Delete from archived
        $deleted = QueryBuilder::table('archivedtexts')
            ->where('AtID', '=', $archivedId)
            ->delete();
        $deleted = "Archived Texts deleted: $deleted";

        Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
        $this->cleanupArchivedTextTags();

        // Get statistics
        $bindings4 = [$textId];
        $sentenceCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS value FROM sentences WHERE SeTxID = ?"
            . UserScopedQuery::forTablePrepared('sentences', $bindings4, '', 'texts'),
            $bindings4
        );
        $bindings5 = [$textId];
        $itemCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS value FROM textitems2 WHERE Ti2TxID = ?"
            . UserScopedQuery::forTablePrepared('textitems2', $bindings5, '', 'texts'),
            $bindings5
        );

        $message = "{$deleted} / {$insertedMsg} / Sentences added: {$sentenceCount} / Text items added: {$itemCount}";

        return ['message' => $message, 'textId' => $textId];
    }

    /**
     * Unarchive multiple texts.
     *
     * @param array $archivedIds Array of archived text IDs
     *
     * @return string Result message
     */
    public function unarchiveTexts(array $archivedIds): string
    {
        if (empty($archivedIds)) {
            return "Multiple Actions: 0";
        }

        $count = 0;
        $ids = array_map('intval', $archivedIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $records = Connection::preparedFetchAll(
            "SELECT AtID, AtLgID FROM archivedtexts WHERE AtID IN ({$placeholders})"
            . UserScopedQuery::forTablePrepared('archivedtexts', $ids),
            $ids
        );

        foreach ($records as $record) {
            $ida = $record['AtID'];
            $bindings1 = [$ida];
            $mess = Connection::preparedExecute(
                "INSERT INTO texts (
                    TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
                ) SELECT AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI
                FROM archivedtexts
                WHERE AtID = ?"
                . UserScopedQuery::forTablePrepared('archivedtexts', $bindings1),
                $bindings1
            );
            $count += $mess;

            $id = Connection::lastInsertId();

            $bindings2 = [$id, $ida];
            Connection::preparedExecute(
                "INSERT INTO texttags (TtTxID, TtT2ID)
                SELECT ?, AgT2ID
                FROM archtexttags
                WHERE AgAtID = ?"
                . UserScopedQuery::forTablePrepared('archtexttags', $bindings2, '', 'archivedtexts'),
                $bindings2
            );

            $bindings3 = [$id];
            $textContent = Connection::preparedFetchValue(
                "SELECT TxText AS value FROM texts WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings3),
                $bindings3
            );
            TextParsing::splitCheck($textContent, $record['AtLgID'], $id);

            QueryBuilder::table('archivedtexts')
                ->where('AtID', '=', $ida)
                ->delete();
        }

        Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
        $this->cleanupArchivedTextTags();

        return "Unarchived Text(s): {$count}";
    }

    /**
     * Update an archived text.
     *
     * @param int    $textId    Archived text ID
     * @param int    $lgId      Language ID
     * @param string $title     Title
     * @param string $text      Text content
     * @param string $audioUri  Audio URI
     * @param string $sourceUri Source URI
     *
     * @return string Result message
     */
    public function updateArchivedText(
        int $textId,
        int $lgId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri
    ): string {
        // Check if text content changed
        $bindings1 = [$textId];
        $oldText = Connection::preparedFetchValue(
            "SELECT AtText AS value FROM archivedtexts WHERE AtID = ?"
            . UserScopedQuery::forTablePrepared('archivedtexts', $bindings1),
            $bindings1
        );
        $textsdiffer = $text !== $oldText;

        $bindings2 = [$lgId, $title, $text, $audioUri, $sourceUri, $textId];
        $affected = Connection::preparedExecute(
            "UPDATE archivedtexts SET
                AtLgID = ?, AtTitle = ?, AtText = ?, AtAudioURI = ?, AtSourceURI = ?
             WHERE AtID = ?"
            . UserScopedQuery::forTablePrepared('archivedtexts', $bindings2),
            $bindings2
        );

        $message = $affected > 0 ? "Updated: {$affected}" : "Updated: 0";

        // Clear annotation if text changed
        if ($affected > 0 && $textsdiffer) {
            $bindings3 = [$textId];
            Connection::preparedExecute(
                "UPDATE archivedtexts SET AtAnnotatedText = '' WHERE AtID = ?"
                . UserScopedQuery::forTablePrepared('archivedtexts', $bindings3),
                $bindings3
            );
        }

        return $message;
    }

    /**
     * Clean up orphaned archived text tags.
     *
     * @return void
     */
    private function cleanupArchivedTextTags(): void
    {
        Connection::execute(
            "DELETE archtexttags
            FROM (
                archtexttags
                LEFT JOIN archivedtexts ON AgAtID = AtID
            )
            WHERE AtID IS NULL"
            . UserScopedQuery::forTable('archtexttags', '', 'archivedtexts'),
            ''
        );
    }

    // =======================
    // FILTER BUILDING METHODS
    // =======================

    /**
     * Build WHERE clause for query filtering (archived texts).
     *
     * Note: This method builds dynamic SQL with escaped values for use in
     * complex queries that combine multiple WHERE clauses. The values are
     * properly escaped using prepared statement binding.
     *
     * @param string $query     Query string
     * @param string $queryMode Query mode ('title,text', 'title', 'text')
     * @param string $regexMode Regex mode ('', 'r', etc.)
     *
     * @return array{clause: string, params: array} SQL WHERE clause fragment and parameters
     */
    public function buildArchivedQueryWhereClause(
        string $query,
        string $queryMode,
        string $regexMode
    ): array {
        if ($query === '') {
            return ['clause' => '', 'params' => []];
        }

        $searchValue = $regexMode === ''
            ? str_replace("*", "%", mb_strtolower($query, 'UTF-8'))
            : $query;
        $operator = $regexMode . 'LIKE';

        switch ($queryMode) {
            case 'title,text':
                return [
                    'clause' => " AND (AtTitle {$operator} ? OR AtText {$operator} ?)",
                    'params' => [$searchValue, $searchValue]
                ];
            case 'title':
                return [
                    'clause' => " AND (AtTitle {$operator} ?)",
                    'params' => [$searchValue]
                ];
            case 'text':
                return [
                    'clause' => " AND (AtText {$operator} ?)",
                    'params' => [$searchValue]
                ];
            default:
                return [
                    'clause' => " AND (AtTitle {$operator} ? OR AtText {$operator} ?)",
                    'params' => [$searchValue, $searchValue]
                ];
        }
    }

    /**
     * Build HAVING clause for tag filtering (archived texts).
     *
     * @param string|int $tag1  First tag filter
     * @param string|int $tag2  Second tag filter
     * @param string     $tag12 AND/OR operator
     *
     * @return string SQL HAVING clause
     */
    public function buildArchivedTagHavingClause($tag1, $tag2, string $tag12): string
    {
        if ($tag1 === '' && $tag2 === '') {
            return '';
        }

        $whTag1 = null;
        $whTag2 = null;

        if ($tag1 !== '') {
            if ($tag1 == -1) {
                $whTag1 = "GROUP_CONCAT(AgT2ID) IS NULL";
            } else {
                $whTag1 = "CONCAT('/', GROUP_CONCAT(AgT2ID SEPARATOR '/'), '/') LIKE '%/{$tag1}/%'";
            }
        }

        if ($tag2 !== '') {
            if ($tag2 == -1) {
                $whTag2 = "GROUP_CONCAT(AgT2ID) IS NULL";
            } else {
                $whTag2 = "CONCAT('/', GROUP_CONCAT(AgT2ID SEPARATOR '/'), '/') LIKE '%/{$tag2}/%'";
            }
        }

        if ($tag1 !== '' && $tag2 === '') {
            return " HAVING ({$whTag1})";
        }
        if ($tag2 !== '' && $tag1 === '') {
            return " HAVING ({$whTag2})";
        }

        $operator = $tag12 ? 'AND' : 'OR';
        return " HAVING (({$whTag1}) {$operator} ({$whTag2}))";
    }

    /**
     * Validate regex query (returns empty string if invalid).
     *
     * @param string $query     Query string
     * @param string $regexMode Regex mode
     *
     * @return bool True if valid, false if invalid
     */
    public function validateRegexQuery(string $query, string $regexMode): bool
    {
        if ($query === '' || $regexMode === '') {
            return true;
        }

        try {
            $stmt = Connection::prepare('SELECT "test" RLIKE ?');
            $stmt->bind('s', $query)->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // ==================
    // PAGINATION METHODS
    // ==================

    /**
     * Get maximum archived texts per page setting.
     *
     * @return int Items per page
     */
    public function getArchivedTextsPerPage(): int
    {
        return (int) Settings::getWithDefault('set-archivedtexts-per-page');
    }

    /**
     * Get maximum texts per page setting.
     *
     * @return int Items per page
     */
    public function getTextsPerPage(): int
    {
        return (int) Settings::getWithDefault('set-texts-per-page');
    }

    /**
     * Calculate pagination info.
     *
     * @param int $totalCount  Total number of items
     * @param int $currentPage Current page number
     * @param int $perPage     Items per page
     *
     * @return array{pages: int, currentPage: int, limit: string}
     */
    public function getPagination(int $totalCount, int $currentPage, int $perPage): array
    {
        $pages = $totalCount === 0 ? 0 : (int) ceil($totalCount / $perPage);

        if ($currentPage < 1) {
            $currentPage = 1;
        }
        if ($currentPage > $pages && $pages > 0) {
            $currentPage = $pages;
        }

        $offset = ($currentPage - 1) * $perPage;
        $limit = "LIMIT {$offset},{$perPage}";

        return [
            'pages' => $pages,
            'currentPage' => $currentPage,
            'limit' => $limit
        ];
    }

    // =====================
    // ACTIVE TEXT METHODS
    // =====================

    /**
     * Get a single active text by ID.
     *
     * @param int $textId Text ID
     *
     * @return array|null Text record or null
     */
    public function getTextById(int $textId): ?array
    {
        $bindings = [$textId];
        return Connection::preparedFetchOne(
            "SELECT TxID, TxLgID, TxTitle, TxText, TxAudioURI, TxSourceURI,
            TxAnnotatedText <> '' AS annot_exists
            FROM texts
            WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings
        );
    }

    /**
     * Delete an active text.
     *
     * @param int $textId Text ID
     *
     * @return string Result message
     */
    public function deleteText(int $textId): string
    {
        $count3 = QueryBuilder::table('textitems2')
            ->where('Ti2TxID', '=', $textId)
            ->delete();
        $count2 = QueryBuilder::table('sentences')
            ->where('SeTxID', '=', $textId)
            ->delete();
        $count1 = QueryBuilder::table('texts')
            ->where('TxID', '=', $textId)
            ->delete();

        Maintenance::adjustAutoIncrement('texts', 'TxID');
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        $this->cleanupTextTags();

        return "Texts deleted: $count1 / Sentences deleted: $count2 / Text items deleted: $count3";
    }

    /**
     * Archive an active text.
     *
     * @param int $textId Text ID
     *
     * @return string Result message
     */
    public function archiveText(int $textId): string
    {
        // Delete parsed data
        $count3 = QueryBuilder::table('textitems2')
            ->where('Ti2TxID', '=', $textId)
            ->delete();
        $count2 = QueryBuilder::table('sentences')
            ->where('SeTxID', '=', $textId)
            ->delete();

        // Insert into archived
        $bindings1 = [$textId];
        $msg4 = Connection::preparedExecute(
            "INSERT INTO archivedtexts (
                AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI
            ) SELECT TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
            FROM texts
            WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings1),
            $bindings1
        );
        $msg4 = "Archived Texts saved: $msg4";

        $archiveId = Connection::lastInsertId();

        // Copy tags
        $bindings2 = [$archiveId, $textId];
        Connection::preparedExecute(
            "INSERT INTO archtexttags (AgAtID, AgT2ID)
            SELECT ?, TtT2ID
            FROM texttags
            WHERE TtTxID = ?"
            . UserScopedQuery::forTablePrepared('texttags', $bindings2, '', 'texts'),
            $bindings2
        );

        // Delete from active
        $count1 = QueryBuilder::table('texts')
            ->where('TxID', '=', $textId)
            ->delete();

        Maintenance::adjustAutoIncrement('texts', 'TxID');
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        $this->cleanupTextTags();

        return "{$msg4} / Texts deleted: $count1 / Sentences deleted: $count2 / Text items deleted: $count3";
    }

    /**
     * Clean up orphaned text tags.
     *
     * @return void
     */
    private function cleanupTextTags(): void
    {
        Connection::execute(
            "DELETE texttags
            FROM (
                texttags
                LEFT JOIN texts ON TtTxID = TxID
            )
            WHERE TxID IS NULL"
            . UserScopedQuery::forTable('texttags', '', 'texts'),
            ''
        );
    }

    // ===========================
    // TEXT LIST/FILTER METHODS
    // ===========================

    /**
     * Get count of active texts matching filters.
     *
     * @param string $whLang  Language WHERE clause
     * @param string $whQuery Query WHERE clause
     * @param string $whTag   Tag HAVING clause
     *
     * @return int Number of matching texts
     */
    public function getTextCount(
        string $whLang,
        string $whQuery,
        string $whTag
    ): int {
        $sql = "SELECT COUNT(*) AS value FROM (
            SELECT TxID FROM (
                texts
                LEFT JOIN texttags ON TxID = TtTxID
            ) WHERE (1=1) {$whLang}{$whQuery}
            GROUP BY TxID {$whTag}
        ) AS dummy" . UserScopedQuery::forTable('texts');
        return (int) Connection::fetchValue($sql);
    }

    /**
     * Get active texts list with pagination.
     *
     * @param string $whLang  Language WHERE clause
     * @param string $whQuery Query WHERE clause
     * @param string $whTag   Tag HAVING clause
     * @param int    $sort    Sort index (1-based)
     * @param int    $page    Page number (1-based)
     * @param int    $perPage Items per page
     *
     * @return array Array of text records
     */
    public function getTextsList(
        string $whLang,
        string $whQuery,
        string $whTag,
        int $sort,
        int $page,
        int $perPage
    ): array {
        $sorts = ['TxTitle', 'TxID desc', 'TxID asc'];
        $sortColumn = $sorts[max(0, min($sort - 1, count($sorts) - 1))];
        $offset = ($page - 1) * $perPage;
        $limit = "LIMIT {$offset},{$perPage}";

        $sql = "SELECT TxID, TxTitle, LgName, TxAudioURI, TxSourceURI,
            LENGTH(TxAnnotatedText) AS annotlen,
            IFNULL(GROUP_CONCAT(DISTINCT T2Text ORDER BY T2Text SEPARATOR ','), '') AS taglist
            FROM (
                (texts LEFT JOIN texttags ON TxID = TtTxID)
                LEFT JOIN tags2 ON T2ID = TtT2ID
            ), languages
            WHERE LgID=TxLgID {$whLang}{$whQuery}
            GROUP BY TxID {$whTag}
            ORDER BY {$sortColumn}
            {$limit}"
            . UserScopedQuery::forTable('texts')
            . UserScopedQuery::forTable('tags2')
            . UserScopedQuery::forTable('languages');

        $res = Connection::query($sql);
        $texts = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $texts[] = $record;
        }
        mysqli_free_result($res);
        return $texts;
    }

    /**
     * Get paginated texts for a specific language.
     *
     * Used by the grouped texts page to load texts per language section.
     *
     * @param int $langId  Language ID
     * @param int $page    Page number (1-based)
     * @param int $perPage Items per page
     * @param int $sort    Sort index (1-based): 1=title, 2=newest, 3=oldest
     *
     * @return array{texts: array, pagination: array{current_page: int, per_page: int, total: int, total_pages: int}}
     */
    public function getTextsForLanguage(
        int $langId,
        int $page,
        int $perPage,
        int $sort
    ): array {
        $sorts = ['TxTitle', 'TxID DESC', 'TxID ASC'];
        $sortColumn = $sorts[max(0, min($sort - 1, count($sorts) - 1))];
        $offset = ($page - 1) * $perPage;

        // Get total count
        $bindings1 = [$langId];
        $total = (int) Connection::preparedFetchValue(
            "SELECT COUNT(*) AS value FROM texts WHERE TxLgID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings1),
            $bindings1
        );
        $totalPages = (int) ceil($total / $perPage);

        // Get texts with tags
        $bindings2 = [$langId, $offset, $perPage];
        $records = Connection::preparedFetchAll(
            "SELECT TxID, TxTitle, TxAudioURI, TxSourceURI,
            LENGTH(TxAnnotatedText) AS annotlen,
            IFNULL(GROUP_CONCAT(DISTINCT T2Text ORDER BY T2Text SEPARATOR ','), '') AS taglist
            FROM (
                (texts LEFT JOIN texttags ON TxID = TtTxID)
                LEFT JOIN tags2 ON T2ID = TtT2ID
            )
            WHERE TxLgID = ?
            GROUP BY TxID
            ORDER BY {$sortColumn}
            LIMIT ?, ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings2)
            . UserScopedQuery::forTablePrepared('tags2', $bindings2),
            $bindings2
        );

        $texts = [];
        foreach ($records as $record) {
            $texts[] = [
                'id' => (int) $record['TxID'],
                'title' => (string) $record['TxTitle'],
                'has_audio' => !empty($record['TxAudioURI']),
                'source_uri' => (string) ($record['TxSourceURI'] ?? ''),
                'has_source' => !empty($record['TxSourceURI']) && substr($record['TxSourceURI'], 0, 1) !== '#',
                'annotated' => !empty($record['annotlen']),
                'taglist' => (string) $record['taglist']
            ];
        }

        return [
            'texts' => $texts,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Get paginated archived texts for a specific language.
     *
     * Used by the grouped archived texts page to load texts per language section.
     *
     * @param int $langId  Language ID
     * @param int $page    Page number (1-based)
     * @param int $perPage Items per page
     * @param int $sort    Sort index (1-based): 1=title, 2=newest, 3=oldest
     *
     * @return array{texts: array, pagination: array{current_page: int, per_page: int, total: int, total_pages: int}}
     */
    public function getArchivedTextsForLanguage(
        int $langId,
        int $page,
        int $perPage,
        int $sort
    ): array {
        $sorts = ['AtTitle', 'AtID DESC', 'AtID ASC'];
        $sortColumn = $sorts[max(0, min($sort - 1, count($sorts) - 1))];
        $offset = ($page - 1) * $perPage;

        // Get total count
        $bindings1 = [$langId];
        $total = (int) Connection::preparedFetchValue(
            "SELECT COUNT(*) AS value FROM archivedtexts WHERE AtLgID = ?"
            . UserScopedQuery::forTablePrepared('archivedtexts', $bindings1),
            $bindings1
        );
        $totalPages = (int) ceil($total / $perPage);

        // Get archived texts with tags
        $bindings2 = [$langId, $offset, $perPage];
        $records = Connection::preparedFetchAll(
            "SELECT AtID, AtTitle, AtAudioURI, AtSourceURI,
            LENGTH(AtAnnotatedText) AS annotlen,
            IFNULL(GROUP_CONCAT(DISTINCT T2Text ORDER BY T2Text SEPARATOR ','), '') AS taglist
            FROM (
                (archivedtexts LEFT JOIN archtexttags ON AtID = AgAtID)
                LEFT JOIN tags2 ON T2ID = AgT2ID
            )
            WHERE AtLgID = ?
            GROUP BY AtID
            ORDER BY {$sortColumn}
            LIMIT ?, ?"
            . UserScopedQuery::forTablePrepared('archivedtexts', $bindings2)
            . UserScopedQuery::forTablePrepared('tags2', $bindings2),
            $bindings2
        );

        $texts = [];
        foreach ($records as $record) {
            $texts[] = [
                'id' => (int) $record['AtID'],
                'title' => (string) $record['AtTitle'],
                'has_audio' => !empty($record['AtAudioURI']),
                'source_uri' => (string) ($record['AtSourceURI'] ?? ''),
                'has_source' => !empty($record['AtSourceURI']),
                'annotated' => !empty($record['annotlen']),
                'taglist' => (string) $record['taglist']
            ];
        }

        return [
            'texts' => $texts,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Build WHERE clause for query filtering (active texts).
     *
     * Note: This method builds dynamic SQL with escaped values for use in
     * complex queries that combine multiple WHERE clauses. The values are
     * properly escaped using prepared statement binding.
     *
     * @param string $query     Query string
     * @param string $queryMode Query mode ('title,text', 'title', 'text')
     * @param string $regexMode Regex mode ('', 'r', etc.)
     *
     * @return array{clause: string, params: array} SQL WHERE clause fragment and parameters
     */
    public function buildTextQueryWhereClause(
        string $query,
        string $queryMode,
        string $regexMode
    ): array {
        if ($query === '') {
            return ['clause' => '', 'params' => []];
        }

        $searchValue = $regexMode === ''
            ? str_replace("*", "%", mb_strtolower($query, 'UTF-8'))
            : $query;
        $operator = $regexMode . 'LIKE';

        switch ($queryMode) {
            case 'title,text':
                return [
                    'clause' => " AND (TxTitle {$operator} ? OR TxText {$operator} ?)",
                    'params' => [$searchValue, $searchValue]
                ];
            case 'title':
                return [
                    'clause' => " AND (TxTitle {$operator} ?)",
                    'params' => [$searchValue]
                ];
            case 'text':
                return [
                    'clause' => " AND (TxText {$operator} ?)",
                    'params' => [$searchValue]
                ];
            default:
                return [
                    'clause' => " AND (TxTitle {$operator} ? OR TxText {$operator} ?)",
                    'params' => [$searchValue, $searchValue]
                ];
        }
    }

    /**
     * Build HAVING clause for tag filtering (active texts).
     *
     * @param string|int $tag1  First tag filter
     * @param string|int $tag2  Second tag filter
     * @param string     $tag12 AND/OR operator
     *
     * @return string SQL HAVING clause
     */
    public function buildTextTagHavingClause($tag1, $tag2, string $tag12): string
    {
        if ($tag1 === '' && $tag2 === '') {
            return '';
        }

        $whTag1 = null;
        $whTag2 = null;

        if ($tag1 !== '') {
            if ($tag1 == -1) {
                $whTag1 = "GROUP_CONCAT(TtT2ID) IS NULL";
            } else {
                $whTag1 = "CONCAT('/', GROUP_CONCAT(TtT2ID SEPARATOR '/'), '/') LIKE '%/{$tag1}/%'";
            }
        }

        if ($tag2 !== '') {
            if ($tag2 == -1) {
                $whTag2 = "GROUP_CONCAT(TtT2ID) IS NULL";
            } else {
                $whTag2 = "CONCAT('/', GROUP_CONCAT(TtT2ID SEPARATOR '/'), '/') LIKE '%/{$tag2}/%'";
            }
        }

        if ($tag1 !== '' && $tag2 === '') {
            return " HAVING ({$whTag1})";
        }
        if ($tag2 !== '' && $tag1 === '') {
            return " HAVING ({$whTag2})";
        }

        $operator = $tag12 ? 'AND' : 'OR';
        return " HAVING (({$whTag1}) {$operator} ({$whTag2}))";
    }

    // ===========================
    // TEXT CRUD OPERATIONS
    // ===========================

    /**
     * Create a new text.
     *
     * @param int    $lgId      Language ID
     * @param string $title     Title
     * @param string $text      Text content
     * @param string $audioUri  Audio URI
     * @param string $sourceUri Source URI
     *
     * @return array{message: string, textId: int} Result with message and new text ID
     */
    public function createText(
        int $lgId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri
    ): array {
        // Remove soft hyphens
        $cleanText = $this->removeSoftHyphens($text);

        // Handle null audio URI (use NULL in database if empty)
        $audioValue = $audioUri === '' ? null : $audioUri;

        $bindings1 = [$lgId, $title, $cleanText, $audioValue, $sourceUri];
        $textId = (int) Connection::preparedInsert(
            "INSERT INTO texts (
                TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
            ) VALUES (?, ?, ?, '', ?, ?)"
            . UserScopedQuery::forTablePrepared('texts', $bindings1),
            $bindings1
        );

        // Parse the text
        TextParsing::splitCheck($cleanText, $lgId, $textId);

        // Get statistics
        $bindings2 = [$textId];
        $sentenceCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS value FROM sentences WHERE SeTxID = ?"
            . UserScopedQuery::forTablePrepared('sentences', $bindings2, '', 'texts'),
            $bindings2
        );
        $bindings3 = [$textId];
        $itemCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS value FROM textitems2 WHERE Ti2TxID = ?"
            . UserScopedQuery::forTablePrepared('textitems2', $bindings3, '', 'texts'),
            $bindings3
        );

        $message = "Sentences added: {$sentenceCount} / Text items added: {$itemCount}";

        return ['message' => $message, 'textId' => $textId];
    }

    /**
     * Update an existing text.
     *
     * @param int    $textId    Text ID
     * @param int    $lgId      Language ID
     * @param string $title     Title
     * @param string $text      Text content
     * @param string $audioUri  Audio URI
     * @param string $sourceUri Source URI
     *
     * @return string Result message
     */
    public function updateText(
        int $textId,
        int $lgId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri
    ): string {
        // Remove soft hyphens
        $cleanText = $this->removeSoftHyphens($text);

        // Handle null audio URI (use NULL in database if empty)
        $audioValue = $audioUri === '' ? null : $audioUri;

        $bindings1 = [$lgId, $title, $cleanText, $audioValue, $sourceUri, $textId];
        Connection::preparedExecute(
            "UPDATE texts SET
                TxLgID = ?, TxTitle = ?, TxText = ?, TxAudioURI = ?, TxSourceURI = ?
             WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings1),
            $bindings1
        );

        // Re-parse the text
        $count1 = QueryBuilder::table('sentences')
            ->where('SeTxID', '=', $textId)
            ->delete();
        $count2 = QueryBuilder::table('textitems2')
            ->where('Ti2TxID', '=', $textId)
            ->delete();
        Maintenance::adjustAutoIncrement('sentences', 'SeID');

        TextParsing::splitCheck($cleanText, $lgId, $textId);

        // Get statistics
        $bindings2 = [$textId];
        $sentenceCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS value FROM sentences WHERE SeTxID = ?"
            . UserScopedQuery::forTablePrepared('sentences', $bindings2, '', 'texts'),
            $bindings2
        );
        $bindings3 = [$textId];
        $itemCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS value FROM textitems2 WHERE Ti2TxID = ?"
            . UserScopedQuery::forTablePrepared('textitems2', $bindings3, '', 'texts'),
            $bindings3
        );

        return "Sentences deleted: $count1 / Text items deleted: $count2 / Sentences added: {$sentenceCount} / Text items added: {$itemCount}";
    }

    /**
     * Delete multiple texts.
     *
     * @param array $textIds Array of text IDs
     *
     * @return string Result message
     */
    public function deleteTexts(array $textIds): string
    {
        if (empty($textIds)) {
            return "Multiple Actions: 0";
        }

        $ids = array_map('intval', $textIds);

        $count3 = QueryBuilder::table('textitems2')
            ->whereIn('Ti2TxID', $ids)
            ->delete();
        $count2 = QueryBuilder::table('sentences')
            ->whereIn('SeTxID', $ids)
            ->delete();
        $count1 = QueryBuilder::table('texts')
            ->whereIn('TxID', $ids)
            ->delete();

        Maintenance::adjustAutoIncrement('texts', 'TxID');
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        $this->cleanupTextTags();

        return "Texts deleted: $count1 / Sentences deleted: $count2 / Text items deleted: $count3";
    }

    /**
     * Archive multiple texts.
     *
     * @param array $textIds Array of text IDs
     *
     * @return string Result message
     */
    public function archiveTexts(array $textIds): string
    {
        if (empty($textIds)) {
            return "Multiple Actions: 0";
        }

        $ids = array_map('intval', $textIds);

        // Delete parsed data
        QueryBuilder::table('textitems2')
            ->whereIn('Ti2TxID', $ids)
            ->delete();
        QueryBuilder::table('sentences')
            ->whereIn('SeTxID', $ids)
            ->delete();

        $count = 0;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $records = Connection::preparedFetchAll(
            "SELECT TxID FROM texts WHERE TxID IN ({$placeholders})"
            . UserScopedQuery::forTablePrepared('texts', $ids),
            $ids
        );

        foreach ($records as $record) {
            $id = $record['TxID'];
            $bindings1 = [$id];
            $count += Connection::preparedExecute(
                "INSERT INTO archivedtexts (
                    AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI
                ) SELECT TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
                FROM texts WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings1),
                $bindings1
            );
            $aid = Connection::lastInsertId();
            $bindings2 = [$aid, $id];
            Connection::preparedExecute(
                "INSERT INTO archtexttags (AgAtID, AgT2ID)
                SELECT ?, TtT2ID
                FROM texttags WHERE TtTxID = ?"
                . UserScopedQuery::forTablePrepared('texttags', $bindings2, '', 'texts'),
                $bindings2
            );
        }

        QueryBuilder::table('texts')
            ->whereIn('TxID', $ids)
            ->delete();
        $this->cleanupTextTags();
        Maintenance::adjustAutoIncrement('texts', 'TxID');
        Maintenance::adjustAutoIncrement('sentences', 'SeID');

        return "Text(s) archived: {$count}";
    }

    /**
     * Rebuild/reparse multiple texts.
     *
     * @param array $textIds Array of text IDs
     *
     * @return string Result message
     */
    public function rebuildTexts(array $textIds): string
    {
        if (empty($textIds)) {
            return "Multiple Actions: 0";
        }

        $count = 0;
        $ids = array_map('intval', $textIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $records = Connection::preparedFetchAll(
            "SELECT TxID, TxLgID FROM texts WHERE TxID IN ({$placeholders})"
            . UserScopedQuery::forTablePrepared('texts', $ids),
            $ids
        );

        foreach ($records as $record) {
            $id = (int) $record['TxID'];
            QueryBuilder::table('sentences')
                ->where('SeTxID', '=', $id)
                ->delete();
            QueryBuilder::table('textitems2')
                ->where('Ti2TxID', '=', $id)
                ->delete();
            Maintenance::adjustAutoIncrement('sentences', 'SeID');

            $bindings = [$id];
            $textContent = Connection::preparedFetchValue(
                "SELECT TxText AS value FROM texts WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings),
                $bindings
            );
            TextParsing::splitCheck($textContent, $record['TxLgID'], $id);
            $count++;
        }

        return "Text(s) reparsed: {$count}";
    }

    // ===========================
    // TEXT CHECK METHODS
    // ===========================

    /**
     * Check text for parsing without saving.
     *
     * @param string $text Text content
     * @param int    $lgId Language ID
     *
     * @return void Outputs HTML directly
     */
    public function checkText(string $text, int $lgId): void
    {
        if (strlen(Escaping::prepareTextdata($text)) > 65000) {
            echo "<p>Error: Text too long, must be below 65000 Bytes.</p>";
        } else {
            TextParsing::splitCheck($text, $lgId, -1);
        }
    }

    /**
     * Validate text length.
     *
     * @param string $text Text to validate
     *
     * @return bool True if valid, false if too long
     */
    public function validateTextLength(string $text): bool
    {
        return strlen(Escaping::prepareTextdata($text)) <= 65000;
    }

    // ===========================
    // HELPER METHODS
    // ===========================

    /**
     * Remove soft hyphens from text.
     *
     * @param string $text Text content
     *
     * @return string Text without soft hyphens
     */
    private function removeSoftHyphens(string $text): string
    {
        return str_replace("\xC2\xAD", "", $text);
    }

    /**
     * Get language data for Google Translate URIs.
     *
     * @return array Mapping of language ID to language code
     */
    public function getLanguageTranslateUris(): array
    {
        $sql = "SELECT LgID, LgGoogleTranslateURI FROM languages
                WHERE LgGoogleTranslateURI <> ''"
                . UserScopedQuery::forTable('languages');
        $res = Connection::query($sql);
        $result = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $result[$record['LgID']] = $record['LgGoogleTranslateURI'];
        }
        mysqli_free_result($res);
        return $result;
    }

    // ===========================
    // LONG TEXT IMPORT METHODS
    // ===========================

    /**
     * Prepare text data for long text import.
     *
     * Handles file upload or clipboard paste, normalizes line endings,
     * and processes paragraphs.
     *
     * @param array  $files             $_FILES array
     * @param string $uploadText        Text from textarea
     * @param int    $paragraphHandling 1=one newline ends paragraph, 2=two newlines
     *
     * @return string Processed text data
     */
    public function prepareLongTextData(
        array $files,
        string $uploadText,
        int $paragraphHandling
    ): string {
        // Get $data with \n line endings
        if (
            isset($files["thefile"])
            && $files["thefile"]["tmp_name"] != ""
            && $files["thefile"]["error"] == 0
        ) {
            $data = file_get_contents($files["thefile"]["tmp_name"]);
            $data = str_replace("\r\n", "\n", $data);
        } else {
            $data = Escaping::prepareTextdata($uploadText);
        }
        // Replace supplementary Unicode planes characters (emoji, etc.) with a block character
        $data = preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xE2\x96\x88", $data);
        $data = trim($data);

        // Use pilcrow symbol for paragraphs separation
        if ($paragraphHandling == 2) {
            $data = preg_replace('/\n\s*?\n/u', '¶', $data);
            $data = str_replace("\n", ' ', $data);
        } else {
            $data = str_replace("\n", '¶', $data);
        }
        $data = preg_replace('/\s{2,}/u', ' ', $data);
        $data = str_replace('¶ ', '¶', $data);
        // Separate paragraphs by \n finally
        $data = str_replace('¶', "\n", $data);

        return $data;
    }

    /**
     * Split long text into smaller texts.
     *
     * @param string $data    Prepared text data
     * @param int    $langId  Language ID
     * @param int    $maxSent Maximum sentences per text
     *
     * @return array Array of text arrays (each containing sentences)
     */
    public function splitLongText(string $data, int $langId, int $maxSent): array
    {
        $sentArray = TextParsing::splitCheck($data, $langId, -2);
        $texts = [];
        $textIndex = 0;
        $texts[$textIndex] = [];
        $cnt = 0;
        $bytes = 0;

        foreach ($sentArray as $item) {
            $itemLen = strlen($item) + 1;
            if ($item != '¶') {
                $cnt++;
            }
            if ($cnt <= $maxSent && $bytes + $itemLen < 65000) {
                $texts[$textIndex][] = $item;
                $bytes += $itemLen;
            } else {
                $textIndex++;
                $texts[$textIndex] = [$item];
                $cnt = 1;
                $bytes = $itemLen;
            }
        }

        return $texts;
    }

    /**
     * Save long text import (multiple texts).
     *
     * @param int    $langId    Language ID
     * @param string $title     Base title
     * @param string $sourceUri Source URI
     * @param array  $texts     Array of text contents
     * @param int    $textCount Expected text count
     *
     * @return array{success: bool, message: string, imported: int}
     */
    public function saveLongTextImport(
        int $langId,
        string $title,
        string $sourceUri,
        array $texts,
        int $textCount
    ): array {
        if (count($texts) != $textCount) {
            return [
                'success' => false,
                'message' => "Error: Number of texts wrong: " . count($texts) . " != " . $textCount,
                'imported' => 0
            ];
        }

        $imported = 0;
        for ($i = 0; $i < $textCount; $i++) {
            $texts[$i] = $this->removeSoftHyphens($texts[$i]);
            $counter = \Lwt\Core\Utils\makeCounterWithTotal($textCount, $i + 1);
            $thisTitle = $title . ($counter == '' ? '' : (' (' . $counter . ')'));

            $bindings = [$langId, $thisTitle, $texts[$i], $sourceUri];
            $affected = Connection::preparedExecute(
                "INSERT INTO texts (
                    TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
                ) VALUES (?, ?, ?, '', '', ?)"
                . UserScopedQuery::forTablePrepared('texts', $bindings),
                $bindings
            );
            $imported += $affected;
            $id = Connection::lastInsertId();
            TagService::saveTextTags($id);
            TextParsing::splitCheck($texts[$i], $langId, $id);
        }

        return [
            'success' => true,
            'message' => $imported . " Text(s) imported!",
            'imported' => $imported
        ];
    }

    // ===========================
    // TEXT READING METHODS
    // ===========================

    /**
     * Get text data for reading interface.
     *
     * @param int $textId Text ID
     *
     * @return array|null Text and language data or null
     */
    public function getTextForReading(int $textId): ?array
    {
        $bindings = [$textId];
        return Connection::preparedFetchOne(
            "SELECT LgName, TxLgID, TxText, TxTitle, TxAudioURI, TxSourceURI, TxAudioPosition
                FROM texts
                JOIN languages ON TxLgID = LgID
                WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings)
                . UserScopedQuery::forTablePrepared('languages', $bindings),
            $bindings
        );
    }

    /**
     * Get text data for text content display.
     *
     * @param int $textId Text ID
     *
     * @return array|null Text data or null
     */
    public function getTextDataForContent(int $textId): ?array
    {
        $bindings = [$textId];
        return Connection::preparedFetchOne(
            "SELECT TxLgID, TxTitle, TxAnnotatedText, TxPosition
                FROM texts
                WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings
        );
    }

    /**
     * Get language settings for text display.
     *
     * @param int $langId Language ID
     *
     * @return array|null Language settings or null
     */
    public function getLanguageSettingsForReading(int $langId): ?array
    {
        $bindings = [$langId];
        return Connection::preparedFetchOne(
            "SELECT LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI,
                LgTextSize, LgRegexpWordCharacters, LgRemoveSpaces, LgRightToLeft
                FROM languages
                WHERE LgID = ?"
                . UserScopedQuery::forTablePrepared('languages', $bindings),
            $bindings
        );
    }

    /**
     * Get TTS voice API for a language.
     *
     * @param int $langId Language ID
     *
     * @return string|null Voice API setting or null
     */
    public function getTtsVoiceApi(int $langId): ?string
    {
        $bindings = [$langId];
        return Connection::preparedFetchValue(
            "SELECT LgTTSVoiceAPI AS value FROM languages
            WHERE LgID = ?"
            . UserScopedQuery::forTablePrepared('languages', $bindings),
            $bindings
        );
    }

    /**
     * Get language ID by name.
     *
     * @param string $langName Language name
     *
     * @return int|null Language ID or null
     */
    public function getLanguageIdByName(string $langName): ?int
    {
        $bindings = [$langName];
        $result = Connection::preparedFetchValue(
            "SELECT LgID as value FROM languages WHERE LgName = ?"
            . UserScopedQuery::forTablePrepared('languages', $bindings),
            $bindings
        );
        return $result !== null ? (int) $result : null;
    }

    // ===========================
    // TEXT EDIT PAGE METHODS
    // ===========================

    /**
     * Set term sentences from texts.
     *
     * Sets WoSentence for all words in the specified texts that don't already
     * have a sentence containing the word.
     *
     * @param array $textIds       Array of text IDs
     * @param bool  $activeOnly    Only set for active terms (status != 98, 99)
     *
     * @return string Result message
     */
    public function setTermSentences(array $textIds, bool $activeOnly = false): string
    {
        if (empty($textIds)) {
            return "Multiple Actions: 0";
        }

        $ids = array_map('intval', $textIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $count = 0;

        $statusFilter = $activeOnly
            ? " AND WoStatus != 98 AND WoStatus != 99"
            : "";

        $sql = "SELECT WoID, WoTextLC, MIN(Ti2SeID) AS SeID
            FROM words, textitems2
            WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID IN ({$placeholders})
            {$statusFilter}
            AND IFNULL(WoSentence,'') NOT LIKE CONCAT('%{',WoText,'}%')
            GROUP BY WoID
            ORDER BY WoID, MIN(Ti2SeID)"
            . UserScopedQuery::forTablePrepared('words', $ids)
            . UserScopedQuery::forTablePrepared('textitems2', $ids, '', 'texts');

        $records = Connection::preparedFetchAll($sql, $ids);
        $sentenceCount = (int) Settings::getWithDefault('set-term-sentence-count');
        $sentenceService = new SentenceService();

        foreach ($records as $record) {
            $sent = $sentenceService->formatSentence(
                $record['SeID'],
                $record['WoTextLC'],
                $sentenceCount
            );
            $bindings = [ExportService::replaceTabNewline($sent[1]), $record['WoID']];
            $count += Connection::preparedExecute(
                "UPDATE words SET WoSentence = ? WHERE WoID = ?"
                . UserScopedQuery::forTablePrepared('words', $bindings),
                $bindings
            );
        }

        return "Term Sentences set from Text(s): {$count}";
    }

    /**
     * Get data for the text edit form.
     *
     * @param int $textId Text ID
     *
     * @return array|null Text data or null if not found
     */
    public function getTextForEdit(int $textId): ?array
    {
        $bindings = [$textId];
        return Connection::preparedFetchOne(
            "SELECT TxID, TxLgID, TxTitle, TxText, TxAudioURI, TxSourceURI,
            TxAnnotatedText <> '' AS annot_exists
            FROM texts
            WHERE TxID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings
        );
    }

    /**
     * Get language translation URIs for form language selection.
     *
     * @return array<int, string> Mapping of language ID to language code
     */
    public function getLanguageDataForForm(): array
    {
        $sql = "SELECT LgID, LgGoogleTranslateURI FROM languages
                WHERE LgGoogleTranslateURI <> ''"
                . UserScopedQuery::forTable('languages');
        $res = Connection::query($sql);
        $result = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $result[$record['LgID']] = UrlUtilities::langFromDict($record['LgGoogleTranslateURI']);
        }
        mysqli_free_result($res);
        return $result;
    }

    /**
     * Save text and reparse it.
     *
     * @param int    $textId    Text ID (0 for new text)
     * @param int    $lgId      Language ID
     * @param string $title     Title
     * @param string $text      Text content
     * @param string $audioUri  Audio URI
     * @param string $sourceUri Source URI
     *
     * @return array{message: string, textId: int, redirect: bool}
     */
    public function saveTextAndReparse(
        int $textId,
        int $lgId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri
    ): array {
        $cleanText = $this->removeSoftHyphens($text);

        // Handle null audio URI (use NULL in database if empty)
        $audioValue = $audioUri === '' ? null : $audioUri;

        if ($textId === 0) {
            // New text
            $bindings1 = [$lgId, $title, $cleanText, $audioValue, $sourceUri];
            $textId = (int) Connection::preparedInsert(
                "INSERT INTO texts (
                    TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
                ) VALUES (?, ?, ?, '', ?, ?)"
                . UserScopedQuery::forTablePrepared('texts', $bindings1),
                $bindings1
            );
        } else {
            // Update existing text
            $bindings1 = [$lgId, $title, $cleanText, $audioValue, $sourceUri, $textId];
            Connection::preparedExecute(
                "UPDATE texts SET
                    TxLgID = ?, TxTitle = ?, TxText = ?, TxAudioURI = ?, TxSourceURI = ?
                 WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings1),
                $bindings1
            );
        }

        // Save tags
        TagService::saveTextTags($textId);

        // Delete old parsed data
        $sentencesDeleted = QueryBuilder::table('sentences')
            ->where('SeTxID', '=', $textId)
            ->delete();
        $textitemsDeleted = QueryBuilder::table('textitems2')
            ->where('Ti2TxID', '=', $textId)
            ->delete();
        Maintenance::adjustAutoIncrement('sentences', 'SeID');

        // Reparse
        $bindings2 = [$textId];
        TextParsing::splitCheck(
            Connection::preparedFetchValue(
                "SELECT TxText AS value FROM texts WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings2),
                $bindings2
            ),
            $lgId,
            $textId
        );

        // Get statistics
        $bindings3 = [$textId];
        $sentenceCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS value FROM sentences WHERE SeTxID = ?"
            . UserScopedQuery::forTablePrepared('sentences', $bindings3, '', 'texts'),
            $bindings3
        );
        $bindings4 = [$textId];
        $itemCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS value FROM textitems2 WHERE Ti2TxID = ?"
            . UserScopedQuery::forTablePrepared('textitems2', $bindings4, '', 'texts'),
            $bindings4
        );

        $message = "Sentences deleted: {$sentencesDeleted} / Textitems deleted: {$textitemsDeleted} / Sentences added: {$sentenceCount} / Text items added: {$itemCount}";

        return [
            'message' => $message,
            'textId' => $textId,
            'redirect' => false
        ];
    }

    // =========================================================================
    // Methods migrated from Core/UI/ui_helpers.php
    // =========================================================================

    /**
     * Get texts formatted for select dropdown options.
     *
     * @param int|null $langId Filter by language ID (null for all languages)
     *
     * @return array<int, array{id: int, title: string, language: string}> Array of text data
     */
    public function getTextsForSelect(?int $langId = null): array
    {
        $result = [];
        if ($langId !== null) {
            $bindings = [$langId];
            $records = Connection::preparedFetchAll(
                "SELECT TxID, TxTitle, LgName
                FROM languages, texts
                WHERE LgID = TxLgID AND TxLgID = ?
                ORDER BY LgName, TxTitle"
                . UserScopedQuery::forTablePrepared('languages', $bindings)
                . UserScopedQuery::forTablePrepared('texts', $bindings),
                $bindings
            );
        } else {
            $records = Connection::preparedFetchAll(
                "SELECT TxID, TxTitle, LgName
                FROM languages, texts
                WHERE LgID = TxLgID
                ORDER BY LgName, TxTitle"
                . UserScopedQuery::forTable('languages')
                . UserScopedQuery::forTable('texts'),
                []
            );
        }
        foreach ($records as $record) {
            $title = (string)$record['TxTitle'];
            if (mb_strlen($title, 'UTF-8') > 30) {
                $title = mb_substr($title, 0, 30, 'UTF-8') . '...';
            }
            $result[] = [
                'id' => (int)$record['TxID'],
                'title' => $title,
                'language' => (string)$record['LgName']
            ];
        }
        return $result;
    }
}

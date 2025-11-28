<?php

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
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Settings;
use Lwt\Database\Maintenance;
use Lwt\Database\TextParsing;
use Lwt\Database\Validation;
use Lwt\Services\TagService;

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
    /**
     * Database table prefix.
     *
     * @var string
     */
    private string $tbpref;

    /**
     * Constructor - initialize table prefix.
     */
    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

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
                {$this->tbpref}archivedtexts
                LEFT JOIN {$this->tbpref}archtexttags ON AtID = AgAtID
            ) WHERE (1=1) {$whLang}{$whQuery}
            GROUP BY AtID {$whTag}
        ) AS dummy";
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
            IF(
                COUNT(T2Text)=0,
                '',
                CONCAT('[', GROUP_CONCAT(DISTINCT T2Text ORDER BY T2Text SEPARATOR ', '), ']')
            ) AS taglist
            FROM (
                ({$this->tbpref}archivedtexts
                LEFT JOIN {$this->tbpref}archtexttags ON AtID = AgAtID)
                LEFT JOIN {$this->tbpref}tags2 ON T2ID = AgT2ID
            ), {$this->tbpref}languages
            WHERE LgID=AtLgID {$whLang}{$whQuery}
            GROUP BY AtID {$whTag}
            ORDER BY {$sortColumn}
            {$limit}";

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
        $sql = "SELECT AtLgID, AtTitle, AtText, AtAudioURI, AtSourceURI,
            LENGTH(AtAnnotatedText) AS annotlen
            FROM {$this->tbpref}archivedtexts
            WHERE AtID = {$textId}";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $record ?: null;
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
        $message = Connection::execute(
            "DELETE FROM {$this->tbpref}archivedtexts WHERE AtID = {$textId}",
            "Archived Texts deleted"
        );
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

        $list = "(" . implode(",", array_map('intval', $textIds)) . ")";
        $message = Connection::execute(
            "DELETE FROM {$this->tbpref}archivedtexts WHERE AtID IN {$list}",
            "Archived Texts deleted"
        );
        Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
        $this->cleanupArchivedTextTags();
        return $message;
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
        $lgId = Connection::fetchValue(
            "SELECT AtLgID AS value FROM {$this->tbpref}archivedtexts
            WHERE AtID = {$archivedId}"
        );

        if ($lgId === null) {
            return ['message' => 'Archived text not found', 'textId' => null];
        }

        // Insert into active texts
        $inserted = Connection::execute(
            "INSERT INTO {$this->tbpref}texts (
                TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
            ) SELECT AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI
            FROM {$this->tbpref}archivedtexts
            WHERE AtID = {$archivedId}",
            "Texts added"
        );

        $textId = Connection::lastInsertId();

        // Copy tags
        Connection::execute(
            "INSERT INTO {$this->tbpref}texttags (TtTxID, TtT2ID)
            SELECT {$textId}, AgT2ID
            FROM {$this->tbpref}archtexttags
            WHERE AgAtID = {$archivedId}",
            ""
        );

        // Parse the text
        $textContent = Connection::fetchValue(
            "SELECT TxText AS value FROM {$this->tbpref}texts WHERE TxID = {$textId}"
        );
        TextParsing::splitCheck($textContent, (int) $lgId, $textId);

        // Delete from archived
        $deleted = Connection::execute(
            "DELETE FROM {$this->tbpref}archivedtexts WHERE AtID = {$archivedId}",
            "Archived Texts deleted"
        );

        Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
        $this->cleanupArchivedTextTags();

        // Get statistics
        $sentenceCount = Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$this->tbpref}sentences WHERE SeTxID = {$textId}"
        );
        $itemCount = Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$this->tbpref}textitems2 WHERE Ti2TxID = {$textId}"
        );

        $message = "{$deleted} / {$inserted} / Sentences added: {$sentenceCount} / Text items added: {$itemCount}";

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
        $list = "(" . implode(",", array_map('intval', $archivedIds)) . ")";

        $sql = "SELECT AtID, AtLgID FROM {$this->tbpref}archivedtexts WHERE AtID IN {$list}";
        $res = Connection::query($sql);

        while ($record = mysqli_fetch_assoc($res)) {
            $ida = $record['AtID'];
            $mess = (int) Connection::execute(
                "INSERT INTO {$this->tbpref}texts (
                    TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
                ) SELECT AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI
                FROM {$this->tbpref}archivedtexts
                WHERE AtID = {$ida}",
                ""
            );
            $count += $mess;

            $id = Connection::lastInsertId();

            Connection::execute(
                "INSERT INTO {$this->tbpref}texttags (TtTxID, TtT2ID)
                SELECT {$id}, AgT2ID
                FROM {$this->tbpref}archtexttags
                WHERE AgAtID = {$ida}",
                ""
            );

            $textContent = Connection::fetchValue(
                "SELECT TxText AS value FROM {$this->tbpref}texts WHERE TxID = {$id}"
            );
            TextParsing::splitCheck($textContent, $record['AtLgID'], $id);

            Connection::execute(
                "DELETE FROM {$this->tbpref}archivedtexts WHERE AtID = {$ida}",
                ""
            );
        }
        mysqli_free_result($res);

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
        $oldText = Connection::fetchValue(
            "SELECT AtText AS value FROM {$this->tbpref}archivedtexts WHERE AtID = {$textId}"
        );
        $textsdiffer = Escaping::toSqlSyntax($text) !== Escaping::toSqlSyntax($oldText);

        $message = Connection::execute(
            "UPDATE {$this->tbpref}archivedtexts SET
                AtLgID = {$lgId},
                AtTitle = " . Escaping::toSqlSyntax($title) . ",
                AtText = " . Escaping::toSqlSyntax($text) . ",
                AtAudioURI = " . Escaping::toSqlSyntax($audioUri) . ",
                AtSourceURI = " . Escaping::toSqlSyntax($sourceUri) . "
            WHERE AtID = {$textId}",
            "Updated"
        );

        // Clear annotation if text changed
        if ($message === 'Updated: 1' && $textsdiffer) {
            Connection::execute(
                "UPDATE {$this->tbpref}archivedtexts
                SET AtAnnotatedText = ''
                WHERE AtID = {$textId}",
                ""
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
            "DELETE {$this->tbpref}archtexttags
            FROM (
                {$this->tbpref}archtexttags
                LEFT JOIN {$this->tbpref}archivedtexts ON AgAtID = AtID
            )
            WHERE AtID IS NULL",
            ''
        );
    }

    // =======================
    // FILTER BUILDING METHODS
    // =======================

    /**
     * Build WHERE clause for query filtering (archived texts).
     *
     * @param string $query     Query string
     * @param string $queryMode Query mode ('title,text', 'title', 'text')
     * @param string $regexMode Regex mode ('', 'r', etc.)
     *
     * @return string SQL WHERE clause fragment
     */
    public function buildArchivedQueryWhereClause(
        string $query,
        string $queryMode,
        string $regexMode
    ): string {
        if ($query === '') {
            return '';
        }

        $whQuery = $regexMode . 'LIKE ' . Escaping::toSqlSyntax(
            $regexMode === '' ?
                str_replace("*", "%", mb_strtolower($query, 'UTF-8')) :
                $query
        );

        switch ($queryMode) {
            case 'title,text':
                return " AND (AtTitle {$whQuery} OR AtText {$whQuery})";
            case 'title':
                return " AND (AtTitle {$whQuery})";
            case 'text':
                return " AND (AtText {$whQuery})";
            default:
                return " AND (AtTitle {$whQuery} OR AtText {$whQuery})";
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

        $result = @mysqli_query(
            $GLOBALS["DBCONNECTION"],
            'SELECT "test" RLIKE ' . Escaping::toSqlSyntax($query)
        );

        return $result !== false;
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
        $sql = "SELECT TxID, TxLgID, TxTitle, TxText, TxAudioURI, TxSourceURI,
            TxAnnotatedText <> '' AS annot_exists
            FROM {$this->tbpref}texts
            WHERE TxID = {$textId}";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $record ?: null;
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
        $msg3 = Connection::execute(
            "DELETE FROM {$this->tbpref}textitems2 WHERE Ti2TxID = {$textId}",
            "Text items deleted"
        );
        $msg2 = Connection::execute(
            "DELETE FROM {$this->tbpref}sentences WHERE SeTxID = {$textId}",
            "Sentences deleted"
        );
        $msg1 = Connection::execute(
            "DELETE FROM {$this->tbpref}texts WHERE TxID = {$textId}",
            "Texts deleted"
        );

        Maintenance::adjustAutoIncrement('texts', 'TxID');
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        $this->cleanupTextTags();

        return "{$msg1} / {$msg2} / {$msg3}";
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
        $msg3 = Connection::execute(
            "DELETE FROM {$this->tbpref}textitems2 WHERE Ti2TxID = {$textId}",
            "Text items deleted"
        );
        $msg2 = Connection::execute(
            "DELETE FROM {$this->tbpref}sentences WHERE SeTxID = {$textId}",
            "Sentences deleted"
        );

        // Insert into archived
        $msg4 = Connection::execute(
            "INSERT INTO {$this->tbpref}archivedtexts (
                AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI
            ) SELECT TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
            FROM {$this->tbpref}texts
            WHERE TxID = {$textId}",
            "Archived Texts saved"
        );

        $archiveId = Connection::lastInsertId();

        // Copy tags
        Connection::execute(
            "INSERT INTO {$this->tbpref}archtexttags (AgAtID, AgT2ID)
            SELECT {$archiveId}, TtT2ID
            FROM {$this->tbpref}texttags
            WHERE TtTxID = {$textId}",
            ""
        );

        // Delete from active
        $msg1 = Connection::execute(
            "DELETE FROM {$this->tbpref}texts WHERE TxID = {$textId}",
            "Texts deleted"
        );

        Maintenance::adjustAutoIncrement('texts', 'TxID');
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        $this->cleanupTextTags();

        return "{$msg4} / {$msg1} / {$msg2} / {$msg3}";
    }

    /**
     * Clean up orphaned text tags.
     *
     * @return void
     */
    private function cleanupTextTags(): void
    {
        Connection::execute(
            "DELETE {$this->tbpref}texttags
            FROM (
                {$this->tbpref}texttags
                LEFT JOIN {$this->tbpref}texts ON TtTxID = TxID
            )
            WHERE TxID IS NULL",
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
                {$this->tbpref}texts
                LEFT JOIN {$this->tbpref}texttags ON TxID = TtTxID
            ) WHERE (1=1) {$whLang}{$whQuery}
            GROUP BY TxID {$whTag}
        ) AS dummy";
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
            IF(
                COUNT(T2Text)=0,
                '',
                CONCAT('[', GROUP_CONCAT(DISTINCT T2Text ORDER BY T2Text SEPARATOR ', '), ']')
            ) AS taglist
            FROM (
                ({$this->tbpref}texts LEFT JOIN {$this->tbpref}texttags ON TxID = TtTxID)
                LEFT JOIN {$this->tbpref}tags2 ON T2ID = TtT2ID
            ), {$this->tbpref}languages
            WHERE LgID=TxLgID {$whLang}{$whQuery}
            GROUP BY TxID {$whTag}
            ORDER BY {$sortColumn}
            {$limit}";

        $res = Connection::query($sql);
        $texts = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $texts[] = $record;
        }
        mysqli_free_result($res);
        return $texts;
    }

    /**
     * Build WHERE clause for query filtering (active texts).
     *
     * @param string $query     Query string
     * @param string $queryMode Query mode ('title,text', 'title', 'text')
     * @param string $regexMode Regex mode ('', 'r', etc.)
     *
     * @return string SQL WHERE clause fragment
     */
    public function buildTextQueryWhereClause(
        string $query,
        string $queryMode,
        string $regexMode
    ): string {
        if ($query === '') {
            return '';
        }

        $whQuery = $regexMode . 'LIKE ' . Escaping::toSqlSyntax(
            $regexMode === '' ?
                str_replace("*", "%", mb_strtolower($query, 'UTF-8')) :
                $query
        );

        switch ($queryMode) {
            case 'title,text':
                return " AND (TxTitle {$whQuery} OR TxText {$whQuery})";
            case 'title':
                return " AND (TxTitle {$whQuery})";
            case 'text':
                return " AND (TxText {$whQuery})";
            default:
                return " AND (TxTitle {$whQuery} OR TxText {$whQuery})";
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

        Connection::execute(
            "INSERT INTO {$this->tbpref}texts (
                TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
            ) VALUES (
                {$lgId},
                " . Escaping::toSqlSyntax($title) . ",
                " . Escaping::toSqlSyntax($cleanText) . ",
                '',
                " . Escaping::toSqlSyntaxNoNull($audioUri) . ",
                " . Escaping::toSqlSyntax($sourceUri) . "
            )",
            "Saved"
        );

        $textId = Connection::lastInsertId();

        // Parse the text
        TextParsing::splitCheck($cleanText, $lgId, $textId);

        // Get statistics
        $sentenceCount = Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$this->tbpref}sentences WHERE SeTxID = {$textId}"
        );
        $itemCount = Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$this->tbpref}textitems2 WHERE Ti2TxID = {$textId}"
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

        Connection::execute(
            "UPDATE {$this->tbpref}texts SET
                TxLgID = {$lgId},
                TxTitle = " . Escaping::toSqlSyntax($title) . ",
                TxText = " . Escaping::toSqlSyntax($cleanText) . ",
                TxAudioURI = " . Escaping::toSqlSyntaxNoNull($audioUri) . ",
                TxSourceURI = " . Escaping::toSqlSyntax($sourceUri) . "
            WHERE TxID = {$textId}",
            "Updated"
        );

        // Re-parse the text
        $msg1 = Connection::execute(
            "DELETE FROM {$this->tbpref}sentences WHERE SeTxID = {$textId}",
            "Sentences deleted"
        );
        $msg2 = Connection::execute(
            "DELETE FROM {$this->tbpref}textitems2 WHERE Ti2TxID = {$textId}",
            "Text items deleted"
        );
        Maintenance::adjustAutoIncrement('sentences', 'SeID');

        TextParsing::splitCheck($cleanText, $lgId, $textId);

        // Get statistics
        $sentenceCount = Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$this->tbpref}sentences WHERE SeTxID = {$textId}"
        );
        $itemCount = Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$this->tbpref}textitems2 WHERE Ti2TxID = {$textId}"
        );

        return "{$msg1} / {$msg2} / Sentences added: {$sentenceCount} / Text items added: {$itemCount}";
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

        $list = "(" . implode(",", array_map('intval', $textIds)) . ")";

        $msg3 = Connection::execute(
            "DELETE FROM {$this->tbpref}textitems2 WHERE Ti2TxID IN {$list}",
            "Text items deleted"
        );
        $msg2 = Connection::execute(
            "DELETE FROM {$this->tbpref}sentences WHERE SeTxID IN {$list}",
            "Sentences deleted"
        );
        $msg1 = Connection::execute(
            "DELETE FROM {$this->tbpref}texts WHERE TxID IN {$list}",
            "Texts deleted"
        );

        Maintenance::adjustAutoIncrement('texts', 'TxID');
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        $this->cleanupTextTags();

        return "{$msg1} / {$msg2} / {$msg3}";
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

        $list = "(" . implode(",", array_map('intval', $textIds)) . ")";

        // Delete parsed data
        Connection::execute(
            "DELETE FROM {$this->tbpref}textitems2 WHERE Ti2TxID IN {$list}",
            ""
        );
        Connection::execute(
            "DELETE FROM {$this->tbpref}sentences WHERE SeTxID IN {$list}",
            ""
        );

        $count = 0;
        $sql = "SELECT TxID FROM {$this->tbpref}texts WHERE TxID IN {$list}";
        $res = Connection::query($sql);

        while ($record = mysqli_fetch_assoc($res)) {
            $id = $record['TxID'];
            $count += (int) Connection::execute(
                "INSERT INTO {$this->tbpref}archivedtexts (
                    AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI
                ) SELECT TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
                FROM {$this->tbpref}texts WHERE TxID = {$id}",
                ""
            );
            $aid = Connection::lastInsertId();
            Connection::execute(
                "INSERT INTO {$this->tbpref}archtexttags (AgAtID, AgT2ID)
                SELECT {$aid}, TtT2ID
                FROM {$this->tbpref}texttags WHERE TtTxID = {$id}",
                ""
            );
        }
        mysqli_free_result($res);

        Connection::execute(
            "DELETE FROM {$this->tbpref}texts WHERE TxID IN {$list}",
            ""
        );
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
        $list = "(" . implode(",", array_map('intval', $textIds)) . ")";

        $sql = "SELECT TxID, TxLgID FROM {$this->tbpref}texts WHERE TxID IN {$list}";
        $res = Connection::query($sql);

        while ($record = mysqli_fetch_assoc($res)) {
            $id = (int) $record['TxID'];
            Connection::execute(
                "DELETE FROM {$this->tbpref}sentences WHERE SeTxID = {$id}",
                ""
            );
            Connection::execute(
                "DELETE FROM {$this->tbpref}textitems2 WHERE Ti2TxID = {$id}",
                ""
            );
            Maintenance::adjustAutoIncrement('sentences', 'SeID');

            $textContent = Connection::fetchValue(
                "SELECT TxText AS value FROM {$this->tbpref}texts WHERE TxID = {$id}"
            );
            TextParsing::splitCheck($textContent, $record['TxLgID'], $id);
            $count++;
        }
        mysqli_free_result($res);

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
        $sql = "SELECT LgID, LgGoogleTranslateURI FROM {$this->tbpref}languages
                WHERE LgGoogleTranslateURI <> ''";
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
        $data = \replace_supp_unicode_planes_char($data);
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
            $counter = \makeCounterWithTotal($textCount, $i + 1);
            $thisTitle = $title . ($counter == '' ? '' : (' (' . $counter . ')'));

            $imported += (int) Connection::execute(
                'INSERT INTO ' . $this->tbpref . 'texts (
                    TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI,
                    TxSourceURI
                ) VALUES ( ' .
                    $langId . ', ' .
                    Escaping::toSqlSyntax($thisTitle) . ', ' .
                    Escaping::toSqlSyntax($texts[$i]) . ", '',
                    '', " .
                    Escaping::toSqlSyntax($sourceUri) .
                ')'
            );
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
        $sql = "SELECT LgName, TxLgID, TxText, TxTitle, TxAudioURI, TxSourceURI, TxAudioPosition
                FROM {$this->tbpref}texts
                JOIN {$this->tbpref}languages ON TxLgID = LgID
                WHERE TxID = {$textId}";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $record ?: null;
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
        $sql = "SELECT TxLgID, TxTitle, TxAnnotatedText, TxPosition
                FROM {$this->tbpref}texts
                WHERE TxID = {$textId}";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $record ?: null;
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
        $sql = "SELECT LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI,
                LgTextSize, LgRegexpWordCharacters, LgRemoveSpaces, LgRightToLeft
                FROM {$this->tbpref}languages
                WHERE LgID = {$langId}";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $record ?: null;
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
        return Connection::fetchValue(
            "SELECT LgTTSVoiceAPI AS value FROM {$this->tbpref}languages
            WHERE LgID = {$langId}"
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
        $result = Connection::fetchValue(
            "SELECT LgID as value
            FROM {$this->tbpref}languages
            WHERE LgName = " . Escaping::toSqlSyntax($langName)
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

        $list = "(" . implode(",", array_map('intval', $textIds)) . ")";
        $count = 0;

        $statusFilter = $activeOnly
            ? " AND WoStatus != 98 AND WoStatus != 99"
            : "";

        $sql = "SELECT WoID, WoTextLC, MIN(Ti2SeID) AS SeID
            FROM {$this->tbpref}words, {$this->tbpref}textitems2
            WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID IN {$list}
            {$statusFilter}
            AND IFNULL(WoSentence,'') NOT LIKE CONCAT('%{',WoText,'}%')
            GROUP BY WoID
            ORDER BY WoID, MIN(Ti2SeID)";

        $res = Connection::query($sql);
        $sentenceCount = (int) Settings::getWithDefault('set-term-sentence-count');

        while ($record = mysqli_fetch_assoc($res)) {
            $sent = \getSentence(
                $record['SeID'],
                $record['WoTextLC'],
                $sentenceCount
            );
            $count += (int) Connection::execute(
                "UPDATE {$this->tbpref}words
                SET WoSentence = " . Escaping::toSqlSyntax(\repl_tab_nl($sent[1])) . "
                WHERE WoID = " . $record['WoID']
            );
        }
        mysqli_free_result($res);

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
        $sql = "SELECT TxID, TxLgID, TxTitle, TxText, TxAudioURI, TxSourceURI,
            TxAnnotatedText <> '' AS annot_exists
            FROM {$this->tbpref}texts
            WHERE TxID = {$textId}";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $record ?: null;
    }

    /**
     * Get language translation URIs for form language selection.
     *
     * @return array<int, string> Mapping of language ID to language code
     */
    public function getLanguageDataForForm(): array
    {
        $sql = "SELECT LgID, LgGoogleTranslateURI FROM {$this->tbpref}languages
                WHERE LgGoogleTranslateURI <> ''";
        $res = Connection::query($sql);
        $result = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $result[$record['LgID']] = \langFromDict($record['LgGoogleTranslateURI']);
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

        if ($textId === 0) {
            // New text
            Connection::execute(
                "INSERT INTO {$this->tbpref}texts (
                    TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
                ) VALUES (
                    {$lgId},
                    " . Escaping::toSqlSyntax($title) . ",
                    " . Escaping::toSqlSyntax($cleanText) . ",
                    '',
                    " . Escaping::toSqlSyntaxNoNull($audioUri) . ",
                    " . Escaping::toSqlSyntax($sourceUri) . "
                )"
            );
            $textId = Connection::lastInsertId();
        } else {
            // Update existing text
            Connection::execute(
                "UPDATE {$this->tbpref}texts SET
                    TxLgID = {$lgId},
                    TxTitle = " . Escaping::toSqlSyntax($title) . ",
                    TxText = " . Escaping::toSqlSyntax($cleanText) . ",
                    TxAudioURI = " . Escaping::toSqlSyntaxNoNull($audioUri) . ",
                    TxSourceURI = " . Escaping::toSqlSyntax($sourceUri) . "
                WHERE TxID = {$textId}"
            );
        }

        // Save tags
        TagService::saveTextTags($textId);

        // Delete old parsed data
        $sentencesDeleted = Connection::execute(
            "DELETE FROM {$this->tbpref}sentences WHERE SeTxID = {$textId}"
        );
        $textitemsDeleted = Connection::execute(
            "DELETE FROM {$this->tbpref}textitems2 WHERE Ti2TxID = {$textId}"
        );
        Maintenance::adjustAutoIncrement('sentences', 'SeID');

        // Reparse
        TextParsing::splitCheck(
            Connection::fetchValue(
                "SELECT TxText AS value FROM {$this->tbpref}texts WHERE TxID = {$textId}"
            ),
            $lgId,
            $textId
        );

        // Get statistics
        $sentenceCount = Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$this->tbpref}sentences WHERE SeTxID = {$textId}"
        );
        $itemCount = Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$this->tbpref}textitems2 WHERE Ti2TxID = {$textId}"
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
        $langFilter = $langId !== null ? "AND TxLgID = $langId" : '';
        $sql = "SELECT TxID, TxTitle, LgName
            FROM {$this->tbpref}languages, {$this->tbpref}texts
            WHERE LgID = TxLgID $langFilter
            ORDER BY LgName, TxTitle";
        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
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
        mysqli_free_result($res);
        return $result;
    }
}

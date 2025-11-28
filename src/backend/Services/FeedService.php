<?php

/**
 * Feed Service - Business logic for RSS feed operations
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

require_once __DIR__ . '/../Core/Feed/feeds.php';

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Settings;
use Lwt\Database\Maintenance;
use Lwt\Database\TextParsing;

/**
 * Service class for managing RSS feeds.
 *
 * Handles CRUD operations and business logic for newsfeeds.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class FeedService
{
    private string $tbpref;

    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    /**
     * Get all newsfeeds for a language (or all languages).
     *
     * @param int|null $langId Language ID filter (null for all)
     *
     * @return array Array of feed records
     */
    public function getFeeds(?int $langId = null): array
    {
        $sql = "SELECT NfID, NfName, NfSourceURI, NfUpdate, NfOptions, NfLgID
                FROM {$this->tbpref}newsfeeds";

        if ($langId !== null && $langId > 0) {
            $sql .= " WHERE NfLgID = $langId";
        }

        $sql .= " ORDER BY NfUpdate DESC";

        $feeds = [];
        $res = Connection::query($sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $feeds[] = $row;
        }
        mysqli_free_result($res);

        return $feeds;
    }

    /**
     * Get a single feed by ID.
     *
     * @param int $feedId Feed ID
     *
     * @return array|null Feed record or null if not found
     */
    public function getFeedById(int $feedId): ?array
    {
        $sql = "SELECT * FROM {$this->tbpref}newsfeeds WHERE NfID = $feedId";
        $res = Connection::query($sql);
        $row = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        return $row ?: null;
    }

    /**
     * Get feed links (articles) for specified feeds with optional filtering.
     *
     * @param string $feedIds     Comma-separated feed IDs
     * @param string $whereQuery  Additional WHERE clause for filtering
     * @param string $orderBy     ORDER BY clause
     * @param int    $offset      Pagination offset
     * @param int    $limit       Pagination limit
     *
     * @return array Array of feed link records
     */
    public function getFeedLinks(
        string $feedIds,
        string $whereQuery = '',
        string $orderBy = 'FlDate DESC',
        int $offset = 0,
        int $limit = 50
    ): array {
        $sql = "SELECT FlID, FlTitle, FlLink, FlDescription, FlDate, FlAudio,
                       TxID, AtID
                FROM {$this->tbpref}feedlinks
                LEFT JOIN {$this->tbpref}texts ON TxSourceURI = TRIM(FlLink)
                LEFT JOIN {$this->tbpref}archivedtexts ON AtSourceURI = TRIM(FlLink)
                WHERE FlNfID IN ($feedIds) $whereQuery
                ORDER BY $orderBy
                LIMIT $offset, $limit";

        $links = [];
        $res = Connection::query($sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $links[] = $row;
        }
        mysqli_free_result($res);

        return $links;
    }

    /**
     * Count feed links for specified feeds with optional filtering.
     *
     * @param string $feedIds    Comma-separated feed IDs
     * @param string $whereQuery Additional WHERE clause for filtering
     *
     * @return int Number of matching feed links
     */
    public function countFeedLinks(string $feedIds, string $whereQuery = ''): int
    {
        $sql = "SELECT COUNT(*) AS value FROM {$this->tbpref}feedlinks
                WHERE FlNfID IN ($feedIds) $whereQuery";
        return (int)Connection::fetchValue($sql);
    }

    /**
     * Count newsfeeds with optional language and query filter.
     *
     * @param int|null $langId   Language ID filter (null for all)
     * @param string   $whQuery  Additional WHERE clause
     *
     * @return int Number of matching feeds
     */
    public function countFeeds(?int $langId = null, string $whQuery = ''): int
    {
        $sql = "SELECT COUNT(*) AS value FROM {$this->tbpref}newsfeeds WHERE ";
        if ($langId !== null && $langId > 0) {
            $sql .= "NfLgID = $langId $whQuery";
        } else {
            $sql .= "1=1 $whQuery";
        }
        return (int)Connection::fetchValue($sql);
    }

    /**
     * Create a new feed.
     *
     * @param array $data Feed data
     *
     * @return int New feed ID
     */
    public function createFeed(array $data): int
    {
        Connection::execute(
            "INSERT INTO {$this->tbpref}newsfeeds (
                NfLgID, NfName, NfSourceURI, NfArticleSectionTags, NfFilterTags, NfOptions
            ) VALUES (
                " . Escaping::toSqlSyntax($data['NfLgID']) . ",
                " . Escaping::toSqlSyntax($data['NfName']) . ",
                " . Escaping::toSqlSyntax($data['NfSourceURI']) . ",
                " . Escaping::toSqlSyntax($data['NfArticleSectionTags']) . ",
                " . Escaping::toSqlSyntaxNoNull($data['NfFilterTags'] ?? '') . ",
                " . Escaping::toSqlSyntaxNoNull(rtrim($data['NfOptions'] ?? '', ',')) . "
            )"
        );

        return (int)Connection::lastInsertId();
    }

    /**
     * Update an existing feed.
     *
     * @param int   $feedId Feed ID
     * @param array $data   Feed data
     *
     * @return void
     */
    public function updateFeed(int $feedId, array $data): void
    {
        Connection::execute(
            "UPDATE {$this->tbpref}newsfeeds SET
                NfLgID = " . Escaping::toSqlSyntax($data['NfLgID']) . ",
                NfName = " . Escaping::toSqlSyntax($data['NfName']) . ",
                NfSourceURI = " . Escaping::toSqlSyntax($data['NfSourceURI']) . ",
                NfArticleSectionTags = " . Escaping::toSqlSyntax($data['NfArticleSectionTags']) . ",
                NfFilterTags = " . Escaping::toSqlSyntaxNoNull($data['NfFilterTags'] ?? '') . ",
                NfOptions = " . Escaping::toSqlSyntaxNoNull(rtrim($data['NfOptions'] ?? '', ',')) . "
            WHERE NfID = $feedId"
        );
    }

    /**
     * Delete feeds by ID(s).
     *
     * @param string $feedIds Comma-separated feed IDs
     *
     * @return array{feeds: int, articles: int} Counts of deleted items
     */
    public function deleteFeeds(string $feedIds): array
    {
        $articles = (int)Connection::execute(
            "DELETE FROM {$this->tbpref}feedlinks WHERE FlNfID IN ($feedIds)"
        );

        $feeds = (int)Connection::execute(
            "DELETE FROM {$this->tbpref}newsfeeds WHERE NfID IN ($feedIds)"
        );

        return ['feeds' => $feeds, 'articles' => $articles];
    }

    /**
     * Delete all articles for specified feeds.
     *
     * @param string $feedIds Comma-separated feed IDs
     *
     * @return int Number of deleted articles
     */
    public function deleteArticles(string $feedIds): int
    {
        $count = (int)Connection::execute(
            "DELETE FROM {$this->tbpref}feedlinks WHERE FlNfID IN ($feedIds)"
        );

        // Update the feed timestamp
        Connection::query(
            "UPDATE {$this->tbpref}newsfeeds SET NfUpdate = " . time() . "
             WHERE NfID IN ($feedIds)"
        );

        return $count;
    }

    /**
     * Reset unloadable articles (remove leading space from links).
     *
     * @param string $feedIds Comma-separated feed IDs
     *
     * @return int Number of reset articles
     */
    public function resetUnloadableArticles(string $feedIds): int
    {
        return (int)Connection::execute(
            "UPDATE {$this->tbpref}feedlinks SET FlLink = TRIM(FlLink)
             WHERE FlNfID IN ($feedIds)"
        );
    }

    /**
     * Get feeds that need auto-update.
     *
     * @return array Array of feeds needing update
     */
    public function getFeedsNeedingAutoUpdate(): array
    {
        $currentTime = time();
        $feeds = [];

        $result = Connection::query(
            "SELECT * FROM {$this->tbpref}newsfeeds
             WHERE NfOptions LIKE '%autoupdate=%'"
        );

        while ($row = mysqli_fetch_assoc($result)) {
            $autoupdate = $this->getNfOption((string)$row['NfOptions'], 'autoupdate');
            if (!$autoupdate) {
                continue;
            }

            $interval = $this->parseAutoUpdateInterval($autoupdate);
            if ($interval === null) {
                continue;
            }

            if ($currentTime > ($interval + (int)$row['NfUpdate'])) {
                $feeds[] = $row;
            }
        }
        mysqli_free_result($result);

        return $feeds;
    }

    /**
     * Parse auto-update interval string to seconds.
     *
     * @param string $autoupdate Interval string (e.g., "2h", "1d", "1w")
     *
     * @return int|null Interval in seconds or null if invalid
     */
    public function parseAutoUpdateInterval(string $autoupdate): ?int
    {
        if (strpos($autoupdate, 'h') !== false) {
            $value = (int)str_replace('h', '', $autoupdate);
            return 60 * 60 * $value;
        } elseif (strpos($autoupdate, 'd') !== false) {
            $value = (int)str_replace('d', '', $autoupdate);
            return 60 * 60 * 24 * $value;
        } elseif (strpos($autoupdate, 'w') !== false) {
            $value = (int)str_replace('w', '', $autoupdate);
            return 60 * 60 * 24 * 7 * $value;
        }

        return null;
    }

    /**
     * Get a specific option from the feed options string.
     *
     * @param string $optionsStr Options string (comma-separated key=value pairs)
     * @param string $option     Option name to retrieve
     *
     * @return string|array|null Option value, all options array, or null
     */
    public function getNfOption(string $optionsStr, string $option)
    {
        return \get_nf_option($optionsStr, $option);
    }

    /**
     * Format last update time as human-readable string.
     *
     * @param int $diff Time difference in seconds
     *
     * @return string Formatted string
     */
    public function formatLastUpdate(int $diff): string
    {
        $periods = [
            [60 * 60 * 24 * 365, 'year'],
            [60 * 60 * 24 * 30, 'month'],
            [60 * 60 * 24 * 7, 'week'],
            [60 * 60 * 24, 'day'],
            [60 * 60, 'hour'],
            [60, 'minute'],
            [1, 'second'],
        ];

        if ($diff < 1) {
            return 'up to date';
        }

        foreach ($periods as $period) {
            $x = intval($diff / $period[0]);
            if ($x >= 1) {
                $unit = $period[1] . ($x > 1 ? 's' : '');
                return "last update: $x $unit ago";
            }
        }

        return 'up to date';
    }

    /**
     * Get the query filter condition for feed links.
     *
     * @param string $query       Search query
     * @param string $queryMode   Query mode (title,desc,text or title)
     * @param string $regexMode   Regex mode (empty, 'r', or 'rb')
     *
     * @return string SQL WHERE clause addition
     */
    public function buildQueryFilter(string $query, string $queryMode, string $regexMode): string
    {
        if (empty($query)) {
            return '';
        }

        $pattern = $regexMode . 'LIKE ' . Escaping::toSqlSyntax(
            ($regexMode == '') ?
            str_replace("*", "%", mb_strtolower($query, 'UTF-8')) :
            $query
        );

        switch ($queryMode) {
            case 'title,desc,text':
                return " AND (FlTitle $pattern OR FlDescription $pattern OR FlText $pattern)";
            case 'title':
                return " AND (FlTitle $pattern)";
            default:
                return " AND (FlTitle $pattern OR FlDescription $pattern OR FlText $pattern)";
        }
    }

    /**
     * Validate regex pattern for search.
     *
     * @param string $pattern Regex pattern to validate
     *
     * @return bool True if valid, false otherwise
     */
    public function validateRegexPattern(string $pattern): bool
    {
        if (empty($pattern)) {
            return true;
        }

        $result = @mysqli_query(
            $GLOBALS["DBCONNECTION"],
            'SELECT "test" RLIKE ' . Escaping::toSqlSyntax($pattern)
        );

        return $result !== false;
    }

    /**
     * Get marked feed links for processing.
     *
     * @param array|string $markedItems Array or comma-separated string of IDs
     *
     * @return array Array of feed link data with feed options
     */
    public function getMarkedFeedLinks($markedItems): array
    {
        if (is_array($markedItems)) {
            $ids = implode(',', array_filter($markedItems, 'is_scalar'));
        } else {
            $ids = $markedItems;
        }

        $sql = "SELECT fl.*, nf.*
                FROM (
                    SELECT * FROM {$this->tbpref}feedlinks
                    WHERE FlID IN ($ids)
                    ORDER BY FlNfID
                ) fl
                LEFT JOIN {$this->tbpref}newsfeeds nf ON NfID = FlNfID";

        $links = [];
        $res = Connection::query($sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $links[] = $row;
        }
        mysqli_free_result($res);

        return $links;
    }

    /**
     * Create a text from feed link data.
     *
     * @param array  $textData Text data (TxTitle, TxText, TxLgID, TxSourceURI, TxAudioURI)
     * @param string $tagName  Tag name to apply
     *
     * @return int New text ID
     */
    public function createTextFromFeed(array $textData, string $tagName): int
    {
        // Ensure tag exists
        Connection::query(
            "INSERT IGNORE INTO {$this->tbpref}tags2 (T2Text) VALUES (" .
            Escaping::toSqlSyntax($tagName) . ")"
        );

        // Create the text
        Connection::query(
            "INSERT INTO {$this->tbpref}texts (
                TxLgID, TxTitle, TxText, TxAudioURI, TxSourceURI
            ) VALUES (
                {$textData['TxLgID']},
                " . Escaping::toSqlSyntax($textData['TxTitle']) . ",
                " . Escaping::toSqlSyntax($textData['TxText']) . ",
                " . Escaping::toSqlSyntax($textData['TxAudioURI'] ?? '') . ",
                " . Escaping::toSqlSyntax($textData['TxSourceURI'] ?? '') . "
            )"
        );

        $textId = (int)Connection::lastInsertId();

        // Parse the text
        TextParsing::splitCheck(
            $textData['TxText'],
            (int)$textData['TxLgID'],
            $textId
        );

        // Apply tag
        Connection::execute(
            "INSERT INTO {$this->tbpref}texttags (TtTxID, TtT2ID)
             SELECT $textId, T2ID FROM {$this->tbpref}tags2
             WHERE T2Text = " . Escaping::toSqlSyntax($tagName)
        );

        return $textId;
    }

    /**
     * Archive old texts with a specific tag to maintain max texts limit.
     *
     * @param string $tagName  Tag name to filter
     * @param int    $maxTexts Maximum number of texts to keep
     *
     * @return array{archived: int, sentences: int, textitems: int}
     */
    public function archiveOldTexts(string $tagName, int $maxTexts): array
    {
        $result = Connection::query(
            "SELECT TtTxID FROM {$this->tbpref}texttags
             JOIN {$this->tbpref}tags2 ON TtT2ID = T2ID
             WHERE T2Text = " . Escaping::toSqlSyntax($tagName)
        );

        $textIds = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $textIds[] = (int)$row['TtTxID'];
        }
        mysqli_free_result($result);

        $stats = ['archived' => 0, 'sentences' => 0, 'textitems' => 0];

        if (count($textIds) <= $maxTexts) {
            return $stats;
        }

        sort($textIds, SORT_NUMERIC);
        $textsToArchive = array_slice($textIds, 0, count($textIds) - $maxTexts);

        foreach ($textsToArchive as $textId) {
            $stats['textitems'] += (int)Connection::execute(
                "DELETE FROM {$this->tbpref}textitems2 WHERE Ti2TxID = $textId"
            );

            $stats['sentences'] += (int)Connection::execute(
                "DELETE FROM {$this->tbpref}sentences WHERE SeTxID = $textId"
            );

            // Archive the text
            Connection::execute(
                "INSERT INTO {$this->tbpref}archivedtexts (
                    AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI
                )
                SELECT TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI
                FROM {$this->tbpref}texts WHERE TxID = $textId"
            );

            $archiveId = (int)Connection::lastInsertId();

            // Copy tags to archive
            Connection::execute(
                "INSERT INTO {$this->tbpref}archtexttags (AgAtID, AgT2ID)
                 SELECT $archiveId, TtT2ID FROM {$this->tbpref}texttags
                 WHERE TtTxID = $textId"
            );

            // Delete original text
            $stats['archived'] += (int)Connection::execute(
                "DELETE FROM {$this->tbpref}texts WHERE TxID = $textId"
            );

            Maintenance::adjustAutoIncrement('texts', 'TxID');
            Maintenance::adjustAutoIncrement('sentences', 'SeID');

            // Clean orphaned text tags
            Connection::execute(
                "DELETE {$this->tbpref}texttags FROM (
                    {$this->tbpref}texttags LEFT JOIN {$this->tbpref}texts ON TtTxID = TxID
                ) WHERE TxID IS NULL"
            );
        }

        return $stats;
    }

    /**
     * Mark feed link as having an error (prepend space to link).
     *
     * @param string $link Original link
     *
     * @return void
     */
    public function markLinkAsError(string $link): void
    {
        Connection::execute(
            "UPDATE {$this->tbpref}feedlinks
             SET FlLink = CONCAT(' ', FlLink)
             WHERE FlLink IN (" . Escaping::toSqlSyntax($link) . ")"
        );
    }

    /**
     * Get all languages for select dropdown.
     *
     * @return array Array of language records
     */
    public function getLanguages(): array
    {
        $sql = "SELECT LgID, LgName FROM {$this->tbpref}languages
                WHERE LgName <> '' ORDER BY LgName";

        $languages = [];
        $res = Connection::query($sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $languages[] = $row;
        }
        mysqli_free_result($res);

        return $languages;
    }

    /**
     * Get the sort options for feed/article lists.
     *
     * @return array Array of sort option arrays with 'value' and 'text'
     */
    public function getSortOptions(): array
    {
        return [
            ['value' => 1, 'text' => 'Title A-Z'],
            ['value' => 2, 'text' => 'Date Newest First'],
            ['value' => 3, 'text' => 'Date Oldest First'],
        ];
    }

    /**
     * Get the sort column for feeds/articles.
     *
     * @param int    $sortIndex  Sort option index (1-3)
     * @param string $prefix     Column prefix (Fl for feedlinks, Nf for newsfeeds)
     *
     * @return string SQL ORDER BY column
     */
    public function getSortColumn(int $sortIndex, string $prefix = 'Fl'): string
    {
        $cols = [
            1 => "{$prefix}Title",
            2 => "{$prefix}Date DESC",
            3 => "{$prefix}Date ASC",
        ];

        if ($prefix === 'Nf') {
            $cols = [
                1 => 'NfName',
                2 => 'NfUpdate DESC',
                3 => 'NfUpdate ASC',
            ];
        }

        return $cols[$sortIndex] ?? $cols[2];
    }
}

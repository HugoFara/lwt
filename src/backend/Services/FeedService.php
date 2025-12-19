<?php declare(strict_types=1);
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

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Maintenance;
use Lwt\Database\QueryBuilder;
use Lwt\Database\TextParsing;
use Lwt\Database\UserScopedQuery;

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
    /**
     * Get all newsfeeds for a language (or all languages).
     *
     * @param int|null $langId Language ID filter (null for all)
     *
     * @return array Array of feed records
     */
    public function getFeeds(?int $langId = null): array
    {
        $query = QueryBuilder::table('newsfeeds')
            ->select(['NfID', 'NfName', 'NfSourceURI', 'NfUpdate', 'NfOptions', 'NfLgID'])
            ->orderBy('NfUpdate', 'DESC');

        if ($langId !== null && $langId > 0) {
            $query->where('NfLgID', '=', $langId);
        }

        return $query->getPrepared();
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
        $row = QueryBuilder::table('newsfeeds')
            ->where('NfID', '=', $feedId)
            ->firstPrepared();
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
        // Complex query with raw SQL and TRIM() in JOIN condition
        // feedlinks inherits user context via FlNfID -> newsfeeds FK
        $bindings = [];
        $sql = "SELECT FlID, FlTitle, FlLink, FlDescription, FlDate, FlAudio,
                       TxID, AtID
                FROM feedlinks
                LEFT JOIN texts ON TxSourceURI = TRIM(FlLink)
                LEFT JOIN archivedtexts ON AtSourceURI = TRIM(FlLink)
                WHERE FlNfID IN ($feedIds) $whereQuery"
                . UserScopedQuery::forTablePrepared('texts', $bindings, 'texts')
                . UserScopedQuery::forTablePrepared('archivedtexts', $bindings, 'archivedtexts')
                . " ORDER BY $orderBy
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
        // feedlinks inherits user context via FlNfID -> newsfeeds FK
        $sql = "SELECT COUNT(*) AS cnt FROM feedlinks
                WHERE FlNfID IN ($feedIds) $whereQuery";
        return (int)Connection::fetchValue($sql, 'cnt');
    }

    /**
     * Count newsfeeds with optional language and query filter.
     *
     * @param int|null    $langId       Language ID filter (null for all)
     * @param string|null $queryPattern LIKE pattern for name filter (null for no filter)
     *
     * @return int Number of matching feeds
     */
    public function countFeeds(?int $langId = null, ?string $queryPattern = null): int
    {
        $query = QueryBuilder::table('newsfeeds');

        if ($langId !== null && $langId > 0) {
            $query->where('NfLgID', '=', $langId);
        }
        if ($queryPattern !== null) {
            $query->where('NfName', 'LIKE', $queryPattern);
        }

        return $query->countPrepared();
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
        return QueryBuilder::table('newsfeeds')
            ->insertPrepared([
                'NfLgID' => $data['NfLgID'],
                'NfName' => $data['NfName'],
                'NfSourceURI' => $data['NfSourceURI'],
                'NfArticleSectionTags' => $data['NfArticleSectionTags'],
                'NfFilterTags' => $data['NfFilterTags'] ?? '',
                'NfOptions' => rtrim($data['NfOptions'] ?? '', ',')
            ]);
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
        QueryBuilder::table('newsfeeds')
            ->where('NfID', '=', $feedId)
            ->updatePrepared([
                'NfLgID' => $data['NfLgID'],
                'NfName' => $data['NfName'],
                'NfSourceURI' => $data['NfSourceURI'],
                'NfArticleSectionTags' => $data['NfArticleSectionTags'],
                'NfFilterTags' => $data['NfFilterTags'] ?? '',
                'NfOptions' => rtrim($data['NfOptions'] ?? '', ',')
            ]);
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
        $ids = array_map('intval', explode(',', $feedIds));

        $articles = QueryBuilder::table('feedlinks')
            ->whereIn('FlNfID', $ids)
            ->delete();

        $feeds = QueryBuilder::table('newsfeeds')
            ->whereIn('NfID', $ids)
            ->delete();

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
        $ids = array_map('intval', explode(',', $feedIds));

        $count = QueryBuilder::table('feedlinks')
            ->whereIn('FlNfID', $ids)
            ->delete();

        // Update the feed timestamp
        QueryBuilder::table('newsfeeds')
            ->whereIn('NfID', $ids)
            ->updatePrepared(['NfUpdate' => time()]);

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
        // feedlinks inherits user context via FlNfID -> newsfeeds FK
        // Use raw SQL for TRIM(FlLink) expression
        return (int)Connection::execute(
            "UPDATE feedlinks SET FlLink = TRIM(FlLink)
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

        $rows = QueryBuilder::table('newsfeeds')
            ->where('NfOptions', 'LIKE', '%autoupdate=%')
            ->getPrepared();

        foreach ($rows as $row) {
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
     * Parses comma-separated key=value pairs. For 'all' returns full array,
     * otherwise returns specific option value or null if not found.
     *
     * Note: When searching for a specific option, returns first match.
     * For 'all', later duplicates overwrite earlier ones.
     *
     * @param string $optionsStr Options string (comma-separated key=value pairs)
     * @param string $option     Option name to retrieve ('all' for full array)
     *
     * @return string|array|null Option value, all options array, or null
     */
    public function getNfOption(string $optionsStr, string $option): string|array|null
    {
        $optionsStr = trim($optionsStr);
        if (empty($optionsStr)) {
            return ($option === 'all') ? [] : null;
        }

        $optionList = explode(',', $optionsStr);
        $result = [];

        foreach ($optionList as $opt) {
            // Note: Original used explode without limit, so key=value=extra
            // splits into ['key','value','extra'] and takes index 1 ('value')
            $parts = explode('=', $opt);
            $key = trim($parts[0] ?? '');
            $value = trim($parts[1] ?? '');

            if (!empty($key)) {
                // For 'all' mode, store all (later duplicates overwrite)
                // For specific option, return first match immediately
                if ($option !== 'all' && $key === $option) {
                    return $value;
                }
                $result[$key] = $value;
            }
        }

        if ($option === 'all') {
            return $result;
        }

        return null;
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
     * Note: This method returns a SQL fragment with embedded values for use
     * in dynamic queries. The returned SQL is safe because $regexMode is
     * validated to be 'r', 'rb', or empty, and $searchValue is escaped.
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

        $searchValue = ($regexMode == '') ?
            str_replace("*", "%", mb_strtolower($query, 'UTF-8')) :
            $query;

        // Use mysqli_real_escape_string for building dynamic SQL fragments
        $escaped = mysqli_real_escape_string(Globals::getDbConnection(), $searchValue);
        $pattern = $regexMode . "LIKE '" . $escaped . "'";

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

        $escaped = mysqli_real_escape_string(Globals::getDbConnection(), $pattern);
        $result = @mysqli_query(
            Globals::getDbConnection(),
            "SELECT 'test' RLIKE '" . $escaped . "'"
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

        // Complex subquery with JOIN
        // feedlinks inherits user context via FlNfID -> newsfeeds FK
        $sql = "SELECT fl.*, nf.*
                FROM (
                    SELECT * FROM feedlinks
                    WHERE FlID IN ($ids)
                    ORDER BY FlNfID
                ) fl
                LEFT JOIN newsfeeds nf ON NfID = FlNfID";

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
        // Ensure tag exists - use raw SQL for INSERT IGNORE
        $bindings = [$tagName];
        $sql = "INSERT IGNORE INTO tags2 (T2Text) VALUES (?)"
            . UserScopedQuery::forTablePrepared('tags2', $bindings);
        Connection::preparedExecute($sql, $bindings);

        // Create the text
        $textId = QueryBuilder::table('texts')
            ->insertPrepared([
                'TxLgID' => $textData['TxLgID'],
                'TxTitle' => $textData['TxTitle'],
                'TxText' => $textData['TxText'],
                'TxAudioURI' => $textData['TxAudioURI'] ?? '',
                'TxSourceURI' => $textData['TxSourceURI'] ?? ''
            ]);

        // Parse the text
        TextParsing::splitCheck(
            $textData['TxText'],
            (int)$textData['TxLgID'],
            $textId
        );

        // Apply tag - texttags inherits user context via TtTxID -> texts FK
        $bindings = [$textId, $tagName];
        $sql = "INSERT INTO texttags (TtTxID, TtT2ID)
             SELECT ?, T2ID FROM tags2
             WHERE T2Text = ?"
            . UserScopedQuery::forTablePrepared('tags2', $bindings);
        Connection::preparedExecute($sql, $bindings);

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
        // texttags inherits user context via TtTxID -> texts FK
        $bindings = [$tagName];
        $sql = "SELECT TtTxID FROM texttags
             JOIN tags2 ON TtT2ID = T2ID
             WHERE T2Text = ?"
            . UserScopedQuery::forTablePrepared('tags2', $bindings);
        $rows = Connection::preparedFetchAll($sql, $bindings);

        $textIds = [];
        foreach ($rows as $row) {
            $textIds[] = (int)$row['TtTxID'];
        }

        $stats = ['archived' => 0, 'sentences' => 0, 'textitems' => 0];

        if (count($textIds) <= $maxTexts) {
            return $stats;
        }

        sort($textIds, SORT_NUMERIC);
        $textsToArchive = array_slice($textIds, 0, count($textIds) - $maxTexts);

        foreach ($textsToArchive as $textId) {
            $stats['textitems'] += QueryBuilder::table('textitems2')
                ->where('Ti2TxID', '=', $textId)
                ->delete();

            $stats['sentences'] += QueryBuilder::table('sentences')
                ->where('SeTxID', '=', $textId)
                ->delete();

            // Archive the text
            $bindings = [$textId];
            $sql = "INSERT INTO archivedtexts (
                    AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI"
                    . UserScopedQuery::insertColumn('archivedtexts')
                . ")
                SELECT TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI"
                    . UserScopedQuery::insertValue('archivedtexts')
                . " FROM texts WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings);
            Connection::preparedExecute($sql, $bindings);

            $archiveId = (int)Connection::lastInsertId();

            // Copy tags to archive - archtexttags inherits user context via AgAtID -> archivedtexts FK
            // texttags inherits user context via TtTxID -> texts FK
            Connection::preparedExecute(
                "INSERT INTO archtexttags (AgAtID, AgT2ID)
                 SELECT ?, TtT2ID FROM texttags
                 WHERE TtTxID = ?",
                [$archiveId, $textId]
            );

            // Delete original text
            $stats['archived'] += QueryBuilder::table('texts')
                ->where('TxID', '=', $textId)
                ->delete();

            Maintenance::adjustAutoIncrement('texts', 'TxID');
            Maintenance::adjustAutoIncrement('sentences', 'SeID');

            // Clean orphaned text tags (complex DELETE with JOIN - keep as-is)
            Connection::execute(
                "DELETE texttags FROM (
                    texttags LEFT JOIN texts ON TtTxID = TxID
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
        // feedlinks inherits user context via FlNfID -> newsfeeds FK
        // Use raw SQL for CONCAT expression
        Connection::preparedExecute(
            "UPDATE feedlinks
             SET FlLink = CONCAT(' ', FlLink)
             WHERE FlLink = ?",
            [$link]
        );
    }

    /**
     * Get all languages for select dropdown.
     *
     * @return array Array of language records
     */
    public function getLanguages(): array
    {
        return QueryBuilder::table('languages')
            ->select(['LgID', 'LgName'])
            ->where('LgName', '<>', '')
            ->orderBy('LgName', 'ASC')
            ->getPrepared();
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

    // =========================================================================
    // RSS Feed Parsing Methods (migrated from Core/Feed/feeds.php)
    // =========================================================================

    /**
     * Extract text content from RSS feed article links.
     *
     * Handles various scenarios:
     * - Inline text from feed (description, content, encoded)
     * - Fetching full article from webpage
     * - Redirect handling for intermediate pages
     * - Charset detection and conversion
     * - XPath-based content extraction
     *
     * @param array       $feedData         Array of feed items with link, title, etc.
     * @param string      $articleSection   XPath selector(s) for article content
     * @param string      $filterTags       XPath selector(s) for elements to remove
     * @param string|null $charset          Override charset (null for auto-detect)
     *
     * @return array|string|null Extracted text data or error info
     */
    public function extractTextFromArticle(
        array $feedData,
        string $articleSection,
        string $filterTags,
        ?string $charset = null
    ): array|string|null {
        $data = null;

        foreach ($feedData as $key => $val) {
            // Handle redirect article sections
            if (strncmp($articleSection, 'redirect:', 9) == 0) {
                $feedData[$key]['link'] = $this->handleRedirectArticle(
                    $feedData[$key]['link'],
                    $articleSection,
                    $articleSection
                );
            }

            $data[$key]['TxTitle'] = $feedData[$key]['title'];
            $data[$key]['TxAudioURI'] = $feedData[$key]['audio'] ?? null;
            $data[$key]['TxText'] = "";

            // Check if feed has inline text
            if (isset($feedData[$key]['text']) && $feedData[$key]['text'] === "") {
                unset($feedData[$key]['text']);
            }

            // Get HTML content - either from inline text or fetched from URL
            if (isset($feedData[$key]['text'])) {
                $link = trim($feedData[$key]['link']);
                if (substr($link, 0, 1) == '#') {
                    // feedlinks inherits user context via FlNfID -> newsfeeds FK
                    Connection::preparedExecute(
                        "UPDATE feedlinks
                        SET FlLink = ?
                        WHERE FlID = ?",
                        [$link, (int)substr($link, 1)]
                    );
                }
                $data[$key]['TxSourceURI'] = $link;
                $htmlString = str_replace(
                    ['>', '<'],
                    ['> ', ' <'],
                    $feedData[$key]['text']
                );
            } else {
                $data[$key]['TxSourceURI'] = $feedData[$key]['link'];
                $htmlString = $this->fetchArticleContent(
                    $data[$key]['TxSourceURI'],
                    $charset
                );
            }

            // Convert line breaks
            $htmlString = str_replace(
                ['<br />', '<br>', '</br>', '</h', '</p'],
                ["\n", "\n", "", "\n</h", "\n</p"],
                $htmlString
            );

            // Parse and extract text
            $dom = new \DOMDocument();
            $previousValue = libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="UTF-8">' . $htmlString);

            // Remove XML processing instruction hack
            foreach ($dom->childNodes as $item) {
                if ($item->nodeType == XML_PI_NODE) {
                    $dom->removeChild($item);
                }
            }
            $dom->encoding = 'UTF-8';

            libxml_clear_errors();
            libxml_use_internal_errors($previousValue);

            // Build filter tags list
            $filterTagsList = explode(
                "!?!",
                rtrim(
                    "//img | //script | //meta | //noscript | //link | //iframe!?!" . $filterTags,
                    "!?!"
                )
            );

            // Check for 'new' article tag (return full HTML)
            foreach (explode("!?!", $articleSection) as $articleTag) {
                if ($articleTag == 'new') {
                    return $this->extractNewArticleHtml($dom, $filterTagsList);
                }
            }

            // Standard extraction with XPath
            $selector = new \DOMXPath($dom);

            // Remove filtered elements
            foreach ($filterTagsList as $filterTag) {
                foreach ($selector->query($filterTag) as $node) {
                    $node->parentNode->removeChild($node);
                }
            }

            // Extract text from article sections
            if (isset($feedData[$key]['text'])) {
                foreach ($selector->query($articleSection) as $textTemp) {
                    if ($textTemp->nodeValue != '') {
                        $data[$key]['TxText'] .= mb_convert_encoding(
                            $textTemp->nodeValue,
                            "HTML-ENTITIES",
                            "UTF-8"
                        );
                    }
                }
                $data[$key]['TxText'] = html_entity_decode(
                    $data[$key]['TxText'],
                    ENT_NOQUOTES,
                    "UTF-8"
                );
            } else {
                $articleTags = explode("!?!", $articleSection);
                if (strncmp($articleSection, 'redirect:', 9) == 0) {
                    unset($articleTags[0]);
                }
                foreach ($articleTags as $articleTag) {
                    $queryResult = @$selector->query($articleTag);
                    if ($queryResult !== false) {
                        foreach ($queryResult as $textTemp) {
                            if ($textTemp->nodeValue != '') {
                                $data[$key]['TxText'] .= $textTemp->nodeValue;
                            }
                        }
                    }
                }
            }

            // Handle empty text
            if ($data[$key]['TxText'] == "") {
                unset($data[$key]);
                if (!isset($data['error']['message'])) {
                    $data['error']['message'] = '';
                }
                $data['error']['message'] .= '"<a href="' . htmlspecialchars($feedData[$key]['link'], ENT_QUOTES, 'UTF-8') .
                    '" data-action="open-window" data-window-name="child">' .
                    htmlspecialchars($feedData[$key]['title'], ENT_QUOTES, 'UTF-8') . '</a>" has no text section!<br />';
                $data['error']['link'][] = $feedData[$key]['link'];
            } else {
                // Clean up whitespace
                $data[$key]['TxText'] = trim(preg_replace(
                    ['/[\r\t]+/', '/(\n)[\s^\n]*\n[\s]*/', '/\ \ +/'],
                    [' ', '$1$1', ' '],
                    $data[$key]['TxText']
                ));
            }
        }

        return $data;
    }

    /**
     * Handle redirect article section to find actual article URL.
     *
     * @param string $link           Original link
     * @param string $articleSection Full article section string
     * @param string &$newSection    Output: updated article section
     *
     * @return string Updated link
     */
    private function handleRedirectArticle(
        string $link,
        string $articleSection,
        string &$newSection
    ): string {
        $dom = new \DOMDocument();
        $htmlString = @file_get_contents(trim($link));
        if ($htmlString === false) {
            return $link;
        }

        $dom->loadHTML($htmlString);
        $xPath = new \DOMXPath($dom);

        $redirect = explode(" | ", $articleSection, 2);
        $newSection = $redirect[1] ?? '';
        $redirect = substr($redirect[0], 9);
        $feedHost = parse_url(trim($link));

        foreach ($xPath->query($redirect) as $node) {
            if (
                empty(trim($node->localName))
                || $node->nodeType == XML_TEXT_NODE
                || !$node->hasAttributes()
            ) {
                continue;
            }

            foreach ($node->attributes as $attr) {
                if ($attr->name == 'href') {
                    $link = $attr->value;
                    if (strncmp($link, '..', 2) == 0) {
                        $link = 'http://' . ($feedHost['host'] ?? 'localhost') .
                            substr($link, 2);
                    }
                }
            }
        }

        return $link;
    }

    /**
     * Fetch article content from URL with charset detection.
     *
     * @param string      $url     Article URL
     * @param string|null $charset Override charset (null for auto-detect)
     *
     * @return string HTML content
     */
    private function fetchArticleContent(string $url, ?string $charset): string
    {
        $context = stream_context_create(['http' => ['follow_location' => true]]);
        $htmlString = @file_get_contents(trim($url), false, $context);

        if (empty($htmlString)) {
            return '';
        }

        $encoding = $this->detectCharset($url, $htmlString, $charset);

        // Apply charset conversion
        $convertedCharset = $this->mapWindowsCharset($encoding);

        $htmlString = '<meta http-equiv="Content-Type" content="text/html; charset=' .
            $convertedCharset . '">' . $htmlString;

        if ($encoding != $convertedCharset) {
            $htmlString = iconv($encoding, 'utf-8', $htmlString);
        } else {
            $htmlString = mb_convert_encoding($htmlString, 'HTML-ENTITIES', $encoding);
        }

        return $htmlString;
    }

    /**
     * Detect charset from HTTP headers, meta tags, or content.
     *
     * @param string      $url        URL being fetched
     * @param string      $htmlString HTML content
     * @param string|null $override   Override charset
     *
     * @return string Detected charset
     */
    private function detectCharset(string $url, string $htmlString, ?string $override): string
    {
        if (!empty($override) && $override != 'meta') {
            return $override;
        }

        // Try HTTP headers first
        $header = @get_headers(trim($url), true);
        if ($header) {
            foreach ($header as $k => $v) {
                if (strtolower($k) == 'content-type') {
                    $contentType = is_array($v) ? $v[count($v) - 1] : $v;
                    $pos = strpos($contentType, 'charset=');
                    if ($pos !== false && strpos($contentType, 'text/html;') !== false) {
                        return substr($contentType, $pos + 8);
                    }
                }
            }
        }

        // Try meta tags
        $doc = new \DOMDocument();
        $previousValue = libxml_use_internal_errors(true);
        $doc->loadHTML($htmlString);
        libxml_clear_errors();
        libxml_use_internal_errors($previousValue);

        $nodes = $doc->getElementsByTagName('meta');

        // Check content-type meta
        foreach ($nodes as $node) {
            $len = $node->attributes->length;
            for ($i = 0; $i < $len; $i++) {
                if ($node->attributes->item($i)->name == 'content') {
                    $pos = strpos($node->attributes->item($i)->value, 'charset=');
                    if ($pos) {
                        return substr($node->attributes->item($i)->value, $pos + 8);
                    }
                }
            }
        }

        // Check charset meta
        foreach ($nodes as $node) {
            $len = $node->attributes->length;
            if ($len == 1 && $node->attributes->item(0)->name == 'charset') {
                return $node->attributes->item(0)->value;
            }
        }

        // Fallback to detection
        mb_detect_order("ASCII,UTF-8,ISO-8859-1,windows-1252,iso-8859-15");
        return mb_detect_encoding($htmlString) ?: 'UTF-8';
    }

    /**
     * Map Windows charset to UTF-8 locale equivalent.
     *
     * @param string $charset Input charset
     *
     * @return string Mapped charset
     */
    private function mapWindowsCharset(string $charset): string
    {
        $mapping = [
            'windows-1253' => 'el_GR.utf8',
            'windows-1254' => 'tr_TR.utf8',
            'windows-1255' => 'he.utf8',
            'windows-1256' => 'ar_AE.utf8',
            'windows-1258' => 'vi_VI.utf8',
            'windows-874' => 'th_TH.utf8',
        ];

        return $mapping[$charset] ?? $charset;
    }

    /**
     * Extract full HTML for 'new' article mode.
     *
     * @param \DOMDocument $dom        DOM document
     * @param array        $filterTags Tags to filter out
     *
     * @return string Cleaned HTML
     */
    private function extractNewArticleHtml(\DOMDocument $dom, array $filterTags): string
    {
        foreach ($filterTags as $filterTag) {
            $nodes = $dom->getElementsByTagName($filterTag);
            $domElemsToRemove = [];
            foreach ($nodes as $domElement) {
                $domElemsToRemove[] = $domElement;
            }
            foreach ($domElemsToRemove as $node) {
                $node->parentNode->removeChild($node);
            }
        }

        $nodes = $dom->getElementsByTagName('*');
        foreach ($nodes as $node) {
            $node->removeAttribute('onclick');
        }

        $str = $dom->saveHTML($dom);

        return preg_replace(
            ['/\<html[^\>]*\>/', '/\<body\>/'],
            ['', ''],
            $str
        );
    }

    /**
     * Parse RSS/Atom feed and return article links with metadata.
     *
     * Supports both RSS 2.0 and Atom feed formats. Extracts:
     * - Title, description, link, publication date
     * - Audio enclosures (podcast support)
     * - Inline text content (if article section specified)
     *
     * @param string $sourceUri      Feed URL
     * @param string $articleSection Tag name for inline text extraction
     *
     * @return array|false Array of feed items or false on error
     */
    public function parseRssFeed(string $sourceUri, string $articleSection): array|false
    {
        $rss = new \DOMDocument('1.0', 'utf-8');
        if (!$rss->load($sourceUri, LIBXML_NOCDATA | ENT_NOQUOTES)) {
            return false;
        }

        $rssData = [];
        $feedTags = $this->getFeedTagMapping($rss);

        if ($feedTags === null) {
            return false;
        }

        foreach ($rss->getElementsByTagName($feedTags['item']) as $node) {
            $item = [
                'title' => preg_replace(
                    ['/\s\s+/', '/\ \&\ /'],
                    [' ', ' &amp; '],
                    trim($node->getElementsByTagName($feedTags['title'])->item(0)->nodeValue)
                ),
                'desc' => isset($node->getElementsByTagName($feedTags['description'])->item(0)->nodeValue)
                    ? preg_replace(
                        ['/\ \&\ /', '/<br(\s+)?\/?>/i', '/<br [^>]*?>/i', '/\<[^\>]*\>/', '/(\n)[\s^\n]*\n[\s]*/'],
                        [' &amp; ', "\n", "\n", '', '$1$1'],
                        trim($node->getElementsByTagName($feedTags['description'])->item(0)->nodeValue)
                    )
                    : '',
                'link' => trim(
                    ($feedTags['item'] == 'entry')
                        ? $node->getElementsByTagName($feedTags['link'])->item(0)->getAttribute('href')
                        : $node->getElementsByTagName($feedTags['link'])->item(0)->nodeValue
                ),
                'date' => $node->getElementsByTagName($feedTags['pubDate'])->item(0)->nodeValue ?? null,
            ];

            // Parse date
            $item['date'] = $this->parseFeedDate($item['date'], count($rssData));

            // Truncate description
            if (strlen($item['desc']) > 1000) {
                $item['desc'] = mb_substr($item['desc'], 0, 995, "utf-8") . '...';
            }

            // Extract inline text if article section specified
            if ($articleSection) {
                foreach ($node->getElementsByTagName($articleSection) as $txtNode) {
                    if ($txtNode->parentNode === $node) {
                        $item['text'] = $txtNode->ownerDocument->saveHTML($txtNode);
                        $item['text'] = mb_convert_encoding(
                            html_entity_decode($item['text'], ENT_NOQUOTES, "UTF-8"),
                            "HTML-ENTITIES",
                            "UTF-8"
                        );
                    }
                }
            }

            // Extract audio enclosure
            $item['audio'] = "";
            foreach ($node->getElementsByTagName($feedTags['enclosure']) as $enc) {
                $type = $enc->getAttribute('type');
                if ($type == "audio/mpeg") {
                    $item['audio'] = $enc->getAttribute($feedTags['url']);
                }
            }

            // Add valid items
            if ($item['title'] != "" && ($item['link'] != "" || ($articleSection != "" && !empty($item['text'])))) {
                $rssData[] = $item;
            }
        }

        return $rssData;
    }

    /**
     * Detect and parse feed, determining best text source.
     *
     * Analyzes feed to determine whether to use:
     * - content (Atom)
     * - description (RSS)
     * - encoded (RSS with content:encoded)
     * - webpage link (external fetch)
     *
     * @param string $sourceUri Feed URL
     *
     * @return array|false Feed data with feed_text indicator or false on error
     */
    public function detectAndParseFeed(string $sourceUri): array|false
    {
        $rss = new \DOMDocument('1.0', 'utf-8');
        if (!$rss->load($sourceUri, LIBXML_NOCDATA | ENT_NOQUOTES)) {
            return false;
        }

        $rssData = [];
        $descCount = 0;
        $descNocount = 0;
        $encCount = 0;
        $encNocount = 0;

        $feedTags = $this->getFeedTagMapping($rss);
        if ($feedTags === null) {
            return false;
        }

        foreach ($rss->getElementsByTagName($feedTags['item']) as $node) {
            $item = [
                'title' => preg_replace(
                    ['/\s\s+/', '/\ \&\ /', '/\"/'],
                    [' ', ' &amp; ', '\"'],
                    trim($node->getElementsByTagName($feedTags['title'])->item(0)->nodeValue)
                ),
                'desc' => preg_replace(
                    ['/\s\s+/', '/\ \&\ /', '/\<[^\>]*\>/', '/\"/'],
                    [' ', ' &amp; ', '', '\"'],
                    trim($node->getElementsByTagName($feedTags['description'])->item(0)->nodeValue)
                ),
                'link' => trim(
                    ($feedTags['item'] == 'entry')
                        ? $node->getElementsByTagName($feedTags['link'])->item(0)->getAttribute('href')
                        : $node->getElementsByTagName($feedTags['link'])->item(0)->nodeValue
                ),
            ];

            // Handle RSS items
            if ($feedTags['item'] == 'item') {
                foreach ($node->getElementsByTagName('encoded') as $txtNode) {
                    if ($txtNode->parentNode === $node) {
                        $item['encoded'] = $txtNode->ownerDocument->saveHTML($txtNode);
                        $item['encoded'] = mb_convert_encoding(
                            html_entity_decode($item['encoded'], ENT_NOQUOTES, "UTF-8"),
                            "HTML-ENTITIES",
                            "UTF-8"
                        );
                    }
                }
                foreach ($node->getElementsByTagName('description') as $txtNode) {
                    if ($txtNode->parentNode === $node) {
                        $item['description'] = $txtNode->ownerDocument->saveHTML($txtNode);
                        $item['description'] = mb_convert_encoding(
                            html_entity_decode($item['description'], ENT_NOQUOTES, "UTF-8"),
                            "HTML-ENTITIES",
                            "UTF-8"
                        );
                    }
                }

                if (isset($item['desc'])) {
                    if (mb_strlen($item['desc'], "UTF-8") > 900) {
                        $descCount++;
                    } else {
                        $descNocount++;
                    }
                }
                if (isset($item['encoded'])) {
                    if (mb_strlen($item['encoded'], "UTF-8") > 900) {
                        $encCount++;
                    } else {
                        $encNocount++;
                    }
                }
            }

            // Handle Atom entries
            if ($feedTags['item'] == 'entry') {
                foreach ($node->getElementsByTagName('content') as $txtNode) {
                    if ($txtNode->parentNode === $node) {
                        $item['content'] = $txtNode->ownerDocument->saveHTML($txtNode);
                        $item['content'] = mb_convert_encoding(
                            html_entity_decode($item['content'], ENT_NOQUOTES, "UTF-8"),
                            "HTML-ENTITIES",
                            "UTF-8"
                        );
                    }
                }
                if (isset($item['content'])) {
                    if (mb_strlen($item['content'], "UTF-8") > 900) {
                        $descCount++;
                    } else {
                        $descNocount++;
                    }
                }
            }

            if ($item['title'] != "" && $item['link'] != "") {
                $rssData[] = $item;
            }
        }

        // Determine best text source
        if ($descCount > $descNocount) {
            $source = ($feedTags['item'] == 'entry') ? 'content' : 'description';
            $rssData['feed_text'] = $source;
            foreach ($rssData as $i => $val) {
                if (is_array($val)) {
                    $rssData[$i]['text'] = $val[$source] ?? '';
                }
            }
        } elseif ($encCount > $encNocount) {
            $rssData['feed_text'] = 'encoded';
            foreach ($rssData as $i => $val) {
                if (is_array($val)) {
                    $rssData[$i]['text'] = $val['encoded'] ?? '';
                }
            }
        } else {
            $rssData['feed_text'] = '';
        }

        $rssData['feed_title'] = $rss->getElementsByTagName('title')->item(0)->nodeValue;

        return $rssData;
    }

    /**
     * Get tag mapping for RSS/Atom feed format.
     *
     * @param \DOMDocument $rss Feed document
     *
     * @return array|null Tag mapping or null if unknown format
     */
    private function getFeedTagMapping(\DOMDocument $rss): ?array
    {
        if ($rss->getElementsByTagName('rss')->length !== 0) {
            return [
                'item' => 'item',
                'title' => 'title',
                'description' => 'description',
                'link' => 'link',
                'pubDate' => 'pubDate',
                'enclosure' => 'enclosure',
                'url' => 'url'
            ];
        } elseif ($rss->getElementsByTagName('feed')->length !== 0) {
            return [
                'item' => 'entry',
                'title' => 'title',
                'description' => 'summary',
                'link' => 'link',
                'pubDate' => 'published',
                'enclosure' => 'link',
                'url' => 'href'
            ];
        }

        return null;
    }

    /**
     * Parse feed date string to MySQL datetime format.
     *
     * @param string|null $dateStr   Date string from feed
     * @param int         $fallback  Fallback offset for ordering
     *
     * @return string MySQL datetime string
     */
    private function parseFeedDate(?string $dateStr, int $fallback): string
    {
        if ($dateStr === null) {
            return date("Y-m-d H:i:s", time() - $fallback);
        }

        $pubDate = date_parse_from_format('D, d M Y H:i:s T', $dateStr);
        if ($pubDate['error_count'] > 0) {
            return date("Y-m-d H:i:s", time() - $fallback);
        }

        return date(
            "Y-m-d H:i:s",
            mktime(
                $pubDate['hour'],
                $pubDate['minute'],
                $pubDate['second'],
                $pubDate['month'],
                $pubDate['day'],
                $pubDate['year']
            )
        );
    }

    /**
     * Save texts from RSS feed to database with automatic archival.
     *
     * Creates texts from parsed feed data, applies tags, and archives
     * old texts if max_texts limit is exceeded.
     *
     * @param array $texts Array of text data from extractTextFromArticle()
     *
     * @return string Status message
     */
    public function saveTextsFromFeed(array $texts): string
    {
        $texts = array_reverse($texts);
        $message1 = $message2 = $message3 = $message4 = 0;
        $NfID = null;

        foreach ($texts as $text) {
            $NfID[] = $text['Nf_ID'];
        }
        $NfID = array_unique($NfID);

        $NfTag = '';
        $textItem = null;
        $nfMaxTexts = null;

        foreach ($NfID as $feedID) {
            foreach ($texts as $text) {
                if ($feedID == $text['Nf_ID']) {
                    if ($NfTag != '"' . implode('","', $text['TagList']) . '"') {
                        $NfTag = '"' . implode('","', $text['TagList']) . '"';

                        // Ensure tags exist
                        foreach ($text['TagList'] as $tag) {
                            if (!in_array($tag, $_SESSION['TEXTTAGS'] ?? [])) {
                                $bindings = [$tag];
                                $sql = 'INSERT INTO tags2 (T2Text'
                                    . UserScopedQuery::insertColumn('tags2')
                                    . ') VALUES (?'
                                    . UserScopedQuery::insertValuePrepared('tags2', $bindings)
                                    . ')';
                                Connection::preparedExecute($sql, $bindings);
                            }
                        }
                        $nfMaxTexts = $text['Nf_Max_Texts'];
                    }

                    // Create the text
                    $id = QueryBuilder::table('texts')
                        ->insertPrepared([
                            'TxLgID' => $text['TxLgID'],
                            'TxTitle' => $text['TxTitle'],
                            'TxText' => $text['TxText'],
                            'TxAudioURI' => $text['TxAudioURI'],
                            'TxSourceURI' => $text['TxSourceURI']
                        ]);

                    // Parse the text
                    $bindings = [$id];
                    $textContent = Connection::preparedFetchValue(
                        'SELECT TxText FROM texts WHERE TxID = ?'
                        . UserScopedQuery::forTablePrepared('texts', $bindings),
                        $bindings,
                        'TxText'
                    );
                    $textLgId = Connection::preparedFetchValue(
                        'SELECT TxLgID FROM texts WHERE TxID = ?'
                        . UserScopedQuery::forTablePrepared('texts', $bindings),
                        $bindings,
                        'TxLgID'
                    );
                    TextParsing::splitCheck($textContent, $textLgId, $id);

                    // Apply tags - texttags inherits user context via TtTxID -> texts FK
                    $bindings = [];
                    Connection::query(
                        'INSERT INTO texttags (TtTxID, TtT2ID)
                        SELECT ' . $id . ', T2ID FROM tags2
                        WHERE T2Text IN (' . $NfTag . ')'
                        . UserScopedQuery::forTablePrepared('tags2', $bindings)
                    );
                }
            }

            // Refresh text tags
            TagService::getAllTextTags(true);

            // Get all texts with this tag - texttags inherits user context via TtTxID -> texts FK
            $bindings = [];
            $result = Connection::query(
                "SELECT TtTxID FROM texttags
                JOIN tags2 ON TtT2ID=T2ID
                WHERE T2Text IN (" . $NfTag . ")"
                . UserScopedQuery::forTablePrepared('tags2', $bindings)
            );

            $textCount = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $textItem[$textCount++] = $row['TtTxID'];
            }
            mysqli_free_result($result);

            // Archive excess texts
            if ($textCount > $nfMaxTexts) {
                sort($textItem, SORT_NUMERIC);
                $textItem = array_slice($textItem, 0, $textCount - $nfMaxTexts);

                foreach ($textItem as $textID) {
                    $message3 += QueryBuilder::table('textitems2')
                        ->where('Ti2TxID', '=', $textID)
                        ->delete();
                    $message2 += QueryBuilder::table('sentences')
                        ->where('SeTxID', '=', $textID)
                        ->delete();

                    $bindings = [$textID];
                    $message4 += (int)Connection::execute(
                        'INSERT INTO archivedtexts (
                            AtLgID, AtTitle, AtText, AtAnnotatedText,
                            AtAudioURI, AtSourceURI'
                            . UserScopedQuery::insertColumn('archivedtexts')
                        . ') SELECT TxLgID, TxTitle, TxText, TxAnnotatedText,
                        TxAudioURI, TxSourceURI'
                            . UserScopedQuery::insertValue('archivedtexts')
                        . ' FROM texts
                        WHERE TxID = ' . $textID
                        . UserScopedQuery::forTable('texts')
                    );

                    $archiveId = (int)Connection::lastInsertId();
                    // archtexttags inherits user context via AgAtID -> archivedtexts FK
                    // texttags inherits user context via TtTxID -> texts FK
                    Connection::execute(
                        'INSERT INTO archtexttags (AgAtID, AgT2ID)
                        SELECT ' . $archiveId . ', TtT2ID FROM texttags
                        WHERE TtTxID = ' . $textID
                    );

                    $message1 += QueryBuilder::table('texts')
                        ->where('TxID', '=', $textID)
                        ->delete();

                    Maintenance::adjustAutoIncrement('texts', 'TxID');
                    Maintenance::adjustAutoIncrement('sentences', 'SeID');

                    // Clean orphaned text tags (complex DELETE with JOIN - keep as-is)
                    Connection::execute(
                        "DELETE texttags
                        FROM (texttags
                            LEFT JOIN texts ON TtTxID = TxID
                        ) WHERE TxID IS NULL"
                    );
                }
            }
        }

        if ($message4 > 0 || $message1 > 0) {
            return "Texts archived: " . $message1 .
                " / Sentences deleted: " . $message2 .
                " / Text items deleted: " . $message3;
        }

        return '';
    }

    /**
     * Get feed data for loading via JavaScript.
     *
     * Returns an array of feed configuration objects that can be encoded as JSON
     * and passed to the frontend TypeScript feed loader.
     *
     * @param int  $currentFeed     Feed ID to load (0 for auto-update check)
     * @param bool $checkAutoupdate Whether to check for auto-update feeds
     *
     * @return array{feeds: array<array{id: int, name: string, sourceUri: string, options: string}>, count: int}
     */
    public function getFeedLoadConfig(int $currentFeed, bool $checkAutoupdate): array
    {
        $feeds = [];

        if ($checkAutoupdate) {
            $rows = QueryBuilder::table('newsfeeds')
                ->where('NfOptions', 'LIKE', '%autoupdate=%')
                ->getPrepared();

            foreach ($rows as $row) {
                $autoupdate = $this->getNfOption((string)$row['NfOptions'], 'autoupdate');
                if (!$autoupdate) {
                    continue;
                }

                $interval = $this->parseAutoUpdateInterval($autoupdate);
                if ($interval === null) {
                    continue;
                }

                if (time() > ($interval + (int)$row['NfUpdate'])) {
                    $feeds[] = [
                        'id' => (int)$row['NfID'],
                        'name' => (string)$row['NfName'],
                        'sourceUri' => (string)($row['NfSourceURI'] ?? ''),
                        'options' => (string)($row['NfOptions'] ?? '')
                    ];
                }
            }
        } else {
            $sql = "SELECT * FROM newsfeeds WHERE NfID IN ($currentFeed)";
            $result = Connection::query($sql);

            while ($row = mysqli_fetch_assoc($result)) {
                $feeds[] = [
                    'id' => (int)$row['NfID'],
                    'name' => (string)$row['NfName'],
                    'sourceUri' => (string)($row['NfSourceURI'] ?? ''),
                    'options' => (string)($row['NfOptions'] ?? '')
                ];
            }
            mysqli_free_result($result);
        }

        return [
            'feeds' => $feeds,
            'count' => count($feeds)
        ];
    }

    /**
     * Render feed loading interface using the modern TypeScript loader.
     *
     * This method outputs JSON configuration that is consumed by the
     * feed_loader.ts TypeScript module instead of inline JavaScript.
     *
     * @param int    $currentFeed     Feed ID to load
     * @param bool   $checkAutoupdate Whether checking auto-update
     * @param string $redirectUrl     URL to redirect after completion
     *
     * @return void
     */
    public function renderFeedLoadInterfaceModern(
        int $currentFeed,
        bool $checkAutoupdate,
        string $redirectUrl
    ): void {
        $config = $this->getFeedLoadConfig($currentFeed, $checkAutoupdate);

        // Output JSON config for TypeScript
        echo '<script type="application/json" id="feed-loader-config">';
        echo json_encode([
            'feeds' => $config['feeds'],
            'redirectUrl' => $redirectUrl
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        echo '</script>';

        // Show progress UI
        if ($config['count'] != 1) {
            echo "<div class=\"msgblue\"><p>UPDATING <span id=\"feedcount\">0</span>/" .
                $config['count'] . " FEEDS</p></div>";
        }

        // Create placeholder divs for each feed
        foreach ($config['feeds'] as $feed) {
            echo "<div id='feed_{$feed['id']}' class=\"msgblue\"><p>" .
                htmlspecialchars($feed['name']) . ": waiting</p></div>";
        }

        // Continue button (no inline onclick - handled by event delegation)
        echo "<div class=\"center\"><button data-action=\"feed-continue\" data-url=\"" .
            htmlspecialchars($redirectUrl) . "\">Continue</button></div>";
    }
}

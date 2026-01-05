<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Modules\Feed\Application\FeedFacade;

/**
 * Handler for RSS feed-related API operations.
 *
 * Extracted from api_v1.php lines 827-954 (namespace Lwt\Ajax\Feed).
 */
class FeedHandler
{
    private FeedFacade $feedService;

    public function __construct(FeedFacade $feedService)
    {
        $this->feedService = $feedService;
    }
    /**
     * Get the list of feeds and insert them into the database.
     *
     * @param array<array<string, string>> $feed A feed with articles
     * @param int                          $nfid News feed ID
     *
     * @return array{0: int, 1: int} Number of imported feeds and number of duplicated feeds.
     */
    public function getFeedsList(array $feed, int $nfid): array
    {
        if (empty($feed)) {
            return [0, 0];
        }

        // Build parameterized query with placeholders
        $placeholderRow = '(?, ?, ?, ?, ?, ?, ?)';
        $placeholders = array_fill(0, count($feed), $placeholderRow);

        $sql = 'INSERT IGNORE INTO feedlinks
                (FlTitle, FlLink, FlText, FlDescription, FlDate, FlAudio, FlNfID)
                VALUES ' . implode(', ', $placeholders);

        // Collect all parameters
        $params = [];
        foreach ($feed as $data) {
            $params[] = $data['title'] ?? '';
            $params[] = $data['link'] ?? '';
            $params[] = $data['text'] ?? null;
            $params[] = $data['desc'] ?? '';
            $params[] = $data['date'] ?? '';
            $params[] = $data['audio'] ?? '';
            $params[] = $nfid;
        }

        $stmt = Connection::prepare($sql);
        $stmt->bindValues($params);
        $stmt->execute();

        $importedFeed = $stmt->affectedRows();
        $nif = count($feed) - $importedFeed;

        return [$importedFeed, $nif];
    }

    /**
     * Update the feeds database and return a result message.
     *
     * @param int    $importedFeed Number of imported feeds
     * @param int    $nif          Number of duplicated feeds
     * @param string $nfname       News feed name
     * @param int    $nfid         News feed ID
     * @param string $nfoptions    News feed options
     *
     * @return string Result message
     */
    public function getFeedResult(int $importedFeed, int $nif, string $nfname, int $nfid, string $nfoptions): string
    {
        // Update feed timestamp using QueryBuilder
        QueryBuilder::table('newsfeeds')
            ->where('NfID', '=', $nfid)
            ->updatePrepared(['NfUpdate' => time()]);

        $nfMaxLinksRaw = $this->feedService->getNfOption($nfoptions, 'max_links');
        if (!$nfMaxLinksRaw || is_array($nfMaxLinksRaw)) {
            if ($this->feedService->getNfOption($nfoptions, 'article_source')) {
                $nfMaxLinksRaw = Settings::getWithDefault('set-max-articles-with-text');
            } else {
                $nfMaxLinksRaw = Settings::getWithDefault('set-max-articles-without-text');
            }
        }
        $nfMaxLinks = (int)$nfMaxLinksRaw;

        $msg = $nfname . ": ";
        if (!$importedFeed) {
            $msg .= "no";
        } else {
            $msg .= $importedFeed;
        }
        $msg .= " new article";
        if ($importedFeed > 1) {
            $msg .= "s";
        }
        $msg .= " imported";
        if ($nif > 1) {
            $msg .= ", $nif articles are dublicates";
        } elseif ($nif == 1) {
            $msg .= ", $nif dublicated article";
        }

        // Count total feedlinks using QueryBuilder
        $row = QueryBuilder::table('feedlinks')
            ->select(['COUNT(*) AS total'])
            ->where('FlNfID', '=', $nfid)
            ->firstPrepared();

        $to = ($row !== null ? (int)$row['total'] : 0) - $nfMaxLinks;
        if ($to > 0) {
            QueryBuilder::table('feedlinks')
                ->whereIn('FlNfID', [$nfid])
                ->orderBy('FlDate', 'ASC')
                ->limit($to)
                ->deletePrepared();
            $msg .= ", $to old article(s) deleted";
        }
        return $msg;
    }

    /**
     * Load a feed and return result.
     *
     * @param string $nfname      Newsfeed name
     * @param int    $nfid        News feed ID
     * @param string $nfsourceuri News feed source
     * @param string $nfoptions   News feed options
     *
     * @return array{success?: true, message?: string, imported?: int, duplicates?: int, error?: string}
     */
    public function loadFeed(string $nfname, int $nfid, string $nfsourceuri, string $nfoptions): array
    {
        $articleSource = $this->feedService->getNfOption($nfoptions, 'article_source');
        $feed = $this->feedService->parseRssFeed($nfsourceuri, is_string($articleSource) ? $articleSource : '');
        if (empty($feed)) {
            return [
                "error" => 'Could not load "' . $nfname . '"'
            ];
        }
        list($importedFeed, $nif) = $this->getFeedsList($feed, $nfid);
        $msg = $this->getFeedResult($importedFeed, $nif, $nfname, $nfid, $nfoptions);
        return [
            "success" => true,
            "message" => $msg,
            "imported" => $importedFeed,
            "duplicates" => $nif
        ];
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for loading a feed.
     *
     * @param string $name      Feed name
     * @param int    $feedId    Feed ID
     * @param string $sourceUri Feed source URI
     * @param string $options   Feed options
     *
     * @return array{success?: true, message?: string, imported?: int, duplicates?: int, error?: string}
     */
    public function formatLoadFeed(string $name, int $feedId, string $sourceUri, string $options): array
    {
        return $this->loadFeed($name, $feedId, $sourceUri, $options);
    }

    // =========================================================================
    // SPA API Methods
    // =========================================================================

    /**
     * Get list of feeds with pagination and filtering.
     *
     * @param array $params Filter parameters:
     *                      - lang: int|null (language ID filter)
     *                      - query: string|null (search query)
     *                      - page: int (default 1)
     *                      - per_page: int (default 50)
     *                      - sort: int (1=name, 2=update desc, 3=update asc)
     *
     * @return array{feeds: array, pagination: array, languages: array}
     */
    public function getFeedList(array $params): array
    {
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = max(1, min(100, (int)($params['per_page'] ?? 50)));
        $langId = isset($params['lang']) && $params['lang'] !== '' ? (int)$params['lang'] : null;
        $query = $params['query'] ?? '';
        $sort = max(1, min(3, (int)($params['sort'] ?? 2)));

        // Build WHERE clause with parameters
        $whereConditions = ['1=1'];
        $params = [];

        if ($langId !== null && $langId > 0) {
            $whereConditions[] = "NfLgID = ?";
            $params[] = $langId;
        }
        if (!empty($query)) {
            $whereConditions[] = "NfName LIKE ?";
            $params[] = '%' . str_replace('*', '%', (string)$query) . '%';
        }

        $where = implode(' AND ', $whereConditions);

        // Count total using raw SQL with fixed table name
        $total = (int)Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM newsfeeds WHERE $where",
            $params,
            'cnt'
        );

        // Calculate pagination
        $totalPages = (int)ceil($total / $perPage);
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        // Sort order
        $sorts = ['NfName ASC', 'NfUpdate DESC', 'NfUpdate ASC'];
        $orderBy = $sorts[$sort - 1] ?? 'NfUpdate DESC';

        // Get feeds with language names and article counts
        $sql = "SELECT nf.*, lg.LgName,
                       (SELECT COUNT(*) FROM feedlinks WHERE FlNfID = NfID) AS articleCount
                FROM newsfeeds nf
                LEFT JOIN languages lg ON lg.LgID = nf.NfLgID
                WHERE $where
                ORDER BY $orderBy
                LIMIT ?, ?";

        // Add pagination parameters
        $params[] = $offset;
        $params[] = $perPage;

        $feeds = [];
        $rows = Connection::preparedFetchAll($sql, $params);
        foreach ($rows as $row) {
            $feeds[] = $this->formatFeedRecord($row);
        }

        // Get languages for filter dropdown
        $languages = $this->getLanguagesForSelect();

        return [
            'feeds' => $feeds,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ],
            'languages' => $languages
        ];
    }

    /**
     * Format a feed record for API response.
     *
     * @param array $row Database record
     *
     * @return array Formatted feed data
     */
    private function formatFeedRecord(array $row): array
    {
        $options = $this->feedService->getNfOption((string)$row['NfOptions'], 'all');
        $updateTimestamp = (int)$row['NfUpdate'];
        $lastUpdate = $updateTimestamp > 0
            ? $this->feedService->formatLastUpdate(time() - $updateTimestamp)
            : 'never';

        return [
            'id' => (int)$row['NfID'],
            'name' => (string)$row['NfName'],
            'sourceUri' => (string)$row['NfSourceURI'],
            'langId' => (int)$row['NfLgID'],
            'langName' => (string)($row['LgName'] ?? ''),
            'articleSectionTags' => (string)$row['NfArticleSectionTags'],
            'filterTags' => (string)$row['NfFilterTags'],
            'options' => is_array($options) ? $options : [],
            'optionsString' => (string)$row['NfOptions'],
            'updateTimestamp' => $updateTimestamp,
            'lastUpdate' => $lastUpdate,
            'articleCount' => (int)($row['articleCount'] ?? 0)
        ];
    }

    /**
     * Get languages for filter dropdown.
     *
     * @return array Array of language options
     */
    private function getLanguagesForSelect(): array
    {
        $languages = [];

        $rows = QueryBuilder::table('languages')
            ->select(['LgID', 'LgName'])
            ->orderBy('LgName', 'ASC')
            ->getPrepared();

        foreach ($rows as $row) {
            $languages[] = [
                'id' => (int)$row['LgID'],
                'name' => (string)$row['LgName']
            ];
        }

        return $languages;
    }

    /**
     * Get a single feed by ID.
     *
     * @param int $feedId Feed ID
     *
     * @return array Feed data or error
     */
    public function getFeed(int $feedId): array
    {
        $feed = $this->feedService->getFeedById($feedId);
        if ($feed === null) {
            return ['error' => 'Feed not found'];
        }

        $feed['LgName'] = '';
        $feed['articleCount'] = 0;

        // Get language name
        $langResult = QueryBuilder::table('languages')
            ->select(['LgName'])
            ->where('LgID', '=', (int)$feed['NfLgID'])
            ->firstPrepared();
        if ($langResult) {
            $feed['LgName'] = $langResult['LgName'];
        }

        // Get article count
        $countResult = QueryBuilder::table('feedlinks')
            ->select(['COUNT(*) AS cnt'])
            ->where('FlNfID', '=', $feedId)
            ->firstPrepared();
        if ($countResult) {
            $feed['articleCount'] = (int)$countResult['cnt'];
        }

        return $this->formatFeedRecord($feed);
    }

    /**
     * Create a new feed.
     *
     * @param array $data Feed data
     *
     * @return array{success: bool, feed?: array, error?: string}
     */
    public function createFeed(array $data): array
    {
        $langId = (int)($data['langId'] ?? 0);
        $name = trim($data['name'] ?? '');
        $sourceUri = trim($data['sourceUri'] ?? '');

        if ($langId <= 0) {
            return ['success' => false, 'error' => 'Language is required'];
        }
        if (empty($name)) {
            return ['success' => false, 'error' => 'Feed name is required'];
        }
        if (empty($sourceUri)) {
            return ['success' => false, 'error' => 'Source URI is required'];
        }

        $feedId = $this->feedService->createFeed([
            'NfLgID' => $langId,
            'NfName' => $name,
            'NfSourceURI' => $sourceUri,
            'NfArticleSectionTags' => $data['articleSectionTags'] ?? '',
            'NfFilterTags' => $data['filterTags'] ?? '',
            'NfOptions' => $data['options'] ?? ''
        ]);

        return [
            'success' => true,
            'feed' => $this->getFeed($feedId)
        ];
    }

    /**
     * Update an existing feed.
     *
     * @param int   $feedId Feed ID
     * @param array $data   Feed data
     *
     * @return array{success: bool, feed?: array, error?: string}
     */
    public function updateFeed(int $feedId, array $data): array
    {
        $existing = $this->feedService->getFeedById($feedId);
        if ($existing === null) {
            return ['success' => false, 'error' => 'Feed not found'];
        }

        $this->feedService->updateFeed($feedId, [
            'NfLgID' => $data['langId'] ?? $existing['NfLgID'],
            'NfName' => $data['name'] ?? $existing['NfName'],
            'NfSourceURI' => $data['sourceUri'] ?? $existing['NfSourceURI'],
            'NfArticleSectionTags' => $data['articleSectionTags'] ?? $existing['NfArticleSectionTags'],
            'NfFilterTags' => $data['filterTags'] ?? $existing['NfFilterTags'],
            'NfOptions' => $data['options'] ?? $existing['NfOptions']
        ]);

        return [
            'success' => true,
            'feed' => $this->getFeed($feedId)
        ];
    }

    /**
     * Delete feeds.
     *
     * @param array $feedIds Array of feed IDs to delete
     *
     * @return array{success: bool, deleted: int}
     */
    public function deleteFeeds(array $feedIds): array
    {
        if (empty($feedIds)) {
            return ['success' => false, 'deleted' => 0];
        }

        $ids = implode(',', array_map('intval', $feedIds));
        $result = $this->feedService->deleteFeeds($ids);

        return [
            'success' => true,
            'deleted' => $result['feeds']
        ];
    }

    /**
     * Get articles for a feed.
     *
     * @param array $params Parameters:
     *                      - feed_id: int (required)
     *                      - query: string (search)
     *                      - page: int
     *                      - per_page: int
     *                      - sort: int (1=date desc, 2=date asc, 3=title)
     *
     * @return array{articles?: array, pagination?: array, feed?: array, error?: string}
     */
    public function getArticles(array $params): array
    {
        $feedId = (int)($params['feed_id'] ?? 0);
        if ($feedId <= 0) {
            return ['error' => 'Feed ID is required'];
        }

        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = max(1, min(100, (int)($params['per_page'] ?? 50)));
        $query = $params['query'] ?? '';
        $sort = max(1, min(3, (int)($params['sort'] ?? 1)));

        // Get feed info
        $feed = $this->feedService->getFeedById($feedId);
        if ($feed === null) {
            return ['error' => 'Feed not found'];
        }

        // Build WHERE clause with parameters
        $whereConditions = ["FlNfID = ?"];
        $queryParams = [$feedId];

        if (!empty($query)) {
            $pattern = '%' . str_replace('*', '%', (string)$query) . '%';
            $whereConditions[] = "(FlTitle LIKE ? OR FlDescription LIKE ?)";
            $queryParams[] = $pattern;
            $queryParams[] = $pattern;
        }

        $where = implode(' AND ', $whereConditions);

        // Count total using raw SQL with fixed table name
        $total = (int)Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM feedlinks WHERE $where",
            $queryParams,
            'cnt'
        );

        // Calculate pagination
        $totalPages = (int)ceil($total / $perPage);
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        // Sort order
        $sorts = ['FlDate DESC', 'FlDate ASC', 'FlTitle ASC'];
        $orderBy = $sorts[$sort - 1] ?? 'FlDate DESC';

        // Get articles with import status
        $sql = "SELECT fl.*, tx.TxID, at.AtID
                FROM feedlinks fl
                LEFT JOIN texts tx ON tx.TxSourceURI = TRIM(fl.FlLink)
                LEFT JOIN archivedtexts at ON at.AtSourceURI = TRIM(fl.FlLink)
                WHERE $where
                ORDER BY $orderBy
                LIMIT ?, ?";

        // Add pagination parameters
        $queryParams[] = $offset;
        $queryParams[] = $perPage;

        $articles = [];
        $rows = Connection::preparedFetchAll($sql, $queryParams);
        foreach ($rows as $row) {
            $articles[] = $this->formatArticleRecord($row);
        }

        return [
            'articles' => $articles,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ],
            'feed' => [
                'id' => (int)$feed['NfID'],
                'name' => (string)$feed['NfName'],
                'langId' => (int)$feed['NfLgID']
            ]
        ];
    }

    /**
     * Format an article record for API response.
     *
     * @param array $row Database record
     *
     * @return array Formatted article data
     */
    private function formatArticleRecord(array $row): array
    {
        $status = 'new';
        if (!empty($row['TxID'])) {
            $status = 'imported';
        } elseif (!empty($row['AtID'])) {
            $status = 'archived';
        } elseif (str_starts_with((string)$row['FlLink'], ' ')) {
            $status = 'error';
        }

        $textId = isset($row['TxID']) && $row['TxID'] !== null && $row['TxID'] !== ''
            ? (int)$row['TxID'] : null;
        $archivedTextId = isset($row['AtID']) && $row['AtID'] !== null && $row['AtID'] !== ''
            ? (int)$row['AtID'] : null;

        return [
            'id' => (int)$row['FlID'],
            'title' => (string)$row['FlTitle'],
            'link' => trim((string)$row['FlLink']),
            'description' => (string)$row['FlDescription'],
            'date' => (string)$row['FlDate'],
            'audio' => (string)$row['FlAudio'],
            'hasText' => !empty($row['FlText']),
            'status' => $status,
            'textId' => $textId,
            'archivedTextId' => $archivedTextId
        ];
    }

    /**
     * Delete articles.
     *
     * @param int   $feedId Feed ID
     * @param array $articleIds Article IDs to delete (empty = all)
     *
     * @return array{success: bool, deleted: int}
     */
    public function deleteArticles(int $feedId, array $articleIds = []): array
    {
        if (empty($articleIds)) {
            // Delete all articles for feed
            $deleted = $this->feedService->deleteArticles((string)$feedId);
        } else {
            // Delete specific articles
            $ids = array_map('intval', $articleIds);
            $deleted = QueryBuilder::table('feedlinks')
                ->whereIn('FlID', $ids)
                ->whereIn('FlNfID', [$feedId])
                ->delete();
        }

        return [
            'success' => true,
            'deleted' => $deleted
        ];
    }

    /**
     * Import articles as texts.
     *
     * @param array $data Import data:
     *                    - article_ids: array of article IDs
     *
     * @return array{success: bool, imported: int, errors: array}
     */
    public function importArticles(array $data): array
    {
        $articleIds = $data['article_ids'] ?? [];
        if (empty($articleIds)) {
            return ['success' => false, 'imported' => 0, 'errors' => ['No articles selected']];
        }

        $ids = implode(',', array_map('intval', $articleIds));
        $feedLinks = $this->feedService->getMarkedFeedLinks($ids);

        $imported = 0;
        $errors = [];

        foreach ($feedLinks as $row) {
            $nfOptions = $row['NfOptions'] ?? '';
            $nfName = (string)$row['NfName'];

            $tagNameRaw = $this->feedService->getNfOption($nfOptions, 'tag');
            $tagName = is_string($tagNameRaw) && $tagNameRaw !== '' ? $tagNameRaw : mb_substr($nfName, 0, 20, 'utf-8');

            $maxTextsRaw = $this->feedService->getNfOption($nfOptions, 'max_texts');
            $maxTexts = is_string($maxTextsRaw) ? (int)$maxTextsRaw : 0;
            if (!$maxTexts) {
                $maxTexts = (int)Settings::getWithDefault('set-max-texts-per-feed');
            }

            $doc = [[
                'link' => empty($row['FlLink']) ? ('#' . $row['FlID']) : $row['FlLink'],
                'title' => $row['FlTitle'],
                'audio' => $row['FlAudio'],
                'text' => $row['FlText']
            ]];

            $charsetRaw = $this->feedService->getNfOption($nfOptions, 'charset');
            $charset = is_string($charsetRaw) ? $charsetRaw : null;
            $texts = $this->feedService->extractTextFromArticle(
                $doc,
                $row['NfArticleSectionTags'],
                $row['NfFilterTags'],
                $charset
            );

            if (isset($texts['error'])) {
                $errors[] = $texts['error']['message'];
                foreach ($texts['error']['link'] as $errLink) {
                    $this->feedService->markLinkAsError($errLink);
                }
                unset($texts['error']);
            }

            if (is_array($texts)) {
                foreach ($texts as $text) {
                    $this->feedService->createTextFromFeed([
                        'TxLgID' => $row['NfLgID'],
                        'TxTitle' => $text['TxTitle'],
                        'TxText' => $text['TxText'],
                        'TxAudioURI' => $text['TxAudioURI'] ?? '',
                        'TxSourceURI' => $text['TxSourceURI'] ?? ''
                    ], $tagName);
                    $imported++;
                }
            }

            $this->feedService->archiveOldTexts($tagName, $maxTexts);
        }

        return [
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
        ];
    }

    /**
     * Reset error articles (remove leading space from links).
     *
     * @param int $feedId Feed ID
     *
     * @return array{success: bool, reset: int}
     */
    public function resetErrorArticles(int $feedId): array
    {
        $reset = $this->feedService->resetUnloadableArticles((string)$feedId);
        return [
            'success' => true,
            'reset' => $reset
        ];
    }

    // =========================================================================
    // SPA API Response Formatters
    // =========================================================================

    /**
     * Format response for getting feed list.
     */
    public function formatGetFeedList(array $params): array
    {
        return $this->getFeedList($params);
    }

    /**
     * Format response for getting single feed.
     */
    public function formatGetFeed(int $feedId): array
    {
        return $this->getFeed($feedId);
    }

    /**
     * Format response for creating feed.
     */
    public function formatCreateFeed(array $data): array
    {
        return $this->createFeed($data);
    }

    /**
     * Format response for updating feed.
     */
    public function formatUpdateFeed(int $feedId, array $data): array
    {
        return $this->updateFeed($feedId, $data);
    }

    /**
     * Format response for deleting feeds.
     */
    public function formatDeleteFeeds(array $feedIds): array
    {
        return $this->deleteFeeds($feedIds);
    }

    /**
     * Format response for getting articles.
     */
    public function formatGetArticles(array $params): array
    {
        return $this->getArticles($params);
    }

    /**
     * Format response for deleting articles.
     */
    public function formatDeleteArticles(int $feedId, array $articleIds = []): array
    {
        return $this->deleteArticles($feedId, $articleIds);
    }

    /**
     * Format response for importing articles.
     */
    public function formatImportArticles(array $data): array
    {
        return $this->importArticles($data);
    }

    /**
     * Format response for resetting error articles.
     */
    public function formatResetErrorArticles(int $feedId): array
    {
        return $this->resetErrorArticles($feedId);
    }
}

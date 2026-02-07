<?php

/**
 * Feed API Handler
 *
 * Handles all feed-related API operations including CRUD, article management,
 * and import functionality.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Feed\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Feed\Http;

use Lwt\Api\V1\Response;
use Lwt\Shared\Http\ApiRoutableInterface;
use Lwt\Shared\Http\ApiRoutableTrait;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Http\JsonResponse;
use Lwt\Modules\Feed\Application\FeedFacade;

/**
 * API handler for feed-related operations.
 *
 * Provides REST API endpoints for feed management, including
 * CRUD operations, article management, and import.
 *
 * @since 3.0.0
 */
class FeedApiHandler implements ApiRoutableInterface
{
    use ApiRoutableTrait;

    private FeedFacade $feedFacade;

    public function __construct(FeedFacade $feedFacade)
    {
        $this->feedFacade = $feedFacade;
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

        $sql = 'INSERT IGNORE INTO feed_links
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
        QueryBuilder::table('news_feeds')
            ->where('NfID', '=', $nfid)
            ->updatePrepared(['NfUpdate' => time()]);

        $nfMaxLinksRaw = $this->feedFacade->getNfOption($nfoptions, 'max_links');
        if ($nfMaxLinksRaw === null || $nfMaxLinksRaw === '' || is_array($nfMaxLinksRaw)) {
            $articleSource = $this->feedFacade->getNfOption($nfoptions, 'article_source');
            if ($articleSource !== null && $articleSource !== '' && !is_array($articleSource)) {
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

        // Count total feed_links using QueryBuilder
        $row = QueryBuilder::table('feed_links')
            ->select(['COUNT(*) AS total'])
            ->where('FlNfID', '=', $nfid)
            ->firstPrepared();

        $to = ($row !== null ? (int)$row['total'] : 0) - $nfMaxLinks;
        if ($to > 0) {
            QueryBuilder::table('feed_links')
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
        $articleSource = $this->feedFacade->getNfOption($nfoptions, 'article_source');
        $feed = $this->feedFacade->parseRssFeed($nfsourceuri, is_string($articleSource) ? $articleSource : '');
        if (!is_array($feed) || count($feed) === 0) {
            return [
                "error" => 'Could not load "' . $nfname . '"'
            ];
        }
        /** @var array<array-key, array<string, string>> $feed */
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
        $query = (string)($params['query'] ?? '');
        $sort = max(1, min(3, (int)($params['sort'] ?? 2)));

        // Build WHERE clause with parameters
        $whereConditions = ['1=1'];
        $queryParams = [];

        if ($langId !== null && $langId > 0) {
            $whereConditions[] = "NfLgID = ?";
            $queryParams[] = $langId;
        }
        if (is_string($query) && $query !== '') {
            $whereConditions[] = "NfName LIKE ?";
            $queryParams[] = '%' . str_replace('*', '%', $query) . '%';
        }

        $where = implode(' AND ', $whereConditions);

        // Count total using raw SQL with fixed table name
        $total = (int)Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM news_feeds WHERE $where",
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
        $sorts = ['NfName ASC', 'NfUpdate DESC', 'NfUpdate ASC'];
        $orderBy = $sorts[$sort - 1] ?? 'NfUpdate DESC';

        // Get feeds with language names and article counts
        $sql = "SELECT nf.*, lg.LgName,
                       (SELECT COUNT(*) FROM feed_links WHERE FlNfID = NfID) AS articleCount
                FROM news_feeds nf
                LEFT JOIN languages lg ON lg.LgID = nf.NfLgID
                WHERE $where
                ORDER BY $orderBy
                LIMIT ?, ?";

        // Add pagination parameters
        $queryParams[] = $offset;
        $queryParams[] = $perPage;

        $feeds = [];
        $rows = Connection::preparedFetchAll($sql, $queryParams);
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
        $options = $this->feedFacade->getNfOption((string)$row['NfOptions'], 'all');
        $updateTimestamp = (int)$row['NfUpdate'];
        $lastUpdate = $updateTimestamp > 0
            ? $this->feedFacade->formatLastUpdate(time() - $updateTimestamp)
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
        $feed = $this->feedFacade->getFeedById($feedId);
        if ($feed === null) {
            return ['error' => 'Feed not found'];
        }

        $feed['LgName'] = '';
        $feed['articleCount'] = 0;

        // Get language name
        $langResult = QueryBuilder::table('languages')
            ->select(['LgName'])
            ->where('LgID', '=', $feed['NfLgID'])
            ->firstPrepared();
        if ($langResult !== null) {
            $feed['LgName'] = (string)$langResult['LgName'];
        }

        // Get article count
        $countResult = QueryBuilder::table('feed_links')
            ->select(['COUNT(*) AS cnt'])
            ->where('FlNfID', '=', $feedId)
            ->firstPrepared();
        if ($countResult !== null) {
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
        $name = trim((string)($data['name'] ?? ''));
        $sourceUri = trim((string)($data['sourceUri'] ?? ''));

        if ($langId <= 0) {
            return ['success' => false, 'error' => 'Language is required'];
        }
        if (empty($name)) {
            return ['success' => false, 'error' => 'Feed name is required'];
        }
        if (empty($sourceUri)) {
            return ['success' => false, 'error' => 'Source URI is required'];
        }

        $feedId = $this->feedFacade->createFeed([
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
        $existing = $this->feedFacade->getFeedById($feedId);
        if ($existing === null) {
            return ['success' => false, 'error' => 'Feed not found'];
        }

        $this->feedFacade->updateFeed($feedId, [
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
        $result = $this->feedFacade->deleteFeeds($ids);

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
        $query = (string)($params['query'] ?? '');
        $sort = max(1, min(3, (int)($params['sort'] ?? 1)));

        // Get feed info
        $feed = $this->feedFacade->getFeedById($feedId);
        if ($feed === null) {
            return ['error' => 'Feed not found'];
        }

        // Build WHERE clause with parameters
        $whereConditions = ["FlNfID = ?"];
        $queryParams = [$feedId];

        if (is_string($query) && $query !== '') {
            $pattern = '%' . str_replace('*', '%', $query) . '%';
            $whereConditions[] = "(FlTitle LIKE ? OR FlDescription LIKE ?)";
            $queryParams[] = $pattern;
            $queryParams[] = $pattern;
        }

        $where = implode(' AND ', $whereConditions);

        // Count total using raw SQL with fixed table name
        $total = (int)Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM feed_links WHERE $where",
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

        // Get articles with import status (archived texts are in texts table with TxArchivedAt)
        $sql = "SELECT fl.*, tx.TxID, tx.TxArchivedAt
                FROM feed_links fl
                LEFT JOIN texts tx ON tx.TxSourceURI = TRIM(fl.FlLink)
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
                'name' => $feed['NfName'],
                'langId' => $feed['NfLgID']
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
        $textId = isset($row['TxID']) && $row['TxID'] !== null && $row['TxID'] !== ''
            ? (int)$row['TxID'] : null;
        $isArchived = $textId !== null && !empty($row['TxArchivedAt']);

        $status = 'new';
        if ($textId !== null && !$isArchived) {
            $status = 'imported';
        } elseif ($isArchived) {
            $status = 'archived';
        } elseif (str_starts_with((string)$row['FlLink'], ' ')) {
            $status = 'error';
        }

        // For archived texts, report the same TxID as archivedTextId
        $archivedTextId = $isArchived ? $textId : null;
        $activeTextId = ($textId !== null && !$isArchived) ? $textId : null;

        return [
            'id' => (int)$row['FlID'],
            'title' => (string)$row['FlTitle'],
            'link' => trim((string)$row['FlLink']),
            'description' => (string)$row['FlDescription'],
            'date' => (string)$row['FlDate'],
            'audio' => (string)$row['FlAudio'],
            'hasText' => !empty($row['FlText']),
            'status' => $status,
            'textId' => $activeTextId,
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
            $deleted = $this->feedFacade->deleteArticles((string)$feedId);
        } else {
            // Delete specific articles
            $ids = array_map('intval', $articleIds);
            $deleted = QueryBuilder::table('feed_links')
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
        if (!is_array($articleIds) || count($articleIds) === 0) {
            return ['success' => false, 'imported' => 0, 'errors' => ['No articles selected']];
        }

        $ids = implode(',', array_map('intval', $articleIds));
        $feedLinks = $this->feedFacade->getMarkedFeedLinks($ids);

        $imported = 0;
        $errors = [];

        foreach ($feedLinks as $row) {
            /** @var array<string, mixed> $row */
            $nfOptions = (string)($row['NfOptions'] ?? '');
            $nfName = (string)($row['NfName'] ?? '');

            $tagNameRaw = $this->feedFacade->getNfOption($nfOptions, 'tag');
            $tagName = is_string($tagNameRaw) && $tagNameRaw !== '' ? $tagNameRaw : mb_substr($nfName, 0, 20, 'utf-8');

            $maxTextsRaw = $this->feedFacade->getNfOption($nfOptions, 'max_texts');
            $maxTexts = is_string($maxTextsRaw) ? (int)$maxTextsRaw : 0;
            if (!$maxTexts) {
                $maxTexts = (int)Settings::getWithDefault('set-max-texts-per-feed');
            }

            $flLink = (string)($row['FlLink'] ?? '');
            $flId = (string)($row['FlID'] ?? '');
            $doc = [[
                'link' => empty($flLink) ? ('#' . $flId) : $flLink,
                'title' => (string)($row['FlTitle'] ?? ''),
                'audio' => (string)($row['FlAudio'] ?? ''),
                'text' => (string)($row['FlText'] ?? '')
            ]];

            $charsetRaw = $this->feedFacade->getNfOption($nfOptions, 'charset');
            $charset = is_string($charsetRaw) ? $charsetRaw : null;
            $texts = $this->feedFacade->extractTextFromArticle(
                $doc,
                (string)($row['NfArticleSectionTags'] ?? ''),
                (string)($row['NfFilterTags'] ?? ''),
                $charset
            );

            if (isset($texts['error'])) {
                /** @var array{message?: string, link?: string[]} $errorData */
                $errorData = $texts['error'];
                $errors[] = $errorData['message'] ?? 'Unknown error';
                foreach ($errorData['link'] ?? [] as $errLink) {
                    $this->feedFacade->markLinkAsError($errLink);
                }
                unset($texts['error']);
            }

            if (is_array($texts)) {
                foreach ($texts as $text) {
                    /** @var array{TxTitle?: mixed, TxText?: mixed, TxAudioURI?: mixed, TxSourceURI?: mixed} $text */
                    $this->feedFacade->createTextFromFeed([
                        'TxLgID' => (int)($row['NfLgID'] ?? 0),
                        'TxTitle' => (string)($text['TxTitle'] ?? ''),
                        'TxText' => (string)($text['TxText'] ?? ''),
                        'TxAudioURI' => (string)($text['TxAudioURI'] ?? ''),
                        'TxSourceURI' => (string)($text['TxSourceURI'] ?? '')
                    ], $tagName);
                    $imported++;
                }
            }

            $this->feedFacade->archiveOldTexts($tagName, $maxTexts);
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
        $reset = $this->feedFacade->resetUnloadableArticles((string)$feedId);
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
     *
     * @param array $params Filter parameters
     *
     * @return array Feed list with pagination
     */
    public function formatGetFeedList(array $params): array
    {
        return $this->getFeedList($params);
    }

    /**
     * Format response for getting single feed.
     *
     * @param int $feedId Feed ID
     *
     * @return array Feed data
     */
    public function formatGetFeed(int $feedId): array
    {
        return $this->getFeed($feedId);
    }

    /**
     * Format response for creating feed.
     *
     * @param array $data Feed data
     *
     * @return array Creation result
     */
    public function formatCreateFeed(array $data): array
    {
        return $this->createFeed($data);
    }

    /**
     * Format response for updating feed.
     *
     * @param int   $feedId Feed ID
     * @param array $data   Feed data
     *
     * @return array Update result
     */
    public function formatUpdateFeed(int $feedId, array $data): array
    {
        return $this->updateFeed($feedId, $data);
    }

    /**
     * Format response for deleting feeds.
     *
     * @param array $feedIds Feed IDs
     *
     * @return array Deletion result
     */
    public function formatDeleteFeeds(array $feedIds): array
    {
        return $this->deleteFeeds($feedIds);
    }

    /**
     * Format response for getting articles.
     *
     * @param array $params Filter parameters
     *
     * @return array Articles with pagination
     */
    public function formatGetArticles(array $params): array
    {
        return $this->getArticles($params);
    }

    /**
     * Format response for deleting articles.
     *
     * @param int   $feedId     Feed ID
     * @param array $articleIds Article IDs (empty = all)
     *
     * @return array Deletion result
     */
    public function formatDeleteArticles(int $feedId, array $articleIds = []): array
    {
        return $this->deleteArticles($feedId, $articleIds);
    }

    /**
     * Format response for importing articles.
     *
     * @param array $data Import data
     *
     * @return array Import result
     */
    public function formatImportArticles(array $data): array
    {
        return $this->importArticles($data);
    }

    /**
     * Format response for resetting error articles.
     *
     * @param int $feedId Feed ID
     *
     * @return array Reset result
     */
    public function formatResetErrorArticles(int $feedId): array
    {
        return $this->resetErrorArticles($feedId);
    }

    // =========================================================================
    // Additional Module-specific Methods
    // =========================================================================

    /**
     * Parse an RSS feed for preview.
     *
     * @param string $sourceUri      Feed URL
     * @param string $articleSection Article section tag
     *
     * @return array|null Feed data or null on error
     */
    public function parseFeed(string $sourceUri, string $articleSection = ''): ?array
    {
        $result = $this->feedFacade->parseRssFeed($sourceUri, $articleSection);
        return $result !== false ? $result : null;
    }

    /**
     * Detect feed format and parse.
     *
     * @param string $sourceUri Feed URL
     *
     * @return array|null Feed data with metadata or null on error
     */
    public function detectFeed(string $sourceUri): ?array
    {
        $result = $this->feedFacade->detectAndParseFeed($sourceUri);
        return $result !== false ? $result : null;
    }

    /**
     * Get list of feeds (simple version).
     *
     * @param int|null $languageId Language ID filter (null for all)
     *
     * @return array Array of feeds
     */
    public function getFeeds(?int $languageId = null): array
    {
        return $this->feedFacade->getFeeds($languageId);
    }

    /**
     * Get feeds needing auto-update.
     *
     * @return array Array of feeds
     */
    public function getFeedsNeedingAutoUpdate(): array
    {
        return $this->feedFacade->getFeedsNeedingAutoUpdate();
    }

    /**
     * Get feed load configuration for frontend.
     *
     * @param int  $feedId         Feed ID
     * @param bool $checkAutoupdate Check auto-update feeds
     *
     * @return array Configuration
     */
    public function getFeedLoadConfig(int $feedId, bool $checkAutoupdate = false): array
    {
        return $this->feedFacade->getFeedLoadConfig($feedId, $checkAutoupdate);
    }

    // =========================================================================
    // API Routing Methods
    // =========================================================================

    public function routeGet(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        if ($frag1 === 'list') {
            return Response::success($this->formatGetFeedList($params));
        }
        if ($frag1 === 'articles') {
            return Response::success($this->formatGetArticles($params));
        }
        if ($frag1 !== '' && ctype_digit($frag1)) {
            return Response::success($this->formatGetFeed((int) $frag1));
        }

        return Response::error('Expected "list", "articles", or feed ID', 404);
    }

    public function routePost(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        if ($frag1 === 'articles' && $frag2 === 'import') {
            return Response::success($this->formatImportArticles($params));
        }
        if ($frag1 === '') {
            return Response::success($this->formatCreateFeed($params));
        }
        if (ctype_digit($frag1) && $frag2 === 'load') {
            return Response::success($this->formatLoadFeed(
                (string) ($params['name'] ?? ''),
                (int) $frag1,
                (string) ($params['source_uri'] ?? ''),
                (string) ($params['options'] ?? '')
            ));
        }

        return Response::error('Expected "articles/import", feed data, or "{id}/load"', 404);
    }

    public function routePut(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        if ($frag1 === '' || !ctype_digit($frag1)) {
            return Response::error('Feed ID (Integer) Expected', 404);
        }

        $feedId = (int) $frag1;
        return Response::success($this->formatUpdateFeed($feedId, $params));
    }

    public function routeDelete(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        if ($frag1 === 'articles' && $frag2 !== '' && ctype_digit($frag2)) {
            $feedId = (int) $frag2;
            /** @var array<int> $articleIds */
            $articleIds = is_array($params['article_ids'] ?? null) ? $params['article_ids'] : [];
            return Response::success($this->formatDeleteArticles($feedId, $articleIds));
        }
        if ($frag1 !== '' && ctype_digit($frag1) && $frag2 === 'reset-errors') {
            return Response::success($this->formatResetErrorArticles((int) $frag1));
        }
        if ($frag1 === '') {
            /** @var array<int> $feedIds */
            $feedIds = is_array($params['feed_ids'] ?? null) ? $params['feed_ids'] : [];
            return Response::success($this->formatDeleteFeeds($feedIds));
        }
        if (ctype_digit($frag1)) {
            return Response::success($this->formatDeleteFeeds([(int) $frag1]));
        }

        return Response::error('Expected feed ID or "articles/{feedId}"', 404);
    }
}

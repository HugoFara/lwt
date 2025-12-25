<?php declare(strict_types=1);
/**
 * Feed API Handler
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

namespace Lwt\Modules\Feed\Http;

use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Modules\Feed\Application\FeedFacade;

/**
 * API handler for feed-related operations.
 *
 * Provides REST API endpoints for feed management.
 *
 * @since 3.0.0
 */
class FeedApiHandler
{
    /**
     * Constructor.
     *
     * @param FeedFacade $feedFacade Feed facade
     */
    public function __construct(
        private FeedFacade $feedFacade
    ) {
    }

    /**
     * Load a feed and return result.
     *
     * @param int    $feedId    Feed ID
     * @param string $feedName  Feed name (for message)
     * @param string $sourceUri Feed source URI
     * @param string $options   Feed options string
     *
     * @return array API response
     */
    public function loadFeed(int $feedId, string $feedName, string $sourceUri, string $options): array
    {
        $articleSource = $this->feedFacade->getNfOption($options, 'article_source') ?? '';
        $feed = $this->feedFacade->parseRssFeed($sourceUri, $articleSource);

        if (empty($feed)) {
            return [
                'error' => 'Could not load "' . $feedName . '"',
            ];
        }

        // Use the LoadFeed use case
        $result = $this->feedFacade->loadFeed($feedId);

        if (!$result['success']) {
            return [
                'error' => $result['error'] ?? 'Unknown error',
            ];
        }

        // Build result message
        $msg = $this->buildResultMessage(
            $feedName,
            $result['inserted'],
            $result['duplicates']
        );

        return [
            'success' => true,
            'message' => $msg,
            'imported' => $result['inserted'],
            'duplicates' => $result['duplicates'],
        ];
    }

    /**
     * Build result message for feed load.
     *
     * @param string $feedName   Feed name
     * @param int    $imported   Number imported
     * @param int    $duplicates Number of duplicates
     *
     * @return string Result message
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    private function buildResultMessage(
        string $feedName,
        int $imported,
        int $duplicates
    ): string {
        $msg = $feedName . ': ';

        if (!$imported) {
            $msg .= 'no';
        } else {
            $msg .= $imported;
        }

        $msg .= ' new article';
        if ($imported > 1) {
            $msg .= 's';
        }
        $msg .= ' imported';

        if ($duplicates > 1) {
            $msg .= ", $duplicates articles are duplicates";
        } elseif ($duplicates === 1) {
            $msg .= ", $duplicates duplicated article";
        }

        return $msg;
    }

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
     * Get list of feeds.
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
     * Get a single feed.
     *
     * @param int $feedId Feed ID
     *
     * @return array|null Feed data or null
     */
    public function getFeed(int $feedId): ?array
    {
        return $this->feedFacade->getFeedById($feedId);
    }

    /**
     * Create a new feed.
     *
     * @param array $data Feed data
     *
     * @return array Result with new feed ID
     */
    public function createFeed(array $data): array
    {
        try {
            $id = $this->feedFacade->createFeed($data);
            return [
                'success' => true,
                'id' => $id,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update a feed.
     *
     * @param int   $feedId Feed ID
     * @param array $data   Feed data
     *
     * @return array Result
     */
    public function updateFeed(int $feedId, array $data): array
    {
        try {
            $this->feedFacade->updateFeed($feedId, $data);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Delete feeds.
     *
     * @param int[] $feedIds Feed IDs
     *
     * @return array Result with counts
     */
    public function deleteFeeds(array $feedIds): array
    {
        $ids = implode(',', array_map('intval', $feedIds));
        $result = $this->feedFacade->deleteFeeds($ids);

        return [
            'success' => true,
            'deleted_feeds' => $result['feeds'],
            'deleted_articles' => $result['articles'],
        ];
    }

    /**
     * Get articles for feeds.
     *
     * @param int[]  $feedIds   Feed IDs
     * @param int    $offset    Pagination offset
     * @param int    $limit     Page size
     * @param string $orderBy   Sort column
     * @param string $direction Sort direction
     * @param string $search    Search query
     *
     * @return array Articles with pagination info
     */
    public function getArticles(
        array $feedIds,
        int $offset = 0,
        int $limit = 50,
        string $orderBy = 'FlDate',
        string $direction = 'DESC',
        string $search = ''
    ): array {
        $ids = implode(',', array_map('intval', $feedIds));
        $orderClause = "$orderBy $direction";

        $whereQuery = '';
        if ($search !== '') {
            $whereQuery = " AND (FlTitle LIKE '%$search%' OR FlDescription LIKE '%$search%')";
        }

        return [
            'articles' => $this->feedFacade->getFeedLinks($ids, $whereQuery, $orderClause, $offset, $limit),
            'total' => $this->feedFacade->countFeedLinks($ids, $whereQuery),
        ];
    }

    /**
     * Import articles as texts.
     *
     * @param int[] $articleIds Article IDs to import
     *
     * @return array Import result
     */
    public function importArticles(array $articleIds): array
    {
        return $this->feedFacade->importArticles($articleIds);
    }

    /**
     * Delete articles.
     *
     * @param int[] $feedIds Feed IDs to delete articles for
     *
     * @return array Result
     */
    public function deleteArticles(array $feedIds): array
    {
        $ids = implode(',', array_map('intval', $feedIds));
        $deleted = $this->feedFacade->deleteArticles($ids);

        return [
            'success' => true,
            'deleted' => $deleted,
        ];
    }

    /**
     * Reset error articles.
     *
     * @param int[] $feedIds Feed IDs
     *
     * @return array Result
     */
    public function resetErrorArticles(array $feedIds): array
    {
        $ids = implode(',', array_map('intval', $feedIds));
        $reset = $this->feedFacade->resetUnloadableArticles($ids);

        return [
            'success' => true,
            'reset' => $reset,
        ];
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
}

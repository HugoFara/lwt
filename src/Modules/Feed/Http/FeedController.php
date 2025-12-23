<?php declare(strict_types=1);
/**
 * Feed Controller
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

use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Modules\Feed\Domain\Feed;

/**
 * Controller for feed management operations.
 *
 * Provides methods for rendering feed-related views and
 * handling feed CRUD operations.
 *
 * @since 3.0.0
 */
class FeedController
{
    /**
     * View base path.
     */
    private string $viewPath;

    /**
     * Constructor.
     *
     * @param FeedFacade $feedFacade Feed facade
     */
    public function __construct(
        private FeedFacade $feedFacade
    ) {
        $this->viewPath = __DIR__ . '/../Views/';
    }

    /**
     * Get the FeedFacade instance.
     *
     * @return FeedFacade
     */
    public function getFacade(): FeedFacade
    {
        return $this->feedFacade;
    }

    /**
     * Set custom view path.
     *
     * @param string $path View path
     *
     * @return void
     */
    public function setViewPath(string $path): void
    {
        $this->viewPath = rtrim($path, '/') . '/';
    }

    // =========================================================================
    // Feed Operations
    // =========================================================================

    /**
     * Get all feeds with optional language filter.
     *
     * @param int|null $languageId Language ID filter
     *
     * @return array Array of feed data
     */
    public function getFeeds(?int $languageId = null): array
    {
        return $this->feedFacade->getFeeds($languageId);
    }

    /**
     * Get a single feed by ID.
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
     * @return int New feed ID
     */
    public function createFeed(array $data): int
    {
        return $this->feedFacade->createFeed($data);
    }

    /**
     * Update a feed.
     *
     * @param int   $feedId Feed ID
     * @param array $data   Feed data
     *
     * @return void
     */
    public function updateFeed(int $feedId, array $data): void
    {
        $this->feedFacade->updateFeed($feedId, $data);
    }

    /**
     * Delete feeds.
     *
     * @param string $feedIds Comma-separated feed IDs
     *
     * @return array{feeds: int, articles: int}
     */
    public function deleteFeeds(string $feedIds): array
    {
        return $this->feedFacade->deleteFeeds($feedIds);
    }

    // =========================================================================
    // Article Operations
    // =========================================================================

    /**
     * Get feed articles with pagination.
     *
     * @param string $feedIds    Comma-separated feed IDs
     * @param string $whereQuery Additional WHERE clause
     * @param string $orderBy    ORDER BY clause
     * @param int    $offset     Pagination offset
     * @param int    $limit      Page size
     *
     * @return array Array of articles
     */
    public function getFeedLinks(
        string $feedIds,
        string $whereQuery = '',
        string $orderBy = 'FlDate DESC',
        int $offset = 0,
        int $limit = 50
    ): array {
        return $this->feedFacade->getFeedLinks($feedIds, $whereQuery, $orderBy, $offset, $limit);
    }

    /**
     * Count feed articles.
     *
     * @param string $feedIds    Comma-separated feed IDs
     * @param string $whereQuery Additional WHERE clause
     *
     * @return int Article count
     */
    public function countFeedLinks(string $feedIds, string $whereQuery = ''): int
    {
        return $this->feedFacade->countFeedLinks($feedIds, $whereQuery);
    }

    /**
     * Delete articles for feeds.
     *
     * @param string $feedIds Comma-separated feed IDs
     *
     * @return int Number deleted
     */
    public function deleteArticles(string $feedIds): int
    {
        return $this->feedFacade->deleteArticles($feedIds);
    }

    /**
     * Reset error articles.
     *
     * @param string $feedIds Comma-separated feed IDs
     *
     * @return int Number reset
     */
    public function resetUnloadableArticles(string $feedIds): int
    {
        return $this->feedFacade->resetUnloadableArticles($feedIds);
    }

    /**
     * Get marked feed links for processing.
     *
     * @param array|string $markedItems Marked item IDs
     *
     * @return array Array of feed links with feed data
     */
    public function getMarkedFeedLinks($markedItems): array
    {
        return $this->feedFacade->getMarkedFeedLinks($markedItems);
    }

    // =========================================================================
    // RSS Operations
    // =========================================================================

    /**
     * Parse RSS feed.
     *
     * @param string $sourceUri      Feed URL
     * @param string $articleSection Article section tag
     *
     * @return array|false Parsed items or false
     */
    public function parseRssFeed(string $sourceUri, string $articleSection): array|false
    {
        return $this->feedFacade->parseRssFeed($sourceUri, $articleSection);
    }

    /**
     * Detect and parse feed.
     *
     * @param string $sourceUri Feed URL
     *
     * @return array|false Feed data or false
     */
    public function detectAndParseFeed(string $sourceUri): array|false
    {
        return $this->feedFacade->detectAndParseFeed($sourceUri);
    }

    /**
     * Extract text from article.
     *
     * @param array       $feedData       Feed items
     * @param string      $articleSection XPath selectors
     * @param string      $filterTags     Filter selectors
     * @param string|null $charset        Override charset
     *
     * @return array|string|null Extracted data
     */
    public function extractTextFromArticle(
        array $feedData,
        string $articleSection,
        string $filterTags,
        ?string $charset = null
    ): array|string|null {
        return $this->feedFacade->extractTextFromArticle($feedData, $articleSection, $filterTags, $charset);
    }

    /**
     * Load/refresh a feed.
     *
     * @param int $feedId Feed ID
     *
     * @return array Load result
     */
    public function loadFeed(int $feedId): array
    {
        return $this->feedFacade->loadFeed($feedId);
    }

    /**
     * Get feeds needing auto-update.
     *
     * @return array Feeds array
     */
    public function getFeedsNeedingAutoUpdate(): array
    {
        return $this->feedFacade->getFeedsNeedingAutoUpdate();
    }

    // =========================================================================
    // Text Creation
    // =========================================================================

    /**
     * Create text from feed article.
     *
     * @param array  $textData Text data
     * @param string $tagName  Tag name
     *
     * @return int New text ID
     */
    public function createTextFromFeed(array $textData, string $tagName): int
    {
        return $this->feedFacade->createTextFromFeed($textData, $tagName);
    }

    /**
     * Archive old texts.
     *
     * @param string $tagName  Tag name
     * @param int    $maxTexts Max texts to keep
     *
     * @return array Archive stats
     */
    public function archiveOldTexts(string $tagName, int $maxTexts): array
    {
        return $this->feedFacade->archiveOldTexts($tagName, $maxTexts);
    }

    /**
     * Mark article link as error.
     *
     * @param string $link Article link
     *
     * @return void
     */
    public function markLinkAsError(string $link): void
    {
        $this->feedFacade->markLinkAsError($link);
    }

    // =========================================================================
    // Utility Methods
    // =========================================================================

    /**
     * Get feed option.
     *
     * @param string $optionsStr Options string
     * @param string $option     Option name
     *
     * @return string|array|null Option value
     */
    public function getNfOption(string $optionsStr, string $option): string|array|null
    {
        return $this->feedFacade->getNfOption($optionsStr, $option);
    }

    /**
     * Parse auto-update interval.
     *
     * @param string $autoupdate Interval string
     *
     * @return int|null Seconds or null
     */
    public function parseAutoUpdateInterval(string $autoupdate): ?int
    {
        return $this->feedFacade->parseAutoUpdateInterval($autoupdate);
    }

    /**
     * Format last update time.
     *
     * @param int $diff Time difference in seconds
     *
     * @return string Formatted string
     */
    public function formatLastUpdate(int $diff): string
    {
        return $this->feedFacade->formatLastUpdate($diff);
    }

    /**
     * Get sort options.
     *
     * @return array Sort options
     */
    public function getSortOptions(): array
    {
        return $this->feedFacade->getSortOptions();
    }

    /**
     * Get sort column.
     *
     * @param int    $sortIndex Sort index
     * @param string $prefix    Column prefix
     *
     * @return string Sort column
     */
    public function getSortColumn(int $sortIndex, string $prefix = 'Fl'): string
    {
        return $this->feedFacade->getSortColumn($sortIndex, $prefix);
    }

    /**
     * Build query filter.
     *
     * @param string $query     Search query
     * @param string $queryMode Query mode
     * @param string $regexMode Regex mode
     *
     * @return string SQL WHERE clause
     */
    public function buildQueryFilter(string $query, string $queryMode, string $regexMode): string
    {
        return $this->feedFacade->buildQueryFilter($query, $queryMode, $regexMode);
    }

    /**
     * Validate regex pattern.
     *
     * @param string $pattern Pattern
     *
     * @return bool True if valid
     */
    public function validateRegexPattern(string $pattern): bool
    {
        return $this->feedFacade->validateRegexPattern($pattern);
    }

    /**
     * Get feed load configuration.
     *
     * @param int  $currentFeed     Feed ID
     * @param bool $checkAutoupdate Check auto-update
     *
     * @return array Configuration
     */
    public function getFeedLoadConfig(int $currentFeed, bool $checkAutoupdate): array
    {
        return $this->feedFacade->getFeedLoadConfig($currentFeed, $checkAutoupdate);
    }

    /**
     * Count feeds.
     *
     * @param int|null    $langId       Language ID
     * @param string|null $queryPattern Query pattern
     *
     * @return int Feed count
     */
    public function countFeeds(?int $langId = null, ?string $queryPattern = null): int
    {
        return $this->feedFacade->countFeeds($langId, $queryPattern);
    }

    // =========================================================================
    // View Rendering
    // =========================================================================

    /**
     * Render a view.
     *
     * @param string $view View name (without .php)
     * @param array  $data View data
     *
     * @return void
     */
    public function render(string $view, array $data = []): void
    {
        $viewFile = $this->viewPath . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $view");
        }

        extract($data);
        require $viewFile;
    }

    /**
     * Render feed load interface.
     *
     * @param int    $currentFeed     Feed ID
     * @param bool   $checkAutoupdate Check auto-update
     * @param string $redirectUrl     Redirect URL
     *
     * @return void
     */
    public function renderFeedLoadInterface(
        int $currentFeed,
        bool $checkAutoupdate,
        string $redirectUrl
    ): void {
        $config = $this->getFeedLoadConfig($currentFeed, $checkAutoupdate);

        // Output JSON config for Alpine component
        echo '<script type="application/json" id="feed-loader-config">';
        echo json_encode([
            'feeds' => $config['feeds'],
            'redirectUrl' => $redirectUrl,
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        echo '</script>';

        // Alpine.js component wrapper
        echo '<div x-data="feedLoader()">';

        if ($config['count'] !== 1) {
            echo '<div class="msgblue"><p>UPDATING <span x-text="loadedCount">0</span>/' .
                $config['count'] . ' FEEDS</p></div>';
        }

        echo '<template x-for="feed in feeds" :key="feed.id">';
        echo '<div :class="getStatusClass(feed.id)"><p x-text="feedMessages[feed.id]"></p></div>';
        echo '</template>';

        echo '<div class="center"><button @click="handleContinue()">Continue</button></div>';
        echo '</div>';
    }
}

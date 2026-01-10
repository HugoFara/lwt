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
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\Validation;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

require_once __DIR__ . '/../../../backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../Shared/UI/Helpers/PageLayoutHelper.php';

/**
 * Controller for feed management operations.
 *
 * This controller handles all feed routes with native implementations
 * using module services and facades. The migration from the legacy
 * FeedsController is complete.
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
     * Feed facade.
     */
    private FeedFacade $feedFacade;

    /**
     * Language facade.
     */
    private LanguageFacade $languageFacade;

    /**
     * Constructor.
     *
     * @param FeedFacade     $feedFacade     Feed facade
     * @param LanguageFacade $languageFacade Language facade
     */
    public function __construct(
        FeedFacade $feedFacade,
        LanguageFacade $languageFacade
    ) {
        $this->viewPath = __DIR__ . '/../Views/';
        $this->feedFacade = $feedFacade;
        $this->languageFacade = $languageFacade;
    }

    /**
     * Get the wizard session data as typed array.
     *
     * @return array<string, mixed>
     */
    private function getWizardSession(): array
    {
        if (!isset($_SESSION['wizard']) || !is_array($_SESSION['wizard'])) {
            $_SESSION['wizard'] = [];
        }
        /** @var array<string, mixed> */
        return $_SESSION['wizard'];
    }

    /**
     * Get the wizard feed data as typed array.
     *
     * @return array<int|string, mixed>
     */
    private function getWizardFeed(): array
    {
        $wizard = $this->getWizardSession();
        if (!isset($wizard['feed']) || !is_array($wizard['feed'])) {
            return [];
        }
        return $wizard['feed'];
    }

    /**
     * Get a string value from wizard session.
     *
     * @param string $key     Key to retrieve
     * @param string $default Default value
     *
     * @return string
     */
    private function getWizardString(string $key, string $default = ''): string
    {
        $wizard = $this->getWizardSession();
        if (!isset($wizard[$key])) {
            return $default;
        }
        return is_string($wizard[$key]) ? $wizard[$key] : $default;
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
     * @param string $feedIds Comma-separated feed IDs
     * @param string $search  Search term (optional)
     * @param string $orderBy ORDER BY clause
     * @param int    $offset  Pagination offset
     * @param int    $limit   Page size
     *
     * @return array Array of articles
     */
    public function getFeedLinks(
        string $feedIds,
        string $search = '',
        string $orderBy = 'FlDate DESC',
        int $offset = 0,
        int $limit = 50
    ): array {
        return $this->feedFacade->getFeedLinks($feedIds, $search, $orderBy, $offset, $limit);
    }

    /**
     * Count feed articles.
     *
     * @param string $feedIds Comma-separated feed IDs
     * @param string $search  Search term (optional)
     *
     * @return int Article count
     */
    public function countFeedLinks(string $feedIds, string $search = ''): int
    {
        return $this->feedFacade->countFeedLinks($feedIds, $search);
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
        /** @var array<int|string, array{link: string, title: string, audio?: string, text?: string}> $feedData */
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
     * @return array{clause: string, search: string, mode: string, regex: string} Filter data
     */
    public function buildQueryFilter(string $query, string $queryMode, string $regexMode): array
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
        /** @var array{feeds: array, count: int} $config */
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

    // =========================================================================
    // Route Handlers
    // =========================================================================

    /**
     * Feeds index page.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        session_start();

        $currentLang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );
        PageLayoutHelper::renderPageStart($this->languageFacade->getLanguageName($currentLang) . ' Feeds', true);

        $currentFeed = InputValidator::getStringWithSession(
            "selected_feed",
            "currentrssfeed"
        );

        $editText = 0;
        $message = '';

        // Handle marked items submission
        $markedItemsArray = InputValidator::getArray('marked_items');
        if (!empty($markedItemsArray)) {
            $result = $this->processMarkedItems();
            $editText = $result['editText'];
            $message = $result['message'];
        }

        // Display messages
        $this->displayFeedMessages($message);

        // Route based on action
        $markAction = InputValidator::getString('markaction');
        if (
            InputValidator::has('load_feed') || InputValidator::has('check_autoupdate')
            || ($markAction == 'update')
        ) {
            $this->feedFacade->renderFeedLoadInterfaceModern(
                (int)$currentFeed,
                InputValidator::has('check_autoupdate'),
                $_SERVER['PHP_SELF'] ?? '/'
            );
        } elseif (empty($editText)) {
            $this->renderFeedsIndex((int)$currentLang, (int)$currentFeed);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Process marked feed items and create texts from them.
     *
     * @return array{editText: int, message: string}
     */
    private function processMarkedItems(): array
    {
        $editText = 0;
        $message = '';

        $markedItemsArray = InputValidator::getArray('marked_items');
        if (empty($markedItemsArray)) {
            return ['editText' => $editText, 'message' => $message];
        }

        $markedItems = implode(',', array_filter($markedItemsArray, 'is_scalar'));
        $feedLinks = $this->feedFacade->getMarkedFeedLinks($markedItems);

        $stats = ['archived' => 0, 'sentences' => 0, 'textitems' => 0];
        $count = 0;
        $languages = null;

        foreach ($feedLinks as $row) {
            $requiresEdit = $this->feedFacade->getNfOption($row['NfOptions'], 'edit_text') == 1;

            if ($requiresEdit) {
                if ($editText == 1) {
                    $count++;
                } else {
                    echo '<form class="validate" action="/feeds" method="post">';
                    echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField();
                    $editText = 1;
                    $languages = $this->feedFacade->getLanguages();
                }
            }

            $doc = [[
                'link' => $row['FlLink'] === '' ? ('#' . ($row['FlID'] ?? 0)) : $row['FlLink'],
                'title' => $row['FlTitle'],
                'audio' => $row['FlAudio'],
                'text' => $row['FlText']
            ]];

            $nfName = $row['NfName'];
            $nfId = $row['NfID'];
            $nfOptions = $row['NfOptions'];

            $tagNameRaw = $this->feedFacade->getNfOption($nfOptions, 'tag');
            $tagName = is_string($tagNameRaw) && $tagNameRaw !== '' ? $tagNameRaw : mb_substr($nfName, 0, 20, "utf-8");

            $maxTextsRaw = $this->feedFacade->getNfOption($nfOptions, 'max_texts');
            $maxTexts = is_string($maxTextsRaw) ? (int)$maxTextsRaw : 0;
            if (!$maxTexts) {
                $maxTexts = (int)Settings::getWithDefault('set-max-texts-per-feed');
            }

            $charsetRaw = $this->feedFacade->getNfOption($nfOptions, 'charset');
            $charset = is_string($charsetRaw) ? $charsetRaw : null;
            $texts = $this->feedFacade->extractTextFromArticle(
                $doc,
                $row['NfArticleSectionTags'],
                $row['NfFilterTags'],
                $charset
            );

            if (isset($texts['error'])) {
                echo (string)$texts['error']['message'];
                /** @var array<string> $errLinks */
                $errLinks = $texts['error']['link'] ?? [];
                foreach ($errLinks as $errLink) {
                    $this->feedFacade->markLinkAsError($errLink);
                }
                unset($texts['error']);
            }

            if ($requiresEdit) {
                // Include edit form view
                $scrdir = $this->languageFacade->getScriptDirectionTag($row['NfLgID']);
                /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
                include $this->viewPath . 'edit_text_form.php';
            } elseif (is_array($texts)) {
                $result = $this->createTextsFromFeed($texts, $row, $tagName, $maxTexts);
                $stats['archived'] += $result['archived'];
                $stats['sentences'] += $result['sentences'];
                $stats['textitems'] += $result['textitems'];
            }
        }

        if ($stats['archived'] > 0) {
            $message = "Texts archived: {$stats['archived']} / Sentences deleted: {$stats['sentences']}" .
                       " / Text items deleted: {$stats['textitems']}";
        }

        if ($editText == 1) {
            /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
            include $this->viewPath . 'edit_text_footer.php';
        }

        return ['editText' => $editText, 'message' => $message];
    }

    /**
     * Create texts from feed data without edit form.
     *
     * @param array<int|string, array<string, mixed>> $texts    Parsed text data
     * @param array<string, mixed>                    $row      Feed data
     * @param string                                  $tagName  Tag name
     * @param int                                     $maxTexts Maximum texts to keep
     *
     * @return array{archived: int, sentences: int, textitems: int}
     */
    private function createTextsFromFeed(array $texts, array $row, string $tagName, int $maxTexts): array
    {
        foreach ($texts as $text) {
            echo '<div class="msgblue">
            <p class="hide_message">+++ "' . htmlspecialchars((string)($text['TxTitle'] ?? ''), ENT_QUOTES, 'UTF-8') . '" added! +++</p>
            </div>';

            $this->feedFacade->createTextFromFeed([
                'TxLgID' => $row['NfLgID'],
                'TxTitle' => $text['TxTitle'],
                'TxText' => $text['TxText'],
                'TxAudioURI' => $text['TxAudioURI'] ?? '',
                'TxSourceURI' => $text['TxSourceURI'] ?? ''
            ], $tagName);
        }

        TagsFacade::getAllTextTags(true);

        return $this->feedFacade->archiveOldTexts($tagName, $maxTexts);
    }

    /**
     * Display errors and messages for feed operations.
     *
     * @param string $message Message to display
     *
     * @return void
     */
    private function displayFeedMessages(string $message): void
    {
        if (InputValidator::has('checked_feeds_save')) {
            /** @var array<int, array{Nf_ID: int|string, TagList: array<string>, Nf_Max_Texts: int|null, TxLgID: int, TxTitle: string, TxText: string, TxAudioURI: string, TxSourceURI: string}> $feedData */
            $feedData = InputValidator::getArray('feed');
            $message = $this->feedFacade->saveTextsFromFeed($feedData);
        }

        if (isset($_SESSION['feed_loaded'])) {
            /** @var array<string> $feedLoaded */
            $feedLoaded = $_SESSION['feed_loaded'];
            foreach ($feedLoaded as $lf) {
                if (substr($lf, 0, 5) == "Error") {
                    echo "\n<div class=\"red\"><p>";
                } else {
                    echo "\n<div class=\"msgblue\"><p class=\"hide_message\">";
                }
                echo "+++ ", $lf, " +++</p></div>";
            }
            unset($_SESSION['feed_loaded']);
        }

        $this->displayMessage($message);
    }

    /**
     * Display a message.
     *
     * @param string $message Message to display
     *
     * @return void
     */
    private function displayMessage(string $message): void
    {
        if ($message !== '') {
            echo '<p id="hide3" class="msgblue">+++ ' . htmlspecialchars($message) . ' +++</p>';
        }
    }

    /**
     * Render the main feeds index page.
     *
     * @param int $currentLang Current language filter
     * @param int $currentFeed Current feed filter
     *
     * @return void
     */
    private function renderFeedsIndex(int $currentLang, int $currentFeed): void
    {
        $currentQuery = InputValidator::getStringWithSession("query", "currentrssquery");
        $currentQueryMode = InputValidator::getStringWithSession("query_mode", "currentrssquerymode", 'title,desc,text');
        $currentRegexMode = Settings::getWithDefault("set-regex-mode");

        $filterData = $this->feedFacade->buildQueryFilter($currentQuery, $currentQueryMode, $currentRegexMode);
        $searchTerm = $filterData['search'];

        if (!empty($currentQuery) && !empty($currentRegexMode)) {
            if (!$this->feedFacade->validateRegexPattern($currentQuery)) {
                $currentQuery = '';
                $searchTerm = '';
                unset($_SESSION['currentwordquery']);
                if (InputValidator::has('query')) {
                    echo '<p id="hide3" class="warning-message">+++ Warning: Invalid Search +++</p>';
                }
            }
        }

        $currentPage = InputValidator::getIntWithSession("page", "currentrsspage", 1);
        $currentSort = InputValidator::getIntWithDb("sort", 'currentrsssort', 2);

        $feeds = $this->feedFacade->getFeeds($currentLang ?: null);

        // Determine current feed
        $feedTime = null;
        if ($currentFeed == 0 || empty($feeds)) {
            if (!empty($feeds)) {
                $currentFeed = (int)$feeds[0]['NfID'];
            }
        } else {
            // Get feed time for the selected feed
            foreach ($feeds as $f) {
                if ((int)$f['NfID'] === $currentFeed) {
                    $feedTime = $f['NfUpdate'];
                    break;
                }
            }
        }

        $feedIds = (string)$currentFeed;
        $recno = $currentFeed ? $this->feedFacade->countFeedLinks($feedIds, $searchTerm) : 0;

        // Pagination
        $maxPerPage = (int)Settings::getWithDefault('set-articles-per-page');
        $pages = $recno == 0 ? 0 : (intval(($recno - 1) / $maxPerPage) + 1);

        if ($currentPage < 1) {
            $currentPage = 1;
        }
        if ($currentPage > $pages) {
            $currentPage = $pages;
        }

        $offset = ($currentPage - 1) * $maxPerPage;
        $sortColumn = $this->feedFacade->getSortColumn($currentSort);

        // Get articles if there are any
        $articles = [];
        if ($recno > 0) {
            $articles = $this->feedFacade->getFeedLinks($feedIds, $searchTerm, $sortColumn, $offset, $maxPerPage);
        }

        // Format last update for view
        $lastUpdateFormatted = null;
        /** @psalm-suppress RedundantCastGivenDocblockType */
        $feedTimeInt = is_numeric($feedTime) ? (int)$feedTime : 0;
        if ($feedTimeInt !== 0) {
            $diff = time() - $feedTimeInt;
            $lastUpdateFormatted = $this->feedFacade->formatLastUpdate($diff);
        }

        // Pass service to view for utility methods
        $feedService = $this->feedFacade;
        $languages = $this->languageFacade->getLanguagesForSelect();

        // Include browse view
        /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
        include $this->viewPath . 'browse.php';
    }

    /**
     * Edit feeds page.
     *
     * Routes based on request parameters:
     * - new_feed=1: Show new feed form
     * - edit_feed=1: Show edit form for feed
     * - multi_load_feed=1: Show multi-load interface
     * - load_feed=1 / check_autoupdate=1 / markaction=update: Load feeds
     * - markaction=del/del_art/res_art: Handle bulk actions
     * - save_feed=1: Create new feed
     * - update_feed=1: Update existing feed
     * - (default): Show feed management list
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function edit(array $params): void
    {
        $currentLang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );
        $currentSort = InputValidator::getIntWithDb("sort", 'currentmanagefeedssort', 2);
        $currentQuery = InputValidator::getStringWithSession("query", "currentmanagefeedsquery");
        $currentPage = InputValidator::getIntWithSession("page", "currentmanagefeedspage", 1);
        $currentFeed = InputValidator::getStringWithSession(
            "selected_feed",
            "currentmanagefeedsfeed"
        );

        // Build query pattern for prepared statement (no SQL escaping needed)
        $queryPattern = ($currentQuery != '') ? ('%' . str_replace("*", "%", $currentQuery) . '%') : null;

        PageLayoutHelper::renderPageStart('Manage ' . $this->languageFacade->getLanguageName($currentLang) . ' Feeds', true);

        // Clear wizard session if exists
        if (isset($_SESSION['wizard'])) {
            unset($_SESSION['wizard']);
        }

        // Handle mark actions (delete, delete articles, reset articles)
        $message = $this->handleMarkAction($currentFeed);
        if (!empty($message)) {
            $this->displayMessage($message);
        }

        // Display session messages from feed loading
        $this->displaySessionMessages();

        // Handle form submissions
        $this->handleUpdateFeed();
        $this->handleSaveFeed();

        // Route to appropriate view
        $markAction = InputValidator::getString('markaction');
        if (
            InputValidator::has('load_feed') || InputValidator::has('check_autoupdate')
            || ($markAction == 'update')
        ) {
            $this->feedFacade->renderFeedLoadInterfaceModern(
                (int)$currentFeed,
                InputValidator::has('check_autoupdate'),
                $_SERVER['PHP_SELF'] ?? '/'
            );
        } elseif (InputValidator::has('new_feed')) {
            $this->showNewForm();
        } elseif (InputValidator::has('edit_feed')) {
            $this->showEditForm((int)$currentFeed);
        } elseif (InputValidator::has('multi_load_feed')) {
            $this->showMultiLoadForm((int)$currentLang);
        } else {
            $this->showList(
                (int)$currentLang,
                $currentQuery,
                $currentPage,
                $currentSort,
                $queryPattern
            );
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle delete/reset actions on feeds.
     *
     * @param string $currentFeed Current selected feed(s)
     *
     * @return string Status message
     */
    private function handleMarkAction(string $currentFeed): string
    {
        $action = InputValidator::getString('markaction');
        if ($action === '' || empty($currentFeed)) {
            return '';
        }

        switch ($action) {
            case 'del':
                $this->feedFacade->deleteFeeds($currentFeed);
                return "Article item(s) deleted / Newsfeed(s) deleted";

            case 'del_art':
                $this->feedFacade->deleteArticles($currentFeed);
                return "Article item(s) deleted";

            case 'res_art':
                $this->feedFacade->resetUnloadableArticles($currentFeed);
                return "Article(s) reset";

            default:
                return '';
        }
    }

    /**
     * Handle update feed form submission.
     *
     * @return void
     */
    private function handleUpdateFeed(): void
    {
        if (!InputValidator::has('update_feed')) {
            return;
        }

        $feedId = InputValidator::getInt('NfID', 0) ?? 0;

        $data = [
            'NfLgID' => InputValidator::getString('NfLgID'),
            'NfName' => InputValidator::getString('NfName'),
            'NfSourceURI' => InputValidator::getString('NfSourceURI'),
            'NfArticleSectionTags' => InputValidator::getString('NfArticleSectionTags'),
            'NfFilterTags' => InputValidator::getString('NfFilterTags'),
            'NfOptions' => rtrim(InputValidator::getString('NfOptions'), ','),
        ];

        $this->feedFacade->updateFeed($feedId, $data);
    }

    /**
     * Handle save new feed form submission.
     *
     * @return void
     */
    private function handleSaveFeed(): void
    {
        if (!InputValidator::has('save_feed')) {
            return;
        }

        $data = [
            'NfLgID' => InputValidator::getString('NfLgID'),
            'NfName' => InputValidator::getString('NfName'),
            'NfSourceURI' => InputValidator::getString('NfSourceURI'),
            'NfArticleSectionTags' => InputValidator::getString('NfArticleSectionTags'),
            'NfFilterTags' => InputValidator::getString('NfFilterTags'),
            'NfOptions' => rtrim(InputValidator::getString('NfOptions'), ','),
        ];

        $this->feedFacade->createFeed($data);
    }

    /**
     * Display session messages for feed loading.
     *
     * @return void
     */
    private function displaySessionMessages(): void
    {
        if (!isset($_SESSION['feed_loaded'])) {
            return;
        }

        /** @var array<string> $feedLoaded */
        $feedLoaded = $_SESSION['feed_loaded'];
        foreach ($feedLoaded as $lf) {
            echo "\n<div class=\"msgblue\"><p class=\"hide_message\">+++ ", $lf, " +++</p></div>";
        }
        unset($_SESSION['feed_loaded']);
    }

    /**
     * Show the new feed form.
     *
     * @return void
     *
     * @psalm-suppress UnresolvableInclude View path is constructed at runtime
     */
    private function showNewForm(): void
    {
        $languages = $this->feedFacade->getLanguages();

        /** @psalm-suppress UnresolvableInclude */
        include $this->viewPath . 'new.php';
    }

    /**
     * Show the edit feed form.
     *
     * @param int $feedId Feed ID to edit
     *
     * @return void
     */
    private function showEditForm(int $feedId): void
    {
        $feed = $this->feedFacade->getFeedById($feedId);

        if ($feed === null) {
            echo '<p class="red">Feed not found.</p>';
            return;
        }

        $languages = $this->feedFacade->getLanguages();

        // Parse options
        $options = $this->feedFacade->getNfOption($feed['NfOptions'], '');
        if (!is_array($options)) {
            $options = [];
        }

        // Parse auto-update interval
        $autoUpdateRaw = $this->feedFacade->getNfOption($feed['NfOptions'], 'autoupdate');
        if ($autoUpdateRaw === null || !is_string($autoUpdateRaw)) {
            $autoUpdateInterval = null;
            $autoUpdateUnit = null;
        } else {
            $autoUpdateUnit = substr($autoUpdateRaw, -1);
            $autoUpdateInterval = substr($autoUpdateRaw, 0, -1);
        }

        /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
        include $this->viewPath . 'edit.php';
    }

    /**
     * Show the multi-load feed form.
     *
     * @param int $currentLang Current language filter
     *
     * @return void
     */
    private function showMultiLoadForm(int $currentLang): void
    {
        $feeds = $this->feedFacade->getFeeds($currentLang ?: null);

        // Pass service to view for utility methods
        $feedService = $this->feedFacade;
        $languages = $this->languageFacade->getLanguagesForSelect();

        /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
        include $this->viewPath . 'multi_load.php';
    }

    /**
     * Show the main feeds management list.
     *
     * @param int         $currentLang   Current language filter
     * @param string      $currentQuery  Current search query
     * @param int         $currentPage   Current page number
     * @param int         $currentSort   Current sort index
     * @param string|null $queryPattern  LIKE pattern for name filter (null if no filter)
     *
     * @return void
     */
    private function showList(
        int $currentLang,
        string $currentQuery,
        int $currentPage,
        int $currentSort,
        ?string $queryPattern
    ): void {
        $totalFeeds = $this->feedFacade->countFeeds($currentLang ?: null, $queryPattern);

        if ($totalFeeds > 0) {
            $maxPerPage = (int)Settings::getWithDefault('set-feeds-per-page');
            $pages = intval(($totalFeeds - 1) / $maxPerPage) + 1;

            if ($currentPage < 1) {
                $currentPage = 1;
            }
            if ($currentPage > $pages) {
                $currentPage = $pages;
            }

            $sorts = [
                ['column' => 'NfName', 'direction' => 'ASC'],
                ['column' => 'NfUpdate', 'direction' => 'DESC'],
                ['column' => 'NfUpdate', 'direction' => 'ASC'],
            ];
            $lsorts = count($sorts);
            if ($currentSort < 1) {
                $currentSort = 1;
            }
            if ($currentSort > $lsorts) {
                $currentSort = $lsorts;
            }

            // Build query with QueryBuilder
            $query = QueryBuilder::table('news_feeds')->select(['*']);

            if (!empty($currentLang)) {
                $query->where('NfLgID', '=', $currentLang);
            }
            if ($queryPattern !== null) {
                $query->where('NfName', 'LIKE', $queryPattern);
            }

            $sortConfig = $sorts[$currentSort - 1];
            $query->orderBy($sortConfig['column'], $sortConfig['direction']);

            $feeds = $query->getPrepared();
        } else {
            $feeds = null;
            $pages = 0;
            $maxPerPage = 0;
        }

        // Pass service to view for utility methods
        $feedService = $this->feedFacade;
        $languages = $this->languageFacade->getLanguagesForSelect();

        /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
        include $this->viewPath . 'index.php';
    }

    /**
     * Feed wizard page.
     *
     * Routes based on step parameter:
     * - step=1: Insert Feed URI
     * - step=2: Select Article Text
     * - step=3: Filter Text
     * - step=4: Edit Options
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function wizard(array $params): void
    {
        session_start();

        $step = InputValidator::getInt('step', 1) ?? 1;

        switch ($step) {
            case 2:
                $this->wizardStep2();
                break;
            case 3:
                $this->wizardStep3();
                break;
            case 4:
                $this->wizardStep4();
                break;
            case 1:
            default:
                $this->wizardStep1();
                break;
        }
    }

    /**
     * Wizard Step 1: Insert Feed URI.
     *
     * @return void
     *
     * @psalm-suppress UnresolvableInclude View path is constructed at runtime
     */
    private function wizardStep1(): void
    {
        $this->initWizardSession();

        PageLayoutHelper::renderPageStart('Feed Wizard', false);

        $errorMessage = InputValidator::has('err') ? true : null;
        /** @var array{rss_url?: string, feed?: array<string|int, mixed>} $wizard */
        $wizard = $_SESSION['wizard'] ?? [];
        $rssUrl = $wizard['rss_url'] ?? null;

        include $this->viewPath . 'wizard_step1.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Wizard Step 2: Select Article Text.
     *
     * @return void
     */
    private function wizardStep2(): void
    {
        // Handle edit mode - load existing feed
        $editFeedId = InputValidator::getInt('edit_feed');
        $rssUrl = InputValidator::getString('rss_url');
        if ($editFeedId !== null && !isset($_SESSION['wizard'])) {
            $this->loadExistingFeedForEdit($editFeedId);
        } elseif ($rssUrl !== '') {
            $this->loadNewFeedFromUrl($rssUrl);
        }

        // Process session parameters
        $this->processStep2SessionParams();

        /** @var array{feed?: array<string|int, mixed>} $wizardSession */
        $wizardSession = $_SESSION['wizard'] ?? [];
        /** @var array<string|int, mixed> $feedData */
        $feedData = $wizardSession['feed'] ?? [];
        $feedLen = count(array_filter(array_keys($feedData), 'is_numeric'));

        // Handle article section change
        $nfArticleSection = InputValidator::getString('NfArticleSection');
        if (
            $nfArticleSection !== '' &&
            ($nfArticleSection != ($feedData['feed_text'] ?? ''))
        ) {
            $this->updateFeedArticleSource($nfArticleSection, $feedLen);
        }

        PageLayoutHelper::renderPageStartNobody('Feed Wizard');

        $wizardData = &$_SESSION['wizard'];
        $feedHtml = $this->getStep2FeedHtml();

        /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
        include $this->viewPath . 'wizard_step2.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Wizard Step 3: Filter Text.
     *
     * @return void
     */
    private function wizardStep3(): void
    {
        $this->processStep3SessionParams();

        $feedData = $this->getWizardFeed();
        $feedLen = count(array_filter(array_keys($feedData), 'is_numeric'));

        PageLayoutHelper::renderPageStartNobody("Feed Wizard");

        $wizardData = &$_SESSION['wizard'];
        $feedHtml = $this->getStep3FeedHtml();

        /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
        include $this->viewPath . 'wizard_step3.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Wizard Step 4: Edit Options.
     *
     * @return void
     */
    private function wizardStep4(): void
    {
        PageLayoutHelper::renderPageStart('Feed Wizard', false);

        $filterTags = InputValidator::getString('filter_tags');
        if ($filterTags !== '') {
            $_SESSION['wizard']['filter_tags'] = $filterTags;
        }

        $options = $this->getWizardString('options');
        $autoUpdI = $this->feedFacade->getNfOption($options, 'autoupdate');
        if ($autoUpdI === null || !is_string($autoUpdI)) {
            $autoUpdV = null;
            $autoUpdI = null;
        } else {
            $autoUpdV = substr($autoUpdI, -1);
            $autoUpdI = substr($autoUpdI, 0, -1);
        }

        $wizardData = &$_SESSION['wizard'];
        $languages = $this->feedFacade->getLanguages();
        $service = $this->feedFacade;

        /** @psalm-suppress UnresolvableInclude View path is constructed at runtime */
        include $this->viewPath . 'wizard_step4.php';

        // Clear wizard session after step 4
        unset($_SESSION['wizard']);

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Initialize wizard session data.
     *
     * @return void
     */
    private function initWizardSession(): void
    {
        $selectMode = InputValidator::getString('select_mode');
        if ($selectMode !== '') {
            $_SESSION['wizard']['select_mode'] = $selectMode;
        }
        $hideImages = InputValidator::getString('hide_images');
        if ($hideImages !== '') {
            $_SESSION['wizard']['hide_images'] = $hideImages;
        }
    }

    /**
     * Load existing feed data for editing.
     *
     * @param int $feedId Feed ID
     *
     * @return void
     */
    private function loadExistingFeedForEdit(int $feedId): void
    {
        $row = $this->feedFacade->getFeedById($feedId);

        if ($row === null) {
            header("Location: /feeds/wizard?step=1&err=1");
            exit();
        }

        $_SESSION['wizard']['edit_feed'] = $feedId;
        $_SESSION['wizard']['rss_url'] = $row['NfSourceURI'];

        // Parse article tags
        $articleTags = explode('|', str_replace('!?!', '|', $row['NfArticleSectionTags']));
        $articleTagsHtml = '';
        foreach ($articleTags as $tag) {
            if (substr_compare(trim($tag), "redirect", 0, 8) == 0) {
                $_SESSION['wizard']['redirect'] = trim($tag) . ' | ';
            } else {
                $articleTagsHtml .= '<li class="left">'
                . IconHelper::render('x', ['class' => 'delete_selection', 'title' => 'Delete Selection', 'alt' => '-'])
                . $tag .
                '</li>';
            }
        }
        $_SESSION['wizard']['article_tags'] = $articleTagsHtml;

        // Parse filter tags
        $filterTags = explode('|', str_replace('!?!', '|', $row['NfFilterTags']));
        $filterTagsHtml = '';
        foreach ($filterTags as $tag) {
            if (trim($tag) != '') {
                $filterTagsHtml .= '<li class="left">'
                . IconHelper::render('x', ['class' => 'delete_selection', 'title' => 'Delete Selection', 'alt' => '-'])
                . $tag .
                '</li>';
            }
        }
        $_SESSION['wizard']['filter_tags'] = $filterTagsHtml;

        $feedData = $this->feedFacade->detectAndParseFeed($row['NfSourceURI']);
        if (!is_array($feedData) || empty($feedData)) {
            $wizardSession = isset($_SESSION['wizard']) && is_array($_SESSION['wizard'])
                ? $_SESSION['wizard']
                : [];
            unset($wizardSession['feed']);
            $_SESSION['wizard'] = $wizardSession;
            header("Location: /feeds/wizard?step=1&err=1");
            exit();
        }
        // Update feed data with title
        $feedData['feed_title'] = $row['NfName'];
        $_SESSION['wizard']['feed'] = $feedData;
        $_SESSION['wizard']['options'] = $row['NfOptions'];

        $feedText = isset($feedData['feed_text']) && is_string($feedData['feed_text'])
            ? $feedData['feed_text']
            : '';
        if ($feedText === '') {
            $feedData['feed_text'] = '';
            $_SESSION['wizard']['feed'] = $feedData;
            $_SESSION['wizard']['detected_feed'] = 'Detected: «Webpage Link»';
        } else {
            $_SESSION['wizard']['detected_feed'] = 'Detected: «' . $feedText . '»';
        }

        $_SESSION['wizard']['lang'] = $row['NfLgID'];

        // Handle custom article source
        $articleSource = $this->feedFacade->getNfOption($row['NfOptions'], 'article_source');
        $articleSourceStr = is_string($articleSource) ? $articleSource : '';
        $currentFeedText = $feedText;
        if ($currentFeedText !== $articleSourceStr && $articleSourceStr !== '') {
            $feedData['feed_text'] = $articleSourceStr;
            $feedLen = count(array_filter(array_keys($feedData), 'is_numeric'));
            for ($i = 0; $i < $feedLen; $i++) {
                $item = $feedData[$i] ?? null;
                if (is_array($item) && isset($item[$articleSourceStr])) {
                    $item['text'] = $item[$articleSourceStr];
                    $feedData[$i] = $item;
                }
            }
            $_SESSION['wizard']['feed'] = $feedData;
        }
    }

    /**
     * Load new feed from URL.
     *
     * @param string $rssUrl Feed URL
     *
     * @return void
     *
     * @psalm-suppress MixedArrayAccess,MixedAssignment,MixedOperand - Session wizard data
     */
    private function loadNewFeedFromUrl(string $rssUrl): void
    {
        if (
            isset($_SESSION['wizard']) && !empty($_SESSION['wizard']['feed']) &&
            $rssUrl === $_SESSION['wizard']['rss_url']
        ) {
            session_destroy();
            throw new \RuntimeException(
                "Session state conflict detected. Please reload the page and try again."
            );
        }

        $_SESSION['wizard']['feed'] = $this->feedFacade->detectAndParseFeed($rssUrl);
        $_SESSION['wizard']['rss_url'] = $rssUrl;

        if ($_SESSION['wizard']['feed'] === false || (is_array($_SESSION['wizard']['feed']) && count($_SESSION['wizard']['feed']) === 0)) {
            unset($_SESSION['wizard']['feed']);
            header("Location: /feeds/wizard?step=1&err=1");
            exit();
        }

        if (!isset($_SESSION['wizard']['article_tags'])) {
            $_SESSION['wizard']['article_tags'] = '';
        }
        if (!isset($_SESSION['wizard']['filter_tags'])) {
            $_SESSION['wizard']['filter_tags'] = '';
        }
        if (!isset($_SESSION['wizard']['options'])) {
            $_SESSION['wizard']['options'] = 'edit_text=1';
        }
        if (!isset($_SESSION['wizard']['lang'])) {
            $_SESSION['wizard']['lang'] = '';
        }

        if ($_SESSION['wizard']['feed']['feed_text'] != '') {
            $_SESSION['wizard']['detected_feed'] = 'Detected: «' .
            $_SESSION['wizard']['feed']['feed_text'] . '»';
        } else {
            $_SESSION['wizard']['detected_feed'] = 'Detected: «Webpage Link»';
        }
    }

    /**
     * Process step 2 session parameters.
     *
     * @return void
     *
     * @psalm-suppress MixedArrayAccess,MixedAssignment - Session wizard data
     */
    private function processStep2SessionParams(): void
    {
        $filterTags = InputValidator::getString('filter_tags');
        if ($filterTags !== '') {
            $_SESSION['wizard']['filter_tags'] = $filterTags;
        }
        $selectedFeed = InputValidator::getString('selected_feed');
        if ($selectedFeed !== '') {
            $_SESSION['wizard']['selected_feed'] = $selectedFeed;
        }
        $maxim = InputValidator::getString('maxim');
        if ($maxim !== '') {
            $_SESSION['wizard']['maxim'] = $maxim;
        }
        if (!isset($_SESSION['wizard']['maxim'])) {
            $_SESSION['wizard']['maxim'] = 1;
        }
        $selectMode = InputValidator::getString('select_mode');
        if ($selectMode !== '') {
            $_SESSION['wizard']['select_mode'] = $selectMode;
        }
        if (!isset($_SESSION['wizard']['select_mode'])) {
            $_SESSION['wizard']['select_mode'] = '0';
        }
        $hideImages = InputValidator::getString('hide_images');
        if ($hideImages !== '') {
            $_SESSION['wizard']['hide_images'] = $hideImages;
        }
        if (!isset($_SESSION['wizard']['hide_images'])) {
            $_SESSION['wizard']['hide_images'] = 'yes';
        }
        if (!isset($_SESSION['wizard']['redirect'])) {
            $_SESSION['wizard']['redirect'] = '';
        }
        if (!isset($_SESSION['wizard']['selected_feed'])) {
            $_SESSION['wizard']['selected_feed'] = 0;
        }
        if (!isset($_SESSION['wizard']['host'])) {
            $_SESSION['wizard']['host'] = array();
        }
        $hostName = InputValidator::getString('host_name');
        $hostStatus = InputValidator::getString('host_status');
        if ($hostStatus !== '' && $hostName !== '') {
            $_SESSION['wizard']['host'][$hostName] = $hostStatus;
        }
        $nfName = InputValidator::getString('NfName');
        if ($nfName !== '') {
            $_SESSION['wizard']['feed']['feed_title'] = $nfName;
        }
    }

    /**
     * Process step 3 session parameters.
     *
     * @return void
     *
     * @psalm-suppress MixedArrayAccess,MixedAssignment - Session wizard data
     */
    private function processStep3SessionParams(): void
    {
        $nfName = InputValidator::getString('NfName');
        if ($nfName !== '') {
            $_SESSION['wizard']['feed']['feed_title'] = $nfName;
        }
        $nfArticleSection = InputValidator::getString('NfArticleSection');
        if ($nfArticleSection !== '') {
            $_SESSION['wizard']['article_section'] = $nfArticleSection;
        }
        $articleSelector = InputValidator::getString('article_selector');
        if ($articleSelector !== '') {
            $_SESSION['wizard']['article_selector'] = $articleSelector;
        }
        $selectedFeed = InputValidator::getString('selected_feed');
        if ($selectedFeed !== '') {
            $_SESSION['wizard']['selected_feed'] = $selectedFeed;
        }
        $articleTags = InputValidator::getString('article_tags');
        if ($articleTags !== '') {
            $_SESSION['wizard']['article_tags'] = $articleTags;
        }
        $html = InputValidator::getString('html');
        if ($html !== '') {
            $_SESSION['wizard']['filter_tags'] = $html;
        }
        $nfOptions = InputValidator::getString('NfOptions');
        if ($nfOptions !== '') {
            $_SESSION['wizard']['options'] = $nfOptions;
        }
        $nfLgId = InputValidator::getString('NfLgID');
        if ($nfLgId !== '') {
            $_SESSION['wizard']['lang'] = $nfLgId;
        }
        if (!isset($_SESSION['wizard']['article_tags'])) {
            $_SESSION['wizard']['article_tags'] = '';
        }
        $maxim = InputValidator::getString('maxim');
        if ($maxim !== '') {
            $_SESSION['wizard']['maxim'] = $maxim;
        }
        $selectMode = InputValidator::getString('select_mode');
        if ($selectMode !== '') {
            $_SESSION['wizard']['select_mode'] = $selectMode;
        }
        $hideImages = InputValidator::getString('hide_images');
        if ($hideImages !== '') {
            $_SESSION['wizard']['hide_images'] = $hideImages;
        }
        if (!isset($_SESSION['wizard']['select_mode'])) {
            $_SESSION['wizard']['select_mode'] = '';
        }
        if (!isset($_SESSION['wizard']['maxim'])) {
            $_SESSION['wizard']['maxim'] = 1;
        }
        if (!isset($_SESSION['wizard']['selected_feed'])) {
            $_SESSION['wizard']['selected_feed'] = 0;
        }
        if (!isset($_SESSION['wizard']['host2'])) {
            $_SESSION['wizard']['host2'] = array();
        }
        $hostName = InputValidator::getString('host_name');
        $hostStatus = InputValidator::getString('host_status');
        $hostStatus2 = InputValidator::getString('host_status2');
        if ($hostStatus !== '' && $hostName !== '') {
            $_SESSION['wizard']['host'][$hostName] = $hostStatus;
        }
        if ($hostStatus2 !== '' && $hostName !== '') {
            $_SESSION['wizard']['host2'][$hostName] = $hostStatus2;
        }
    }

    /**
     * Update feed article source.
     *
     * @param string $articleSection New article section
     * @param int    $feedLen        Number of feed items
     *
     * @return void
     *
     * @psalm-suppress MixedArrayAccess,MixedAssignment,MixedArrayOffset - Session wizard data
     */
    private function updateFeedArticleSource(string $articleSection, int $feedLen): void
    {
        $_SESSION['wizard']['feed']['feed_text'] = $articleSection;
        $source = $_SESSION['wizard']['feed']['feed_text'];

        for ($i = 0; $i < $feedLen; $i++) {
            if ($_SESSION['wizard']['feed']['feed_text'] != '') {
                $_SESSION['wizard']['feed'][$i]['text'] = $_SESSION['wizard']['feed'][$i][$source];
            } else {
                unset($_SESSION['wizard']['feed'][$i]['text']);
            }
            unset($_SESSION['wizard']['feed'][$i]['html']);
        }
        $_SESSION['wizard']['host'] = array();
    }

    /**
     * Get HTML content for step 2 feed preview.
     *
     * @return string HTML content
     *
     * @psalm-suppress MixedArrayAccess,MixedArgument,MixedAssignment,MixedOperand,MixedArrayOffset - Session wizard data
     */
    private function getStep2FeedHtml(): string
    {
        $i = $_SESSION['wizard']['selected_feed'];
        /** @var list<mixed> */
        $aFeed = [];

        if (!isset($_SESSION['wizard']['feed'][$i]['html'])) {
            $aFeed = [$_SESSION['wizard']['feed'][$i]];
            $charsetRaw = $this->feedFacade->getNfOption($_SESSION['wizard']['options'], 'charset');
            $charset = is_string($charsetRaw) ? $charsetRaw : null;
            $_SESSION['wizard']['feed'][$i]['html'] = $this->feedFacade->extractTextFromArticle(
                $aFeed,
                $_SESSION['wizard']['redirect'] . 'new',
                'iframe!?!script!?!noscript!?!head!?!meta!?!link!?!style',
                $charset
            );
        }

        return $_SESSION['wizard']['feed'][$i]['html'];
    }

    /**
     * Get HTML content for step 3 feed preview.
     *
     * @return string HTML content
     *
     * @psalm-suppress MixedArrayAccess,MixedArgument,MixedAssignment,MixedOperand,MixedArrayOffset - Session wizard data
     */
    private function getStep3FeedHtml(): string
    {
        $i = $_SESSION['wizard']['selected_feed'];
        /** @var list<mixed> */
        $aFeed = [];

        if (!isset($_SESSION['wizard']['feed'][$i]['html'])) {
            $aFeed = [$_SESSION['wizard']['feed'][$i]];
            $charsetRaw = $this->feedFacade->getNfOption($_SESSION['wizard']['options'], 'charset');
            $charset = is_string($charsetRaw) ? $charsetRaw : null;
            $_SESSION['wizard']['feed'][$i]['html'] = $this->feedFacade->extractTextFromArticle(
                $aFeed,
                $_SESSION['wizard']['redirect'] . 'new',
                'iframe!?!script!?!noscript!?!head!?!meta!?!link!?!style',
                $charset
            );
        }

        return $_SESSION['wizard']['feed'][$i]['html'];
    }

    /**
     * Feeds SPA page - modern Alpine.js single page application.
     *
     * This method provides a reactive feed management interface with:
     * - Feed list with filtering, sorting, and pagination
     * - Article browsing with import functionality
     * - Create/edit feed forms
     * - Bulk actions
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnresolvableInclude View path is constructed at runtime
     */
    public function spa(array $params): void
    {
        PageLayoutHelper::renderPageStart('Feed Manager', true);
        /** @psalm-suppress UnresolvableInclude */
        include $this->viewPath . 'spa.php';
        PageLayoutHelper::renderPageEnd();
    }
}

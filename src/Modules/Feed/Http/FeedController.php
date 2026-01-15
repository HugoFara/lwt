<?php

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

declare(strict_types=1);

namespace Lwt\Modules\Feed\Http;

use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Modules\Feed\Infrastructure\FeedWizardSessionManager;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\Validation;
use Lwt\Shared\Infrastructure\Http\FlashMessageService;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

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
     * Wizard session manager.
     */
    private FeedWizardSessionManager $wizardSession;

    /**
     * Flash message service.
     */
    private FlashMessageService $flashService;

    /**
     * Constructor.
     *
     * @param FeedFacade               $feedFacade     Feed facade
     * @param LanguageFacade           $languageFacade Language facade
     * @param FeedWizardSessionManager $wizardSession  Wizard session manager
     * @param FlashMessageService      $flashService   Flash message service
     */
    public function __construct(
        FeedFacade $feedFacade,
        LanguageFacade $languageFacade,
        ?FeedWizardSessionManager $wizardSession = null,
        ?FlashMessageService $flashService = null
    ) {
        $this->viewPath = __DIR__ . '/../Views/';
        $this->feedFacade = $feedFacade;
        $this->languageFacade = $languageFacade;
        $this->wizardSession = $wizardSession ?? new FeedWizardSessionManager();
        $this->flashService = $flashService ?? new FlashMessageService();
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

        // EXTR_SKIP prevents overwriting existing variables
        extract($data, EXTR_SKIP);
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
            echo '<div class="notification is-info">' .
                '<p>UPDATING <span x-text="loadedCount">0</span>/' .
                $config['count'] . ' FEEDS</p></div>';
        }

        echo '<template x-for="feed in feeds" :key="feed.id">';
        echo '<div :class="getStatusClass(feed.id)"><p x-text="feedMessages[feed.id]"></p></div>';
        echo '</template>';

        echo '<div class="has-text-centered"><button @click="handleContinue()">Continue</button></div>';
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
        $currentLang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );
        PageLayoutHelper::renderPageStart($this->languageFacade->getLanguageName($currentLang) . ' Feeds', true);

        $currentFeed = InputValidator::getString("selected_feed");

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
            echo '<div class="notification is-success" data-auto-hide="true">' .
                '<button class="delete" aria-label="close"></button>' .
                'Text "' . htmlspecialchars((string)($text['TxTitle'] ?? ''), ENT_QUOTES, 'UTF-8') . '" added!' .
                '</div>';

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
            $result = $this->feedFacade->saveTextsFromFeed($feedData);
            $message = "Texts archived: {$result['textsArchived']}, " .
                "Sentences deleted: {$result['sentencesDeleted']}, " .
                "Text items deleted: {$result['textItemsDeleted']}";
        }

        // Display flash messages from previous requests
        $flashMessages = $this->flashService->getAndClear();
        foreach ($flashMessages as $flashMsg) {
            $isError = FlashMessageService::isError($flashMsg['type']);
            $notifClass = $isError ? 'is-danger' : 'is-success';
            $autoHide = $isError ? '' : ' data-auto-hide="true"';
            echo '<div class="notification ' . $notifClass . '"' . $autoHide . '>' .
                '<button class="delete" aria-label="close"></button>' .
                htmlspecialchars($flashMsg['message']) .
                '</div>';
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
            PageLayoutHelper::renderMessage($message);
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
        $currentQuery = InputValidator::getString("query");
        $currentQueryMode = InputValidator::getString("query_mode", 'title,desc,text');
        $currentRegexMode = Settings::getWithDefault("set-regex-mode");

        $filterData = $this->feedFacade->buildQueryFilter($currentQuery, $currentQueryMode, $currentRegexMode);
        $searchTerm = $filterData['search'];

        if (!empty($currentQuery) && !empty($currentRegexMode)) {
            if (!$this->feedFacade->validateRegexPattern($currentQuery)) {
                $currentQuery = '';
                $searchTerm = '';
                if (InputValidator::has('query')) {
                    echo '<div class="notification is-warning" data-auto-hide="true">' .
                        '<button class="delete" aria-label="close"></button>' .
                        'Warning: Invalid Search' .
                        '</div>';
                }
            }
        }

        $currentPage = InputValidator::getIntParam("page", 1, 1);
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
        $currentQuery = InputValidator::getString("query");
        $currentPage = InputValidator::getIntParam("page", 1, 1);
        $currentFeed = InputValidator::getString("selected_feed");

        // Build query pattern for prepared statement (no SQL escaping needed)
        $queryPattern = ($currentQuery != '') ? ('%' . str_replace("*", "%", $currentQuery) . '%') : null;

        // Clear wizard session if exists (must be before any output)
        if ($this->wizardSession->exists()) {
            $this->wizardSession->clear();
        }

        $langName = $this->languageFacade->getLanguageName($currentLang);
        PageLayoutHelper::renderPageStart('Manage ' . $langName . ' Feeds', true);

        // Handle mark actions (delete, delete articles, reset articles)
        $result = $this->handleMarkAction($currentFeed);
        $message = $this->formatMarkActionMessage($result);
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
            $this->showNewForm((int)$currentLang);
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
     * @return array{action: string, success: bool}|null Result data or null if no action
     */
    private function handleMarkAction(string $currentFeed): ?array
    {
        $action = InputValidator::getString('markaction');
        if ($action === '' || empty($currentFeed)) {
            return null;
        }

        switch ($action) {
            case 'del':
                $this->feedFacade->deleteFeeds($currentFeed);
                return ['action' => 'del', 'success' => true];

            case 'del_art':
                $this->feedFacade->deleteArticles($currentFeed);
                return ['action' => 'del_art', 'success' => true];

            case 'res_art':
                $this->feedFacade->resetUnloadableArticles($currentFeed);
                return ['action' => 'res_art', 'success' => true];

            default:
                return null;
        }
    }

    /**
     * Format mark action result into a display message.
     *
     * @param array{action: string, success: bool}|null $result Action result
     *
     * @return string Formatted message for display
     */
    private function formatMarkActionMessage(?array $result): string
    {
        if ($result === null) {
            return '';
        }

        return match ($result['action']) {
            'del' => 'Article item(s) deleted / Newsfeed(s) deleted',
            'del_art' => 'Article item(s) deleted',
            'res_art' => 'Article(s) reset',
            default => ''
        };
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
        $flashMessages = $this->flashService->getAndClear();
        foreach ($flashMessages as $flashMsg) {
            $isError = FlashMessageService::isError($flashMsg['type']);
            $notifClass = $isError ? 'is-danger' : 'is-success';
            $autoHide = $isError ? '' : ' data-auto-hide="true"';
            echo '<div class="notification ' . $notifClass . '"' . $autoHide . '>' .
                '<button class="delete" aria-label="close"></button>' .
                htmlspecialchars($flashMsg['message']) .
                '</div>';
        }
    }

    /**
     * Show the new feed form.
     *
     * @param int $currentLang Current language ID to pre-select
     *
     * @return void
     *
     * @psalm-suppress UnresolvableInclude View path is constructed at runtime
     */
    private function showNewForm(int $currentLang): void
    {
        $viewData = [
            'languages' => $this->feedFacade->getLanguages(),
            'currentLang' => $currentLang,
        ];
        extract($viewData);

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
            echo '<div class="notification is-danger">' .
                '<button class="delete" aria-label="close"></button>' .
                'Feed not found.' .
                '</div>';
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

    // =========================================================================
    // RESTful Route Handlers
    // =========================================================================

    /**
     * New feed form.
     *
     * Route: GET/POST /feeds/new
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function newFeed(array $params): void
    {
        $currentLang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );

        $langName = $this->languageFacade->getLanguageName($currentLang);
        PageLayoutHelper::renderPageStart('New Feed - ' . $langName, true);

        // Handle form submission
        if (InputValidator::has('save_feed')) {
            $data = [
                'NfLgID' => InputValidator::getString('NfLgID'),
                'NfName' => InputValidator::getString('NfName'),
                'NfSourceURI' => InputValidator::getString('NfSourceURI'),
                'NfArticleSectionTags' => InputValidator::getString('NfArticleSectionTags'),
                'NfFilterTags' => InputValidator::getString('NfFilterTags'),
                'NfOptions' => rtrim(InputValidator::getString('NfOptions'), ','),
            ];

            $feedId = $this->feedFacade->createFeed($data);
            $this->flashService->success('Feed created successfully');
            header('Location: ' . url('/feeds/' . $feedId . '/edit'));
            exit;
        }

        $this->showNewForm((int)$currentLang);
        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Edit feed form.
     *
     * Route: GET/POST /feeds/{id}/edit
     *
     * @param int $id Feed ID from route parameter
     *
     * @return void
     */
    public function editFeed(int $id): void
    {
        $feed = $this->feedFacade->getFeedById($id);

        if ($feed === null) {
            $this->flashService->error('Feed not found');
            header('Location: ' . url('/feeds/manage'));
            exit;
        }

        $langName = $this->languageFacade->getLanguageName($feed['NfLgID']);
        PageLayoutHelper::renderPageStart('Edit Feed - ' . $langName, true);

        // Handle form submission
        if (InputValidator::has('update_feed')) {
            $data = [
                'NfLgID' => InputValidator::getString('NfLgID'),
                'NfName' => InputValidator::getString('NfName'),
                'NfSourceURI' => InputValidator::getString('NfSourceURI'),
                'NfArticleSectionTags' => InputValidator::getString('NfArticleSectionTags'),
                'NfFilterTags' => InputValidator::getString('NfFilterTags'),
                'NfOptions' => rtrim(InputValidator::getString('NfOptions'), ','),
            ];

            $this->feedFacade->updateFeed($id, $data);
            $this->flashService->success('Feed updated successfully');
            header('Location: ' . url('/feeds/manage'));
            exit;
        }

        $this->showEditForm($id);
        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Delete a feed.
     *
     * Route: DELETE /feeds/{id}
     *
     * @param int $id Feed ID from route parameter
     *
     * @return void
     */
    public function deleteFeed(int $id): void
    {
        $result = $this->feedFacade->deleteFeeds((string)$id);

        if ($result['feeds'] > 0) {
            $this->flashService->success('Feed deleted successfully');
        } else {
            $this->flashService->error('Failed to delete feed');
        }

        header('Location: ' . url('/feeds/manage'));
        exit;
    }

    /**
     * Load/refresh a single feed.
     *
     * Route: GET /feeds/{id}/load
     *
     * @param int $id Feed ID from route parameter
     *
     * @return void
     */
    public function loadFeedRoute(int $id): void
    {
        $currentLang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );

        $langName = $this->languageFacade->getLanguageName($currentLang);
        PageLayoutHelper::renderPageStart('Loading Feed - ' . $langName, true);

        $this->feedFacade->renderFeedLoadInterfaceModern(
            $id,
            false,
            '/feeds/manage'
        );

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Multi-load feeds interface.
     *
     * Route: GET /feeds/multi-load
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function multiLoad(array $params): void
    {
        $currentLang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );

        $langName = $this->languageFacade->getLanguageName($currentLang);
        PageLayoutHelper::renderPageStart('Multi-Load Feeds - ' . $langName, true);

        $this->showMultiLoadForm((int)$currentLang);

        PageLayoutHelper::renderPageEnd();
    }
}

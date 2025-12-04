<?php declare(strict_types=1);
/**
 * \file
 * \brief Feeds Controller - RSS feed management
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-feedscontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\View\Helper\PageLayoutHelper;
use Lwt\View\Helper\IconHelper;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';
require_once __DIR__ . '/../Services/TextStatisticsService.php';
require_once __DIR__ . '/../Services/SentenceService.php';
require_once __DIR__ . '/../Services/AnnotationService.php';
require_once __DIR__ . '/../Services/SimilarTermsService.php';
require_once __DIR__ . '/../Services/TextNavigationService.php';
require_once __DIR__ . '/../Services/TextParsingService.php';
require_once __DIR__ . '/../Services/ExpressionService.php';
require_once __DIR__ . '/../Core/Database/Restore.php';
require_once __DIR__ . '/../Core/Http/param_helpers.php';
require_once __DIR__ . '/../Services/MediaService.php';
require_once __DIR__ . '/../Services/TagService.php';
require_once __DIR__ . '/../Services/LanguageService.php';

use Lwt\Core\Utils\ErrorHandler;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Settings;
use Lwt\Database\Validation;
use Lwt\Services\FeedService;
use Lwt\Services\TagService;
use Lwt\Services\LanguageService;
use Lwt\Core\Http\ParamHelpers;

/**
 * Controller for RSS feed management.
 *
 * Handles:
 * - Feed listing and browsing
 * - Feed editing (add/edit/delete)
 * - Feed import wizard
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class FeedsController extends BaseController
{
    /**
     * @var FeedService Feed service instance
     */
    private FeedService $feedService;

    /**
     * @var LanguageService Language service instance
     */
    private LanguageService $languageService;

    /**
     * Constructor - initialize feed service.
     */
    public function __construct()
    {
        parent::__construct();
        $this->feedService = new FeedService();
        $this->languageService = new LanguageService();
    }

    /**
     * Get the FeedService instance for testing.
     *
     * @return FeedService
     */
    public function getFeedService(): FeedService
    {
        return $this->feedService;
    }

    /**
     * Feeds index page (replaces feeds_index.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        session_start();

        $currentLang = Validation::language(
            (string)ParamHelpers::processDBParam("filterlang", 'currentlanguage', '', false)
        );
        PageLayoutHelper::renderPageStart($this->languageService->getLanguageName($currentLang) . ' Feeds', true);

        $currentFeed = (string)ParamHelpers::processSessParam(
            "selected_feed",
            "currentrssfeed",
            '',
            false
        );

        $editText = 0;
        $message = '';

        // Handle marked items submission
        $markedItemsArray = $this->paramArray('marked_items');
        if (!empty($markedItemsArray)) {
            $result = $this->processMarkedItems();
            $editText = $result['editText'];
            $message = $result['message'];
        }

        // Display messages
        $this->displayFeedMessages($message);

        // Route based on action
        $markAction = $this->param('markaction');
        if (
            $this->hasParam('load_feed') || $this->hasParam('check_autoupdate')
            || ($markAction == 'update')
        ) {
            $this->feedService->renderFeedLoadInterfaceModern(
                (int)$currentFeed,
                $this->hasParam('check_autoupdate'),
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

        $markedItemsArray = $this->paramArray('marked_items');
        if (empty($markedItemsArray)) {
            return ['editText' => $editText, 'message' => $message];
        }

        $markedItems = implode(',', array_filter($markedItemsArray, 'is_scalar'));
        $feedLinks = $this->feedService->getMarkedFeedLinks($markedItems);

        $stats = ['archived' => 0, 'sentences' => 0, 'textitems' => 0, 'texts' => 0];
        $count = 0;
        $languages = null;

        foreach ($feedLinks as $row) {
            $requiresEdit = $this->feedService->getNfOption($row['NfOptions'], 'edit_text') == 1;

            if ($requiresEdit) {
                if ($editText == 1) {
                    $count++;
                } else {
                    echo '<form class="validate" action="/feeds" method="post">';
                    $editText = 1;
                    $languages = $this->feedService->getLanguages();
                }
            }

            $doc = [[
                'link' => empty($row['FlLink']) ? ('#' . $row['FlID']) : $row['FlLink'],
                'title' => $row['FlTitle'],
                'audio' => $row['FlAudio'],
                'text' => $row['FlText']
            ]];

            $nfName = (string)$row['NfName'];
            $nfId = (int)$row['NfID'];
            $nfOptions = $row['NfOptions'];

            $tagName = $this->feedService->getNfOption($nfOptions, 'tag');
            if (!$tagName) {
                $tagName = mb_substr($nfName, 0, 20, "utf-8");
            }

            $maxTexts = (int)$this->feedService->getNfOption($nfOptions, 'max_texts');
            if (!$maxTexts) {
                $maxTexts = (int)Settings::getWithDefault('set-max-texts-per-feed');
            }

            $texts = $this->feedService->extractTextFromArticle(
                $doc,
                $row['NfArticleSectionTags'],
                $row['NfFilterTags'],
                $this->feedService->getNfOption($nfOptions, 'charset')
            );

            if (isset($texts['error'])) {
                echo $texts['error']['message'];
                foreach ($texts['error']['link'] as $errLink) {
                    $this->feedService->markLinkAsError($errLink);
                }
                unset($texts['error']);
            }

            if ($requiresEdit) {
                // Include edit form view
                $scrdir = $this->languageService->getScriptDirectionTag((int)$row['NfLgID']);
                include __DIR__ . '/../Views/Feed/edit_text_form.php';
            } else {
                $result = $this->createTextsFromFeed($texts, $row, $tagName, $maxTexts);
                $stats['archived'] += $result['archived'];
                $stats['sentences'] += $result['sentences'];
                $stats['textitems'] += $result['textitems'];
            }
        }

        if ($stats['archived'] > 0 || $stats['texts'] > 0) {
            $message = "Texts archived: {$stats['archived']} / Sentences deleted: {$stats['sentences']}" .
                       " / Text items deleted: {$stats['textitems']}";
        }

        if ($editText == 1) {
            include __DIR__ . '/../Views/Feed/edit_text_footer.php';
        }

        return ['editText' => $editText, 'message' => $message];
    }

    /**
     * Create texts from feed data without edit form.
     *
     * @param array  $texts    Parsed text data
     * @param array  $row      Feed data
     * @param string $tagName  Tag name
     * @param int    $maxTexts Maximum texts to keep
     *
     * @return array{archived: int, sentences: int, textitems: int}
     */
    private function createTextsFromFeed(array $texts, array $row, string $tagName, int $maxTexts): array
    {
        foreach ($texts as $text) {
            echo '<div class="msgblue">
            <p class="hide_message">+++ "' . $text['TxTitle'] . '" added! +++</p>
            </div>';

            $this->feedService->createTextFromFeed([
                'TxLgID' => $row['NfLgID'],
                'TxTitle' => $text['TxTitle'],
                'TxText' => $text['TxText'],
                'TxAudioURI' => $text['TxAudioURI'] ?? '',
                'TxSourceURI' => $text['TxSourceURI'] ?? ''
            ], $tagName);
        }

        TagService::getAllTextTags(true);

        return $this->feedService->archiveOldTexts($tagName, $maxTexts);
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
        if ($this->hasParam('checked_feeds_save')) {
            $message = $this->feedService->saveTextsFromFeed($this->paramArray('feed'));
        }

        if (isset($_SESSION['feed_loaded'])) {
            foreach ($_SESSION['feed_loaded'] as $lf) {
                if (substr($lf, 0, 5) == "Error") {
                    echo "\n<div class=\"red\"><p>";
                } else {
                    echo "\n<div class=\"msgblue\"><p class=\"hide_message\">";
                }
                echo "+++ ", $lf, " +++</p></div>";
            }
            unset($_SESSION['feed_loaded']);
        }

        $this->message($message, false);
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
        $debug = \Lwt\Core\Globals::isDebug();

        $currentQuery = (string)ParamHelpers::processSessParam("query", "currentrssquery", '', false);
        $currentQueryMode = (string)ParamHelpers::processSessParam("query_mode", "currentrssquerymode", 'title,desc,text', false);
        $currentRegexMode = Settings::getWithDefault("set-regex-mode");

        $whQuery = $this->feedService->buildQueryFilter($currentQuery, $currentQueryMode, $currentRegexMode);

        if (!empty($currentQuery) && !empty($currentRegexMode)) {
            if (!$this->feedService->validateRegexPattern($currentQuery)) {
                $currentQuery = '';
                $whQuery = '';
                unset($_SESSION['currentwordquery']);
                if ($this->hasParam('query')) {
                    echo '<p id="hide3" class="warning-message">+++ Warning: Invalid Search +++</p>';
                }
            }
        }

        $currentPage = (int)ParamHelpers::processSessParam("page", "currentrsspage", '1', true);
        $currentSort = (int)ParamHelpers::processDBParam("sort", 'currentrsssort', '2', true);

        $feeds = $this->feedService->getFeeds($currentLang ?: null);

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
        $recno = $currentFeed ? $this->feedService->countFeedLinks($feedIds, $whQuery) : 0;

        if ($debug) {
            echo "Feed IDs: $feedIds, Count: $recno";
        }

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
        $sortColumn = $this->feedService->getSortColumn($currentSort);

        // Get articles if there are any
        $articles = [];
        if ($recno > 0) {
            $articles = $this->feedService->getFeedLinks($feedIds, $whQuery, $sortColumn, $offset, $maxPerPage);
        }

        // Format last update for view
        $lastUpdateFormatted = null;
        if ($feedTime) {
            $diff = time() - (int)$feedTime;
            $lastUpdateFormatted = $this->feedService->formatLastUpdate($diff);
        }

        // Pass service to view for utility methods
        $feedService = $this->feedService;
        $languages = $this->languageService->getLanguagesForSelect();

        // Include browse view
        include __DIR__ . '/../Views/Feed/browse.php';
    }

    /**
     * Edit feeds page (replaces feeds_edit.php)
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
     * @param array $params Route parameters
     *
     * @return void
     */
    public function edit(array $params): void
    {
        $currentLang = Validation::language(
            (string)$this->dbParam("filterlang", 'currentlanguage', '', false)
        );
        $currentSort = (int)$this->dbParam("sort", 'currentmanagefeedssort', '2', true);
        $currentQuery = (string)$this->sessionParam("query", "currentmanagefeedsquery", '', false);
        $currentPage = (int)$this->sessionParam("page", "currentmanagefeedspage", '1', true);
        $currentFeed = (string)$this->sessionParam(
            "selected_feed",
            "currentmanagefeedsfeed",
            '',
            false
        );

        $whQuery = Escaping::toSqlSyntax(str_replace("*", "%", $currentQuery));
        $whQuery = ($currentQuery != '') ? (' and (NfName like ' . $whQuery . ')') : '';

        PageLayoutHelper::renderPageStart('Manage ' . $this->languageService->getLanguageName($currentLang) . ' Feeds', true);

        // Clear wizard session if exists
        if (isset($_SESSION['wizard'])) {
            unset($_SESSION['wizard']);
        }

        // Handle mark actions (delete, delete articles, reset articles)
        $message = $this->handleMarkAction($currentFeed);
        if (!empty($message)) {
            $this->message($message, false);
        }

        // Display session messages from feed loading
        $this->displaySessionMessages();

        // Handle form submissions
        $this->handleUpdateFeed();
        $this->handleSaveFeed();

        // Route to appropriate view
        $markAction = $this->param('markaction');
        if (
            $this->hasParam('load_feed') || $this->hasParam('check_autoupdate')
            || ($markAction == 'update')
        ) {
            $this->feedService->renderFeedLoadInterfaceModern(
                (int)$currentFeed,
                $this->hasParam('check_autoupdate'),
                $_SERVER['PHP_SELF'] ?? '/'
            );
        } elseif ($this->hasParam('new_feed')) {
            $this->showNewForm((int)$currentLang);
        } elseif ($this->hasParam('edit_feed')) {
            $this->showEditForm((int)$currentFeed);
        } elseif ($this->hasParam('multi_load_feed')) {
            $this->showMultiLoadForm((int)$currentLang);
        } else {
            $this->showList(
                (int)$currentLang,
                $currentQuery,
                $currentPage,
                $currentSort,
                $whQuery
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
        $action = $this->param('markaction');
        if ($action === '' || empty($currentFeed)) {
            return '';
        }

        switch ($action) {
            case 'del':
                $this->feedService->deleteFeeds($currentFeed);
                return "Article item(s) deleted / Newsfeed(s) deleted";

            case 'del_art':
                $this->feedService->deleteArticles($currentFeed);
                return "Article item(s) deleted";

            case 'res_art':
                $this->feedService->resetUnloadableArticles($currentFeed);
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
        if (!$this->hasParam('update_feed')) {
            return;
        }

        $feedId = $this->paramInt('NfID', 0) ?? 0;

        $data = [
            'NfLgID' => $this->param('NfLgID'),
            'NfName' => $this->param('NfName'),
            'NfSourceURI' => $this->param('NfSourceURI'),
            'NfArticleSectionTags' => $this->param('NfArticleSectionTags'),
            'NfFilterTags' => $this->param('NfFilterTags'),
            'NfOptions' => rtrim($this->param('NfOptions'), ','),
        ];

        $this->feedService->updateFeed($feedId, $data);
    }

    /**
     * Handle save new feed form submission.
     *
     * @return void
     */
    private function handleSaveFeed(): void
    {
        if (!$this->hasParam('save_feed')) {
            return;
        }

        $data = [
            'NfLgID' => $this->param('NfLgID'),
            'NfName' => $this->param('NfName'),
            'NfSourceURI' => $this->param('NfSourceURI'),
            'NfArticleSectionTags' => $this->param('NfArticleSectionTags'),
            'NfFilterTags' => $this->param('NfFilterTags'),
            'NfOptions' => rtrim($this->param('NfOptions'), ','),
        ];

        $this->feedService->createFeed($data);
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

        foreach ($_SESSION['feed_loaded'] as $lf) {
            echo "\n<div class=\"msgblue\"><p class=\"hide_message\">+++ ", $lf, " +++</p></div>";
        }
        unset($_SESSION['feed_loaded']);
    }

    /**
     * Show the new feed form.
     *
     * @param int $currentLang Current language filter
     *
     * @return void
     */
    private function showNewForm(int $currentLang): void
    {
        $languages = $this->feedService->getLanguages();

        include __DIR__ . '/../Views/Feed/new.php';
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
        $feed = $this->feedService->getFeedById($feedId);

        if (!$feed) {
            echo '<p class="red">Feed not found.</p>';
            return;
        }

        $languages = $this->feedService->getLanguages();

        // Parse options
        $options = $this->feedService->getNfOption($feed['NfOptions'], '');
        if (!is_array($options)) {
            $options = [];
        }

        // Parse auto-update interval
        $autoUpdateRaw = $this->feedService->getNfOption($feed['NfOptions'], 'autoupdate');
        if ($autoUpdateRaw === null) {
            $autoUpdateInterval = null;
            $autoUpdateUnit = null;
        } else {
            $autoUpdateUnit = substr($autoUpdateRaw, -1);
            $autoUpdateInterval = substr($autoUpdateRaw, 0, -1);
        }

        include __DIR__ . '/../Views/Feed/edit.php';
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
        $feeds = $this->feedService->getFeeds($currentLang ?: null);

        // Pass service to view for utility methods
        $feedService = $this->feedService;
        $languages = $this->languageService->getLanguagesForSelect();

        include __DIR__ . '/../Views/Feed/multi_load.php';
    }

    /**
     * Show the main feeds management list.
     *
     * @param int    $currentLang  Current language filter
     * @param string $currentQuery Current search query
     * @param int    $currentPage  Current page number
     * @param int    $currentSort  Current sort index
     * @param string $whQuery      WHERE clause for query filter
     *
     * @return void
     */
    private function showList(
        int $currentLang,
        string $currentQuery,
        int $currentPage,
        int $currentSort,
        string $whQuery
    ): void {
        $totalFeeds = $this->feedService->countFeeds($currentLang ?: null, $whQuery);

        if ($totalFeeds > 0) {
            $maxPerPage = (int)Settings::getWithDefault('set-feeds-per-page');
            $pages = $totalFeeds == 0 ? 0 : (intval(($totalFeeds - 1) / $maxPerPage) + 1);

            if ($currentPage < 1) {
                $currentPage = 1;
            }
            if ($currentPage > $pages) {
                $currentPage = $pages;
            }

            $sorts = ['NfName', 'NfUpdate DESC', 'NfUpdate ASC'];
            $lsorts = count($sorts);
            if ($currentSort < 1) {
                $currentSort = 1;
            }
            if ($currentSort > $lsorts) {
                $currentSort = $lsorts;
            }

            // Build query
            $sql = "SELECT * FROM {$this->tbpref}newsfeeds WHERE ";
            if (!empty($currentLang)) {
                $sql .= "NfLgID = $currentLang $whQuery";
            } else {
                $sql .= "(1=1) $whQuery";
            }
            $sql .= " ORDER BY " . $sorts[$currentSort - 1];

            $feeds = Connection::query($sql);
        } else {
            $feeds = null;
            $pages = 0;
            $maxPerPage = 0;
        }

        // Pass service to view for utility methods
        $feedService = $this->feedService;
        $languages = $this->languageService->getLanguagesForSelect();

        include __DIR__ . '/../Views/Feed/index.php';
    }

    /**
     * Feed wizard page (replaces feeds_wizard.php)
     *
     * Routes based on step parameter:
     * - step=1: Insert Feed URI
     * - step=2: Select Article Text
     * - step=3: Filter Text
     * - step=4: Edit Options
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function wizard(array $params): void
    {
        session_start();

        $step = $this->paramInt('step', 1) ?? 1;

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
     */
    private function wizardStep1(): void
    {
        $this->initWizardSession();

        PageLayoutHelper::renderPageStart('Feed Wizard', false);

        $errorMessage = $this->hasParam('err') ? true : null;
        $rssUrl = $_SESSION['wizard']['rss_url'] ?? null;

        include __DIR__ . '/../Views/Feed/wizard_step1.php';

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
        $editFeedId = $this->paramInt('edit_feed');
        $rssUrl = $this->param('rss_url');
        if ($editFeedId !== null && !isset($_SESSION['wizard'])) {
            $this->loadExistingFeedForEdit($editFeedId);
        } elseif ($rssUrl !== '') {
            $this->loadNewFeedFromUrl($rssUrl);
        }

        // Process session parameters
        $this->processStep2SessionParams();

        $feedLen = count(array_filter(array_keys($_SESSION['wizard']['feed']), 'is_numeric'));

        // Handle article section change
        $nfArticleSection = $this->param('NfArticleSection');
        if (
            $nfArticleSection !== '' &&
            ($nfArticleSection != $_SESSION['wizard']['feed']['feed_text'])
        ) {
            $this->updateFeedArticleSource($nfArticleSection, $feedLen);
        }

        PageLayoutHelper::renderPageStartNobody('Feed Wizard');

        $wizardData = &$_SESSION['wizard'];
        $feedHtml = $this->getStep2FeedHtml();

        include __DIR__ . '/../Views/Feed/wizard_step2.php';

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

        $feedLen = count(array_filter(array_keys($_SESSION['wizard']['feed']), 'is_numeric'));

        PageLayoutHelper::renderPageStartNobody("Feed Wizard");

        $wizardData = &$_SESSION['wizard'];
        $feedHtml = $this->getStep3FeedHtml();

        include __DIR__ . '/../Views/Feed/wizard_step3.php';

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

        $filterTags = $this->param('filter_tags');
        if ($filterTags !== '') {
            $_SESSION['wizard']['filter_tags'] = $filterTags;
        }

        $autoUpdI = $this->feedService->getNfOption($_SESSION['wizard']['options'], 'autoupdate');
        if ($autoUpdI == null) {
            $autoUpdV = null;
        } else {
            $autoUpdV = substr($autoUpdI, -1);
            $autoUpdI = substr($autoUpdI, 0, -1);
        }

        $wizardData = &$_SESSION['wizard'];
        $languages = $this->feedService->getLanguages();
        $service = $this->feedService;

        include __DIR__ . '/../Views/Feed/wizard_step4.php';

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
        $selectMode = $this->param('select_mode');
        if ($selectMode !== '') {
            $_SESSION['wizard']['select_mode'] = $selectMode;
        }
        $hideImages = $this->param('hide_images');
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
        $row = $this->feedService->getFeedById($feedId);

        if (!$row) {
            header("Location: /feeds/wizard?step=1&err=1");
            exit();
        }

        $_SESSION['wizard']['edit_feed'] = $feedId;
        $_SESSION['wizard']['rss_url'] = $row['NfSourceURI'];

        // Parse article tags
        $articleTags = explode('|', str_replace('!?!', '|', $row['NfArticleSectionTags']));
        $_SESSION['wizard']['article_tags'] = '';
        foreach ($articleTags as $tag) {
            if (substr_compare(trim($tag), "redirect", 0, 8) == 0) {
                $_SESSION['wizard']['redirect'] = trim($tag) . ' | ';
            } else {
                $_SESSION['wizard']['article_tags'] .= '<li class="left">'
                . IconHelper::render('x', ['class' => 'delete_selection', 'title' => 'Delete Selection', 'alt' => '-'])
                . $tag .
                '</li>';
            }
        }

        // Parse filter tags
        $filterTags = explode('|', str_replace('!?!', '|', $row['NfFilterTags']));
        $_SESSION['wizard']['filter_tags'] = '';
        foreach ($filterTags as $tag) {
            if (trim($tag) != '') {
                $_SESSION['wizard']['filter_tags'] .= '<li class="left">'
                . IconHelper::render('x', ['class' => 'delete_selection', 'title' => 'Delete Selection', 'alt' => '-'])
                . $tag .
                '</li>';
            }
        }

        $_SESSION['wizard']['feed'] = $this->feedService->detectAndParseFeed($row['NfSourceURI']);
        if (empty($_SESSION['wizard']['feed'])) {
            unset($_SESSION['wizard']['feed']);
            header("Location: /feeds/wizard?step=1&err=1");
            exit();
        }

        $_SESSION['wizard']['feed']['feed_title'] = $row['NfName'];
        $_SESSION['wizard']['options'] = $row['NfOptions'];

        if (empty($_SESSION['wizard']['feed']['feed_text'])) {
            $_SESSION['wizard']['feed']['feed_text'] = '';
            $_SESSION['wizard']['detected_feed'] = 'Detected: «Webpage Link»';
        } else {
            $_SESSION['wizard']['detected_feed'] = 'Detected: «' . $_SESSION['wizard']['feed']['feed_text'] . '»';
        }

        $_SESSION['wizard']['lang'] = $row['NfLgID'];

        // Handle custom article source
        if (
            $_SESSION['wizard']['feed']['feed_text'] != $this->feedService->getNfOption($_SESSION['wizard']['options'], 'article_source')
        ) {
            $source = $this->feedService->getNfOption($_SESSION['wizard']['options'], 'article_source');
            $_SESSION['wizard']['feed']['feed_text'] = $source;
            $feedLen = count(array_filter(array_keys($_SESSION['wizard']['feed']), 'is_numeric'));
            for ($i = 0; $i < $feedLen; $i++) {
                $_SESSION['wizard']['feed'][$i]['text'] = $_SESSION['wizard']['feed'][$i][$source];
            }
        }
    }

    /**
     * Load new feed from URL.
     *
     * @param string $rssUrl Feed URL
     *
     * @return void
     */
    private function loadNewFeedFromUrl(string $rssUrl): void
    {
        if (
            isset($_SESSION['wizard']) && !empty($_SESSION['wizard']['feed']) &&
            $rssUrl === $_SESSION['wizard']['rss_url']
        ) {
            session_destroy();
            ErrorHandler::die("Your session seems to have an issue, please reload the page.");
        }

        $_SESSION['wizard']['feed'] = $this->feedService->detectAndParseFeed($rssUrl);
        $_SESSION['wizard']['rss_url'] = $rssUrl;

        if (empty($_SESSION['wizard']['feed'])) {
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
     */
    private function processStep2SessionParams(): void
    {
        $filterTags = $this->param('filter_tags');
        if ($filterTags !== '') {
            $_SESSION['wizard']['filter_tags'] = $filterTags;
        }
        $selectedFeed = $this->param('selected_feed');
        if ($selectedFeed !== '') {
            $_SESSION['wizard']['selected_feed'] = $selectedFeed;
        }
        $maxim = $this->param('maxim');
        if ($maxim !== '') {
            $_SESSION['wizard']['maxim'] = $maxim;
        }
        if (!isset($_SESSION['wizard']['maxim'])) {
            $_SESSION['wizard']['maxim'] = 1;
        }
        $selectMode = $this->param('select_mode');
        if ($selectMode !== '') {
            $_SESSION['wizard']['select_mode'] = $selectMode;
        }
        if (!isset($_SESSION['wizard']['select_mode'])) {
            $_SESSION['wizard']['select_mode'] = '0';
        }
        $hideImages = $this->param('hide_images');
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
        $hostName = $this->param('host_name');
        $hostStatus = $this->param('host_status');
        if ($hostStatus !== '' && $hostName !== '') {
            $_SESSION['wizard']['host'][$hostName] = $hostStatus;
        }
        $nfName = $this->param('NfName');
        if ($nfName !== '') {
            $_SESSION['wizard']['feed']['feed_title'] = $nfName;
        }
    }

    /**
     * Process step 3 session parameters.
     *
     * @return void
     */
    private function processStep3SessionParams(): void
    {
        $nfName = $this->param('NfName');
        if ($nfName !== '') {
            $_SESSION['wizard']['feed']['feed_title'] = $nfName;
        }
        $nfArticleSection = $this->param('NfArticleSection');
        if ($nfArticleSection !== '') {
            $_SESSION['wizard']['article_section'] = $nfArticleSection;
        }
        $articleSelector = $this->param('article_selector');
        if ($articleSelector !== '') {
            $_SESSION['wizard']['article_selector'] = $articleSelector;
        }
        $selectedFeed = $this->param('selected_feed');
        if ($selectedFeed !== '') {
            $_SESSION['wizard']['selected_feed'] = $selectedFeed;
        }
        $articleTags = $this->param('article_tags');
        if ($articleTags !== '') {
            $_SESSION['wizard']['article_tags'] = $articleTags;
        }
        $html = $this->param('html');
        if ($html !== '') {
            $_SESSION['wizard']['filter_tags'] = $html;
        }
        $nfOptions = $this->param('NfOptions');
        if ($nfOptions !== '') {
            $_SESSION['wizard']['options'] = $nfOptions;
        }
        $nfLgId = $this->param('NfLgID');
        if ($nfLgId !== '') {
            $_SESSION['wizard']['lang'] = $nfLgId;
        }
        if (!isset($_SESSION['wizard']['article_tags'])) {
            $_SESSION['wizard']['article_tags'] = '';
        }
        $maxim = $this->param('maxim');
        if ($maxim !== '') {
            $_SESSION['wizard']['maxim'] = $maxim;
        }
        $selectMode = $this->param('select_mode');
        if ($selectMode !== '') {
            $_SESSION['wizard']['select_mode'] = $selectMode;
        }
        $hideImages = $this->param('hide_images');
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
        $hostName = $this->param('host_name');
        $hostStatus = $this->param('host_status');
        $hostStatus2 = $this->param('host_status2');
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
     */
    private function getStep2FeedHtml(): string
    {
        $i = $_SESSION['wizard']['selected_feed'];

        if (!isset($_SESSION['wizard']['feed'][$i]['html'])) {
            $aFeed[0] = $_SESSION['wizard']['feed'][$i];
            $_SESSION['wizard']['feed'][$i]['html'] = $this->feedService->extractTextFromArticle(
                $aFeed,
                $_SESSION['wizard']['redirect'] . 'new',
                'iframe!?!script!?!noscript!?!head!?!meta!?!link!?!style',
                $this->feedService->getNfOption($_SESSION['wizard']['options'], 'charset')
            );
        }

        return $_SESSION['wizard']['feed'][$i]['html'];
    }

    /**
     * Get HTML content for step 3 feed preview.
     *
     * @return string HTML content
     */
    private function getStep3FeedHtml(): string
    {
        $i = $_SESSION['wizard']['selected_feed'];

        if (!isset($_SESSION['wizard']['feed'][$i]['html'])) {
            $aFeed[0] = $_SESSION['wizard']['feed'][$i];
            $_SESSION['wizard']['feed'][$i]['html'] = $this->feedService->extractTextFromArticle(
                $aFeed,
                $_SESSION['wizard']['redirect'] . 'new',
                'iframe!?!script!?!noscript!?!head!?!meta!?!link!?!style',
                $this->feedService->getNfOption($_SESSION['wizard']['options'], 'charset')
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
     * @param array $params Route parameters
     *
     * @return void
     */
    public function spa(array $params): void
    {
        PageLayoutHelper::renderPageStart('Feed Manager', true);
        include __DIR__ . '/../Views/Feed/spa.php';
        PageLayoutHelper::renderPageEnd();
    }
}

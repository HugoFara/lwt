<?php

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

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../Core/UI/ui_helpers.php';
require_once __DIR__ . '/../Core/Tag/tags.php';
require_once __DIR__ . '/../Core/Feed/feeds.php';
require_once __DIR__ . '/../Core/Text/text_helpers.php';
require_once __DIR__ . '/../Core/Http/param_helpers.php';
require_once __DIR__ . '/../Core/Media/media_helpers.php';
require_once __DIR__ . '/../Core/Language/language_utilities.php';

use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Settings;
use Lwt\Database\Validation;
use Lwt\Services\FeedService;

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
     * Constructor - initialize feed service.
     */
    public function __construct()
    {
        parent::__construct();
        $this->feedService = new FeedService();
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
            (string)\processDBParam("filterlang", 'currentlanguage', '', false)
        );
        \pagestart('My ' . \getLanguage($currentLang) . ' Feeds', true);

        $currentFeed = (string)\processSessParam(
            "selected_feed",
            "currentrssfeed",
            '',
            false
        );

        $editText = 0;
        $message = '';

        // Handle marked items submission
        if (isset($_REQUEST['marked_items']) && is_array($_REQUEST['marked_items'])) {
            $result = $this->processMarkedItems();
            $editText = $result['editText'];
            $message = $result['message'];
        }

        // Display messages
        $this->displayFeedMessages($message);

        // Route based on action
        if (
            isset($_REQUEST['load_feed']) || isset($_REQUEST['check_autoupdate'])
            || (isset($_REQUEST['markaction']) && $_REQUEST['markaction'] == 'update')
        ) {
            \load_feeds((int)$currentFeed);
        } elseif (empty($editText)) {
            $this->renderFeedsIndex((int)$currentLang, (int)$currentFeed);
        }

        \pageend();
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

        if (!isset($_REQUEST['marked_items']) || !is_array($_REQUEST['marked_items'])) {
            return ['editText' => $editText, 'message' => $message];
        }

        $markedItems = implode(',', array_filter($_REQUEST['marked_items'], 'is_scalar'));
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

            $texts = \get_text_from_rsslink(
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

        ?>
<script type="text/javascript">
$(".hide_message").delay(2500).slideUp(1000);
</script>
        <?php

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

        \get_texttags(1);

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
        if (isset($_REQUEST['checked_feeds_save'])) {
            $message = \write_rss_to_db($_REQUEST['feed']);
            ?>
    <script type="text/javascript">
    $(".hide_message").delay(2500).slideUp(1000);
    </script>
            <?php
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
            ?>
    <script type="text/javascript">
    $(".hide_message").delay(2500).slideUp(1000);
    </script>
            <?php
            unset($_SESSION['feed_loaded']);
        }

        echo \error_message_with_hide($message, false);
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

        $currentQuery = (string)\processSessParam("query", "currentrssquery", '', false);
        $currentQueryMode = (string)\processSessParam("query_mode", "currentrssquerymode", 'title,desc,text', false);
        $currentRegexMode = Settings::getWithDefault("set-regex-mode");

        $whQuery = $this->feedService->buildQueryFilter($currentQuery, $currentQueryMode, $currentRegexMode);

        if (!empty($currentQuery) && !empty($currentRegexMode)) {
            if (!$this->feedService->validateRegexPattern($currentQuery)) {
                $currentQuery = '';
                $whQuery = '';
                unset($_SESSION['currentwordquery']);
                if (isset($_REQUEST['query'])) {
                    echo '<p id="hide3" style="color:red;text-align:center;">+++ Warning: Invalid Search +++</p>';
                }
            }
        }

        $currentPage = (int)\processSessParam("page", "currentrsspage", '1', true);
        $currentSort = (int)\processDBParam("sort", 'currentrsssort', '2', true);

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

        \pagestart('Manage ' . \getLanguage($currentLang) . ' Feeds', true);

        // Clear wizard session if exists
        if (isset($_SESSION['wizard'])) {
            unset($_SESSION['wizard']);
        }

        // Handle mark actions (delete, delete articles, reset articles)
        $message = $this->handleMarkAction($currentFeed);
        if (!empty($message)) {
            echo \error_message_with_hide($message, false);
        }

        // Display session messages from feed loading
        $this->displaySessionMessages();

        // Handle form submissions
        $this->handleUpdateFeed();
        $this->handleSaveFeed();

        // Route to appropriate view
        if (
            isset($_REQUEST['load_feed']) || isset($_REQUEST['check_autoupdate'])
            || (isset($_REQUEST['markaction']) && $_REQUEST['markaction'] == 'update')
        ) {
            \load_feeds((int)$currentFeed);
        } elseif (isset($_REQUEST['new_feed'])) {
            $this->showNewForm((int)$currentLang);
        } elseif (isset($_REQUEST['edit_feed'])) {
            $this->showEditForm((int)$currentFeed);
        } elseif (isset($_REQUEST['multi_load_feed'])) {
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

        \pageend();
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
        if (!isset($_REQUEST['markaction']) || empty($currentFeed)) {
            return '';
        }

        $action = $_REQUEST['markaction'];

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
        if (!isset($_REQUEST['update_feed'])) {
            return;
        }

        $feedId = (int)$_REQUEST['NfID'];

        $data = [
            'NfLgID' => $_REQUEST['NfLgID'] ?? '',
            'NfName' => $_REQUEST['NfName'] ?? '',
            'NfSourceURI' => $_REQUEST['NfSourceURI'] ?? '',
            'NfArticleSectionTags' => $_REQUEST['NfArticleSectionTags'] ?? '',
            'NfFilterTags' => $_REQUEST['NfFilterTags'] ?? '',
            'NfOptions' => rtrim($_REQUEST['NfOptions'] ?? '', ','),
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
        if (!isset($_REQUEST['save_feed'])) {
            return;
        }

        $data = [
            'NfLgID' => $_REQUEST['NfLgID'] ?? '',
            'NfName' => $_REQUEST['NfName'] ?? '',
            'NfSourceURI' => $_REQUEST['NfSourceURI'] ?? '',
            'NfArticleSectionTags' => $_REQUEST['NfArticleSectionTags'] ?? '',
            'NfFilterTags' => $_REQUEST['NfFilterTags'] ?? '',
            'NfOptions' => rtrim($_REQUEST['NfOptions'] ?? '', ','),
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
        ?>
<script type="text/javascript">
$(".hide_message").delay(2500).slideUp(1000);
</script>
        <?php
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

        include __DIR__ . '/../Views/Feed/index.php';
    }

    /**
     * Feed wizard page (replaces feeds_wizard.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function wizard(array $params): void
    {
        require_once __DIR__ . '/../Legacy/feeds_wizard.php';
        \Lwt\Interface\Feed_Wizard\doPage($this->feedService);
    }
}

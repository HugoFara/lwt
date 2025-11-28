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
require_once __DIR__ . '/../Core/Feed/feeds.php';
require_once __DIR__ . '/../Core/Http/param_helpers.php';
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
     * Feeds index page (replaces feeds_index.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        require_once __DIR__ . '/../Legacy/feeds_index.php';
        \Lwt\Interface\Do_Feeds\do_page($this->feedService);
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

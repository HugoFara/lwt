<?php

declare(strict_types=1);

namespace Lwt\Modules\Feed\Http;

use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Modules\Feed\Infrastructure\FeedWizardSessionManager;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\Validation;
use Lwt\Shared\Infrastructure\Http\FlashMessageService;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

/**
 * Controller for feed CRUD operations.
 *
 * Handles feed creation, editing, deletion, and the management list.
 *
 * @since 3.0.0
 */
class FeedEditController
{
    use FeedFlashTrait;

    private string $viewPath;
    private FeedFacade $feedFacade;
    private LanguageFacade $languageFacade;
    private FeedWizardSessionManager $wizardSession;
    private FlashMessageService $flashService;

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
            PageLayoutHelper::renderMessage($message);
        }

        // Display session messages from feed loading
        $this->renderFlashMessages($this->flashService);

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
                '/feeds/edit'
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
     * Feeds SPA page - modern Alpine.js single page application.
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

    /**
     * New feed form (wizard with 3 tabs: Browse, URL Wizard, Manual).
     *
     * Route: GET/POST /feeds/new
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function newFeed(array $params): void
    {
        // Handle form submission before any output
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
            $this->redirect(url('/feeds/' . $feedId . '/edit'));
            return;
        }

        // Clear wizard session if exists (must be before any output)
        if ($this->wizardSession->exists()) {
            $this->wizardSession->clear();
        }

        PageLayoutHelper::renderPageStart('Add a Feed', true);

        $this->showNewForm();
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
            $this->redirect(url('/feeds/manage'));
            return;
        }

        // Handle form submission before any output
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
            $this->redirect(url('/feeds/manage'));
            return;
        }

        $langName = $this->languageFacade->getLanguageName($feed['NfLgID']);
        PageLayoutHelper::renderPageStart('Edit Feed - ' . $langName, true);

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

        $this->redirect(url('/feeds/manage'));
    }

    /**
     * Send a redirect response.
     *
     * Extracted to allow tests to override and prevent exit().
     *
     * @param string $url Target URL
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
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
     * Show the new feed form (wizard step 1 with 3 tabs).
     *
     * @return void
     *
     * @psalm-suppress UnresolvableInclude View path is constructed at runtime
     */
    private function showNewForm(): void
    {
        $errorMessage = InputValidator::has('err') ? true : null;
        $rssUrl = null;
        $editFeedId = null;
        $languages = $this->languageFacade->getLanguagesForSelect();
        $curatedFeeds = $this->loadCuratedFeeds();
        $currentLanguageId = (int) Settings::get('currentlanguage');
        $currentLanguageName = $this->languageFacade->getLanguageName($currentLanguageId);

        include $this->viewPath . 'wizard_step1.php';
    }

    /**
     * Load curated feeds from the JSON registry.
     *
     * @return list<array<string, mixed>>
     */
    private function loadCuratedFeeds(): array
    {
        $path = dirname(__DIR__, 4) . '/data/curated_feeds.json';
        if (!file_exists($path)) {
            return [];
        }
        $json = file_get_contents($path);
        if ($json === false) {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['feeds'])) {
            return [];
        }
        /** @var list<array<string, mixed>> */
        $feeds = $data['feeds'];
        return $feeds;
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
}

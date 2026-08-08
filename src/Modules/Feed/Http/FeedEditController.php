<?php

declare(strict_types=1);

namespace Lwt\Modules\Feed\Http;

use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Modules\Feed\Infrastructure\FeedWizardSessionManager;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Http\FlashMessageService;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\Infrastructure\Language\CurrentLanguage;
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
            $this->flashService->success(__('feed.flash.created'));
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
            $this->flashService->error(__('feed.flash.not_found'));
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
            $this->flashService->success(__('feed.flash.updated'));
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
            $this->flashService->success(__('feed.flash.deleted'));
        } else {
            $this->flashService->error(__('feed.flash.delete_failed'));
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
        // Resolves the unset-'currentlanguage' case centrally; without a
        // language the curated-feed wizard posts NfLgID=0 and the server
        // rejects with 500.
        $currentLanguageId = CurrentLanguage::resolveId();
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
        $options = $this->feedFacade->getFeedOption($feed['NfOptions'], '');
        if (!is_array($options)) {
            $options = [];
        }

        // Parse auto-update interval
        $autoUpdateRaw = $this->feedFacade->getFeedOption($feed['NfOptions'], 'autoupdate');
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
}

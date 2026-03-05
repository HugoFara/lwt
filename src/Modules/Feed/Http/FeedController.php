<?php

/**
 * Feed Controller (Facade)
 *
 * Thin facade delegating to FeedIndexController, FeedEditController,
 * and FeedLoadController. Maintained for backward compatibility with
 * existing route registrations.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Feed\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Feed\Http;

use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Modules\Feed\Infrastructure\FeedWizardSessionManager;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Http\FlashMessageService;

/**
 * Facade controller delegating to specialized sub-controllers.
 *
 * @since 3.0.0
 */
class FeedController
{
    private FeedFacade $feedFacade;
    private FeedIndexController $indexController;
    private FeedEditController $editController;
    private FeedLoadController $loadController;

    public function __construct(
        FeedFacade $feedFacade,
        LanguageFacade $languageFacade,
        ?FeedWizardSessionManager $wizardSession = null,
        ?FlashMessageService $flashService = null
    ) {
        $this->feedFacade = $feedFacade;
        $this->indexController = new FeedIndexController(
            $feedFacade,
            $languageFacade,
            $flashService
        );
        $this->editController = new FeedEditController(
            $feedFacade,
            $languageFacade,
            $wizardSession,
            $flashService
        );
        $this->loadController = new FeedLoadController(
            $feedFacade,
            $languageFacade
        );
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

    // =========================================================================
    // Delegated Route Handlers
    // =========================================================================

    /** @param array<string, string> $params */
    public function index(array $params): void
    {
        $this->indexController->index($params);
    }

    /** @param array<string, string> $params */
    public function edit(array $params): void
    {
        $this->editController->edit($params);
    }

    /** @param array<string, string> $params */
    public function spa(array $params): void
    {
        $this->editController->spa($params);
    }

    /** @param array<string, string> $params */
    public function newFeed(array $params): void
    {
        $this->editController->newFeed($params);
    }

    public function editFeed(int $id): void
    {
        $this->editController->editFeed($id);
    }

    public function deleteFeed(int $id): void
    {
        $this->editController->deleteFeed($id);
    }

    public function loadFeedRoute(int $id): void
    {
        $this->loadController->loadFeedRoute($id);
    }

    /** @param array<string, string> $params */
    public function multiLoad(array $params): void
    {
        $this->loadController->multiLoad($params);
    }

    /**
     * Render feed load interface (used by renderFeedLoadInterfaceModern delegation).
     */
    public function renderFeedLoadInterface(
        int $currentFeed,
        bool $checkAutoupdate,
        string $redirectUrl
    ): void {
        $this->loadController->renderFeedLoadInterface($currentFeed, $checkAutoupdate, $redirectUrl);
    }
}

<?php

declare(strict_types=1);

namespace Lwt\Modules\Feed\Http;

use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Database\Validation;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

/**
 * Controller for feed loading/importing operations.
 *
 * Handles feed loading, multi-load interface, and the
 * renderFeedLoadInterface method for Alpine.js feed loader.
 *
 * @since 3.0.0
 */
class FeedLoadController
{
    private string $viewPath;
    private FeedFacade $feedFacade;
    private LanguageFacade $languageFacade;

    public function __construct(
        FeedFacade $feedFacade,
        LanguageFacade $languageFacade
    ) {
        $this->viewPath = __DIR__ . '/../Views/';
        $this->feedFacade = $feedFacade;
        $this->languageFacade = $languageFacade;
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
        $config = $this->feedFacade->getFeedLoadConfig($currentFeed, $checkAutoupdate);

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
}

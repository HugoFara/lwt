<?php

/**
 * API V1 Entry Point.
 *
 * This file provides a cleaner structure for the API while maintaining
 * backward compatibility with the original api_v1.php.
 *
 * Usage: Include this file instead of api_v1.php, or call ApiV1::handleRequest()
 * after setting up the bootstrap.
 *
 * @category Lwt
 * @package  Lwt\Api\V1
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Api\V1;

// Load required dependencies for API handlers
require_once __DIR__ . '/../../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../Core/UI/ui_helpers.php';
require_once __DIR__ . '/../../Core/Text/text_helpers.php';
require_once __DIR__ . '/../../Core/Test/test_helpers.php';
require_once __DIR__ . '/../../Core/Http/param_helpers.php';
require_once __DIR__ . '/../../Core/Word/word_status.php';
require_once __DIR__ . '/../../Core/Word/dictionary_links.php';
require_once __DIR__ . '/../../Core/Media/media_helpers.php';
require_once __DIR__ . '/../../Core/Text/simterms.php';
require_once __DIR__ . '/../../Services/LanguageService.php';
require_once __DIR__ . '/../../Services/LanguageDefinitions.php';

use Lwt\Api\V1\Handlers\FeedHandler;
use Lwt\Api\V1\Handlers\ImportHandler;
use Lwt\Api\V1\Handlers\ImprovedTextHandler;
use Lwt\Api\V1\Handlers\LanguageHandler;
use Lwt\Api\V1\Handlers\MediaHandler;
use Lwt\Api\V1\Handlers\ReviewHandler;
use Lwt\Api\V1\Handlers\SettingsHandler;
use Lwt\Api\V1\Handlers\StatisticsHandler;
use Lwt\Api\V1\Handlers\TermHandler;
use Lwt\Api\V1\Handlers\TextHandler;

/**
 * Main API V1 handler class.
 */
class ApiV1
{
    private const VERSION = "0.1.1";
    private const RELEASE_DATE = "2023-12-29";

    private FeedHandler $feedHandler;
    private ImportHandler $importHandler;
    private ImprovedTextHandler $improvedTextHandler;
    private LanguageHandler $languageHandler;
    private MediaHandler $mediaHandler;
    private ReviewHandler $reviewHandler;
    private SettingsHandler $settingsHandler;
    private StatisticsHandler $statisticsHandler;
    private TermHandler $termHandler;
    private TextHandler $textHandler;

    public function __construct()
    {
        $this->feedHandler = new FeedHandler();
        $this->importHandler = new ImportHandler();
        $this->improvedTextHandler = new ImprovedTextHandler();
        $this->languageHandler = new LanguageHandler();
        $this->mediaHandler = new MediaHandler();
        $this->reviewHandler = new ReviewHandler();
        $this->settingsHandler = new SettingsHandler();
        $this->statisticsHandler = new StatisticsHandler();
        $this->termHandler = new TermHandler();
        $this->textHandler = new TextHandler();
    }

    /**
     * Handle the incoming API request.
     *
     * @param string     $method   HTTP method
     * @param string     $uri      Request URI
     * @param array|null $postData POST data
     *
     * @return void
     */
    public function handle(string $method, string $uri, ?array $postData): void
    {
        $endpoint = Endpoints::resolve($method, $uri);
        $fragments = Endpoints::parseFragments($endpoint);

        if ($method === 'GET') {
            $this->handleGet($fragments, $this->parseQueryParams($uri));
        } elseif ($method === 'POST') {
            $this->handlePost($fragments, $postData ?? []);
        }
    }

    /**
     * Handle GET requests.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    Query parameters
     */
    private function handleGet(array $fragments, array $params): void
    {
        switch ($fragments[0]) {
            case 'version':
                Response::success([
                    "version" => self::VERSION,
                    "release_date" => self::RELEASE_DATE
                ]);
                break;

            case 'media-files':
                Response::success($this->mediaHandler->formatMediaFiles());
                break;

            case 'phonetic-reading':
                Response::success($this->languageHandler->formatPhoneticReading($params));
                break;

            case 'languages':
                $this->handleLanguagesGet($fragments);
                break;

            case 'review':
                $this->handleReviewGet($fragments, $params);
                break;

            case 'sentences-with-term':
                $this->handleSentencesGet($fragments, $params);
                break;

            case 'similar-terms':
                Response::success($this->languageHandler->formatSimilarTerms(
                    (int)$params["lg_id"],
                    (string)$params["term"]
                ));
                break;

            case 'settings':
                $this->handleSettingsGet($fragments, $params);
                break;

            case 'terms':
                $this->handleTermsGet($fragments, $params);
                break;

            case 'texts':
                $this->handleTextsGet($fragments, $params);
                break;

            default:
                Response::error('Endpoint Not Found: ' . $fragments[0], 404);
        }
    }

    /**
     * Handle POST requests.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    POST parameters
     */
    private function handlePost(array $fragments, array $params): void
    {
        switch ($fragments[0]) {
            case 'settings':
                Response::success($this->settingsHandler->formatSaveSetting(
                    $params['key'],
                    $params['value']
                ));
                break;

            case 'texts':
                $this->handleTextsPost($fragments, $params);
                break;

            case 'terms':
                $this->handleTermsPost($fragments, $params);
                break;

            case 'feeds':
                $this->handleFeedsPost($fragments, $params);
                break;

            default:
                Response::error('Endpoint Not Found On POST: ' . $fragments[0], 404);
        }
    }

    // =========================================================================
    // GET Request Handlers
    // =========================================================================

    private function handleLanguagesGet(array $fragments): void
    {
        if (!isset($fragments[1]) || !ctype_digit($fragments[1])) {
            Response::error('Expected Language ID', 404);
        }
        if (($fragments[2] ?? '') !== 'reading-configuration') {
            Response::error('Expected "reading-configuration"', 404);
        }

        Response::success($this->languageHandler->formatReadingConfiguration(
            (int)$fragments[1]
        ));
    }

    private function handleReviewGet(array $fragments, array $params): void
    {
        switch ($fragments[1] ?? '') {
            case 'next-word':
                Response::success($this->reviewHandler->formatNextWord($params));
                break;
            case 'tomorrow-count':
                Response::success($this->reviewHandler->formatTomorrowCount($params));
                break;
            default:
                Response::error('Endpoint Not Found: ' . ($fragments[1] ?? ''), 404);
        }
    }

    private function handleSentencesGet(array $fragments, array $params): void
    {
        if (isset($fragments[1]) && ctype_digit($fragments[1])) {
            Response::success($this->languageHandler->formatSentencesWithRegisteredTerm(
                (int)$params["lg_id"],
                $params["word_lc"],
                (int)$fragments[1]
            ));
        } else {
            Response::success($this->languageHandler->formatSentencesWithNewTerm(
                (int)$params["lg_id"],
                $params["word_lc"],
                array_key_exists("advanced_search", $params)
            ));
        }
    }

    private function handleSettingsGet(array $fragments, array $params): void
    {
        if (($fragments[1] ?? '') === 'theme-path') {
            Response::success($this->settingsHandler->formatThemePath($params['path']));
        } else {
            Response::error('Endpoint Not Found: ' . ($fragments[1] ?? ''), 404);
        }
    }

    private function handleTermsGet(array $fragments, array $params): void
    {
        if (($fragments[1] ?? '') === 'imported') {
            Response::success($this->importHandler->formatImportedTerms(
                $params["last_update"],
                (int)$params["page"],
                (int)$params["count"]
            ));
        } elseif (isset($fragments[1]) && ctype_digit($fragments[1])) {
            if (($fragments[2] ?? '') === 'translations') {
                Response::success($this->improvedTextHandler->formatTermTranslations(
                    (string)$params["term_lc"],
                    (int)$params["text_id"]
                ));
            } else {
                Response::error('Expected "translations"', 404);
            }
        } else {
            Response::error('Endpoint Not Found: ' . ($fragments[1] ?? ''), 404);
        }
    }

    private function handleTextsGet(array $fragments, array $params): void
    {
        if (($fragments[1] ?? '') === 'statistics') {
            Response::success($this->statisticsHandler->formatTextsStatistics(
                $params["texts_id"]
            ));
        } else {
            Response::error('Expected "statistics"', 404);
        }
    }

    // =========================================================================
    // POST Request Handlers
    // =========================================================================

    private function handleTextsPost(array $fragments, array $params): void
    {
        if (!isset($fragments[1]) || !ctype_digit($fragments[1])) {
            Response::error('Text ID (Integer) Expected', 404);
        }

        $textId = (int)$fragments[1];

        switch ($fragments[2] ?? '') {
            case 'annotation':
                Response::success($this->textHandler->formatSetAnnotation(
                    $textId,
                    $params['elem'],
                    $params['data']
                ));
                break;
            case 'audio-position':
                Response::success($this->textHandler->formatSetAudioPosition(
                    $textId,
                    (int)$params['position']
                ));
                break;
            case 'reading-position':
                Response::success($this->textHandler->formatSetTextPosition(
                    $textId,
                    (int)$params['position']
                ));
                break;
            default:
                Response::error('Endpoint Not Found: ' . ($fragments[2] ?? ''), 404);
        }
    }

    private function handleTermsPost(array $fragments, array $params): void
    {
        if (isset($fragments[1]) && ctype_digit($fragments[1])) {
            $termId = (int)$fragments[1];

            if (($fragments[2] ?? '') === 'status') {
                $this->handleTermStatusPost($fragments, $termId);
            } elseif (($fragments[2] ?? '') === 'translations') {
                Response::success($this->termHandler->formatUpdateTranslation(
                    $termId,
                    $params['translation']
                ));
            } else {
                Response::error('"status" or "translations" Expected', 404);
            }
        } elseif (($fragments[1] ?? '') === 'new') {
            Response::success($this->termHandler->formatAddTranslation(
                $params['term_text'],
                (int)$params['lg_id'],
                $params['translation']
            ));
        } else {
            Response::error('Term ID (Integer) or "new" Expected', 404);
        }
    }

    private function handleTermStatusPost(array $fragments, int $termId): void
    {
        switch ($fragments[3] ?? '') {
            case 'down':
                Response::success($this->termHandler->formatIncrementStatus($termId, false));
                break;
            case 'up':
                Response::success($this->termHandler->formatIncrementStatus($termId, true));
                break;
            default:
                if (ctype_digit($fragments[3] ?? '')) {
                    Response::success($this->termHandler->formatSetStatus(
                        $termId,
                        (int)$fragments[3]
                    ));
                } else {
                    Response::error('Endpoint Not Found: ' . ($fragments[3] ?? ''), 404);
                }
        }
    }

    private function handleFeedsPost(array $fragments, array $params): void
    {
        if (!isset($fragments[1]) || !ctype_digit($fragments[1])) {
            Response::error('Feed ID (Integer) Expected', 404);
        }

        if (($fragments[2] ?? '') === 'load') {
            Response::success($this->feedHandler->formatLoadFeed(
                $params['name'],
                (int)$fragments[1],
                $params['source_uri'],
                $params['options']
            ));
        } else {
            Response::error('Expected "load"', 404);
        }
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function parseQueryParams(string $uri): array
    {
        $query = parse_url($uri, PHP_URL_QUERY);
        if ($query === null) {
            return [];
        }
        parse_str($query, $params);
        return $params;
    }

    /**
     * Static entry point for handling requests.
     *
     * @return void
     */
    public static function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method Not Allowed', 405);
        }

        $api = new self();
        $api->handle(
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI'],
            $_POST
        );
    }
}

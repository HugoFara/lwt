<?php declare(strict_types=1);
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
require_once __DIR__ . '/../../Services/TextStatisticsService.php';
require_once __DIR__ . '/../../Services/SentenceService.php';
require_once __DIR__ . '/../../Services/AnnotationService.php';
require_once __DIR__ . '/../../Services/SimilarTermsService.php';
require_once __DIR__ . '/../../Services/TextNavigationService.php';
require_once __DIR__ . '/../../Services/TextParsingService.php';
require_once __DIR__ . '/../../Services/ExpressionService.php';
require_once __DIR__ . '/../../Core/Database/Restore.php';
require_once __DIR__ . '/../../Core/Http/param_helpers.php';
require_once __DIR__ . '/../../Services/WordStatusService.php';
require_once __DIR__ . '/../../Services/DictionaryService.php';
require_once __DIR__ . '/../../Services/MediaService.php';
require_once __DIR__ . '/../../Services/LanguageService.php';
require_once __DIR__ . '/../../Services/LanguageDefinitions.php';
require_once __DIR__ . '/../../Services/TagService.php';

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
     * @param array|null $postData POST data (also used for PUT/DELETE with JSON body)
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
        } elseif ($method === 'PUT') {
            $this->handlePut($fragments, $postData ?? []);
        } elseif ($method === 'DELETE') {
            $this->handleDelete($fragments, $this->parseQueryParams($uri));
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

            case 'statuses':
                Response::success(\Lwt\Services\WordStatusService::getStatuses());
                break;

            case 'tags':
                $this->handleTagsGet($fragments);
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
        // Handle /languages/with-texts - returns languages that have texts with counts
        if (($fragments[1] ?? '') === 'with-texts') {
            Response::success($this->languageHandler->formatLanguagesWithTexts());
            return;
        }

        // Handle /languages/with-archived-texts - returns languages that have archived texts with counts
        if (($fragments[1] ?? '') === 'with-archived-texts') {
            Response::success($this->languageHandler->formatLanguagesWithArchivedTexts());
            return;
        }

        if (!isset($fragments[1]) || !ctype_digit($fragments[1])) {
            Response::error('Expected Language ID, "with-texts", or "with-archived-texts"', 404);
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
            case 'config':
                Response::success($this->reviewHandler->formatTestConfig($params));
                break;
            case 'table-words':
                Response::success($this->reviewHandler->formatTableWords($params));
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

    private function handleTagsGet(array $fragments): void
    {
        switch ($fragments[1] ?? '') {
            case 'term':
                Response::success(\Lwt\Services\TagService::getAllTermTags());
                break;
            case 'text':
                Response::success(\Lwt\Services\TagService::getAllTextTags());
                break;
            default:
                // Return both tag types
                Response::success([
                    'term' => \Lwt\Services\TagService::getAllTermTags(),
                    'text' => \Lwt\Services\TagService::getAllTextTags()
                ]);
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
        } elseif (($fragments[1] ?? '') === 'for-edit') {
            // GET /terms/for-edit - get term data for editing in modal
            Response::success($this->termHandler->formatGetTermForEdit(
                (int)($params['tid'] ?? 0),
                (int)($params['ord'] ?? 0),
                isset($params['wid']) && $params['wid'] !== '' ? (int)$params['wid'] : null
            ));
        } elseif (($fragments[1] ?? '') === 'multi') {
            // GET /terms/multi - get multi-word expression data for editing
            Response::success($this->termHandler->formatGetMultiWord(
                (int)($params['tid'] ?? 0),
                (int)($params['ord'] ?? 0),
                $params['txt'] ?? null,
                isset($params['wid']) ? (int)$params['wid'] : null
            ));
        } elseif (isset($fragments[1]) && ctype_digit($fragments[1])) {
            $termId = (int)$fragments[1];
            if (($fragments[2] ?? '') === 'translations') {
                Response::success($this->improvedTextHandler->formatTermTranslations(
                    (string)$params["term_lc"],
                    (int)$params["text_id"]
                ));
            } elseif (($fragments[2] ?? '') === 'details') {
                // GET /terms/{id}/details - get term details with sentence and tags
                Response::success($this->termHandler->formatGetTermDetails(
                    $termId,
                    $params['ann'] ?? null
                ));
            } elseif (!isset($fragments[2])) {
                // GET /terms/{id} - get term by ID
                Response::success($this->termHandler->formatGetTerm($termId));
            } else {
                Response::error('Expected "translations", "details", or no sub-path', 404);
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
        } elseif (($fragments[1] ?? '') === 'by-language') {
            // GET /texts/by-language/{langId} - get paginated texts for a language
            if (!isset($fragments[2]) || !ctype_digit($fragments[2])) {
                Response::error('Expected Language ID after "by-language"', 404);
            }
            Response::success($this->textHandler->formatTextsByLanguage(
                (int)$fragments[2],
                $params
            ));
        } elseif (($fragments[1] ?? '') === 'archived-by-language') {
            // GET /texts/archived-by-language/{langId} - get paginated archived texts for a language
            if (!isset($fragments[2]) || !ctype_digit($fragments[2])) {
                Response::error('Expected Language ID after "archived-by-language"', 404);
            }
            Response::success($this->textHandler->formatArchivedTextsByLanguage(
                (int)$fragments[2],
                $params
            ));
        } elseif (isset($fragments[1]) && ctype_digit($fragments[1])) {
            $textId = (int)$fragments[1];
            if (($fragments[2] ?? '') === 'words') {
                // GET /texts/{id}/words - get all words for client-side rendering
                Response::success($this->textHandler->formatGetWords($textId));
            } else {
                Response::error('Expected "words"', 404);
            }
        } else {
            Response::error('Expected "statistics", "by-language", "archived-by-language", or text ID', 404);
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
        } elseif (($fragments[1] ?? '') === 'quick') {
            // POST /terms/quick - quick create term with status (98 or 99)
            Response::success($this->termHandler->formatQuickCreate(
                (int)$params['textId'],
                (int)$params['position'],
                (int)$params['status']
            ));
        } elseif (($fragments[1] ?? '') === 'full') {
            // POST /terms/full - create term with full data
            Response::success($this->termHandler->formatCreateTermFull($params));
        } elseif (($fragments[1] ?? '') === 'multi') {
            // POST /terms/multi - create multi-word expression
            Response::success($this->termHandler->formatCreateMultiWord($params));
        } else {
            Response::error('Term ID (Integer), "new", "quick", or "multi" Expected', 404);
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
    // PUT Request Handlers
    // =========================================================================

    /**
     * Handle PUT requests.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    Request body parameters
     */
    private function handlePut(array $fragments, array $params): void
    {
        switch ($fragments[0]) {
            case 'review':
                $this->handleReviewPut($fragments, $params);
                break;

            case 'terms':
                $this->handleTermsPut($fragments, $params);
                break;

            case 'texts':
                $this->handleTextsPut($fragments, $params);
                break;

            default:
                Response::error('Endpoint Not Found On PUT: ' . $fragments[0], 404);
        }
    }

    private function handleReviewPut(array $fragments, array $params): void
    {
        if (($fragments[1] ?? '') === 'status') {
            // PUT /review/status - update word status during review
            Response::success($this->reviewHandler->formatUpdateStatus($params));
        } else {
            Response::error('Expected "status"', 404);
        }
    }

    private function handleTermsPut(array $fragments, array $params): void
    {
        if (($fragments[1] ?? '') === 'bulk-status') {
            // PUT /terms/bulk-status - bulk update term statuses
            $termIds = $params['term_ids'] ?? [];
            $status = (int)($params['status'] ?? 0);
            Response::success($this->termHandler->formatBulkStatus($termIds, $status));
        } elseif (($fragments[1] ?? '') === 'multi' && isset($fragments[2]) && ctype_digit($fragments[2])) {
            // PUT /terms/multi/{id} - update multi-word expression
            $termId = (int)$fragments[2];
            Response::success($this->termHandler->formatUpdateMultiWord($termId, $params));
        } elseif (isset($fragments[1]) && ctype_digit($fragments[1])) {
            $termId = (int)$fragments[1];
            if (($fragments[2] ?? '') === 'translation') {
                // PUT /terms/{id}/translation - update translation
                Response::success($this->termHandler->formatUpdateTranslation(
                    $termId,
                    $params['translation'] ?? ''
                ));
            } elseif (!isset($fragments[2])) {
                // PUT /terms/{id} - update term with full data
                Response::success($this->termHandler->formatUpdateTermFull($termId, $params));
            } else {
                Response::error('Expected "translation" or no sub-path', 404);
            }
        } else {
            Response::error('Term ID (Integer), "bulk-status", or "multi/{id}" Expected', 404);
        }
    }

    private function handleTextsPut(array $fragments, array $params): void
    {
        if (!isset($fragments[1]) || !ctype_digit($fragments[1])) {
            Response::error('Text ID (Integer) Expected', 404);
        }

        $textId = (int)$fragments[1];

        switch ($fragments[2] ?? '') {
            case 'display-mode':
                // PUT /texts/{id}/display-mode - set display mode settings
                Response::success($this->textHandler->formatSetDisplayMode($textId, $params));
                break;
            case 'mark-all-wellknown':
                // PUT /texts/{id}/mark-all-wellknown - mark all unknown as well-known
                Response::success($this->textHandler->formatMarkAllWellKnown($textId));
                break;
            case 'mark-all-ignored':
                // PUT /texts/{id}/mark-all-ignored - mark all unknown as ignored
                Response::success($this->textHandler->formatMarkAllIgnored($textId));
                break;
            default:
                Response::error('Expected "display-mode", "mark-all-wellknown", or "mark-all-ignored"', 404);
        }
    }

    // =========================================================================
    // DELETE Request Handlers
    // =========================================================================

    /**
     * Handle DELETE requests.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    Query parameters (reserved for future use)
     *
     * @psalm-suppress UnusedParam
     */
    private function handleDelete(array $fragments, array $params): void
    {
        switch ($fragments[0]) {
            case 'terms':
                $this->handleTermsDelete($fragments);
                break;

            default:
                Response::error('Endpoint Not Found On DELETE: ' . $fragments[0], 404);
        }
    }

    private function handleTermsDelete(array $fragments): void
    {
        if (!isset($fragments[1]) || !ctype_digit($fragments[1])) {
            Response::error('Term ID (Integer) Expected', 404);
        }

        $termId = (int)$fragments[1];
        Response::success($this->termHandler->formatDeleteTerm($termId));
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
     * Parse JSON body for PUT/DELETE requests.
     *
     * @return array Parsed body data
     */
    private static function parseJsonBody(): array
    {
        $input = file_get_contents('php://input');
        if (empty($input)) {
            return [];
        }
        $data = json_decode($input, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Static entry point for handling requests.
     *
     * @return void
     */
    public static function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'])) {
            Response::error('Method Not Allowed', 405);
        }

        // Get body data based on method
        $bodyData = [];
        if ($method === 'POST') {
            $bodyData = !empty($_POST) ? $_POST : self::parseJsonBody();
        } elseif (in_array($method, ['PUT', 'DELETE'])) {
            $bodyData = self::parseJsonBody();
        }

        $api = new self();
        $api->handle($method, $_SERVER['REQUEST_URI'], $bodyData);
    }
}

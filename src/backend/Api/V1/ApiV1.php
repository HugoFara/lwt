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
require_once __DIR__ . '/../../../Modules/Text/Application/Services/TextStatisticsService.php';
require_once __DIR__ . '/../../../Modules/Text/Application/Services/SentenceService.php';
require_once __DIR__ . '/../../../Modules/Text/Application/Services/AnnotationService.php';
require_once __DIR__ . '/../../../Modules/Text/Application/Services/TextNavigationService.php';
require_once __DIR__ . '/../../../Shared/Infrastructure/Database/Restore.php';
require_once __DIR__ . '/../../../Modules/Vocabulary/Application/UseCases/FindSimilarTerms.php';
require_once __DIR__ . '/../../../Modules/Vocabulary/Application/Services/ExpressionService.php';
require_once __DIR__ . '/../../../Modules/Vocabulary/Infrastructure/DictionaryAdapter.php';
require_once __DIR__ . '/../../../Modules/Admin/Application/Services/MediaService.php';
// Language module now loaded via autoloader

use Lwt\Modules\Dictionary\Http\DictionaryApiHandler;
use Lwt\Modules\Language\Http\LanguageApiHandler;
use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Modules\Feed\Http\FeedApiHandler;
use Lwt\Modules\Admin\Application\AdminFacade;
use Lwt\Modules\Admin\Http\AdminApiHandler;
use Lwt\Modules\Review\Http\ReviewApiHandler;
use Lwt\Modules\Tags\Http\TagApiHandler;
use Lwt\Modules\User\Http\UserApiHandler;
use Lwt\Modules\Vocabulary\Http\VocabularyApiHandler;
use Lwt\Modules\Text\Http\TextApiHandler;
use Lwt\Modules\Book\Http\BookApiHandler;
use Lwt\Modules\Book\Application\BookFacade;
use Lwt\Api\V1\Handlers\YouTubeApiHandler;
use Lwt\Api\V1\Handlers\NlpServiceHandler;
use Lwt\Api\V1\Handlers\WhisperApiHandler;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Container\Container;

/**
 * Main API V1 handler class.
 */
class ApiV1
{
    private const VERSION = "0.1.1";
    private const RELEASE_DATE = "2023-12-29";

    private UserApiHandler $authHandler;
    private FeedApiHandler $feedHandler;
    private LanguageApiHandler $languageHandler;
    private DictionaryApiHandler $localDictionaryHandler;
    private AdminApiHandler $adminHandler;
    private ReviewApiHandler $reviewHandler;
    private TagApiHandler $tagHandler;
    private VocabularyApiHandler $termHandler;
    private TextApiHandler $textHandler;
    private ?BookApiHandler $bookHandler = null;
    private YouTubeApiHandler $youtubeHandler;
    private NlpServiceHandler $nlpHandler;
    private WhisperApiHandler $whisperHandler;

    /**
     * Endpoints that do not require authentication.
     *
     * @var array<string, bool>
     */
    private const PUBLIC_ENDPOINTS = [
        'auth/login' => true,
        'auth/register' => true,
        'version' => true,
    ];

    public function __construct()
    {
        $this->authHandler = new UserApiHandler();
        $this->feedHandler = new FeedApiHandler(
            Container::getInstance()->get(FeedFacade::class)
        );
        $this->languageHandler = new LanguageApiHandler();
        $this->localDictionaryHandler = new DictionaryApiHandler();
        $this->adminHandler = new AdminApiHandler(
            Container::getInstance()->get(AdminFacade::class)
        );
        $this->reviewHandler = new ReviewApiHandler();
        $this->tagHandler = new TagApiHandler();
        $this->termHandler = new VocabularyApiHandler();
        $this->textHandler = new TextApiHandler();
        try {
            $this->bookHandler = new BookApiHandler(
                Container::getInstance()->get(BookFacade::class)
            );
        } catch (\Throwable $e) {
            // Book module not available
            $this->bookHandler = null;
        }
        $this->youtubeHandler = new YouTubeApiHandler();
        $this->nlpHandler = new NlpServiceHandler();
        $this->whisperHandler = new WhisperApiHandler();
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

        // Validate authentication for protected endpoints
        if (!$this->isPublicEndpoint($endpoint)) {
            $this->validateAuth();
        }

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
     * Check if an endpoint is public (does not require authentication).
     *
     * @param string $endpoint The endpoint path
     *
     * @return bool True if endpoint is public
     */
    private function isPublicEndpoint(string $endpoint): bool
    {
        // Check exact match
        if (isset(self::PUBLIC_ENDPOINTS[$endpoint])) {
            return true;
        }

        // Check if endpoint starts with 'auth/' for login/register
        // but NOT for auth/me, auth/refresh, auth/logout which need auth
        if ($endpoint === 'auth/login' || $endpoint === 'auth/register') {
            return true;
        }

        // In non-multi-user mode, all endpoints are effectively public
        if (!Globals::isMultiUserEnabled()) {
            return true;
        }

        return false;
    }

    /**
     * Validate authentication for the current request.
     *
     * Checks for Bearer token or session authentication.
     * Responds with 401 Unauthorized if authentication fails.
     *
     * @return void
     */
    private function validateAuth(): void
    {
        // Skip auth validation if multi-user mode is not enabled
        if (!Globals::isMultiUserEnabled()) {
            return;
        }

        if (!$this->authHandler->isAuthenticated()) {
            Response::error('Authentication required', 401);
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
            case 'auth':
                $this->handleAuthGet($fragments);
                break;

            case 'version':
                Response::success([
                    "version" => self::VERSION,
                    "release_date" => self::RELEASE_DATE
                ]);
                break;

            case 'media-files':
                Response::success($this->adminHandler->formatMediaFiles());
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
                    (int)($params["language_id"] ?? 0),
                    (string)$params["term"]
                ));
                break;

            case 'statuses':
                Response::success(\Lwt\Modules\Vocabulary\Application\Services\TermStatusService::getStatuses());
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

            case 'word-families':
                $this->handleWordFamiliesGet($fragments, $params);
                break;

            case 'texts':
                $this->handleTextsGet($fragments, $params);
                break;

            case 'feeds':
                $this->handleFeedsGet($fragments, $params);
                break;

            case 'books':
                $this->handleBooksGet($fragments, $params);
                break;

            case 'local-dictionaries':
                $this->handleLocalDictionariesGet($fragments, $params);
                break;

            case 'youtube':
                $this->handleYouTubeGet($fragments, $params);
                break;

            case 'tts':
                $this->handleTtsGet($fragments, $params);
                break;

            case 'whisper':
                $this->handleWhisperGet($fragments, $params);
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
            case 'auth':
                $this->handleAuthPost($fragments, $params);
                break;

            case 'settings':
                Response::success($this->adminHandler->formatSaveSetting(
                    $params['key'],
                    $params['value']
                ));
                break;

            case 'languages':
                $this->handleLanguagesPost($fragments, $params);
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

            case 'local-dictionaries':
                $this->handleLocalDictionariesPost($fragments, $params);
                break;

            case 'tts':
                $this->handleTtsPost($fragments, $params);
                break;

            case 'whisper':
                $this->handleWhisperPost($fragments, $params);
                break;

            default:
                Response::error('Endpoint Not Found On POST: ' . $fragments[0], 404);
        }
    }

    /**
     * Handle POST requests for languages.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    POST parameters
     */
    private function handleLanguagesPost(array $fragments, array $params): void
    {
        // POST /languages - create new language
        if (!isset($fragments[1]) || $fragments[1] === '') {
            Response::success($this->languageHandler->formatCreate($params));
            return;
        }

        // POST /languages/{id}/refresh - reparse texts
        if (ctype_digit($fragments[1])) {
            $langId = (int)$fragments[1];

            if (($fragments[2] ?? '') === 'refresh') {
                Response::success($this->languageHandler->formatRefresh($langId));
                return;
            }

            if (($fragments[2] ?? '') === 'set-default') {
                Response::success($this->languageHandler->formatSetDefault($langId));
                return;
            }

            Response::error('Expected "refresh" or "set-default"', 404);
        }

        Response::error('Language ID (Integer) Expected', 404);
    }

    // =========================================================================
    // Auth Request Handlers
    // =========================================================================

    /**
     * Handle GET requests for auth endpoints.
     *
     * @param string[] $fragments Endpoint path segments
     */
    private function handleAuthGet(array $fragments): void
    {
        switch ($fragments[1] ?? '') {
            case 'me':
                // GET /auth/me - get current user info
                Response::success($this->authHandler->formatMe());
                break;
            default:
                Response::error('Endpoint Not Found: auth/' . ($fragments[1] ?? ''), 404);
        }
    }

    /**
     * Handle POST requests for auth endpoints.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    POST parameters
     */
    private function handleAuthPost(array $fragments, array $params): void
    {
        switch ($fragments[1] ?? '') {
            case 'login':
                // POST /auth/login - authenticate user
                Response::success($this->authHandler->formatLogin($params));
                break;
            case 'register':
                // POST /auth/register - create new user
                Response::success($this->authHandler->formatRegister($params));
                break;
            case 'refresh':
                // POST /auth/refresh - refresh API token
                Response::success($this->authHandler->formatRefresh());
                break;
            case 'logout':
                // POST /auth/logout - invalidate token and logout
                Response::success($this->authHandler->formatLogout());
                break;
            default:
                Response::error('Endpoint Not Found: auth/' . ($fragments[1] ?? ''), 404);
        }
    }

    // =========================================================================
    // GET Request Handlers
    // =========================================================================

    private function handleLanguagesGet(array $fragments): void
    {
        // Handle /languages - list all languages with stats
        if (!isset($fragments[1]) || $fragments[1] === '') {
            Response::success($this->languageHandler->formatGetAll());
            return;
        }

        // Handle /languages/definitions - get predefined language presets
        if ($fragments[1] === 'definitions') {
            Response::success($this->languageHandler->formatGetDefinitions());
            return;
        }

        // Handle /languages/with-texts - returns languages that have texts with counts
        if ($fragments[1] === 'with-texts') {
            Response::success($this->languageHandler->formatLanguagesWithTexts());
            return;
        }

        // Handle /languages/with-archived-texts - returns languages that have archived texts with counts
        if ($fragments[1] === 'with-archived-texts') {
            Response::success($this->languageHandler->formatLanguagesWithArchivedTexts());
            return;
        }

        // Handle /languages/{id} - get single language or sub-resources
        if (!ctype_digit($fragments[1])) {
            Response::error('Expected Language ID, "definitions", "with-texts", or "with-archived-texts"', 404);
        }

        $langId = (int)$fragments[1];

        // Handle /languages/{id}/stats
        if (($fragments[2] ?? '') === 'stats') {
            Response::success($this->languageHandler->formatGetStats($langId));
            return;
        }

        // Handle /languages/{id}/reading-configuration
        if (($fragments[2] ?? '') === 'reading-configuration') {
            Response::success($this->languageHandler->formatReadingConfiguration($langId));
            return;
        }

        // Handle /languages/{id} - get single language for editing
        if (!isset($fragments[2]) || $fragments[2] === '') {
            $result = $this->languageHandler->formatGetOne($langId);
            if ($result === null) {
                Response::error('Language not found', 404);
            }
            Response::success($result);
            return;
        }

        Response::error('Expected "reading-configuration", "stats", or no sub-path', 404);
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
        $languageId = (int)($params["language_id"] ?? 0);
        $termLc = $params["term_lc"] ?? '';

        if (isset($fragments[1]) && ctype_digit($fragments[1])) {
            Response::success($this->languageHandler->formatSentencesWithRegisteredTerm(
                $languageId,
                $termLc,
                (int)$fragments[1]
            ));
        } else {
            Response::success($this->languageHandler->formatSentencesWithNewTerm(
                $languageId,
                $termLc,
                array_key_exists("advanced_search", $params)
            ));
        }
    }

    private function handleSettingsGet(array $fragments, array $params): void
    {
        if (($fragments[1] ?? '') === 'theme-path') {
            Response::success($this->adminHandler->formatThemePath($params['path']));
        } else {
            Response::error('Endpoint Not Found: ' . ($fragments[1] ?? ''), 404);
        }
    }

    private function handleTagsGet(array $fragments): void
    {
        $this->tagHandler->handleGet(array_slice($fragments, 1));
    }

    private function handleTermsGet(array $fragments, array $params): void
    {
        if (($fragments[1] ?? '') === 'list') {
            // GET /terms/list - get paginated, filtered word list
            Response::success($this->termHandler->formatGetWordList($params));
        } elseif (($fragments[1] ?? '') === 'filter-options') {
            // GET /terms/filter-options - get filter dropdown options
            $langId = isset($params['language_id']) && $params['language_id'] !== '' ? (int)$params['language_id'] : null;
            Response::success($this->termHandler->formatGetFilterOptions($langId));
        } elseif (($fragments[1] ?? '') === 'imported') {
            Response::success($this->termHandler->formatImportedTerms(
                $params["last_update"],
                (int)$params["page"],
                (int)$params["count"]
            ));
        } elseif (($fragments[1] ?? '') === 'for-edit') {
            // GET /terms/for-edit - get term data for editing in modal
            Response::success($this->termHandler->formatGetTermForEdit(
                (int)($params['term_id'] ?? 0),
                (int)($params['ord'] ?? 0),
                isset($params['wid']) && $params['wid'] !== '' ? (int)$params['wid'] : null
            ));
        } elseif (($fragments[1] ?? '') === 'multi') {
            // GET /terms/multi - get multi-word expression data for editing
            Response::success($this->termHandler->formatGetMultiWord(
                (int)($params['term_id'] ?? 0),
                (int)($params['ord'] ?? 0),
                $params['txt'] ?? null,
                isset($params['wid']) ? (int)$params['wid'] : null
            ));
        } elseif (($fragments[1] ?? '') === 'family') {
            // GET /terms/family?term_id=N - get word family for a term
            // GET /terms/family/suggestion?term_id=N&status=X - get family update suggestion
            if (($fragments[2] ?? '') === 'suggestion') {
                $termId = (int)($params['term_id'] ?? 0);
                $newStatus = (int)($params['status'] ?? 0);
                Response::success($this->termHandler->formatGetFamilyUpdateSuggestion($termId, $newStatus));
            } else {
                $termId = (int)($params['term_id'] ?? 0);
                if ($termId <= 0) {
                    Response::error('term_id is required', 400);
                }
                Response::success($this->termHandler->formatGetTermFamily($termId));
            }
        } elseif (isset($fragments[1]) && ctype_digit($fragments[1])) {
            $termId = (int)$fragments[1];
            if (($fragments[2] ?? '') === 'translations') {
                Response::success($this->textHandler->formatTermTranslations(
                    (string)$params["term_lc"],
                    (int)$params["text_id"]
                ));
            } elseif (($fragments[2] ?? '') === 'details') {
                // GET /terms/{id}/details - get term details with sentence and tags
                Response::success($this->termHandler->formatGetTermDetails(
                    $termId,
                    $params['ann'] ?? null
                ));
            } elseif (($fragments[2] ?? '') === 'family') {
                // GET /terms/{id}/family - get word family for this term
                Response::success($this->termHandler->formatGetTermFamily($termId));
            } elseif (!isset($fragments[2])) {
                // GET /terms/{id} - get term by ID
                Response::success($this->termHandler->formatGetTerm($termId));
            } else {
                Response::error('Expected "translations", "details", "family", or no sub-path', 404);
            }
        } else {
            Response::error('Endpoint Not Found: ' . ($fragments[1] ?? ''), 404);
        }
    }

    /**
     * Handle GET requests for word families.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    Query parameters
     */
    private function handleWordFamiliesGet(array $fragments, array $params): void
    {
        // GET /word-families/stats?language_id=N - get lemma statistics for a language
        if (($fragments[1] ?? '') === 'stats') {
            $langId = (int)($params['language_id'] ?? 0);
            if ($langId <= 0) {
                Response::error('language_id is required', 400);
            }
            Response::success($this->termHandler->formatGetLemmaStatistics($langId));
            return;
        }

        // GET /word-families?language_id=N - get paginated list of word families
        $langId = (int)($params['language_id'] ?? 0);
        if ($langId <= 0) {
            Response::error('language_id is required', 400);
        }

        // Check if getting family by lemma
        $lemmaLc = $params['lemma_lc'] ?? '';
        if ($lemmaLc !== '') {
            // GET /word-families?language_id=N&lemma_lc=run - get specific family
            Response::success($this->termHandler->formatGetWordFamilyByLemma($langId, $lemmaLc));
            return;
        }

        // GET /word-families?language_id=N&page=1&per_page=50 - get list of families
        Response::success($this->termHandler->formatGetWordFamilyList($langId, $params));
    }

    private function handleTextsGet(array $fragments, array $params): void
    {
        if (($fragments[1] ?? '') === 'statistics') {
            $textIds = $params["text_ids"] ?? null;
            if ($textIds === null) {
                Response::error('Missing required parameter: text_ids', 400);
                return;
            }
            Response::success($this->adminHandler->formatTextsStatistics($textIds));
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
            } elseif (($fragments[2] ?? '') === 'print-items') {
                // GET /texts/{id}/print-items - get text items for print view
                Response::success($this->textHandler->formatGetPrintItems($textId));
            } elseif (($fragments[2] ?? '') === 'annotation') {
                // GET /texts/{id}/annotation - get annotation for improved text view
                Response::success($this->textHandler->formatGetAnnotation($textId));
            } else {
                Response::error('Expected "words", "print-items", or "annotation"', 404);
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
                (int)($params['language_id'] ?? 0),
                $params['translation']
            ));
        } elseif (($fragments[1] ?? '') === 'quick') {
            // POST /terms/quick - quick create term with status (98 or 99)
            Response::success($this->termHandler->formatQuickCreate(
                (int)$params['text_id'],
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
        // POST /feeds/articles/import - import articles as texts
        if (($fragments[1] ?? '') === 'articles' && ($fragments[2] ?? '') === 'import') {
            Response::success($this->feedHandler->formatImportArticles($params));
            return;
        }

        // POST /feeds - create new feed
        if (!isset($fragments[1]) || $fragments[1] === '') {
            Response::success($this->feedHandler->formatCreateFeed($params));
            return;
        }

        // POST /feeds/{id}/load - legacy feed load
        if (ctype_digit($fragments[1]) && ($fragments[2] ?? '') === 'load') {
            Response::success($this->feedHandler->formatLoadFeed(
                $params['name'],
                (int)$fragments[1],
                $params['source_uri'],
                $params['options']
            ));
            return;
        }

        Response::error('Expected "articles/import", feed data, or "{id}/load"', 404);
    }

    private function handleFeedsGet(array $fragments, array $params): void
    {
        // GET /feeds/list - get paginated feed list
        if (($fragments[1] ?? '') === 'list') {
            Response::success($this->feedHandler->formatGetFeedList($params));
            return;
        }

        // GET /feeds/articles - get articles for a feed
        if (($fragments[1] ?? '') === 'articles') {
            Response::success($this->feedHandler->formatGetArticles($params));
            return;
        }

        // GET /feeds/{id} - get single feed
        if (isset($fragments[1]) && ctype_digit($fragments[1])) {
            Response::success($this->feedHandler->formatGetFeed((int)$fragments[1]));
            return;
        }

        Response::error('Expected "list", "articles", or feed ID', 404);
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
            case 'languages':
                $this->handleLanguagesPut($fragments, $params);
                break;

            case 'review':
                $this->handleReviewPut($fragments, $params);
                break;

            case 'terms':
                $this->handleTermsPut($fragments, $params);
                break;

            case 'texts':
                $this->handleTextsPut($fragments, $params);
                break;

            case 'feeds':
                $this->handleFeedsPut($fragments, $params);
                break;

            case 'books':
                $this->handleBooksPut($fragments, $params);
                break;

            case 'local-dictionaries':
                $this->handleLocalDictionariesPut($fragments, $params);
                break;

            default:
                Response::error('Endpoint Not Found On PUT: ' . $fragments[0], 404);
        }
    }

    private function handleFeedsPut(array $fragments, array $params): void
    {
        if (!isset($fragments[1]) || !ctype_digit($fragments[1])) {
            Response::error('Feed ID (Integer) Expected', 404);
        }

        $feedId = (int)$fragments[1];
        Response::success($this->feedHandler->formatUpdateFeed($feedId, $params));
    }

    /**
     * Handle PUT requests for languages.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    Request body parameters
     */
    private function handleLanguagesPut(array $fragments, array $params): void
    {
        if (!isset($fragments[1]) || !ctype_digit($fragments[1])) {
            Response::error('Language ID (Integer) Expected', 404);
        }

        $langId = (int)$fragments[1];

        // PUT /languages/{id} - update language
        if (!isset($fragments[2]) || $fragments[2] === '') {
            Response::success($this->languageHandler->formatUpdate($langId, $params));
            return;
        }

        Response::error('Unexpected sub-path for PUT /languages/{id}', 404);
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
        } elseif (($fragments[1] ?? '') === 'bulk-action') {
            // PUT /terms/bulk-action - perform bulk action on selected terms
            $ids = $params['ids'] ?? [];
            $action = $params['action'] ?? '';
            $data = $params['data'] ?? null;
            Response::success($this->termHandler->formatBulkAction($ids, $action, $data));
        } elseif (($fragments[1] ?? '') === 'all-action') {
            // PUT /terms/all-action - perform action on all filtered terms
            $filters = $params['filters'] ?? [];
            $action = $params['action'] ?? '';
            $data = $params['data'] ?? null;
            Response::success($this->termHandler->formatAllAction($filters, $action, $data));
        } elseif (($fragments[1] ?? '') === 'family') {
            // PUT /terms/family/status - update status for entire word family
            // PUT /terms/family/apply - apply suggested family update
            if (($fragments[2] ?? '') === 'status') {
                $langId = (int)($params['language_id'] ?? 0);
                $lemmaLc = $params['lemma_lc'] ?? '';
                $status = (int)($params['status'] ?? 0);

                if ($langId <= 0 || $lemmaLc === '') {
                    Response::error('language_id and lemma_lc are required', 400);
                }
                Response::success($this->termHandler->formatUpdateWordFamilyStatus($langId, $lemmaLc, $status));
            } elseif (($fragments[2] ?? '') === 'apply') {
                $termIds = $params['term_ids'] ?? [];
                $status = (int)($params['status'] ?? 0);

                if (empty($termIds)) {
                    Response::error('term_ids is required', 400);
                }
                Response::success($this->termHandler->formatApplyFamilyUpdate($termIds, $status));
            } else {
                Response::error('Expected "status" or "apply"', 404);
            }
        } elseif (isset($fragments[1]) && ctype_digit($fragments[1]) && ($fragments[2] ?? '') === 'inline-edit') {
            // PUT /terms/{id}/inline-edit - inline edit translation or romanization
            $termId = (int)$fragments[1];
            $field = $params['field'] ?? '';
            $value = $params['value'] ?? '';
            Response::success($this->termHandler->formatInlineEdit($termId, $field, $value));
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
            Response::error('Term ID (Integer), "bulk-status", "family", or "multi/{id}" Expected', 404);
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
            case 'languages':
                $this->handleLanguagesDelete($fragments);
                break;

            case 'terms':
                $this->handleTermsDelete($fragments);
                break;

            case 'feeds':
                $this->handleFeedsDelete($fragments, $params);
                break;

            case 'books':
                $this->handleBooksDelete($fragments);
                break;

            case 'local-dictionaries':
                $this->handleLocalDictionariesDelete($fragments);
                break;

            case 'tts':
                $this->handleTtsDelete($fragments);
                break;

            case 'whisper':
                $this->handleWhisperDelete($fragments);
                break;

            default:
                Response::error('Endpoint Not Found On DELETE: ' . $fragments[0], 404);
        }
    }

    private function handleFeedsDelete(array $fragments, array $params): void
    {
        // DELETE /feeds/articles/{feedId} - delete articles for a feed
        if (($fragments[1] ?? '') === 'articles' && isset($fragments[2]) && ctype_digit($fragments[2])) {
            $feedId = (int)$fragments[2];
            $articleIds = $params['article_ids'] ?? [];
            Response::success($this->feedHandler->formatDeleteArticles($feedId, $articleIds));
            return;
        }

        // DELETE /feeds/{id}/reset-errors - reset error articles
        if (isset($fragments[1]) && ctype_digit($fragments[1]) && ($fragments[2] ?? '') === 'reset-errors') {
            Response::success($this->feedHandler->formatResetErrorArticles((int)$fragments[1]));
            return;
        }

        // DELETE /feeds - bulk delete feeds (ids in body)
        if (!isset($fragments[1]) || $fragments[1] === '') {
            $feedIds = $params['feed_ids'] ?? [];
            Response::success($this->feedHandler->formatDeleteFeeds($feedIds));
            return;
        }

        // DELETE /feeds/{id} - delete single feed
        if (ctype_digit($fragments[1])) {
            Response::success($this->feedHandler->formatDeleteFeeds([(int)$fragments[1]]));
            return;
        }

        Response::error('Expected feed ID or "articles/{feedId}"', 404);
    }

    /**
     * Handle DELETE requests for languages.
     *
     * @param string[] $fragments Endpoint path segments
     */
    private function handleLanguagesDelete(array $fragments): void
    {
        if (!isset($fragments[1]) || !ctype_digit($fragments[1])) {
            Response::error('Language ID (Integer) Expected', 404);
        }

        $langId = (int)$fragments[1];
        Response::success($this->languageHandler->formatDelete($langId));
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
    // Local Dictionary Request Handlers
    // =========================================================================

    /**
     * Handle GET requests for local dictionaries.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    Query parameters
     */
    private function handleLocalDictionariesGet(array $fragments, array $params): void
    {
        // GET /local-dictionaries/lookup - look up a term
        if (($fragments[1] ?? '') === 'lookup') {
            $languageId = (int)($params['language_id'] ?? 0);
            $term = $params['term'] ?? '';

            if ($languageId <= 0) {
                Response::error('language_id is required', 400);
            }
            if ($term === '') {
                Response::error('term is required', 400);
            }

            Response::success($this->localDictionaryHandler->formatLookup($languageId, $term));
            return;
        }

        // GET /local-dictionaries/entries/{dictId} - get entries for dictionary
        if (($fragments[1] ?? '') === 'entries' && isset($fragments[2]) && ctype_digit($fragments[2])) {
            Response::success($this->localDictionaryHandler->formatGetEntries((int)$fragments[2], $params));
            return;
        }

        // GET /local-dictionaries/{id} - get single dictionary
        if (isset($fragments[1]) && ctype_digit($fragments[1])) {
            Response::success($this->localDictionaryHandler->formatGetDictionary((int)$fragments[1]));
            return;
        }

        // GET /local-dictionaries?language_id=N - list dictionaries for language
        $languageId = (int)($params['language_id'] ?? 0);
        if ($languageId <= 0) {
            Response::error('language_id is required', 400);
        }

        Response::success($this->localDictionaryHandler->formatGetDictionaries($languageId));
    }

    /**
     * Handle POST requests for local dictionaries.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    POST parameters
     */
    private function handleLocalDictionariesPost(array $fragments, array $params): void
    {
        // POST /local-dictionaries/preview - preview file before import
        if (($fragments[1] ?? '') === 'preview') {
            Response::success($this->localDictionaryHandler->formatPreview($params));
            return;
        }

        // POST /local-dictionaries/entries/{dictId} - add entry to dictionary
        if (($fragments[1] ?? '') === 'entries' && isset($fragments[2]) && ctype_digit($fragments[2])) {
            Response::success($this->localDictionaryHandler->formatAddEntry((int)$fragments[2], $params));
            return;
        }

        // POST /local-dictionaries/{id}/import - import entries into dictionary
        if (isset($fragments[1]) && ctype_digit($fragments[1]) && ($fragments[2] ?? '') === 'import') {
            Response::success($this->localDictionaryHandler->formatImport((int)$fragments[1], $params));
            return;
        }

        // POST /local-dictionaries/{id}/clear - clear all entries
        if (isset($fragments[1]) && ctype_digit($fragments[1]) && ($fragments[2] ?? '') === 'clear') {
            Response::success($this->localDictionaryHandler->formatClearEntries((int)$fragments[1]));
            return;
        }

        // POST /local-dictionaries - create new dictionary
        if (!isset($fragments[1]) || $fragments[1] === '') {
            Response::success($this->localDictionaryHandler->formatCreateDictionary($params));
            return;
        }

        Response::error('Endpoint Not Found: local-dictionaries/' . ($fragments[1] ?? ''), 404);
    }

    /**
     * Handle PUT requests for local dictionaries.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    Request body parameters
     */
    private function handleLocalDictionariesPut(array $fragments, array $params): void
    {
        // PUT /local-dictionaries/{id} - update dictionary
        if (isset($fragments[1]) && ctype_digit($fragments[1]) && !isset($fragments[2])) {
            Response::success($this->localDictionaryHandler->formatUpdateDictionary((int)$fragments[1], $params));
            return;
        }

        Response::error('Dictionary ID (Integer) Expected', 404);
    }

    /**
     * Handle DELETE requests for local dictionaries.
     *
     * @param string[] $fragments Endpoint path segments
     */
    private function handleLocalDictionariesDelete(array $fragments): void
    {
        // DELETE /local-dictionaries/{id} - delete dictionary
        if (isset($fragments[1]) && ctype_digit($fragments[1])) {
            Response::success($this->localDictionaryHandler->formatDeleteDictionary((int)$fragments[1]));
            return;
        }

        Response::error('Dictionary ID (Integer) Expected', 404);
    }

    // =========================================================================
    // TTS Request Handlers (Piper TTS via NLP microservice)
    // =========================================================================

    /**
     * Handle GET requests for TTS endpoints.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    Query parameters
     */
    private function handleTtsGet(array $fragments, array $params): void
    {
        switch ($fragments[1] ?? '') {
            case 'voices':
                if (($fragments[2] ?? '') === 'installed') {
                    // GET /tts/voices/installed - get only installed voices
                    Response::success(['voices' => $this->nlpHandler->getInstalledVoices()]);
                } else {
                    // GET /tts/voices - get all voices (installed + available)
                    Response::success(['voices' => $this->nlpHandler->getVoices()]);
                }
                break;

            default:
                Response::error('Expected "voices"', 404);
        }
    }

    /**
     * Handle POST requests for TTS endpoints.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    POST parameters
     */
    private function handleTtsPost(array $fragments, array $params): void
    {
        switch ($fragments[1] ?? '') {
            case 'speak':
                // POST /tts/speak - synthesize speech
                $text = $params['text'] ?? '';
                $voiceId = $params['voice_id'] ?? '';

                if ($text === '' || $voiceId === '') {
                    Response::error('text and voice_id are required', 400);
                }

                $audioData = $this->nlpHandler->speak($text, $voiceId);
                if ($audioData === null) {
                    Response::error('TTS service unavailable or synthesis failed', 503);
                }

                Response::success(['audio' => $audioData]);
                break;

            case 'voices':
                if (($fragments[2] ?? '') === 'download') {
                    // POST /tts/voices/download - download a voice
                    $voiceId = $params['voice_id'] ?? '';
                    if ($voiceId === '') {
                        Response::error('voice_id is required', 400);
                    }

                    $success = $this->nlpHandler->downloadVoice($voiceId);
                    if (!$success) {
                        Response::error('Voice download failed', 500);
                    }

                    Response::success(['success' => true, 'voice_id' => $voiceId]);
                } else {
                    Response::error('Expected "download"', 404);
                }
                break;

            default:
                Response::error('Expected "speak" or "voices/download"', 404);
        }
    }

    /**
     * Handle DELETE requests for TTS endpoints.
     *
     * @param string[] $fragments Endpoint path segments
     */
    private function handleTtsDelete(array $fragments): void
    {
        // DELETE /tts/voices/{id} - delete a voice
        if (($fragments[1] ?? '') === 'voices' && isset($fragments[2]) && $fragments[2] !== '') {
            $voiceId = $fragments[2];
            $success = $this->nlpHandler->deleteVoice($voiceId);

            if (!$success) {
                Response::error('Voice not found or deletion failed', 404);
            }

            Response::success(['success' => true]);
            return;
        }

        Response::error('Expected "voices/{id}"', 404);
    }

    // =========================================================================
    // YouTube Request Handlers
    // =========================================================================

    /**
     * Handle GET requests for YouTube API proxy.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    Query parameters
     */
    private function handleYouTubeGet(array $fragments, array $params): void
    {
        switch ($fragments[1] ?? '') {
            case 'configured':
                // GET /youtube/configured - check if YouTube API is configured
                Response::success($this->youtubeHandler->formatIsConfigured());
                break;

            case 'video':
                // GET /youtube/video?video_id=xxx - get video info
                $videoId = $params['video_id'] ?? '';
                if ($videoId === '') {
                    Response::error('video_id parameter is required', 400);
                }
                Response::success($this->youtubeHandler->formatGetVideoInfo($videoId));
                break;

            default:
                Response::error('Expected "configured" or "video"', 404);
        }
    }

    // =========================================================================
    // Whisper Request Handlers (Audio/Video Transcription via NLP microservice)
    // =========================================================================

    /**
     * Handle GET requests for Whisper endpoints.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    Query parameters
     */
    private function handleWhisperGet(array $fragments, array $params): void
    {
        switch ($fragments[1] ?? '') {
            case 'available':
                // GET /whisper/available - check if Whisper is available
                Response::success($this->whisperHandler->formatIsAvailable());
                break;

            case 'languages':
                // GET /whisper/languages - get supported languages
                Response::success($this->whisperHandler->formatGetLanguages());
                break;

            case 'models':
                // GET /whisper/models - get available models
                Response::success($this->whisperHandler->formatGetModels());
                break;

            case 'status':
                // GET /whisper/status/{job_id} - get job status
                $jobId = $fragments[2] ?? '';
                if ($jobId === '') {
                    Response::error('job_id is required', 400);
                }
                Response::success($this->whisperHandler->formatGetStatus($jobId));
                break;

            case 'result':
                // GET /whisper/result/{job_id} - get completed result
                $jobId = $fragments[2] ?? '';
                if ($jobId === '') {
                    Response::error('job_id is required', 400);
                }
                try {
                    Response::success($this->whisperHandler->formatGetResult($jobId));
                } catch (\RuntimeException $e) {
                    Response::error($e->getMessage(), 500);
                }
                break;

            default:
                Response::error('Expected "available", "languages", "models", "status/{id}", or "result/{id}"', 404);
        }
    }

    /**
     * Handle POST requests for Whisper endpoints.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    POST parameters
     */
    private function handleWhisperPost(array $fragments, array $params): void
    {
        if (($fragments[1] ?? '') === 'transcribe') {
            // POST /whisper/transcribe - start transcription
            $file = $_FILES['file'] ?? null;
            if ($file === null) {
                Response::error('No file uploaded', 400);
            }

            $language = $params['language'] ?? null;
            if ($language === '') {
                $language = null;
            }
            $model = $params['model'] ?? 'small';

            try {
                Response::success($this->whisperHandler->formatStartTranscription(
                    $file,
                    $language,
                    $model
                ));
            } catch (\InvalidArgumentException $e) {
                Response::error($e->getMessage(), 400);
            } catch (\RuntimeException $e) {
                Response::error($e->getMessage(), 503);
            }
            return;
        }

        Response::error('Expected "transcribe"', 404);
    }

    /**
     * Handle DELETE requests for Whisper endpoints.
     *
     * @param string[] $fragments Endpoint path segments
     */
    private function handleWhisperDelete(array $fragments): void
    {
        // DELETE /whisper/job/{id} - cancel job
        if (($fragments[1] ?? '') === 'job' && isset($fragments[2]) && $fragments[2] !== '') {
            $jobId = $fragments[2];
            Response::success($this->whisperHandler->formatCancelJob($jobId));
            return;
        }

        Response::error('Expected "job/{id}"', 404);
    }

    // =========================================================================
    // Book Request Handlers
    // =========================================================================

    /**
     * Handle GET requests for books.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    Query parameters
     */
    private function handleBooksGet(array $fragments, array $params): void
    {
        if ($this->bookHandler === null) {
            Response::error('Book module not available', 503);
        }

        // GET /books/{id}/chapters - get chapters for a book
        if (isset($fragments[1]) && ctype_digit($fragments[1]) && ($fragments[2] ?? '') === 'chapters') {
            Response::success($this->bookHandler->getChapters(['id' => $fragments[1]]));
            return;
        }

        // GET /books/{id} - get single book
        if (isset($fragments[1]) && ctype_digit($fragments[1])) {
            Response::success($this->bookHandler->getBook(['id' => $fragments[1]]));
            return;
        }

        // GET /books - list all books
        Response::success($this->bookHandler->listBooks($params));
    }

    /**
     * Handle PUT requests for books.
     *
     * @param string[] $fragments Endpoint path segments
     * @param array    $params    PUT parameters
     */
    private function handleBooksPut(array $fragments, array $params): void
    {
        if ($this->bookHandler === null) {
            Response::error('Book module not available', 503);
        }

        // PUT /books/{id}/progress - update reading progress
        if (isset($fragments[1]) && ctype_digit($fragments[1]) && ($fragments[2] ?? '') === 'progress') {
            $params['id'] = $fragments[1];
            Response::success($this->bookHandler->updateProgress($params));
            return;
        }

        Response::error('Expected "progress"', 404);
    }

    /**
     * Handle DELETE requests for books.
     *
     * @param string[] $fragments Endpoint path segments
     */
    private function handleBooksDelete(array $fragments): void
    {
        if ($this->bookHandler === null) {
            Response::error('Book module not available', 503);
        }

        // DELETE /books/{id} - delete a book
        if (isset($fragments[1]) && ctype_digit($fragments[1])) {
            Response::success($this->bookHandler->deleteBook(['id' => $fragments[1]]));
            return;
        }

        Response::error('Book ID (Integer) Expected', 404);
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
        if ($input === false || $input === '') {
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
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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
        $api->handle($method, $_SERVER['REQUEST_URI'] ?? '/', $bodyData);
    }
}

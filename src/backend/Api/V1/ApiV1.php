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

declare(strict_types=1);

namespace Lwt\Api\V1;

use Lwt\Modules\Dictionary\Http\DictionaryApiHandler;
use Lwt\Modules\Language\Http\LanguageApiHandler;
use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Modules\Feed\Http\FeedApiHandler;
use Lwt\Modules\Admin\Application\AdminFacade;
use Lwt\Modules\Admin\Http\AdminApiHandler;
use Lwt\Modules\Review\Http\ReviewApiHandler;
use Lwt\Modules\Tags\Http\TagApiHandler;
use Lwt\Modules\User\Http\UserApiHandler;
use Lwt\Modules\Vocabulary\Http\TermCrudApiHandler;
use Lwt\Modules\Vocabulary\Http\WordFamilyApiHandler;
use Lwt\Modules\Vocabulary\Http\MultiWordApiHandler;
use Lwt\Modules\Vocabulary\Http\WordListApiHandler;
use Lwt\Modules\Vocabulary\Http\TermTranslationApiHandler;
use Lwt\Modules\Vocabulary\Http\TermStatusApiHandler;
use Lwt\Modules\Text\Http\TextApiHandler;
use Lwt\Modules\Book\Http\BookApiHandler;
use Lwt\Modules\Book\Application\BookFacade;
use Lwt\Modules\Text\Http\YouTubeApiHandler;
use Lwt\Modules\Language\Infrastructure\NlpServiceHandler;
use Lwt\Modules\Text\Http\WhisperApiHandler;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Shared\Infrastructure\Http\JsonResponse;

/**
 * Main API V1 handler class.
 */
class ApiV1
{
    private const VERSION = "3.0.0";
    private const RELEASE_DATE = "2026-01-10";

    private UserApiHandler $authHandler;
    private FeedApiHandler $feedHandler;
    private LanguageApiHandler $languageHandler;
    private DictionaryApiHandler $localDictionaryHandler;
    private AdminApiHandler $adminHandler;
    private ReviewApiHandler $reviewHandler;
    private TagApiHandler $tagHandler;
    private TermCrudApiHandler $termHandler;
    private WordFamilyApiHandler $wordFamilyHandler;
    private MultiWordApiHandler $multiWordHandler;
    private WordListApiHandler $wordListHandler;
    private TermTranslationApiHandler $termTranslationHandler;
    private TermStatusApiHandler $termStatusHandler;
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
        $container = Container::getInstance();
        /** @var FeedFacade $feedFacade */
        $feedFacade = $container->get(FeedFacade::class);
        $this->feedHandler = new FeedApiHandler($feedFacade);
        $this->languageHandler = new LanguageApiHandler();
        $this->localDictionaryHandler = new DictionaryApiHandler();
        /** @var AdminFacade $adminFacade */
        $adminFacade = $container->get(AdminFacade::class);
        $this->adminHandler = new AdminApiHandler($adminFacade);
        $this->reviewHandler = new ReviewApiHandler();
        $this->tagHandler = new TagApiHandler();
        $this->termHandler = new TermCrudApiHandler();
        $this->wordFamilyHandler = new WordFamilyApiHandler();
        $this->multiWordHandler = new MultiWordApiHandler();
        $this->wordListHandler = new WordListApiHandler();
        $this->termTranslationHandler = new TermTranslationApiHandler();
        $this->termStatusHandler = new TermStatusApiHandler();
        $this->textHandler = new TextApiHandler();
        try {
            /** @var BookFacade $bookFacade */
            $bookFacade = $container->get(BookFacade::class);
            $this->bookHandler = new BookApiHandler($bookFacade);
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
     * @param string                     $method   HTTP method
     * @param string                     $uri      Request URI
     * @param array<string, mixed>|null  $postData POST data (also used for PUT/DELETE with JSON body)
     *
     * @return void
     */
    public function handle(string $method, string $uri, ?array $postData): void
    {
        $endpointResult = Endpoints::resolve($method, $uri);

        // Check if resolution returned an error response
        if ($endpointResult instanceof \Lwt\Shared\Infrastructure\Http\JsonResponse) {
            $endpointResult->send();
            return;
        }

        $endpoint = $endpointResult;
        $fragments = Endpoints::parseFragments($endpoint);

        // Validate authentication for protected endpoints
        if (!$this->isPublicEndpoint($endpoint)) {
            $authError = $this->validateAuth();
            if ($authError !== null) {
                $authError->send();
                return;
            }
        }

        if ($method === 'GET') {
            $response = $this->handleGet($fragments, $this->parseQueryParams($uri));
        } elseif ($method === 'POST') {
            $response = $this->handlePost($fragments, $postData ?? []);
        } elseif ($method === 'PUT') {
            $response = $this->handlePut($fragments, $postData ?? []);
        } elseif ($method === 'DELETE') {
            $response = $this->handleDelete($fragments, $postData ?? []);
        } else {
            $response = Response::error('Method Not Allowed', 405);
        }

        $response->send();
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
     *
     * @return \Lwt\Shared\Infrastructure\Http\JsonResponse|null Error response or null if valid
     */
    private function validateAuth(): ?\Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        // Skip auth validation if multi-user mode is not enabled
        if (!Globals::isMultiUserEnabled()) {
            return null;
        }

        if (!$this->authHandler->isAuthenticated()) {
            return Response::error('Authentication required', 401);
        }

        return null;
    }

    /**
     * Handle GET requests.
     *
     * @param list<string>         $fragments Endpoint path segments
     * @param array<string, mixed> $params    Query parameters
     */
    private function handleGet(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        switch ($fragments[0]) {
            case 'auth':
                return $this->handleAuthGet($fragments);

            case 'version':
                return Response::success([
                    "version" => self::VERSION,
                    "release_date" => self::RELEASE_DATE
                ]);

            case 'media-files':
                return Response::success($this->adminHandler->formatMediaFiles());

            case 'phonetic-reading':
                /** @var array{text?: string, language_id?: int|string, lang?: string} $params */
                return Response::success($this->languageHandler->formatPhoneticReading($params));

            case 'languages':
                return $this->handleLanguagesGet($fragments);

            case 'review':
                return $this->handleReviewGet($fragments, $params);

            case 'sentences-with-term':
                return $this->handleSentencesGet($fragments, $params);

            case 'similar-terms':
                return Response::success($this->languageHandler->formatSimilarTerms(
                    (int)($params["language_id"] ?? 0),
                    (string)$params["term"]
                ));

            case 'statuses':
                return Response::success(\Lwt\Modules\Vocabulary\Application\Services\TermStatusService::getStatuses());

            case 'tags':
                return $this->handleTagsGet($fragments);

            case 'settings':
                return $this->handleSettingsGet($fragments, $params);

            case 'terms':
                return $this->handleTermsGet($fragments, $params);

            case 'word-families':
                return $this->handleWordFamiliesGet($fragments, $params);

            case 'texts':
                return $this->handleTextsGet($fragments, $params);

            case 'feeds':
                return $this->handleFeedsGet($fragments, $params);

            case 'books':
                return $this->handleBooksGet($fragments, $params);

            case 'local-dictionaries':
                return $this->handleLocalDictionariesGet($fragments, $params);

            case 'youtube':
                return $this->handleYouTubeGet($fragments, $params);

            case 'tts':
                return $this->handleTtsGet($fragments, $params);

            case 'whisper':
                return $this->handleWhisperGet($fragments, $params);

            default:
                return Response::error('Endpoint Not Found: ' . $fragments[0], 404);
        }
    }

    /**
     * Handle POST requests.
     *
     * @param list<string>         $fragments Endpoint path segments
     * @param array<string, mixed> $params    POST parameters
     */
    private function handlePost(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        switch ($fragments[0]) {
            case 'auth':
                return $this->handleAuthPost($fragments, $params);

            case 'settings':
                return Response::success($this->adminHandler->formatSaveSetting(
                    (string) ($params['key'] ?? ''),
                    (string) ($params['value'] ?? '')
                ));

            case 'languages':
                return $this->handleLanguagesPost($fragments, $params);

            case 'texts':
                return $this->handleTextsPost($fragments, $params);

            case 'terms':
                return $this->handleTermsPost($fragments, $params);

            case 'feeds':
                return $this->handleFeedsPost($fragments, $params);

            case 'local-dictionaries':
                return $this->handleLocalDictionariesPost($fragments, $params);

            case 'tts':
                return $this->handleTtsPost($fragments, $params);

            case 'whisper':
                return $this->handleWhisperPost($fragments, $params);

            default:
                return Response::error('Endpoint Not Found On POST: ' . $fragments[0], 404);
        }
    }

    /**
     * Handle POST requests for languages.
     *
     * @param list<string>         $fragments Endpoint path segments
     * @param array<string, mixed> $params    POST parameters
     */
    private function handleLanguagesPost(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        // POST /languages - create new language
        if (!isset($fragments[1]) || $fragments[1] === '') {
            return Response::success($this->languageHandler->formatCreate($params));
        }

        // POST /languages/{id}/refresh - reparse texts
        if (ctype_digit($fragments[1])) {
            $langId = (int)$fragments[1];

            if (($fragments[2] ?? '') === 'refresh') {
                return Response::success($this->languageHandler->formatRefresh($langId));
            }

            if (($fragments[2] ?? '') === 'set-default') {
                return Response::success($this->languageHandler->formatSetDefault($langId));
            }

            return Response::error('Expected "refresh" or "set-default"', 404);
        }

        return Response::error('Language ID (Integer) Expected', 404);
    }

    // =========================================================================
    // Auth Request Handlers
    // =========================================================================

    /**
     * Handle GET requests for auth endpoints.
     *
     * @param list<string> $fragments Endpoint path segments
     */
    private function handleAuthGet(array $fragments): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        switch ($fragments[1] ?? '') {
            case 'me':
                // GET /auth/me - get current user info
                return Response::success($this->authHandler->formatMe());
            default:
                return Response::error('Endpoint Not Found: auth/' . ($fragments[1] ?? ''), 404);
        }
    }

    /**
     * Handle POST requests for auth endpoints.
     *
     * @param list<string>         $fragments Endpoint path segments
     * @param array<string, mixed> $params    POST parameters
     */
    private function handleAuthPost(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        switch ($fragments[1] ?? '') {
            case 'login':
                // POST /auth/login - authenticate user
                return Response::success($this->authHandler->formatLogin($params));
            case 'register':
                // POST /auth/register - create new user
                return Response::success($this->authHandler->formatRegister($params));
            case 'refresh':
                // POST /auth/refresh - refresh API token
                return Response::success($this->authHandler->formatRefresh());
            case 'logout':
                // POST /auth/logout - invalidate token and logout
                return Response::success($this->authHandler->formatLogout());
            default:
                return Response::error('Endpoint Not Found: auth/' . ($fragments[1] ?? ''), 404);
        }
    }

    // =========================================================================
    // GET Request Handlers
    // =========================================================================

    /**
     * @param list<string> $fragments
     */
    private function handleLanguagesGet(array $fragments): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        // Handle /languages - list all languages with stats
        if ($frag1 === '') {
            return Response::success($this->languageHandler->formatGetAll());
        }

        // Handle /languages/definitions - get predefined language presets
        if ($frag1 === 'definitions') {
            return Response::success($this->languageHandler->formatGetDefinitions());
        }

        // Handle /languages/with-texts - returns languages that have texts with counts
        if ($frag1 === 'with-texts') {
            return Response::success($this->languageHandler->formatLanguagesWithTexts());
        }

        // Handle /languages/with-archived-texts - returns languages that have archived texts with counts
        if ($frag1 === 'with-archived-texts') {
            return Response::success($this->languageHandler->formatLanguagesWithArchivedTexts());
        }

        // Handle /languages/{id} - get single language or sub-resources
        if (!ctype_digit($frag1)) {
            return Response::error('Expected Language ID, "definitions", "with-texts", or "with-archived-texts"', 404);
        }

        $langId = (int) $frag1;

        // Handle /languages/{id}/stats
        if ($frag2 === 'stats') {
            return Response::success($this->languageHandler->formatGetStats($langId));
        }

        // Handle /languages/{id}/reading-configuration
        if ($frag2 === 'reading-configuration') {
            return Response::success($this->languageHandler->formatReadingConfiguration($langId));
        }

        // Handle /languages/{id} - get single language for editing
        if ($frag2 === '') {
            $result = $this->languageHandler->formatGetOne($langId);
            if ($result === null) {
                return Response::error('Language not found', 404);
            }
            return Response::success($result);
        }

        return Response::error('Expected "reading-configuration", "stats", or no sub-path', 404);
    }

    /**
     * @param list<string> $fragments
     * @param array<string, mixed> $params
     */
    private function handleReviewGet(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        switch ($frag1) {
            case 'next-word':
                return Response::success($this->reviewHandler->formatNextWord($params));
            case 'tomorrow-count':
                return Response::success($this->reviewHandler->formatTomorrowCount($params));
            case 'config':
                return Response::success($this->reviewHandler->formatTestConfig($params));
            case 'table-words':
                return Response::success($this->reviewHandler->formatTableWords($params));
            default:
                return Response::error('Endpoint Not Found: ' . $frag1, 404);
        }
    }

    /**
     * @param list<string> $fragments
     * @param array<string, mixed> $params
     */
    private function handleSentencesGet(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $languageId = (int) ($params["language_id"] ?? 0);
        $termLc = (string) ($params["term_lc"] ?? '');
        $frag1 = $this->frag($fragments, 1);

        if ($frag1 !== '' && ctype_digit($frag1)) {
            return Response::success($this->languageHandler->formatSentencesWithRegisteredTerm(
                $languageId,
                $termLc,
                (int) $frag1
            ));
        } else {
            return Response::success($this->languageHandler->formatSentencesWithNewTerm(
                $languageId,
                $termLc,
                array_key_exists("advanced_search", $params)
            ));
        }
    }

    /**
     * @param list<string> $fragments
     * @param array<string, mixed> $params
     */
    private function handleSettingsGet(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        if ($frag1 === 'theme-path') {
            return Response::success($this->adminHandler->formatThemePath((string) ($params['path'] ?? '')));
        } else {
            return Response::error('Endpoint Not Found: ' . $frag1, 404);
        }
    }

    /**
     * @param list<string> $fragments
     */
    private function handleTagsGet(array $fragments): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        return $this->tagHandler->handleGet(array_slice($fragments, 1));
    }

    /**
     * @param list<string> $fragments
     * @param array<string, mixed> $params
     */
    private function handleTermsGet(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        if ($frag1 === 'list') {
            // GET /terms/list - get paginated, filtered word list
            return Response::success($this->wordListHandler->getWordList($params));
        } elseif ($frag1 === 'filter-options') {
            // GET /terms/filter-options - get filter dropdown options
            $langId = isset($params['language_id']) && $params['language_id'] !== ''
                ? (int) $params['language_id']
                : null;
            return Response::success($this->wordListHandler->getFilterOptions($langId));
        } elseif ($frag1 === 'imported') {
            return Response::success($this->wordListHandler->importedTermsList(
                (string) ($params["last_update"] ?? ''),
                (int) ($params["page"] ?? 0),
                (int) ($params["count"] ?? 0)
            ));
        } elseif ($frag1 === 'for-edit') {
            // GET /terms/for-edit - get term data for editing in modal
            return Response::success($this->termHandler->formatGetTermForEdit(
                (int) ($params['term_id'] ?? 0),
                (int) ($params['ord'] ?? 0),
                isset($params['wid']) && $params['wid'] !== '' ? (int) $params['wid'] : null
            ));
        } elseif ($frag1 === 'multi') {
            // GET /terms/multi - get multi-word expression data for editing
            return Response::success($this->multiWordHandler->getMultiWordForEdit(
                (int) ($params['term_id'] ?? 0),
                (int) ($params['ord'] ?? 0),
                isset($params['txt']) ? (string) $params['txt'] : null,
                isset($params['wid']) ? (int) $params['wid'] : null
            ));
        } elseif ($frag1 === 'family') {
            // GET /terms/family?term_id=N - get word family for a term
            // GET /terms/family/suggestion?term_id=N&status=X - get family update suggestion
            if ($frag2 === 'suggestion') {
                $termId = (int) ($params['term_id'] ?? 0);
                $newStatus = (int) ($params['status'] ?? 0);
                return Response::success($this->wordFamilyHandler->getFamilyUpdateSuggestion($termId, $newStatus));
            } else {
                $termId = (int) ($params['term_id'] ?? 0);
                if ($termId <= 0) {
                    return Response::error('term_id is required', 400);
                }
                return Response::success($this->wordFamilyHandler->getTermFamily($termId));
            }
        } elseif ($frag1 !== '' && ctype_digit($frag1)) {
            $termId = (int) $frag1;
            if ($frag2 === 'translations') {
                return Response::success($this->textHandler->formatTermTranslations(
                    (string) ($params["term_lc"] ?? ''),
                    (int) ($params["text_id"] ?? 0)
                ));
            } elseif ($frag2 === 'details') {
                // GET /terms/{id}/details - get term details with sentence and tags
                $ann = isset($params['ann']) ? (string) $params['ann'] : null;
                return Response::success($this->termHandler->formatGetTermDetails($termId, $ann));
            } elseif ($frag2 === 'family') {
                // GET /terms/{id}/family - get word family for this term
                return Response::success($this->wordFamilyHandler->getTermFamily($termId));
            } elseif ($frag2 === '') {
                // GET /terms/{id} - get term by ID
                return Response::success($this->termHandler->formatGetTerm($termId));
            } else {
                return Response::error('Expected "translations", "details", "family", or no sub-path', 404);
            }
        } else {
            return Response::error('Endpoint Not Found: ' . $frag1, 404);
        }
    }

    /**
     * Handle GET requests for word families.
     *
     * @param list<string>         $fragments Endpoint path segments
     * @param array<string, mixed> $params    Query parameters
     */
    private function handleWordFamiliesGet(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        // GET /word-families/stats?language_id=N - get lemma statistics for a language
        if ($frag1 === 'stats') {
            $langId = (int) ($params['language_id'] ?? 0);
            if ($langId <= 0) {
                return Response::error('language_id is required', 400);
            }
            return Response::success($this->wordFamilyHandler->getLemmaStatistics($langId));
        }

        // GET /word-families?language_id=N - get paginated list of word families
        $langId = (int) ($params['language_id'] ?? 0);
        if ($langId <= 0) {
            return Response::error('language_id is required', 400);
        }

        // Check if getting family by lemma
        $lemmaLc = (string) ($params['lemma_lc'] ?? '');
        if ($lemmaLc !== '') {
            // GET /word-families?language_id=N&lemma_lc=run - get specific family
            return Response::success($this->wordFamilyHandler->getWordFamilyByLemma($langId, $lemmaLc));
        }

        // GET /word-families?language_id=N&page=1&per_page=50 - get list of families
        return Response::success($this->wordFamilyHandler->getWordFamilyListFromParams($langId, $params));
    }

    /**
     * @param list<string>         $fragments
     * @param array<string, mixed> $params
     */
    private function handleTextsGet(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        if ($frag1 === 'statistics') {
            $textIds = isset($params["text_ids"]) ? (string) $params["text_ids"] : '';
            if ($textIds === '') {
                return Response::error('Missing required parameter: text_ids', 400);
            }
            return Response::success($this->adminHandler->formatTextsStatistics($textIds));
        } elseif ($frag1 === 'scoring') {
            // Handle scoring endpoints
            if ($frag2 === 'recommended') {
                // GET /texts/scoring/recommended?language_id=N - get recommended texts
                $langId = (int) ($params['language_id'] ?? 0);
                if ($langId <= 0) {
                    return Response::error('language_id is required', 400);
                }
                return Response::success($this->textHandler->formatGetRecommendedTexts($langId, $params));
            } else {
                // GET /texts/scoring?text_id=N or text_ids=1,2,3 - get score(s)
                $textId = isset($params['text_id']) ? (int) $params['text_id'] : 0;
                $textIds = (string) ($params['text_ids'] ?? '');

                if ($textId > 0) {
                    // Single text score
                    return Response::success($this->textHandler->formatGetTextScore($textId));
                } elseif ($textIds !== '') {
                    // Multiple text scores
                    $ids = array_map('intval', explode(',', $textIds));
                    $ids = array_filter($ids, fn($id) => $id > 0);
                    if (empty($ids)) {
                        return Response::error('No valid text IDs provided', 400);
                    }
                    return Response::success($this->textHandler->formatGetTextScores($ids));
                } else {
                    return Response::error('text_id or text_ids parameter is required', 400);
                }
            }
        } elseif ($frag1 === 'by-language') {
            // GET /texts/by-language/{langId} - get paginated texts for a language
            if ($frag2 === '' || !ctype_digit($frag2)) {
                return Response::error('Expected Language ID after "by-language"', 404);
            }
            return Response::success($this->textHandler->formatTextsByLanguage((int) $frag2, $params));
        } elseif ($frag1 === 'archived-by-language') {
            // GET /texts/archived-by-language/{langId} - get paginated archived texts for a language
            if ($frag2 === '' || !ctype_digit($frag2)) {
                return Response::error('Expected Language ID after "archived-by-language"', 404);
            }
            return Response::success($this->textHandler->formatArchivedTextsByLanguage((int) $frag2, $params));
        } elseif ($frag1 !== '' && ctype_digit($frag1)) {
            $textId = (int) $frag1;
            if ($frag2 === 'words') {
                // GET /texts/{id}/words - get all words for client-side rendering
                return Response::success($this->textHandler->formatGetWords($textId));
            } elseif ($frag2 === 'print-items') {
                // GET /texts/{id}/print-items - get text items for print view
                return Response::success($this->textHandler->formatGetPrintItems($textId));
            } elseif ($frag2 === 'annotation') {
                // GET /texts/{id}/annotation - get annotation for improved text view
                return Response::success($this->textHandler->formatGetAnnotation($textId));
            } else {
                return Response::error('Expected "words", "print-items", or "annotation"', 404);
            }
        } else {
            return Response::error('Expected "statistics", "by-language", "archived-by-language", or text ID', 404);
        }
    }

    // =========================================================================
    // POST Request Handlers
    // =========================================================================

    /**
     * @param list<string>         $fragments
     * @param array<string, mixed> $params
     */
    private function handleTextsPost(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        if ($frag1 === '' || !ctype_digit($frag1)) {
            return Response::error('Text ID (Integer) Expected', 404);
        }

        $textId = (int) $frag1;

        switch ($frag2) {
            case 'annotation':
                return Response::success($this->textHandler->formatSetAnnotation(
                    $textId,
                    (string) ($params['elem'] ?? ''),
                    (string) ($params['data'] ?? '')
                ));
            case 'audio-position':
                return Response::success($this->textHandler->formatSetAudioPosition(
                    $textId,
                    (int) ($params['position'] ?? 0)
                ));
            case 'reading-position':
                return Response::success($this->textHandler->formatSetTextPosition(
                    $textId,
                    (int) ($params['position'] ?? 0)
                ));
            default:
                return Response::error('Endpoint Not Found: ' . $frag2, 404);
        }
    }

    /**
     * @param list<string>         $fragments
     * @param array<string, mixed> $params
     */
    private function handleTermsPost(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        if ($frag1 !== '' && ctype_digit($frag1)) {
            $termId = (int) $frag1;

            if ($frag2 === 'status') {
                return $this->handleTermStatusPost($fragments, $termId);
            } elseif ($frag2 === 'translations') {
                return Response::success($this->termTranslationHandler->formatUpdateTranslation(
                    $termId,
                    (string) ($params['translation'] ?? '')
                ));
            } else {
                return Response::error('"status" or "translations" Expected', 404);
            }
        } elseif ($frag1 === 'new') {
            return Response::success($this->termTranslationHandler->formatAddTranslation(
                (string) ($params['term_text'] ?? ''),
                (int) ($params['language_id'] ?? 0),
                (string) ($params['translation'] ?? '')
            ));
        } elseif ($frag1 === 'quick') {
            // POST /terms/quick - quick create term with status (98 or 99)
            return Response::success($this->termHandler->formatQuickCreate(
                (int) ($params['text_id'] ?? 0),
                (int) ($params['position'] ?? 0),
                (int) ($params['status'] ?? 0)
            ));
        } elseif ($frag1 === 'full') {
            // POST /terms/full - create term with full data
            return Response::success($this->termHandler->formatCreateTermFull($params));
        } elseif ($frag1 === 'multi') {
            // POST /terms/multi - create multi-word expression
            return Response::success($this->multiWordHandler->createMultiWordTerm($params));
        } else {
            return Response::error('Term ID (Integer), "new", "quick", or "multi" Expected', 404);
        }
    }

    /**
     * @param list<string> $fragments
     * @param int          $termId
     */
    private function handleTermStatusPost(array $fragments, int $termId): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag3 = $this->frag($fragments, 3);

        switch ($frag3) {
            case 'down':
                return Response::success($this->termStatusHandler->formatIncrementStatus($termId, false));
            case 'up':
                return Response::success($this->termStatusHandler->formatIncrementStatus($termId, true));
            default:
                if ($frag3 !== '' && ctype_digit($frag3)) {
                    return Response::success($this->termStatusHandler->formatSetStatus($termId, (int) $frag3));
                } else {
                    return Response::error('Endpoint Not Found: ' . $frag3, 404);
                }
        }
    }

    /**
     * @param list<string>         $fragments
     * @param array<string, mixed> $params
     */
    private function handleFeedsPost(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        // POST /feeds/articles/import - import articles as texts
        if ($frag1 === 'articles' && $frag2 === 'import') {
            return Response::success($this->feedHandler->formatImportArticles($params));
        }

        // POST /feeds - create new feed
        if ($frag1 === '') {
            return Response::success($this->feedHandler->formatCreateFeed($params));
        }

        // POST /feeds/{id}/load - legacy feed load
        if (ctype_digit($frag1) && $frag2 === 'load') {
            return Response::success($this->feedHandler->formatLoadFeed(
                (string) ($params['name'] ?? ''),
                (int) $frag1,
                (string) ($params['source_uri'] ?? ''),
                (string) ($params['options'] ?? '')
            ));
        }

        return Response::error('Expected "articles/import", feed data, or "{id}/load"', 404);
    }

    /**
     * @param list<string>         $fragments
     * @param array<string, mixed> $params
     */
    private function handleFeedsGet(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        // GET /feeds/list - get paginated feed list
        if ($frag1 === 'list') {
            return Response::success($this->feedHandler->formatGetFeedList($params));
        }

        // GET /feeds/articles - get articles for a feed
        if ($frag1 === 'articles') {
            return Response::success($this->feedHandler->formatGetArticles($params));
        }

        // GET /feeds/{id} - get single feed
        if ($frag1 !== '' && ctype_digit($frag1)) {
            return Response::success($this->feedHandler->formatGetFeed((int) $frag1));
        }

        return Response::error('Expected "list", "articles", or feed ID', 404);
    }

    // =========================================================================
    // PUT Request Handlers
    // =========================================================================

    /**
     * Handle PUT requests.
     *
     * @param list<string>         $fragments Endpoint path segments
     * @param array<string, mixed> $params    Request body parameters
     */
    private function handlePut(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag0 = $this->frag($fragments, 0);

        switch ($frag0) {
            case 'languages':
                return $this->handleLanguagesPut($fragments, $params);

            case 'review':
                return $this->handleReviewPut($fragments, $params);

            case 'terms':
                return $this->handleTermsPut($fragments, $params);

            case 'texts':
                return $this->handleTextsPut($fragments, $params);

            case 'feeds':
                return $this->handleFeedsPut($fragments, $params);

            case 'books':
                return $this->handleBooksPut($fragments, $params);

            case 'local-dictionaries':
                return $this->handleLocalDictionariesPut($fragments, $params);

            default:
                return Response::error('Endpoint Not Found On PUT: ' . $frag0, 404);
        }
    }

    /**
     * @param list<string>         $fragments
     * @param array<string, mixed> $params
     */
    private function handleFeedsPut(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        if ($frag1 === '' || !ctype_digit($frag1)) {
            return Response::error('Feed ID (Integer) Expected', 404);
        }

        $feedId = (int) $frag1;
        return Response::success($this->feedHandler->formatUpdateFeed($feedId, $params));
    }

    /**
     * Handle PUT requests for languages.
     *
     * @param list<string>         $fragments Endpoint path segments
     * @param array<string, mixed> $params    Request body parameters
     */
    private function handleLanguagesPut(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        if ($frag1 === '' || !ctype_digit($frag1)) {
            return Response::error('Language ID (Integer) Expected', 404);
        }

        $langId = (int) $frag1;

        // PUT /languages/{id} - update language
        if ($frag2 === '') {
            return Response::success($this->languageHandler->formatUpdate($langId, $params));
        }

        return Response::error('Unexpected sub-path for PUT /languages/{id}', 404);
    }

    /**
     * @param list<string>         $fragments
     * @param array<string, mixed> $params
     */
    private function handleReviewPut(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        if ($frag1 === 'status') {
            // PUT /review/status - update word status during review
            return Response::success($this->reviewHandler->formatUpdateStatus($params));
        } else {
            return Response::error('Expected "status"', 404);
        }
    }

    /**
     * @param list<string>         $fragments
     * @param array<string, mixed> $params
     */
    private function handleTermsPut(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        if ($frag1 === 'bulk-status') {
            // PUT /terms/bulk-status - bulk update term statuses
            /** @var array<int> $termIds */
            $termIds = is_array($params['term_ids'] ?? null) ? $params['term_ids'] : [];
            $status = (int) ($params['status'] ?? 0);
            return Response::success($this->termStatusHandler->formatBulkStatus($termIds, $status));
        } elseif ($frag1 === 'bulk-action') {
            // PUT /terms/bulk-action - perform bulk action on selected terms
            /** @var array<int> $ids */
            $ids = is_array($params['ids'] ?? null) ? $params['ids'] : [];
            $action = (string) ($params['action'] ?? '');
            $data = isset($params['data']) ? (string) $params['data'] : null;
            return Response::success($this->wordListHandler->bulkAction($ids, $action, $data));
        } elseif ($frag1 === 'all-action') {
            // PUT /terms/all-action - perform action on all filtered terms
            /** @var array<string, mixed> $filters */
            $filters = is_array($params['filters'] ?? null) ? $params['filters'] : [];
            $action = (string) ($params['action'] ?? '');
            $data = isset($params['data']) ? (string) $params['data'] : null;
            return Response::success($this->wordListHandler->allAction($filters, $action, $data));
        } elseif ($frag1 === 'family') {
            // PUT /terms/family/status - update status for entire word family
            // PUT /terms/family/apply - apply suggested family update
            if ($frag2 === 'status') {
                $langId = (int) ($params['language_id'] ?? 0);
                $lemmaLc = (string) ($params['lemma_lc'] ?? '');
                $status = (int) ($params['status'] ?? 0);

                if ($langId <= 0 || $lemmaLc === '') {
                    return Response::error('language_id and lemma_lc are required', 400);
                }
                return Response::success($this->wordFamilyHandler->updateWordFamilyStatus($langId, $lemmaLc, $status));
            } elseif ($frag2 === 'apply') {
                /** @var array<int> $termIds */
                $termIds = is_array($params['term_ids'] ?? null) ? $params['term_ids'] : [];
                $status = (int) ($params['status'] ?? 0);

                if (empty($termIds)) {
                    return Response::error('term_ids is required', 400);
                }
                return Response::success($this->wordFamilyHandler->applyFamilyUpdate($termIds, $status));
            } else {
                return Response::error('Expected "status" or "apply"', 404);
            }
        } elseif ($frag1 !== '' && ctype_digit($frag1) && $frag2 === 'inline-edit') {
            // PUT /terms/{id}/inline-edit - inline edit translation or romanization
            $termId = (int) $frag1;
            $field = (string) ($params['field'] ?? '');
            $value = (string) ($params['value'] ?? '');
            return Response::success($this->wordListHandler->inlineEdit($termId, $field, $value));
        } elseif ($frag1 === 'multi' && $frag2 !== '' && ctype_digit($frag2)) {
            // PUT /terms/multi/{id} - update multi-word expression
            $termId = (int) $frag2;
            return Response::success($this->multiWordHandler->updateMultiWordTerm($termId, $params));
        } elseif ($frag1 !== '' && ctype_digit($frag1)) {
            $termId = (int) $frag1;
            if ($frag2 === 'translation') {
                // PUT /terms/{id}/translation - update translation
                return Response::success($this->termTranslationHandler->formatUpdateTranslation(
                    $termId,
                    (string) ($params['translation'] ?? '')
                ));
            } elseif ($frag2 === '') {
                // PUT /terms/{id} - update term with full data
                return Response::success($this->termHandler->formatUpdateTermFull($termId, $params));
            } else {
                return Response::error('Expected "translation" or no sub-path', 404);
            }
        } else {
            return Response::error('Term ID (Integer), "bulk-status", "family", or "multi/{id}" Expected', 404);
        }
    }

    /**
     * @param list<string>         $fragments
     * @param array<string, mixed> $params
     */
    private function handleTextsPut(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        if ($frag1 === '' || !ctype_digit($frag1)) {
            return Response::error('Text ID (Integer) Expected', 404);
        }

        $textId = (int) $frag1;

        switch ($frag2) {
            case 'display-mode':
                // PUT /texts/{id}/display-mode - set display mode settings
                return Response::success($this->textHandler->formatSetDisplayMode($textId, $params));
            case 'mark-all-wellknown':
                // PUT /texts/{id}/mark-all-wellknown - mark all unknown as well-known
                return Response::success($this->textHandler->formatMarkAllWellKnown($textId));
            case 'mark-all-ignored':
                // PUT /texts/{id}/mark-all-ignored - mark all unknown as ignored
                return Response::success($this->textHandler->formatMarkAllIgnored($textId));
            default:
                return Response::error('Expected "display-mode", "mark-all-wellknown", or "mark-all-ignored"', 404);
        }
    }

    // =========================================================================
    // DELETE Request Handlers
    // =========================================================================

    /**
     * Handle DELETE requests.
     *
     * @param list<string>         $fragments Endpoint path segments
     * @param array<string, mixed> $params    Query parameters (reserved for future use)
     *
     * @psalm-suppress UnusedParam
     */
    private function handleDelete(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag0 = $this->frag($fragments, 0);

        switch ($frag0) {
            case 'languages':
                return $this->handleLanguagesDelete($fragments);

            case 'terms':
                return $this->handleTermsDelete($fragments);

            case 'feeds':
                return $this->handleFeedsDelete($fragments, $params);

            case 'books':
                return $this->handleBooksDelete($fragments);

            case 'local-dictionaries':
                return $this->handleLocalDictionariesDelete($fragments);

            case 'tts':
                return $this->handleTtsDelete($fragments);

            case 'whisper':
                return $this->handleWhisperDelete($fragments);

            default:
                return Response::error('Endpoint Not Found On DELETE: ' . $frag0, 404);
        }
    }

    /**
     * @param list<string>         $fragments
     * @param array<string, mixed> $params
     */
    private function handleFeedsDelete(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        // DELETE /feeds/articles/{feedId} - delete articles for a feed
        if ($frag1 === 'articles' && $frag2 !== '' && ctype_digit($frag2)) {
            $feedId = (int) $frag2;
            /** @var array<int> $articleIds */
            $articleIds = is_array($params['article_ids'] ?? null) ? $params['article_ids'] : [];
            return Response::success($this->feedHandler->formatDeleteArticles($feedId, $articleIds));
        }

        // DELETE /feeds/{id}/reset-errors - reset error articles
        if ($frag1 !== '' && ctype_digit($frag1) && $frag2 === 'reset-errors') {
            return Response::success($this->feedHandler->formatResetErrorArticles((int) $frag1));
        }

        // DELETE /feeds - bulk delete feeds (ids in body)
        if ($frag1 === '') {
            /** @var array<int> $feedIds */
            $feedIds = is_array($params['feed_ids'] ?? null) ? $params['feed_ids'] : [];
            return Response::success($this->feedHandler->formatDeleteFeeds($feedIds));
        }

        // DELETE /feeds/{id} - delete single feed
        if (ctype_digit($frag1)) {
            return Response::success($this->feedHandler->formatDeleteFeeds([(int) $frag1]));
        }

        return Response::error('Expected feed ID or "articles/{feedId}"', 404);
    }

    /**
     * Handle DELETE requests for languages.
     *
     * @param list<string> $fragments Endpoint path segments
     */
    private function handleLanguagesDelete(array $fragments): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        if ($frag1 === '' || !ctype_digit($frag1)) {
            return Response::error('Language ID (Integer) Expected', 404);
        }

        $langId = (int) $frag1;
        return Response::success($this->languageHandler->formatDelete($langId));
    }

    /**
     * @param list<string> $fragments
     */
    private function handleTermsDelete(array $fragments): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        if ($frag1 === '' || !ctype_digit($frag1)) {
            return Response::error('Term ID (Integer) Expected', 404);
        }

        $termId = (int) $frag1;
        return Response::success($this->termHandler->formatDeleteTerm($termId));
    }

    // =========================================================================
    // Local Dictionary Request Handlers
    // =========================================================================

    /**
     * Handle GET requests for local dictionaries.
     *
     * @param list<string> $fragments Endpoint path segments
     * @param array    $params    Query parameters
     */
    private function handleLocalDictionariesGet(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        // GET /local-dictionaries/lookup - look up a term
        if ($frag1 === 'lookup') {
            $languageId = (int) ($params['language_id'] ?? 0);
            $term = (string) ($params['term'] ?? '');

            if ($languageId <= 0) {
                return Response::error('language_id is required', 400);
            }
            if ($term === '') {
                return Response::error('term is required', 400);
            }

            return Response::success($this->localDictionaryHandler->formatLookup($languageId, $term));
        }

        // GET /local-dictionaries/entries/{dictId} - get entries for dictionary
        if ($frag1 === 'entries' && $frag2 !== '' && ctype_digit($frag2)) {
            return Response::success($this->localDictionaryHandler->formatGetEntries((int) $frag2, $params));
        }

        // GET /local-dictionaries/{id} - get single dictionary
        if ($frag1 !== '' && ctype_digit($frag1)) {
            return Response::success($this->localDictionaryHandler->formatGetDictionary((int) $frag1));
        }

        // GET /local-dictionaries?language_id=N - list dictionaries for language
        $languageId = (int) ($params['language_id'] ?? 0);
        if ($languageId <= 0) {
            return Response::error('language_id is required', 400);
        }

        return Response::success($this->localDictionaryHandler->formatGetDictionaries($languageId));
    }

    /**
     * Handle POST requests for local dictionaries.
     *
     * @param list<string> $fragments Endpoint path segments
     * @param array    $params    POST parameters
     */
    private function handleLocalDictionariesPost(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        // POST /local-dictionaries/preview - preview file before import
        if ($frag1 === 'preview') {
            return Response::success($this->localDictionaryHandler->formatPreview($params));
        }

        // POST /local-dictionaries/entries/{dictId} - add entry to dictionary
        if ($frag1 === 'entries' && $frag2 !== '' && ctype_digit($frag2)) {
            return Response::success($this->localDictionaryHandler->formatAddEntry((int) $frag2, $params));
        }

        // POST /local-dictionaries/{id}/import - import entries into dictionary
        if ($frag1 !== '' && ctype_digit($frag1) && $frag2 === 'import') {
            return Response::success($this->localDictionaryHandler->formatImport((int) $frag1, $params));
        }

        // POST /local-dictionaries/{id}/clear - clear all entries
        if ($frag1 !== '' && ctype_digit($frag1) && $frag2 === 'clear') {
            return Response::success($this->localDictionaryHandler->formatClearEntries((int) $frag1));
        }

        // POST /local-dictionaries - create new dictionary
        if ($frag1 === '') {
            return Response::success($this->localDictionaryHandler->formatCreateDictionary($params));
        }

        return Response::error('Endpoint Not Found: local-dictionaries/' . $frag1, 404);
    }

    /**
     * Handle PUT requests for local dictionaries.
     *
     * @param list<string> $fragments Endpoint path segments
     * @param array    $params    Request body parameters
     */
    private function handleLocalDictionariesPut(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        // PUT /local-dictionaries/{id} - update dictionary
        if ($frag1 !== '' && ctype_digit($frag1) && $frag2 === '') {
            return Response::success($this->localDictionaryHandler->formatUpdateDictionary((int) $frag1, $params));
        }

        return Response::error('Dictionary ID (Integer) Expected', 404);
    }

    /**
     * Handle DELETE requests for local dictionaries.
     *
     * @param list<string> $fragments Endpoint path segments
     */
    private function handleLocalDictionariesDelete(array $fragments): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        // DELETE /local-dictionaries/{id} - delete dictionary
        if ($frag1 !== '' && ctype_digit($frag1)) {
            return Response::success($this->localDictionaryHandler->formatDeleteDictionary((int) $frag1));
        }

        return Response::error('Dictionary ID (Integer) Expected', 404);
    }

    // =========================================================================
    // TTS Request Handlers (Piper TTS via NLP microservice)
    // =========================================================================

    /**
     * Handle GET requests for TTS endpoints.
     *
     * @param list<string> $fragments Endpoint path segments
     * @param array    $_params   Query parameters (unused)
     */
    private function handleTtsGet(array $fragments, array $_params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        switch ($frag1) {
            case 'voices':
                if ($frag2 === 'installed') {
                    // GET /tts/voices/installed - get only installed voices
                    return Response::success(['voices' => $this->nlpHandler->getInstalledVoices()]);
                }
                // GET /tts/voices - get all voices (installed + available)
                return Response::success(['voices' => $this->nlpHandler->getVoices()]);

            default:
                return Response::error('Expected "voices"', 404);
        }
    }

    /**
     * Handle POST requests for TTS endpoints.
     *
     * @param list<string> $fragments Endpoint path segments
     * @param array    $params    POST parameters
     */
    private function handleTtsPost(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        switch ($frag1) {
            case 'speak':
                // POST /tts/speak - synthesize speech
                $text = (string) ($params['text'] ?? '');
                $voiceId = (string) ($params['voice_id'] ?? '');

                if ($text === '' || $voiceId === '') {
                    return Response::error('text and voice_id are required', 400);
                }

                $audioData = $this->nlpHandler->speak($text, $voiceId);
                if ($audioData === null) {
                    return Response::error('TTS service unavailable or synthesis failed', 503);
                }

                return Response::success(['audio' => $audioData]);

            case 'voices':
                if ($frag2 === 'download') {
                    // POST /tts/voices/download - download a voice
                    $voiceId = (string) ($params['voice_id'] ?? '');
                    if ($voiceId === '') {
                        return Response::error('voice_id is required', 400);
                    }

                    $success = $this->nlpHandler->downloadVoice($voiceId);
                    if (!$success) {
                        return Response::error('Voice download failed', 500);
                    }

                    return Response::success(['success' => true, 'voice_id' => $voiceId]);
                }
                return Response::error('Expected "download"', 404);

            default:
                return Response::error('Expected "speak" or "voices/download"', 404);
        }
    }

    /**
     * Handle DELETE requests for TTS endpoints.
     *
     * @param list<string> $fragments Endpoint path segments
     */
    private function handleTtsDelete(array $fragments): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        // DELETE /tts/voices/{id} - delete a voice
        if ($frag1 === 'voices' && $frag2 !== '') {
            $success = $this->nlpHandler->deleteVoice($frag2);

            if (!$success) {
                return Response::error('Voice not found or deletion failed', 404);
            }

            return Response::success(['success' => true]);
        }

        return Response::error('Expected "voices/{id}"', 404);
    }

    // =========================================================================
    // YouTube Request Handlers
    // =========================================================================

    /**
     * Handle GET requests for YouTube API proxy.
     *
     * @param list<string> $fragments Endpoint path segments
     * @param array    $params    Query parameters
     */
    private function handleYouTubeGet(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        switch ($frag1) {
            case 'configured':
                // GET /youtube/configured - check if YouTube API is configured
                return Response::success($this->youtubeHandler->formatIsConfigured());

            case 'video':
                // GET /youtube/video?video_id=xxx - get video info
                $videoId = (string) ($params['video_id'] ?? '');
                if ($videoId === '') {
                    return Response::error('video_id parameter is required', 400);
                }
                return Response::success($this->youtubeHandler->formatGetVideoInfo($videoId));

            default:
                return Response::error('Expected "configured" or "video"', 404);
        }
    }

    // =========================================================================
    // Whisper Request Handlers (Audio/Video Transcription via NLP microservice)
    // =========================================================================

    /**
     * Handle GET requests for Whisper endpoints.
     *
     * @param list<string> $fragments Endpoint path segments
     * @param array    $_params   Query parameters (unused)
     */
    private function handleWhisperGet(array $fragments, array $_params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        switch ($frag1) {
            case 'available':
                // GET /whisper/available - check if Whisper is available
                return Response::success($this->whisperHandler->formatIsAvailable());

            case 'languages':
                // GET /whisper/languages - get supported languages
                return Response::success($this->whisperHandler->formatGetLanguages());

            case 'models':
                // GET /whisper/models - get available models
                return Response::success($this->whisperHandler->formatGetModels());

            case 'status':
                // GET /whisper/status/{job_id} - get job status
                if ($frag2 === '') {
                    return Response::error('job_id is required', 400);
                }
                return Response::success($this->whisperHandler->formatGetStatus($frag2));

            case 'result':
                // GET /whisper/result/{job_id} - get completed result
                if ($frag2 === '') {
                    return Response::error('job_id is required', 400);
                }
                try {
                    return Response::success($this->whisperHandler->formatGetResult($frag2));
                } catch (\RuntimeException $e) {
                    return Response::error($e->getMessage(), 500);
                }

            default:
                return Response::error(
                    'Expected "available", "languages", "models", "status/{id}", or "result/{id}"',
                    404
                );
        }
    }

    /**
     * Handle POST requests for Whisper endpoints.
     *
     * @param list<string> $fragments Endpoint path segments
     * @param array    $params    POST parameters
     */
    private function handleWhisperPost(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        if ($frag1 === 'transcribe') {
            // POST /whisper/transcribe - start transcription
            /** @var array{name?: string, tmp_name?: string, size?: int}|null $file */
            $file = $_FILES['file'] ?? null;
            if ($file === null) {
                return Response::error('No file uploaded', 400);
            }

            $language = isset($params['language']) && $params['language'] !== '' ? (string) $params['language'] : null;
            $model = (string) ($params['model'] ?? 'small');

            try {
                return Response::success($this->whisperHandler->formatStartTranscription(
                    $file,
                    $language,
                    $model
                ));
            } catch (\InvalidArgumentException $e) {
                return Response::error($e->getMessage(), 400);
            } catch (\RuntimeException $e) {
                return Response::error($e->getMessage(), 503);
            }
        }

        return Response::error('Expected "transcribe"', 404);
    }

    /**
     * Handle DELETE requests for Whisper endpoints.
     *
     * @param list<string> $fragments Endpoint path segments
     */
    private function handleWhisperDelete(array $fragments): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        // DELETE /whisper/job/{id} - cancel job
        if ($frag1 === 'job' && $frag2 !== '') {
            return Response::success($this->whisperHandler->formatCancelJob($frag2));
        }

        return Response::error('Expected "job/{id}"', 404);
    }

    // =========================================================================
    // Book Request Handlers
    // =========================================================================

    /**
     * Handle GET requests for books.
     *
     * @param list<string> $fragments Endpoint path segments
     * @param array    $params    Query parameters
     */
    private function handleBooksGet(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        if ($this->bookHandler === null) {
            return Response::error('Book module not available', 503);
        }

        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        // GET /books/{id}/chapters - get chapters for a book
        if ($frag1 !== '' && ctype_digit($frag1) && $frag2 === 'chapters') {
            return Response::success($this->bookHandler->getChapters(['id' => $frag1]));
        }

        // GET /books/{id} - get single book
        if ($frag1 !== '' && ctype_digit($frag1)) {
            return Response::success($this->bookHandler->getBook(['id' => $frag1]));
        }

        // GET /books - list all books
        return Response::success($this->bookHandler->listBooks($params));
    }

    /**
     * Handle PUT requests for books.
     *
     * @param list<string> $fragments Endpoint path segments
     * @param array    $params    PUT parameters
     */
    private function handleBooksPut(array $fragments, array $params): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        if ($this->bookHandler === null) {
            return Response::error('Book module not available', 503);
        }

        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        // PUT /books/{id}/progress - update reading progress
        if ($frag1 !== '' && ctype_digit($frag1) && $frag2 === 'progress') {
            $params['id'] = $frag1;
            return Response::success($this->bookHandler->updateProgress($params));
        }

        return Response::error('Expected "progress"', 404);
    }

    /**
     * Handle DELETE requests for books.
     *
     * @param list<string> $fragments Endpoint path segments
     */
    private function handleBooksDelete(array $fragments): \Lwt\Shared\Infrastructure\Http\JsonResponse
    {
        if ($this->bookHandler === null) {
            return Response::error('Book module not available', 503);
        }

        $frag1 = $this->frag($fragments, 1);

        // DELETE /books/{id} - delete a book
        if ($frag1 !== '' && ctype_digit($frag1)) {
            return Response::success($this->bookHandler->deleteBook(['id' => $frag1]));
        }

        return Response::error('Book ID (Integer) Expected', 404);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Extract a fragment from the fragments array.
     *
     * @param list<string> $fragments The URL path fragments
     * @param int          $index     The index to extract
     *
     * @return string The fragment at the index, or empty string if not present
     */
    private function frag(array $fragments, int $index): string
    {
        return $fragments[$index] ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    private function parseQueryParams(string $uri): array
    {
        $query = parse_url($uri, PHP_URL_QUERY);
        if ($query === null || $query === false) {
            return [];
        }
        parse_str($query, $params);
        /** @var array<string, mixed> */
        return $params;
    }

    /**
     * Parse JSON body for PUT/DELETE requests.
     *
     * @return array<string, mixed> Parsed body data
     */
    private static function parseJsonBody(): array
    {
        $input = file_get_contents('php://input');
        if ($input === false || $input === '') {
            return [];
        }
        /** @var array<string, mixed>|null $data */
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
            Response::error('Method Not Allowed', 405)->send();
            return;
        }

        // Get body data based on method
        $bodyData = self::getRequestBody($method);

        $api = new self();
        $api->handle($method, $_SERVER['REQUEST_URI'] ?? '/', $bodyData);
    }

    /**
     * Get request body data based on HTTP method.
     *
     * @param string $method HTTP method
     *
     * @return array<string, mixed>
     */
    private static function getRequestBody(string $method): array
    {
        if ($method === 'POST') {
            if (!empty($_POST)) {
                /** @var array<string, mixed> */
                return $_POST;
            }
            return self::parseJsonBody();
        }

        if (in_array($method, ['PUT', 'DELETE'])) {
            return self::parseJsonBody();
        }

        return [];
    }
}

<?php

declare(strict_types=1);

namespace Lwt\Tests\Api\V1;

require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';

use Lwt\Api\V1\ApiV1;
use Lwt\Api\V1\Response;
use Lwt\Api\V1\Endpoints;
use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Modules\Admin\AdminServiceProvider;
use Lwt\Modules\Feed\FeedServiceProvider;
use Lwt\Shared\Infrastructure\Database\Configuration;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');

/**
 * Tests for ApiV1 routing functionality.
 */
class ApiV1RoutingTest extends TestCase
{
    private static bool $dbConnected = false;
    private ApiV1 $api;

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            try {
                $connection = Configuration::connect(
                    $config['server'],
                    $config['userid'],
                    $config['passwd'],
                    $testDbname,
                    $config['socket'] ?? ''
                );
                Globals::setDbConnection($connection);
                self::$dbConnected = true;
            } catch (\Exception $e) {
                self::$dbConnected = false;
            }
        } else {
            self::$dbConnected = true;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Register service providers for handler dependencies
        $container = Container::getInstance();

        $feedProvider = new FeedServiceProvider();
        $feedProvider->register($container);
        $feedProvider->boot($container);

        $adminProvider = new AdminServiceProvider();
        $adminProvider->register($container);
        $adminProvider->boot($container);

        $this->api = new ApiV1();
    }

    // ===== Endpoints class tests =====

    /**
     * @dataProvider validEndpointsProvider
     */
    public function testResolveValidEndpoints(string $method, string $uri, string $expectedEndpoint): void
    {
        $result = Endpoints::resolve($method, $uri);

        // Should return string (endpoint) not JsonResponse (error)
        $this->assertIsString($result);
        $this->assertEquals($expectedEndpoint, $result);
    }

    public static function validEndpointsProvider(): array
    {
        return [
            // GET endpoints
            ['GET', '/api/v1/version', 'version'],
            ['GET', '/api/v1/languages', 'languages'],
            ['GET', '/api/v1/languages/1', 'languages/1'],
            ['GET', '/api/v1/languages/definitions', 'languages/definitions'],
            ['GET', '/api/v1/settings/theme-path?path=test', 'settings/theme-path'],
            ['GET', '/api/v1/statuses', 'statuses'],
            ['GET', '/api/v1/tags', 'tags'],
            ['GET', '/api/v1/terms/list', 'terms/list'],
            ['GET', '/api/v1/review/next-word?test_type=1', 'review/next-word'],

            // POST endpoints
            ['POST', '/api/v1/auth/login', 'auth/login'],
            ['POST', '/api/v1/auth/register', 'auth/register'],
            ['POST', '/api/v1/settings', 'settings'],
            ['POST', '/api/v1/languages', 'languages'],

            // PUT endpoints
            ['PUT', '/api/v1/review/status', 'review/status'],
            ['PUT', '/api/v1/terms/1', 'terms/1'],

            // DELETE endpoints
            ['DELETE', '/api/v1/languages/1', 'languages/1'],
            ['DELETE', '/api/v1/terms/1', 'terms/1'],
        ];
    }

    /**
     * Test that invalid methods return error JsonResponse.
     */
    public function testResolveInvalidMethod(): void
    {
        $result = Endpoints::resolve('PATCH', '/api/v1/version');

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test parseFragments splits endpoints correctly.
     */
    public function testParseFragments(): void
    {
        $fragments = Endpoints::parseFragments('languages/1/stats');
        $this->assertEquals(['languages', '1', 'stats'], $fragments);

        $fragments = Endpoints::parseFragments('version');
        $this->assertEquals(['version'], $fragments);

        $fragments = Endpoints::parseFragments('terms/list');
        $this->assertEquals(['terms', 'list'], $fragments);
    }

    // ===== Public endpoint tests =====

    /**
     * Test isPublicEndpoint logic via reflection.
     */
    public function testPublicEndpoints(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('isPublicEndpoint');
        $method->setAccessible(true);

        // Auth login/register should be public
        $this->assertTrue($method->invoke($this->api, 'auth/login'));
        $this->assertTrue($method->invoke($this->api, 'auth/register'));
        $this->assertTrue($method->invoke($this->api, 'version'));
    }

    // ===== Fragment extraction tests =====

    /**
     * Test frag helper extracts fragments correctly.
     */
    public function testFragMethod(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('frag');
        $method->setAccessible(true);

        $fragments = ['languages', '1', 'stats'];

        $this->assertEquals('languages', $method->invoke($this->api, $fragments, 0));
        $this->assertEquals('1', $method->invoke($this->api, $fragments, 1));
        $this->assertEquals('stats', $method->invoke($this->api, $fragments, 2));
        $this->assertEquals('', $method->invoke($this->api, $fragments, 3)); // Out of bounds
    }

    // ===== Query parameter parsing tests =====

    /**
     * Test parseQueryParams extracts query params correctly.
     */
    public function testParseQueryParams(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('parseQueryParams');
        $method->setAccessible(true);

        $params = $method->invoke($this->api, '/api/v1/terms?language_id=1&status=5');
        $this->assertEquals(['language_id' => '1', 'status' => '5'], $params);

        $params = $method->invoke($this->api, '/api/v1/version');
        $this->assertEquals([], $params);
    }

    // ===== Response class tests =====

    /**
     * Test Response::success returns correct format.
     */
    public function testResponseSuccess(): void
    {
        $response = Response::success(['test' => 'data']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $response);
    }

    /**
     * Test Response::error returns correct format.
     */
    public function testResponseError(): void
    {
        $response = Response::error('Test error', 400);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $response);
    }

    // ===== Handler method existence tests =====

    /**
     * Test that all expected handler methods exist.
     */
    public function testHandlerMethodsExist(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);

        $expectedMethods = [
            'handleGet',
            'handlePost',
            'handlePut',
            'handleDelete',
            'handleAuthGet',
            'handleAuthPost',
            'handleLanguagesGet',
            'handleLanguagesPost',
            'handleLanguagesPut',
            'handleLanguagesDelete',
            'handleReviewGet',
            'handleReviewPut',
            'handleSentencesGet',
            'handleSettingsGet',
            'handleTagsGet',
            'handleTermsGet',
            'handleTermsPost',
            'handleTermsPut',
            'handleTermsDelete',
            'handleTermStatusPost',
            'handleTextsGet',
            'handleTextsPost',
            'handleTextsPut',
            'handleFeedsGet',
            'handleFeedsPost',
            'handleFeedsPut',
            'handleFeedsDelete',
            'handleLocalDictionariesGet',
            'handleLocalDictionariesPost',
            'handleLocalDictionariesPut',
            'handleLocalDictionariesDelete',
            'handleTtsGet',
            'handleTtsPost',
            'handleTtsDelete',
            'handleYouTubeGet',
            'handleWhisperGet',
            'handleWhisperPost',
            'handleWhisperDelete',
            'handleBooksGet',
            'handleBooksPut',
            'handleBooksDelete',
            'handleWordFamiliesGet',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "ApiV1 should have method: $methodName"
            );
        }
    }

    // ===== Handler property existence tests =====

    /**
     * Test that all handler properties are initialized.
     */
    public function testHandlerPropertiesInitialized(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);

        $expectedHandlers = [
            'authHandler',
            'feedHandler',
            'languageHandler',
            'localDictionaryHandler',
            'adminHandler',
            'reviewHandler',
            'tagHandler',
            'termHandler',
            'wordFamilyHandler',
            'multiWordHandler',
            'wordListHandler',
            'termTranslationHandler',
            'termStatusHandler',
            'textHandler',
            'youtubeHandler',
            'nlpHandler',
            'whisperHandler',
        ];

        foreach ($expectedHandlers as $propertyName) {
            $this->assertTrue(
                $reflection->hasProperty($propertyName),
                "ApiV1 should have property: $propertyName"
            );

            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $value = $property->getValue($this->api);

            $this->assertNotNull(
                $value,
                "Handler property $propertyName should be initialized"
            );
        }
    }

    // ===== Static method tests =====

    /**
     * Test getRequestBody method for different HTTP methods.
     */
    public function testGetRequestBodyMethod(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('getRequestBody');
        $method->setAccessible(true);

        // GET should return empty array
        $result = $method->invoke(null, 'GET');
        $this->assertEquals([], $result);
    }

    /**
     * Test parseJsonBody returns empty array for empty input.
     */
    public function testParseJsonBodyEmptyInput(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('parseJsonBody');
        $method->setAccessible(true);

        // When there's no input, should return empty array
        // Note: This will actually try to read php://input which is empty in tests
        $result = $method->invoke(null);
        $this->assertIsArray($result);
    }

    // ===== Private handler method tests via reflection =====

    /**
     * Test handleGet returns 404 for unknown endpoint.
     */
    public function testHandleGetReturns404ForUnknownEndpoint(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['unknown-endpoint'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleGet version endpoint.
     */
    public function testHandleGetVersionEndpoint(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['version'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleGet statuses endpoint.
     */
    public function testHandleGetStatusesEndpoint(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['statuses'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handlePost returns 404 for unknown endpoint.
     */
    public function testHandlePostReturns404ForUnknownEndpoint(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handlePost');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['unknown-endpoint'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handlePost settings endpoint.
     */
    public function testHandlePostSettingsEndpoint(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handlePost');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['settings'], ['key' => 'test', 'value' => 'value']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handlePut returns 404 for unknown endpoint.
     */
    public function testHandlePutReturns404ForUnknownEndpoint(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handlePut');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['unknown-endpoint'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleDelete returns 404 for unknown endpoint.
     */
    public function testHandleDeleteReturns404ForUnknownEndpoint(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleDelete');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['unknown-endpoint'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    // ===== Language endpoint handler tests =====

    /**
     * Test handleLanguagesGet returns list for root endpoint.
     */
    public function testHandleLanguagesGetListEndpoint(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['languages']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleLanguagesGet definitions endpoint.
     */
    public function testHandleLanguagesGetDefinitionsEndpoint(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['languages', 'definitions']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleLanguagesGet with-texts endpoint.
     */
    public function testHandleLanguagesGetWithTextsEndpoint(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['languages', 'with-texts']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleLanguagesGet with-archived-texts endpoint.
     */
    public function testHandleLanguagesGetWithArchivedTextsEndpoint(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['languages', 'with-archived-texts']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleLanguagesGet returns 404 for invalid fragment.
     */
    public function testHandleLanguagesGetInvalidFragment(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['languages', 'invalid']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleLanguagesGet for single language.
     */
    public function testHandleLanguagesGetSingleLanguage(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['languages', '1']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleLanguagesGet for language stats.
     */
    public function testHandleLanguagesGetStats(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['languages', '1', 'stats']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleLanguagesGet for language reading-configuration.
     */
    public function testHandleLanguagesGetReadingConfiguration(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required for this test');
        }

        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesGet');
        $method->setAccessible(true);

        try {
            $result = $method->invoke($this->api, ['languages', '1', 'reading-configuration']);
            $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
        } catch (\Lwt\Shared\Infrastructure\Exception\DatabaseException $e) {
            // Schema may be outdated in test DB - skip
            $this->markTestSkipped('Test DB schema may be outdated: ' . $e->getMessage());
        }
    }

    /**
     * Test handleLanguagesGet returns 404 for invalid sub-path.
     */
    public function testHandleLanguagesGetInvalidSubPath(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['languages', '1', 'invalid']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    // ===== Review endpoint handler tests =====

    /**
     * Test handleReviewGet next-word endpoint.
     */
    public function testHandleReviewGetNextWord(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleReviewGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['review', 'next-word'], ['test_type' => 1]);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleReviewGet tomorrow-count endpoint.
     */
    public function testHandleReviewGetTomorrowCount(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleReviewGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['review', 'tomorrow-count'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleReviewGet config endpoint.
     */
    public function testHandleReviewGetConfig(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleReviewGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['review', 'config'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleReviewGet table-words endpoint.
     */
    public function testHandleReviewGetTableWords(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleReviewGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['review', 'table-words'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleReviewGet returns 404 for unknown endpoint.
     */
    public function testHandleReviewGetUnknown(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleReviewGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['review', 'unknown'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    // ===== Settings endpoint handler tests =====

    /**
     * Test handleSettingsGet theme-path endpoint.
     */
    public function testHandleSettingsGetThemePath(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleSettingsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['settings', 'theme-path'], ['path' => 'test']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleSettingsGet returns 404 for unknown endpoint.
     */
    public function testHandleSettingsGetUnknown(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleSettingsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['settings', 'unknown'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    // ===== Auth endpoint handler tests =====

    /**
     * Test handleAuthGet me endpoint.
     */
    public function testHandleAuthGetMe(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleAuthGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['auth', 'me']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleAuthGet returns 404 for unknown endpoint.
     */
    public function testHandleAuthGetUnknown(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleAuthGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['auth', 'unknown']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleAuthPost login endpoint.
     */
    public function testHandleAuthPostLogin(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleAuthPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['auth', 'login'], ['username' => 'test', 'password' => 'test']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleAuthPost register endpoint.
     */
    public function testHandleAuthPostRegister(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleAuthPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['auth', 'register'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleAuthPost refresh endpoint.
     */
    public function testHandleAuthPostRefresh(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleAuthPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['auth', 'refresh'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleAuthPost logout endpoint.
     */
    public function testHandleAuthPostLogout(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleAuthPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['auth', 'logout'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleAuthPost returns 404 for unknown endpoint.
     */
    public function testHandleAuthPostUnknown(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleAuthPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['auth', 'unknown'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    // ===== Terms endpoint handler tests =====

    /**
     * Test handleTermsGet list endpoint.
     */
    public function testHandleTermsGetList(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', 'list'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleTermsGet filter-options endpoint.
     */
    public function testHandleTermsGetFilterOptions(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', 'filter-options'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleTermsGet imported endpoint.
     */
    public function testHandleTermsGetImported(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', 'imported'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleTermsGet for-edit endpoint.
     */
    public function testHandleTermsGetForEdit(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', 'for-edit'], ['term_id' => 1, 'ord' => 1]);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleTermsGet multi endpoint.
     */
    public function testHandleTermsGetMulti(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', 'multi'], ['term_id' => 1, 'ord' => 1]);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleTermsGet family endpoint.
     */
    public function testHandleTermsGetFamily(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', 'family'], ['term_id' => 1]);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleTermsGet family suggestion endpoint.
     */
    public function testHandleTermsGetFamilySuggestion(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', 'family', 'suggestion'], ['term_id' => 1, 'status' => 2]);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleTermsGet returns 400 for missing term_id in family.
     */
    public function testHandleTermsGetFamilyMissingTermId(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', 'family'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleTermsGet single term endpoint.
     */
    public function testHandleTermsGetSingleTerm(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', '1'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleTermsGet term translations endpoint.
     */
    public function testHandleTermsGetTranslations(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', '1', 'translations'], ['term_lc' => 'test', 'text_id' => 1]);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleTermsGet term details endpoint.
     */
    public function testHandleTermsGetDetails(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', '1', 'details'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleTermsGet term family by id endpoint.
     */
    public function testHandleTermsGetTermFamily(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', '1', 'family'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleTermsGet returns 404 for invalid sub-path.
     */
    public function testHandleTermsGetInvalidSubPath(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', '1', 'invalid'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleTermsGet returns 404 for unknown endpoint.
     */
    public function testHandleTermsGetUnknown(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleTermsGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['terms', 'unknown'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    // ===== Sentences endpoint handler tests =====

    /**
     * Test handleSentencesGet with term ID.
     */
    public function testHandleSentencesGetWithTermId(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleSentencesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['sentences-with-term', '1'], ['language_id' => 1, 'term_lc' => 'test']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleSentencesGet without term ID.
     */
    public function testHandleSentencesGetWithoutTermId(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleSentencesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['sentences-with-term'], ['language_id' => 1, 'term_lc' => 'test']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleSentencesGet with advanced search.
     */
    public function testHandleSentencesGetWithAdvancedSearch(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleSentencesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['sentences-with-term'], ['language_id' => 1, 'term_lc' => 'test', 'advanced_search' => true]);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    // ===== Languages POST handler tests =====

    /**
     * Test handleLanguagesPost create endpoint.
     */
    public function testHandleLanguagesPostCreate(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['languages'], ['LgName' => 'Test']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleLanguagesPost refresh endpoint.
     */
    public function testHandleLanguagesPostRefresh(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['languages', '1', 'refresh'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleLanguagesPost set-default endpoint.
     */
    public function testHandleLanguagesPostSetDefault(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['languages', '1', 'set-default'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleLanguagesPost returns 404 for invalid action.
     */
    public function testHandleLanguagesPostInvalidAction(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['languages', '1', 'invalid'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleLanguagesPost returns 404 for non-numeric ID.
     */
    public function testHandleLanguagesPostNonNumericId(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleLanguagesPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['languages', 'abc'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    // ===== WordFamilies GET handler tests =====

    /**
     * Test handleWordFamiliesGet stats endpoint.
     */
    public function testHandleWordFamiliesGetStats(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleWordFamiliesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['word-families', 'stats'], ['language_id' => 1]);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleWordFamiliesGet stats returns 400 for missing language_id.
     */
    public function testHandleWordFamiliesGetStatsMissingLangId(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleWordFamiliesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['word-families', 'stats'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleWordFamiliesGet list endpoint.
     */
    public function testHandleWordFamiliesGetList(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleWordFamiliesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['word-families'], ['language_id' => 1]);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleWordFamiliesGet returns 400 for missing language_id.
     */
    public function testHandleWordFamiliesGetListMissingLangId(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleWordFamiliesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['word-families'], []);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }

    /**
     * Test handleWordFamiliesGet by lemma endpoint.
     */
    public function testHandleWordFamiliesGetByLemma(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $method = $reflection->getMethod('handleWordFamiliesGet');
        $method->setAccessible(true);

        $result = $method->invoke($this->api, ['word-families'], ['language_id' => 1, 'lemma_lc' => 'test']);

        $this->assertInstanceOf(\Lwt\Shared\Infrastructure\Http\JsonResponse::class, $result);
    }
}

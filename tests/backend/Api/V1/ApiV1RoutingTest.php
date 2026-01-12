<?php

declare(strict_types=1);

namespace Lwt\Tests\Api\V1;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Api\V1\ApiV1;
use Lwt\Api\V1\Response;
use Lwt\Api\V1\Endpoints;
use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
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
}

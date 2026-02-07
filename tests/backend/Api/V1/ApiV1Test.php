<?php

declare(strict_types=1);

namespace Lwt\Tests\Api\V1;

require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';

use Lwt\Api\V1\ApiV1;
use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Shared\Infrastructure\Container\CoreServiceProvider;
use Lwt\Shared\Infrastructure\Container\ControllerServiceProvider;
use Lwt\Shared\Infrastructure\Container\RepositoryServiceProvider;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Modules\Text\TextServiceProvider;
use Lwt\Modules\Language\LanguageServiceProvider;
use Lwt\Modules\Feed\FeedServiceProvider;
use Lwt\Modules\Vocabulary\VocabularyServiceProvider;
use Lwt\Modules\Tags\TagsServiceProvider;
use Lwt\Modules\Review\ReviewServiceProvider;
use Lwt\Modules\Admin\AdminServiceProvider;
use Lwt\Modules\User\UserServiceProvider;
use Lwt\Modules\Dictionary\DictionaryServiceProvider;
use Lwt\Modules\Book\BookServiceProvider;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../../src/backend/Api/V1/ApiV1.php';

/**
 * Unit tests for the ApiV1 class.
 *
 * Tests main API V1 handler functionality.
 */
class ApiV1Test extends TestCase
{
    private ApiV1 $api;
    private static bool $providersRegistered = false;

    public static function setUpBeforeClass(): void
    {
        if (!self::$providersRegistered) {
            $container = Container::getInstance();

            $providers = [
                new CoreServiceProvider(),
                new ControllerServiceProvider(),
                new RepositoryServiceProvider(),
                new TextServiceProvider(),
                new LanguageServiceProvider(),
                new FeedServiceProvider(),
                new VocabularyServiceProvider(),
                new TagsServiceProvider(),
                new ReviewServiceProvider(),
                new AdminServiceProvider(),
                new UserServiceProvider(),
                new DictionaryServiceProvider(),
                new BookServiceProvider(),
            ];

            foreach ($providers as $provider) {
                $provider->register($container);
            }
            foreach ($providers as $provider) {
                $provider->boot($container);
            }

            self::$providersRegistered = true;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->api = new ApiV1();
    }

    // ===== Class structure tests =====

    /**
     * Test that ApiV1 class can be instantiated.
     */
    public function testCanInstantiate(): void
    {
        $this->assertInstanceOf(ApiV1::class, $this->api);
    }

    /**
     * Test that ApiV1 class has the required methods.
     */
    public function testClassHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);

        // Public instance method
        $this->assertTrue($reflection->hasMethod('handle'));

        // Public static method
        $this->assertTrue($reflection->hasMethod('handleRequest'));
        $this->assertTrue($reflection->getMethod('handleRequest')->isStatic());
    }

    /**
     * Test handle method signature.
     */
    public function testHandleMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(ApiV1::class, 'handle');
        $params = $reflection->getParameters();

        $this->assertCount(3, $params);
        $this->assertEquals('method', $params[0]->getName());
        $this->assertEquals('uri', $params[1]->getName());
        $this->assertEquals('postData', $params[2]->getName());
    }

    /**
     * Test that constructor accepts optional Container parameter.
     */
    public function testConstructorAcceptsContainer(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);

        $this->assertTrue(
            $reflection->hasProperty('container'),
            "ApiV1 should have a container property"
        );

        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = $constructor->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('container', $params[0]->getName());
        $this->assertTrue($params[0]->allowsNull());
    }

    /**
     * Test VERSION constant exists.
     */
    public function testVersionConstantExists(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $this->assertTrue($reflection->hasConstant('VERSION'));
    }

    /**
     * Test RELEASE_DATE constant exists.
     */
    public function testReleaseDateConstantExists(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $this->assertTrue($reflection->hasConstant('RELEASE_DATE'));
    }

    /**
     * Test HANDLER_MAP constant exists and has expected entries.
     */
    public function testHandlerMapConstantExists(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $this->assertTrue($reflection->hasConstant('HANDLER_MAP'));

        $constant = $reflection->getReflectionConstant('HANDLER_MAP');
        $this->assertNotFalse($constant);
        $map = $constant->getValue();
        $this->assertIsArray($map);

        // Verify key route entries exist
        $expectedRoutes = [
            'auth', 'languages', 'review', 'settings', 'tags',
            'terms', 'word-families', 'texts', 'feeds', 'books',
            'local-dictionaries', 'youtube', 'tts', 'whisper',
        ];

        foreach ($expectedRoutes as $route) {
            $this->assertArrayHasKey(
                $route,
                $map,
                "HANDLER_MAP should contain route: $route"
            );
        }
    }

    /**
     * Test PUBLIC_ENDPOINTS constant exists.
     */
    public function testPublicEndpointsConstantExists(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $this->assertTrue($reflection->hasConstant('PUBLIC_ENDPOINTS'));
    }

    // ===== Key private methods exist =====

    /**
     * Test that key private methods exist.
     */
    public function testPrivateMethodsExist(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);

        $expectedMethods = [
            'dispatch',
            'handleInlineEndpoints',
            'handleSentencesGet',
            'isPublicEndpoint',
            'validateAuth',
            'parseQueryParams',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "ApiV1 should have method: $methodName"
            );
        }
    }
}

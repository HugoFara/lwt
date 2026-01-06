<?php declare(strict_types=1);
namespace Lwt\Tests\Api\V1;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Api\V1\ApiV1;
use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Modules\Admin\AdminServiceProvider;
use Lwt\Modules\Feed\FeedServiceProvider;
use Lwt\Shared\Infrastructure\Database\Configuration;
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
     * Test that constructor initializes all handlers.
     */
    public function testConstructorInitializesHandlers(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);

        // Get all private handler properties
        // Note: importHandler, improvedTextHandler, and mediaHandler have been
        // consolidated into their respective module handlers (termHandler, textHandler, adminHandler)
        $expectedHandlers = [
            'feedHandler',
            'languageHandler',
            'localDictionaryHandler',
            'adminHandler',  // Combined settings + statistics + media handler
            'reviewHandler',
            'tagHandler',
            'termHandler',   // Also handles import functionality
            'textHandler',   // Also handles improved text functionality
            'authHandler',
        ];

        foreach ($expectedHandlers as $handlerName) {
            $this->assertTrue(
                $reflection->hasProperty($handlerName),
                "ApiV1 should have property: $handlerName"
            );
        }
    }

    /**
     * Test VERSION constant exists.
     */
    public function testVersionConstantExists(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);
        $constants = $reflection->getConstants();

        // VERSION is private constant, check via ReflectionClassConstant
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

    // ===== Handler private method tests =====

    /**
     * Test that private handler methods exist.
     */
    public function testPrivateHandlerMethodsExist(): void
    {
        $reflection = new \ReflectionClass(ApiV1::class);

        $expectedMethods = [
            'handleGet',
            'handlePost',
            'handleLanguagesGet',
            'handleReviewGet',
            'handleSentencesGet',
            'handleSettingsGet',
            'handleTermsGet',
            'handleTextsGet',
            'handleTextsPost',
            'handleTermsPost',
            'handleTermStatusPost',
            'handleFeedsPost',
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

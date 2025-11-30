<?php declare(strict_types=1);
namespace Lwt\Tests\Api\V1\Handlers;

require_once __DIR__ . '/../../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Api\V1\Handlers\TextHandler;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../../../src/backend/Api/V1/ApiV1.php';
require_once __DIR__ . '/../../../../../src/backend/Api/V1/Handlers/TextHandler.php';

/**
 * Unit tests for the TextHandler class.
 *
 * Tests text-related API operations.
 */
class TextHandlerTest extends TestCase
{
    private static bool $dbConnected = false;
    private TextHandler $handler;

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
        $this->handler = new TextHandler();
    }

    // ===== Class structure tests =====

    /**
     * Test that TextHandler class has the required methods.
     */
    public function testClassHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(TextHandler::class);

        // Business logic methods
        $this->assertTrue($reflection->hasMethod('saveTextPosition'));
        $this->assertTrue($reflection->hasMethod('saveAudioPosition'));
        $this->assertTrue($reflection->hasMethod('saveImprText'));
        $this->assertTrue($reflection->hasMethod('saveImprTextData'));

        // API formatter methods
        $this->assertTrue($reflection->hasMethod('formatSetTextPosition'));
        $this->assertTrue($reflection->hasMethod('formatSetAudioPosition'));
        $this->assertTrue($reflection->hasMethod('formatSetAnnotation'));
    }

    /**
     * Test formatSetTextPosition returns correct message format.
     */
    public function testFormatSetTextPositionReturnsCorrectFormat(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Use a non-existent text ID - method should still work (0 rows affected)
        $result = $this->handler->formatSetTextPosition(999999999, 100);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('text', $result);
        $this->assertEquals('Reading position set', $result['text']);
    }

    /**
     * Test formatSetAudioPosition returns correct message format.
     */
    public function testFormatSetAudioPositionReturnsCorrectFormat(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Use a non-existent text ID - method should still work (0 rows affected)
        $result = $this->handler->formatSetAudioPosition(999999999, 5000);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('audio', $result);
        $this->assertEquals('Audio position set', $result['audio']);
    }

    /**
     * Test all public methods are accessible.
     */
    public function testPublicMethods(): void
    {
        $reflection = new \ReflectionClass(TextHandler::class);
        $publicMethods = array_filter(
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
            fn($m) => !$m->isConstructor()
        );

        // Should have at least 6 public methods
        $this->assertGreaterThanOrEqual(6, count($publicMethods));
    }
}

<?php declare(strict_types=1);
namespace Lwt\Tests\Api\V1\Handlers;

require_once __DIR__ . '/../../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Api\V1\Handlers\TermHandler;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../../../src/backend/Api/V1/ApiV1.php';

/**
 * Unit tests for the TermHandler class.
 *
 * Tests term/word-related API operations.
 */
class TermHandlerTest extends TestCase
{
    private static bool $dbConnected = false;
    private TermHandler $handler;

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
        $this->handler = new TermHandler();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        if (self::$dbConnected) {
            $prefix = Globals::getTablePrefix();
            Connection::query("DELETE FROM {$prefix}words WHERE WoText LIKE 'test_api_%'");
        }
        parent::tearDown();
    }

    // ===== getNewStatus tests =====

    /**
     * Test status increment from 1.
     */
    public function testGetNewStatusIncrementFrom1(): void
    {
        $result = $this->handler->getNewStatus(1, true);
        $this->assertEquals(2, $result);
    }

    /**
     * Test status increment from 5 goes to 99 (well-known).
     */
    public function testGetNewStatusIncrementFrom5(): void
    {
        $result = $this->handler->getNewStatus(5, true);
        $this->assertEquals(99, $result);
    }

    /**
     * Test status increment from 98 (ignored) goes to 1.
     */
    public function testGetNewStatusIncrementFrom98(): void
    {
        $result = $this->handler->getNewStatus(98, true);
        $this->assertEquals(1, $result);
    }

    /**
     * Test status decrement from 1 goes to 98 (ignored).
     */
    public function testGetNewStatusDecrementFrom1(): void
    {
        $result = $this->handler->getNewStatus(1, false);
        $this->assertEquals(98, $result);
    }

    /**
     * Test status decrement from 99 (well-known) goes to 5.
     */
    public function testGetNewStatusDecrementFrom99(): void
    {
        $result = $this->handler->getNewStatus(99, false);
        $this->assertEquals(5, $result);
    }

    /**
     * Test status decrement from 3.
     */
    public function testGetNewStatusDecrementFrom3(): void
    {
        $result = $this->handler->getNewStatus(3, false);
        $this->assertEquals(2, $result);
    }

    // ===== formatSetStatus tests =====

    /**
     * Test formatSetStatus returns proper error format.
     */
    public function testFormatSetStatusWithInvalidTerm(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Use a non-existent term ID
        $result = $this->handler->formatSetStatus(999999999, 3);

        // Should return set => 0 because no rows were affected
        $this->assertArrayHasKey('set', $result);
        $this->assertEquals(0, $result['set']);
    }

    // ===== formatIncrementStatus tests =====

    /**
     * Test formatIncrementStatus returns error for non-existent term.
     */
    public function testFormatIncrementStatusWithInvalidTerm(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatIncrementStatus(999999999, true);

        // Should return error because term doesn't exist
        $this->assertArrayHasKey('error', $result);
    }

    // ===== formatUpdateTranslation tests =====

    /**
     * Test formatUpdateTranslation returns error for non-existent term.
     */
    public function testFormatUpdateTranslationWithInvalidTerm(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatUpdateTranslation(999999999, 'test translation');

        // Should return error because 0 words found
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Error', $result['error']);
    }

    // ===== Class structure tests =====

    /**
     * Test that TermHandler class has the required methods.
     */
    public function testClassHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(TermHandler::class);

        // Business logic methods
        $this->assertTrue($reflection->hasMethod('addNewTermTranslation'));
        $this->assertTrue($reflection->hasMethod('editTermTranslation'));
        $this->assertTrue($reflection->hasMethod('checkUpdateTranslation'));
        $this->assertTrue($reflection->hasMethod('setWordStatus'));
        $this->assertTrue($reflection->hasMethod('getNewStatus'));
        $this->assertTrue($reflection->hasMethod('updateWordStatus'));
        $this->assertTrue($reflection->hasMethod('incrementTermStatus'));

        // API formatter methods
        $this->assertTrue($reflection->hasMethod('formatUpdateTranslation'));
        $this->assertTrue($reflection->hasMethod('formatAddTranslation'));
        $this->assertTrue($reflection->hasMethod('formatIncrementStatus'));
        $this->assertTrue($reflection->hasMethod('formatSetStatus'));
    }

    /**
     * Test that all public methods are accessible.
     */
    public function testPublicMethods(): void
    {
        $reflection = new \ReflectionClass(TermHandler::class);
        $publicMethods = array_filter(
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
            fn($m) => !$m->isConstructor()
        );

        // Should have at least 8 public methods
        $this->assertGreaterThanOrEqual(8, count($publicMethods));
    }
}

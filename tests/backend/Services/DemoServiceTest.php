<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Services\DemoService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/DemoService.php';

/**
 * Unit tests for the DemoService class.
 *
 * Tests demo database installation through the service layer.
 */
class DemoServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private DemoService $service;

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        // Always ensure the database name is set (important for test isolation)
        Globals::setDatabaseName($testDbname);

        if (!Globals::getDbConnection()) {
            $connection = Configuration::connect(
                $config['server'],
                $config['userid'],
                $config['passwd'],
                $testDbname,
                $config['socket'] ?? ''
            );
            Globals::setDbConnection($connection);
        }
        self::$dbConnected = (Globals::getDbConnection() !== null);
    }

    protected function setUp(): void
    {
        $this->service = new DemoService();
    }

    // ===== getPrefixInfo() tests =====

    public function testGetPrefixInfoReturnsString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getPrefixInfo();
        $this->assertIsString($result);
    }

    public function testGetPrefixInfoContainsTableSetInfo(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getPrefixInfo();

        // No table prefix feature - returns empty string
        $this->assertEquals('', $result);
    }

    // ===== getDatabaseName() tests =====

    public function testGetDatabaseNameReturnsString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getDatabaseName();
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // ===== getLanguageCount() tests =====

    public function testGetLanguageCountReturnsInteger(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageCount();
        $this->assertIsInt($result);
    }

    public function testGetLanguageCountIsNonNegative(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageCount();
        $this->assertGreaterThanOrEqual(0, $result);
    }

    // ===== installDemo() tests =====
    // Note: We don't actually run installDemo to avoid destroying test data

    public function testInstallDemoMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->service, 'installDemo'),
            'installDemo method should exist'
        );
    }
}

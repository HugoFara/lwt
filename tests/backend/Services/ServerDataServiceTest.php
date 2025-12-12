<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Services\ServerDataService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/ServerDataService.php';

/**
 * Unit tests for the ServerDataService class.
 *
 * Tests server and database information retrieval through the service layer.
 */
class ServerDataServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private ServerDataService $service;

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
        // Set up SERVER superglobal for tests
        $_SERVER['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/2.4.41';
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $this->service = new ServerDataService();
    }

    // ===== getServerData() tests =====

    public function testGetServerDataReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getServerData();
        $this->assertIsArray($result);
    }

    public function testGetServerDataContainsDbName(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getServerData();

        $this->assertArrayHasKey('db_name', $result);
        $this->assertIsString($result['db_name']);
        $this->assertNotEmpty($result['db_name']);
    }

    public function testGetServerDataContainsDbPrefix(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getServerData();

        $this->assertArrayHasKey('db_prefix', $result);
        $this->assertIsString($result['db_prefix']);
    }

    public function testGetServerDataContainsDbSize(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getServerData();

        $this->assertArrayHasKey('db_size', $result);
        $this->assertIsFloat($result['db_size']);
        $this->assertGreaterThanOrEqual(0, $result['db_size']);
    }

    public function testGetServerDataContainsServerSoft(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getServerData();

        $this->assertArrayHasKey('server_soft', $result);
        $this->assertIsString($result['server_soft']);
    }

    public function testGetServerDataContainsApache(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getServerData();

        $this->assertArrayHasKey('apache', $result);
        $this->assertIsString($result['apache']);
    }

    public function testGetServerDataContainsPhpVersion(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getServerData();

        $this->assertArrayHasKey('php', $result);
        $this->assertNotEmpty($result['php']);
    }

    public function testGetServerDataContainsMysqlVersion(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getServerData();

        $this->assertArrayHasKey('mysql', $result);
        $this->assertIsString($result['mysql']);
        $this->assertNotEmpty($result['mysql']);
    }

    public function testGetServerDataContainsLwtVersion(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getServerData();

        $this->assertArrayHasKey('lwt_version', $result);
        $this->assertIsString($result['lwt_version']);
    }

    public function testGetServerDataContainsServerLocation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getServerData();

        $this->assertArrayHasKey('server_location', $result);
        $this->assertIsString($result['server_location']);
    }

    public function testGetServerDataPhpVersionMatchesRuntime(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getServerData();

        $this->assertEquals(phpversion(), $result['php']);
    }

    public function testGetServerDataApacheVersionParsedCorrectly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getServerData();

        // Apache version should start with 'Apache/' if server is Apache
        if (str_starts_with($result['server_soft'], 'Apache/')) {
            $this->assertStringStartsWith('Apache/', $result['apache']);
        } else {
            $this->assertEquals('Apache/?', $result['apache']);
        }
    }
}

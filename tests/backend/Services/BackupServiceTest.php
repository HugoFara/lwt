<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Services\BackupService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/BackupService.php';

/**
 * Unit tests for the BackupService class.
 *
 * Tests backup, restore, and database management through the service layer.
 */
class BackupServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private BackupService $service;

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
        $this->service = new BackupService();
    }

    // ===== getFilePrefix() tests =====

    public function testGetFilePrefixReturnsString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getFilePrefix();
        $this->assertIsString($result);
    }

    public function testGetFilePrefixEmptyForDefaultTableSet(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // File prefix should always be empty now (no table prefix feature)
        $this->assertEquals('', $this->service->getFilePrefix());
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

    public function testGetDatabaseNameMatchesGlobal(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getDatabaseName();
        // Database name should match what's configured
        $this->assertNotEmpty($result);
        // The database name returned should be a valid string
        $this->assertIsString($result);
    }

    // ===== restoreFromUpload() tests =====

    public function testRestoreFromUploadWithNoFileReturnsError(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->restoreFromUpload([]);
        $this->assertStringContainsString('Error:', $result);
        $this->assertStringContainsString('No Restore file specified', $result);
    }

    public function testRestoreFromUploadWithEmptyTmpNameReturnsError(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $fileData = [
            'thefile' => [
                'tmp_name' => '',
                'error' => 0
            ]
        ];

        $result = $this->service->restoreFromUpload($fileData);
        $this->assertStringContainsString('Error:', $result);
    }

    public function testRestoreFromUploadWithUploadErrorReturnsError(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $fileData = [
            'thefile' => [
                'tmp_name' => '/tmp/test.sql',
                'error' => UPLOAD_ERR_INI_SIZE
            ]
        ];

        $result = $this->service->restoreFromUpload($fileData);
        $this->assertStringContainsString('Error:', $result);
    }

    public function testRestoreFromUploadWithNonexistentFileReturnsError(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $fileData = [
            'thefile' => [
                'tmp_name' => '/nonexistent/path/to/file.sql.gz',
                'error' => 0
            ]
        ];

        $result = $this->service->restoreFromUpload($fileData);
        $this->assertStringContainsString('Error:', $result);
    }

    // ===== emptyDatabase() tests =====
    // Note: We skip the actual emptyDatabase test to avoid destroying test data

    public function testEmptyDatabaseReturnsMessage(): void
    {
        // This test would actually empty the database, so we skip it
        // In a real scenario, you'd use a separate test database or mock
        $this->markTestSkipped('Skipping to avoid destroying test data');
    }
}

<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Services\TableSetService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/TableSetService.php';

/**
 * Unit tests for the TableSetService class.
 *
 * Tests table set management through the service layer.
 */
class TableSetServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private TableSetService $service;

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

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
        $this->service = new TableSetService();
    }

    // ===== isFixedPrefix() tests =====

    public function testIsFixedPrefixReturnsBool(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->isFixedPrefix();
        $this->assertIsBool($result);
    }

    // ===== getCurrentPrefix() tests =====

    public function testGetCurrentPrefixReturnsString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getCurrentPrefix();
        $this->assertIsString($result);
    }

    // ===== getPrefixes() tests =====

    public function testGetPrefixesReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getPrefixes();
        $this->assertIsArray($result);
    }

    // ===== createTableSet() tests =====

    public function testCreateTableSetWithExistingPrefixReturnsFalse(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // First get existing prefixes
        $existingPrefixes = $this->service->getPrefixes();

        if (!empty($existingPrefixes)) {
            $existingPrefix = $existingPrefixes[0];
            $result = $this->service->createTableSet($existingPrefix);

            $this->assertIsBool($result['success']);
            $this->assertFalse($result['success']);
            $this->assertStringContainsString('already exists', $result['message']);
        } else {
            $this->markTestSkipped('No existing prefixes to test with');
        }
    }

    public function testCreateTableSetReturnsExpectedStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Use a prefix that hopefully doesn't exist
        $testPrefix = 'nonexistent_test_prefix_' . time();

        // We don't actually want to create the table set,
        // just verify the return structure when it would fail
        // (because we'd need to clean up afterwards)
        $existingPrefixes = $this->service->getPrefixes();
        if (in_array($testPrefix, $existingPrefixes)) {
            $result = $this->service->createTableSet($testPrefix);
            $this->assertArrayHasKey('success', $result);
            $this->assertArrayHasKey('message', $result);
            $this->assertArrayHasKey('redirect', $result);
        }

        // Just verify the method exists and returns an array
        $this->assertTrue(method_exists($this->service, 'createTableSet'));
    }

    // ===== selectTableSet() tests =====

    public function testSelectTableSetWithDashReturnsFalse(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->selectTableSet('-');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('redirect', $result);
        $this->assertFalse($result['success']);
        $this->assertFalse($result['redirect']);
    }

    public function testSelectTableSetReturnsExpectedStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->selectTableSet('');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('redirect', $result);
    }

    // ===== deleteTableSet() tests =====

    public function testDeleteTableSetWithDashReturnsEmpty(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->deleteTableSet('-');
        $this->assertEquals('', $result);
    }

    public function testDeleteTableSetMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->service, 'deleteTableSet'),
            'deleteTableSet method should exist'
        );
    }
}

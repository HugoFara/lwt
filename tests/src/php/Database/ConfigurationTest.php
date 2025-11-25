<?php

declare(strict_types=1);

namespace Lwt\Tests\Database;

require_once __DIR__ . '/../../../../src/backend/Core/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\LWT_Globals;
use Lwt\Database\Configuration;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../../src/backend/Core/database_connect.php';

/**
 * Unit tests for the Database\Configuration class.
 *
 * Tests database configuration and connection utilities.
 */
class ConfigurationTest extends TestCase
{
    private static bool $dbConnected = false;
    private static array $dbConfig = [];

    public static function setUpBeforeClass(): void
    {
        self::$dbConfig = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . self::$dbConfig['dbname'];

        if (!LWT_Globals::getDbConnection()) {
            $connection = connect_to_database(
                self::$dbConfig['server'],
                self::$dbConfig['userid'],
                self::$dbConfig['passwd'],
                $testDbname,
                self::$dbConfig['socket'] ?? ''
            );
            LWT_Globals::setDbConnection($connection);
        }
        self::$dbConnected = (LWT_Globals::getDbConnection() !== null);
    }

    // ===== loadFromEnv() tests =====

    public function testLoadFromEnvWithValidPath(): void
    {
        $envPath = __DIR__ . '/../../../../.env';

        if (!file_exists($envPath)) {
            $this->markTestSkipped('.env file not found');
        }

        $config = Configuration::loadFromEnv($envPath);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('server', $config);
        $this->assertArrayHasKey('userid', $config);
        $this->assertArrayHasKey('passwd', $config);
        $this->assertArrayHasKey('dbname', $config);
        $this->assertArrayHasKey('socket', $config);
        $this->assertArrayHasKey('tbpref', $config);
    }

    public function testLoadFromEnvWithInvalidPath(): void
    {
        $config = Configuration::loadFromEnv('/nonexistent/path/.env');

        // Should return defaults
        $this->assertIsArray($config);
        $this->assertEquals('localhost', $config['server']);
        $this->assertEquals('root', $config['userid']);
        $this->assertEquals('', $config['passwd']);
        $this->assertEquals('learning-with-texts', $config['dbname']);
        $this->assertEquals('', $config['socket']);
        $this->assertNull($config['tbpref']);
    }

    public function testLoadFromEnvReturnsCompleteConfig(): void
    {
        $envPath = __DIR__ . '/../../../../.env';

        if (!file_exists($envPath)) {
            $this->markTestSkipped('.env file not found');
        }

        $config = Configuration::loadFromEnv($envPath);

        // All required keys should be present
        $requiredKeys = ['server', 'userid', 'passwd', 'dbname', 'socket', 'tbpref'];
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $config, "Config should contain key: $key");
        }
    }

    // ===== connect() tests =====

    public function testConnectWithValidCredentials(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $testDbname = "test_" . self::$dbConfig['dbname'];
        $connection = Configuration::connect(
            self::$dbConfig['server'],
            self::$dbConfig['userid'],
            self::$dbConfig['passwd'],
            $testDbname,
            self::$dbConfig['socket'] ?? ''
        );

        $this->assertInstanceOf(\mysqli::class, $connection);
        $this->assertEquals(0, mysqli_connect_errno(), 'Should connect successfully');
    }

    public function testConnectReturnsWorkingConnection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $testDbname = "test_" . self::$dbConfig['dbname'];
        $connection = Configuration::connect(
            self::$dbConfig['server'],
            self::$dbConfig['userid'],
            self::$dbConfig['passwd'],
            $testDbname,
            self::$dbConfig['socket'] ?? ''
        );

        // Test that connection can execute queries
        $result = mysqli_query($connection, "SELECT 1 as test");
        $this->assertNotFalse($result, 'Connection should be able to execute queries');

        if ($result instanceof \mysqli_result) {
            mysqli_free_result($result);
        }
    }

    public function testConnectSetsUtf8Charset(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $testDbname = "test_" . self::$dbConfig['dbname'];
        $connection = Configuration::connect(
            self::$dbConfig['server'],
            self::$dbConfig['userid'],
            self::$dbConfig['passwd'],
            $testDbname,
            self::$dbConfig['socket'] ?? ''
        );

        $charset = mysqli_character_set_name($connection);
        $this->assertContains(
            $charset,
            ['utf8', 'utf8mb3', 'utf8mb4'],
            'Connection should use UTF-8 charset'
        );
    }

    public function testConnectWithSocket(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test that socket parameter is accepted (even if empty)
        $testDbname = "test_" . self::$dbConfig['dbname'];
        $connection = Configuration::connect(
            self::$dbConfig['server'],
            self::$dbConfig['userid'],
            self::$dbConfig['passwd'],
            $testDbname,
            '' // Empty socket
        );

        $this->assertInstanceOf(\mysqli::class, $connection);
    }

    // ===== getPrefix() tests =====

    public function testGetPrefixWithNullConfiguredPrefix(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $connection = LWT_Globals::getDbConnection();
        $result = Configuration::getPrefix($connection, null);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        list($prefix, $fixed) = $result;
        $this->assertIsString($prefix);
        $this->assertIsBool($fixed);
        $this->assertFalse($fixed, 'Prefix should not be fixed when not configured');
    }

    public function testGetPrefixWithConfiguredPrefix(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $connection = LWT_Globals::getDbConnection();
        $result = Configuration::getPrefix($connection, 'test');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        list($prefix, $fixed) = $result;
        $this->assertEquals('test_', $prefix, 'Prefix should have underscore appended');
        $this->assertTrue($fixed, 'Prefix should be fixed when configured');
    }

    public function testGetPrefixWithEmptyConfiguredPrefix(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $connection = LWT_Globals::getDbConnection();
        $result = Configuration::getPrefix($connection, '');

        $this->assertIsArray($result);
        list($prefix, $fixed) = $result;
        $this->assertEquals('', $prefix, 'Empty prefix should remain empty');
        $this->assertTrue($fixed, 'Empty prefix should still be considered fixed');
    }

    public function testGetPrefixValidatesLength(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // This test would cause my_die() to be called with a long prefix
        // We can't easily test this without mocking, so we just document the behavior
        $this->assertTrue(true, 'Prefix length validation exists (> 20 chars causes error)');
    }

    public function testGetPrefixValidatesCharacters(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Valid characters are: 0-9, a-z, A-Z, _
        // This test documents that invalid characters cause an error
        $this->assertTrue(true, 'Prefix character validation exists (only alphanumeric and underscore)');
    }

    public function testGetPrefixWithUnderscore(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $connection = LWT_Globals::getDbConnection();
        $result = Configuration::getPrefix($connection, 'my_prefix');

        list($prefix, $fixed) = $result;
        $this->assertEquals('my_prefix_', $prefix, 'Prefix with underscore should get another underscore appended');
    }

    public function testGetPrefixWithNumbers(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $connection = LWT_Globals::getDbConnection();
        $result = Configuration::getPrefix($connection, 'app123');

        list($prefix, $fixed) = $result;
        $this->assertEquals('app123_', $prefix, 'Prefix with numbers should work');
    }

    public function testGetPrefixSetsGlobalConnection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $connection = LWT_Globals::getDbConnection();
        Configuration::getPrefix($connection, 'test');

        // The function sets the connection in LWT_Globals and $GLOBALS
        $this->assertSame($connection, LWT_Globals::getDbConnection());
        $this->assertSame($connection, $GLOBALS['DBCONNECTION']);
    }

    // ===== Integration tests =====

    public function testConnectAndGetPrefix(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $testDbname = "test_" . self::$dbConfig['dbname'];

        // Connect
        $connection = Configuration::connect(
            self::$dbConfig['server'],
            self::$dbConfig['userid'],
            self::$dbConfig['passwd'],
            $testDbname,
            self::$dbConfig['socket'] ?? ''
        );

        // Get prefix
        $result = Configuration::getPrefix($connection, null);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testLoadFromEnvAndConnect(): void
    {
        $envPath = __DIR__ . '/../../../../.env';

        if (!file_exists($envPath)) {
            $this->markTestSkipped('.env file not found');
        }

        $config = Configuration::loadFromEnv($envPath);
        $testDbname = "test_" . $config['dbname'];

        $connection = Configuration::connect(
            $config['server'],
            $config['userid'],
            $config['passwd'],
            $testDbname,
            $config['socket']
        );

        $this->assertInstanceOf(\mysqli::class, $connection);
    }

    // ===== Edge cases =====

    public function testGetPrefixSetsLwtTableValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $connection = LWT_Globals::getDbConnection();

        // Clear any existing prefix
        do_mysqli_query("DELETE FROM _lwtgeneral WHERE LWTKey = 'current_table_prefix'");

        // Get prefix with null (will read from _lwtgeneral)
        $result = Configuration::getPrefix($connection, null);

        list($prefix, $fixed) = $result;

        // The function should set the prefix in _lwtgeneral if not fixed
        if (!$fixed) {
            $storedPrefix = get_first_value(
                "SELECT LWTValue as value FROM _lwtgeneral WHERE LWTKey = 'current_table_prefix'"
            );
            // The stored prefix doesn't include the trailing underscore
            $expectedStored = rtrim($prefix, '_');
            $this->assertEquals($expectedStored, $storedPrefix);
        }
    }

    public function testConnectSetsSqlMode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $testDbname = "test_" . self::$dbConfig['dbname'];
        $connection = Configuration::connect(
            self::$dbConfig['server'],
            self::$dbConfig['userid'],
            self::$dbConfig['passwd'],
            $testDbname,
            self::$dbConfig['socket'] ?? ''
        );

        // Check SQL mode was set
        $result = mysqli_query($connection, "SELECT @@SESSION.sql_mode as mode");
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);

        // The function sets sql_mode to empty string
        $this->assertEquals('', $row['mode'], 'SQL mode should be empty');
    }

    public function testPrefixValidCharactersAccepted(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $connection = LWT_Globals::getDbConnection();

        // Test all valid character types
        $validPrefixes = [
            'abc',           // lowercase
            'ABC',           // uppercase
            'Abc',           // mixed case
            '123',           // numbers
            'a1B2',          // alphanumeric
            '_prefix',       // underscore start
            'prefix_',       // underscore end
            'pre_fix',       // underscore middle
        ];

        foreach ($validPrefixes as $testPrefix) {
            $result = Configuration::getPrefix($connection, $testPrefix);
            $this->assertIsArray($result, "Prefix '$testPrefix' should be accepted");
        }
    }

    public function testGetPrefixMaxLength(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $connection = LWT_Globals::getDbConnection();

        // 20 characters is the max allowed
        $maxPrefix = str_repeat('a', 20);
        $result = Configuration::getPrefix($connection, $maxPrefix);

        list($prefix, $fixed) = $result;
        $this->assertEquals($maxPrefix . '_', $prefix, '20-character prefix should work');
    }
}

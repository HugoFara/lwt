<?php

declare(strict_types=1);

namespace Lwt\Tests\Core;

require_once __DIR__ . '/../../../../src/backend/Core/EnvLoader.php';

use Lwt\Core\EnvLoader;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the EnvLoader class.
 *
 * Tests .env file loading, parsing, and value retrieval.
 */
class EnvLoaderTest extends TestCase
{
    private string $testEnvFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary .env file for testing
        $this->testEnvFile = sys_get_temp_dir() . '/test_' . uniqid() . '.env';
        
        // Reset EnvLoader state before each test
        EnvLoader::reset();
    }

    protected function tearDown(): void
    {
        // Clean up test file
        if (file_exists($this->testEnvFile)) {
            unlink($this->testEnvFile);
        }
        
        // Reset EnvLoader state after each test
        EnvLoader::reset();
        
        parent::tearDown();
    }

    // ===== load() tests =====

    public function testLoadReturnsTrue(): void
    {
        file_put_contents($this->testEnvFile, "TEST_KEY=test_value\n");
        
        $result = EnvLoader::load($this->testEnvFile);
        
        $this->assertTrue($result);
    }

    public function testLoadReturnsFalseForNonexistentFile(): void
    {
        $result = EnvLoader::load('/path/that/does/not/exist.env');
        
        $this->assertFalse($result);
    }

    public function testLoadReturnsFalseForUnreadableFile(): void
    {
        // Skip on Windows where chmod doesn't work the same way
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('chmod test not applicable on Windows');
        }
        
        // Create file and make it unreadable
        file_put_contents($this->testEnvFile, "TEST=value\n");
        chmod($this->testEnvFile, 0000);
        
        $result = EnvLoader::load($this->testEnvFile);
        
        $this->assertFalse($result);
        
        // Restore permissions for cleanup
        chmod($this->testEnvFile, 0644);
    }

    public function testLoadParsesSimpleKeyValue(): void
    {
        file_put_contents($this->testEnvFile, "SIMPLE_KEY=simple_value\n");
        
        EnvLoader::load($this->testEnvFile);
        
        $this->assertEquals('simple_value', EnvLoader::get('SIMPLE_KEY'));
    }

    public function testLoadParsesDoubleQuotedValue(): void
    {
        file_put_contents($this->testEnvFile, 'QUOTED_KEY="value with spaces"' . "\n");
        
        EnvLoader::load($this->testEnvFile);
        
        $this->assertEquals('value with spaces', EnvLoader::get('QUOTED_KEY'));
    }

    public function testLoadParsesSingleQuotedValue(): void
    {
        file_put_contents($this->testEnvFile, "SINGLE_QUOTED='value with spaces'\n");
        
        EnvLoader::load($this->testEnvFile);
        
        $this->assertEquals('value with spaces', EnvLoader::get('SINGLE_QUOTED'));
    }

    public function testLoadSkipsComments(): void
    {
        file_put_contents($this->testEnvFile, "# This is a comment\nVALID_KEY=valid_value\n");
        
        EnvLoader::load($this->testEnvFile);
        
        $this->assertEquals('valid_value', EnvLoader::get('VALID_KEY'));
    }

    public function testLoadSkipsEmptyLines(): void
    {
        file_put_contents($this->testEnvFile, "KEY1=value1\n\nKEY2=value2\n");
        
        EnvLoader::load($this->testEnvFile);
        
        $this->assertEquals('value1', EnvLoader::get('KEY1'));
        $this->assertEquals('value2', EnvLoader::get('KEY2'));
    }

    public function testLoadSkipsLinesWithoutEquals(): void
    {
        file_put_contents($this->testEnvFile, "INVALID_LINE\nVALID_KEY=value\n");
        
        EnvLoader::load($this->testEnvFile);
        
        $this->assertEquals('value', EnvLoader::get('VALID_KEY'));
        $this->assertNull(EnvLoader::get('INVALID_LINE'));
    }

    public function testLoadTrimsWhitespace(): void
    {
        file_put_contents($this->testEnvFile, "  TRIMMED_KEY  =  trimmed_value  \n");
        
        EnvLoader::load($this->testEnvFile);
        
        $this->assertEquals('trimmed_value', EnvLoader::get('TRIMMED_KEY'));
    }

    public function testLoadHandlesEmptyValue(): void
    {
        file_put_contents($this->testEnvFile, "EMPTY_KEY=\n");
        
        EnvLoader::load($this->testEnvFile);
        
        $this->assertEquals('', EnvLoader::get('EMPTY_KEY'));
    }

    // ===== get() tests =====

    public function testGetReturnsLoadedValue(): void
    {
        file_put_contents($this->testEnvFile, "GET_KEY=get_value\n");
        EnvLoader::load($this->testEnvFile);
        
        $value = EnvLoader::get('GET_KEY');
        
        $this->assertEquals('get_value', $value);
    }

    public function testGetReturnsDefaultWhenNotFound(): void
    {
        $value = EnvLoader::get('NONEXISTENT_KEY', 'default_value');
        
        $this->assertEquals('default_value', $value);
    }

    public function testGetReturnsNullWhenNotFoundAndNoDefault(): void
    {
        $value = EnvLoader::get('NONEXISTENT_KEY');
        
        $this->assertNull($value);
    }

    public function testGetChecksEnvSuperglobal(): void
    {
        $_ENV['ENV_SUPER_KEY'] = 'env_super_value';
        
        $value = EnvLoader::get('ENV_SUPER_KEY');
        
        $this->assertEquals('env_super_value', $value);
        
        unset($_ENV['ENV_SUPER_KEY']);
    }

    public function testGetChecksGetenv(): void
    {
        putenv('GETENV_KEY=getenv_value');
        
        $value = EnvLoader::get('GETENV_KEY');
        
        $this->assertEquals('getenv_value', $value);
        
        putenv('GETENV_KEY');
    }

    // ===== getBool() tests =====

    public function testGetBoolReturnsTrue(): void
    {
        file_put_contents($this->testEnvFile, "BOOL_TRUE=true\n");
        EnvLoader::load($this->testEnvFile);
        
        $this->assertTrue(EnvLoader::getBool('BOOL_TRUE'));
    }

    public function testGetBoolRecognizesTrueValues(): void
    {
        file_put_contents(
            $this->testEnvFile, 
            "BOOL_TRUE=true\nBOOL_ONE=1\nBOOL_YES=yes\nBOOL_ON=on\n"
        );
        EnvLoader::load($this->testEnvFile);
        
        $this->assertTrue(EnvLoader::getBool('BOOL_TRUE'));
        $this->assertTrue(EnvLoader::getBool('BOOL_ONE'));
        $this->assertTrue(EnvLoader::getBool('BOOL_YES'));
        $this->assertTrue(EnvLoader::getBool('BOOL_ON'));
    }

    public function testGetBoolRecognizesFalseValues(): void
    {
        file_put_contents(
            $this->testEnvFile, 
            "BOOL_FALSE=false\nBOOL_ZERO=0\nBOOL_NO=no\nBOOL_OFF=off\nBOOL_EMPTY=\n"
        );
        EnvLoader::load($this->testEnvFile);
        
        $this->assertFalse(EnvLoader::getBool('BOOL_FALSE'));
        $this->assertFalse(EnvLoader::getBool('BOOL_ZERO'));
        $this->assertFalse(EnvLoader::getBool('BOOL_NO'));
        $this->assertFalse(EnvLoader::getBool('BOOL_OFF'));
        $this->assertFalse(EnvLoader::getBool('BOOL_EMPTY'));
    }

    public function testGetBoolReturnsDefault(): void
    {
        $this->assertFalse(EnvLoader::getBool('NONEXISTENT_BOOL', false));
        $this->assertTrue(EnvLoader::getBool('NONEXISTENT_BOOL', true));
    }

    public function testGetBoolReturnsDefaultForInvalidValue(): void
    {
        file_put_contents($this->testEnvFile, "BOOL_INVALID=maybe\n");
        EnvLoader::load($this->testEnvFile);
        
        $this->assertTrue(EnvLoader::getBool('BOOL_INVALID', true));
        $this->assertFalse(EnvLoader::getBool('BOOL_INVALID', false));
    }

    // ===== getInt() tests =====

    public function testGetIntReturnsInteger(): void
    {
        file_put_contents($this->testEnvFile, "INT_KEY=42\n");
        EnvLoader::load($this->testEnvFile);
        
        $this->assertEquals(42, EnvLoader::getInt('INT_KEY'));
    }

    public function testGetIntReturnsDefaultWhenNotFound(): void
    {
        $this->assertEquals(99, EnvLoader::getInt('NONEXISTENT_INT', 99));
    }

    public function testGetIntReturnsDefaultForNonNumeric(): void
    {
        file_put_contents($this->testEnvFile, "INT_INVALID=not_a_number\n");
        EnvLoader::load($this->testEnvFile);
        
        $this->assertEquals(0, EnvLoader::getInt('INT_INVALID'));
        $this->assertEquals(50, EnvLoader::getInt('INT_INVALID', 50));
    }

    public function testGetIntHandlesNegativeNumbers(): void
    {
        file_put_contents($this->testEnvFile, "INT_NEGATIVE=-123\n");
        EnvLoader::load($this->testEnvFile);
        
        $this->assertEquals(-123, EnvLoader::getInt('INT_NEGATIVE'));
    }

    // ===== isLoaded() tests =====

    public function testIsLoadedReturnsFalseInitially(): void
    {
        EnvLoader::reset();
        
        $this->assertFalse(EnvLoader::isLoaded());
    }

    public function testIsLoadedReturnsTrueAfterLoad(): void
    {
        file_put_contents($this->testEnvFile, "KEY=value\n");
        EnvLoader::load($this->testEnvFile);
        
        $this->assertTrue(EnvLoader::isLoaded());
    }

    // ===== has() tests =====

    public function testHasReturnsTrueForLoadedKey(): void
    {
        file_put_contents($this->testEnvFile, "HAS_KEY=value\n");
        EnvLoader::load($this->testEnvFile);
        
        $this->assertTrue(EnvLoader::has('HAS_KEY'));
    }

    public function testHasReturnsFalseForNonexistentKey(): void
    {
        $this->assertFalse(EnvLoader::has('NONEXISTENT_KEY'));
    }

    public function testHasReturnsTrueForEnvSuperglobal(): void
    {
        $_ENV['ENV_HAS_KEY'] = 'value';
        
        $this->assertTrue(EnvLoader::has('ENV_HAS_KEY'));
        
        unset($_ENV['ENV_HAS_KEY']);
    }

    // ===== all() tests =====

    public function testAllReturnsEmptyArrayInitially(): void
    {
        EnvLoader::reset();
        
        $this->assertEquals([], EnvLoader::all());
    }

    public function testAllReturnsLoadedVariables(): void
    {
        file_put_contents($this->testEnvFile, "KEY1=value1\nKEY2=value2\n");
        EnvLoader::load($this->testEnvFile);
        
        $all = EnvLoader::all();
        
        $this->assertArrayHasKey('KEY1', $all);
        $this->assertArrayHasKey('KEY2', $all);
        $this->assertEquals('value1', $all['KEY1']);
        $this->assertEquals('value2', $all['KEY2']);
    }

    // ===== reset() tests =====

    public function testResetClearsLoadedVariables(): void
    {
        file_put_contents($this->testEnvFile, "RESET_KEY=value\n");
        EnvLoader::load($this->testEnvFile);
        
        EnvLoader::reset();
        
        $this->assertFalse(EnvLoader::isLoaded());
        $this->assertEquals([], EnvLoader::all());
    }

    // ===== getDatabaseConfig() tests =====

    public function testGetDatabaseConfigReturnsDefaults(): void
    {
        EnvLoader::reset();
        
        $config = EnvLoader::getDatabaseConfig();
        
        $this->assertEquals('localhost', $config['server']);
        $this->assertEquals('root', $config['userid']);
        $this->assertEquals('', $config['passwd']);
        $this->assertEquals('learning-with-texts', $config['dbname']);
        $this->assertEquals('', $config['socket']);
        $this->assertNull($config['tbpref']);
    }

    public function testGetDatabaseConfigReturnsLoadedValues(): void
    {
        file_put_contents(
            $this->testEnvFile, 
            "DB_HOST=testhost\nDB_USER=testuser\nDB_PASSWORD=testpass\n" .
            "DB_NAME=testdb\nDB_SOCKET=/tmp/mysql.sock\nDB_TABLE_PREFIX=test_\n"
        );
        EnvLoader::load($this->testEnvFile);
        
        $config = EnvLoader::getDatabaseConfig();
        
        $this->assertEquals('testhost', $config['server']);
        $this->assertEquals('testuser', $config['userid']);
        $this->assertEquals('testpass', $config['passwd']);
        $this->assertEquals('testdb', $config['dbname']);
        $this->assertEquals('/tmp/mysql.sock', $config['socket']);
        $this->assertEquals('test_', $config['tbpref']);
    }
}

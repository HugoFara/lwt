<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Services/DatabaseWizardService.php';

use Lwt\Services\DatabaseConnection;
use Lwt\Services\DatabaseWizardService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the DatabaseWizardService and DatabaseConnection classes.
 *
 * Tests database connection wizard functionality.
 * These tests don't require a database connection since they test
 * file operations and connection configuration.
 */
class DatabaseWizardServiceTest extends TestCase
{
    private DatabaseWizardService $service;
    private string $testEnvPath;

    protected function setUp(): void
    {
        $this->service = new DatabaseWizardService();

        // Create a temporary test .env file path
        $this->testEnvPath = sys_get_temp_dir() . '/lwt_test_' . uniqid() . '.env';
    }

    protected function tearDown(): void
    {
        // Clean up test .env file if created
        if (file_exists($this->testEnvPath)) {
            unlink($this->testEnvPath);
        }
    }

    // ===== DatabaseConnection tests =====

    public function testDatabaseConnectionConstructorSetsDefaults(): void
    {
        $conn = new DatabaseConnection();

        $this->assertEquals('', $conn->server);
        $this->assertEquals('', $conn->userid);
        $this->assertEquals('', $conn->passwd);
        $this->assertEquals('', $conn->dbname);
        $this->assertEquals('', $conn->socket);
    }

    public function testDatabaseConnectionConstructorAcceptsParameters(): void
    {
        $conn = new DatabaseConnection(
            'localhost',
            'root',
            'password123',
            'testdb',
            '/var/run/mysql.sock'
        );

        $this->assertEquals('localhost', $conn->server);
        $this->assertEquals('root', $conn->userid);
        $this->assertEquals('password123', $conn->passwd);
        $this->assertEquals('testdb', $conn->dbname);
        $this->assertEquals('/var/run/mysql.sock', $conn->socket);
    }

    public function testDatabaseConnectionGetAsTextReturnsEnvFormat(): void
    {
        $conn = new DatabaseConnection(
            'localhost',
            'root',
            'secret',
            'mydb',
            ''
        );

        $text = $conn->getAsText();

        $this->assertStringContainsString('DB_HOST=localhost', $text);
        $this->assertStringContainsString('DB_USER=root', $text);
        $this->assertStringContainsString('DB_PASSWORD=secret', $text);
        $this->assertStringContainsString('DB_NAME=mydb', $text);
        $this->assertStringNotContainsString('DB_SOCKET=', $text);
    }

    public function testDatabaseConnectionGetAsTextIncludesSocketWhenSet(): void
    {
        $conn = new DatabaseConnection(
            'localhost',
            'root',
            'secret',
            'mydb',
            '/var/run/mysql.sock'
        );

        $text = $conn->getAsText();

        $this->assertStringContainsString('DB_SOCKET=/var/run/mysql.sock', $text);
    }

    public function testDatabaseConnectionLoadFileWithNonexistentFile(): void
    {
        $conn = new DatabaseConnection();
        $conn->loadFile('/nonexistent/path/to/.env');

        // Should not change default values
        $this->assertEquals('', $conn->server);
        $this->assertEquals('', $conn->userid);
    }

    public function testDatabaseConnectionLoadFileLoadsValues(): void
    {
        // Create a test .env file
        $envContent = "DB_HOST=testhost\nDB_USER=testuser\nDB_PASSWORD=testpass\nDB_NAME=testname\n";
        file_put_contents($this->testEnvPath, $envContent);

        $conn = new DatabaseConnection();
        $conn->loadFile($this->testEnvPath);

        $this->assertEquals('testhost', $conn->server);
        $this->assertEquals('testuser', $conn->userid);
        $this->assertEquals('testpass', $conn->passwd);
        $this->assertEquals('testname', $conn->dbname);
    }

    public function testDatabaseConnectionLoadFileIgnoresComments(): void
    {
        $envContent = "# This is a comment\nDB_HOST=localhost\n# Another comment\nDB_USER=root\n";
        file_put_contents($this->testEnvPath, $envContent);

        $conn = new DatabaseConnection();
        $conn->loadFile($this->testEnvPath);

        $this->assertEquals('localhost', $conn->server);
        $this->assertEquals('root', $conn->userid);
    }

    public function testDatabaseConnectionLoadFileHandlesMissingValues(): void
    {
        $envContent = "DB_HOST=localhost\n";
        file_put_contents($this->testEnvPath, $envContent);

        $conn = new DatabaseConnection();
        $conn->loadFile($this->testEnvPath);

        $this->assertEquals('localhost', $conn->server);
        $this->assertEquals('', $conn->userid);  // Not in file, should remain empty
    }

    // ===== DatabaseWizardService tests =====

    public function testEnvFileExistsReturnsBool(): void
    {
        $result = $this->service->envFileExists();
        $this->assertIsBool($result);
    }

    public function testGetEnvPathReturnsString(): void
    {
        $result = $this->service->getEnvPath();
        $this->assertIsString($result);
        $this->assertStringContainsString('.env', $result);
    }

    public function testCreateEmptyConnectionReturnsConnection(): void
    {
        $conn = $this->service->createEmptyConnection();

        $this->assertInstanceOf(DatabaseConnection::class, $conn);
        $this->assertEquals('', $conn->server);
        $this->assertEquals('', $conn->userid);
    }

    public function testCreateConnectionFromFormReturnsConnection(): void
    {
        $formData = [
            'server' => 'localhost',
            'userid' => 'testuser',
            'passwd' => 'testpass',
            'dbname' => 'testdb',
            'socket' => '/tmp/mysql.sock'
        ];

        $conn = $this->service->createConnectionFromForm($formData);

        $this->assertInstanceOf(DatabaseConnection::class, $conn);
        $this->assertEquals('localhost', $conn->server);
        $this->assertEquals('testuser', $conn->userid);
        $this->assertEquals('testpass', $conn->passwd);
        $this->assertEquals('testdb', $conn->dbname);
        $this->assertEquals('/tmp/mysql.sock', $conn->socket);
    }

    public function testCreateConnectionFromFormHandlesMissingKeys(): void
    {
        $formData = [
            'server' => 'localhost'
        ];

        $conn = $this->service->createConnectionFromForm($formData);

        $this->assertEquals('localhost', $conn->server);
        $this->assertEquals('', $conn->userid);
        $this->assertEquals('', $conn->passwd);
        $this->assertEquals('', $conn->dbname);
        $this->assertEquals('', $conn->socket);
    }

    public function testAutocompleteConnectionReturnsConnection(): void
    {
        // Set up SERVER superglobal
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_NAME'] = 'testserver';

        $conn = $this->service->autocompleteConnection();

        $this->assertInstanceOf(DatabaseConnection::class, $conn);
        $this->assertNotEmpty($conn->server);
    }

    public function testTestConnectionWithInvalidHostReturnsError(): void
    {
        $conn = new DatabaseConnection(
            'invalid.host.that.does.not.exist',
            'user',
            'pass',
            'db'
        );

        $result = $this->service->testConnection($conn);

        // Should return an error message (either can't connect or exception)
        $this->assertIsString($result);
        $this->assertNotEquals('Connection established with success!', $result);
    }

    public function testLoadConnectionReturnsConnection(): void
    {
        // The actual .env might exist
        $conn = $this->service->loadConnection();
        $this->assertInstanceOf(DatabaseConnection::class, $conn);
    }

    // ===== Integration tests =====

    public function testRoundTripCreateAndLoad(): void
    {
        // Create a connection
        $original = new DatabaseConnection(
            'testhost',
            'testuser',
            'testpass',
            'testdb',
            '/tmp/test.sock'
        );

        // Save to test file
        $content = $original->getAsText();
        file_put_contents($this->testEnvPath, $content);

        // Load from test file
        $loaded = new DatabaseConnection();
        $loaded->loadFile($this->testEnvPath);

        // Verify all values match
        $this->assertEquals($original->server, $loaded->server);
        $this->assertEquals($original->userid, $loaded->userid);
        $this->assertEquals($original->passwd, $loaded->passwd);
        $this->assertEquals($original->dbname, $loaded->dbname);
        $this->assertEquals($original->socket, $loaded->socket);
    }
}

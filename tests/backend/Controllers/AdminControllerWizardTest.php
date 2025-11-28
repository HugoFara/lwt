<?php

declare(strict_types=1);

namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Services/DatabaseWizardService.php';

use Lwt\Services\DatabaseConnection;
use Lwt\Services\DatabaseWizardService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the AdminController wizard method and DatabaseWizardService.
 *
 * Tests the database connection wizard functionality which can run
 * without an existing database connection.
 */
class AdminControllerWizardTest extends TestCase
{
    private DatabaseWizardService $wizardService;
    private array $originalRequest;
    private array $originalServer;
    private string $testEnvPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Save original superglobals
        $this->originalRequest = $_REQUEST;
        $this->originalServer = $_SERVER;

        // Reset superglobals
        $_REQUEST = [];
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'localhost'
        ];

        $this->wizardService = new DatabaseWizardService();

        // Create a temporary .env path for testing
        $this->testEnvPath = sys_get_temp_dir() . '/lwt_test_' . uniqid() . '.env';
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_REQUEST = $this->originalRequest;
        $_SERVER = $this->originalServer;

        // Clean up test file if exists
        if (file_exists($this->testEnvPath)) {
            unlink($this->testEnvPath);
        }

        parent::tearDown();
    }

    // ===== DatabaseWizardService instantiation tests =====

    public function testWizardServiceCanBeInstantiated(): void
    {
        $service = new DatabaseWizardService();

        $this->assertInstanceOf(DatabaseWizardService::class, $service);
    }

    public function testWizardServiceHasEnvPath(): void
    {
        $envPath = $this->wizardService->getEnvPath();

        $this->assertIsString($envPath);
        $this->assertStringEndsWith('.env', $envPath);
    }

    // ===== DatabaseConnection tests =====

    public function testDatabaseConnectionCanBeCreated(): void
    {
        $conn = new DatabaseConnection();

        $this->assertInstanceOf(DatabaseConnection::class, $conn);
    }

    public function testDatabaseConnectionHasDefaultValues(): void
    {
        $conn = new DatabaseConnection();

        $this->assertSame('', $conn->server);
        $this->assertSame('', $conn->userid);
        $this->assertSame('', $conn->passwd);
        $this->assertSame('', $conn->dbname);
        $this->assertSame('', $conn->socket);
    }

    public function testDatabaseConnectionAcceptsParameters(): void
    {
        $conn = new DatabaseConnection(
            'localhost',
            'testuser',
            'testpass',
            'testdb',
            '/var/run/mysql.sock'
        );

        $this->assertSame('localhost', $conn->server);
        $this->assertSame('testuser', $conn->userid);
        $this->assertSame('testpass', $conn->passwd);
        $this->assertSame('testdb', $conn->dbname);
        $this->assertSame('/var/run/mysql.sock', $conn->socket);
    }

    // ===== createEmptyConnection tests =====

    public function testCreateEmptyConnectionReturnsConnection(): void
    {
        $conn = $this->wizardService->createEmptyConnection();

        $this->assertInstanceOf(DatabaseConnection::class, $conn);
    }

    public function testCreateEmptyConnectionHasEmptyValues(): void
    {
        $conn = $this->wizardService->createEmptyConnection();

        $this->assertSame('', $conn->server);
        $this->assertSame('', $conn->userid);
        $this->assertSame('', $conn->passwd);
        $this->assertSame('', $conn->dbname);
        $this->assertSame('', $conn->socket);
    }

    // ===== autocompleteConnection tests =====

    public function testAutocompleteConnectionReturnsConnection(): void
    {
        $conn = $this->wizardService->autocompleteConnection();

        $this->assertInstanceOf(DatabaseConnection::class, $conn);
    }

    public function testAutocompleteConnectionHasServerAddress(): void
    {
        $conn = $this->wizardService->autocompleteConnection();

        $this->assertNotEmpty($conn->server);
    }

    public function testAutocompleteConnectionHasEmptyCredentials(): void
    {
        $conn = $this->wizardService->autocompleteConnection();

        $this->assertSame('', $conn->userid);
        $this->assertSame('', $conn->passwd);
    }

    // ===== createConnectionFromForm tests =====

    public function testCreateConnectionFromFormReturnsConnection(): void
    {
        $formData = [
            'server' => 'localhost',
            'userid' => 'root',
            'passwd' => 'password',
            'dbname' => 'testdb',
            'socket' => ''
        ];

        $conn = $this->wizardService->createConnectionFromForm($formData);

        $this->assertInstanceOf(DatabaseConnection::class, $conn);
    }

    public function testCreateConnectionFromFormSetsValues(): void
    {
        $formData = [
            'server' => 'localhost',
            'userid' => 'root',
            'passwd' => 'secretpassword',
            'dbname' => 'lwt_test',
            'socket' => '/tmp/mysql.sock'
        ];

        $conn = $this->wizardService->createConnectionFromForm($formData);

        $this->assertSame('localhost', $conn->server);
        $this->assertSame('root', $conn->userid);
        $this->assertSame('secretpassword', $conn->passwd);
        $this->assertSame('lwt_test', $conn->dbname);
        $this->assertSame('/tmp/mysql.sock', $conn->socket);
    }

    public function testCreateConnectionFromFormHandlesMissingKeys(): void
    {
        $formData = [
            'server' => 'localhost'
            // Missing other keys
        ];

        $conn = $this->wizardService->createConnectionFromForm($formData);

        $this->assertSame('localhost', $conn->server);
        $this->assertSame('', $conn->userid);
        $this->assertSame('', $conn->passwd);
        $this->assertSame('', $conn->dbname);
        $this->assertSame('', $conn->socket);
    }

    public function testCreateConnectionFromFormHandlesEmptyArray(): void
    {
        $formData = [];

        $conn = $this->wizardService->createConnectionFromForm($formData);

        $this->assertSame('', $conn->server);
        $this->assertSame('', $conn->userid);
        $this->assertSame('', $conn->passwd);
        $this->assertSame('', $conn->dbname);
        $this->assertSame('', $conn->socket);
    }

    // ===== DatabaseConnection getAsText tests =====

    public function testGetAsTextReturnsString(): void
    {
        $conn = new DatabaseConnection('localhost', 'user', 'pass', 'db', '');

        $text = $conn->getAsText();

        $this->assertIsString($text);
    }

    public function testGetAsTextContainsHeader(): void
    {
        $conn = new DatabaseConnection('localhost', 'user', 'pass', 'db', '');

        $text = $conn->getAsText();

        $this->assertStringContainsString('# LWT Database Configuration', $text);
    }

    public function testGetAsTextContainsDbHost(): void
    {
        $conn = new DatabaseConnection('localhost', 'user', 'pass', 'db', '');

        $text = $conn->getAsText();

        $this->assertStringContainsString('DB_HOST=localhost', $text);
    }

    public function testGetAsTextContainsDbUser(): void
    {
        $conn = new DatabaseConnection('localhost', 'testuser', 'pass', 'db', '');

        $text = $conn->getAsText();

        $this->assertStringContainsString('DB_USER=testuser', $text);
    }

    public function testGetAsTextContainsDbPassword(): void
    {
        $conn = new DatabaseConnection('localhost', 'user', 'mypassword', 'db', '');

        $text = $conn->getAsText();

        $this->assertStringContainsString('DB_PASSWORD=mypassword', $text);
    }

    public function testGetAsTextContainsDbName(): void
    {
        $conn = new DatabaseConnection('localhost', 'user', 'pass', 'testdb', '');

        $text = $conn->getAsText();

        $this->assertStringContainsString('DB_NAME=testdb', $text);
    }

    public function testGetAsTextContainsSocketWhenSet(): void
    {
        $conn = new DatabaseConnection('localhost', 'user', 'pass', 'db', '/tmp/mysql.sock');

        $text = $conn->getAsText();

        $this->assertStringContainsString('DB_SOCKET=/tmp/mysql.sock', $text);
    }

    public function testGetAsTextOmitsSocketWhenEmpty(): void
    {
        $conn = new DatabaseConnection('localhost', 'user', 'pass', 'db', '');

        $text = $conn->getAsText();

        $this->assertStringNotContainsString('DB_SOCKET=', $text);
    }

    // ===== DatabaseConnection loadFile tests =====

    public function testLoadFileLoadsValidEnvFile(): void
    {
        // Create a test .env file
        $envContent = "DB_HOST=testhost\nDB_USER=testuser\nDB_PASSWORD=testpass\nDB_NAME=testdb\n";
        file_put_contents($this->testEnvPath, $envContent);

        $conn = new DatabaseConnection();
        $conn->loadFile($this->testEnvPath);

        $this->assertSame('testhost', $conn->server);
        $this->assertSame('testuser', $conn->userid);
        $this->assertSame('testpass', $conn->passwd);
        $this->assertSame('testdb', $conn->dbname);
    }

    public function testLoadFileLoadsSocket(): void
    {
        $envContent = "DB_HOST=localhost\nDB_SOCKET=/var/run/mysql.sock\n";
        file_put_contents($this->testEnvPath, $envContent);

        $conn = new DatabaseConnection();
        $conn->loadFile($this->testEnvPath);

        $this->assertSame('/var/run/mysql.sock', $conn->socket);
    }

    public function testLoadFileIgnoresComments(): void
    {
        $envContent = "# This is a comment\nDB_HOST=testhost\n# Another comment\nDB_USER=testuser\n";
        file_put_contents($this->testEnvPath, $envContent);

        $conn = new DatabaseConnection();
        $conn->loadFile($this->testEnvPath);

        $this->assertSame('testhost', $conn->server);
        $this->assertSame('testuser', $conn->userid);
    }

    public function testLoadFileIgnoresInvalidLines(): void
    {
        $envContent = "DB_HOST=testhost\ninvalid_line_without_equals\nDB_USER=testuser\n";
        file_put_contents($this->testEnvPath, $envContent);

        $conn = new DatabaseConnection();
        $conn->loadFile($this->testEnvPath);

        $this->assertSame('testhost', $conn->server);
        $this->assertSame('testuser', $conn->userid);
    }

    public function testLoadFileHandlesNonExistentFile(): void
    {
        $conn = new DatabaseConnection('initial', 'user', 'pass', 'db', '');
        $conn->loadFile('/nonexistent/path/.env');

        // Values should remain unchanged
        $this->assertSame('initial', $conn->server);
        $this->assertSame('user', $conn->userid);
    }

    public function testLoadFileHandlesEmptyFile(): void
    {
        file_put_contents($this->testEnvPath, '');

        $conn = new DatabaseConnection('initial', 'user', 'pass', 'db', '');
        $conn->loadFile($this->testEnvPath);

        // Values should remain unchanged
        $this->assertSame('initial', $conn->server);
    }

    public function testLoadFileTrimsValues(): void
    {
        $envContent = "DB_HOST=  spacedvalue  \nDB_USER=  anothervalue  \n";
        file_put_contents($this->testEnvPath, $envContent);

        $conn = new DatabaseConnection();
        $conn->loadFile($this->testEnvPath);

        $this->assertSame('spacedvalue', $conn->server);
        $this->assertSame('anothervalue', $conn->userid);
    }

    // ===== testConnection tests =====

    public function testTestConnectionReturnsString(): void
    {
        $conn = new DatabaseConnection('invalid', 'user', 'pass', 'db', '');

        $result = $this->wizardService->testConnection($conn);

        $this->assertIsString($result);
    }

    public function testTestConnectionWithInvalidHostReturnsError(): void
    {
        $conn = new DatabaseConnection('nonexistent.invalid.host', 'user', 'pass', 'db', '');

        $result = $this->wizardService->testConnection($conn);

        // Should not contain "Success" since connection should fail
        $this->assertStringNotContainsString('Success', $result);
    }

    public function testTestConnectionWithEmptyCredentialsReturnsError(): void
    {
        $conn = new DatabaseConnection('', '', '', '', '');

        $result = $this->wizardService->testConnection($conn);

        // Empty connection should fail
        $this->assertNotEmpty($result);
    }

    // ===== envFileExists tests =====

    public function testEnvFileExistsReturnsBool(): void
    {
        $result = $this->wizardService->envFileExists();

        $this->assertIsBool($result);
    }

    // ===== Request operation handling tests =====

    public function testRequestOpAutocomplete(): void
    {
        $_REQUEST['op'] = 'Autocomplete';

        $op = $_REQUEST['op'] ?? '';

        if ($op == 'Autocomplete') {
            $conn = $this->wizardService->autocompleteConnection();
        } else {
            $conn = $this->wizardService->createEmptyConnection();
        }

        $this->assertNotEmpty($conn->server);
    }

    public function testRequestOpCheck(): void
    {
        $_REQUEST = [
            'op' => 'Check',
            'server' => 'localhost',
            'userid' => 'root',
            'passwd' => 'password',
            'dbname' => 'test',
            'socket' => ''
        ];

        $op = $_REQUEST['op'] ?? '';

        if ($op == 'Check') {
            $conn = $this->wizardService->createConnectionFromForm($_REQUEST);
            $errorMessage = $this->wizardService->testConnection($conn);
        } else {
            $conn = $this->wizardService->createEmptyConnection();
            $errorMessage = null;
        }

        $this->assertSame('localhost', $conn->server);
        $this->assertIsString($errorMessage);
    }

    public function testRequestOpEmptyLoadsOrCreatesConnection(): void
    {
        $_REQUEST['op'] = '';

        $op = $_REQUEST['op'] ?? '';

        if ($op != '') {
            $conn = null; // Would handle operations
        } elseif ($this->wizardService->envFileExists()) {
            $conn = $this->wizardService->loadConnection();
        } else {
            $conn = $this->wizardService->createEmptyConnection();
        }

        $this->assertInstanceOf(DatabaseConnection::class, $conn);
    }

    // ===== Integration tests =====

    public function testFullWorkflowCreateAndSaveConnection(): void
    {
        // Create connection from form
        $formData = [
            'server' => 'testserver',
            'userid' => 'testuser',
            'passwd' => 'testpass',
            'dbname' => 'testdb',
            'socket' => '/tmp/test.sock'
        ];

        $conn = $this->wizardService->createConnectionFromForm($formData);

        // Write the connection to test file using getAsText
        $text = $conn->getAsText();
        file_put_contents($this->testEnvPath, $text);

        // Verify file was created
        $this->assertFileExists($this->testEnvPath);

        // Load and verify
        $loadedConn = new DatabaseConnection();
        $loadedConn->loadFile($this->testEnvPath);

        $this->assertSame('testserver', $loadedConn->server);
        $this->assertSame('testuser', $loadedConn->userid);
        $this->assertSame('testpass', $loadedConn->passwd);
        $this->assertSame('testdb', $loadedConn->dbname);
        $this->assertSame('/tmp/test.sock', $loadedConn->socket);
    }

    public function testConnectionRoundTrip(): void
    {
        // Create a connection
        $original = new DatabaseConnection(
            'myhost',
            'myuser',
            'mypass',
            'mydb',
            '/var/run/mysql.sock'
        );

        // Save to text
        $text = $original->getAsText();
        file_put_contents($this->testEnvPath, $text);

        // Load back
        $loaded = new DatabaseConnection();
        $loaded->loadFile($this->testEnvPath);

        // Verify all values match
        $this->assertSame($original->server, $loaded->server);
        $this->assertSame($original->userid, $loaded->userid);
        $this->assertSame($original->passwd, $loaded->passwd);
        $this->assertSame($original->dbname, $loaded->dbname);
        $this->assertSame($original->socket, $loaded->socket);
    }
}

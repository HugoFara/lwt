<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Admin\Infrastructure;

use Lwt\Modules\Admin\Application\DTO\DatabaseConnectionDTO;
use Lwt\Modules\Admin\Infrastructure\FileSystemEnvRepository;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FileSystemEnvRepository.
 *
 * Tests .env file operations including proper quoting/escaping of special characters.
 */
class FileSystemEnvRepositoryTest extends TestCase
{
    private string $testDir;
    private FileSystemEnvRepository $repository;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/lwt_env_test_' . uniqid();
        mkdir($this->testDir);
        $this->repository = new FileSystemEnvRepository($this->testDir);
    }

    protected function tearDown(): void
    {
        $envPath = $this->testDir . '/.env';
        if (file_exists($envPath)) {
            unlink($envPath);
        }
        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }
    }

    // ===== Basic functionality tests =====

    public function testExistsReturnsFalseWhenFileNotPresent(): void
    {
        $this->assertFalse($this->repository->exists());
    }

    public function testExistsReturnsTrueAfterSave(): void
    {
        $dto = new DatabaseConnectionDTO('localhost', 'user', 'pass', 'db');
        $this->repository->save($dto);
        $this->assertTrue($this->repository->exists());
    }

    public function testGetPathReturnsCorrectPath(): void
    {
        $this->assertEquals($this->testDir . '/.env', $this->repository->getPath());
    }

    public function testLoadReturnsEmptyDtoWhenFileNotPresent(): void
    {
        $dto = $this->repository->load();
        $this->assertTrue($dto->isEmpty());
    }

    // ===== Round-trip tests with special characters =====

    public function testRoundTripWithSimpleValues(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'root',
            passwd: 'password123',
            dbname: 'mydb',
            socket: ''
        );

        $this->repository->save($dto);
        $loaded = $this->repository->load();

        $this->assertEquals($dto->server, $loaded->server);
        $this->assertEquals($dto->userid, $loaded->userid);
        $this->assertEquals($dto->passwd, $loaded->passwd);
        $this->assertEquals($dto->dbname, $loaded->dbname);
    }

    public function testRoundTripWithEqualsSign(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user=admin',
            passwd: 'pass=word',
            dbname: 'db'
        );

        $this->repository->save($dto);
        $loaded = $this->repository->load();

        $this->assertEquals('user=admin', $loaded->userid);
        $this->assertEquals('pass=word', $loaded->passwd);
    }

    public function testRoundTripWithHashSign(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user',
            passwd: 'pass#word#123',
            dbname: 'db'
        );

        $this->repository->save($dto);
        $loaded = $this->repository->load();

        $this->assertEquals('pass#word#123', $loaded->passwd);
    }

    public function testRoundTripWithDoubleQuotes(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user',
            passwd: 'pass"word',
            dbname: 'db'
        );

        $this->repository->save($dto);
        $loaded = $this->repository->load();

        $this->assertEquals('pass"word', $loaded->passwd);
    }

    public function testRoundTripWithSingleQuotes(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user',
            passwd: "pass'word",
            dbname: 'db'
        );

        $this->repository->save($dto);
        $loaded = $this->repository->load();

        $this->assertEquals("pass'word", $loaded->passwd);
    }

    public function testRoundTripWithDollarSign(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user',
            passwd: 'pass$word$123',
            dbname: 'db'
        );

        $this->repository->save($dto);
        $loaded = $this->repository->load();

        $this->assertEquals('pass$word$123', $loaded->passwd);
    }

    public function testRoundTripWithBackslash(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user',
            passwd: 'pass\\word',
            dbname: 'db'
        );

        $this->repository->save($dto);
        $loaded = $this->repository->load();

        $this->assertEquals('pass\\word', $loaded->passwd);
    }

    public function testRoundTripWithSpaces(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user',
            passwd: 'pass word with spaces',
            dbname: 'db'
        );

        $this->repository->save($dto);
        $loaded = $this->repository->load();

        $this->assertEquals('pass word with spaces', $loaded->passwd);
    }

    public function testRoundTripWithAllSpecialCharacters(): void
    {
        $complexPassword = 'p@ss"w0rd$pecial#test with spaces\\end=value';

        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user=admin',
            passwd: $complexPassword,
            dbname: 'my-database'
        );

        $this->repository->save($dto);
        $loaded = $this->repository->load();

        $this->assertEquals('localhost', $loaded->server);
        $this->assertEquals('user=admin', $loaded->userid);
        $this->assertEquals($complexPassword, $loaded->passwd);
        $this->assertEquals('my-database', $loaded->dbname);
    }

    public function testRoundTripWithSocket(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user',
            passwd: 'pass',
            dbname: 'db',
            socket: '/var/run/mysqld/mysqld.sock'
        );

        $this->repository->save($dto);
        $loaded = $this->repository->load();

        $this->assertEquals('/var/run/mysqld/mysqld.sock', $loaded->socket);
    }

    public function testRoundTripWithEmptyPassword(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'root',
            passwd: '',
            dbname: 'db'
        );

        $this->repository->save($dto);
        $loaded = $this->repository->load();

        $this->assertEquals('', $loaded->passwd);
    }

    // ===== Backwards compatibility: loading unquoted values =====

    public function testLoadUnquotedValues(): void
    {
        $content = "DB_HOST=localhost\n";
        $content .= "DB_USER=root\n";
        $content .= "DB_PASSWORD=simplepass\n";
        $content .= "DB_NAME=mydb\n";

        file_put_contents($this->testDir . '/.env', $content);

        $loaded = $this->repository->load();

        $this->assertEquals('localhost', $loaded->server);
        $this->assertEquals('root', $loaded->userid);
        $this->assertEquals('simplepass', $loaded->passwd);
        $this->assertEquals('mydb', $loaded->dbname);
    }

    public function testLoadSingleQuotedValues(): void
    {
        $content = "DB_HOST='localhost'\n";
        $content .= "DB_USER='root'\n";
        $content .= "DB_PASSWORD='pass with spaces'\n";
        $content .= "DB_NAME='mydb'\n";

        file_put_contents($this->testDir . '/.env', $content);

        $loaded = $this->repository->load();

        $this->assertEquals('localhost', $loaded->server);
        $this->assertEquals('root', $loaded->userid);
        $this->assertEquals('pass with spaces', $loaded->passwd);
        $this->assertEquals('mydb', $loaded->dbname);
    }

    public function testLoadDoubleQuotedValuesWithEscapes(): void
    {
        $content = "DB_HOST=\"localhost\"\n";
        $content .= "DB_USER=\"root\"\n";
        $content .= "DB_PASSWORD=\"pass\\\"word\\\$test\\\\\"\n";
        $content .= "DB_NAME=\"mydb\"\n";

        file_put_contents($this->testDir . '/.env', $content);

        $loaded = $this->repository->load();

        $this->assertEquals('localhost', $loaded->server);
        $this->assertEquals('root', $loaded->userid);
        $this->assertEquals('pass"word$test\\', $loaded->passwd);
        $this->assertEquals('mydb', $loaded->dbname);
    }

    // ===== File content verification =====

    public function testSaveCreatesProperlyQuotedFile(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user',
            passwd: 'pass"word',
            dbname: 'db'
        );

        $this->repository->save($dto);
        $content = file_get_contents($this->testDir . '/.env');

        $this->assertStringContainsString('DB_PASSWORD="pass\\"word"', $content);
    }

    public function testSaveEscapesDollarSign(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user',
            passwd: '$ecret',
            dbname: 'db'
        );

        $this->repository->save($dto);
        $content = file_get_contents($this->testDir . '/.env');

        $this->assertStringContainsString('DB_PASSWORD="\\$ecret"', $content);
    }

    public function testSaveEscapesBackslash(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user',
            passwd: 'path\\to\\file',
            dbname: 'db'
        );

        $this->repository->save($dto);
        $content = file_get_contents($this->testDir . '/.env');

        $this->assertStringContainsString('DB_PASSWORD="path\\\\to\\\\file"', $content);
    }

    // ===== Edge cases =====

    public function testLoadSkipsCommentLines(): void
    {
        $content = "# This is a comment\n";
        $content .= "DB_HOST=localhost\n";
        $content .= "# Another comment\n";
        $content .= "DB_USER=root\n";
        $content .= "DB_PASSWORD=pass\n";
        $content .= "DB_NAME=db\n";

        file_put_contents($this->testDir . '/.env', $content);

        $loaded = $this->repository->load();

        $this->assertEquals('localhost', $loaded->server);
        $this->assertEquals('root', $loaded->userid);
    }

    public function testLoadSkipsLinesWithoutEquals(): void
    {
        $content = "DB_HOST=localhost\n";
        $content .= "INVALID_LINE\n";
        $content .= "DB_USER=root\n";
        $content .= "DB_PASSWORD=pass\n";
        $content .= "DB_NAME=db\n";

        file_put_contents($this->testDir . '/.env', $content);

        $loaded = $this->repository->load();

        $this->assertEquals('localhost', $loaded->server);
        $this->assertEquals('root', $loaded->userid);
    }

    public function testSaveReturnsTrueOnSuccess(): void
    {
        $dto = new DatabaseConnectionDTO('localhost', 'user', 'pass', 'db');
        $result = $this->repository->save($dto);
        $this->assertTrue($result);
    }

    public function testSaveOmitsEmptySocket(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user',
            passwd: 'pass',
            dbname: 'db',
            socket: ''
        );

        $this->repository->save($dto);
        $content = file_get_contents($this->testDir . '/.env');

        $this->assertStringNotContainsString('DB_SOCKET', $content);
    }

    public function testSaveIncludesSocketWhenProvided(): void
    {
        $dto = new DatabaseConnectionDTO(
            server: 'localhost',
            userid: 'user',
            passwd: 'pass',
            dbname: 'db',
            socket: '/tmp/mysql.sock'
        );

        $this->repository->save($dto);
        $content = file_get_contents($this->testDir . '/.env');

        $this->assertStringContainsString('DB_SOCKET="/tmp/mysql.sock"', $content);
    }
}

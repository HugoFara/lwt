<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Database;

use Lwt\Shared\Infrastructure\Database\SqlValidator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SqlValidator class.
 *
 * Tests SQL validation for backup restore security.
 */
class SqlValidatorTest extends TestCase
{
    private SqlValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SqlValidator();
    }

    // ===== Valid DROP TABLE statements =====

    public function testValidDropTable(): void
    {
        $sql = "DROP TABLE IF EXISTS languages";
        $this->assertTrue($this->validator->validate($sql));
    }

    public function testValidDropTableWithBackticks(): void
    {
        $sql = "DROP TABLE IF EXISTS `words`";
        $this->assertTrue($this->validator->validate($sql));
    }

    public function testValidDropTableWithoutIfExists(): void
    {
        $sql = "DROP TABLE texts";
        $this->assertTrue($this->validator->validate($sql));
    }

    // ===== Valid CREATE TABLE statements =====

    public function testValidCreateTable(): void
    {
        $sql = "CREATE TABLE languages ( LgID int(11) unsigned NOT NULL AUTO_INCREMENT )";
        $this->assertTrue($this->validator->validate($sql));
    }

    public function testValidCreateTableIfNotExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `words` ( WoID int(11) )";
        $this->assertTrue($this->validator->validate($sql));
    }

    // ===== Valid INSERT statements =====

    public function testValidInsert(): void
    {
        $sql = "INSERT INTO languages VALUES(1, 'English', 'dict1.php')";
        $this->assertTrue($this->validator->validate($sql));
    }

    public function testValidInsertWithBackticks(): void
    {
        $sql = "INSERT INTO `words` VALUES(1, 1, 'hello', 'hello', 1)";
        $this->assertTrue($this->validator->validate($sql));
    }

    // ===== Invalid table names =====

    public function testInvalidTableDropUsers(): void
    {
        $sql = "DROP TABLE IF EXISTS users";
        $this->assertFalse($this->validator->validate($sql));
        $this->assertStringContainsString("Table not allowed", $this->validator->getFirstError() ?? '');
    }

    public function testInvalidTableCreateAdmin(): void
    {
        $sql = "CREATE TABLE admin ( id int )";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testInvalidTableInsertMigrations(): void
    {
        $sql = "INSERT INTO _migrations VALUES(1, 'test')";
        $this->assertFalse($this->validator->validate($sql));
    }

    // ===== Disallowed statement types =====

    public function testSelectNotAllowed(): void
    {
        $sql = "SELECT * FROM languages";
        $this->assertFalse($this->validator->validate($sql));
        // SELECT is blocked as a dangerous pattern to prevent data exfiltration
        $this->assertStringContainsString("Dangerous SQL pattern", $this->validator->getFirstError() ?? '');
    }

    public function testUpdateNotAllowed(): void
    {
        $sql = "UPDATE languages SET LgName = 'test'";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testDeleteNotAllowed(): void
    {
        $sql = "DELETE FROM languages";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testTruncateNotAllowed(): void
    {
        $sql = "TRUNCATE TABLE languages";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testAlterNotAllowed(): void
    {
        $sql = "ALTER TABLE languages ADD COLUMN test VARCHAR(100)";
        $this->assertFalse($this->validator->validate($sql));
    }

    // ===== Dangerous patterns =====

    public function testLoadFileBlocked(): void
    {
        $sql = "INSERT INTO texts VALUES(1, 1, 'test', LOAD_FILE('/etc/passwd'))";
        $this->assertFalse($this->validator->validate($sql));
        $this->assertStringContainsString("Dangerous SQL pattern", $this->validator->getFirstError() ?? '');
    }

    public function testIntoOutfileBlocked(): void
    {
        $sql = "SELECT * FROM languages INTO OUTFILE '/tmp/test.txt'";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testIntoDumpfileBlocked(): void
    {
        $sql = "SELECT * FROM languages INTO DUMPFILE '/tmp/test.bin'";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testLoadDataBlocked(): void
    {
        $sql = "LOAD DATA INFILE '/tmp/test.txt' INTO TABLE languages";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testCreateUserBlocked(): void
    {
        $sql = "CREATE USER 'hacker'@'%' IDENTIFIED BY 'password'";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testDropUserBlocked(): void
    {
        $sql = "DROP USER 'admin'@'localhost'";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testGrantBlocked(): void
    {
        $sql = "GRANT ALL PRIVILEGES ON *.* TO 'hacker'@'%'";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testRevokeBlocked(): void
    {
        $sql = "REVOKE ALL PRIVILEGES ON *.* FROM 'admin'@'localhost'";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testCreateDatabaseBlocked(): void
    {
        $sql = "CREATE DATABASE evil_db";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testDropDatabaseBlocked(): void
    {
        $sql = "DROP DATABASE lwt";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testCreateProcedureBlocked(): void
    {
        $sql = "CREATE PROCEDURE evil() BEGIN SELECT 1; END";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testCreateTriggerBlocked(): void
    {
        $sql = "CREATE TRIGGER evil BEFORE INSERT ON languages FOR EACH ROW DELETE FROM words";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testCallBlocked(): void
    {
        $sql = "CALL evil_procedure()";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testSetGlobalBlocked(): void
    {
        $sql = "SET GLOBAL max_connections = 1";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testSetForeignKeyChecksDisableAllowed(): void
    {
        $sql = "SET FOREIGN_KEY_CHECKS = 0";
        $this->assertTrue($this->validator->validate($sql));
    }

    public function testSetForeignKeyChecksEnableAllowed(): void
    {
        $sql = "SET FOREIGN_KEY_CHECKS = 1";
        $this->assertTrue($this->validator->validate($sql));
    }

    public function testSetOtherVariableBlocked(): void
    {
        $sql = "SET autocommit = 0";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testSleepBlocked(): void
    {
        $sql = "INSERT INTO texts VALUES(1, 1, 'test', SLEEP(10))";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testBenchmarkBlocked(): void
    {
        $sql = "INSERT INTO texts VALUES(1, 1, BENCHMARK(1000000, MD5('x')))";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testInformationSchemaBlocked(): void
    {
        $sql = "INSERT INTO texts VALUES(1, 1, (SELECT table_name FROM INFORMATION_SCHEMA.tables LIMIT 1))";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testKillBlocked(): void
    {
        $sql = "KILL 123";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testShutdownBlocked(): void
    {
        $sql = "SHUTDOWN";
        $this->assertFalse($this->validator->validate($sql));
    }

    // ===== Comments and empty statements =====

    public function testCommentAllowed(): void
    {
        $sql = "-- This is a comment";
        $this->assertTrue($this->validator->validate($sql));
    }

    public function testEmptyStringAllowed(): void
    {
        $sql = "";
        $this->assertTrue($this->validator->validate($sql));
    }

    public function testWhitespaceOnlyAllowed(): void
    {
        $sql = "   \t\n  ";
        $this->assertTrue($this->validator->validate($sql));
    }

    // ===== validateAll tests =====

    public function testValidateAllWithValidStatements(): void
    {
        $statements = [
            "DROP TABLE IF EXISTS languages",
            "CREATE TABLE languages ( LgID int(11) )",
            "INSERT INTO languages VALUES(1, 'English')",
        ];
        $this->assertTrue($this->validator->validateAll($statements));
    }

    public function testValidateAllWithOneInvalidStatement(): void
    {
        $statements = [
            "DROP TABLE IF EXISTS languages",
            "SELECT * FROM users",  // Invalid
            "INSERT INTO languages VALUES(1, 'English')",
        ];
        $this->assertFalse($this->validator->validateAll($statements));
    }

    public function testValidateAllWithAllInvalidStatements(): void
    {
        $statements = [
            "SELECT * FROM users",
            "DELETE FROM words",
            "DROP DATABASE lwt",
        ];
        $this->assertFalse($this->validator->validateAll($statements));
    }

    // ===== All allowed tables =====

    /**
     * @dataProvider provideAllowedTables
     */
    public function testAllAllowedTables(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS $table";
        $this->assertTrue(
            $this->validator->validate($sql),
            "Table '$table' should be allowed but was rejected"
        );
    }

    public static function provideAllowedTables(): array
    {
        return [
            ['feed_links'],
            ['languages'],
            ['local_dictionaries'],
            ['local_dictionary_entries'],
            ['news_feeds'],
            ['sentences'],
            ['settings'],
            ['tags'],
            ['temp_word_occurrences'],
            ['temp_words'],
            ['text_tags'],
            ['word_occurrences'],
            ['texts'],
            ['text_tag_map'],
            ['words'],
            ['word_tag_map'],
        ];
    }

    // ===== getAllowedTables =====

    public function testGetAllowedTablesReturnsExpectedCount(): void
    {
        $tables = SqlValidator::getAllowedTables();
        // 14 tables: feed_links, languages, local_dictionaries, local_dictionary_entries,
        // news_feeds, sentences, settings, tags, temp_word_occurrences, temp_words,
        // text_tags, word_occurrences, texts, text_tag_map, words, word_tag_map
        $this->assertCount(16, $tables);
    }

    public function testGetAllowedTablesContainsLanguages(): void
    {
        $tables = SqlValidator::getAllowedTables();
        $this->assertContains('languages', $tables);
    }

    // ===== Error message tests =====

    public function testGetErrorsReturnsArray(): void
    {
        $this->validator->validate("SELECT * FROM users");
        $errors = $this->validator->getErrors();
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }

    public function testGetFirstErrorReturnsString(): void
    {
        $this->validator->validate("SELECT * FROM users");
        $error = $this->validator->getFirstError();
        $this->assertIsString($error);
    }

    public function testGetFirstErrorReturnsNullWhenNoErrors(): void
    {
        $this->validator->validate("DROP TABLE IF EXISTS languages");
        $error = $this->validator->getFirstError();
        $this->assertNull($error);
    }

    // ===== SQL injection in table names =====

    public function testSqlInjectionInTableName(): void
    {
        $sql = "DROP TABLE IF EXISTS languages; DROP DATABASE lwt; --";
        $this->assertFalse($this->validator->validate($sql));
    }

    public function testSubqueryInInsert(): void
    {
        $sql = "INSERT INTO texts VALUES(1, 1, (SELECT password FROM users LIMIT 1))";
        $this->assertFalse($this->validator->validate($sql));
    }
}

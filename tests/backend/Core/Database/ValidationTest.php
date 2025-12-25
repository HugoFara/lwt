<?php declare(strict_types=1);
namespace Lwt\Tests\Core\Database;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Validation;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the Database\Validation class.
 *
 * Tests database ID and tag validation utilities.
 */
class ValidationTest extends TestCase
{
    private static bool $dbConnected = false;
    private static ?int $testLanguageId = null;
    private static ?int $testTextId = null;
    private static ?int $testTagId = null;
    private static ?int $testTag2Id = null;

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

        if (self::$dbConnected) {
            self::createTestData();
        }
    }

    private static function createTestData(): void
    {
        // Clean up any existing test data first
        Connection::query("DELETE FROM texttags WHERE TtTxID IN (SELECT TxID FROM texts WHERE TxTitle = 'Test Validation Text')");
        Connection::query("DELETE FROM textitems2 WHERE Ti2TxID IN (SELECT TxID FROM texts WHERE TxTitle = 'Test Validation Text')");
        Connection::query("DELETE FROM sentences WHERE SeTxID IN (SELECT TxID FROM texts WHERE TxTitle = 'Test Validation Text')");
        Connection::query("DELETE FROM texts WHERE TxTitle = 'Test Validation Text'");
        Connection::query("DELETE FROM languages WHERE LgName = 'Test Validation Language'");
        Connection::query("DELETE FROM tags WHERE TgText = 'test_validation_tag'");
        Connection::query("DELETE FROM tags2 WHERE T2Text = 'test_validation_tag2'");

        // Create test language
        $sql = "INSERT INTO languages (
            LgName, LgDict1URI, LgGoogleTranslateURI, LgTextSize,
            LgCharacterSubstitutions, LgRegexpSplitSentences,
            LgExceptionsSplitSentences, LgRegexpWordCharacters,
            LgRemoveSpaces, LgSplitEachChar, LgRightToLeft
        ) VALUES (
            'Test Validation Language',
            'https://en.wiktionary.org/wiki/###',
            'https://translate.google.com/?text=###',
            100, '', '.!?', '', 'a-zA-Z', 0, 0, 0
        )";
        Connection::query($sql);
        self::$testLanguageId = mysqli_insert_id(Globals::getDbConnection());

        // Create test text
        $sql = "INSERT INTO texts (
            TxLgID, TxTitle, TxText, TxAudioURI
        ) VALUES (
            " . self::$testLanguageId . ",
            'Test Validation Text',
            'This is a test text.',
            ''
        )";
        Connection::query($sql);
        self::$testTextId = mysqli_insert_id(Globals::getDbConnection());

        // Create test tag (for words)
        $sql = "INSERT INTO tags (TgText, TgComment) VALUES ('test_validation_tag', 'Test tag')";
        Connection::query($sql);
        self::$testTagId = mysqli_insert_id(Globals::getDbConnection());

        // Create test tag2 (for texts)
        $sql = "INSERT INTO tags2 (T2Text, T2Comment) VALUES ('test_validation_tag2', 'Test tag2')";
        Connection::query($sql);
        self::$testTag2Id = mysqli_insert_id(Globals::getDbConnection());
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test data in reverse order
        if (self::$testTag2Id) {
            Connection::query("DELETE FROM tags2 WHERE T2ID = " . self::$testTag2Id);
        }
        if (self::$testTagId) {
            Connection::query("DELETE FROM tags WHERE TgID = " . self::$testTagId);
        }
        if (self::$testTextId) {
            Connection::query("DELETE FROM texts WHERE TxID = " . self::$testTextId);
        }
        if (self::$testLanguageId) {
            Connection::query("DELETE FROM languages WHERE LgID = " . self::$testLanguageId);
        }
    }

    // ===== language() tests =====

    public function testLanguageEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::language('');
        $this->assertEquals('', $result);
    }

    public function testLanguageNonNumeric(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::language('invalid');
        $this->assertEquals('', $result);
    }

    public function testLanguageNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::language('99999');
        $this->assertEquals('', $result);
    }

    public function testLanguageValidId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::language((string)self::$testLanguageId);
        $this->assertEquals((string)self::$testLanguageId, $result);
    }

    public function testLanguageSqlInjectionOr(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::language("1 OR 1=1");
        $this->assertEquals('', $result, 'SQL injection attempt should be rejected');
    }

    public function testLanguageSqlInjectionDrop(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::language("1; DROP TABLE languages; --");
        $this->assertEquals('', $result, 'SQL injection with DROP TABLE should be rejected');
    }

    public function testLanguageSqlInjectionQuotes(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::language("1' OR '1'='1");
        $this->assertEquals('', $result, 'SQL injection with quotes should be rejected');
    }

    public function testLanguageNegativeNumber(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::language('-1');
        $this->assertEquals('', $result, 'Negative ID should return empty');
    }

    public function testLanguageFloatNumber(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // is_numeric returns true for floats, but casting to int handles it
        $result = Validation::language('1.5');
        // Depends on whether language with ID 1 exists
        $this->assertIsString($result);
    }

    public function testLanguageZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::language('0');
        $this->assertEquals('', $result, 'Zero ID should return empty');
    }

    // ===== text() tests =====

    public function testTextEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::text('');
        $this->assertEquals('', $result);
    }

    public function testTextNonNumeric(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::text('invalid');
        $this->assertEquals('', $result);
    }

    public function testTextNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::text('99999');
        $this->assertEquals('', $result);
    }

    public function testTextValidId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::text((string)self::$testTextId);
        $this->assertEquals((string)self::$testTextId, $result);
    }

    public function testTextSqlInjectionOr(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::text("1 OR 1=1");
        $this->assertEquals('', $result, 'SQL injection attempt should be rejected');
    }

    public function testTextSqlInjectionDrop(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::text("1; DROP TABLE texts; --");
        $this->assertEquals('', $result, 'SQL injection with DROP TABLE should be rejected');
    }

    public function testTextSqlInjectionUnion(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::text("1' UNION SELECT * FROM users --");
        $this->assertEquals('', $result, 'SQL injection with UNION should be rejected');
    }

    public function testTextNegativeNumber(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::text('-1');
        $this->assertEquals('', $result, 'Negative ID should return empty');
    }

    public function testTextZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::text('0');
        $this->assertEquals('', $result, 'Zero ID should return empty');
    }

    // ===== tag() tests =====

    public function testTagEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::tag('', '1');
        $this->assertEquals('', $result, 'Empty tag should return empty string');
    }

    public function testTagSpecialValueMinusOne(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::tag('-1', '1');
        $this->assertEquals('-1', $result, 'Special value -1 should pass through');
    }

    public function testTagNonNumeric(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::tag('abc', '1');
        $this->assertEquals('', $result, 'Non-numeric tag should be rejected');
    }

    public function testTagSqlInjectionInTag(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::tag("1 OR 1=1", '1');
        $this->assertEquals('', $result, 'SQL injection in tag should be rejected');
    }

    public function testTagSqlInjectionDropTable(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::tag("1; DROP TABLE tags; --", '1');
        $this->assertEquals('', $result, 'SQL injection with DROP should be rejected');
    }

    public function testTagSqlInjectionQuotes(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::tag("1' OR '1'='1", '1');
        $this->assertEquals('', $result, 'SQL injection with quotes should be rejected');
    }

    public function testTagSqlInjectionInLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::tag('1', "1; DROP TABLE languages; --");
        $this->assertEquals('', $result, 'SQL injection in language ID should be rejected');
    }

    public function testTagSqlInjectionUnionInLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::tag('1', "1' UNION SELECT * FROM users --");
        $this->assertEquals('', $result, 'SQL injection with UNION should be rejected');
    }

    public function testTagNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::tag('99999', '1');
        $this->assertEquals('', $result, 'Non-existent tag should return empty');
    }

    public function testTagWithEmptyLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::tag('1', '');
        // Should handle gracefully (result depends on DB state)
        $this->assertIsString($result);
    }

    // ===== archTextTag() tests =====

    public function testArchTextTagEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::archTextTag('', '1');
        $this->assertEquals('', $result, 'Empty tag should return empty string');
    }

    public function testArchTextTagSpecialValueMinusOne(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::archTextTag('-1', '1');
        $this->assertEquals('-1', $result, 'Special value -1 should pass through');
    }

    public function testArchTextTagNonNumeric(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::archTextTag('invalid', '1');
        $this->assertEquals('', $result, 'Non-numeric tag should be rejected');
    }

    public function testArchTextTagSqlInjectionInTag(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::archTextTag("1 OR 1=1", '1');
        $this->assertEquals('', $result, 'SQL injection in tag should be rejected');
    }

    public function testArchTextTagSqlInjectionDrop(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::archTextTag("1'; DROP TABLE tags2; --", '1');
        $this->assertEquals('', $result, 'SQL injection with DROP should be rejected');
    }

    public function testArchTextTagSqlInjectionInLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::archTextTag('1', "1 OR 1=1");
        $this->assertEquals('', $result, 'SQL injection in language should be rejected');
    }

    public function testArchTextTagNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::archTextTag('99999', '1');
        $this->assertEquals('', $result, 'Non-existent tag should return empty');
    }

    public function testArchTextTagWithEmptyLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Empty language should use the query without language filter
        $result = Validation::archTextTag('99999', '');
        $this->assertEquals('', $result, 'Non-existent tag with empty language should return empty');
    }

    // ===== textTag() tests =====

    public function testTextTagEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::textTag('', '1');
        $this->assertEquals('', $result, 'Empty tag should return empty string');
    }

    public function testTextTagSpecialValueMinusOne(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::textTag('-1', '1');
        $this->assertEquals('-1', $result, 'Special value -1 should pass through');
    }

    public function testTextTagNonNumericRejected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::textTag('abc', '1');
        $this->assertEquals('', $result, 'Non-numeric tag should be rejected');
    }

    public function testTextTagSqlInjectionRejected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::textTag("1 OR 1=1", '1');
        $this->assertEquals('', $result, 'SQL injection should be rejected');
    }

    public function testTextTagNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::textTag('99999', '1');
        $this->assertEquals('', $result, 'Non-existent tag should return empty');
    }

    public function testTextTagWithEmptyLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Empty language should use the query without language filter
        $result = Validation::textTag('99999', '');
        $this->assertEquals('', $result, 'Non-existent tag with empty language should return empty');
    }

    public function testTextTagNonNumericLanguageRejected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Validation::textTag('1', 'invalid');
        $this->assertEquals('', $result, 'Non-numeric language should be rejected');
    }
}

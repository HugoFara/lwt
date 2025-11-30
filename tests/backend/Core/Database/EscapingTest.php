<?php declare(strict_types=1);
namespace Lwt\Tests\Core\Database;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Escaping;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the Database\Escaping class.
 *
 * Tests SQL escaping and text preparation utilities.
 */
class EscapingTest extends TestCase
{
    private static bool $dbConnected = false;

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

    // ===== prepareTextdata tests =====

    /**
     * @dataProvider providerPrepareTextdata
     */
    public function testPrepareTextdata(string $input, string $expected): void
    {
        $result = Escaping::prepareTextdata($input);
        $this->assertEquals($expected, $result);
    }

    public static function providerPrepareTextdata(): array
    {
        return [
            'Windows line endings converted to Unix' => ["line1\r\nline2", "line1\nline2"],
            'Multiple Windows line endings' => ["a\r\nb\r\nc", "a\nb\nc"],
            'Unix line endings unchanged' => ["line1\nline2", "line1\nline2"],
            'Empty string' => ['', ''],
            'No line endings' => ['single line', 'single line'],
            'Only carriage return (not changed)' => ["line1\rline2", "line1\rline2"],
            'Mixed endings' => ["a\r\nb\nc\rd", "a\nb\nc\rd"],
            'Multiple consecutive CRLF' => ["a\r\n\r\nb", "a\n\nb"],
            'Unicode characters preserved' => ["日本語\r\nテスト", "日本語\nテスト"],
            'Tab and space preserved' => ["hello\t\r\nworld ", "hello\t\nworld "],
        ];
    }

    // ===== prepareTextdataJs tests =====

    public function testPrepareTextdataJsBasicString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::prepareTextdataJs('test');
        $this->assertEquals("\\'test\\'", $result);
    }

    public function testPrepareTextdataJsEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::prepareTextdataJs('');
        $this->assertEquals("''", $result);
    }

    public function testPrepareTextdataJsWhitespaceOnly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::prepareTextdataJs('   ');
        $this->assertEquals("''", $result);
    }

    public function testPrepareTextdataJsSingleQuotes(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::prepareTextdataJs("test'value");
        $this->assertStringContainsString("\\'", $result);
    }

    public function testPrepareTextdataJsLineEndings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::prepareTextdataJs("line1\r\nline2");
        // Should contain escaped newline and the text
        $this->assertStringContainsString("line1", $result);
        $this->assertStringContainsString("line2", $result);
    }

    public function testPrepareTextdataJsUnicode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::prepareTextdataJs('日本語');
        $this->assertStringContainsString('日本語', $result);
        $this->assertStringStartsWith("\\'", $result);
    }

    // ===== toSqlSyntax tests =====

    public function testToSqlSyntaxBasicString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntax('test');
        $this->assertEquals("'test'", $result);
    }

    public function testToSqlSyntaxEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntax('');
        $this->assertEquals('NULL', $result);
    }

    public function testToSqlSyntaxWhitespaceOnly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntax('   ');
        $this->assertEquals('NULL', $result);
    }

    public function testToSqlSyntaxSqlInjection(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntax("test'OR'1'='1");
        $this->assertStringContainsString("\\'", $result);
        $this->assertStringStartsWith("'", $result);
        $this->assertStringEndsWith("'", $result);
    }

    public function testToSqlSyntaxLineEndings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntax("line1\r\nline2");
        $this->assertStringContainsString("line1\\nline2", $result);
    }

    public function testToSqlSyntaxBackslash(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntax('test\\value');
        $this->assertStringContainsString("\\\\", $result);
    }

    public function testToSqlSyntaxUnicode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntax('日本語');
        $this->assertStringContainsString('日本語', $result);
    }

    public function testToSqlSyntaxDropTable(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntax("test'; DROP TABLE users; --");
        $this->assertStringContainsString("\\'", $result);
    }

    public function testToSqlSyntaxTrimsWhitespace(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntax('  test  ');
        $this->assertEquals("'test'", $result);
    }

    // ===== toSqlSyntaxNoNull tests =====

    public function testToSqlSyntaxNoNullBasicString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntaxNoNull('test');
        $this->assertEquals("'test'", $result);
    }

    public function testToSqlSyntaxNoNullEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntaxNoNull('');
        $this->assertEquals("''", $result);
        $this->assertNotEquals('NULL', $result);
    }

    public function testToSqlSyntaxNoNullWhitespaceOnly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntaxNoNull('   ');
        $this->assertEquals("''", $result);
    }

    public function testToSqlSyntaxNoNullQuotes(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntaxNoNull("test'value");
        $this->assertStringContainsString("\\'", $result);
    }

    public function testToSqlSyntaxNoNullLineEndings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntaxNoNull("line1\r\nline2");
        $this->assertStringContainsString("line1\\nline2", $result);
    }

    // ===== toSqlSyntaxNoTrimNoNull tests =====

    public function testToSqlSyntaxNoTrimNoNullPreservesWhitespace(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntaxNoTrimNoNull('  test  ');
        $this->assertStringContainsString('  test  ', $result);
    }

    public function testToSqlSyntaxNoTrimNoNullEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntaxNoTrimNoNull('');
        $this->assertEquals("''", $result);
    }

    public function testToSqlSyntaxNoTrimNoNullLineEndings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntaxNoTrimNoNull("line1\r\nline2");
        $this->assertStringContainsString("line1\\nline2", $result);
    }

    public function testToSqlSyntaxNoTrimNoNullLeadingTrailingTabs(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntaxNoTrimNoNull("\ttest\t");
        $this->assertStringContainsString("\t", $result);
    }

    // ===== regexpToSqlSyntax tests =====

    public function testRegexpToSqlSyntaxBasicPattern(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::regexpToSqlSyntax('[a-z]+');
        $this->assertStringStartsWith("'", $result);
        $this->assertStringEndsWith("'", $result);
    }

    public function testRegexpToSqlSyntaxHexEscape(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::regexpToSqlSyntax('\\x{41}'); // 'A' in hex
        $this->assertStringContainsString('A', $result);
    }

    public function testRegexpToSqlSyntaxMultipleHexEscapes(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::regexpToSqlSyntax('\\x{41}\\x{42}\\x{43}');
        $this->assertStringContainsString('ABC', $result);
    }

    public function testRegexpToSqlSyntaxCharacterClass(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::regexpToSqlSyntax('[a-z]');
        $this->assertStringContainsString('[a-z]', $result);
    }

    public function testRegexpToSqlSyntaxEmptyPattern(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::regexpToSqlSyntax('');
        $this->assertEquals("''", $result);
    }

    public function testRegexpToSqlSyntaxSqlInjectionInPattern(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::regexpToSqlSyntax("test' OR '1'='1");
        $this->assertStringContainsString("\\'", $result);
    }

    public function testRegexpToSqlSyntaxComplexCharacterClass(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::regexpToSqlSyntax('[a-zA-Z0-9]');
        $this->assertStringContainsString('[a-zA-Z0-9]', $result);
    }

    public function testRegexpToSqlSyntaxSpecialRegexChars(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::regexpToSqlSyntax('\\d+');
        // Backslash before 'd' should be removed (not a special escape like \t, \n)
        $this->assertStringContainsString('d+', $result);
    }

    public function testRegexpToSqlSyntaxMixedHexAndRegular(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::regexpToSqlSyntax('test\\x{41}value');
        $this->assertStringContainsString('testAvalue', $result);
    }

    public function testRegexpToSqlSyntaxHighCodepoint(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Emoji (high codepoint)
        $result = Escaping::regexpToSqlSyntax('\\x{1F600}');
        $this->assertStringStartsWith("'", $result);
        $this->assertStringEndsWith("'", $result);
    }

    // ===== Edge cases and security tests =====

    public function testToSqlSyntaxVeryLongString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $longString = str_repeat('test ', 1000);
        $result = Escaping::toSqlSyntax($longString);
        $this->assertStringStartsWith("'", $result);
        $this->assertStringEndsWith("'", $result);
    }

    public function testToSqlSyntaxNullByte(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntax("test\0value");
        $this->assertStringStartsWith("'", $result);
    }

    public function testToSqlSyntaxOnlySpecialChars(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntax("'\"\\");
        $this->assertStringContainsString("\\'", $result);
        $this->assertStringContainsString('\\"', $result);
        $this->assertStringContainsString("\\\\", $result);
    }

    public function testToSqlSyntaxUnionSelect(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = Escaping::toSqlSyntax("1' UNION SELECT * FROM users --");
        $this->assertStringContainsString("\\'", $result);
        // Should be safe, properly escaped
    }
}

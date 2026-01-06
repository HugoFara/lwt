<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Modules\Text\Application\Services\TextDisplayService;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/Modules/Text/Application/Services/TextDisplayService.php';

/**
 * Unit tests for the TextDisplayService class.
 *
 * Tests data retrieval for text display functionality.
 */
class TextDisplayServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private static int $testLangId = 0;
    private static int $testTextId = 0;
    private static int $testWordId = 0;
    private TextDisplayService $service;

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
            // Create a test language if it doesn't exist
            $existingLang = Connection::fetchValue(
                "SELECT LgID AS value FROM languages WHERE LgName = 'TestDisplayLanguage' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                    "VALUES ('TestDisplayLanguage', 'http://test.com/###', '', 'http://translate.test/###', " .
                    "120, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 1)"
                );
                self::$testLangId = (int)Connection::fetchValue(
                    "SELECT LAST_INSERT_ID() AS value"
                );
            }

            // Create a test text with annotations
            $annotatedText = "-1\t.\n0\tHello\t\t*\n0\tworld\t\ttranslation";
            Connection::query(
                "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) " .
                "VALUES (" . self::$testLangId . ", 'Test Display Text', 'Hello world.', " .
                "'" . mysqli_real_escape_string(Globals::getDbConnection(), $annotatedText) . "', " .
                "'http://audio.test/file.mp3', 'http://source.test/article')"
            );
            self::$testTextId = (int)Connection::fetchValue(
                "SELECT LAST_INSERT_ID() AS value"
            );

            // Create a test word with romanization
            Connection::query(
                "INSERT INTO words (WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoRomanization) " .
                "VALUES (" . self::$testLangId . ", 'testword', 'testword', 1, 'test translation', 'testwɜːd')"
            );
            self::$testWordId = (int)Connection::fetchValue(
                "SELECT LAST_INSERT_ID() AS value"
            );
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test data
        if (self::$testTextId > 0) {
            Connection::query("DELETE FROM texts WHERE TxID = " . self::$testTextId);
        }
        if (self::$testWordId > 0) {
            Connection::query("DELETE FROM words WHERE WoID = " . self::$testWordId);
        }
    }

    protected function setUp(): void
    {
        $this->service = new TextDisplayService();
    }

    // ===== getHeaderData() tests =====

    public function testGetHeaderDataReturnsCorrectData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getHeaderData(self::$testTextId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('audio', $result);
        $this->assertArrayHasKey('sourceUri', $result);

        $this->assertEquals('Test Display Text', $result['title']);
        $this->assertEquals('http://audio.test/file.mp3', $result['audio']);
        $this->assertEquals('http://source.test/article', $result['sourceUri']);
    }

    public function testGetHeaderDataReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getHeaderData(999999);

        $this->assertNull($result);
    }

    public function testGetHeaderDataHandlesEmptyAudio(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a text without audio
        Connection::query(
            "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText) " .
            "VALUES (" . self::$testLangId . ", 'No Audio Text', 'Content.', '0\tContent')"
        );
        $noAudioTextId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $result = $this->service->getHeaderData($noAudioTextId);

        $this->assertIsArray($result);
        $this->assertEquals('', $result['audio']);

        // Cleanup
        Connection::query("DELETE FROM texts WHERE TxID = " . $noAudioTextId);
    }

    // ===== getTextDisplaySettings() tests =====

    public function testGetTextDisplaySettingsReturnsCorrectData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextDisplaySettings(self::$testTextId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('textSize', $result);
        $this->assertArrayHasKey('rtlScript', $result);

        $this->assertEquals(120, $result['textSize']); // We set LgTextSize = 120
        $this->assertFalse($result['rtlScript']); // We set LgRightToLeft = 0
    }

    public function testGetTextDisplaySettingsReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextDisplaySettings(999999);

        $this->assertNull($result);
    }

    // ===== getAnnotatedText() tests =====

    public function testGetAnnotatedTextReturnsContent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getAnnotatedText(self::$testTextId);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('world', $result);
    }

    public function testGetAnnotatedTextReturnsEmptyForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getAnnotatedText(999999);

        $this->assertEquals('', $result);
    }

    // ===== getAudioUri() tests =====

    public function testGetAudioUriReturnsUri(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getAudioUri(self::$testTextId);

        $this->assertEquals('http://audio.test/file.mp3', $result);
    }

    public function testGetAudioUriReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getAudioUri(999999);

        $this->assertNull($result);
    }

    // ===== getWordRomanization() tests =====

    public function testGetWordRomanizationReturnsRomanization(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getWordRomanization(self::$testWordId);

        $this->assertEquals('testwɜːd', $result);
    }

    public function testGetWordRomanizationReturnsEmptyForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getWordRomanization(999999);

        $this->assertEquals('', $result);
    }

    // ===== parseAnnotationItem() tests =====

    public function testParseAnnotationItemParsesWordWithTranslation(): void
    {
        $item = "0\tHello\t123\tBonjour";

        $result = $this->service->parseAnnotationItem($item);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['type']);
        $this->assertEquals('Hello', $result['text']);
        $this->assertEquals('Bonjour', $result['trans']);
    }

    public function testParseAnnotationItemParsesWordWithAsteriskTranslation(): void
    {
        $item = "0\tWord\t\t*";

        $result = $this->service->parseAnnotationItem($item);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['type']);
        $this->assertEquals('Word', $result['text']);
        // Asterisk is replaced with text + hair space
        $this->assertEquals("Word ", $result['trans']); // U+200A HAIR SPACE
    }

    public function testParseAnnotationItemParsesPunctuation(): void
    {
        $item = "-1\t.";

        $result = $this->service->parseAnnotationItem($item);

        $this->assertIsArray($result);
        $this->assertEquals(-1, $result['type']);
        $this->assertEquals('.', $result['text']);
        $this->assertEquals('', $result['trans']);
    }

    public function testParseAnnotationItemReturnsNullForEmpty(): void
    {
        $item = "";

        $result = $this->service->parseAnnotationItem($item);

        $this->assertNull($result);
    }

    public function testParseAnnotationItemReturnsNullForInsufficientParts(): void
    {
        $item = "0"; // Only type, no text

        $result = $this->service->parseAnnotationItem($item);

        $this->assertNull($result);
    }

    // ===== parseAnnotations() tests =====

    public function testParseAnnotationsReturnsArrayOfItems(): void
    {
        $annotatedText = "-1\t.\n0\tHello\t\t*\n0\tworld\t\ttranslation";

        $result = $this->service->parseAnnotations($annotatedText);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // First item: punctuation
        $this->assertEquals(-1, $result[0]['type']);
        $this->assertEquals('.', $result[0]['text']);

        // Second item: word with asterisk
        $this->assertEquals(0, $result[1]['type']);
        $this->assertEquals('Hello', $result[1]['text']);

        // Third item: word with translation
        $this->assertEquals(0, $result[2]['type']);
        $this->assertEquals('world', $result[2]['text']);
        $this->assertEquals('translation', $result[2]['trans']);
    }

    public function testParseAnnotationsHandlesEmptyString(): void
    {
        $result = $this->service->parseAnnotations('');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testParseAnnotationsHandlesSingleItem(): void
    {
        $annotatedText = "0\tWord\t\ttranslation";

        $result = $this->service->parseAnnotations($annotatedText);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Word', $result[0]['text']);
    }

    // ===== Integration tests =====

    public function testFullTextDisplayWorkflow(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Get header data
        $headerData = $this->service->getHeaderData(self::$testTextId);
        $this->assertNotNull($headerData);
        $this->assertEquals('Test Display Text', $headerData['title']);

        // Get display settings
        $settings = $this->service->getTextDisplaySettings(self::$testTextId);
        $this->assertNotNull($settings);
        $this->assertEquals(120, $settings['textSize']);

        // Get and parse annotations
        $annotatedText = $this->service->getAnnotatedText(self::$testTextId);
        $this->assertNotEmpty($annotatedText);

        $annotations = $this->service->parseAnnotations($annotatedText);
        $this->assertNotEmpty($annotations);

        // Verify we have parseable content
        $hasWord = false;
        foreach ($annotations as $item) {
            if ($item['type'] >= 0 && !empty($item['text'])) {
                $hasWord = true;
                break;
            }
        }
        $this->assertTrue($hasWord, 'Should have at least one word in annotations');
    }

    public function testGetHeaderDataWithRtlLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create an RTL language
        Connection::query(
            "INSERT INTO languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
            "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
            "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
            "VALUES ('TestRTLLanguage', 'http://test.com/###', '', '', " .
            "100, '', '.!?', '', 'a-zA-Z', 0, 0, 1, 0)"
        );
        $rtlLangId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        // Create a text with RTL language
        Connection::query(
            "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText) " .
            "VALUES (" . $rtlLangId . ", 'RTL Test Text', 'Content.', '0\tContent')"
        );
        $rtlTextId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        // Test that RTL is detected
        $settings = $this->service->getTextDisplaySettings($rtlTextId);

        $this->assertNotNull($settings);
        $this->assertTrue($settings['rtlScript']);

        // Cleanup
        Connection::query("DELETE FROM texts WHERE TxID = " . $rtlTextId);
        Connection::query("DELETE FROM languages WHERE LgID = " . $rtlLangId);
    }

    public function testParseAnnotationItemWithWordId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test parsing with actual word ID to get romanization
        $item = "0\ttestword\t" . self::$testWordId . "\ttranslation";

        $result = $this->service->parseAnnotationItem($item);

        $this->assertIsArray($result);
        $this->assertEquals('testword', $result['text']);
        $this->assertEquals('translation', $result['trans']);
        $this->assertEquals('testwɜːd', $result['rom']);
    }
}

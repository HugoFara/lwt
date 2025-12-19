<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\TextPrintService;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use Lwt\Database\Settings;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/TextPrintService.php';

/**
 * Unit tests for the TextPrintService class.
 *
 * Tests service methods for text printing functionality.
 */
class TextPrintServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private static int $testLangId = 0;
    private static int $testTextId = 0;

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
                "SELECT LgID AS value FROM languages WHERE LgName = 'TextPrintTestLang' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                    "VALUES ('TextPrintTestLang', 'http://test.com/###', '', 'http://translate.google.com/?sl=en&tl=fr&###', " .
                    "100, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 1)"
                );
                self::$testLangId = (int)Connection::fetchValue(
                    "SELECT LAST_INSERT_ID() AS value"
                );
            }

            // Create a test text
            $existingText = Connection::fetchValue(
                "SELECT TxID AS value FROM texts WHERE TxTitle = 'TextPrintTestText' LIMIT 1"
            );

            if ($existingText) {
                self::$testTextId = (int)$existingText;
            } else {
                Connection::query(
                    "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) " .
                    "VALUES (" . self::$testLangId . ", 'TextPrintTestText', 'This is test text.', " .
                    "'0\tThis\t\t\n1\tis\t\t\n2\ttest\t\t\n3\ttext\t\ttranslation', " .
                    "'http://audio.test/audio.mp3', 'http://source.test')"
                );
                self::$testTextId = (int)Connection::fetchValue(
                    "SELECT LAST_INSERT_ID() AS value"
                );
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test data
        Connection::query("DELETE FROM textitems2 WHERE Ti2TxID = " . self::$testTextId);
        Connection::query("DELETE FROM sentences WHERE SeTxID = " . self::$testTextId);
        Connection::query("DELETE FROM texts WHERE TxTitle = 'TextPrintTestText'");
        Connection::query("DELETE FROM languages WHERE LgName = 'TextPrintTestLang'");
    }

    // ===== Constructor tests =====

    public function testServiceCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $this->assertInstanceOf(TextPrintService::class, $service);
    }

    // ===== getTextData tests =====

    public function testGetTextDataReturnsDataForExistingText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $data = $service->getTextData(self::$testTextId);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('TxID', $data);
        $this->assertArrayHasKey('TxLgID', $data);
        $this->assertArrayHasKey('TxTitle', $data);
        $this->assertEquals('TextPrintTestText', $data['TxTitle']);
    }

    public function testGetTextDataReturnsNullForNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $data = $service->getTextData(999999);

        $this->assertNull($data);
    }

    // ===== getLanguageData tests =====

    public function testGetLanguageDataReturnsDataForExistingLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $data = $service->getLanguageData(self::$testLangId);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('LgTextSize', $data);
        $this->assertArrayHasKey('LgRightToLeft', $data);
    }

    public function testGetLanguageDataReturnsNullForNonExistentLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $data = $service->getLanguageData(999999);

        $this->assertNull($data);
    }

    // ===== getAnnotatedText tests =====

    public function testGetAnnotatedTextReturnsAnnotation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $ann = $service->getAnnotatedText(self::$testTextId);

        $this->assertIsString($ann);
        $this->assertNotEmpty($ann);
    }

    public function testGetAnnotatedTextReturnsNullForTextWithoutAnnotation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a text without annotation
        Connection::query(
            "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText) " .
            "VALUES (" . self::$testLangId . ", 'NoAnnotationTest', 'Test text.', '')"
        );
        $textId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $service = new TextPrintService();
        $ann = $service->getAnnotatedText($textId);

        $this->assertNull($ann);

        // Cleanup
        Connection::query("DELETE FROM texts WHERE TxID = {$textId}");
    }

    // ===== hasAnnotation tests =====

    public function testHasAnnotationReturnsTrueForAnnotatedText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $result = $service->hasAnnotation(self::$testTextId);

        $this->assertTrue($result);
    }

    public function testHasAnnotationReturnsFalseForNonAnnotatedText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a text without annotation
        Connection::query(
            "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText) " .
            "VALUES (" . self::$testLangId . ", 'NoAnnotationTest2', 'Test text.', '')"
        );
        $textId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $service = new TextPrintService();
        $result = $service->hasAnnotation($textId);

        $this->assertFalse($result);

        // Cleanup
        Connection::query("DELETE FROM texts WHERE TxID = {$textId}");
    }

    // ===== Settings tests =====

    public function testGetAnnotationSettingReturnsRequestValueWhenSet(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $result = $service->getAnnotationSetting('5');

        $this->assertEquals(5, $result);
    }

    public function testGetAnnotationSettingReturnsSavedValueWhenEmpty(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Save a known value first, clear, then test
        Settings::save('currentprintannotation', '');

        $service = new TextPrintService();
        $result = $service->getAnnotationSetting(null);

        // When saved setting is empty, returns default (3)
        // Verify it uses saved value when one exists
        $savedValue = Settings::get('currentprintannotation');
        $expected = $savedValue === '' ? 3 : (int)$savedValue;
        $this->assertEquals($expected, $result);
    }

    public function testGetStatusRangeSettingReturnsRequestValueWhenSet(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $result = $service->getStatusRangeSetting('10');

        $this->assertEquals(10, $result);
    }

    public function testGetStatusRangeSettingReturnsSavedValueWhenEmpty(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        Settings::save('currentprintstatus', '');

        $service = new TextPrintService();
        $result = $service->getStatusRangeSetting(null);

        // When saved setting is empty, returns default (14)
        $savedValue = Settings::get('currentprintstatus');
        $expected = $savedValue === '' ? 14 : (int)$savedValue;
        $this->assertEquals($expected, $result);
    }

    public function testGetAnnotationPlacementSettingReturnsRequestValueWhenSet(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $result = $service->getAnnotationPlacementSetting('2');

        $this->assertEquals(2, $result);
    }

    public function testGetAnnotationPlacementSettingReturnsSavedValueWhenEmpty(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        Settings::save('currentprintannotationplacement', '');

        $service = new TextPrintService();
        $result = $service->getAnnotationPlacementSetting(null);

        // When saved setting is empty, returns default (0)
        $savedValue = Settings::get('currentprintannotationplacement');
        $expected = $savedValue === '' ? 0 : (int)$savedValue;
        $this->assertEquals($expected, $result);
    }

    // ===== savePrintSettings tests =====

    public function testSavePrintSettingsSavesAllSettings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $service->savePrintSettings(self::$testTextId, 7, 15, 2);

        // Verify settings were saved
        $this->assertEquals((string)self::$testTextId, Settings::get('currenttext'));
        $this->assertEquals('7', Settings::get('currentprintannotation'));
        $this->assertEquals('15', Settings::get('currentprintstatus'));
        $this->assertEquals('2', Settings::get('currentprintannotationplacement'));
    }

    // ===== getTtsClass tests =====

    public function testGetTtsClassExtractsLanguageCode(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $result = $service->getTtsClass('http://translate.google.com/?sl=en&tl=fr&text=###');

        $this->assertEquals('tts_en ', $result);
    }

    public function testGetTtsClassReturnsNullForEmptyUri(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $result = $service->getTtsClass('');

        $this->assertNull($result);
    }

    public function testGetTtsClassReturnsNullForInvalidUri(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $result = $service->getTtsClass('http://example.com/');

        $this->assertNull($result);
    }

    // ===== parseAnnotation tests =====

    public function testParseAnnotationParsesCorrectly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $annotation = "0\tword1\t123\ttrans1\n1\tword2\t\ttrans2\n-1\tpunctuation\t\t";

        $result = $service->parseAnnotation($annotation);

        $this->assertCount(3, $result);
        $this->assertEquals(0, $result[0]['order']);
        $this->assertEquals('word1', $result[0]['text']);
        $this->assertEquals(123, $result[0]['wordId']);
        $this->assertEquals('trans1', $result[0]['translation']);

        $this->assertEquals(1, $result[1]['order']);
        $this->assertEquals('word2', $result[1]['text']);
        $this->assertNull($result[1]['wordId']);
        $this->assertEquals('trans2', $result[1]['translation']);

        $this->assertEquals(-1, $result[2]['order']);
        $this->assertEquals('punctuation', $result[2]['text']);
    }

    // ===== preparePlainPrintData tests =====

    public function testPreparePlainPrintDataReturnsCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $data = $service->preparePlainPrintData(self::$testTextId);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('textId', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('sourceUri', $data);
        $this->assertArrayHasKey('langId', $data);
        $this->assertArrayHasKey('textSize', $data);
        $this->assertArrayHasKey('rtlScript', $data);
        $this->assertArrayHasKey('hasAnnotation', $data);

        $this->assertEquals(self::$testTextId, $data['textId']);
        $this->assertEquals('TextPrintTestText', $data['title']);
    }

    public function testPreparePlainPrintDataReturnsNullForNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $data = $service->preparePlainPrintData(999999);

        $this->assertNull($data);
    }

    // ===== prepareAnnotatedPrintData tests =====

    public function testPrepareAnnotatedPrintDataReturnsCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $data = $service->prepareAnnotatedPrintData(self::$testTextId);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('textId', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('annotation', $data);
        $this->assertArrayHasKey('hasAnnotation', $data);
        $this->assertArrayHasKey('ttsClass', $data);

        $this->assertEquals(self::$testTextId, $data['textId']);
        $this->assertTrue($data['hasAnnotation']);
    }

    public function testPrepareAnnotatedPrintDataReturnsNullForNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new TextPrintService();
        $data = $service->prepareAnnotatedPrintData(999999);

        $this->assertNull($data);
    }

    // ===== Constants tests =====

    public function testAnnotationConstants(): void
    {
        $this->assertEquals(2, TextPrintService::ANN_SHOW_ROM);
        $this->assertEquals(1, TextPrintService::ANN_SHOW_TRANS);
        $this->assertEquals(4, TextPrintService::ANN_SHOW_TAGS);
    }

    public function testPlacementConstants(): void
    {
        $this->assertEquals(0, TextPrintService::ANN_PLACEMENT_BEHIND);
        $this->assertEquals(1, TextPrintService::ANN_PLACEMENT_INFRONT);
        $this->assertEquals(2, TextPrintService::ANN_PLACEMENT_RUBY);
    }
}

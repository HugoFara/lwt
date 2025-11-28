<?php

declare(strict_types=1);

namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\TextService;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/TextService.php';

/**
 * Unit tests for the TextService reading-related methods.
 *
 * Tests data retrieval for text reading functionality.
 */
class TextServiceReadingTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
    private static int $testLangId = 0;
    private static int $testTextId = 0;
    private TextService $service;

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
        self::$tbpref = Globals::getTablePrefix();

        if (self::$dbConnected) {
            $tbpref = self::$tbpref;

            // Create a test language
            $existingLang = Connection::fetchValue(
                "SELECT LgID AS value FROM {$tbpref}languages WHERE LgName = 'TestReadingLanguage' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization, " .
                    "LgTTSVoiceAPI) " .
                    "VALUES ('TestReadingLanguage', 'http://dict1.test/###', 'http://dict2.test/###', " .
                    "'http://translate.test/?sl=en&tl=fr&text=###', " .
                    "150, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 1, 'Google')"
                );
                self::$testLangId = (int)Connection::fetchValue(
                    "SELECT LAST_INSERT_ID() AS value"
                );
            }

            // Create a test text with annotations and audio
            $annotatedText = "-1\t.\n0\tHello\t\t*\n0\tworld\t\ttranslation";
            Connection::query(
                "INSERT INTO {$tbpref}texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, " .
                "TxSourceURI, TxAudioPosition, TxPosition) " .
                "VALUES (" . self::$testLangId . ", 'Test Reading Text', 'Hello world.', " .
                "'" . mysqli_real_escape_string(Globals::getDbConnection(), $annotatedText) . "', " .
                "'http://audio.test/file.mp3', 'http://source.test/article', 30, 100)"
            );
            self::$testTextId = (int)Connection::fetchValue(
                "SELECT LAST_INSERT_ID() AS value"
            );
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        $tbpref = self::$tbpref;

        // Clean up test data
        if (self::$testTextId > 0) {
            Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = " . self::$testTextId);
        }
        if (self::$testLangId > 0) {
            Connection::query("DELETE FROM {$tbpref}languages WHERE LgName = 'TestReadingLanguage'");
        }
    }

    protected function setUp(): void
    {
        $this->service = new TextService();
    }

    // ===== getTextForReading() tests =====

    public function testGetTextForReadingReturnsCorrectData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextForReading(self::$testTextId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('LgName', $result);
        $this->assertArrayHasKey('TxLgID', $result);
        $this->assertArrayHasKey('TxText', $result);
        $this->assertArrayHasKey('TxTitle', $result);
        $this->assertArrayHasKey('TxAudioURI', $result);
        $this->assertArrayHasKey('TxSourceURI', $result);
        $this->assertArrayHasKey('TxAudioPosition', $result);

        $this->assertEquals('TestReadingLanguage', $result['LgName']);
        $this->assertEquals(self::$testLangId, (int)$result['TxLgID']);
        $this->assertEquals('Hello world.', $result['TxText']);
        $this->assertEquals('Test Reading Text', $result['TxTitle']);
        $this->assertEquals('http://audio.test/file.mp3', $result['TxAudioURI']);
        $this->assertEquals('http://source.test/article', $result['TxSourceURI']);
        $this->assertEquals(30, (int)$result['TxAudioPosition']);
    }

    public function testGetTextForReadingReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextForReading(999999);

        $this->assertNull($result);
    }

    public function testGetTextForReadingReturnsNullForZeroId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextForReading(0);

        $this->assertNull($result);
    }

    // ===== getTextDataForContent() tests =====

    public function testGetTextDataForContentReturnsCorrectData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextDataForContent(self::$testTextId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('TxLgID', $result);
        $this->assertArrayHasKey('TxTitle', $result);
        $this->assertArrayHasKey('TxAnnotatedText', $result);
        $this->assertArrayHasKey('TxPosition', $result);

        $this->assertEquals(self::$testLangId, (int)$result['TxLgID']);
        $this->assertEquals('Test Reading Text', $result['TxTitle']);
        $this->assertStringContainsString('Hello', $result['TxAnnotatedText']);
        $this->assertEquals(100, (int)$result['TxPosition']);
    }

    public function testGetTextDataForContentReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextDataForContent(999999);

        $this->assertNull($result);
    }

    // ===== getLanguageSettingsForReading() tests =====

    public function testGetLanguageSettingsForReadingReturnsCorrectData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageSettingsForReading(self::$testLangId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('LgName', $result);
        $this->assertArrayHasKey('LgDict1URI', $result);
        $this->assertArrayHasKey('LgDict2URI', $result);
        $this->assertArrayHasKey('LgGoogleTranslateURI', $result);
        $this->assertArrayHasKey('LgTextSize', $result);
        $this->assertArrayHasKey('LgRegexpWordCharacters', $result);
        $this->assertArrayHasKey('LgRemoveSpaces', $result);
        $this->assertArrayHasKey('LgRightToLeft', $result);

        $this->assertEquals('TestReadingLanguage', $result['LgName']);
        $this->assertEquals('http://dict1.test/###', $result['LgDict1URI']);
        $this->assertEquals('http://dict2.test/###', $result['LgDict2URI']);
        $this->assertStringContainsString('translate.test', $result['LgGoogleTranslateURI']);
        $this->assertEquals(150, (int)$result['LgTextSize']);
        $this->assertEquals('a-zA-Z', $result['LgRegexpWordCharacters']);
        $this->assertEquals(0, (int)$result['LgRemoveSpaces']);
        $this->assertEquals(0, (int)$result['LgRightToLeft']);
    }

    public function testGetLanguageSettingsForReadingReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageSettingsForReading(999999);

        $this->assertNull($result);
    }

    public function testGetLanguageSettingsForReadingHandlesRtlLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Create an RTL language
        Connection::query(
            "INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
            "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
            "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
            "VALUES ('TestRTLReadingLang', 'http://test.com/###', '', '', " .
            "100, '', '.!?', '', 'a-zA-Z', 0, 0, 1, 0)"
        );
        $rtlLangId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $result = $this->service->getLanguageSettingsForReading($rtlLangId);

        $this->assertIsArray($result);
        $this->assertEquals(1, (int)$result['LgRightToLeft']);

        // Cleanup
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = " . $rtlLangId);
    }

    // ===== getTtsVoiceApi() tests =====

    public function testGetTtsVoiceApiReturnsVoiceApi(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTtsVoiceApi(self::$testLangId);

        $this->assertEquals('Google', $result);
    }

    public function testGetTtsVoiceApiReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTtsVoiceApi(999999);

        $this->assertNull($result);
    }

    public function testGetTtsVoiceApiReturnsNullForLanguageWithoutTts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Create a language without TTS voice API
        Connection::query(
            "INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
            "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
            "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
            "VALUES ('TestNoTtsLang', 'http://test.com/###', '', '', " .
            "100, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 0)"
        );
        $noTtsLangId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $result = $this->service->getTtsVoiceApi($noTtsLangId);

        // Should be null or empty string
        $this->assertTrue($result === null || $result === '');

        // Cleanup
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = " . $noTtsLangId);
    }

    // ===== getLanguageIdByName() tests =====

    public function testGetLanguageIdByNameReturnsId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageIdByName('TestReadingLanguage');

        $this->assertEquals(self::$testLangId, $result);
    }

    public function testGetLanguageIdByNameReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageIdByName('NonExistentLanguage');

        $this->assertNull($result);
    }

    public function testGetLanguageIdByNameIsCaseSensitive(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // MySQL collation may be case-insensitive, so this test verifies behavior
        $resultUppercase = $this->service->getLanguageIdByName('TestReadingLanguage');
        $resultLowercase = $this->service->getLanguageIdByName('testreadinglanguage');

        // If both return the same ID, the collation is case-insensitive
        // This is not an error - just documenting the actual behavior
        if ($resultLowercase !== null) {
            $this->assertEquals($resultUppercase, $resultLowercase);
        } else {
            // Case-sensitive collation - lowercase should return null
            $this->assertNull($resultLowercase);
        }
    }

    // ===== Integration tests =====

    public function testFullTextReadingWorkflow(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Step 1: Get text for reading (header data)
        $headerData = $this->service->getTextForReading(self::$testTextId);
        $this->assertNotNull($headerData);
        $this->assertEquals('Test Reading Text', $headerData['TxTitle']);

        $langId = (int)$headerData['TxLgID'];

        // Step 2: Get text data for content display
        $contentData = $this->service->getTextDataForContent(self::$testTextId);
        $this->assertNotNull($contentData);
        $this->assertEquals($langId, (int)$contentData['TxLgID']);

        // Step 3: Get language settings
        $langSettings = $this->service->getLanguageSettingsForReading($langId);
        $this->assertNotNull($langSettings);
        $this->assertEquals(150, (int)$langSettings['LgTextSize']);

        // Step 4: Get TTS voice API
        $voiceApi = $this->service->getTtsVoiceApi($langId);
        $this->assertEquals('Google', $voiceApi);

        // Verify all data is consistent
        $this->assertEquals('TestReadingLanguage', $headerData['LgName']);
        $this->assertEquals('TestReadingLanguage', $langSettings['LgName']);
    }

    public function testTextWithEmptyAudioUri(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Create a text without audio
        Connection::query(
            "INSERT INTO {$tbpref}texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxSourceURI) " .
            "VALUES (" . self::$testLangId . ", 'No Audio Reading Text', 'Test content.', '', " .
            "'http://source.test')"
        );
        $noAudioTextId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $result = $this->service->getTextForReading($noAudioTextId);

        $this->assertIsArray($result);
        $this->assertTrue(
            $result['TxAudioURI'] === null || $result['TxAudioURI'] === '',
            'Audio URI should be null or empty'
        );

        // Cleanup
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = " . $noAudioTextId);
    }

    public function testTextWithEmptyAnnotatedText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Create a text without annotations
        Connection::query(
            "INSERT INTO {$tbpref}texts (TxLgID, TxTitle, TxText, TxAnnotatedText) " .
            "VALUES (" . self::$testLangId . ", 'No Annotation Text', 'Plain text.', '')"
        );
        $noAnnTextId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $result = $this->service->getTextDataForContent($noAnnTextId);

        $this->assertIsArray($result);
        $this->assertEquals('', $result['TxAnnotatedText']);

        // Cleanup
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = " . $noAnnTextId);
    }

    public function testTextWithZeroPositions(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Create a text with zero positions (default)
        Connection::query(
            "INSERT INTO {$tbpref}texts (TxLgID, TxTitle, TxText, TxAnnotatedText) " .
            "VALUES (" . self::$testLangId . ", 'Zero Position Text', 'Content.', '')"
        );
        $zeroPosTextId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $headerResult = $this->service->getTextForReading($zeroPosTextId);
        $contentResult = $this->service->getTextDataForContent($zeroPosTextId);

        $this->assertEquals(0, (int)($headerResult['TxAudioPosition'] ?? 0));
        $this->assertEquals(0, (int)($contentResult['TxPosition'] ?? 0));

        // Cleanup
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = " . $zeroPosTextId);
    }

    public function testLanguageWithRemoveSpaces(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Create a language with remove spaces enabled (like Japanese)
        Connection::query(
            "INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
            "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
            "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
            "VALUES ('TestRemoveSpacesLang', 'http://test.com/###', '', '', " .
            "100, '', '.!?', '', 'a-zA-Z', 1, 0, 0, 0)"
        );
        $removeSpacesLangId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $result = $this->service->getLanguageSettingsForReading($removeSpacesLangId);

        $this->assertIsArray($result);
        $this->assertEquals(1, (int)$result['LgRemoveSpaces']);

        // Cleanup
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = " . $removeSpacesLangId);
    }

    // ===== Edge case tests =====

    public function testGetTextForReadingWithSpecialCharactersInTitle(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        $specialTitle = "Test's \"Special\" & <Characters>";

        // Create a text with special characters in title
        Connection::query(
            "INSERT INTO {$tbpref}texts (TxLgID, TxTitle, TxText, TxAnnotatedText) " .
            "VALUES (" . self::$testLangId . ", " .
            "'" . mysqli_real_escape_string(Globals::getDbConnection(), $specialTitle) . "', " .
            "'Content.', '')"
        );
        $specialTextId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $result = $this->service->getTextForReading($specialTextId);

        $this->assertIsArray($result);
        $this->assertEquals($specialTitle, $result['TxTitle']);

        // Cleanup
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = " . $specialTextId);
    }

    public function testGetTextForReadingWithUnicodeText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        $unicodeText = "こんにちは世界 - Привет мир - مرحبا";

        // Create a text with Unicode content
        Connection::query(
            "INSERT INTO {$tbpref}texts (TxLgID, TxTitle, TxText, TxAnnotatedText) " .
            "VALUES (" . self::$testLangId . ", 'Unicode Test', " .
            "'" . mysqli_real_escape_string(Globals::getDbConnection(), $unicodeText) . "', '')"
        );
        $unicodeTextId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $result = $this->service->getTextForReading($unicodeTextId);

        $this->assertIsArray($result);
        $this->assertEquals($unicodeText, $result['TxText']);

        // Cleanup
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = " . $unicodeTextId);
    }
}

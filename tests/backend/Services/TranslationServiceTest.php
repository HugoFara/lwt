<?php

declare(strict_types=1);

namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\TranslationService;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/TranslationService.php';

/**
 * Unit tests for the TranslationService class.
 *
 * Tests translation service methods for Google Translate,
 * Glosbe, and dictionary link operations.
 */
class TranslationServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
    private static int $testLangId = 0;
    private static int $testTextId = 0;
    private TranslationService $service;

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
            self::setupTestData();
        }
    }

    private static function setupTestData(): void
    {
        $tbpref = self::$tbpref;

        // Create a test language
        $existingLang = Connection::fetchValue(
            "SELECT LgID AS value FROM {$tbpref}languages WHERE LgName = 'TranslationServiceTestLang' LIMIT 1"
        );

        if ($existingLang) {
            self::$testLangId = (int)$existingLang;
        } else {
            Connection::query(
                "INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                "VALUES ('TranslationServiceTestLang', 'http://dict1.test/lwt_term', " .
                "'http://dict2.test/###', 'ggl.php?text=lwt_term&sl=es&tl=en', " .
                "100, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 1)"
            );
            self::$testLangId = (int)Connection::fetchValue(
                "SELECT LAST_INSERT_ID() AS value"
            );
        }

        // Create a test text
        $existingText = Connection::fetchValue(
            "SELECT TxID AS value FROM {$tbpref}texts WHERE TxTitle = 'TranslationServiceTestText' LIMIT 1"
        );

        if ($existingText) {
            self::$testTextId = (int)$existingText;
        } else {
            Connection::query(
                "INSERT INTO {$tbpref}texts (TxLgID, TxTitle, TxText, TxAudioURI) " .
                "VALUES (" . self::$testLangId . ", 'TranslationServiceTestText', " .
                "'This is a test sentence. Another test sentence.', '')"
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
        // Clean up in reverse order
        Connection::query("DELETE FROM {$tbpref}textitems2 WHERE Ti2TxID = " . self::$testTextId);
        Connection::query("DELETE FROM {$tbpref}sentences WHERE SeLgID = " . self::$testLangId);
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxTitle = 'TranslationServiceTestText'");
        Connection::query("DELETE FROM {$tbpref}words WHERE WoLgID = " . self::$testLangId);
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgName = 'TranslationServiceTestLang'");
    }

    protected function setUp(): void
    {
        $this->service = new TranslationService();
    }

    // ===== translateViaGoogle() tests =====

    public function testTranslateViaGoogleWithEmptyTextReturnsError(): void
    {
        $result = $this->service->translateViaGoogle('', 'en', 'es');

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['translations']);
        $this->assertEquals('Text is empty', $result['error']);
    }

    public function testTranslateViaGoogleWithWhitespaceOnlyTextReturnsError(): void
    {
        $result = $this->service->translateViaGoogle('   ', 'en', 'es');

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['translations']);
        $this->assertEquals('Text is empty', $result['error']);
    }

    public function testTranslateViaGoogleWithEmptySourceLangReturnsError(): void
    {
        $result = $this->service->translateViaGoogle('hello', '', 'es');

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['translations']);
        $this->assertEquals('Source and target languages are required', $result['error']);
    }

    public function testTranslateViaGoogleWithEmptyTargetLangReturnsError(): void
    {
        $result = $this->service->translateViaGoogle('hello', 'en', '');

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['translations']);
        $this->assertEquals('Source and target languages are required', $result['error']);
    }

    public function testTranslateViaGoogleWithEmptyBothLangsReturnsError(): void
    {
        $result = $this->service->translateViaGoogle('hello', '', '');

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['translations']);
        $this->assertEquals('Source and target languages are required', $result['error']);
    }

    // ===== buildGlosbeUrl() tests =====

    public function testBuildGlosbeUrlWithSimpleParams(): void
    {
        $url = $this->service->buildGlosbeUrl('hello', 'en', 'es');

        $this->assertEquals('http://glosbe.com/en/es/hello', $url);
    }

    public function testBuildGlosbeUrlWithSpecialCharacters(): void
    {
        $url = $this->service->buildGlosbeUrl('café', 'fr', 'en');

        $this->assertStringContainsString('glosbe.com', $url);
        $this->assertStringContainsString('fr', $url);
        $this->assertStringContainsString('en', $url);
        $this->assertStringContainsString(urlencode('café'), $url);
    }

    public function testBuildGlosbeUrlWithSpaces(): void
    {
        $url = $this->service->buildGlosbeUrl('good morning', 'en', 'de');

        // urlencode uses + for spaces, which is valid
        $this->assertEquals('http://glosbe.com/en/de/good+morning', $url);
    }

    public function testBuildGlosbeUrlWithEmptyPhrase(): void
    {
        $url = $this->service->buildGlosbeUrl('', 'en', 'es');

        $this->assertEquals('http://glosbe.com/en/es/', $url);
    }

    // ===== validateGlosbeParams() tests =====

    public function testValidateGlosbeParamsWithValidInputs(): void
    {
        $result = $this->service->validateGlosbeParams('en', 'es', 'hello');

        $this->assertTrue($result['valid']);
        $this->assertArrayNotHasKey('error', $result);
    }

    public function testValidateGlosbeParamsWithEmptyFrom(): void
    {
        $result = $this->service->validateGlosbeParams('', 'es', 'hello');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Language codes are required', $result['error']);
    }

    public function testValidateGlosbeParamsWithEmptyDest(): void
    {
        $result = $this->service->validateGlosbeParams('en', '', 'hello');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Language codes are required', $result['error']);
    }

    public function testValidateGlosbeParamsWithEmptyPhrase(): void
    {
        $result = $this->service->validateGlosbeParams('en', 'es', '');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Term is not set', $result['error']);
    }

    public function testValidateGlosbeParamsWithAllEmpty(): void
    {
        $result = $this->service->validateGlosbeParams('', '', '');

        $this->assertFalse($result['valid']);
        // Language codes error should take precedence
        $this->assertEquals('Language codes are required', $result['error']);
    }

    // ===== createDictLink() tests =====

    public function testCreateDictLinkWithLwtTermPlaceholder(): void
    {
        $url = $this->service->createDictLink('http://dict.test/search?term=lwt_term', 'hello');

        $this->assertEquals('http://dict.test/search?term=hello', $url);
    }

    public function testCreateDictLinkWithHashPlaceholder(): void
    {
        $url = $this->service->createDictLink('http://dict.test/search?q=###', 'world');

        $this->assertEquals('http://dict.test/search?q=world', $url);
    }

    public function testCreateDictLinkWithNoPlaceholder(): void
    {
        $url = $this->service->createDictLink('http://dict.test/search?q=', 'test');

        $this->assertEquals('http://dict.test/search?q=test', $url);
    }

    public function testCreateDictLinkWithSpecialCharacters(): void
    {
        $url = $this->service->createDictLink('http://dict.test/###', 'café');

        $this->assertStringContainsString(urlencode('café'), $url);
    }

    public function testCreateDictLinkWithEmptyTerm(): void
    {
        $url = $this->service->createDictLink('http://dict.test/###', '');

        // With empty term, should replace with + or be empty
        $this->assertStringContainsString('dict.test', $url);
    }

    // ===== buildGoogleTranslateUrl() tests =====

    public function testBuildGoogleTranslateUrlWithSimpleParams(): void
    {
        $url = $this->service->buildGoogleTranslateUrl('hello', 'en', 'es');

        $this->assertStringStartsWith('https://translate.google.com/', $url);
        $this->assertStringContainsString('sl=en', $url);
        $this->assertStringContainsString('tl=es', $url);
        $this->assertStringContainsString('text=hello', $url);
        $this->assertStringContainsString('lwt_popup=true', $url);
    }

    public function testBuildGoogleTranslateUrlWithSpecialCharacters(): void
    {
        $url = $this->service->buildGoogleTranslateUrl('¿Cómo estás?', 'es', 'en');

        $this->assertStringStartsWith('https://translate.google.com/', $url);
        $this->assertStringContainsString('sl=es', $url);
        $this->assertStringContainsString('tl=en', $url);
        // URL encoded special characters
        $this->assertStringContainsString(urlencode('¿Cómo estás?'), $url);
    }

    public function testBuildGoogleTranslateUrlWithSpaces(): void
    {
        $url = $this->service->buildGoogleTranslateUrl('good morning', 'en', 'de');

        // urlencode uses + for spaces, which is valid
        $this->assertStringContainsString('text=good+morning', $url);
    }

    // ===== getTranslatorUrl() tests =====

    public function testGetTranslatorUrlWithInvalidTextIdReturnsError(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTranslatorUrl(999999, 1);

        $this->assertNull($result['url']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testGetTranslatorUrlWithZeroTextIdReturnsError(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTranslatorUrl(0, 0);

        $this->assertNull($result['url']);
        $this->assertArrayHasKey('error', $result);
    }

    // ===== Edge cases and integration tests =====

    public function testServiceCanBeInstantiated(): void
    {
        $service = new TranslationService();

        $this->assertInstanceOf(TranslationService::class, $service);
    }

    public function testBuildGlosbeUrlIsIdempotent(): void
    {
        $url1 = $this->service->buildGlosbeUrl('test', 'en', 'es');
        $url2 = $this->service->buildGlosbeUrl('test', 'en', 'es');

        $this->assertEquals($url1, $url2);
    }

    public function testValidateGlosbeParamsWithWhitespaceOnlyFrom(): void
    {
        // Whitespace strings should be treated as empty after trim
        // Note: The service doesn't trim, so this tests current behavior
        $result = $this->service->validateGlosbeParams('  ', 'es', 'hello');

        // Whitespace is not empty, so this passes validation
        $this->assertTrue($result['valid']);
    }

    public function testCreateDictLinkWithMultiplePlaceholders(): void
    {
        // Test URL with two ### placeholders (encoding scenario)
        $url = $this->service->createDictLink('http://dict.test/###UTF-8###', 'test');

        // Should handle encoding placeholder
        $this->assertStringContainsString('dict.test', $url);
    }

    public function testBuildGoogleTranslateUrlWithEmptyText(): void
    {
        $url = $this->service->buildGoogleTranslateUrl('', 'en', 'es');

        $this->assertStringStartsWith('https://translate.google.com/', $url);
        $this->assertStringContainsString('text=', $url);
    }

    public function testBuildGoogleTranslateUrlWithUnicodeCharacters(): void
    {
        $url = $this->service->buildGoogleTranslateUrl('日本語', 'ja', 'en');

        $this->assertStringStartsWith('https://translate.google.com/', $url);
        $this->assertStringContainsString('sl=ja', $url);
        $this->assertStringContainsString('tl=en', $url);
        // Japanese characters should be URL encoded
        $this->assertStringContainsString(urlencode('日本語'), $url);
    }

    public function testValidateGlosbeParamsWithMultipleErrors(): void
    {
        // When both languages and phrase are empty, language error takes precedence
        $result = $this->service->validateGlosbeParams('', '', '');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Language codes are required', $result['error']);
    }

    public function testTranslateViaGoogleStructureContainsRequiredKeys(): void
    {
        $result = $this->service->translateViaGoogle('test', 'en', 'es');

        // Whether success or failure, should have these keys
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('translations', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsArray($result['translations']);
    }

    public function testValidateGlosbeParamsStructureOnSuccess(): void
    {
        $result = $this->service->validateGlosbeParams('en', 'es', 'hello');

        $this->assertArrayHasKey('valid', $result);
        $this->assertTrue($result['valid']);
    }

    public function testValidateGlosbeParamsStructureOnFailure(): void
    {
        $result = $this->service->validateGlosbeParams('', '', '');

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertFalse($result['valid']);
        $this->assertIsString($result['error']);
    }
}

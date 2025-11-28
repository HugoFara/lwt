<?php

declare(strict_types=1);

namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\MobileService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/MobileService.php';

/**
 * Unit tests for the MobileService class.
 *
 * Tests mobile interface data retrieval and processing.
 */
class MobileServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private MobileService $service;

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

    protected function setUp(): void
    {
        $this->service = new MobileService();
    }

    // ===== Constructor tests =====

    public function testConstructorCreatesInstance(): void
    {
        $service = new MobileService();
        $this->assertInstanceOf(MobileService::class, $service);
    }

    // ===== getLanguages() tests =====

    public function testGetLanguagesReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguages();
        $this->assertIsArray($result);
    }

    public function testGetLanguagesReturnsCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguages();
        if (!empty($result)) {
            $language = $result[0];
            $this->assertArrayHasKey('id', $language);
            $this->assertArrayHasKey('name', $language);
            $this->assertIsInt($language['id']);
            $this->assertIsString($language['name']);
        }
        $this->assertTrue(true); // Pass if no languages exist
    }

    // ===== getLanguageName() tests =====

    public function testGetLanguageNameReturnsNullForNonexistentId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageName(999999);
        $this->assertNull($result);
    }

    public function testGetLanguageNameReturnsStringForValidId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $languages = $this->service->getLanguages();
        if (!empty($languages)) {
            $langId = $languages[0]['id'];
            $result = $this->service->getLanguageName($langId);
            $this->assertIsString($result);
            $this->assertEquals($languages[0]['name'], $result);
        }
        $this->assertTrue(true); // Pass if no languages exist
    }

    // ===== getTextsByLanguage() tests =====

    public function testGetTextsByLanguageReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextsByLanguage(1);
        $this->assertIsArray($result);
    }

    public function testGetTextsByLanguageReturnsCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $languages = $this->service->getLanguages();
        if (!empty($languages)) {
            $texts = $this->service->getTextsByLanguage($languages[0]['id']);
            if (!empty($texts)) {
                $text = $texts[0];
                $this->assertArrayHasKey('id', $text);
                $this->assertArrayHasKey('title', $text);
                $this->assertIsInt($text['id']);
                $this->assertIsString($text['title']);
            }
        }
        $this->assertTrue(true); // Pass if no data exists
    }

    public function testGetTextsByLanguageReturnsEmptyForNonexistentLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextsByLanguage(999999);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ===== getTextById() tests =====

    public function testGetTextByIdReturnsNullForNonexistentId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextById(999999);
        $this->assertNull($result);
    }

    public function testGetTextByIdReturnsCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $languages = $this->service->getLanguages();
        if (!empty($languages)) {
            $texts = $this->service->getTextsByLanguage($languages[0]['id']);
            if (!empty($texts)) {
                $text = $this->service->getTextById($texts[0]['id']);
                $this->assertIsArray($text);
                $this->assertArrayHasKey('id', $text);
                $this->assertArrayHasKey('title', $text);
                $this->assertArrayHasKey('audioUri', $text);
                $this->assertIsInt($text['id']);
                $this->assertIsString($text['title']);
                $this->assertIsString($text['audioUri']);
            }
        }
        $this->assertTrue(true); // Pass if no data exists
    }

    // ===== getSentencesByText() tests =====

    public function testGetSentencesByTextReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getSentencesByText(1);
        $this->assertIsArray($result);
    }

    public function testGetSentencesByTextReturnsCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $languages = $this->service->getLanguages();
        if (!empty($languages)) {
            $texts = $this->service->getTextsByLanguage($languages[0]['id']);
            if (!empty($texts)) {
                $sentences = $this->service->getSentencesByText($texts[0]['id']);
                if (!empty($sentences)) {
                    $sentence = $sentences[0];
                    $this->assertArrayHasKey('id', $sentence);
                    $this->assertArrayHasKey('text', $sentence);
                    $this->assertIsInt($sentence['id']);
                    $this->assertIsString($sentence['text']);
                }
            }
        }
        $this->assertTrue(true); // Pass if no data exists
    }

    public function testGetSentencesByTextExcludesParagraphMarkers(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $languages = $this->service->getLanguages();
        if (!empty($languages)) {
            $texts = $this->service->getTextsByLanguage($languages[0]['id']);
            if (!empty($texts)) {
                $sentences = $this->service->getSentencesByText($texts[0]['id']);
                foreach ($sentences as $sentence) {
                    $this->assertNotEquals('¶', trim($sentence['text']));
                }
            }
        }
        $this->assertTrue(true); // Pass if no data exists
    }

    // ===== getSentenceById() tests =====

    public function testGetSentenceByIdReturnsNullForNonexistentId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getSentenceById(999999);
        $this->assertNull($result);
    }

    public function testGetSentenceByIdReturnsCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $languages = $this->service->getLanguages();
        if (!empty($languages)) {
            $texts = $this->service->getTextsByLanguage($languages[0]['id']);
            if (!empty($texts)) {
                $sentences = $this->service->getSentencesByText($texts[0]['id']);
                if (!empty($sentences)) {
                    $sentence = $this->service->getSentenceById($sentences[0]['id']);
                    $this->assertIsArray($sentence);
                    $this->assertArrayHasKey('id', $sentence);
                    $this->assertArrayHasKey('text', $sentence);
                    $this->assertArrayHasKey('textId', $sentence);
                    $this->assertIsInt($sentence['id']);
                    $this->assertIsString($sentence['text']);
                    $this->assertIsInt($sentence['textId']);
                }
            }
        }
        $this->assertTrue(true); // Pass if no data exists
    }

    // ===== getNextSentenceId() tests =====

    public function testGetNextSentenceIdReturnsNullForNonexistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getNextSentenceId(999999, 1);
        $this->assertNull($result);
    }

    public function testGetNextSentenceIdReturnsIntOrNull(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $languages = $this->service->getLanguages();
        if (!empty($languages)) {
            $texts = $this->service->getTextsByLanguage($languages[0]['id']);
            if (!empty($texts)) {
                $sentences = $this->service->getSentencesByText($texts[0]['id']);
                if (count($sentences) > 1) {
                    $nextId = $this->service->getNextSentenceId(
                        $texts[0]['id'],
                        $sentences[0]['id']
                    );
                    $this->assertIsInt($nextId);
                    $this->assertGreaterThan($sentences[0]['id'], $nextId);
                }
            }
        }
        $this->assertTrue(true); // Pass if no data exists
    }

    public function testGetNextSentenceIdReturnsNullForLastSentence(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $languages = $this->service->getLanguages();
        if (!empty($languages)) {
            $texts = $this->service->getTextsByLanguage($languages[0]['id']);
            if (!empty($texts)) {
                $sentences = $this->service->getSentencesByText($texts[0]['id']);
                if (!empty($sentences)) {
                    $lastSentence = end($sentences);
                    $nextId = $this->service->getNextSentenceId(
                        $texts[0]['id'],
                        $lastSentence['id']
                    );
                    $this->assertNull($nextId);
                }
            }
        }
        $this->assertTrue(true); // Pass if no data exists
    }

    // ===== getTermsBySentence() tests =====

    public function testGetTermsBySentenceReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTermsBySentence(1);
        $this->assertIsArray($result);
    }

    public function testGetTermsBySentenceReturnsCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $languages = $this->service->getLanguages();
        if (!empty($languages)) {
            $texts = $this->service->getTextsByLanguage($languages[0]['id']);
            if (!empty($texts)) {
                $sentences = $this->service->getSentencesByText($texts[0]['id']);
                if (!empty($sentences)) {
                    $terms = $this->service->getTermsBySentence($sentences[0]['id']);
                    if (!empty($terms)) {
                        $term = $terms[0];
                        $this->assertArrayHasKey('type', $term);
                        $this->assertArrayHasKey('text', $term);
                        $this->assertContains($term['type'], ['word', 'nonword']);
                        $this->assertIsString($term['text']);

                        if ($term['type'] === 'word') {
                            $this->assertArrayHasKey('description', $term);
                            $this->assertArrayHasKey('status', $term);
                        }
                    }
                }
            }
        }
        $this->assertTrue(true); // Pass if no data exists
    }

    public function testGetTermsBySentenceReturnsEmptyForNonexistentSentence(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTermsBySentence(999999);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ===== getVersion() tests =====

    public function testGetVersionReturnsString(): void
    {
        $result = $this->service->getVersion();
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGetVersionReturnsValidVersionFormat(): void
    {
        $result = $this->service->getVersion();
        // Version should contain dots or be a valid version string
        $this->assertMatchesRegularExpression('/^\d+\.\d+/', $result);
    }

    // ===== Integration tests =====

    public function testNavigationFlowFromLanguageToTerms(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Step 1: Get languages
        $languages = $this->service->getLanguages();
        if (empty($languages)) {
            $this->markTestSkipped('No languages in test database');
        }

        $langId = $languages[0]['id'];
        $langName = $this->service->getLanguageName($langId);
        $this->assertNotNull($langName);
        $this->assertEquals($languages[0]['name'], $langName);

        // Step 2: Get texts for language
        $texts = $this->service->getTextsByLanguage($langId);
        if (empty($texts)) {
            $this->markTestSkipped('No texts for language in test database');
        }

        $textId = $texts[0]['id'];
        $text = $this->service->getTextById($textId);
        $this->assertNotNull($text);
        $this->assertEquals($textId, $text['id']);

        // Step 3: Get sentences for text
        $sentences = $this->service->getSentencesByText($textId);
        if (empty($sentences)) {
            $this->markTestSkipped('No sentences for text in test database');
        }

        $sentId = $sentences[0]['id'];
        $sentence = $this->service->getSentenceById($sentId);
        $this->assertNotNull($sentence);
        $this->assertEquals($sentId, $sentence['id']);
        $this->assertEquals($textId, $sentence['textId']);

        // Step 4: Get terms for sentence
        $terms = $this->service->getTermsBySentence($sentId);
        $this->assertIsArray($terms);

        // Step 5: Check next sentence navigation
        if (count($sentences) > 1) {
            $nextId = $this->service->getNextSentenceId($textId, $sentId);
            $this->assertNotNull($nextId);
            $this->assertGreaterThan($sentId, $nextId);
        }
    }

    // ===== Edge case tests =====

    public function testGetLanguageNameWithZeroId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageName(0);
        $this->assertNull($result);
    }

    public function testGetTextByIdWithZeroId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextById(0);
        $this->assertNull($result);
    }

    public function testGetSentenceByIdWithZeroId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getSentenceById(0);
        $this->assertNull($result);
    }

    public function testGetTextsByLanguageWithZeroId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextsByLanguage(0);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetSentencesByTextWithZeroId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getSentencesByText(0);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetTermsBySentenceWithZeroId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTermsBySentence(0);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetNextSentenceIdWithZeroIds(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getNextSentenceId(0, 0);
        $this->assertNull($result);
    }
}

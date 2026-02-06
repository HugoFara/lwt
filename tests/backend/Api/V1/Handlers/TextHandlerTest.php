<?php

declare(strict_types=1);

namespace Lwt\Tests\Api\V1\Handlers;

require_once __DIR__ . '/../../../../../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';

use Lwt\Modules\Text\Http\TextApiHandler;
use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../../../src/backend/Api/V1/ApiV1.php';
require_once __DIR__ . '/../../../../../src/Modules/Text/Http/TextApiHandler.php';

/**
 * Unit tests for the TextHandler class.
 *
 * Tests text-related API operations.
 */
class TextHandlerTest extends TestCase
{
    private static bool $dbConnected = false;
    private TextApiHandler $handler;

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            try {
                $connection = Configuration::connect(
                    $config['server'],
                    $config['userid'],
                    $config['passwd'],
                    $testDbname,
                    $config['socket'] ?? ''
                );
                Globals::setDbConnection($connection);
                self::$dbConnected = true;
            } catch (\Exception $e) {
                self::$dbConnected = false;
            }
        } else {
            self::$dbConnected = true;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new TextApiHandler();
    }

    // ===== Class structure tests =====

    /**
     * Test that TextHandler class has the required methods.
     */
    public function testClassHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(TextApiHandler::class);

        // Business logic methods
        $this->assertTrue($reflection->hasMethod('saveTextPosition'));
        $this->assertTrue($reflection->hasMethod('saveAudioPosition'));
        $this->assertTrue($reflection->hasMethod('saveImprText'));
        $this->assertTrue($reflection->hasMethod('saveImprTextData'));

        // API formatter methods
        $this->assertTrue($reflection->hasMethod('formatSetTextPosition'));
        $this->assertTrue($reflection->hasMethod('formatSetAudioPosition'));
        $this->assertTrue($reflection->hasMethod('formatSetAnnotation'));
    }

    /**
     * Test formatSetTextPosition returns correct message format.
     */
    public function testFormatSetTextPositionReturnsCorrectFormat(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Use a non-existent text ID - method should still work (0 rows affected)
        $result = $this->handler->formatSetTextPosition(999999999, 100);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('text', $result);
        $this->assertEquals('Reading position set', $result['text']);
    }

    /**
     * Test formatSetAudioPosition returns correct message format.
     */
    public function testFormatSetAudioPositionReturnsCorrectFormat(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Use a non-existent text ID - method should still work (0 rows affected)
        $result = $this->handler->formatSetAudioPosition(999999999, 5000);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('audio', $result);
        $this->assertEquals('Audio position set', $result['audio']);
    }

    /**
     * Test all public methods are accessible.
     */
    public function testPublicMethods(): void
    {
        $reflection = new \ReflectionClass(TextApiHandler::class);
        $publicMethods = array_filter(
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
            fn($m) => !$m->isConstructor()
        );

        // Should have at least 6 public methods
        $this->assertGreaterThanOrEqual(6, count($publicMethods));
    }

    // ===== saveImprTextData tests =====

    /**
     * Test saveImprTextData returns error for non-existent text.
     */
    public function testSaveImprTextDataReturnsErrorForInvalidTextId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Non-existent text ID should return empty annotation, and line out of range
        $result = $this->handler->saveImprTextData(999999999, 0, 'test annotation');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    // ===== formatSetAnnotation tests =====

    /**
     * Test formatSetAnnotation returns error for invalid JSON.
     */
    public function testFormatSetAnnotationReturnsErrorForInvalidJson(): void
    {
        $result = $this->handler->formatSetAnnotation(1, 'tx0', 'not valid json');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Invalid JSON data', $result['error']);
    }

    /**
     * Test formatSetAnnotation with valid JSON but non-existent text.
     */
    public function testFormatSetAnnotationWithValidJson(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatSetAnnotation(999999999, 'tx0', '{"tx0": "test"}');

        $this->assertIsArray($result);
        // Should either return success or error based on text existence
        $this->assertTrue(
            array_key_exists('save_impr_text', $result) || array_key_exists('error', $result)
        );
    }

    // ===== setDisplayMode tests =====

    /**
     * Test setDisplayMode returns error for non-existent text.
     */
    public function testSetDisplayModeReturnsErrorForNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->setDisplayMode(999999999, 1, true, true);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertFalse($result['updated']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Text not found', $result['error']);
    }

    /**
     * Test formatSetDisplayMode extracts parameters correctly.
     */
    public function testFormatSetDisplayModeExtractsParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $params = [
            'annotations' => '2',
            'romanization' => 'true',
            'translation' => 'false',
        ];

        $result = $this->handler->formatSetDisplayMode(999999999, $params);

        $this->assertIsArray($result);
        // For non-existent text, should return error
        $this->assertArrayHasKey('updated', $result);
    }

    // ===== getWords tests =====

    /**
     * Test getWords returns error for non-existent text.
     */
    public function testGetWordsReturnsErrorForNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->getWords(999999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Text not found', $result['error']);
    }

    /**
     * Test formatGetWords delegates to getWords.
     */
    public function testFormatGetWordsDelegatesToGetWords(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatGetWords(999999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // ===== formatTextsByLanguage tests =====

    /**
     * Test formatTextsByLanguage returns correct structure.
     */
    public function testFormatTextsByLanguageReturnsCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatTextsByLanguage(999999, []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('texts', $result);
        $this->assertArrayHasKey('pagination', $result);
    }

    /**
     * Test formatTextsByLanguage accepts pagination params.
     */
    public function testFormatTextsByLanguageAcceptsPaginationParams(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $params = ['page' => '2', 'per_page' => '20', 'sort' => '2'];
        $result = $this->handler->formatTextsByLanguage(999999, $params);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pagination', $result);
    }

    // ===== formatArchivedTextsByLanguage tests =====

    /**
     * Test formatArchivedTextsByLanguage returns correct structure.
     */
    public function testFormatArchivedTextsByLanguageReturnsCorrectStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatArchivedTextsByLanguage(999999, []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('texts', $result);
        $this->assertArrayHasKey('pagination', $result);
    }

    // ===== getPrintItems tests =====

    /**
     * Test getPrintItems returns error for non-existent text.
     */
    public function testGetPrintItemsReturnsErrorForNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->getPrintItems(999999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Text not found', $result['error']);
    }

    /**
     * Test formatGetPrintItems delegates to getPrintItems.
     */
    public function testFormatGetPrintItemsDelegatesToGetPrintItems(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatGetPrintItems(999999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // ===== getAnnotation tests =====

    /**
     * Test getAnnotation returns error for non-existent text.
     */
    public function testGetAnnotationReturnsErrorForNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->getAnnotation(999999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Text not found', $result['error']);
    }

    /**
     * Test formatGetAnnotation delegates to getAnnotation.
     */
    public function testFormatGetAnnotationDelegatesToGetAnnotation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatGetAnnotation(999999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // ===== getTranslations tests =====

    /**
     * Test getTranslations returns empty array for non-existent word.
     */
    public function testGetTranslationsReturnsEmptyForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->getTranslations(999999999);

        $this->assertIsArray($result);
        // Non-existent word returns empty array
        $this->assertEmpty($result);
    }

    // ===== getTermTranslations tests =====

    /**
     * Test getTermTranslations returns error for non-existent text.
     */
    public function testGetTermTranslationsReturnsErrorForNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->getTermTranslations('test', 999999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Text not found', $result['error']);
    }

    /**
     * Test formatTermTranslations delegates correctly.
     */
    public function testFormatTermTranslationsDelegatesCorrectly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatTermTranslations('test', 999999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    // ===== editTermForm tests =====

    /**
     * Test editTermForm returns message for non-existent text.
     */
    public function testEditTermFormReturnsMessageForNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->editTermForm(999999999);

        $this->assertIsString($result);
        $this->assertStringContainsString('Text not found', $result);
    }

    /**
     * Test formatEditTermForm wraps result in HTML key.
     */
    public function testFormatEditTermFormWrapsResult(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatEditTermForm(999999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('html', $result);
    }

    // ===== markAllWellKnown tests =====

    /**
     * Test markAllWellKnown returns count structure.
     */
    public function testMarkAllWellKnownReturnsCountStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        try {
            $result = $this->handler->markAllWellKnown(999999999);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
        } catch (\RuntimeException $e) {
            // Non-existent text throws RuntimeException, which is valid behavior
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }

    /**
     * Test formatMarkAllWellKnown delegates correctly.
     */
    public function testFormatMarkAllWellKnownDelegates(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        try {
            $result = $this->handler->formatMarkAllWellKnown(999999999);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
        } catch (\RuntimeException $e) {
            // Non-existent text throws RuntimeException, which is valid behavior
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }

    // ===== markAllIgnored tests =====

    /**
     * Test markAllIgnored returns count structure.
     */
    public function testMarkAllIgnoredReturnsCountStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        try {
            $result = $this->handler->markAllIgnored(999999999);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
        } catch (\RuntimeException $e) {
            // Non-existent text throws RuntimeException, which is valid behavior
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }

    /**
     * Test formatMarkAllIgnored delegates correctly.
     */
    public function testFormatMarkAllIgnoredDelegates(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        try {
            $result = $this->handler->formatMarkAllIgnored(999999999);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('count', $result);
        } catch (\RuntimeException $e) {
            // Non-existent text throws RuntimeException, which is valid behavior
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }

    // ===== Text Scoring tests =====

    /**
     * Test formatGetTextScore returns score structure.
     */
    public function testFormatGetTextScoreReturnsStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatGetTextScore(999999999);

        $this->assertIsArray($result);
        // Score structure should have text_id (snake_case) even if text doesn't exist
        $this->assertArrayHasKey('text_id', $result);
    }

    /**
     * Test formatGetTextScores returns scores for multiple texts.
     */
    public function testFormatGetTextScoresReturnsMultiple(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatGetTextScores([999999998, 999999999]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('scores', $result);
    }

    /**
     * Test formatGetRecommendedTexts returns recommendations structure.
     */
    public function testFormatGetRecommendedTextsReturnsStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatGetRecommendedTexts(999999, []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('target_comprehensibility', $result);
    }

    /**
     * Test formatGetRecommendedTexts clamps target parameter.
     */
    public function testFormatGetRecommendedTextsClamsTarget(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test with target below minimum
        $result = $this->handler->formatGetRecommendedTexts(999999, ['target' => '0.1']);
        $this->assertEquals(0.5, $result['target_comprehensibility']);

        // Test with target above maximum
        $result = $this->handler->formatGetRecommendedTexts(999999, ['target' => '1.5']);
        $this->assertEquals(1.0, $result['target_comprehensibility']);

        // Test with valid target
        $result = $this->handler->formatGetRecommendedTexts(999999, ['target' => '0.9']);
        $this->assertEquals(0.9, $result['target_comprehensibility']);
    }

    /**
     * Test formatGetRecommendedTexts handles limit parameter.
     */
    public function testFormatGetRecommendedTextsHandlesLimit(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test with high limit (should clamp to 50)
        $result = $this->handler->formatGetRecommendedTexts(999999, ['limit' => '100']);
        $this->assertIsArray($result['recommendations']);
    }

    // ===== makeTrans tests =====

    /**
     * Test makeTrans generates HTML for new word.
     */
    public function testMakeTransGeneratesHtmlForNewWord(): void
    {
        $result = $this->handler->makeTrans(0, null, '', 'test', 1);

        $this->assertIsString($result);
        $this->assertStringContainsString('input', $result);
        $this->assertStringContainsString('rg0', $result);
        $this->assertStringContainsString('tx0', $result);
    }

    /**
     * Test makeTrans generates HTML for existing word with ID.
     */
    public function testMakeTransGeneratesHtmlForExistingWord(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->makeTrans(1, 1, 'translation', 'test', 1);

        $this->assertIsString($result);
        $this->assertStringContainsString('input', $result);
        $this->assertStringContainsString('rg1', $result);
        $this->assertStringContainsString('tx1', $result);
    }

    // ===== Method signature tests =====

    /**
     * Test all handler methods have correct return types.
     */
    public function testHandlerMethodsHaveCorrectReturnTypes(): void
    {
        $reflection = new \ReflectionClass(TextApiHandler::class);

        $arrayReturnMethods = [
            'formatSetTextPosition',
            'formatSetAudioPosition',
            'formatSetAnnotation',
            'formatSetDisplayMode',
            'formatMarkAllWellKnown',
            'formatMarkAllIgnored',
            'formatGetWords',
            'formatTextsByLanguage',
            'formatArchivedTextsByLanguage',
            'formatGetPrintItems',
            'formatGetAnnotation',
            'formatTermTranslations',
            'formatEditTermForm',
            'formatGetTextScore',
            'formatGetTextScores',
            'formatGetRecommendedTexts',
        ];

        foreach ($arrayReturnMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $returnType = $method->getReturnType();

            $this->assertNotNull(
                $returnType,
                "Method $methodName should have a return type"
            );
            $this->assertEquals(
                'array',
                $returnType->getName(),
                "Method $methodName should return array"
            );
        }
    }
}

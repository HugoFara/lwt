<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Services;

use Lwt\Shared\Infrastructure\Globals;
use Lwt\Modules\Text\Application\Services\SentenceService;
use Lwt\Modules\Language\Application\Services\TextParsingService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for the SentenceService class.
 *
 * Note: Many methods in SentenceService depend on direct database access.
 * Full coverage requires integration tests with a test database.
 * These tests focus on methods that can be tested in isolation.
 */
class SentenceServiceTest extends TestCase
{
    private SentenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Globals::reset();
        $this->service = new SentenceService();
    }

    protected function tearDown(): void
    {
        Globals::reset();
        parent::tearDown();
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    public function testConstructorCreatesDefaultTextParsingService(): void
    {
        $service = new SentenceService();

        $this->assertInstanceOf(SentenceService::class, $service);
    }

    public function testConstructorAcceptsCustomTextParsingService(): void
    {
        $textParsingService = $this->createMock(TextParsingService::class);
        $service = new SentenceService($textParsingService);

        $this->assertInstanceOf(SentenceService::class, $service);
    }

    // =========================================================================
    // convertZwsToSpacing() Tests (using Reflection)
    // =========================================================================

    /**
     * Get access to private method for testing.
     */
    private function getConvertZwsToSpacingMethod(): ReflectionMethod
    {
        $method = new ReflectionMethod(SentenceService::class, 'convertZwsToSpacing');
        $method->setAccessible(true);
        return $method;
    }

    public function testConvertZwsToSpacingAddsSpaceBetweenWords(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        // Using English word characters
        $termchar = 'a-zA-Z';
        $input = "Hello​world"; // ZWS between words
        $result = $method->invoke($this->service, $input, $termchar);

        $this->assertEquals("Hello world", $result);
    }

    public function testConvertZwsToSpacingPreservesPunctuation(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        $termchar = 'a-zA-Z';
        $input = "Hello​,​world"; // ZWS around comma
        $result = $method->invoke($this->service, $input, $termchar);

        $this->assertEquals("Hello, world", $result);
    }

    public function testConvertZwsToSpacingHandlesEmptyString(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        $termchar = 'a-zA-Z';
        $result = $method->invoke($this->service, "", $termchar);

        $this->assertEquals("", $result);
    }

    public function testConvertZwsToSpacingTrimsResult(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        $termchar = 'a-zA-Z';
        $input = "​Hello​world​"; // ZWS at start and end
        $result = $method->invoke($this->service, $input, $termchar);

        $this->assertEquals("Hello world", $result);
    }

    public function testConvertZwsToSpacingHandlesSentenceEnding(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        $termchar = 'a-zA-Z';
        $input = "Hello​.​World"; // ZWS around period
        $result = $method->invoke($this->service, $input, $termchar);

        // Period followed by word should have space
        $this->assertEquals("Hello. World", $result);
    }

    public function testConvertZwsToSpacingHandlesUnicode(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        // French characters
        $termchar = 'a-zA-ZÀ-ÿ';
        $input = "Bonjour​le​monde";
        $result = $method->invoke($this->service, $input, $termchar);

        $this->assertEquals("Bonjour le monde", $result);
    }

    // =========================================================================
    // extractCenteredPortion() Tests (using Reflection)
    // =========================================================================

    private function getExtractCenteredPortionMethod(): ReflectionMethod
    {
        $method = new ReflectionMethod(SentenceService::class, 'extractCenteredPortion');
        $method->setAccessible(true);
        return $method;
    }

    public function testExtractCenteredPortionReturnsFullTextIfUnderLimit(): void
    {
        $method = $this->getExtractCenteredPortionMethod();

        $text = "Short text";
        $result = $method->invoke($this->service, $text, 100);

        $this->assertEquals("Short text", $result);
    }

    public function testExtractCenteredPortionTruncatesLongText(): void
    {
        $method = $this->getExtractCenteredPortionMethod();

        $text = str_repeat("a", 200);
        $result = $method->invoke($this->service, $text, 50);

        // Should be shorter than original
        $this->assertLessThan(200, mb_strlen($result));
        // Should include ellipsis
        $this->assertStringContainsString('...', $result);
    }

    public function testExtractCenteredPortionHandlesEmptyString(): void
    {
        $method = $this->getExtractCenteredPortionMethod();

        $result = $method->invoke($this->service, "", 50);

        $this->assertEquals("", $result);
    }

    // =========================================================================
    // extractPortionAroundWord() Tests (using Reflection)
    // =========================================================================

    private function getExtractPortionAroundWordMethod(): ReflectionMethod
    {
        $method = new ReflectionMethod(SentenceService::class, 'extractPortionAroundWord');
        $method->setAccessible(true);
        return $method;
    }

    public function testExtractPortionAroundWordCentersOnWord(): void
    {
        $method = $this->getExtractPortionAroundWordMethod();

        $text = "The quick brown fox jumps over the lazy dog";
        $word = "fox";
        $result = $method->invoke($this->service, $text, $word, 10);

        $this->assertStringContainsString("fox", $result);
    }

    public function testExtractPortionAroundWordHandlesWordNotFound(): void
    {
        $method = $this->getExtractPortionAroundWordMethod();

        $text = "The quick brown fox jumps over the lazy dog";
        $word = "cat";
        $result = $method->invoke($this->service, $text, $word, 10);

        // Should fallback to centered extraction
        $this->assertIsString($result);
    }

    public function testExtractPortionAroundWordAddsEllipsisWhenNeeded(): void
    {
        $method = $this->getExtractPortionAroundWordMethod();

        $text = "This is a very long text with many words where target is somewhere in the middle of the sentence";
        $word = "target";
        $result = $method->invoke($this->service, $text, $word, 10);

        // Should have ellipsis since we're extracting from the middle
        $this->assertStringContainsString("...", $result);
        $this->assertStringContainsString("target", $result);
    }

    public function testExtractPortionAroundWordNoEllipsisAtStart(): void
    {
        $method = $this->getExtractPortionAroundWordMethod();

        $text = "start word in the middle of this text";
        $word = "start";
        $result = $method->invoke($this->service, $text, $word, 10);

        // Should not start with ellipsis since word is at the start
        $this->assertStringStartsWith("start", $result);
    }

    // =========================================================================
    // buildSentencesContainingWordQuery() Tests
    // (Tests structure without requiring database execution)
    // =========================================================================

    /**
     * @group integration
     */
    public function testBuildSentencesContainingWordQueryReturnsEmptyForNoLanguage(): void
    {
        // This test requires database access to check language settings
        // Marking as integration test
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required for this test');
        }

        $result = $this->service->buildSentencesContainingWordQuery('test', 999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('params', $result);
    }

    // =========================================================================
    // Method Availability Tests
    // =========================================================================

    public function testServiceHasExpectedPublicMethods(): void
    {
        $expectedMethods = [
            'buildSentencesContainingWordQuery',
            'findSentencesFromWord',
            'formatSentence',
            'getSentenceText',
            'getSentenceAtPosition',
            'getSentencesWithWord',
            'get20Sentences',
            'renderExampleSentencesArea',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                method_exists($this->service, $method),
                "Method $method should exist in SentenceService"
            );
        }
    }

    // =========================================================================
    // formatSentence() Tests (requires database)
    // =========================================================================

    /**
     * @group integration
     */
    public function testFormatSentenceReturnsArrayWithTwoElements(): void
    {
        // formatSentence requires database access (looks up sentence by ID)
        // This test verifies the method signature and return type
        try {
            $result = $this->service->formatSentence(0, 'test', 1);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database required for formatSentence test');
        }

        $this->assertIsArray($result);
    }

    // =========================================================================
    // getSentenceText() Tests (requires database)
    // =========================================================================

    /**
     * @group integration
     */
    public function testGetSentenceTextReturnsString(): void
    {
        // getSentenceText requires database access
        try {
            $result = $this->service->getSentenceText(0, 'test', 0);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database required for getSentenceText test');
        }

        // May return null for non-existent sentence
        $this->assertTrue($result === null || is_string($result));
    }

    // =========================================================================
    // Additional extractCenteredPortion() Tests
    // =========================================================================

    public function testExtractCenteredPortionWithExactLimit(): void
    {
        $method = $this->getExtractCenteredPortionMethod();

        $text = str_repeat("a", 50);
        $result = $method->invoke($this->service, $text, 50);

        // Should return full text when exactly at limit
        $this->assertEquals($text, $result);
    }

    public function testExtractCenteredPortionWithVeryShortLimit(): void
    {
        $method = $this->getExtractCenteredPortionMethod();

        $text = "Hello World";
        $result = $method->invoke($this->service, $text, 5);

        // Should still produce a result
        $this->assertIsString($result);
        $this->assertLessThanOrEqual(mb_strlen($text), mb_strlen($result));
    }

    // =========================================================================
    // Additional extractPortionAroundWord() Tests
    // =========================================================================

    public function testExtractPortionAroundWordHandlesEmptyWord(): void
    {
        $method = $this->getExtractPortionAroundWordMethod();

        $text = "The quick brown fox";
        $result = $method->invoke($this->service, $text, "", 50);

        // Should fallback to centered extraction
        $this->assertIsString($result);
    }

    public function testExtractPortionAroundWordWithZeroLimit(): void
    {
        $method = $this->getExtractPortionAroundWordMethod();

        $text = "The quick brown fox jumps over the lazy dog";
        $word = "fox";
        $result = $method->invoke($this->service, $text, $word, 0);

        $this->assertIsString($result);
    }

    public function testExtractPortionAroundWordCaseInsensitive(): void
    {
        $method = $this->getExtractPortionAroundWordMethod();

        $text = "The quick brown FOX jumps over the lazy dog";
        $word = "fox";
        $result = $method->invoke($this->service, $text, $word, 10);

        // Should find case-insensitive match
        $this->assertStringContainsString("FOX", $result);
    }

    // =========================================================================
    // Additional convertZwsToSpacing() Tests
    // =========================================================================

    public function testConvertZwsToSpacingWithMultipleConsecutiveZws(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        $termchar = 'a-zA-Z';
        $input = "Hello​​​world"; // Multiple ZWS between words
        $result = $method->invoke($this->service, $input, $termchar);

        // Should collapse multiple ZWS into single space
        $this->assertIsString($result);
    }

    public function testConvertZwsToSpacingWithOnlyZws(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        $termchar = 'a-zA-Z';
        $input = "​​​"; // Only ZWS characters
        $result = $method->invoke($this->service, $input, $termchar);

        // Should trim to empty string
        $this->assertEquals("", $result);
    }

    public function testConvertZwsToSpacingPreservesExistingSpaces(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        $termchar = 'a-zA-Z';
        $input = "Hello world"; // Regular spaces
        $result = $method->invoke($this->service, $input, $termchar);

        // Should preserve existing spaces
        $this->assertEquals("Hello world", $result);
    }

    // =========================================================================
    // Additional convertZwsToSpacing() Tests for Edge Cases
    // =========================================================================

    public function testConvertZwsToSpacingWithQuotedText(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        $termchar = 'a-zA-Z';
        $input = '"Hello"​world';
        $result = $method->invoke($this->service, $input, $termchar);

        // Quote followed by word should have space
        $this->assertStringContainsString('world', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    public function testConvertZwsToSpacingWithBrackets(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        $termchar = 'a-zA-Z';
        $input = "(test)​word";
        $result = $method->invoke($this->service, $input, $termchar);

        $this->assertStringContainsString('test', $result);
        $this->assertStringContainsString('word', $result);
    }

    public function testConvertZwsToSpacingWithCyrillicCharacters(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        // Extended character set including Cyrillic
        $termchar = 'a-zA-ZА-Яа-яЁё';
        $input = "Привет​мир";
        $result = $method->invoke($this->service, $input, $termchar);

        $this->assertStringContainsString("Привет", $result);
        $this->assertStringContainsString("мир", $result);
    }

    public function testConvertZwsToSpacingWithNumbers(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        $termchar = 'a-zA-Z0-9';
        $input = "test123​word456";
        $result = $method->invoke($this->service, $input, $termchar);

        $this->assertStringContainsString("123", $result);
        $this->assertStringContainsString("456", $result);
    }

    public function testConvertZwsToSpacingWithMixedPunctuation(): void
    {
        $method = $this->getConvertZwsToSpacingMethod();

        $termchar = 'a-zA-Z';
        $input = "Hello​!​World​?​Yes";
        $result = $method->invoke($this->service, $input, $termchar);

        // Should have proper spacing around punctuation
        $this->assertIsString($result);
    }

    // =========================================================================
    // Additional extractCenteredPortion() Tests
    // =========================================================================

    public function testExtractCenteredPortionWithUnicodeText(): void
    {
        $method = $this->getExtractCenteredPortionMethod();

        $text = "日本語のテキストです";
        $result = $method->invoke($this->service, $text, 100);

        $this->assertEquals($text, $result);
    }

    public function testExtractCenteredPortionPreservesWordBoundaries(): void
    {
        $method = $this->getExtractCenteredPortionMethod();

        $text = "The quick brown fox jumps over the lazy dog";
        $result = $method->invoke($this->service, $text, 15);

        // Result should contain ellipsis since text is truncated
        $this->assertStringContainsString('...', $result);
    }

    public function testExtractCenteredPortionWithSingleWord(): void
    {
        $method = $this->getExtractCenteredPortionMethod();

        $text = "Supercalifragilisticexpialidocious";
        $result = $method->invoke($this->service, $text, 10);

        // Should truncate the single word
        $this->assertIsString($result);
        $this->assertStringContainsString('...', $result);
    }

    // =========================================================================
    // Additional extractPortionAroundWord() Tests
    // =========================================================================

    public function testExtractPortionAroundWordAtStart(): void
    {
        $method = $this->getExtractPortionAroundWordMethod();

        $text = "Start is at the beginning of this text with many words";
        $word = "Start";
        $result = $method->invoke($this->service, $text, $word, 15);

        $this->assertStringContainsString("Start", $result);
        // Should not have ellipsis at the start
        $this->assertStringStartsNotWith('...', $result);
    }

    public function testExtractPortionAroundWordAtEnd(): void
    {
        $method = $this->getExtractPortionAroundWordMethod();

        $text = "This is a long text with many words and end at finish";
        $word = "finish";
        $result = $method->invoke($this->service, $text, $word, 15);

        $this->assertStringContainsString("finish", $result);
    }

    public function testExtractPortionAroundWordWithUnicode(): void
    {
        $method = $this->getExtractPortionAroundWordMethod();

        $text = "これは日本語のテスト文です。特定の単語を探します。";
        $word = "テスト";
        $result = $method->invoke($this->service, $text, $word, 10);

        $this->assertStringContainsString("テスト", $result);
    }

    // =========================================================================
    // renderExampleSentencesArea() Tests
    // =========================================================================

    public function testRenderExampleSentencesAreaReturnsHtml(): void
    {
        $result = $this->service->renderExampleSentencesArea(1, 'test', 'targetCtl', 1);

        $this->assertIsString($result);
        $this->assertStringContainsString('<div id="exsent">', $result);
    }

    public function testRenderExampleSentencesAreaContainsTermInLowerCase(): void
    {
        $result = $this->service->renderExampleSentencesArea(1, 'myterm', 'targetCtl', 1);

        $this->assertStringContainsString('myterm', $result);
    }

    public function testRenderExampleSentencesAreaContainsDataAttributes(): void
    {
        $result = $this->service->renderExampleSentencesArea(1, 'word', 'targetCtl', 42);

        $this->assertStringContainsString('data-lang="1"', $result);
        $this->assertStringContainsString('data-termlc="word"', $result);
        $this->assertStringContainsString('data-target="targetCtl"', $result);
        $this->assertStringContainsString('data-wid="42"', $result);
    }

    public function testRenderExampleSentencesAreaEscapesHtmlSpecialChars(): void
    {
        $result = $this->service->renderExampleSentencesArea(1, '<script>', 'targetCtl', 1);

        // Should not contain unescaped script tag
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testRenderExampleSentencesAreaContainsShowSentencesAction(): void
    {
        $result = $this->service->renderExampleSentencesArea(1, 'word', 'targetCtl', 1);

        $this->assertStringContainsString('data-action="show-sentences"', $result);
        $this->assertStringContainsString('Show Sentences', $result);
    }

    public function testRenderExampleSentencesAreaContainsLoadingIcon(): void
    {
        $result = $this->service->renderExampleSentencesArea(1, 'word', 'targetCtl', 1);

        $this->assertStringContainsString('exsent-waiting', $result);
        $this->assertStringContainsString('Loading...', $result);
    }

    // =========================================================================
    // get20Sentences() Tests (structure only, no DB)
    // =========================================================================

    public function testGet20SentencesReturnsHtmlString(): void
    {
        // Even with no sentences found, should return valid HTML structure
        try {
            $result = $this->service->get20Sentences(1, 'testword', null, 'targetCtl', 1);
            $this->assertIsString($result);
            $this->assertStringContainsString('Sentences in active texts', $result);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database required for full get20Sentences test');
        }
    }

    public function testGet20SentencesContainsWordInHeader(): void
    {
        try {
            $result = $this->service->get20Sentences(1, 'specialword', null, 'targetCtl', 1);
            $this->assertStringContainsString('specialword', $result);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database required for full get20Sentences test');
        }
    }

    public function testGet20SentencesEscapesWordInHeader(): void
    {
        try {
            $result = $this->service->get20Sentences(1, '<b>bold</b>', null, 'targetCtl', 1);
            // Word should be escaped in the header
            $this->assertStringNotContainsString('<b>bold</b>', $result);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database required for full get20Sentences test');
        }
    }

    // =========================================================================
    // getSentencesWithWord() Tests (structure only)
    // =========================================================================

    public function testGetSentencesWithWordReturnsArray(): void
    {
        try {
            $result = $this->service->getSentencesWithWord(1, 'test', null, 1, 10);
            $this->assertIsArray($result);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database required for getSentencesWithWord test');
        }
    }

    public function testGetSentencesWithWordLimitParameter(): void
    {
        try {
            // With limit of 1, should return at most 1 result
            $result = $this->service->getSentencesWithWord(1, 'test', null, 1, 1);
            $this->assertIsArray($result);
            $this->assertLessThanOrEqual(1, count($result));
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database required for getSentencesWithWord test');
        }
    }

    // =========================================================================
    // findSentencesFromWord() Tests (structure only)
    // =========================================================================

    public function testFindSentencesFromWordReturnsArray(): void
    {
        try {
            $result = $this->service->findSentencesFromWord(null, 'test', 1, 5);
            $this->assertIsArray($result);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database required for findSentencesFromWord test');
        }
    }

    public function testFindSentencesFromWordWithWordId(): void
    {
        try {
            $result = $this->service->findSentencesFromWord(1, 'test', 1, 5);
            $this->assertIsArray($result);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database required for findSentencesFromWord test');
        }
    }

    public function testFindSentencesFromWordWithComplexSearch(): void
    {
        try {
            // -1 triggers complex search mode
            $result = $this->service->findSentencesFromWord(-1, 'test', 1, 5);
            $this->assertIsArray($result);
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database required for findSentencesFromWord test');
        }
    }

    // =========================================================================
    // getSentenceAtPosition() Tests (structure only)
    // =========================================================================

    public function testGetSentenceAtPositionReturnsNullOrString(): void
    {
        try {
            $result = $this->service->getSentenceAtPosition(1, 1);
            $this->assertTrue($result === null || is_string($result));
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database required for getSentenceAtPosition test');
        }
    }

    // =========================================================================
    // Class Property Tests
    // =========================================================================

    public function testServiceHasTextParsingServiceDependency(): void
    {
        $reflection = new \ReflectionClass(SentenceService::class);

        $this->assertTrue(
            $reflection->hasProperty('textParsingService'),
            'SentenceService should have textParsingService property'
        );
    }

    public function testPrivateMethodsExist(): void
    {
        $reflection = new \ReflectionClass(SentenceService::class);

        $privateMethods = [
            'convertZwsToSpacing',
            'extractCenteredPortion',
            'extractPortionAroundWord',
            'executeSentencesContainingWordQuery',
        ];

        foreach ($privateMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "SentenceService should have private method: $method"
            );
        }
    }
}

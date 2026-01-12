<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Services;

use Lwt\Core\Globals;
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
}

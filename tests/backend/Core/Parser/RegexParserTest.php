<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Parser;

use PHPUnit\Framework\TestCase;
use Lwt\Core\Parser\Parsers\RegexParser;
use Lwt\Core\Parser\ParserConfig;
use Lwt\Core\Parser\ParserResult;
use Lwt\Core\Parser\Token;

/**
 * Tests for the RegexParser class.
 */
class RegexParserTest extends TestCase
{
    private RegexParser $parser;

    protected function setUp(): void
    {
        $this->parser = new RegexParser();
    }

    public function testGetType(): void
    {
        $this->assertEquals('regex', $this->parser->getType());
    }

    public function testGetName(): void
    {
        $this->assertEquals('Standard (Regex)', $this->parser->getName());
    }

    public function testIsAvailable(): void
    {
        $this->assertTrue($this->parser->isAvailable());
    }

    public function testGetAvailabilityMessage(): void
    {
        $this->assertEquals('', $this->parser->getAvailabilityMessage());
    }

    public function testParseSimpleSentence(): void
    {
        $config = $this->createConfig();
        $text = "Hello world.";

        $result = $this->parser->parse($text, $config);

        $this->assertInstanceOf(ParserResult::class, $result);
        $this->assertGreaterThan(0, $result->getSentenceCount());
    }

    public function testParseMultipleSentences(): void
    {
        $config = $this->createConfig();
        $text = "First sentence. Second sentence.";

        $result = $this->parser->parse($text, $config);

        $this->assertGreaterThanOrEqual(2, $result->getSentenceCount());
    }

    public function testParseReturnsTokens(): void
    {
        $config = $this->createConfig();
        $text = "Hello world.";

        $result = $this->parser->parse($text, $config);

        $this->assertGreaterThan(0, $result->getTokenCount());
        $tokens = $result->getTokens();
        $this->assertContainsOnlyInstancesOf(Token::class, $tokens);
    }

    public function testParseIdentifiesWords(): void
    {
        $config = $this->createConfig();
        $text = "Hello world.";

        $result = $this->parser->parse($text, $config);

        // Parser should produce tokens
        $tokens = $result->getTokens();
        $this->assertGreaterThan(0, count($tokens));

        // Check that some tokens are words (have text content)
        $wordTexts = array_filter(
            array_map(fn($t) => $t->getText(), $tokens),
            fn($text) => preg_match('/^[a-zA-Z]+$/', $text)
        );
        $this->assertGreaterThan(0, count($wordTexts), 'Should have word-like tokens');
    }

    public function testParseEmptyText(): void
    {
        $config = $this->createConfig();
        $text = "";

        $result = $this->parser->parse($text, $config);

        $this->assertInstanceOf(ParserResult::class, $result);
        // Empty text should still return at least one empty sentence
        $this->assertGreaterThanOrEqual(1, $result->getSentenceCount());
    }

    public function testParseWithParagraphs(): void
    {
        $config = $this->createConfig();
        $text = "First paragraph.\n\nSecond paragraph.";

        $result = $this->parser->parse($text, $config);

        // Should have multiple sentences or contain paragraph markers
        $this->assertGreaterThan(0, $result->getSentenceCount());
    }

    /**
     * Create a basic parser configuration for testing.
     */
    private function createConfig(): ParserConfig
    {
        return new ParserConfig(
            languageId: 1,
            regexpSplitSentences: '.!?',
            exceptionsSplitSentences: '',
            regexpWordCharacters: 'a-zA-Z0-9',
            characterSubstitutions: '',
            removeSpaces: false,
            splitEachChar: false,
            rightToLeft: false
        );
    }
}

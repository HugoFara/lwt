<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language\Infrastructure\Parser;

use Lwt\Modules\Language\Domain\Parser\ExternalParserConfig;
use Lwt\Modules\Language\Domain\Parser\ParserConfig;
use Lwt\Modules\Language\Infrastructure\Parser\ExternalParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for the ExternalParser class.
 */
class ExternalParserTest extends TestCase
{
    private function createParserConfig(): ParserConfig
    {
        return new ParserConfig(
            1,
            '.!?',
            '',
            'a-zA-Z',
            '',
            false,
            false,
            false
        );
    }

    public function testGetTypeReturnsConfigType(): void
    {
        $config = new ExternalParserConfig(
            'custom',
            'Custom Parser',
            '/usr/bin/custom',
            [],
            'stdin',
            'line'
        );

        $parser = new ExternalParser($config);

        $this->assertEquals('custom', $parser->getType());
    }

    public function testGetNameReturnsConfigName(): void
    {
        $config = new ExternalParserConfig(
            'custom',
            'Custom Parser Name',
            '/usr/bin/custom',
            [],
            'stdin',
            'line'
        );

        $parser = new ExternalParser($config);

        $this->assertEquals('Custom Parser Name', $parser->getName());
    }

    public function testIsAvailableReturnsFalseForNonexistentBinary(): void
    {
        $config = new ExternalParserConfig(
            'test',
            'Test',
            '/nonexistent/binary/path/12345',
            [],
            'stdin',
            'line'
        );

        $parser = new ExternalParser($config);

        $this->assertFalse($parser->isAvailable());
        $this->assertNotEmpty($parser->getAvailabilityMessage());
    }

    public function testIsAvailableReturnsTrueForExistingCommand(): void
    {
        // 'echo' should be available on all systems
        $config = new ExternalParserConfig(
            'test',
            'Test',
            'echo',
            [],
            'stdin',
            'line'
        );

        $parser = new ExternalParser($config);

        $this->assertTrue($parser->isAvailable());
        $this->assertEmpty($parser->getAvailabilityMessage());
    }

    public function testParseThrowsWhenNotAvailable(): void
    {
        $config = new ExternalParserConfig(
            'test',
            'Test',
            '/nonexistent/binary/12345',
            [],
            'stdin',
            'line'
        );

        $parser = new ExternalParser($config);
        $parserConfig = $this->createParserConfig();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not available');

        $parser->parse('test text', $parserConfig);
    }

    public function testParseReturnsEmptyResultForEmptyText(): void
    {
        $config = new ExternalParserConfig(
            'test',
            'Test',
            'echo',
            [],
            'stdin',
            'line'
        );

        $parser = new ExternalParser($config);
        $parserConfig = $this->createParserConfig();

        $result = $parser->parse('', $parserConfig);

        $this->assertTrue($result->isEmpty());
    }

    public function testParseReturnsEmptyResultForWhitespaceOnly(): void
    {
        $config = new ExternalParserConfig(
            'test',
            'Test',
            'echo',
            [],
            'stdin',
            'line'
        );

        $parser = new ExternalParser($config);
        $parserConfig = $this->createParserConfig();

        $result = $parser->parse('   ', $parserConfig);

        $this->assertTrue($result->isEmpty());
    }

    /**
     * @group integration
     */
    public function testParseWithCatAsEchoLineFormat(): void
    {
        // Skip on Windows where cat may not be available
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Cat not available on Windows');
        }

        // Use cat to echo stdin back - this is more reliable than echo
        $config = new ExternalParserConfig(
            'test',
            'Test',
            'cat',
            [],
            'stdin',
            'line'
        );

        $parser = new ExternalParser($config);
        $parserConfig = $this->createParserConfig();

        // Cat outputs what we send it
        $result = $parser->parse('hello', $parserConfig);

        $this->assertFalse($result->isEmpty());
        $this->assertGreaterThan(0, $result->getTokenCount());
    }

    /**
     * @group integration
     */
    public function testParseWithCatCommandStdinMode(): void
    {
        // Skip on Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Cat not available on Windows');
        }

        $config = new ExternalParserConfig(
            'test',
            'Test',
            'cat',
            [],
            'stdin',
            'line'
        );

        $parser = new ExternalParser($config);
        $parserConfig = $this->createParserConfig();

        $result = $parser->parse("word1\nword2\nword3", $parserConfig);

        // Cat outputs each line, so we should have 3 tokens
        $this->assertEquals(3, $result->getTokenCount());

        $tokens = $result->getTokens();
        $this->assertEquals('word1', $tokens[0]->getText());
        $this->assertEquals('word2', $tokens[1]->getText());
        $this->assertEquals('word3', $tokens[2]->getText());
    }

    /**
     * @group integration
     */
    public function testParseWithFileInputMode(): void
    {
        // Skip on Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Cat not available on Windows');
        }

        $config = new ExternalParserConfig(
            'test',
            'Test',
            'cat',
            [],
            'file',
            'line'
        );

        $parser = new ExternalParser($config);
        $parserConfig = $this->createParserConfig();

        $result = $parser->parse("line1\nline2", $parserConfig);

        $this->assertEquals(2, $result->getTokenCount());
    }

    public function testParseWakatiFormat(): void
    {
        // Skip on Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Echo behavior differs on Windows');
        }

        // Use printf to output space-separated tokens
        $config = new ExternalParserConfig(
            'test',
            'Test',
            'printf',
            ['%s', 'word1 word2 word3'],
            'stdin',
            'wakati'
        );

        $parser = new ExternalParser($config);
        $parserConfig = $this->createParserConfig();

        $result = $parser->parse('ignored', $parserConfig);

        // Wakati format: tokens separated by spaces
        // We should get tokens with spaces between them
        $words = $result->getWords();
        $this->assertCount(3, $words);
        $this->assertEquals('word1', $words[0]->getText());
        $this->assertEquals('word2', $words[1]->getText());
        $this->assertEquals('word3', $words[2]->getText());
    }

    public function testAvailabilityIsCached(): void
    {
        $config = new ExternalParserConfig(
            'test',
            'Test',
            '/nonexistent/binary/12345',
            [],
            'stdin',
            'line'
        );

        $parser = new ExternalParser($config);

        // First call
        $available1 = $parser->isAvailable();
        $message1 = $parser->getAvailabilityMessage();

        // Second call should return cached result
        $available2 = $parser->isAvailable();
        $message2 = $parser->getAvailabilityMessage();

        $this->assertEquals($available1, $available2);
        $this->assertEquals($message1, $message2);
    }

    public function testParseSentenceBoundaryDetection(): void
    {
        // Skip on Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Echo behavior differs on Windows');
        }

        $config = new ExternalParserConfig(
            'test',
            'Test',
            'cat',
            [],
            'stdin',
            'line'
        );

        $parser = new ExternalParser($config);
        $parserConfig = $this->createParserConfig();

        // Empty lines separate sentences in line mode
        $result = $parser->parse("hello\n\nworld", $parserConfig);

        // Should have 2 sentences
        $this->assertEquals(2, $result->getSentenceCount());
    }

    public function testParseIdentifiesWordsVsNonWords(): void
    {
        // Skip on Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Cat not available on Windows');
        }

        $config = new ExternalParserConfig(
            'test',
            'Test',
            'cat',
            [],
            'stdin',
            'line'
        );

        $parser = new ExternalParser($config);
        // Configure word characters as letters only
        $parserConfig = new ParserConfig(1, '.!?', '', 'a-zA-Z', '', false, false, false);

        $result = $parser->parse("hello\n!!!\nworld", $parserConfig);

        $tokens = $result->getTokens();

        // hello is a word
        $this->assertTrue($tokens[0]->isWord());
        // !!! is not a word
        $this->assertFalse($tokens[1]->isWord());
        // world is a word
        $this->assertTrue($tokens[2]->isWord());
    }
}

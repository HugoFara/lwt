<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language\Infrastructure\Parser;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Language\Infrastructure\Parser\ParserRegistry;
use Lwt\Modules\Language\Domain\Parser\ParserInterface;
use Lwt\Modules\Language\Infrastructure\Parser\RegexParser;
use Lwt\Modules\Language\Infrastructure\Parser\CharacterParser;
use Lwt\Modules\Language\Infrastructure\Parser\MecabParser;

/**
 * Tests for the ParserRegistry class.
 */
class ParserRegistryTest extends TestCase
{
    private ParserRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ParserRegistry();
    }

    public function testRegistryRegistersDefaultParsers(): void
    {
        $all = $this->registry->getAll();

        $this->assertArrayHasKey('regex', $all);
        $this->assertArrayHasKey('character', $all);
        $this->assertArrayHasKey('mecab', $all);
    }

    public function testGetReturnsParser(): void
    {
        $parser = $this->registry->get('regex');

        $this->assertInstanceOf(ParserInterface::class, $parser);
        $this->assertInstanceOf(RegexParser::class, $parser);
    }

    public function testGetReturnsNullForUnknownType(): void
    {
        $parser = $this->registry->get('unknown');

        $this->assertNull($parser);
    }

    public function testHasReturnsTrueForRegisteredType(): void
    {
        $this->assertTrue($this->registry->has('regex'));
        $this->assertTrue($this->registry->has('character'));
        $this->assertTrue($this->registry->has('mecab'));
    }

    public function testHasReturnsFalseForUnknownType(): void
    {
        $this->assertFalse($this->registry->has('unknown'));
    }

    public function testGetDefaultType(): void
    {
        $this->assertEquals('regex', $this->registry->getDefaultType());
    }

    public function testResolveParserTypeFromRow(): void
    {
        // Test explicit parser type
        $row = ['LgParserType' => 'character'];
        $this->assertEquals('character', $this->registry->resolveParserTypeFromRow($row));

        // Test MeCab magic word
        $row = ['LgRegexpWordCharacters' => 'MECAB'];
        $this->assertEquals('mecab', $this->registry->resolveParserTypeFromRow($row));

        $row = ['LgRegexpWordCharacters' => 'mecab'];
        $this->assertEquals('mecab', $this->registry->resolveParserTypeFromRow($row));

        // Test splitEachChar flag
        $row = ['LgSplitEachChar' => 1];
        $this->assertEquals('character', $this->registry->resolveParserTypeFromRow($row));

        // Test default fallback
        $row = ['LgRegexpWordCharacters' => 'a-zA-Z'];
        $this->assertEquals('regex', $this->registry->resolveParserTypeFromRow($row));
    }

    public function testGetAvailableReturnsAvailableParsers(): void
    {
        $available = $this->registry->getAvailable();

        // At minimum, regex and character parsers should always be available
        $this->assertArrayHasKey('regex', $available);
        $this->assertArrayHasKey('character', $available);
    }

    public function testGetParserInfo(): void
    {
        $info = $this->registry->getParserInfo();

        $this->assertArrayHasKey('regex', $info);
        $this->assertArrayHasKey('type', $info['regex']);
        $this->assertArrayHasKey('name', $info['regex']);
        $this->assertArrayHasKey('available', $info['regex']);
        $this->assertArrayHasKey('message', $info['regex']);
    }

    public function testRegisterCustomParser(): void
    {
        $customParser = $this->createMock(ParserInterface::class);
        $customParser->method('getType')->willReturn('custom');

        $this->registry->register($customParser);

        $this->assertTrue($this->registry->has('custom'));
        $this->assertSame($customParser, $this->registry->get('custom'));
    }
}

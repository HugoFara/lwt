<?php

declare(strict_types=1);

namespace Lwt\Tests\Core;

use PHPUnit\Framework\TestCase;
use Lwt\Core\StringUtils;

/**
 * Tests for StringUtils::parseInlineMarkdown()
 *
 * @license Unlicense <http://unlicense.org/>
 */
class StringUtilsMarkdownTest extends TestCase
{
    // =========================================================================
    // Basic formatting
    // =========================================================================

    public function testParseBoldWithDoubleAsterisks(): void
    {
        $result = StringUtils::parseInlineMarkdown('**bold**');
        $this->assertEquals('<strong>bold</strong>', $result);
    }

    public function testParseItalicWithSingleAsterisk(): void
    {
        $result = StringUtils::parseInlineMarkdown('*italic*');
        $this->assertEquals('<em>italic</em>', $result);
    }

    public function testParseStrikethroughWithDoubleTildes(): void
    {
        $result = StringUtils::parseInlineMarkdown('~~strikethrough~~');
        $this->assertEquals('<del>strikethrough</del>', $result);
    }

    public function testParseLinks(): void
    {
        $result = StringUtils::parseInlineMarkdown('[text](https://example.com)');
        $this->assertEquals(
            '<a href="https://example.com" target="_blank" rel="noopener noreferrer">text</a>',
            $result
        );
    }

    // =========================================================================
    // Combined formatting
    // =========================================================================

    public function testBoldAndItalicTogether(): void
    {
        $result = StringUtils::parseInlineMarkdown('**bold** and *italic*');
        $this->assertEquals('<strong>bold</strong> and <em>italic</em>', $result);
    }

    public function testMultipleFormattingTypes(): void
    {
        $result = StringUtils::parseInlineMarkdown('**bold** *italic* ~~strike~~');
        $this->assertEquals('<strong>bold</strong> <em>italic</em> <del>strike</del>', $result);
    }

    public function testFormattingWithinLinks(): void
    {
        $result = StringUtils::parseInlineMarkdown('[**bold link**](https://example.com)');
        $expected = '<a href="https://example.com" target="_blank" rel="noopener noreferrer">' .
            '<strong>bold link</strong></a>';
        $this->assertEquals($expected, $result);
    }

    // =========================================================================
    // Edge cases
    // =========================================================================

    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertEquals('', StringUtils::parseInlineMarkdown(''));
    }

    public function testPlainTextWithoutFormatting(): void
    {
        $result = StringUtils::parseInlineMarkdown('just plain text');
        $this->assertEquals('just plain text', $result);
    }

    // =========================================================================
    // XSS prevention
    // =========================================================================

    public function testEscapesScriptTags(): void
    {
        $result = StringUtils::parseInlineMarkdown('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testEscapesAngleBrackets(): void
    {
        $result = StringUtils::parseInlineMarkdown('a < b > c');
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);
    }

    public function testEscapesQuotes(): void
    {
        $result = StringUtils::parseInlineMarkdown('He said "hello"');
        $this->assertStringContainsString('&quot;', $result);
    }

    public function testEscapesAmpersands(): void
    {
        $result = StringUtils::parseInlineMarkdown('Tom & Jerry');
        $this->assertStringContainsString('&amp;', $result);
    }

    // =========================================================================
    // URL sanitization
    // =========================================================================

    public function testAllowsHttpsUrls(): void
    {
        $result = StringUtils::parseInlineMarkdown('[link](https://example.com)');
        $this->assertStringContainsString('href="https://example.com"', $result);
    }

    public function testAllowsHttpUrls(): void
    {
        $result = StringUtils::parseInlineMarkdown('[link](http://example.com)');
        $this->assertStringContainsString('href="http://example.com"', $result);
    }

    public function testAllowsRelativeUrlsStartingWithSlash(): void
    {
        $result = StringUtils::parseInlineMarkdown('[link](/path/to/page)');
        $this->assertStringContainsString('href="/path/to/page"', $result);
    }

    public function testAllowsRelativeUrlsStartingWithDotSlash(): void
    {
        $result = StringUtils::parseInlineMarkdown('[link](./relative)');
        $this->assertStringContainsString('href="./relative"', $result);
    }

    public function testAllowsRelativeUrlsStartingWithDotDotSlash(): void
    {
        $result = StringUtils::parseInlineMarkdown('[link](../parent)');
        $this->assertStringContainsString('href="../parent"', $result);
    }

    public function testBlocksJavascriptUrls(): void
    {
        $result = StringUtils::parseInlineMarkdown('[click](javascript:alert(1))');
        $this->assertStringNotContainsString('javascript:', $result);
        // Should just return the link text without the dangerous URL
        $this->assertStringContainsString('click', $result);
    }

    public function testBlocksDataUrls(): void
    {
        $result = StringUtils::parseInlineMarkdown('[click](data:text/html,<script>)');
        $this->assertStringNotContainsString('data:', $result);
    }

    // =========================================================================
    // Link attributes
    // =========================================================================

    public function testAddsTargetBlankToLinks(): void
    {
        $result = StringUtils::parseInlineMarkdown('[link](https://example.com)');
        $this->assertStringContainsString('target="_blank"', $result);
    }

    public function testAddsNoopenerNoreferrerToLinks(): void
    {
        $result = StringUtils::parseInlineMarkdown('[link](https://example.com)');
        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
    }

    // =========================================================================
    // Real-world usage examples
    // =========================================================================

    public function testTranslationWithEmphasis(): void
    {
        $input = 'to **emphasize** a word';
        $result = StringUtils::parseInlineMarkdown($input);
        $this->assertEquals('to <strong>emphasize</strong> a word', $result);
    }

    public function testTranslationWithDictionaryLink(): void
    {
        $input = 'see [Wiktionary](https://wiktionary.org/wiki/word)';
        $result = StringUtils::parseInlineMarkdown($input);
        $this->assertStringContainsString('<a href="https://wiktionary.org/wiki/word"', $result);
        $this->assertStringContainsString('>Wiktionary</a>', $result);
    }

    public function testNotesWithMultipleFormats(): void
    {
        $input = '*Formal* usage only. See also: **related term**';
        $result = StringUtils::parseInlineMarkdown($input);
        $this->assertStringContainsString('<em>Formal</em>', $result);
        $this->assertStringContainsString('<strong>related term</strong>', $result);
    }
}

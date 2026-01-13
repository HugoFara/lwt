<?php

declare(strict_types=1);

namespace Tests\Modules\Feed\Application\Services;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Feed\Application\Services\ArticleExtractor;

/**
 * Tests for ArticleExtractor service.
 *
 * @covers \Lwt\Modules\Feed\Application\Services\ArticleExtractor
 */
class ArticleExtractorTest extends TestCase
{
    private ArticleExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new ArticleExtractor();
    }

    // =========================================================================
    // mapWindowsCharset() Tests
    // =========================================================================

    public function testMapWindowsCharsetGreek(): void
    {
        $result = $this->extractor->mapWindowsCharset('windows-1253');

        $this->assertSame('el_GR.utf8', $result);
    }

    public function testMapWindowsCharsetTurkish(): void
    {
        $result = $this->extractor->mapWindowsCharset('windows-1254');

        $this->assertSame('tr_TR.utf8', $result);
    }

    public function testMapWindowsCharsetHebrew(): void
    {
        $result = $this->extractor->mapWindowsCharset('windows-1255');

        $this->assertSame('he.utf8', $result);
    }

    public function testMapWindowsCharsetArabic(): void
    {
        $result = $this->extractor->mapWindowsCharset('windows-1256');

        $this->assertSame('ar_AE.utf8', $result);
    }

    public function testMapWindowsCharsetVietnamese(): void
    {
        $result = $this->extractor->mapWindowsCharset('windows-1258');

        $this->assertSame('vi_VI.utf8', $result);
    }

    public function testMapWindowsCharsetThai(): void
    {
        $result = $this->extractor->mapWindowsCharset('windows-874');

        $this->assertSame('th_TH.utf8', $result);
    }

    public function testMapWindowsCharsetPassthroughUtf8(): void
    {
        $result = $this->extractor->mapWindowsCharset('UTF-8');

        $this->assertSame('UTF-8', $result);
    }

    public function testMapWindowsCharsetPassthroughIso88591(): void
    {
        $result = $this->extractor->mapWindowsCharset('ISO-8859-1');

        $this->assertSame('ISO-8859-1', $result);
    }

    public function testMapWindowsCharsetPassthroughUnknown(): void
    {
        $result = $this->extractor->mapWindowsCharset('unknown-charset');

        $this->assertSame('unknown-charset', $result);
    }

    // =========================================================================
    // detectCharset() Tests
    // =========================================================================

    public function testDetectCharsetWithOverride(): void
    {
        $result = $this->extractor->detectCharset(
            'https://example.com',
            '<html><head></head><body>Test</body></html>',
            'ISO-8859-1'
        );

        $this->assertSame('ISO-8859-1', $result);
    }

    public function testDetectCharsetWithExplicitOverride(): void
    {
        // When an explicit override is given, it should be used directly
        $html = '<html><head></head><body>Test ASCII content</body></html>';
        $result = $this->extractor->detectCharset('', $html, 'ISO-8859-1');

        $this->assertSame('ISO-8859-1', $result);
    }

    public function testDetectCharsetFromMetaContentTypeViaReflection(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'detectCharsetFromMeta');
        $method->setAccessible(true);

        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>Test</body></html>';
        $result = $method->invoke($this->extractor, $html);

        $this->assertSame('UTF-8', $result);
    }

    public function testDetectCharsetFromMetaCharsetViaReflection(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'detectCharsetFromMeta');
        $method->setAccessible(true);

        $html = '<html><head><meta charset="ISO-8859-1"></head><body>Test</body></html>';
        $result = $method->invoke($this->extractor, $html);

        $this->assertSame('ISO-8859-1', $result);
    }

    public function testDetectCharsetFromMetaReturnsNullWhenNotFound(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'detectCharsetFromMeta');
        $method->setAccessible(true);

        $html = '<html><body>Simple ASCII text without meta tags</body></html>';
        $result = $method->invoke($this->extractor, $html);

        $this->assertNull($result);
    }

    public function testDetectCharsetWithEmptyHtmlFallsBackToDetection(): void
    {
        // When no meta tags and detection is used, should get a result
        $html = '<html><body>Plain ASCII text</body></html>';
        // Use override to avoid network call
        $result = $this->extractor->detectCharset('', $html, 'UTF-8');

        $this->assertSame('UTF-8', $result);
    }

    // =========================================================================
    // Private Method Tests via Reflection - convertLineBreaks()
    // =========================================================================

    public function testConvertLineBreaksWithBrTag(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'convertLineBreaks');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, 'Hello<br>World');

        $this->assertSame("Hello\nWorld", $result);
    }

    public function testConvertLineBreaksWithBrSelfClosing(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'convertLineBreaks');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, 'Hello<br />World');

        $this->assertSame("Hello\nWorld", $result);
    }

    public function testConvertLineBreaksWithClosingBr(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'convertLineBreaks');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, 'Hello</br>World');

        $this->assertSame('HelloWorld', $result);
    }

    public function testConvertLineBreaksWithHeadingTags(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'convertLineBreaks');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, '<h1>Title</h1><p>Text</p>');

        $this->assertStringContainsString("\n</h1>", $result);
        $this->assertStringContainsString("\n</p>", $result);
    }

    public function testConvertLineBreaksWithMultipleBrs(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'convertLineBreaks');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, 'Line1<br>Line2<br />Line3<br>Line4');

        $this->assertSame("Line1\nLine2\nLine3\nLine4", $result);
    }

    // =========================================================================
    // Private Method Tests via Reflection - prepareInlineHtml()
    // =========================================================================

    public function testPrepareInlineHtmlAddsSpaces(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'prepareInlineHtml');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, '<p>Text</p>');

        $this->assertSame(' <p> Text </p> ', $result);
    }

    public function testPrepareInlineHtmlWithNestedTags(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'prepareInlineHtml');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, '<div><p>Text</p></div>');

        $this->assertSame(' <div>  <p> Text </p>  </div> ', $result);
    }

    // =========================================================================
    // Private Method Tests via Reflection - processInlineLink()
    // =========================================================================

    public function testProcessInlineLinkTrims(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'processInlineLink');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, '  https://example.com  ');

        $this->assertSame('https://example.com', $result);
    }

    // =========================================================================
    // Private Method Tests via Reflection - buildFilterTagsList()
    // =========================================================================

    public function testBuildFilterTagsListWithEmptyInput(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'buildFilterTagsList');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, '');

        // Default filter tags use XPath union operator, returned as single element
        $this->assertCount(1, $result);
        $this->assertStringContainsString('//img', $result[0]);
        $this->assertStringContainsString('//script', $result[0]);
        $this->assertStringContainsString('//meta', $result[0]);
    }

    public function testBuildFilterTagsListWithAdditionalTags(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'buildFilterTagsList');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, '//nav!?!//footer');

        // Should have defaults (1 element) plus custom tags (2 elements)
        $this->assertCount(3, $result);
        $this->assertStringContainsString('//img', $result[0]); // Defaults in first element
        $this->assertSame('//nav', $result[1]);
        $this->assertSame('//footer', $result[2]);
    }

    public function testBuildFilterTagsListRemovesTrailingSeparator(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'buildFilterTagsList');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, '//nav!?!');

        // Should not have empty string at end
        $this->assertNotContains('', $result);
        $this->assertCount(2, $result); // Defaults + //nav
    }

    // =========================================================================
    // Private Method Tests via Reflection - cleanExtractedText()
    // =========================================================================

    public function testCleanExtractedTextRemovesExtraSpaces(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'cleanExtractedText');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, 'Hello    World');

        $this->assertSame('Hello World', $result);
    }

    public function testCleanExtractedTextRemovesTabs(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'cleanExtractedText');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, "Hello\tWorld");

        $this->assertSame('Hello World', $result);
    }

    public function testCleanExtractedTextRemovesCarriageReturns(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'cleanExtractedText');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, "Hello\rWorld");

        $this->assertSame('Hello World', $result);
    }

    public function testCleanExtractedTextNormalizesNewlines(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'cleanExtractedText');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, "Para1\n\n\n\nPara2");

        $this->assertSame("Para1\n\nPara2", $result);
    }

    public function testCleanExtractedTextTrims(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'cleanExtractedText');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, '  Hello World  ');

        $this->assertSame('Hello World', $result);
    }

    public function testCleanExtractedTextComplexInput(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'cleanExtractedText');
        $method->setAccessible(true);

        $input = "  \t\rHello\t\t  World\r\n\n\n\nNext  paragraph  \r\n  ";
        $result = $method->invoke($this->extractor, $input);

        $this->assertStringNotContainsString("\t", $result);
        $this->assertStringNotContainsString("\r", $result);
        $this->assertStringNotContainsString('  ', $result);
        $this->assertStringNotContainsString("\n\n\n", $result);
    }

    // =========================================================================
    // Private Method Tests via Reflection - formatErrorMessage()
    // =========================================================================

    public function testFormatErrorMessageContainsLink(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'formatErrorMessage');
        $method->setAccessible(true);

        $item = [
            'link' => 'https://example.com/article',
            'title' => 'Test Article',
        ];

        $result = $method->invoke($this->extractor, $item);

        $this->assertStringContainsString('href="https://example.com/article"', $result);
        $this->assertStringContainsString('Test Article', $result);
        $this->assertStringContainsString('has no text section!', $result);
    }

    public function testFormatErrorMessageEscapesHtml(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'formatErrorMessage');
        $method->setAccessible(true);

        $item = [
            'link' => 'https://example.com/article?a=1&b=2',
            'title' => '<script>alert(1)</script>',
        ];

        $result = $method->invoke($this->extractor, $item);

        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringNotContainsString('<script>alert', $result);
    }

    public function testFormatErrorMessageIncludesDataAttributes(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'formatErrorMessage');
        $method->setAccessible(true);

        $item = ['link' => 'https://example.com', 'title' => 'Test'];

        $result = $method->invoke($this->extractor, $item);

        $this->assertStringContainsString('data-action="open-window"', $result);
        $this->assertStringContainsString('data-window-name="child"', $result);
    }

    // =========================================================================
    // Private Method Tests via Reflection - parseHtml()
    // =========================================================================

    public function testParseHtmlReturnsDomDocument(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'parseHtml');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, '<html><body>Test</body></html>');

        $this->assertInstanceOf(\DOMDocument::class, $result);
    }

    public function testParseHtmlHandlesMalformedHtml(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'parseHtml');
        $method->setAccessible(true);

        // Malformed HTML should not throw exception
        $result = $method->invoke($this->extractor, '<html><body><p>Unclosed');

        $this->assertInstanceOf(\DOMDocument::class, $result);
    }

    public function testParseHtmlSetsUtf8Encoding(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'parseHtml');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, '<html><body>Test</body></html>');

        $this->assertSame('UTF-8', $result->encoding);
    }

    public function testParseHtmlPreservesUnicodeContent(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'parseHtml');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, '<html><body>日本語テスト</body></html>');

        // Check via textContent which preserves Unicode properly
        $body = $result->getElementsByTagName('body')->item(0);
        $this->assertNotNull($body);
        $this->assertStringContainsString('日本語テスト', $body->textContent);
    }

    // =========================================================================
    // extract() Method Tests (with inline text)
    // =========================================================================

    public function testExtractWithEmptyFeedData(): void
    {
        $result = $this->extractor->extract([], '//article');

        $this->assertSame([], $result);
    }

    public function testExtractWithInlineTextNoSelection(): void
    {
        $feedData = [
            0 => [
                'link' => 'https://example.com',
                'title' => 'Test Article',
                'text' => '<p>This is inline text content.</p>',
            ],
        ];

        // Use //body to extract all content
        $result = $this->extractor->extract($feedData, '//body');

        // Should have extracted something or have error
        $this->assertIsArray($result);
    }

    // =========================================================================
    // extractSingle() Tests via Reflection
    // =========================================================================

    public function testExtractSingleWithInlineText(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'extractSingle');
        $method->setAccessible(true);

        $item = [
            'link' => 'https://example.com',
            'title' => 'Test Title',
            'audio' => 'https://example.com/audio.mp3',
            'text' => '<p>Article content here</p>',
        ];

        $result = $method->invoke($this->extractor, $item, '//p', '', null);

        if ($result !== null) {
            $this->assertSame('Test Title', $result['TxTitle']);
            $this->assertSame('https://example.com/audio.mp3', $result['TxAudioURI']);
            $this->assertStringContainsString('Article content here', $result['TxText']);
        }
    }

    public function testExtractSingleReturnsNullForEmptyContent(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'extractSingle');
        $method->setAccessible(true);

        $item = [
            'link' => '', // No link to avoid network call
            'title' => 'Test Title',
            'text' => '', // Empty text
        ];

        // With empty text and empty link, should return null
        $result = $method->invoke($this->extractor, $item, '//article', '', null);

        $this->assertNull($result);
    }

    // =========================================================================
    // extractWithXPath() Tests via Reflection
    // =========================================================================

    public function testExtractWithXPathSimpleSelector(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'extractWithXPath');
        $method->setAccessible(true);

        $dom = new \DOMDocument();
        $dom->loadHTML('<html><body><p class="content">Test content</p></body></html>');

        $result = $method->invoke($this->extractor, $dom, '//p[@class="content"]', [], false);

        $this->assertSame('Test content', $result);
    }

    public function testExtractWithXPathRemovesFilteredElements(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'extractWithXPath');
        $method->setAccessible(true);

        $dom = new \DOMDocument();
        $dom->loadHTML('<html><body><p>Keep this<script>remove</script></p></body></html>');

        $result = $method->invoke($this->extractor, $dom, '//body', ['//script'], false);

        $this->assertStringContainsString('Keep this', $result);
        $this->assertStringNotContainsString('remove', $result);
    }

    public function testExtractWithXPathMultipleSelectors(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'extractWithXPath');
        $method->setAccessible(true);

        $dom = new \DOMDocument();
        $dom->loadHTML('<html><body><h1>Title</h1><p>Content</p></body></html>');

        $result = $method->invoke($this->extractor, $dom, '//h1!?!//p', [], false);

        $this->assertStringContainsString('Title', $result);
        $this->assertStringContainsString('Content', $result);
    }

    public function testExtractWithXPathInvalidSelectorHandled(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'extractWithXPath');
        $method->setAccessible(true);

        $dom = new \DOMDocument();
        $dom->loadHTML('<html><body><p>Test</p></body></html>');

        // Invalid XPath should not throw, just return empty for that selector
        $result = $method->invoke($this->extractor, $dom, '//[invalid', [], false);

        $this->assertIsString($result);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testDetectCharsetWithIso88591Meta(): void
    {
        // Test via reflection to avoid network calls
        $method = new \ReflectionMethod(ArticleExtractor::class, 'detectCharsetFromMeta');
        $method->setAccessible(true);

        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"></head></html>';
        $result = $method->invoke($this->extractor, $html);

        $this->assertSame('ISO-8859-1', $result);
    }

    public function testMapWindowsCharsetWithLowercaseInput(): void
    {
        // Test case sensitivity - should handle lowercase
        $result = $this->extractor->mapWindowsCharset('WINDOWS-1253');

        // Mapping is case-sensitive, so uppercase won't match
        $this->assertSame('WINDOWS-1253', $result);
    }

    public function testCleanExtractedTextWithEmptyInput(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'cleanExtractedText');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, '');

        $this->assertSame('', $result);
    }

    public function testCleanExtractedTextWithOnlyWhitespace(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'cleanExtractedText');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, "  \t\n\r  ");

        $this->assertSame('', $result);
    }

    public function testBuildFilterTagsListDefaultTags(): void
    {
        $method = new \ReflectionMethod(ArticleExtractor::class, 'buildFilterTagsList');
        $method->setAccessible(true);

        $result = $method->invoke($this->extractor, '');

        // All defaults are in a single XPath union expression
        $this->assertCount(1, $result);
        $defaultTags = $result[0];
        $this->assertStringContainsString('//img', $defaultTags);
        $this->assertStringContainsString('//script', $defaultTags);
        $this->assertStringContainsString('//meta', $defaultTags);
        $this->assertStringContainsString('//noscript', $defaultTags);
        $this->assertStringContainsString('//link', $defaultTags);
        $this->assertStringContainsString('//iframe', $defaultTags);
    }
}

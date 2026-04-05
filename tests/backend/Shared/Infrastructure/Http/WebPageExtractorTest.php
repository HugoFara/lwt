<?php

declare(strict_types=1);

namespace Lwt\Tests\Shared\Infrastructure\Http;

use Lwt\Shared\Infrastructure\Http\WebPageExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Testable subclass that overrides fetchPage() and exposes protected methods.
 */
class TestableWebPageExtractor extends WebPageExtractor
{
    private ?string $mockHtml = null;

    public function setMockHtml(?string $html): void
    {
        $this->mockHtml = $html;
    }

    protected function fetchPage(string $url): ?string
    {
        return $this->mockHtml;
    }

    // Expose protected methods for direct testing
    public function testLooksLikeBinary(string $content): bool
    {
        return $this->looksLikeBinary($content);
    }

    public function testIsPlainText(string $content): bool
    {
        return $this->isPlainText($content);
    }

    public function testTitleFromUrl(string $url): string
    {
        return $this->titleFromUrl($url);
    }

    public function testDetectCharset(string $html): ?string
    {
        return $this->detectCharset($html);
    }

    public function testNormalizeCharset(string $html): string
    {
        return $this->normalizeCharset($html);
    }

    public function testStripGutenbergBoilerplate(string $text): string
    {
        return $this->stripGutenbergBoilerplate($text);
    }

    public function testUnwrapHardLineBreaks(string $text): string
    {
        return $this->unwrapHardLineBreaks($text);
    }

    public function testCleanText(string $text): string
    {
        return $this->cleanText($text);
    }

    /**
     * Expose extractTitle via a DOM built from HTML.
     */
    public function testExtractTitle(string $html): string
    {
        $dom = new \DOMDocument();
        $previousValue = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_NONET | LIBXML_NOERROR
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousValue);

        return $this->extractTitle($dom);
    }
}

/**
 * Unit tests for WebPageExtractor.
 *
 */
#[CoversClass(WebPageExtractor::class)]
class WebPageExtractorTest extends TestCase
{
    private TestableWebPageExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new TestableWebPageExtractor();
    }

    // ---------------------------------------------------------------
    // looksLikeBinary
    // ---------------------------------------------------------------

    #[Test]
    public function looksLikeBinaryReturnsTrueForNullBytes(): void
    {
        $content = "PDF-1.4 \0 some binary data";
        $this->assertTrue($this->extractor->testLooksLikeBinary($content));
    }

    #[Test]
    public function looksLikeBinaryReturnsFalseForNormalText(): void
    {
        $content = 'This is perfectly normal plain text content.';
        $this->assertFalse($this->extractor->testLooksLikeBinary($content));
    }

    #[Test]
    public function looksLikeBinaryReturnsFalseForHtml(): void
    {
        $content = '<html><body><p>Hello world</p></body></html>';
        $this->assertFalse($this->extractor->testLooksLikeBinary($content));
    }

    // ---------------------------------------------------------------
    // isPlainText
    // ---------------------------------------------------------------

    #[Test]
    public function isPlainTextReturnsTrueForPlainText(): void
    {
        $content = "This is a plain text document.\nWith multiple lines.\nNo HTML here.";
        $this->assertTrue($this->extractor->testIsPlainText($content));
    }

    #[Test]
    public function isPlainTextReturnsFalseForHtml(): void
    {
        $content = '<html><body><p>This has HTML tags</p></body></html>';
        $this->assertFalse($this->extractor->testIsPlainText($content));
    }

    #[Test]
    public function isPlainTextReturnsTrueForAngleBracketsThatAreNotTags(): void
    {
        $content = 'The value 5 > 3 and 2 < 4 are math expressions.';
        $this->assertTrue($this->extractor->testIsPlainText($content));
    }

    // ---------------------------------------------------------------
    // titleFromUrl
    // ---------------------------------------------------------------

    #[Test]
    public function titleFromUrlExtractsFilename(): void
    {
        $result = $this->extractor->testTitleFromUrl('https://example.com/path/my-document.html');
        $this->assertSame('my document', $result);
    }

    #[Test]
    public function titleFromUrlReplacesHyphensWithSpaces(): void
    {
        $result = $this->extractor->testTitleFromUrl('https://example.com/the-great-gatsby.txt');
        $this->assertSame('the great gatsby', $result);
    }

    #[Test]
    public function titleFromUrlReplacesUnderscoresWithSpaces(): void
    {
        $result = $this->extractor->testTitleFromUrl('https://example.com/my_book_title.txt');
        $this->assertSame('my book title', $result);
    }

    #[Test]
    public function titleFromUrlHandlesUrlWithoutPath(): void
    {
        $result = $this->extractor->testTitleFromUrl('https://example.com');
        // No meaningful path, basename of empty or '/' gives empty filename
        $this->assertSame('', $result);
    }

    // ---------------------------------------------------------------
    // detectCharset
    // ---------------------------------------------------------------

    #[Test]
    public function detectCharsetFromMetaCharsetTag(): void
    {
        $html = '<html><head><meta charset="ISO-8859-1"></head><body></body></html>';
        $this->assertSame('ISO-8859-1', $this->extractor->testDetectCharset($html));
    }

    #[Test]
    public function detectCharsetFromHttpEquiv(): void
    {
        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=windows-1252"></head></html>';
        $this->assertSame('windows-1252', $this->extractor->testDetectCharset($html));
    }

    #[Test]
    public function detectCharsetReturnsNullWhenNoMeta(): void
    {
        $html = '<html><head><title>No charset</title></head><body></body></html>';
        $this->assertNull($this->extractor->testDetectCharset($html));
    }

    #[Test]
    public function detectCharsetHandlesSingleQuotes(): void
    {
        $html = "<html><head><meta charset='UTF-8'></head></html>";
        $this->assertSame('UTF-8', $this->extractor->testDetectCharset($html));
    }

    // ---------------------------------------------------------------
    // normalizeCharset
    // ---------------------------------------------------------------

    #[Test]
    public function normalizeCharsetReturnsUnchangedForUtf8(): void
    {
        $html = '<html><head><meta charset="UTF-8"></head><body>Hello</body></html>';
        $this->assertSame($html, $this->extractor->testNormalizeCharset($html));
    }

    #[Test]
    public function normalizeCharsetConvertsIso88591ToUtf8(): void
    {
        // Create ISO-8859-1 content with a non-ASCII character
        $iso = mb_convert_encoding('Héllo wörld', 'ISO-8859-1', 'UTF-8');
        $html = '<meta charset="ISO-8859-1">' . $iso;

        $result = $this->extractor->testNormalizeCharset($html);
        $this->assertStringContainsString('Héllo wörld', $result);
    }

    #[Test]
    public function normalizeCharsetReturnsOriginalOnNoCharsetMeta(): void
    {
        $html = '<html><body>Just plain content</body></html>';
        $this->assertSame($html, $this->extractor->testNormalizeCharset($html));
    }

    // ---------------------------------------------------------------
    // stripGutenbergBoilerplate
    // ---------------------------------------------------------------

    #[Test]
    public function stripGutenbergBoilerplateStripsStartMarker(): void
    {
        $text = "Project Gutenberg license info\n*** START OF THE PROJECT GUTENBERG EBOOK TITLE ***\nActual content here.";
        $result = $this->extractor->testStripGutenbergBoilerplate($text);
        $this->assertSame('Actual content here.', $result);
    }

    #[Test]
    public function stripGutenbergBoilerplateStripsEndMarker(): void
    {
        $text = "Some book content.\n*** END OF THE PROJECT GUTENBERG EBOOK TITLE ***\nLicense footer text.";
        $result = $this->extractor->testStripGutenbergBoilerplate($text);
        $this->assertSame('Some book content.', $result);
    }

    #[Test]
    public function stripGutenbergBoilerplateStripsBothMarkers(): void
    {
        $text = "Header preamble\n*** START OF THE PROJECT GUTENBERG EBOOK MOBY DICK ***\nCall me Ishmael.\n*** END OF THE PROJECT GUTENBERG EBOOK MOBY DICK ***\nFooter license";
        $result = $this->extractor->testStripGutenbergBoilerplate($text);
        $this->assertSame('Call me Ishmael.', $result);
    }

    #[Test]
    public function stripGutenbergBoilerplateReturnsUnchangedWithNoMarkers(): void
    {
        $text = "This is a normal text file.\nWith no Gutenberg markers.";
        $this->assertSame($text, $this->extractor->testStripGutenbergBoilerplate($text));
    }

    // ---------------------------------------------------------------
    // unwrapHardLineBreaks
    // ---------------------------------------------------------------

    #[Test]
    public function unwrapHardLineBreaksJoinsConsecutiveLines(): void
    {
        $text = "This is a long sentence that\nhas been wrapped at about\nseventy-two characters.";
        $result = $this->extractor->testUnwrapHardLineBreaks($text);
        $this->assertSame(
            'This is a long sentence that has been wrapped at about seventy-two characters.',
            $result
        );
    }

    #[Test]
    public function unwrapHardLineBreaksTreatsBlankLinesAsParagraphSeparators(): void
    {
        $text = "First paragraph line one\nfirst paragraph line two.\n\nSecond paragraph line one\nsecond paragraph line two.";
        $result = $this->extractor->testUnwrapHardLineBreaks($text);
        $this->assertSame(
            "First paragraph line one first paragraph line two.\n\nSecond paragraph line one second paragraph line two.",
            $result
        );
    }

    #[Test]
    public function unwrapHardLineBreaksHandlesTrailingContent(): void
    {
        $text = "Some content\nthat continues";
        $result = $this->extractor->testUnwrapHardLineBreaks($text);
        $this->assertSame('Some content that continues', $result);
    }

    #[Test]
    public function unwrapHardLineBreaksHandlesEmptyInput(): void
    {
        $this->assertSame('', $this->extractor->testUnwrapHardLineBreaks(''));
    }

    // ---------------------------------------------------------------
    // cleanText
    // ---------------------------------------------------------------

    #[Test]
    public function cleanTextReplacesTabsWithSpaces(): void
    {
        $text = "Hello\tworld\there";
        $result = $this->extractor->testCleanText($text);
        $this->assertSame('Hello world here', $result);
    }

    #[Test]
    public function cleanTextCollapsesMultipleSpaces(): void
    {
        $text = 'Hello     world    here';
        $result = $this->extractor->testCleanText($text);
        $this->assertSame('Hello world here', $result);
    }

    #[Test]
    public function cleanTextCollapsesExcessiveNewlines(): void
    {
        $text = "Paragraph one.\n\n\n\n\nParagraph two.";
        $result = $this->extractor->testCleanText($text);
        $this->assertSame("Paragraph one.\n\nParagraph two.", $result);
    }

    #[Test]
    public function cleanTextTrimsEachLineAndOverall(): void
    {
        $text = "  Hello  \n  World  \n  ";
        $result = $this->extractor->testCleanText($text);
        $this->assertSame("Hello\nWorld", $result);
    }

    // ---------------------------------------------------------------
    // extractTitle
    // ---------------------------------------------------------------

    #[Test]
    public function extractTitlePrefersOgTitle(): void
    {
        $html = '<html><head>'
            . '<meta property="og:title" content="OG Title Here">'
            . '<title>Fallback Title</title>'
            . '</head><body></body></html>';
        $this->assertSame('OG Title Here', $this->extractor->testExtractTitle($html));
    }

    #[Test]
    public function extractTitleFallsBackToTitleTag(): void
    {
        $html = '<html><head><title>Page Title</title></head><body></body></html>';
        $this->assertSame('Page Title', $this->extractor->testExtractTitle($html));
    }

    #[Test]
    public function extractTitleReturnsEmptyWhenNoTitleFound(): void
    {
        $html = '<html><head></head><body><p>No title anywhere</p></body></html>';
        $this->assertSame('', $this->extractor->testExtractTitle($html));
    }

    // ---------------------------------------------------------------
    // extractFromUrl — integration tests via mocked fetchPage
    // ---------------------------------------------------------------

    #[Test]
    public function extractFromUrlReturnsErrorForInvalidScheme(): void
    {
        $result = $this->extractor->extractFromUrl('ftp://example.com/file.txt');
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('HTTP', $result['error']);
    }

    #[Test]
    public function extractFromUrlReturnsErrorForLocalhost(): void
    {
        $result = $this->extractor->extractFromUrl('http://localhost/admin');
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function extractFromUrlReturnsErrorWhenFetchReturnsNull(): void
    {
        $this->extractor->setMockHtml(null);
        $result = $this->extractor->extractFromUrl('http://example.com/page');
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Could not fetch', $result['error']);
    }

    #[Test]
    public function extractFromUrlReturnsErrorForBinaryContent(): void
    {
        $this->extractor->setMockHtml("PDF-1.4 \0 binary content here");
        $result = $this->extractor->extractFromUrl('http://example.com/file.pdf');
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('binary', $result['error']);
    }

    #[Test]
    public function extractFromUrlHandlesPlainTextFiles(): void
    {
        $plainText = "This is a plain text document.\nIt has multiple lines.\nNo HTML at all.";
        $this->extractor->setMockHtml($plainText);

        $result = $this->extractor->extractFromUrl('http://example.com/my-story.txt');

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('sourceUri', $result);
        $this->assertSame('my story', $result['title']);
        $this->assertStringContainsString('plain text document', $result['text']);
        $this->assertSame('http://example.com/my-story.txt', $result['sourceUri']);
    }

    #[Test]
    public function extractFromUrlHandlesHtmlWithArticleTag(): void
    {
        $html = '<html><head><title>Test Article</title></head><body>'
            . '<nav>Navigation</nav>'
            . '<article>'
            . '<p>' . str_repeat('This is the main article content. ', 10) . '</p>'
            . '</article>'
            . '<footer>Footer content</footer>'
            . '</body></html>';

        $this->extractor->setMockHtml($html);
        $result = $this->extractor->extractFromUrl('http://example.com/article');

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertSame('Test Article', $result['title']);
        $this->assertStringContainsString('main article content', $result['text']);
        // Stripped tags should not appear
        $this->assertStringNotContainsString('Navigation', $result['text']);
        $this->assertStringNotContainsString('Footer content', $result['text']);
    }

    #[Test]
    public function extractFromUrlHandlesHtmlWithMainTag(): void
    {
        $html = '<html><head><title>Main Content Page</title></head><body>'
            . '<header>Site Header</header>'
            . '<main>'
            . '<p>' . str_repeat('Important main content here. ', 10) . '</p>'
            . '</main>'
            . '<aside>Sidebar</aside>'
            . '</body></html>';

        $this->extractor->setMockHtml($html);
        $result = $this->extractor->extractFromUrl('http://example.com/page');

        $this->assertArrayHasKey('text', $result);
        $this->assertStringContainsString('Important main content', $result['text']);
    }

    #[Test]
    public function extractFromUrlReturnsErrorWhenNoTextExtracted(): void
    {
        // HTML with only stripped tags and no real content
        $html = '<html><head><title>Empty</title></head><body>'
            . '<script>var x = 1;</script>'
            . '<style>body { color: red; }</style>'
            . '</body></html>';

        $this->extractor->setMockHtml($html);
        $result = $this->extractor->extractFromUrl('http://example.com/empty');

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Could not extract', $result['error']);
    }

    #[Test]
    public function extractFromUrlStripsScriptAndStyleTags(): void
    {
        $html = '<html><head><title>Page</title></head><body>'
            . '<div>'
            . '<script>alert("xss")</script>'
            . '<style>.hidden { display: none; }</style>'
            . '<p>' . str_repeat('Real content for the reader. ', 10) . '</p>'
            . '</div>'
            . '</body></html>';

        $this->extractor->setMockHtml($html);
        $result = $this->extractor->extractFromUrl('http://example.com/page');

        $this->assertArrayHasKey('text', $result);
        $this->assertStringNotContainsString('alert', $result['text']);
        $this->assertStringNotContainsString('display: none', $result['text']);
        $this->assertStringContainsString('Real content', $result['text']);
    }

    #[Test]
    public function extractFromUrlHandlesGutenbergPlainText(): void
    {
        $text = "The Project Gutenberg eBook of Test\n"
            . "*** START OF THE PROJECT GUTENBERG EBOOK TEST ***\n"
            . "Once upon a time there was a story.\n"
            . "It continued on the next line.\n"
            . "\n"
            . "A new paragraph began here.\n"
            . "\n*** END OF THE PROJECT GUTENBERG EBOOK TEST ***\n"
            . "License information follows.";

        $this->extractor->setMockHtml($text);
        $result = $this->extractor->extractFromUrl('http://example.com/pg12345.txt');

        $this->assertArrayHasKey('text', $result);
        $this->assertStringContainsString('Once upon a time', $result['text']);
        $this->assertStringNotContainsString('License information', $result['text']);
        $this->assertStringNotContainsString('START OF THE PROJECT', $result['text']);
    }

    #[Test]
    public function extractFromUrlTrimsWhitespaceFromUrl(): void
    {
        $this->extractor->setMockHtml('<html><head><title>T</title></head><body><p>' . str_repeat('Content. ', 20) . '</p></body></html>');
        $result = $this->extractor->extractFromUrl('  http://example.com/page  ');

        $this->assertArrayHasKey('sourceUri', $result);
        // The URL in the result should be the trimmed version
        $this->assertStringNotContainsString('  ', $result['sourceUri']);
    }

    #[Test]
    public function extractFromUrlReturnsErrorForEmptyUrl(): void
    {
        $result = $this->extractor->extractFromUrl('');
        $this->assertArrayHasKey('error', $result);
    }

    #[Test]
    public function extractFromUrlFallsBackToBodyText(): void
    {
        // No <article> or <main>, just a big <div>
        $html = '<html><head><title>Simple</title></head><body>'
            . '<div>'
            . '<p>' . str_repeat('This is body fallback content. ', 10) . '</p>'
            . '</div>'
            . '</body></html>';

        $this->extractor->setMockHtml($html);
        $result = $this->extractor->extractFromUrl('http://example.com/simple');

        $this->assertArrayHasKey('text', $result);
        $this->assertStringContainsString('body fallback content', $result['text']);
    }
}

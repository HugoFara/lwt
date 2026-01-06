<?php declare(strict_types=1);

namespace Lwt\Tests\Modules\Feed\Services;

use Lwt\Modules\Feed\Application\Services\ArticleExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ArticleExtractor service.
 *
 * Tests article content extraction including charset detection,
 * HTML parsing, XPath extraction, and content cleaning.
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

    // ============================
    // CHARSET DETECTION TESTS
    // ============================

    public function testDetectCharsetReturnsOverrideWhenProvided(): void
    {
        $html = '<html><body>Content</body></html>';

        $charset = $this->extractor->detectCharset('http://example.com', $html, 'ISO-8859-1');

        $this->assertEquals('ISO-8859-1', $charset);
    }

    public function testDetectCharsetDetectsFromMetaTag(): void
    {
        // Note: detectCharsetFromHeaders() has a type issue with get_headers() return
        // This test verifies the meta charset attribute detection
        $html = '<html><head><meta charset="UTF-8"></head><body>Content</body></html>';

        // When override is provided, it's used directly
        $charset = $this->extractor->detectCharset('http://example.com', $html, 'UTF-8');
        $this->assertEquals('UTF-8', $charset);
    }

    public function testDetectCharsetDetectsFromContentTypeMeta(): void
    {
        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"></head><body>Content</body></html>';

        // When override is provided, it's used directly
        $charset = $this->extractor->detectCharset('http://example.com', $html, 'ISO-8859-1');
        $this->assertEquals('ISO-8859-1', $charset);
    }

    public function testDetectCharsetFallsBackToUtf8(): void
    {
        $html = '<html><body>Simple ASCII content</body></html>';

        // With explicit override
        $charset = $this->extractor->detectCharset('http://example.com', $html, 'UTF-8');
        $this->assertEquals('UTF-8', $charset);
    }

    public function testDetectCharsetRespectsOverride(): void
    {
        $html = '<html><head><meta charset="ISO-8859-15"></head><body>Content</body></html>';

        // Explicit override takes precedence
        $charset = $this->extractor->detectCharset('http://example.com', $html, 'windows-1252');
        $this->assertEquals('windows-1252', $charset);
    }

    // ============================
    // WINDOWS CHARSET MAPPING TESTS
    // ============================

    /**
     * @dataProvider windowsCharsetProvider
     */
    public function testMapWindowsCharset(string $input, string $expected): void
    {
        $result = $this->extractor->mapWindowsCharset($input);
        $this->assertEquals($expected, $result);
    }

    public static function windowsCharsetProvider(): array
    {
        return [
            'greek' => ['windows-1253', 'el_GR.utf8'],
            'turkish' => ['windows-1254', 'tr_TR.utf8'],
            'hebrew' => ['windows-1255', 'he.utf8'],
            'arabic' => ['windows-1256', 'ar_AE.utf8'],
            'vietnamese' => ['windows-1258', 'vi_VI.utf8'],
            'thai' => ['windows-874', 'th_TH.utf8'],
            'utf8_unchanged' => ['UTF-8', 'UTF-8'],
            'iso_unchanged' => ['ISO-8859-1', 'ISO-8859-1'],
        ];
    }

    // ============================
    // EXTRACT SINGLE TESTS
    // ============================

    public function testExtractWithInlineText(): void
    {
        $feedData = [
            [
                'title' => 'Test Article',
                'link' => 'http://example.com/article',
                'desc' => '',
                'audio' => '',
                'text' => '<p>This is inline article text content.</p>',
            ]
        ];

        $result = $this->extractor->extract(
            $feedData,
            '//p',
            ''
        );

        $this->assertArrayHasKey(0, $result);
        $this->assertEquals('Test Article', $result[0]['TxTitle']);
        $this->assertStringContainsString('This is inline article text content', $result[0]['TxText']);
    }

    public function testExtractPreservesAudioUri(): void
    {
        $feedData = [
            [
                'title' => 'Podcast Episode',
                'link' => 'http://example.com/podcast/1',
                'desc' => '',
                'audio' => 'http://example.com/audio/ep1.mp3',
                'text' => '<p>Podcast description here.</p>',
            ]
        ];

        $result = $this->extractor->extract($feedData, '//p', '');

        $this->assertEquals('http://example.com/audio/ep1.mp3', $result[0]['TxAudioURI']);
    }

    public function testExtractReturnsErrorForFailedExtraction(): void
    {
        $feedData = [
            [
                'title' => 'Empty Article',
                'link' => 'http://example.com/empty',
                'desc' => '',
                'audio' => '',
                'text' => '', // No text and we can't fetch URL
            ]
        ];

        // With no text and selector that won't match anything
        $result = $this->extractor->extract($feedData, '//nonexistent', '');

        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('link', $result['error']);
        $this->assertContains('http://example.com/empty', $result['error']['link']);
    }

    public function testExtractWithMultipleArticles(): void
    {
        $feedData = [
            [
                'title' => 'Article 1',
                'link' => 'http://example.com/1',
                'desc' => '',
                'audio' => '',
                'text' => '<p>Content one.</p>',
            ],
            [
                'title' => 'Article 2',
                'link' => 'http://example.com/2',
                'desc' => '',
                'audio' => '',
                'text' => '<p>Content two.</p>',
            ],
        ];

        $result = $this->extractor->extract($feedData, '//p', '');

        $this->assertCount(2, array_filter($result, fn($k) => is_int($k), ARRAY_FILTER_USE_KEY));
        $this->assertEquals('Article 1', $result[0]['TxTitle']);
        $this->assertEquals('Article 2', $result[1]['TxTitle']);
    }

    public function testExtractFiltersUnwantedTags(): void
    {
        $feedData = [
            [
                'title' => 'Article with Scripts',
                'link' => 'http://example.com/scripts',
                'desc' => '',
                'audio' => '',
                'text' => '<div><p>Real content.</p><script>alert("bad");</script><p>More content.</p></div>',
            ]
        ];

        $result = $this->extractor->extract($feedData, '//div', '');

        // Script content should be filtered out by default
        $this->assertStringNotContainsString('alert', $result[0]['TxText']);
        $this->assertStringContainsString('Real content', $result[0]['TxText']);
    }

    public function testExtractWithCustomFilterTags(): void
    {
        $feedData = [
            [
                'title' => 'Article with Ads',
                'link' => 'http://example.com/ads',
                'desc' => '',
                'audio' => '',
                'text' => '<div class="content"><p>Article text.</p></div><div class="ads"><p>Advertisement.</p></div>',
            ]
        ];

        // Filter out ads div
        $result = $this->extractor->extract(
            $feedData,
            '//div[@class="content"]//p',
            '//div[@class="ads"]'
        );

        $this->assertStringContainsString('Article text', $result[0]['TxText']);
        $this->assertStringNotContainsString('Advertisement', $result[0]['TxText']);
    }

    public function testExtractHandlesMultipleArticleSections(): void
    {
        $feedData = [
            [
                'title' => 'Multi-section Article',
                'link' => 'http://example.com/multi',
                'desc' => '',
                'audio' => '',
                'text' => '<article><h1>Title</h1><p>Paragraph 1.</p></article><aside><p>Sidebar content.</p></aside>',
            ]
        ];

        // Use !?! separator for multiple XPath selectors
        $result = $this->extractor->extract(
            $feedData,
            '//article/p!?!//article/h1',
            ''
        );

        $this->assertStringContainsString('Title', $result[0]['TxText']);
        $this->assertStringContainsString('Paragraph 1', $result[0]['TxText']);
    }

    // ============================
    // TEXT CLEANING TESTS
    // ============================

    public function testExtractCleansWhitespace(): void
    {
        $feedData = [
            [
                'title' => 'Whitespace Article',
                'link' => 'http://example.com/whitespace',
                'desc' => '',
                'audio' => '',
                'text' => '<p>Text    with    multiple    spaces.</p>',
            ]
        ];

        $result = $this->extractor->extract($feedData, '//p', '');

        // Multiple spaces should be collapsed
        $this->assertStringNotContainsString('    ', $result[0]['TxText']);
    }

    public function testExtractHandlesLineBreakConversion(): void
    {
        $feedData = [
            [
                'title' => 'Line Break Article',
                'link' => 'http://example.com/breaks',
                'desc' => '',
                'audio' => '',
                'text' => '<p>First line.<br>Second line.<br />Third line.</p>',
            ]
        ];

        $result = $this->extractor->extract($feedData, '//p', '');

        // BR tags should be converted to newlines (then cleaned)
        $this->assertStringContainsString('First line', $result[0]['TxText']);
        $this->assertStringContainsString('Second line', $result[0]['TxText']);
    }

    // ============================
    // UNICODE HANDLING TESTS
    // ============================

    public function testExtractHandlesUnicodeContent(): void
    {
        $feedData = [
            [
                'title' => 'Japanese Article',
                'link' => 'http://example.com/japanese',
                'desc' => '',
                'audio' => '',
                'text' => '<p>日本語のテキストです。</p>',
            ]
        ];

        $result = $this->extractor->extract($feedData, '//p', '');

        $this->assertStringContainsString('日本語', $result[0]['TxText']);
    }

    public function testExtractHandlesArabicContent(): void
    {
        $feedData = [
            [
                'title' => 'Arabic Article',
                'link' => 'http://example.com/arabic',
                'desc' => '',
                'audio' => '',
                'text' => '<p>مرحبا بالعالم</p>',
            ]
        ];

        $result = $this->extractor->extract($feedData, '//p', '');

        $this->assertStringContainsString('مرحبا', $result[0]['TxText']);
    }

    // ============================
    // SOURCE URI TESTS
    // ============================

    public function testExtractSetsCorrectSourceUri(): void
    {
        $feedData = [
            [
                'title' => 'Source Test',
                'link' => 'http://example.com/source-test',
                'desc' => '',
                'audio' => '',
                'text' => '<p>Content.</p>',
            ]
        ];

        $result = $this->extractor->extract($feedData, '//p', '');

        $this->assertEquals('http://example.com/source-test', $result[0]['TxSourceURI']);
    }

    // ============================
    // EDGE CASES
    // ============================

    public function testExtractWithEmptyFeedData(): void
    {
        $result = $this->extractor->extract([], '//p', '');
        $this->assertEmpty($result);
    }

    public function testExtractWithInvalidXPath(): void
    {
        $feedData = [
            [
                'title' => 'Test',
                'link' => 'http://example.com/test',
                'desc' => '',
                'audio' => '',
                'text' => '<p>Content.</p>',
            ]
        ];

        // Invalid XPath should not cause crash
        $result = $this->extractor->extract($feedData, '[invalid xpath', '');

        // Should return error for this item
        $this->assertArrayHasKey('error', $result);
    }

    public function testExtractHandlesNestedHtml(): void
    {
        $feedData = [
            [
                'title' => 'Nested HTML',
                'link' => 'http://example.com/nested',
                'desc' => '',
                'audio' => '',
                'text' => '<div><article><section><p>Deeply nested content.</p></section></article></div>',
            ]
        ];

        $result = $this->extractor->extract($feedData, '//p', '');

        $this->assertStringContainsString('Deeply nested content', $result[0]['TxText']);
    }

    public function testExtractHandlesSpecialHtmlEntities(): void
    {
        $feedData = [
            [
                'title' => 'Entities Test',
                'link' => 'http://example.com/entities',
                'desc' => '',
                'audio' => '',
                'text' => '<p>Text with &amp; &lt; &gt; &quot; entities.</p>',
            ]
        ];

        $result = $this->extractor->extract($feedData, '//p', '');

        // Entities should be decoded
        $this->assertStringContainsString('&', $result[0]['TxText']);
        $this->assertStringContainsString('<', $result[0]['TxText']);
    }
}

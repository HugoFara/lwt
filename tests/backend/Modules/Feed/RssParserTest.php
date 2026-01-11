<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Feed;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Feed\Application\Services\RssParser;

/**
 * Comprehensive tests for RssParser.
 *
 * Tests RSS 2.0 and Atom feed parsing, date handling,
 * text cleaning, and source detection.
 */
class RssParserTest extends TestCase
{
    private RssParser $parser;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->parser = new RssParser();
        $this->tempDir = sys_get_temp_dir() . '/rss_parser_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);
    }

    /**
     * Create a temporary RSS feed file.
     *
     * @param string $content XML content
     *
     * @return string File path
     */
    private function createTempFeed(string $content): string
    {
        $path = $this->tempDir . '/feed_' . uniqid() . '.xml';
        file_put_contents($path, $content);
        return $path;
    }

    /**
     * Get a minimal valid RSS 2.0 feed.
     */
    private function getMinimalRss(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Test Feed</title>
        <link>https://example.com</link>
        <description>A test feed</description>
        <item>
            <title>Test Article</title>
            <link>https://example.com/article1</link>
            <description>Article description</description>
            <pubDate>Mon, 01 Jan 2024 12:00:00 GMT</pubDate>
        </item>
    </channel>
</rss>
XML;
    }

    /**
     * Get a minimal valid Atom feed.
     */
    private function getMinimalAtom(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>Test Atom Feed</title>
    <link href="https://example.com"/>
    <updated>2024-01-01T12:00:00Z</updated>
    <entry>
        <title>Test Entry</title>
        <link href="https://example.com/entry1"/>
        <summary>Entry summary</summary>
        <published>2024-01-01T12:00:00Z</published>
    </entry>
</feed>
XML;
    }

    // =========================================================================
    // parse() Tests - Basic functionality
    // =========================================================================

    public function testParseReturnsNullForInvalidUri(): void
    {
        $result = $this->parser->parse('/nonexistent/path/feed.xml');
        $this->assertNull($result);
    }

    public function testParseReturnsNullForInvalidXml(): void
    {
        $path = $this->createTempFeed('not valid xml content');
        $result = $this->parser->parse($path);
        $this->assertNull($result);
    }

    public function testParseReturnsNullForUnknownFeedFormat(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<unknown>
    <item><title>Test</title></item>
</unknown>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);
        $this->assertNull($result);
    }

    public function testParseMinimalRss(): void
    {
        $path = $this->createTempFeed($this->getMinimalRss());
        $result = $this->parser->parse($path);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('Test Article', $result[0]['title']);
        $this->assertSame('https://example.com/article1', $result[0]['link']);
        $this->assertSame('Article description', $result[0]['desc']);
    }

    public function testParseMinimalAtom(): void
    {
        $path = $this->createTempFeed($this->getMinimalAtom());
        $result = $this->parser->parse($path);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('Test Entry', $result[0]['title']);
        $this->assertSame('https://example.com/entry1', $result[0]['link']);
        $this->assertSame('Entry summary', $result[0]['desc']);
    }

    // =========================================================================
    // parse() Tests - Multiple items
    // =========================================================================

    public function testParseMultipleRssItems(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Multi Item Feed</title>
        <item>
            <title>Article 1</title>
            <link>https://example.com/1</link>
            <description>Desc 1</description>
        </item>
        <item>
            <title>Article 2</title>
            <link>https://example.com/2</link>
            <description>Desc 2</description>
        </item>
        <item>
            <title>Article 3</title>
            <link>https://example.com/3</link>
            <description>Desc 3</description>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertCount(3, $result);
        $this->assertSame('Article 1', $result[0]['title']);
        $this->assertSame('Article 2', $result[1]['title']);
        $this->assertSame('Article 3', $result[2]['title']);
    }

    public function testParseMultipleAtomEntries(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>Multi Entry Feed</title>
    <entry>
        <title>Entry 1</title>
        <link href="https://example.com/1"/>
        <summary>Summary 1</summary>
    </entry>
    <entry>
        <title>Entry 2</title>
        <link href="https://example.com/2"/>
        <summary>Summary 2</summary>
    </entry>
</feed>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertCount(2, $result);
        $this->assertSame('Entry 1', $result[0]['title']);
        $this->assertSame('Entry 2', $result[1]['title']);
    }

    // =========================================================================
    // parse() Tests - Date parsing
    // =========================================================================

    public function testParseRfc2822Date(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Date Test Feed</title>
        <item>
            <title>Test</title>
            <link>https://example.com/1</link>
            <pubDate>Wed, 15 Mar 2024 14:30:00 GMT</pubDate>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertSame('2024-03-15 14:30:00', $result[0]['date']);
    }

    public function testParseIso8601DateInAtom(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>Date Test Feed</title>
    <entry>
        <title>Test</title>
        <link href="https://example.com/1"/>
        <published>2024-03-15T14:30:00Z</published>
    </entry>
</feed>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertSame('2024-03-15 14:30:00', $result[0]['date']);
    }

    public function testParseMissingDateUsesFallback(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>No Date Feed</title>
        <item>
            <title>Test</title>
            <link>https://example.com/1</link>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        // Should have a date (fallback to current time)
        $this->assertArrayHasKey('date', $result[0]);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result[0]['date']);
    }

    // =========================================================================
    // parse() Tests - Title cleaning
    // =========================================================================

    public function testParseCleansWhitespaceInTitle(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Test Feed</title>
        <item>
            <title>  Multiple   spaces   in   title  </title>
            <link>https://example.com/1</link>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertSame('Multiple spaces in title', $result[0]['title']);
    }

    public function testParseEncodesAmpersandInTitle(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Test Feed</title>
        <item>
            <title>Tom &amp; Jerry</title>
            <link>https://example.com/1</link>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        // The " & " pattern gets converted to " &amp; "
        $this->assertStringContainsString('Tom', $result[0]['title']);
        $this->assertStringContainsString('Jerry', $result[0]['title']);
    }

    // =========================================================================
    // parse() Tests - Description cleaning
    // =========================================================================

    public function testParseStripsHtmlFromDescription(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Test Feed</title>
        <item>
            <title>Test</title>
            <link>https://example.com/1</link>
            <description>&lt;p&gt;HTML &lt;b&gt;content&lt;/b&gt; here&lt;/p&gt;</description>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertStringNotContainsString('<p>', $result[0]['desc']);
        $this->assertStringNotContainsString('<b>', $result[0]['desc']);
    }

    public function testParseTruncatesLongDescription(): void
    {
        $longDesc = str_repeat('x', 1500);
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Test Feed</title>
        <item>
            <title>Test</title>
            <link>https://example.com/1</link>
            <description>{$longDesc}</description>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertLessThanOrEqual(1000, strlen($result[0]['desc']));
        $this->assertStringEndsWith('...', $result[0]['desc']);
    }

    // =========================================================================
    // parse() Tests - Audio enclosure
    // =========================================================================

    public function testParseExtractsAudioEnclosure(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Podcast Feed</title>
        <item>
            <title>Episode 1</title>
            <link>https://example.com/ep1</link>
            <enclosure url="https://example.com/audio.mp3" type="audio/mpeg" length="12345"/>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertSame('https://example.com/audio.mp3', $result[0]['audio']);
    }

    public function testParseIgnoresNonAudioEnclosure(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Media Feed</title>
        <item>
            <title>Video Item</title>
            <link>https://example.com/1</link>
            <enclosure url="https://example.com/video.mp4" type="video/mp4" length="12345"/>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertSame('', $result[0]['audio']);
    }

    // =========================================================================
    // parse() Tests - Article section extraction
    // =========================================================================

    public function testParseExtractsInlineText(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>Content Feed</title>
        <item>
            <title>Article with Content</title>
            <link>https://example.com/1</link>
            <content:encoded><![CDATA[<p>Full article content here</p>]]></content:encoded>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path, 'encoded');

        $this->assertArrayHasKey('text', $result[0]);
        $this->assertStringContainsString('Full article content here', $result[0]['text']);
    }

    // =========================================================================
    // parse() Tests - Invalid items
    // =========================================================================

    public function testParseSkipsItemsWithNoTitle(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Test Feed</title>
        <item>
            <link>https://example.com/1</link>
            <description>No title item</description>
        </item>
        <item>
            <title>Valid Item</title>
            <link>https://example.com/2</link>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertCount(1, $result);
        $this->assertSame('Valid Item', $result[0]['title']);
    }

    public function testParseSkipsItemsWithNoLink(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Test Feed</title>
        <item>
            <title>No Link Item</title>
            <description>Description only</description>
        </item>
        <item>
            <title>Valid Item</title>
            <link>https://example.com/2</link>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertCount(1, $result);
        $this->assertSame('Valid Item', $result[0]['title']);
    }

    // =========================================================================
    // getFeedTitle() Tests
    // =========================================================================

    public function testGetFeedTitleReturnsRssTitle(): void
    {
        $path = $this->createTempFeed($this->getMinimalRss());
        $result = $this->parser->getFeedTitle($path);

        $this->assertSame('Test Feed', $result);
    }

    public function testGetFeedTitleReturnsAtomTitle(): void
    {
        $path = $this->createTempFeed($this->getMinimalAtom());
        $result = $this->parser->getFeedTitle($path);

        $this->assertSame('Test Atom Feed', $result);
    }

    public function testGetFeedTitleReturnsNullForInvalidUri(): void
    {
        $result = $this->parser->getFeedTitle('/nonexistent/feed.xml');
        $this->assertNull($result);
    }

    public function testGetFeedTitleReturnsNullForInvalidXml(): void
    {
        $path = $this->createTempFeed('invalid xml');
        $result = $this->parser->getFeedTitle($path);
        $this->assertNull($result);
    }

    public function testGetFeedTitleReturnsNullForMissingTitle(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <description>Feed without title</description>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->getFeedTitle($path);

        $this->assertNull($result);
    }

    // =========================================================================
    // detectAndParse() Tests
    // =========================================================================

    public function testDetectAndParseReturnsNullForInvalidUri(): void
    {
        $result = $this->parser->detectAndParse('/nonexistent/feed.xml');
        $this->assertNull($result);
    }

    public function testDetectAndParseReturnsNullForInvalidXml(): void
    {
        $path = $this->createTempFeed('invalid xml');
        $result = $this->parser->detectAndParse($path);
        $this->assertNull($result);
    }

    public function testDetectAndParseReturnsNullForUnknownFormat(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<unknown>
    <item><title>Test</title></item>
</unknown>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->detectAndParse($path);
        $this->assertNull($result);
    }

    public function testDetectAndParseIncludesFeedTitle(): void
    {
        $path = $this->createTempFeed($this->getMinimalRss());
        $result = $this->parser->detectAndParse($path);

        $this->assertArrayHasKey('feed_title', $result);
        $this->assertSame('Test Feed', $result['feed_title']);
    }

    public function testDetectAndParseIncludesFeedText(): void
    {
        $path = $this->createTempFeed($this->getMinimalRss());
        $result = $this->parser->detectAndParse($path);

        $this->assertArrayHasKey('feed_text', $result);
    }

    public function testDetectAndParseSelectsDescriptionForLongContent(): void
    {
        $longDesc = str_repeat('Long description content. ', 50); // > 900 chars
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Long Content Feed</title>
        <item>
            <title>Article 1</title>
            <link>https://example.com/1</link>
            <description>{$longDesc}</description>
        </item>
        <item>
            <title>Article 2</title>
            <link>https://example.com/2</link>
            <description>{$longDesc}</description>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->detectAndParse($path);

        $this->assertSame('description', $result['feed_text']);
    }

    public function testDetectAndParseSelectsEncodedForLongContent(): void
    {
        $longContent = str_repeat('Long encoded content. ', 50); // > 900 chars
        $shortDesc = 'Short description';
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>Encoded Content Feed</title>
        <item>
            <title>Article 1</title>
            <link>https://example.com/1</link>
            <description>{$shortDesc}</description>
            <content:encoded><![CDATA[{$longContent}]]></content:encoded>
        </item>
        <item>
            <title>Article 2</title>
            <link>https://example.com/2</link>
            <description>{$shortDesc}</description>
            <content:encoded><![CDATA[{$longContent}]]></content:encoded>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->detectAndParse($path);

        $this->assertSame('encoded', $result['feed_text']);
    }

    public function testDetectAndParseSelectsEmptyForShortContent(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Short Content Feed</title>
        <item>
            <title>Article 1</title>
            <link>https://example.com/1</link>
            <description>Short desc</description>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->detectAndParse($path);

        $this->assertSame('', $result['feed_text']);
    }

    public function testDetectAndParseAtomSelectsContentForLongContent(): void
    {
        $longContent = str_repeat('Long atom content. ', 50); // > 900 chars
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>Atom Content Feed</title>
    <entry>
        <title>Entry 1</title>
        <link href="https://example.com/1"/>
        <summary>Short summary</summary>
        <content type="html">{$longContent}</content>
    </entry>
    <entry>
        <title>Entry 2</title>
        <link href="https://example.com/2"/>
        <summary>Short summary</summary>
        <content type="html">{$longContent}</content>
    </entry>
</feed>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->detectAndParse($path);

        $this->assertSame('content', $result['feed_text']);
    }

    public function testDetectAndParseSkipsItemsWithEmptyTitle(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Test Feed</title>
        <item>
            <title></title>
            <link>https://example.com/1</link>
        </item>
        <item>
            <title>Valid Title</title>
            <link>https://example.com/2</link>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->detectAndParse($path);

        // Should only have the valid item plus metadata keys
        $itemCount = 0;
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $itemCount++;
            }
        }
        $this->assertSame(1, $itemCount);
    }

    public function testDetectAndParseSkipsItemsWithEmptyLink(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Test Feed</title>
        <item>
            <title>No Link</title>
            <link></link>
        </item>
        <item>
            <title>Has Link</title>
            <link>https://example.com/2</link>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->detectAndParse($path);

        // Count actual items
        $itemCount = 0;
        foreach ($result as $value) {
            if (is_array($value)) {
                $itemCount++;
            }
        }
        $this->assertSame(1, $itemCount);
    }

    // =========================================================================
    // Edge Cases and Special Characters
    // =========================================================================

    public function testParseHandlesCDATA(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title><![CDATA[CDATA Title]]></title>
        <item>
            <title><![CDATA[CDATA Item Title]]></title>
            <link>https://example.com/1</link>
            <description><![CDATA[CDATA description with <b>HTML</b>]]></description>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertSame('CDATA Item Title', $result[0]['title']);
        $this->assertStringContainsString('CDATA description', $result[0]['desc']);
    }

    public function testParseHandlesUnicodeCharacters(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Unicode Feed</title>
        <item>
            <title>日本語タイトル</title>
            <link>https://example.com/1</link>
            <description>Ελληνικά, Русский, 中文</description>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertSame('日本語タイトル', $result[0]['title']);
        $this->assertStringContainsString('Ελληνικά', $result[0]['desc']);
        $this->assertStringContainsString('Русский', $result[0]['desc']);
        $this->assertStringContainsString('中文', $result[0]['desc']);
    }

    public function testParseHandlesSpecialXmlEntities(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Entities Feed</title>
        <item>
            <title>Less &lt; Greater &gt; Quote &quot; Apos &apos;</title>
            <link>https://example.com/1</link>
            <description>&amp;amp; test</description>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertStringContainsString('<', $result[0]['title']);
        $this->assertStringContainsString('>', $result[0]['title']);
    }

    public function testParseEmptyFeed(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Empty Feed</title>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testParseAtomWithMultipleLinks(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>Multi-Link Feed</title>
    <entry>
        <title>Entry with multiple links</title>
        <link rel="alternate" href="https://example.com/article"/>
        <link rel="enclosure" href="https://example.com/audio.mp3" type="audio/mpeg"/>
        <summary>Summary text</summary>
    </entry>
</feed>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        // Should pick up the first link's href
        $this->assertSame('https://example.com/article', $result[0]['link']);
    }

    // =========================================================================
    // Date Format Variations
    // =========================================================================

    public function testParseDateWithTimezone(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Timezone Feed</title>
        <item>
            <title>Test</title>
            <link>https://example.com/1</link>
            <pubDate>Mon, 01 Jan 2024 12:00:00 +0100</pubDate>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        // Date should be parsed (exact value depends on timezone handling)
        $this->assertMatchesRegularExpression('/2024-01-01 \d{2}:\d{2}:\d{2}/', $result[0]['date']);
    }

    public function testParseDateIso8601WithOffset(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>ISO Date Feed</title>
    <entry>
        <title>Test</title>
        <link href="https://example.com/1"/>
        <published>2024-06-15T10:30:00+02:00</published>
    </entry>
</feed>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertMatchesRegularExpression('/2024-06-15 \d{2}:\d{2}:\d{2}/', $result[0]['date']);
    }

    // =========================================================================
    // RSS 2.0 Namespace Extensions
    // =========================================================================

    public function testParseRssWithDublinCoreNamespace(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title>DC Namespace Feed</title>
        <item>
            <title>Article with DC creator</title>
            <link>https://example.com/1</link>
            <dc:creator>John Doe</dc:creator>
            <description>Test description</description>
        </item>
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        // Should parse normally, ignoring DC namespace elements
        $this->assertCount(1, $result);
        $this->assertSame('Article with DC creator', $result[0]['title']);
    }

    // =========================================================================
    // Performance and Limits
    // =========================================================================

    public function testParseLargeFeed(): void
    {
        $items = '';
        for ($i = 1; $i <= 100; $i++) {
            $items .= <<<XML
        <item>
            <title>Article {$i}</title>
            <link>https://example.com/{$i}</link>
            <description>Description for article {$i}</description>
            <pubDate>Mon, 01 Jan 2024 12:00:00 GMT</pubDate>
        </item>
XML;
        }

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <title>Large Feed</title>
        {$items}
    </channel>
</rss>
XML;
        $path = $this->createTempFeed($xml);
        $result = $this->parser->parse($path);

        $this->assertCount(100, $result);
        $this->assertSame('Article 1', $result[0]['title']);
        $this->assertSame('Article 100', $result[99]['title']);
    }
}

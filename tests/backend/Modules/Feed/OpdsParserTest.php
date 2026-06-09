<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Feed;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Feed\Application\Services\OpdsParser;

/**
 * Tests for OpdsParser against representative OPDS feeds.
 *
 * The fixtures below are modelled on the Global Digital Library's OPDS v1
 * output (CC-BY/CC-BY-SA early-grade readers, levelled 1–5). They are
 * hand-authored to the OPDS 1.2 spec rather than captured live; the exact
 * language codes and category labels should be re-confirmed against
 * https://opds.digitallibrary.io/v1/root.xml before the GdlClient ships.
 */
class OpdsParserTest extends TestCase
{
    private OpdsParser $parser;

    protected function setUp(): void
    {
        $this->parser = new OpdsParser();
    }

    /**
     * A GDL-style acquisition feed: three book entries plus a "next" cursor.
     *
     * - Entry 1: Level 1, ePUB + PDF + cover + thumbnail, single author, dc:language.
     * - Entry 2: "Read aloud", two authors, only an ePUB (no cover).
     * - Entry 3: a non-book entry (cover only, no acquisition link) — must be skipped.
     */
    private function getAcquisitionFeed(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom"
      xmlns:dc="http://purl.org/dc/terms/"
      xmlns:opds="http://opds-spec.org/2010/catalog">
    <id>https://opds.digitallibrary.io/v1/en/level1.xml</id>
    <title>Level 1</title>
    <updated>2026-01-01T00:00:00Z</updated>
    <link rel="self" href="https://opds.digitallibrary.io/v1/en/level1.xml"
          type="application/atom+xml;profile=opds-catalog;kind=acquisition"/>
    <link rel="next" href="https://opds.digitallibrary.io/v1/en/level1.xml?page=2"
          type="application/atom+xml;profile=opds-catalog;kind=acquisition"/>
    <entry>
        <id>urn:gdl:the-sleepy-cat</id>
        <title>The Sleepy Cat</title>
        <author><name>Asha Rao</name></author>
        <dc:language>en</dc:language>
        <summary>A short story about a cat who will not wake up.</summary>
        <category scheme="http://digitallibrary.io/genre" term="fiction" label="Fiction"/>
        <category scheme="http://digitallibrary.io/reading-levels" term="1" label="Level 1"/>
        <link rel="http://opds-spec.org/acquisition/open-access"
              href="https://books.digitallibrary.io/epub/en/sleepy-cat.epub"
              type="application/epub+zip"/>
        <link rel="http://opds-spec.org/acquisition/open-access"
              href="https://books.digitallibrary.io/pdf/en/sleepy-cat.pdf"
              type="application/pdf"/>
        <link rel="http://opds-spec.org/image"
              href="https://books.digitallibrary.io/covers/sleepy-cat.jpg"
              type="image/jpeg"/>
        <link rel="http://opds-spec.org/image/thumbnail"
              href="https://books.digitallibrary.io/thumbs/sleepy-cat.jpg"
              type="image/jpeg"/>
    </entry>
    <entry>
        <id>urn:gdl:counting-mangoes</id>
        <title>Counting Mangoes</title>
        <author><name>Priya Menon</name></author>
        <author><name>Sam Okeke</name></author>
        <dc:language>en</dc:language>
        <summary>Count the mangoes from one to ten.</summary>
        <category scheme="http://digitallibrary.io/reading-levels" term="0" label="Read aloud"/>
        <link rel="http://opds-spec.org/acquisition/open-access"
              href="https://books.digitallibrary.io/epub/en/counting-mangoes.epub"
              type="application/epub+zip"/>
    </entry>
    <entry>
        <id>urn:gdl:section-divider</id>
        <title>More books</title>
        <link rel="http://opds-spec.org/image"
              href="https://books.digitallibrary.io/covers/divider.jpg"
              type="image/jpeg"/>
    </entry>
</feed>
XML;
    }

    /**
     * A GDL-style navigation feed listing language sub-catalogs.
     */
    private function getNavigationFeed(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <id>https://opds.digitallibrary.io/v1/root.xml</id>
    <title>Global Digital Library</title>
    <updated>2026-01-01T00:00:00Z</updated>
    <link rel="self" href="https://opds.digitallibrary.io/v1/root.xml"
          type="application/atom+xml;profile=opds-catalog;kind=navigation"/>
    <entry>
        <id>urn:gdl:nav:en</id>
        <title>English</title>
        <updated>2026-01-01T00:00:00Z</updated>
        <link rel="subsection" href="https://opds.digitallibrary.io/v1/en/root.xml"
              hreflang="en"
              type="application/atom+xml;profile=opds-catalog;kind=navigation"/>
    </entry>
    <entry>
        <id>urn:gdl:nav:sw</id>
        <title>Kiswahili</title>
        <updated>2026-01-01T00:00:00Z</updated>
        <link rel="subsection" href="https://opds.digitallibrary.io/v1/sw/root.xml"
              hreflang="sw"
              type="application/atom+xml;profile=opds-catalog;kind=navigation"/>
    </entry>
</feed>
XML;
    }

    // =========================================================================
    // acquisitionEntries()
    // =========================================================================

    public function testAcquisitionEntriesParsesBooks(): void
    {
        $result = $this->parser->acquisitionEntries($this->getAcquisitionFeed());

        // The third entry has no acquisition link and must be dropped.
        $this->assertCount(2, $result['results']);

        $book = $result['results'][0];
        $this->assertSame('urn:gdl:the-sleepy-cat', $book['id']);
        $this->assertSame('The Sleepy Cat', $book['title']);
        $this->assertSame(['Asha Rao'], $book['authors']);
        $this->assertSame('en', $book['language']);
        $this->assertSame('Level 1', $book['level']);
        $this->assertSame('A short story about a cat who will not wake up.', $book['summary']);
        $this->assertSame('https://books.digitallibrary.io/epub/en/sleepy-cat.epub', $book['epubUrl']);
    }

    public function testNonBookEntriesAreSkipped(): void
    {
        $result = $this->parser->acquisitionEntries($this->getAcquisitionFeed());

        $ids = array_column($result['results'], 'id');
        $this->assertNotContains('urn:gdl:section-divider', $ids);
    }

    public function testNextCursorIsExtracted(): void
    {
        $result = $this->parser->acquisitionEntries($this->getAcquisitionFeed());

        $this->assertSame(
            'https://opds.digitallibrary.io/v1/en/level1.xml?page=2',
            $result['next']
        );
    }

    public function testNextCursorIsNullOnLastPage(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <id>last-page</id>
    <title>Level 1</title>
    <link rel="self" href="https://example.com/last.xml"/>
    <entry>
        <id>urn:gdl:only</id>
        <title>Only Book</title>
        <link rel="http://opds-spec.org/acquisition/open-access"
              href="https://example.com/only.epub" type="application/epub+zip"/>
    </entry>
</feed>
XML;

        $result = $this->parser->acquisitionEntries($xml);
        $this->assertNull($result['next']);
    }

    public function testMultipleAuthorsAreCollected(): void
    {
        $result = $this->parser->acquisitionEntries($this->getAcquisitionFeed());

        $book = $result['results'][1];
        $this->assertSame(['Priya Menon', 'Sam Okeke'], $book['authors']);
    }

    public function testReadAloudLevelIsRecognised(): void
    {
        $result = $this->parser->acquisitionEntries($this->getAcquisitionFeed());

        $this->assertSame('Read aloud', $result['results'][1]['level']);
    }

    public function testGenreCategoryIsNotMistakenForLevel(): void
    {
        // Entry 1 carries a "Fiction" genre category before its level category;
        // only the reading-level one should be picked up.
        $result = $this->parser->acquisitionEntries($this->getAcquisitionFeed());

        $this->assertSame('Level 1', $result['results'][0]['level']);
    }

    public function testCoverPrefersFullImageOverThumbnail(): void
    {
        $result = $this->parser->acquisitionEntries($this->getAcquisitionFeed());

        $this->assertSame(
            'https://books.digitallibrary.io/covers/sleepy-cat.jpg',
            $result['results'][0]['coverUrl']
        );
    }

    public function testCoverIsNullWhenAbsent(): void
    {
        $result = $this->parser->acquisitionEntries($this->getAcquisitionFeed());

        $this->assertNull($result['results'][1]['coverUrl']);
    }

    public function testInvalidXmlYieldsEmptyResult(): void
    {
        $result = $this->parser->acquisitionEntries('not valid xml at all');

        $this->assertSame([], $result['results']);
        $this->assertNull($result['next']);
    }

    public function testEmptyStringYieldsEmptyResult(): void
    {
        $result = $this->parser->acquisitionEntries('');

        $this->assertSame([], $result['results']);
        $this->assertNull($result['next']);
    }

    public function testNavigationFeedYieldsNoBooks(): void
    {
        // Calling the acquisition parser on a navigation feed must not throw
        // and must surface zero books (no acquisition links present).
        $result = $this->parser->acquisitionEntries($this->getNavigationFeed());

        $this->assertSame([], $result['results']);
    }

    // =========================================================================
    // navigationLinks() / findNavHref()
    // =========================================================================

    public function testNavigationLinksAreParsed(): void
    {
        $links = $this->parser->navigationLinks($this->getNavigationFeed());

        $this->assertCount(2, $links);
        $this->assertSame('English', $links[0]['title']);
        $this->assertSame('https://opds.digitallibrary.io/v1/en/root.xml', $links[0]['href']);
        $this->assertSame('en', $links[0]['lang']);
    }

    public function testFindNavHrefByLanguageCode(): void
    {
        $href = $this->parser->findNavHref($this->getNavigationFeed(), 'sw');

        $this->assertSame('https://opds.digitallibrary.io/v1/sw/root.xml', $href);
    }

    public function testFindNavHrefByTitleFallback(): void
    {
        // No hreflang match for "english" as a code, but the title matches.
        $href = $this->parser->findNavHref($this->getNavigationFeed(), 'English');

        $this->assertSame('https://opds.digitallibrary.io/v1/en/root.xml', $href);
    }

    public function testFindNavHrefReturnsNullWhenAbsent(): void
    {
        $href = $this->parser->findNavHref($this->getNavigationFeed(), 'fr');

        $this->assertNull($href);
    }

    public function testFindNavHrefReturnsNullForEmptyNeedle(): void
    {
        $href = $this->parser->findNavHref($this->getNavigationFeed(), '   ');

        $this->assertNull($href);
    }
}

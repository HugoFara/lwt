<?php

/**
 * Unit tests for the in-tree EPUB reader.
 *
 * PHP version 8.2
 *
 * @category Testing
 * @package  Lwt\Tests\Modules\Book\Infrastructure\Epub
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.4.0
 */

declare(strict_types=1);

namespace Lwt\Tests\Modules\Book\Infrastructure\Epub;

use Lwt\Modules\Book\Infrastructure\Epub\EpubReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

/**
 * Unit tests for EpubReader.
 *
 * These cover the reader that replaced `kiwilan/php-ebook` in 3.4.0 (#263).
 * Each test assembles a real EPUB on disk rather than mocking, because the
 * whole point of the class is to survive the shapes real EPUBs come in.
 *
 * @since 3.4.0
 */
class EpubReaderTest extends TestCase
{
    /** @var list<string> Temp files to clean up after each test */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('Zip extension not available');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    // =========================================================================
    // EPUB 2 (NCX table of contents)
    // =========================================================================

    #[Test]
    public function readsMetadataFromAnEpub2Package(): void
    {
        $book = EpubReader::read($this->buildEpub2());

        $this->assertSame('A Tale of Two Cities', $book->getTitle());
        $this->assertSame('Charles Dickens', $book->getAuthorMain());
        $this->assertSame('It was the best of times.', $book->getDescription());
        $this->assertSame('en', $book->getLanguage());
    }

    #[Test]
    public function readsChapterLabelsFromTheNcx(): void
    {
        $book = EpubReader::read($this->buildEpub2());
        $chapters = $book->getChapters();

        $this->assertCount(2, $chapters);
        $this->assertSame('Recalled to Life', $chapters[0]->getLabel());
        $this->assertSame('The Golden Thread', $chapters[1]->getLabel());
        $this->assertStringContainsString('best of times', $chapters[0]->getContent());
        $this->assertStringContainsString('golden thread', $chapters[1]->getContent());
    }

    #[Test]
    public function chapterContentIsTheBodyNotTheWholeDocument(): void
    {
        $book = EpubReader::read($this->buildEpub2());

        $content = $book->getChapters()[0]->getContent();
        $this->assertStringNotContainsString('<html', $content);
        $this->assertStringNotContainsString('<head', $content);
        $this->assertStringContainsString('<p>', $content);
    }

    #[Test]
    public function readsAllAuthorsInDocumentOrder(): void
    {
        $book = EpubReader::read($this->buildEpub2(
            extraMetadata: '<dc:creator>Wilkie Collins</dc:creator>'
        ));

        $this->assertSame(['Charles Dickens', 'Wilkie Collins'], $book->getAuthors());
        $this->assertSame('Charles Dickens', $book->getAuthorMain());
    }

    #[Test]
    public function spineOrderDrivesDocumentOrder(): void
    {
        $book = EpubReader::read($this->buildEpub2());

        $filenames = array_map(
            static fn($document): string => $document->getFilename(),
            $book->getHtml()
        );
        $this->assertSame(
            ['OEBPS/chapter1.xhtml', 'OEBPS/chapter2.xhtml'],
            $filenames
        );
    }

    #[Test]
    public function resolvesTocHrefsThatCarryAFragment(): void
    {
        // Real NCX files routinely point at "chapter1.xhtml#start".
        $book = EpubReader::read($this->buildEpub2(tocFragment: '#start'));

        $chapters = $book->getChapters();
        $this->assertCount(2, $chapters);
        $this->assertSame('OEBPS/chapter1.xhtml', $chapters[0]->getFilename());
    }

    #[Test]
    public function collapsesRepeatedTocEntriesPointingAtOneFile(): void
    {
        // Several navPoints into the same file (per-section anchors) must not
        // produce the same chapter text several times over.
        $book = EpubReader::read($this->buildEpub2(duplicateNavPoint: true));

        $this->assertCount(2, $book->getChapters());
    }

    // =========================================================================
    // EPUB 3 (navigation document)
    // =========================================================================

    #[Test]
    public function readsChapterLabelsFromAnEpub3NavDocument(): void
    {
        $book = EpubReader::read($this->buildEpub3());
        $chapters = $book->getChapters();

        $this->assertCount(2, $chapters);
        $this->assertSame('Chapter One', $chapters[0]->getLabel());
        $this->assertSame('Chapter Two', $chapters[1]->getLabel());
    }

    #[Test]
    public function readsMetadataFromAnEpub3Package(): void
    {
        $book = EpubReader::read($this->buildEpub3());

        $this->assertSame('Modern Book', $book->getTitle());
        $this->assertSame('Jane Author', $book->getAuthorMain());
        $this->assertSame('fr', $book->getLanguage());
    }

    // =========================================================================
    // Degraded and hostile inputs
    // =========================================================================

    #[Test]
    public function returnsNoChaptersButKeepsDocumentsWhenTocIsMissing(): void
    {
        $book = EpubReader::read($this->buildEpub2(omitToc: true));

        $this->assertSame([], $book->getChapters());
        $this->assertCount(2, $book->getHtml(), 'spine documents must survive a missing TOC');
    }

    #[Test]
    public function fallsBackToAConventionalOpfPathWhenContainerIsMalformed(): void
    {
        $path = $this->tempFile();
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('mimetype', 'application/epub+zip');
        $zip->addFromString('META-INF/container.xml', '<?xml version="1.0"?><container/>');
        $zip->addFromString('content.opf', $this->opf(
            title: 'Rescued Book',
            manifest: '',
            spine: '',
            metadata: '<dc:language>de</dc:language>'
        ));
        $zip->close();

        $book = EpubReader::read($path);
        $this->assertSame('Rescued Book', $book->getTitle());
        $this->assertSame('de', $book->getLanguage());
    }

    #[Test]
    public function throwsWhenNoPackageDocumentExists(): void
    {
        $path = $this->tempFile();
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('mimetype', 'application/epub+zip');
        $zip->addFromString('META-INF/container.xml', '<?xml version="1.0"?><container/>');
        $zip->close();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/no package document/i');
        EpubReader::read($path);
    }

    #[Test]
    public function throwsWhenTheFileIsNotAZipArchive(): void
    {
        $path = $this->tempFile();
        file_put_contents($path, 'this is plainly not a zip');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ZIP archive/i');
        EpubReader::read($path);
    }

    #[Test]
    public function doesNotResolveExternalEntities(): void
    {
        // A hostile EPUB must not be able to read files off the server via an
        // XXE payload in the package document.
        $secret = $this->tempFile();
        file_put_contents($secret, 'TOP-SECRET-CONTENTS');

        $path = $this->tempFile();
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('mimetype', 'application/epub+zip');
        $zip->addFromString(
            'META-INF/container.xml',
            '<?xml version="1.0"?>'
            . '<container xmlns="urn:oasis:names:tc:opendocument:xmlns:container">'
            . '<rootfiles><rootfile full-path="content.opf"/></rootfiles>'
            . '</container>'
        );
        $zip->addFromString(
            'content.opf',
            '<?xml version="1.0"?>'
            . '<!DOCTYPE package [<!ENTITY xxe SYSTEM "file://' . $secret . '">]>'
            . '<package xmlns="http://www.idpf.org/2007/opf" version="2.0">'
            . '<metadata xmlns:dc="http://purl.org/dc/elements/1.1/">'
            . '<dc:title>&xxe;</dc:title>'
            . '</metadata><manifest/><spine/></package>'
        );
        $zip->close();

        $title = EpubReader::read($path)->getTitle();
        $this->assertNotSame('TOP-SECRET-CONTENTS', $title);
        $this->assertStringNotContainsString('TOP-SECRET', (string) $title);
    }

    // =========================================================================
    // Fixture builders
    // =========================================================================

    /**
     * Build an EPUB 2 with an NCX table of contents, OPF nested under OEBPS/.
     */
    private function buildEpub2(
        string $extraMetadata = '',
        string $tocFragment = '',
        bool $duplicateNavPoint = false,
        bool $omitToc = false
    ): string {
        $path = $this->tempFile();
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('mimetype', 'application/epub+zip');
        $zip->addFromString(
            'META-INF/container.xml',
            '<?xml version="1.0"?>'
            . '<container xmlns="urn:oasis:names:tc:opendocument:xmlns:container" version="1.0">'
            . '<rootfiles>'
            . '<rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>'
            . '</rootfiles></container>'
        );

        $tocItem = $omitToc
            ? ''
            : '<item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>';

        $zip->addFromString('OEBPS/content.opf', $this->opf(
            title: 'A Tale of Two Cities',
            manifest: '<item id="c1" href="chapter1.xhtml" media-type="application/xhtml+xml"/>'
                . '<item id="c2" href="chapter2.xhtml" media-type="application/xhtml+xml"/>'
                . $tocItem,
            spine: '<itemref idref="c1"/><itemref idref="c2"/>',
            metadata: '<dc:creator>Charles Dickens</dc:creator>'
                . '<dc:description>It was the best of times.</dc:description>'
                . '<dc:language>en</dc:language>'
                . $extraMetadata
        ));

        $zip->addFromString('OEBPS/chapter1.xhtml', $this->xhtml(
            'Recalled to Life',
            '<p>It was the best of times, it was the worst of times.</p>'
        ));
        $zip->addFromString('OEBPS/chapter2.xhtml', $this->xhtml(
            'The Golden Thread',
            '<p>A golden thread ran through the tale.</p>'
        ));

        if (!$omitToc) {
            $duplicate = $duplicateNavPoint
                ? $this->navPoint('np1b', 'Recalled to Life, again', 'chapter1.xhtml#later')
                : '';
            $zip->addFromString(
                'OEBPS/toc.ncx',
                '<?xml version="1.0"?>'
                . '<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1"><navMap>'
                . $this->navPoint('np1', 'Recalled to Life', 'chapter1.xhtml' . $tocFragment)
                . $duplicate
                . $this->navPoint('np2', 'The Golden Thread', 'chapter2.xhtml' . $tocFragment)
                . '</navMap></ncx>'
            );
        }

        $zip->close();
        return $path;
    }

    /**
     * Build an EPUB 3 whose table of contents is a nav document.
     */
    private function buildEpub3(): string
    {
        $path = $this->tempFile();
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('mimetype', 'application/epub+zip');
        $zip->addFromString(
            'META-INF/container.xml',
            '<?xml version="1.0"?>'
            . '<container xmlns="urn:oasis:names:tc:opendocument:xmlns:container" version="1.0">'
            . '<rootfiles>'
            . '<rootfile full-path="content.opf" media-type="application/oebps-package+xml"/>'
            . '</rootfiles></container>'
        );
        $zip->addFromString('content.opf', $this->opf(
            title: 'Modern Book',
            manifest: '<item id="nav" href="nav.xhtml" media-type="application/xhtml+xml" properties="nav"/>'
                . '<item id="c1" href="c1.xhtml" media-type="application/xhtml+xml"/>'
                . '<item id="c2" href="c2.xhtml" media-type="application/xhtml+xml"/>',
            spine: '<itemref idref="c1"/><itemref idref="c2"/>',
            metadata: '<dc:creator>Jane Author</dc:creator><dc:language>fr</dc:language>',
            version: '3.0'
        ));
        $zip->addFromString(
            'nav.xhtml',
            '<?xml version="1.0" encoding="utf-8"?>'
            . '<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">'
            . '<head><title>TOC</title></head><body>'
            . '<nav epub:type="toc"><ol>'
            . '<li><a href="c1.xhtml">Chapter One</a></li>'
            . '<li><a href="c2.xhtml">Chapter Two</a></li>'
            . '</ol></nav></body></html>'
        );
        $zip->addFromString('c1.xhtml', $this->xhtml('One', '<p>First chapter body.</p>'));
        $zip->addFromString('c2.xhtml', $this->xhtml('Two', '<p>Second chapter body.</p>'));
        $zip->close();

        return $path;
    }

    /**
     * Render an OPF package document.
     */
    private function opf(
        string $title,
        string $manifest,
        string $spine,
        string $metadata = '',
        string $version = '2.0'
    ): string {
        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<package xmlns="http://www.idpf.org/2007/opf" version="' . $version . '" '
            . 'unique-identifier="bookid">'
            . '<metadata xmlns:dc="http://purl.org/dc/elements/1.1/" '
            . 'xmlns:opf="http://www.idpf.org/2007/opf">'
            . '<dc:title>' . $title . '</dc:title>'
            . '<dc:identifier id="bookid">urn:uuid:test</dc:identifier>'
            . $metadata
            . '</metadata>'
            . '<manifest>' . $manifest . '</manifest>'
            . '<spine toc="ncx">' . $spine . '</spine>'
            . '</package>';
    }

    /**
     * Render one NCX navigation point.
     */
    private function navPoint(string $id, string $label, string $src): string
    {
        return '<navPoint id="' . $id . '">'
            . '<navLabel><text>' . $label . '</text></navLabel>'
            . '<content src="' . $src . '"/>'
            . '</navPoint>';
    }

    /**
     * Render a minimal XHTML content document.
     */
    private function xhtml(string $title, string $body): string
    {
        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<html xmlns="http://www.w3.org/1999/xhtml"><head><title>' . $title . '</title></head>'
            . '<body>' . $body . '</body></html>';
    }

    /**
     * Reserve a temp path that tearDown will clean up.
     */
    private function tempFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lwtepub');
        $this->tempFiles[] = $path;
        return $path;
    }
}

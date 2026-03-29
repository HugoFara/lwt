<?php

/**
 * Unit tests for the EpubParserService.
 *
 * Tests EPUB parsing and validation functionality.
 *
 * PHP version 8.1
 *
 * @category Testing
 * @package  Lwt\Tests\Modules\Book\Application\Services
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Tests\Modules\Book\Application\Services;

use Lwt\Modules\Book\Application\Services\EpubParserService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use InvalidArgumentException;

/**
 * Unit tests for EpubParserService.
 *
 * @since 3.0.0
 */
class EpubParserServiceTest extends TestCase
{
    private EpubParserService $service;

    protected function setUp(): void
    {
        $this->service = new EpubParserService();
    }

    #[Test]
    public function canBeInstantiated(): void
    {
        $this->assertInstanceOf(EpubParserService::class, $this->service);
    }

    // =========================================================================
    // Zip extension validation tests
    // =========================================================================

    #[Test]
    public function isValidEpubReturnsFalseWhenZipExtensionMissing(): void
    {
        // Skip this test if zip extension is actually available
        if (extension_loaded('zip')) {
            $this->markTestSkipped('Zip extension is loaded, cannot test missing extension scenario');
        }

        $result = $this->service->isValidEpub('/tmp/nonexistent.epub');
        $this->assertFalse($result);
    }

    #[Test]
    public function parseThrowsExceptionWhenZipExtensionMissing(): void
    {
        // Skip this test if zip extension is actually available
        if (extension_loaded('zip')) {
            $this->markTestSkipped('Zip extension is loaded, cannot test missing extension scenario');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The 'zip' PHP extension is required for EPUB import but is not installed");

        $this->service->parse('/tmp/nonexistent.epub');
    }

    #[Test]
    public function getMetadataReturnsNullWhenZipExtensionMissing(): void
    {
        // Skip this test if zip extension is actually available
        if (extension_loaded('zip')) {
            $this->markTestSkipped('Zip extension is loaded, cannot test missing extension scenario');
        }

        $result = $this->service->getMetadata('/tmp/nonexistent.epub');
        $this->assertNull($result);
    }

    // =========================================================================
    // File validation tests (when zip is available)
    // =========================================================================

    #[Test]
    public function isValidEpubReturnsFalseForNonExistentFile(): void
    {
        // Skip if zip extension not available
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('Zip extension not available');
        }

        $result = $this->service->isValidEpub('/tmp/nonexistent-file.epub');
        $this->assertFalse($result);
    }

    #[Test]
    public function parseThrowsExceptionForNonExistentFile(): void
    {
        // Skip if zip extension not available
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('Zip extension not available');
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('EPUB file not found');

        $this->service->parse('/tmp/nonexistent-file.epub');
    }

    #[Test]
    public function getMetadataReturnsNullForNonExistentFile(): void
    {
        // Skip if zip extension not available
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('Zip extension not available');
        }

        $result = $this->service->getMetadata('/tmp/nonexistent-file.epub');
        $this->assertNull($result);
    }

    // =========================================================================
    // Extension validation tests
    // =========================================================================

    #[Test]
    public function isValidEpubReturnsFalseForNonEpubExtension(): void
    {
        // Skip if zip extension not available
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('Zip extension not available');
        }

        // Create a temporary file with wrong extension
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.txt';
        file_put_contents($tempFile, 'test content');

        try {
            $result = $this->service->isValidEpub($tempFile);
            $this->assertFalse($result);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    #[Test]
    public function isValidEpubReturnsFalseForInvalidZipFile(): void
    {
        // Skip if zip extension not available
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('Zip extension not available');
        }

        // Create a temporary .epub file that's not actually a ZIP
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.epub';
        file_put_contents($tempFile, 'not a zip file');

        try {
            $result = $this->service->isValidEpub($tempFile);
            $this->assertFalse($result);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    // =========================================================================
    // Bug reproduction: GitHub issue #231
    // =========================================================================

    #[Test]
    public function isValidEpubFailsForTempUploadPathWithoutExtension(): void
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('Zip extension not available');
        }

        // Build a minimal valid EPUB (ZIP with mimetype + container.xml)
        $tempFile = tempnam(sys_get_temp_dir(), 'php');
        $zip = new \ZipArchive();
        $zip->open($tempFile, \ZipArchive::OVERWRITE);
        $zip->addFromString('mimetype', 'application/epub+zip');
        $zip->addFromString(
            'META-INF/container.xml',
            '<?xml version="1.0"?><container/>'
        );
        $zip->close();

        try {
            // Temp path has NO .epub extension (like /tmp/phpXXXXXX).
            // Passing the original filename allows the extension check
            // to pass (GitHub issue #231).
            $result = $this->service->isValidEpub(
                $tempFile,
                'book.epub'
            );
            $this->assertTrue(
                $result,
                'isValidEpub() should accept valid EPUB at temp path '
                . 'when original filename is provided (GitHub issue '
                . '#231). Path: ' . $tempFile
            );
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    // =========================================================================
    // HTML cleaning tests
    // =========================================================================

    #[Test]
    public function cleanHtmlContentRemovesScriptTags(): void
    {
        $html = '<p>Hello</p><script>alert("test");</script><p>World</p>';
        $result = $this->service->cleanHtmlContent($html);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('World', $result);
    }

    #[Test]
    public function cleanHtmlContentRemovesStyleTags(): void
    {
        $html = '<p>Content</p><style>body { color: red; }</style>';
        $result = $this->service->cleanHtmlContent($html);

        $this->assertStringNotContainsString('<style>', $result);
        $this->assertStringNotContainsString('color: red', $result);
        $this->assertStringContainsString('Content', $result);
    }

    #[Test]
    public function cleanHtmlContentConvertsBreaksToNewlines(): void
    {
        $html = '<p>Line 1<br>Line 2<br/>Line 3</p>';
        $result = $this->service->cleanHtmlContent($html);

        $this->assertStringContainsString("Line 1\nLine 2\nLine 3", $result);
    }

    #[Test]
    public function cleanHtmlContentConvertsParagraphsToDoubleNewlines(): void
    {
        $html = '<p>Para 1</p><p>Para 2</p>';
        $result = $this->service->cleanHtmlContent($html);

        $this->assertStringContainsString("Para 1\n\nPara 2", $result);
    }

    #[Test]
    public function cleanHtmlContentConvertsListItems(): void
    {
        $html = '<ul><li>Item 1</li><li>Item 2</li></ul>';
        $result = $this->service->cleanHtmlContent($html);

        $this->assertStringContainsString("- Item 1", $result);
        $this->assertStringContainsString("- Item 2", $result);
    }

    #[Test]
    public function cleanHtmlContentDecodesHtmlEntities(): void
    {
        $html = '<p>Hello &amp; goodbye &lt;test&gt;</p>';
        $result = $this->service->cleanHtmlContent($html);

        $this->assertStringContainsString('Hello & goodbye <test>', $result);
    }

    #[Test]
    public function cleanHtmlContentNormalizesWhitespace(): void
    {
        $html = '<p>Word1    word2      word3</p>';
        $result = $this->service->cleanHtmlContent($html);

        $this->assertSame('Word1 word2 word3', $result);
    }

    #[Test]
    public function cleanHtmlContentTrimsResult(): void
    {
        $html = '   <p>Content</p>   ';
        $result = $this->service->cleanHtmlContent($html);

        $this->assertSame(trim($result), $result);
        $this->assertStringContainsString('Content', $result);
    }

    #[Test]
    public function cleanHtmlContentReturnsEmptyStringForEmptyInput(): void
    {
        $result = $this->service->cleanHtmlContent('');
        $this->assertSame('', $result);
    }

    #[Test]
    public function cleanHtmlContentHandlesOnlyWhitespace(): void
    {
        $html = '   <div>   </div>   ';
        $result = $this->service->cleanHtmlContent($html);
        $this->assertSame('', $result);
    }
}
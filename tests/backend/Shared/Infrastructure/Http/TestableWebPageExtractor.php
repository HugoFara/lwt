<?php

declare(strict_types=1);

namespace Lwt\Tests\Shared\Infrastructure\Http;

use Lwt\Shared\Infrastructure\Http\WebPageExtractor;

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

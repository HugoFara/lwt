<?php

/**
 * EPUB Parser Service
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Book\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Book\Application\Services;

use Kiwilan\Ebook\Ebook;
use Kiwilan\Ebook\Formats\Epub\EpubModule;
use Kiwilan\Ebook\Formats\Epub\Parser\EpubChapter;
use Kiwilan\Ebook\Formats\Epub\Parser\EpubHtml;
use Kiwilan\Ebook\Models\BookAuthor;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service for parsing EPUB files and extracting content.
 *
 * Uses the kiwilan/php-ebook library to read EPUB files and extract
 * metadata and chapter content for import into LWT.
 *
 * @since 3.0.0
 */
class EpubParserService
{
    /**
     * Parse an EPUB file and extract metadata and chapters.
     *
     * @param string $filePath     Absolute path to the EPUB file
     * @param string $originalName Original filename (used to derive the
     *                             format when $filePath has no extension,
     *                             e.g. PHP upload temp paths)
     *
     * @return array{
     *     metadata: array{
     *         title: string,
     *         author: string|null,
     *         description: string|null,
     *         language: string|null,
     *         sourceHash: string
     *     },
     *     chapters: array<array{num: int, title: string, content: string}>
     * }
     *
     * @throws InvalidArgumentException If file doesn't exist
     * @throws RuntimeException If file cannot be parsed
     */
    public function parse(string $filePath, string $originalName = ''): array
    {
        if (!extension_loaded('zip')) {
            throw new RuntimeException(
                "The 'zip' PHP extension is required for EPUB import "
                . "but is not installed. Please install php-zip extension."
            );
        }

        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("EPUB file not found: {$filePath}");
        }

        // Additional validation - check if file is readable and has content
        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("EPUB file is not readable: {$filePath}");
        }

        if (filesize($filePath) === 0) {
            throw new InvalidArgumentException("EPUB file is empty: {$filePath}");
        }

        try {
            $ebook = Ebook::read($filePath, $this->resolveFormat($filePath, $originalName));
            if ($ebook === null) {
                throw new RuntimeException(
                    "Failed to read EPUB file: {$filePath}. "
                    . "The file may be corrupted or not a valid EPUB format."
                );
            }
        } catch (\Throwable $e) {
            // Provide more specific error messages
            $message = $e->getMessage();
            if (str_contains($message, 'getManifest() on null')) {
                $message = "EPUB file appears to be corrupted or has an invalid internal structure (missing manifest).";
            } elseif (str_contains($message, 'ZIP')) {
                $message = "EPUB file could not be read as a ZIP archive. The file may be corrupted.";
            }
            throw new RuntimeException("Failed to parse EPUB file: {$message}", 0, $e);
        }

        $metadata = [
            'title' => $ebook->getTitle() ?? 'Unknown Title',
            'author' => $this->extractAuthor($ebook),
            'description' => $ebook->getDescription(),
            'language' => $ebook->getLanguage(),
            'sourceHash' => (string) hash_file('sha256', $filePath),
        ];

        $chapters = $this->extractChapters($ebook);

        return [
            'metadata' => $metadata,
            'chapters' => $chapters,
        ];
    }

    /**
     * Extract the primary author name from an ebook.
     *
     * @param Ebook $ebook The ebook object
     *
     * @return string|null Author name or null if not found
     */
    private function extractAuthor(Ebook $ebook): ?string
    {
        $author = $ebook->getAuthorMain();
        if ($author !== null) {
            return $author->getName();
        }

        /** @var BookAuthor[] $authors */
        $authors = $ebook->getAuthors();
        if (!empty($authors)) {
            return $authors[0]->getName();
        }

        return null;
    }

    /**
     * Extract chapters from an ebook.
     *
     * @param Ebook $ebook The ebook object
     *
     * @return array<array{num: int, title: string, content: string}>
     */
    private function extractChapters(Ebook $ebook): array
    {
        $chapters = [];
        $chapterNum = 1;

        // Try to get chapters from the ebook via the EPUB parser
        $epubModule = $this->getEpubModule($ebook);
        if ($epubModule !== null) {
            try {
                /** @var EpubChapter[] $ebookChapters */
                $ebookChapters = $epubModule->getChapters();

                foreach ($ebookChapters as $chapter) {
                    $content = $this->cleanHtmlContent($chapter->getContent());

                    // Skip empty chapters
                    if (trim($content) === '') {
                        continue;
                    }

                    $chapters[] = [
                        'num' => $chapterNum,
                        'title' => $chapter->getLabel() ?: "Chapter {$chapterNum}",
                        'content' => $content,
                    ];
                    $chapterNum++;
                }
            } catch (\Throwable $e) {
                // If chapter extraction fails, log the error and continue with HTML fallback
                error_log("EPUB chapter extraction failed, trying HTML fallback: " . $e->getMessage());
            }
        }

        // If no chapters found, try to extract from HTML files
        if (empty($chapters)) {
            $chapters = $this->extractFromHtmlFiles($ebook);
        }

        return $chapters;
    }

    /**
     * Get the EpubModule from an Ebook.
     *
     * @param Ebook $ebook The ebook object
     *
     * @return EpubModule|null The EPUB module or null if not an EPUB
     */
    private function getEpubModule(Ebook $ebook): ?EpubModule
    {
        $parser = $ebook->getParser();
        if ($parser === null) {
            return null;
        }
        return $parser->getEpub();
    }

    /**
     * Extract content from HTML files in the EPUB as fallback.
     *
     * @param Ebook $ebook The ebook object
     *
     * @return array<array{num: int, title: string, content: string}>
     */
    private function extractFromHtmlFiles(Ebook $ebook): array
    {
        $chapters = [];
        $chapterNum = 1;

        // Try to get HTML content via the EPUB module
        $epubModule = $this->getEpubModule($ebook);
        if ($epubModule !== null) {
            try {
                /** @var EpubHtml[] $htmlFiles */
                $htmlFiles = $epubModule->getHtml();
                foreach ($htmlFiles as $htmlFile) {
                    $content = $this->cleanHtmlContent($htmlFile->getBody() ?? '');

                    if (trim($content) === '') {
                        continue;
                    }

                    // Try to extract title from content
                    $title = $this->extractTitleFromContent($content, $chapterNum);

                    $chapters[] = [
                        'num' => $chapterNum,
                        'title' => $title,
                        'content' => $content,
                    ];
                    $chapterNum++;
                }
            } catch (\Throwable $e) {
                error_log("EPUB HTML extraction fallback failed: " . $e->getMessage());
            }
        }

        return $chapters;
    }

    /**
     * Extract a title from content if possible.
     *
     * @param string $content The text content
     * @param int    $num     Default chapter number
     *
     * @return string The extracted or default title
     */
    private function extractTitleFromContent(string $content, int $num): string
    {
        // Get first line as potential title
        $lines = explode("\n", trim($content));
        $firstLine = trim($lines[0] ?? '');

        // If first line looks like a title (short, not a paragraph)
        if ($firstLine !== '' && mb_strlen($firstLine) <= 100 && !str_contains($firstLine, '.')) {
            return $firstLine;
        }

        return "Chapter {$num}";
    }

    /**
     * Clean HTML content to plain text suitable for LWT.
     *
     * Strips HTML tags while preserving paragraph structure with double
     * newlines for paragraph breaks.
     *
     * @param string $html The HTML content
     *
     * @return string Clean plain text
     */
    public function cleanHtmlContent(string $html): string
    {
        // Remove scripts and styles
        $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $text) ?? $text;

        // Convert paragraph and div tags to double newlines
        $text = preg_replace('/<\/?(p|div)[^>]*>/i', "\n\n", $text) ?? $text;

        // Convert line breaks to single newlines
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text) ?? $text;

        // Convert headings to preserve structure
        $text = preg_replace('/<h[1-6][^>]*>/i', "\n\n", $text) ?? $text;
        $text = preg_replace('/<\/h[1-6]>/i', "\n\n", $text) ?? $text;

        // Convert list items
        $text = preg_replace('/<li[^>]*>/i', "\n- ", $text) ?? $text;

        // Strip remaining HTML tags
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        // Replace multiple spaces/tabs with single space
        $text = preg_replace('/[ \t\r]+/', ' ', $text) ?? $text;

        // Normalize multiple newlines to double newline (paragraph break)
        $text = preg_replace('/\n\s*\n/', "\n\n", $text) ?? $text;

        // Remove leading/trailing whitespace from lines
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);

        // Remove excessive newlines (more than 2)
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Validate that a file is an EPUB.
     *
     * @param string $filePath Path to the file
     *
     * @return bool True if valid EPUB
     */
    public function isValidEpub(string $filePath, string $originalName = ''): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        if (!is_readable($filePath)) {
            return false;
        }

        if (filesize($filePath) === 0) {
            return false;
        }

        // Check file extension using original filename when available
        // (PHP stores uploads at temp paths like /tmp/phpXXXXXX with no extension)
        $nameToCheck = $originalName !== '' ? $originalName : $filePath;
        $extension = strtolower(pathinfo($nameToCheck, PATHINFO_EXTENSION));
        if ($extension !== 'epub') {
            return false;
        }

        // Check if it's a ZIP file (EPUBs are ZIP archives)
        $fh = fopen($filePath, 'rb');
        if ($fh === false) {
            return false;
        }

        $header = fread($fh, 4);
        fclose($fh);

        // ZIP magic number - basic validation that doesn't require zip extension
        if ($header !== "PK\x03\x04") {
            return false;
        }

        // If ZIP extension is not loaded, we can't do deeper validation
        if (!extension_loaded('zip')) {
            return true; // Basic validation passed, let the parser handle the rest
        }

        // Additional validation with ZIP extension if available
        /** @psalm-suppress UndefinedClass */
        $zip = new \ZipArchive();
        /** @psalm-suppress UndefinedClass */
        $result = $zip->open($filePath, \ZipArchive::RDONLY);

        if ($result !== true) {
            return false;
        }

        // Check for EPUB-specific files
        $hasMimetype = $zip->locateName('mimetype') !== false;
        $hasContainer = $zip->locateName('META-INF/container.xml') !== false;

        $zip->close();

        return $hasMimetype && $hasContainer;
    }

    /**
     * Get just the metadata without parsing chapters.
     *
     * @param string $filePath     Path to the EPUB file
     * @param string $originalName Original filename (used to derive the
     *                             format when $filePath has no extension,
     *                             e.g. PHP upload temp paths)
     *
     * @return array{
     *     title: string,
     *     author: string|null,
     *     description: string|null,
     *     language: string|null
     * }|null Metadata or null on failure
     */
    public function getMetadata(string $filePath, string $originalName = ''): ?array
    {
        if (!extension_loaded('zip')) {
            return null;
        }

        if (!file_exists($filePath)) {
            return null;
        }

        try {
            $ebook = Ebook::read($filePath, $this->resolveFormat($filePath, $originalName));
            if ($ebook === null) {
                return null;
            }

            return [
                'title' => $ebook->getTitle() ?? 'Unknown Title',
                'author' => $this->extractAuthor($ebook),
                'description' => $ebook->getDescription(),
                'language' => $ebook->getLanguage(),
            ];
        } catch (\Throwable $e) {
            error_log("EpubParserService::getMetadata failed for '$filePath': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Resolve the format hint for the underlying ebook library.
     *
     * Falls back to the original filename's extension when the path itself
     * has none (PHP upload temp paths look like /tmp/phpXXXXXX). Returns
     * null when no extension can be determined, letting the library decide.
     */
    private function resolveFormat(string $filePath, string $originalName): ?string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension === '' && $originalName !== '') {
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        }
        return $extension === '' ? null : $extension;
    }
}

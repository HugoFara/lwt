<?php

/**
 * Minimal in-tree EPUB reader.
 *
 * PHP version 8.2
 *
 * @category Lwt
 * @package  Lwt\Modules\Book\Infrastructure\Epub
 * @author   HugoFara <git@hugofara.net>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.4.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Book\Infrastructure\Epub;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

/**
 * Minimal in-tree EPUB reader.
 *
 * Replaces `kiwilan/php-ebook` for LWT's single use case: pulling metadata and
 * readable chapter text out of an EPUB. It deliberately implements only EPUB,
 * where the dependency also covered MOBI, CBZ and PDF, none of which LWT
 * imports.
 *
 * Structure read, per the OCF and OPF specifications:
 *
 * 1. `META-INF/container.xml` names the OPF package document.
 * 2. The OPF carries Dublin Core metadata, a manifest of every resource, and a
 *    spine giving reading order.
 * 3. The table of contents is either an EPUB 2 NCX (`navMap`/`navPoint`) or an
 *    EPUB 3 navigation document (`<nav epub:type="toc">`). Both are read; NCX
 *    wins when a book ships both, matching reader behaviour.
 *
 * Every XPath query matches on `local-name()` because EPUBs in the wild are
 * inconsistent about namespace prefixes and default namespaces.
 *
 * @since 3.4.0
 */
final class EpubReader
{
    /**
     * libxml options: never fetch external resources, and keep entity
     * substitution off, so a hostile EPUB cannot mount an XXE or billion-laughs
     * attack through any of the XML documents we parse.
     */
    private const LIBXML_OPTIONS = LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING;

    /**
     * Read an EPUB from disk.
     *
     * @param string $filePath Absolute path to the EPUB
     *
     * @return EpubBook
     *
     * @throws RuntimeException When the archive or its package document is unreadable
     */
    public static function read(string $filePath): EpubBook
    {
        $archive = EpubArchive::open($filePath);

        try {
            return self::readArchive($archive);
        } finally {
            $archive->close();
        }
    }

    /**
     * Parse an already-open archive.
     *
     * @throws RuntimeException When the package document is missing or unparseable
     */
    private static function readArchive(EpubArchive $archive): EpubBook
    {
        $opfPath = self::locatePackageDocument($archive);
        $opfSource = $archive->read($opfPath);
        if ($opfSource === null) {
            throw new RuntimeException(
                "EPUB package document '{$opfPath}' is missing from the archive."
            );
        }

        $opf = self::parseXml($opfSource);
        if ($opf === null) {
            throw new RuntimeException(
                'EPUB package document could not be parsed as XML.'
            );
        }

        $xpath = new DOMXPath($opf);
        $baseDir = self::directoryOf($opfPath);

        $manifest = self::readManifest($xpath, $baseDir, $archive);
        $documents = self::readSpine($xpath, $manifest, $archive);
        $chapters = self::readTableOfContents($xpath, $manifest, $archive, $documents);

        return new EpubBook(
            self::firstMetadataValue($xpath, 'title'),
            self::metadataValues($xpath, 'creator'),
            self::firstMetadataValue($xpath, 'description'),
            self::firstMetadataValue($xpath, 'language'),
            $documents,
            $chapters
        );
    }

    /**
     * Resolve the OPF package document path from `META-INF/container.xml`.
     *
     * Falls back to scanning for a lone `.opf` entry when the container is
     * absent or malformed, which keeps slightly broken EPUBs importable.
     *
     * @throws RuntimeException When no package document can be located
     */
    private static function locatePackageDocument(EpubArchive $archive): string
    {
        $container = $archive->read('META-INF/container.xml');
        if ($container !== null) {
            $dom = self::parseXml($container);
            if ($dom !== null) {
                $xpath = new DOMXPath($dom);
                $nodes = $xpath->query('//*[local-name()="rootfile"]/@full-path');
                if ($nodes !== false && $nodes->length > 0) {
                    $path = trim($nodes->item(0)?->nodeValue ?? '');
                    if ($path !== '' && $archive->has($path)) {
                        return $archive->normalize($path);
                    }
                }
            }
        }

        foreach (['content.opf', 'OEBPS/content.opf', 'OPS/content.opf'] as $candidate) {
            if ($archive->has($candidate)) {
                return $archive->normalize($candidate);
            }
        }

        throw new RuntimeException(
            'EPUB has an invalid internal structure: no package document (.opf) could be located.'
        );
    }

    /**
     * Read the manifest into a map of item id => item details.
     *
     * @return array<string, array{href: string, mediaType: string, properties: string}>
     */
    private static function readManifest(DOMXPath $xpath, string $baseDir, EpubArchive $archive): array
    {
        $items = [];
        $nodes = $xpath->query('//*[local-name()="manifest"]/*[local-name()="item"]');
        if ($nodes === false) {
            return $items;
        }

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $id = $node->getAttribute('id');
            $href = $node->getAttribute('href');
            if ($id === '' || $href === '') {
                continue;
            }
            $items[$id] = [
                'href' => $archive->normalize($baseDir . rawurldecode($href)),
                'mediaType' => $node->getAttribute('media-type'),
                'properties' => $node->getAttribute('properties'),
            ];
        }

        return $items;
    }

    /**
     * Read the spine into content documents, in reading order.
     *
     * @param array<string, array{href: string, mediaType: string, properties: string}> $manifest
     *
     * @return list<EpubDocument>
     */
    private static function readSpine(DOMXPath $xpath, array $manifest, EpubArchive $archive): array
    {
        $documents = [];
        $nodes = $xpath->query('//*[local-name()="spine"]/*[local-name()="itemref"]');
        if ($nodes === false) {
            return $documents;
        }

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $idref = $node->getAttribute('idref');
            $item = $manifest[$idref] ?? null;
            if ($item === null) {
                continue;
            }
            $contents = $archive->read($item['href']);
            if ($contents === null) {
                continue;
            }
            $documents[] = new EpubDocument($item['href'], $contents);
        }

        return $documents;
    }

    /**
     * Build the chapter list from whichever table of contents the EPUB ships.
     *
     * @param array<string, array{href: string, mediaType: string, properties: string}> $manifest
     * @param list<EpubDocument>                                                        $documents
     *
     * @return list<EpubChapter>
     */
    private static function readTableOfContents(
        DOMXPath $xpath,
        array $manifest,
        EpubArchive $archive,
        array $documents
    ): array {
        $entries = self::readNcxEntries($manifest, $archive);
        if ($entries === []) {
            $entries = self::readNavEntries($manifest, $archive);
        }
        if ($entries === []) {
            return [];
        }

        // Index the spine documents so a TOC href resolves to real content.
        $byPath = [];
        foreach ($documents as $document) {
            $byPath[$document->getFilename()] = $document;
        }

        $chapters = [];
        $seen = [];
        foreach ($entries as [$label, $href]) {
            $document = $byPath[$href] ?? null;
            if ($document === null || isset($seen[$href])) {
                continue;
            }
            $seen[$href] = true;
            $chapters[] = new EpubChapter($label, $href, $document->getBody());
        }

        return $chapters;
    }

    /**
     * Read EPUB 2 NCX navigation points, in document order.
     *
     * @param array<string, array{href: string, mediaType: string, properties: string}> $manifest
     *
     * @return list<array{0: string, 1: string}> Label / resolved-href pairs
     */
    private static function readNcxEntries(array $manifest, EpubArchive $archive): array
    {
        $ncxPath = null;
        foreach ($manifest as $item) {
            if ($item['mediaType'] === 'application/x-dtbncx+xml') {
                $ncxPath = $item['href'];
                break;
            }
        }
        if ($ncxPath === null) {
            return [];
        }

        $source = $archive->read($ncxPath);
        if ($source === null) {
            return [];
        }
        $dom = self::parseXml($source);
        if ($dom === null) {
            return [];
        }

        $baseDir = self::directoryOf($ncxPath);
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//*[local-name()="navMap"]//*[local-name()="navPoint"]');
        if ($nodes === false) {
            return [];
        }

        $entries = [];
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $labels = $xpath->query('.//*[local-name()="navLabel"]/*[local-name()="text"]', $node);
            $targets = $xpath->query('.//*[local-name()="content"]/@src', $node);
            if ($labels === false || $targets === false || $targets->length === 0) {
                continue;
            }
            $label = trim($labels->item(0)?->textContent ?? '');
            $src = trim($targets->item(0)?->nodeValue ?? '');
            if ($src === '') {
                continue;
            }
            $entries[] = [$label, self::resolveHref($archive, $baseDir, $src)];
        }

        return $entries;
    }

    /**
     * Read EPUB 3 navigation-document TOC links, in document order.
     *
     * @param array<string, array{href: string, mediaType: string, properties: string}> $manifest
     *
     * @return list<array{0: string, 1: string}> Label / resolved-href pairs
     */
    private static function readNavEntries(array $manifest, EpubArchive $archive): array
    {
        $navPath = null;
        foreach ($manifest as $item) {
            if (preg_match('/\bnav\b/', $item['properties']) === 1) {
                $navPath = $item['href'];
                break;
            }
        }
        if ($navPath === null) {
            return [];
        }

        $source = $archive->read($navPath);
        if ($source === null) {
            return [];
        }
        $dom = self::parseHtml($source);
        if ($dom === null) {
            return [];
        }

        $baseDir = self::directoryOf($navPath);
        $xpath = new DOMXPath($dom);

        // Prefer the nav element explicitly typed as the TOC; fall back to the
        // first nav so that documents omitting epub:type still yield chapters.
        $navs = $xpath->query('//*[local-name()="nav"][contains(@*[local-name()="type"], "toc")]');
        if ($navs === false || $navs->length === 0) {
            $navs = $xpath->query('//*[local-name()="nav"]');
        }
        if ($navs === false || $navs->length === 0) {
            return [];
        }

        $nav = $navs->item(0);
        if (!$nav instanceof DOMElement) {
            return [];
        }

        $links = $xpath->query('.//*[local-name()="a"][@href]', $nav);
        if ($links === false) {
            return [];
        }

        $entries = [];
        foreach ($links as $link) {
            if (!$link instanceof DOMElement) {
                continue;
            }
            $href = trim($link->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#')) {
                continue;
            }
            $entries[] = [
                trim($link->textContent),
                self::resolveHref($archive, $baseDir, $href),
            ];
        }

        return $entries;
    }

    /**
     * Resolve a TOC href, dropping any fragment, against its document's directory.
     */
    private static function resolveHref(EpubArchive $archive, string $baseDir, string $href): string
    {
        $hashPosition = strpos($href, '#');
        if ($hashPosition !== false) {
            $href = substr($href, 0, $hashPosition);
        }

        return $archive->normalize($baseDir . rawurldecode($href));
    }

    /**
     * The first Dublin Core value for an element name, or null when absent.
     */
    private static function firstMetadataValue(DOMXPath $xpath, string $name): ?string
    {
        return self::metadataValues($xpath, $name)[0] ?? null;
    }

    /**
     * Every non-empty Dublin Core value for an element name, in document order.
     *
     * @return list<string>
     */
    private static function metadataValues(DOMXPath $xpath, string $name): array
    {
        $nodes = $xpath->query(
            '//*[local-name()="metadata"]/*[local-name()="' . $name . '"]'
        );
        if ($nodes === false) {
            return [];
        }

        $values = [];
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $value = trim($node->textContent);
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * The directory part of an archive path, with a trailing slash, or '' at the root.
     */
    private static function directoryOf(string $path): string
    {
        $slash = strrpos($path, '/');
        return $slash === false ? '' : substr($path, 0, $slash + 1);
    }

    /**
     * Parse XML, returning null instead of raising on malformed input.
     */
    private static function parseXml(string $source): ?DOMDocument
    {
        if (trim($source) === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $dom = new DOMDocument();
            $loaded = $dom->loadXML($source, self::LIBXML_OPTIONS);
            return $loaded ? $dom : null;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * Parse a navigation document, which may be XHTML or plain HTML.
     */
    private static function parseHtml(string $source): ?DOMDocument
    {
        $dom = self::parseXml($source);
        if ($dom !== null) {
            return $dom;
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $dom = new DOMDocument();
            $loaded = $dom->loadHTML(
                '<?xml encoding="UTF-8">' . $source,
                self::LIBXML_OPTIONS
            );
            return $loaded ? $dom : null;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}

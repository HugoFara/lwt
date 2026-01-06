<?php declare(strict_types=1);
/**
 * Article Content Extractor Service
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Feed\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Feed\Application\Services;

/**
 * Service for extracting text content from web articles.
 *
 * Provides HTML content extraction using XPath selectors,
 * charset detection, and content cleaning.
 *
 * @since 3.0.0
 */
class ArticleExtractor
{
    /**
     * Default filter tags to remove from extracted content.
     */
    private const DEFAULT_FILTER_TAGS = '//img | //script | //meta | //noscript | //link | //iframe';

    /**
     * Extract text content from feed article data.
     *
     * Handles various scenarios:
     * - Inline text from feed (description, content, encoded)
     * - Fetching full article from webpage
     * - Redirect handling for intermediate pages
     * - Charset detection and conversion
     * - XPath-based content extraction
     *
     * @param array<int|string, array{link: string, title: string, audio?: string, text?: string}> $feedData Array of feed items with link, title, etc.
     * @param string      $articleSection XPath selector(s) for article content
     * @param string      $filterTags     XPath selector(s) for elements to remove
     * @param string|null $charset        Override charset (null for auto-detect)
     *
     * @return array<int|string, array<string, mixed>> Extracted text data with 'error' key for failed extractions
     */
    public function extract(
        array $feedData,
        string $articleSection,
        string $filterTags = '',
        ?string $charset = null
    ): array {
        $data = [];

        foreach ($feedData as $key => $item) {
            $result = $this->extractSingle(
                $item,
                $articleSection,
                $filterTags,
                $charset
            );

            if ($result === null) {
                // Add error entry
                if (!isset($data['error']['message'])) {
                    $data['error']['message'] = '';
                }
                $errorMsg = $this->formatErrorMessage($item);
                $data['error']['message'] .= $errorMsg;
                $data['error']['link'][] = $item['link'];
            } else {
                $data[$key] = $result;
            }
        }

        return $data;
    }

    /**
     * Extract content from a single article.
     *
     * @param array{link: string, title: string, audio?: string, text?: string} $item Feed item data
     * @param string      $articleSection XPath selector(s)
     * @param string      $filterTags     Filter selectors
     * @param string|null $charset        Override charset
     *
     * @return array{TxTitle: string, TxAudioURI: string, TxText: string, TxSourceURI: string}|null Extracted data or null on failure
     */
    private function extractSingle(
        array $item,
        string $articleSection,
        string $filterTags,
        ?string $charset
    ): ?array {
        $data = [
            'TxTitle' => $item['title'],
            'TxAudioURI' => $item['audio'] ?? '',
            'TxText' => '',
            'TxSourceURI' => '',
        ];

        // Handle redirect article sections
        $effectiveSection = $articleSection;
        $link = $item['link'];

        if (str_starts_with($articleSection, 'redirect:')) {
            $link = $this->handleRedirect($link, $articleSection, $effectiveSection);
        }

        // Determine source and get HTML content
        $hasInlineText = isset($item['text']) && $item['text'] !== '';

        if ($hasInlineText) {
            $data['TxSourceURI'] = $this->processInlineLink($link);
            $htmlString = $this->prepareInlineHtml($item['text']);
        } else {
            $data['TxSourceURI'] = $link;
            $htmlString = $this->fetchArticleContent($link, $charset);
        }

        if ($htmlString === '') {
            return null;
        }

        // Convert line breaks
        $htmlString = $this->convertLineBreaks($htmlString);

        // Parse HTML
        $dom = $this->parseHtml($htmlString);

        // Build filter tags list
        $filterTagsList = $this->buildFilterTagsList($filterTags);

        // Check for 'new' article mode (returns full HTML)
        foreach (explode('!?!', $effectiveSection) as $tag) {
            if ($tag === 'new') {
                return $this->extractNewArticleHtml($dom, $filterTagsList);
            }
        }

        // Standard extraction with XPath
        $text = $this->extractWithXPath(
            $dom,
            $effectiveSection,
            $filterTagsList,
            $hasInlineText
        );

        if ($text === '') {
            return null;
        }

        $data['TxText'] = $this->cleanExtractedText($text);

        return $data['TxText'] !== '' ? $data : null;
    }

    /**
     * Handle redirect article section to find actual article URL.
     *
     * @param string $link           Original link
     * @param string $articleSection Full article section string
     * @param string &$newSection    Output: updated article section
     *
     * @return string Updated link
     */
    private function handleRedirect(
        string $link,
        string $articleSection,
        string &$newSection
    ): string {
        $dom = new \DOMDocument();
        $htmlString = @file_get_contents(trim($link));
        if ($htmlString === false) {
            return $link;
        }

        @$dom->loadHTML($htmlString);
        $xPath = new \DOMXPath($dom);

        $redirect = explode(' | ', $articleSection, 2);
        $newSection = $redirect[1] ?? '';
        $redirectSelector = substr($redirect[0], 9); // Remove 'redirect:' prefix
        $feedHost = parse_url(trim($link));

        foreach ($xPath->query($redirectSelector) as $node) {
            if (
                !$node instanceof \DOMElement
                || empty(trim($node->localName))
                || $node->nodeType === XML_TEXT_NODE
                || !$node->hasAttributes()
            ) {
                continue;
            }

            foreach ($node->attributes as $attr) {
                if ($attr->name === 'href') {
                    $link = $attr->value;
                    if (str_starts_with($link, '..')) {
                        $link = 'http://' . ($feedHost['host'] ?? 'localhost') .
                            substr($link, 2);
                    }
                }
            }
        }

        return $link;
    }

    /**
     * Fetch article content from URL with charset detection.
     *
     * @param string      $url     Article URL
     * @param string|null $charset Override charset (null for auto-detect)
     *
     * @return string HTML content
     */
    public function fetchArticleContent(string $url, ?string $charset = null): string
    {
        $context = stream_context_create([
            'http' => [
                'follow_location' => true,
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; LWT Feed Reader)',
            ],
        ]);

        $htmlString = @file_get_contents(trim($url), false, $context);

        if ($htmlString === false || $htmlString === '') {
            return '';
        }

        $encoding = $this->detectCharset($url, $htmlString, $charset);
        $convertedCharset = $this->mapWindowsCharset($encoding);

        $htmlString = '<meta http-equiv="Content-Type" content="text/html; charset=' .
            $convertedCharset . '">' . $htmlString;

        if ($encoding !== $convertedCharset) {
            $converted = @iconv($encoding, 'utf-8//IGNORE', $htmlString);
            return $converted !== false ? $converted : $htmlString;
        }

        return mb_convert_encoding($htmlString, 'HTML-ENTITIES', $encoding);
    }

    /**
     * Detect charset from HTTP headers, meta tags, or content.
     *
     * @param string      $url        URL being fetched
     * @param string      $htmlString HTML content
     * @param string|null $override   Override charset
     *
     * @return string Detected charset
     */
    public function detectCharset(string $url, string $htmlString, ?string $override = null): string
    {
        if ($override !== null && $override !== '' && $override !== 'meta') {
            return $override;
        }

        // Try HTTP headers first
        $charset = $this->detectCharsetFromHeaders($url);
        if ($charset !== null) {
            return $charset;
        }

        // Try meta tags
        $charset = $this->detectCharsetFromMeta($htmlString);
        if ($charset !== null) {
            return $charset;
        }

        // Fallback to detection
        $detected = mb_detect_encoding(
            $htmlString,
            ['ASCII', 'UTF-8', 'ISO-8859-1', 'windows-1252', 'iso-8859-15'],
            true
        );

        return $detected !== false ? $detected : 'UTF-8';
    }

    /**
     * Detect charset from HTTP headers.
     *
     * @param string $url URL to check
     *
     * @return string|null Charset or null if not found
     */
    private function detectCharsetFromHeaders(string $url): ?string
    {
        $header = @get_headers(trim($url), true);
        if ($header === false) {
            return null;
        }

        foreach ($header as $k => $v) {
            if (strtolower($k) !== 'content-type') {
                continue;
            }

            $contentType = is_array($v) ? (string)end($v) : $v;
            $pos = strpos($contentType, 'charset=');
            if ($pos !== false && strpos($contentType, 'text/html;') !== false) {
                return substr($contentType, $pos + 8);
            }
        }

        return null;
    }

    /**
     * Detect charset from HTML meta tags.
     *
     * @param string $htmlString HTML content
     *
     * @return string|null Charset or null if not found
     */
    private function detectCharsetFromMeta(string $htmlString): ?string
    {
        $doc = new \DOMDocument();
        $previousValue = libxml_use_internal_errors(true);
        @$doc->loadHTML($htmlString);
        libxml_clear_errors();
        libxml_use_internal_errors($previousValue);

        $nodes = $doc->getElementsByTagName('meta');

        // Check content-type meta
        foreach ($nodes as $node) {
            $len = $node->attributes->length;
            for ($i = 0; $i < $len; $i++) {
                if ($node->attributes->item($i)->name === 'content') {
                    $pos = strpos($node->attributes->item($i)->value, 'charset=');
                    if ($pos !== false) {
                        return substr($node->attributes->item($i)->value, $pos + 8);
                    }
                }
            }
        }

        // Check charset meta
        foreach ($nodes as $node) {
            $len = $node->attributes->length;
            if ($len === 1 && $node->attributes->item(0)->name === 'charset') {
                return $node->attributes->item(0)->value;
            }
        }

        return null;
    }

    /**
     * Map Windows charset to UTF-8 locale equivalent.
     *
     * @param string $charset Input charset
     *
     * @return string Mapped charset
     */
    public function mapWindowsCharset(string $charset): string
    {
        $mapping = [
            'windows-1253' => 'el_GR.utf8',
            'windows-1254' => 'tr_TR.utf8',
            'windows-1255' => 'he.utf8',
            'windows-1256' => 'ar_AE.utf8',
            'windows-1258' => 'vi_VI.utf8',
            'windows-874' => 'th_TH.utf8',
        ];

        return $mapping[$charset] ?? $charset;
    }

    /**
     * Process inline link (handle # prefix for feed link references).
     *
     * @param string $link Original link
     *
     * @return string Processed link
     */
    private function processInlineLink(string $link): string
    {
        return trim($link);
    }

    /**
     * Prepare inline HTML content.
     *
     * @param string $text Inline text from feed
     *
     * @return string Prepared HTML
     */
    private function prepareInlineHtml(string $text): string
    {
        return str_replace(
            ['>', '<'],
            ['> ', ' <'],
            $text
        );
    }

    /**
     * Convert HTML line break tags to newlines.
     *
     * @param string $html HTML content
     *
     * @return string Converted content
     */
    private function convertLineBreaks(string $html): string
    {
        return str_replace(
            ['<br />', '<br>', '</br>', '</h', '</p'],
            ["\n", "\n", '', "\n</h", "\n</p"],
            $html
        );
    }

    /**
     * Parse HTML into DOMDocument.
     *
     * @param string $htmlString HTML content
     *
     * @return \DOMDocument Parsed document
     */
    private function parseHtml(string $htmlString): \DOMDocument
    {
        $dom = new \DOMDocument();
        $previousValue = libxml_use_internal_errors(true);

        $dom->loadHTML('<?xml encoding="UTF-8">' . $htmlString);

        // Remove XML processing instruction hack
        foreach ($dom->childNodes as $item) {
            if ($item->nodeType === XML_PI_NODE) {
                $dom->removeChild($item);
            }
        }
        $dom->encoding = 'UTF-8';

        libxml_clear_errors();
        libxml_use_internal_errors($previousValue);

        return $dom;
    }

    /**
     * Build filter tags list from string.
     *
     * @param string $filterTags Additional filter tags
     *
     * @return array Filter tags array
     */
    private function buildFilterTagsList(string $filterTags): array
    {
        $combined = self::DEFAULT_FILTER_TAGS;
        if ($filterTags !== '') {
            $combined .= '!?!' . $filterTags;
        }

        return explode('!?!', rtrim($combined, '!?!'));
    }

    /**
     * Extract content using XPath selectors.
     *
     * @param \DOMDocument  $dom            DOM document
     * @param string        $articleSection Article selectors
     * @param array<string> $filterTags     Filter selectors
     * @param bool          $isInlineText   Whether source is inline text
     *
     * @return string Extracted text
     */
    private function extractWithXPath(
        \DOMDocument $dom,
        string $articleSection,
        array $filterTags,
        bool $isInlineText
    ): string {
        $selector = new \DOMXPath($dom);

        // Remove filtered elements
        foreach ($filterTags as $filterTag) {
            $nodes = @$selector->query(trim($filterTag));
            if ($nodes === false) {
                continue;
            }
            foreach ($nodes as $node) {
                if ($node instanceof \DOMNode && $node->parentNode !== null) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        $text = '';

        // Extract text from article sections
        $articleTags = explode('!?!', $articleSection);

        // Skip redirect prefix tag
        if (str_starts_with($articleSection, 'redirect:')) {
            unset($articleTags[0]);
        }

        foreach ($articleTags as $articleTag) {
            $queryResult = @$selector->query(trim($articleTag));
            if ($queryResult === false) {
                continue;
            }

            foreach ($queryResult as $textNode) {
                $nodeValue = $textNode->nodeValue;
                if ($nodeValue !== null && $nodeValue !== '') {
                    if ($isInlineText) {
                        $text .= mb_convert_encoding(
                            $nodeValue,
                            'HTML-ENTITIES',
                            'UTF-8'
                        );
                    } else {
                        $text .= $nodeValue;
                    }
                }
            }
        }

        if ($isInlineText) {
            $text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
        }

        return $text;
    }

    /**
     * Extract full HTML for 'new' article mode.
     *
     * @param \DOMDocument  $dom        DOM document
     * @param array<string> $filterTags Tags to filter out
     *
     * @return array{TxText: string} Result with TxText containing cleaned HTML
     */
    private function extractNewArticleHtml(\DOMDocument $dom, array $filterTags): array
    {
        foreach ($filterTags as $filterTag) {
            // For new mode, use tag names not XPath
            $tagName = trim($filterTag);
            if (str_starts_with($tagName, '//')) {
                $tagName = substr($tagName, 2);
            }
            if (strpos($tagName, ' ') !== false || strpos($tagName, '|') !== false) {
                continue; // Skip complex selectors
            }

            $nodes = $dom->getElementsByTagName($tagName);
            $toRemove = [];
            foreach ($nodes as $domElement) {
                $toRemove[] = $domElement;
            }
            foreach ($toRemove as $node) {
                if ($node->parentNode !== null) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        // Remove onclick attributes
        $nodes = $dom->getElementsByTagName('*');
        foreach ($nodes as $node) {
            $node->removeAttribute('onclick');
        }

        $html = $dom->saveHTML($dom);

        $html = preg_replace(
            ['/\<html[^\>]*\>/', '/\<body\>/'],
            ['', ''],
            $html
        );

        return [
            'TxTitle' => '',
            'TxText' => $html,
            'TxSourceURI' => '',
            'TxAudioURI' => '',
        ];
    }

    /**
     * Clean extracted text.
     *
     * @param string $text Raw extracted text
     *
     * @return string Cleaned text
     */
    private function cleanExtractedText(string $text): string
    {
        return trim(preg_replace(
            ['/[\r\t]+/', '/(\n)[\s^\n]*\n[\s]*/', '/\ \ +/'],
            [' ', '$1$1', ' '],
            $text
        ));
    }

    /**
     * Format error message for failed extraction.
     *
     * @param array{link: string, title: string, audio?: string, text?: string} $item Feed item
     *
     * @return string Error message HTML
     */
    private function formatErrorMessage(array $item): string
    {
        return '"<a href="' . htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') .
            '" data-action="open-window" data-window-name="child">' .
            htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') .
            '</a>" has no text section!<br />';
    }
}

<?php

/**
 * One (X)HTML content document from an EPUB spine.
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

/**
 * One (X)HTML content document from an EPUB spine.
 *
 * Replaces the `EpubHtml` model previously supplied by `kiwilan/php-ebook`.
 *
 * @since 3.4.0
 */
final class EpubDocument
{
    private string $filename;

    private string $content;

    private ?string $body = null;

    /**
     * @param string $filename Entry path inside the archive
     * @param string $content  Raw document markup
     */
    public function __construct(string $filename, string $content)
    {
        $this->filename = $filename;
        $this->content = $content;
    }

    /**
     * Entry path of this document inside the archive.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * The raw, unmodified document markup.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * The inner markup of the document's `<body>` element.
     *
     * Falls back to the whole document when no `<body>` is present, which is
     * what fragments produced by some authoring tools look like.
     *
     * @return string Body markup, empty string when the document is empty
     */
    public function getBody(): string
    {
        if ($this->body !== null) {
            return $this->body;
        }

        $matches = [];
        if (preg_match('/<body\b[^>]*>(.*)<\/body>/is', $this->content, $matches) === 1) {
            $this->body = $matches[1];
        } else {
            $this->body = $this->content;
        }

        return $this->body;
    }
}

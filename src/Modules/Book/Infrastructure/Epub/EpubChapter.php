<?php

/**
 * One table-of-contents entry resolved to its content document.
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
 * One table-of-contents entry resolved to its content document.
 *
 * Replaces the `EpubChapter` model previously supplied by `kiwilan/php-ebook`.
 *
 * @since 3.4.0
 */
final class EpubChapter
{
    private string $label;

    private string $filename;

    private string $content;

    /**
     * @param string $label    Human-readable chapter title from the TOC
     * @param string $filename Entry path of the backing content document
     * @param string $content  Body markup of the backing content document
     */
    public function __construct(string $label, string $filename, string $content)
    {
        $this->label = $label;
        $this->filename = $filename;
        $this->content = $content;
    }

    /**
     * Human-readable chapter title as it appeared in the table of contents.
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Entry path of the backing content document.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Body markup of the backing content document.
     */
    public function getContent(): string
    {
        return $this->content;
    }
}

<?php

/**
 * A parsed EPUB: metadata, spine documents and table-of-contents chapters.
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
 * A parsed EPUB: metadata, spine documents and table-of-contents chapters.
 *
 * Replaces the `Ebook` + `EpubModule` pair previously supplied by
 * `kiwilan/php-ebook`, narrowed to the EPUB format and to the members LWT
 * actually consumes.
 *
 * @since 3.4.0
 */
final class EpubBook
{
    private ?string $title;

    /** @var list<string> */
    private array $authors;

    private ?string $description;

    private ?string $language;

    /** @var list<EpubDocument> */
    private array $documents;

    /** @var list<EpubChapter> */
    private array $chapters;

    /**
     * @param string|null      $title       Title from `dc:title`
     * @param list<string>     $authors     Names from `dc:creator`, document order
     * @param string|null      $description Description from `dc:description`
     * @param string|null      $language    Language tag from `dc:language`
     * @param list<EpubDocument> $documents Spine documents, reading order
     * @param list<EpubChapter>  $chapters  TOC chapters, reading order
     */
    public function __construct(
        ?string $title,
        array $authors,
        ?string $description,
        ?string $language,
        array $documents,
        array $chapters
    ) {
        $this->title = $title;
        $this->authors = $authors;
        $this->description = $description;
        $this->language = $language;
        $this->documents = $documents;
        $this->chapters = $chapters;
    }

    /**
     * Title from `dc:title`, or null when the package declares none.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * All `dc:creator` names in document order.
     *
     * @return list<string>
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * The first `dc:creator` name, or null when the package declares none.
     */
    public function getAuthorMain(): ?string
    {
        return $this->authors[0] ?? null;
    }

    /**
     * Description from `dc:description`, or null when absent.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Language tag from `dc:language`, or null when absent.
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * Spine documents in reading order.
     *
     * @return list<EpubDocument>
     */
    public function getHtml(): array
    {
        return $this->documents;
    }

    /**
     * Table-of-contents chapters in reading order.
     *
     * Empty when the EPUB ships no usable NCX or EPUB 3 nav document, in which
     * case callers should fall back to {@see getHtml()}.
     *
     * @return list<EpubChapter>
     */
    public function getChapters(): array
    {
        return $this->chapters;
    }
}

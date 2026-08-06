<?php

/**
 * Read-only accessor over the ZIP container of an EPUB.
 *
 * PHP version 8.2
 *
 * @category Lwt
 * @package  Lwt\Modules\Book\Infrastructure\Epub
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.4.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Book\Infrastructure\Epub;

use RuntimeException;
use ZipArchive;

/**
 * Read-only accessor over the ZIP container of an EPUB.
 *
 * An EPUB is a ZIP archive with a prescribed internal layout. This class owns
 * the archive handle and nothing else: locating and interpreting the entries is
 * {@see EpubReader}'s job.
 *
 * Entry lookups are case-insensitive on the stored path, because real EPUBs in
 * the wild disagree about the casing of `META-INF/container.xml`.
 *
 * @since 3.4.0
 */
final class EpubArchive
{
    private ZipArchive $zip;

    /**
     * Lowercased entry name => actual entry name, for case-insensitive lookup.
     *
     * @var array<string, string>
     */
    private array $index = [];

    private function __construct(ZipArchive $zip)
    {
        $this->zip = $zip;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false || str_ends_with($name, '/')) {
                continue;
            }
            $this->index[strtolower($name)] = $name;
        }
    }

    /**
     * Open an EPUB archive for reading.
     *
     * @param string $filePath Absolute path to the EPUB on disk
     *
     * @return self
     *
     * @throws RuntimeException When the file is not a readable ZIP archive
     */
    public static function open(string $filePath): self
    {
        if (!extension_loaded('zip')) {
            throw new RuntimeException(
                "The 'zip' PHP extension is required to read EPUB files."
            );
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException(
                'EPUB file could not be read as a ZIP archive. The file may be corrupted.'
            );
        }

        return new self($zip);
    }

    /**
     * Read one entry's contents.
     *
     * @param string $path Entry path inside the archive
     *
     * @return string|null Contents, or null when the entry is absent or unreadable
     */
    public function read(string $path): ?string
    {
        $actual = $this->index[strtolower($this->normalize($path))] ?? null;
        if ($actual === null) {
            return null;
        }

        $contents = $this->zip->getFromName($actual);
        return $contents === false ? null : $contents;
    }

    /**
     * Whether an entry exists in the archive.
     */
    public function has(string $path): bool
    {
        return isset($this->index[strtolower($this->normalize($path))]);
    }

    /**
     * Collapse `.` / `..` segments and strip a leading slash from a ZIP path.
     *
     * EPUB hrefs are relative to the document that references them, so resolving
     * them produces paths like `OEBPS/text/../images/cover.jpg` that must be
     * flattened before they will match a stored entry name.
     */
    public function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        $out = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($out);
                continue;
            }
            $out[] = $segment;
        }

        return implode('/', $out);
    }

    /**
     * Close the underlying archive handle.
     */
    public function close(): void
    {
        $this->zip->close();
    }
}

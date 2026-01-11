<?php

declare(strict_types=1);

/**
 * Dictionary Importer Interface
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Dictionary\Infrastructure\Import
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Dictionary\Infrastructure\Import;

/**
 * Interface for dictionary importers.
 *
 * Defines the contract for parsing dictionary files and yielding entries.
 *
 * @since 3.0.0
 */
interface ImporterInterface
{
    /**
     * Parse a dictionary file and yield entries.
     *
     * @param string               $filePath Path to the dictionary file
     * @param array<string, mixed> $options  Import options (format-specific)
     *
     * @return iterable<array{term: string, definition: string, reading?: ?string, pos?: ?string}>
     */
    public function parse(string $filePath, array $options = []): iterable;

    /**
     * Get the supported file extensions for this importer.
     *
     * @return string[]
     */
    public function getSupportedExtensions(): array;

    /**
     * Validate that a file can be imported.
     *
     * @param string $filePath Path to the file
     *
     * @return bool True if the file can be imported
     */
    public function canImport(string $filePath): bool;

    /**
     * Get a preview of the first N entries.
     *
     * @param string               $filePath Path to the dictionary file
     * @param int                  $limit    Number of entries to preview
     * @param array<string, mixed> $options  Import options
     *
     * @return array<array{term: string, definition: string, reading?: ?string, pos?: ?string}>
     */
    public function preview(string $filePath, int $limit = 10, array $options = []): array;
}

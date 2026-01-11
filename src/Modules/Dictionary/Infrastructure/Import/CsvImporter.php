<?php

/**
 * CSV Dictionary Importer
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

declare(strict_types=1);

namespace Lwt\Modules\Dictionary\Infrastructure\Import;

use Generator;
use RuntimeException;

/**
 * Importer for CSV/TSV dictionary files.
 *
 * Supports configurable column mapping and delimiters.
 *
 * @since 3.0.0
 */
class CsvImporter implements ImporterInterface
{
    /**
     * Default column mapping.
     */
    private const DEFAULT_COLUMN_MAP = [
        'term' => 0,
        'definition' => 1,
        'reading' => null,
        'pos' => null,
    ];

    /**
     * {@inheritdoc}
     */
    public function parse(string $filePath, array $options = []): iterable
    {
        $this->validateFile($filePath);

        /** @var string $delimiter */
        $delimiter = $options['delimiter'] ?? ',';
        /** @var bool $hasHeader */
        $hasHeader = $options['hasHeader'] ?? true;
        /** @var array<string, int> $columnMap */
        $columnMap = $options['columnMap'] ?? self::DEFAULT_COLUMN_MAP;
        /** @var string $encoding */
        $encoding = $options['encoding'] ?? 'UTF-8';

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Cannot open file: $filePath");
        }

        try {
            $lineNumber = 0;

            // Skip header if present
            if ($hasHeader === true) {
                fgetcsv($handle, 0, $delimiter);
                $lineNumber++;
            }

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $lineNumber++;

                // Skip empty rows
                if (count($row) === 0 || (count($row) === 1 && trim($row[0]) === '')) {
                    continue;
                }

                $entry = $this->mapRowToEntry($row, $columnMap, $encoding);
                if ($entry !== null) {
                    yield $entry;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedExtensions(): array
    {
        return ['csv', 'tsv', 'txt'];
    }

    /**
     * {@inheritdoc}
     */
    public function canImport(string $filePath): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($extension, $this->getSupportedExtensions(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function preview(string $filePath, int $limit = 10, array $options = []): array
    {
        $entries = [];
        $count = 0;

        foreach ($this->parse($filePath, $options) as $entry) {
            $entries[] = $entry;
            $count++;

            if ($count >= $limit) {
                break;
            }
        }

        return $entries;
    }

    /**
     * Detect the delimiter used in a CSV file.
     *
     * @param string $filePath Path to the file
     *
     * @return string Detected delimiter
     */
    public function detectDelimiter(string $filePath): string
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return ',';
        }

        $firstLine = fgets($handle);
        fclose($handle);

        if ($firstLine === false) {
            return ',';
        }

        $delimiters = ["\t", ',', ';', '|'];
        $counts = [];

        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = substr_count($firstLine, $delimiter);
        }

        // Return delimiter with highest count
        arsort($counts);
        $detected = array_key_first($counts);

        return $counts[$detected] > 0 ? $detected : ',';
    }

    /**
     * Detect column headers from the first row.
     *
     * @param string $filePath  Path to the file
     * @param string $delimiter Delimiter to use
     *
     * @return string[] Column headers
     */
    public function detectHeaders(string $filePath, string $delimiter = ','): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }

        $headers = fgetcsv($handle, 0, $delimiter);
        fclose($handle);

        return $headers ?: [];
    }

    /**
     * Suggest column mapping based on header names.
     *
     * @param string[] $headers Column headers
     *
     * @return array<string, int|null> Suggested column mapping
     */
    public function suggestColumnMap(array $headers): array
    {
        $columnMap = [
            'term' => null,
            'definition' => null,
            'reading' => null,
            'pos' => null,
        ];

        $termPatterns = ['term', 'word', 'headword', 'entry', 'lemma', 'source'];
        $defPatterns = ['definition', 'meaning', 'translation', 'gloss', 'target', 'def'];
        $readingPatterns = ['reading', 'pronunciation', 'phonetic', 'furigana', 'pinyin', 'kana'];
        $posPatterns = ['pos', 'part of speech', 'partofspeech', 'category', 'type', 'class'];

        foreach ($headers as $index => $header) {
            $headerLower = strtolower(trim($header));
            $intIndex = (int)$index;

            if ($columnMap['term'] === null) {
                foreach ($termPatterns as $pattern) {
                    if (str_contains($headerLower, $pattern)) {
                        $columnMap['term'] = $intIndex;
                        break;
                    }
                }
            }

            if ($columnMap['definition'] === null) {
                foreach ($defPatterns as $pattern) {
                    if (str_contains($headerLower, $pattern)) {
                        $columnMap['definition'] = $intIndex;
                        break;
                    }
                }
            }

            if ($columnMap['reading'] === null) {
                foreach ($readingPatterns as $pattern) {
                    if (str_contains($headerLower, $pattern)) {
                        $columnMap['reading'] = $intIndex;
                        break;
                    }
                }
            }

            if ($columnMap['pos'] === null) {
                foreach ($posPatterns as $pattern) {
                    if (str_contains($headerLower, $pattern)) {
                        $columnMap['pos'] = $intIndex;
                        break;
                    }
                }
            }
        }

        // Default to first two columns if not detected
        if ($columnMap['term'] === null && count($headers) > 0) {
            $columnMap['term'] = 0;
        }
        if ($columnMap['definition'] === null && count($headers) > 1) {
            $columnMap['definition'] = 1;
        }

        return $columnMap;
    }

    /**
     * Validate that the file exists and is readable.
     *
     * @param string $filePath Path to the file
     *
     * @return void
     *
     * @throws RuntimeException If file is invalid
     */
    private function validateFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File not found: $filePath");
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException("File is not readable: $filePath");
        }
    }

    /**
     * Map a CSV row to an entry array.
     *
     * @param string[]             $row       CSV row data
     * @param array<string, mixed> $columnMap Column mapping
     * @param string               $encoding  Source encoding
     *
     * @return array{term: string, definition: string, reading?: ?string, pos?: ?string}|null
     */
    private function mapRowToEntry(array $row, array $columnMap, string $encoding): ?array
    {
        /** @var int $termIndex */
        $termIndex = $columnMap['term'] ?? 0;
        /** @var int $defIndex */
        $defIndex = $columnMap['definition'] ?? 1;
        /** @var int|null $readingIndex */
        $readingIndex = $columnMap['reading'] ?? null;
        /** @var int|null $posIndex */
        $posIndex = $columnMap['pos'] ?? null;

        // Validate required columns exist
        if (!isset($row[$termIndex]) || !isset($row[$defIndex])) {
            return null;
        }

        $term = $this->convertEncoding(trim($row[$termIndex]), $encoding);
        $definition = $this->convertEncoding(trim($row[$defIndex]), $encoding);

        // Skip empty entries
        if ($term === '' || $definition === '') {
            return null;
        }

        $entry = [
            'term' => $term,
            'definition' => $definition,
        ];

        if ($readingIndex !== null && isset($row[$readingIndex])) {
            $reading = $this->convertEncoding(trim($row[$readingIndex]), $encoding);
            if ($reading !== '') {
                $entry['reading'] = $reading;
            }
        }

        if ($posIndex !== null && isset($row[$posIndex])) {
            $pos = $this->convertEncoding(trim($row[$posIndex]), $encoding);
            if ($pos !== '') {
                $entry['pos'] = $pos;
            }
        }

        return $entry;
    }

    /**
     * Convert string encoding to UTF-8.
     *
     * @param string $string   String to convert
     * @param string $encoding Source encoding
     *
     * @return string UTF-8 encoded string
     */
    private function convertEncoding(string $string, string $encoding): string
    {
        if ($encoding === 'UTF-8' || $encoding === 'utf-8') {
            return $string;
        }

        $converted = mb_convert_encoding($string, 'UTF-8', $encoding);
        return $converted !== false ? $converted : $string;
    }
}

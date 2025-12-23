<?php declare(strict_types=1);
/**
 * JSON Dictionary Importer
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services\DictionaryImport
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Services\DictionaryImport;

use Generator;
use RuntimeException;

/**
 * Importer for JSON dictionary files.
 *
 * Supports arrays of entries or objects with term keys.
 *
 * @since 3.0.0
 */
class JsonImporter implements ImporterInterface
{
    /**
     * Default field mapping for JSON entries.
     */
    private const DEFAULT_FIELD_MAP = [
        'term' => ['term', 'word', 'headword', 'entry', 'lemma'],
        'definition' => ['definition', 'meaning', 'translation', 'gloss', 'def'],
        'reading' => ['reading', 'pronunciation', 'phonetic', 'furigana', 'pinyin'],
        'pos' => ['pos', 'partOfSpeech', 'part_of_speech', 'category'],
    ];

    /**
     * {@inheritdoc}
     */
    public function parse(string $filePath, array $options = []): iterable
    {
        $this->validateFile($filePath);

        $fieldMap = $options['fieldMap'] ?? null;

        // Try streaming for large files
        $fileSize = filesize($filePath);
        if ($fileSize !== false && $fileSize > 10 * 1024 * 1024) {
            // > 10MB, use streaming parser
            yield from $this->parseStreaming($filePath, $fieldMap);
        } else {
            yield from $this->parseSimple($filePath, $fieldMap);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedExtensions(): array
    {
        return ['json'];
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
        if ($extension !== 'json') {
            return false;
        }

        // Quick validation: check if file starts with [ or {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return false;
        }

        $start = fread($handle, 1024);
        fclose($handle);

        if ($start === false) {
            return false;
        }

        $start = ltrim($start);
        return str_starts_with($start, '[') || str_starts_with($start, '{');
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
     * Detect the structure of a JSON file.
     *
     * @param string $filePath Path to the file
     *
     * @return array{type: string, fieldNames: string[]} Structure info
     */
    public function detectStructure(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return ['type' => 'unknown', 'fieldNames' => []];
        }

        $content = fread($handle, 65536); // Read first 64KB
        fclose($handle);

        if ($content === false) {
            return ['type' => 'unknown', 'fieldNames' => []];
        }

        $content = ltrim($content);

        if (str_starts_with($content, '[')) {
            // Array of entries
            $data = json_decode($content, true);
            if (is_array($data) && !empty($data) && is_array($data[0])) {
                return [
                    'type' => 'array',
                    'fieldNames' => array_keys($data[0]),
                ];
            }
        } elseif (str_starts_with($content, '{')) {
            // Object with term keys
            $data = json_decode($content, true);
            if (is_array($data)) {
                $firstKey = array_key_first($data);
                if ($firstKey !== null && is_array($data[$firstKey])) {
                    return [
                        'type' => 'object',
                        'fieldNames' => array_keys($data[$firstKey]),
                    ];
                }
            }
        }

        return ['type' => 'unknown', 'fieldNames' => []];
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
     * Parse JSON file by loading it entirely into memory.
     *
     * @param string                   $filePath Path to the file
     * @param array<string, string>|null $fieldMap Custom field mapping
     *
     * @return Generator<array{term: string, definition: string, reading?: ?string, pos?: ?string}>
     */
    private function parseSimple(string $filePath, ?array $fieldMap): Generator
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException("Cannot read file: $filePath");
        }

        $data = json_decode($content, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON: " . json_last_error_msg());
        }

        if (is_array($data)) {
            // Check if it's an array of entries or object with term keys
            if (array_is_list($data)) {
                // Array of entries: [{"term": "...", "definition": "..."}, ...]
                foreach ($data as $item) {
                    if (is_array($item)) {
                        $entry = $this->mapItemToEntry($item, $fieldMap);
                        if ($entry !== null) {
                            yield $entry;
                        }
                    }
                }
            } else {
                // Object with term keys: {"term1": {"definition": "..."}, ...}
                foreach ($data as $term => $value) {
                    $entry = $this->mapObjectEntryToEntry((string) $term, $value, $fieldMap);
                    if ($entry !== null) {
                        yield $entry;
                    }
                }
            }
        }
    }

    /**
     * Parse JSON file using streaming for large files.
     *
     * This is a simplified streaming approach that works for JSON arrays.
     *
     * @param string                   $filePath Path to the file
     * @param array<string, string>|null $fieldMap Custom field mapping
     *
     * @return Generator<array{term: string, definition: string, reading?: ?string, pos?: ?string}>
     */
    private function parseStreaming(string $filePath, ?array $fieldMap): Generator
    {
        // For very large files, fall back to simple parsing
        // A proper streaming JSON parser would require a library like JsonMachine
        yield from $this->parseSimple($filePath, $fieldMap);
    }

    /**
     * Map a JSON item (array entry) to a dictionary entry.
     *
     * @param array<string, mixed>       $item     JSON item
     * @param array<string, string>|null $fieldMap Custom field mapping
     *
     * @return array{term: string, definition: string, reading?: ?string, pos?: ?string}|null
     */
    private function mapItemToEntry(array $item, ?array $fieldMap): ?array
    {
        $term = $this->findField($item, 'term', $fieldMap);
        $definition = $this->findField($item, 'definition', $fieldMap);

        if ($term === null || $definition === null) {
            return null;
        }

        $entry = [
            'term' => (string) $term,
            'definition' => (string) $definition,
        ];

        $reading = $this->findField($item, 'reading', $fieldMap);
        if ($reading !== null && $reading !== '') {
            $entry['reading'] = (string) $reading;
        }

        $pos = $this->findField($item, 'pos', $fieldMap);
        if ($pos !== null && $pos !== '') {
            $entry['pos'] = (string) $pos;
        }

        return $entry;
    }

    /**
     * Map an object entry (term => data) to a dictionary entry.
     *
     * @param string                     $term     The term (object key)
     * @param mixed                      $value    The entry data
     * @param array<string, string>|null $fieldMap Custom field mapping
     *
     * @return array{term: string, definition: string, reading?: ?string, pos?: ?string}|null
     */
    private function mapObjectEntryToEntry(string $term, mixed $value, ?array $fieldMap): ?array
    {
        if ($term === '') {
            return null;
        }

        if (is_string($value)) {
            // Simple: {"term": "definition"}
            return [
                'term' => $term,
                'definition' => $value,
            ];
        }

        if (is_array($value)) {
            // Complex: {"term": {"definition": "...", ...}}
            $definition = $this->findField($value, 'definition', $fieldMap);
            if ($definition === null) {
                // Try 'meaning' or first string value
                $definition = $value['meaning'] ?? $value['gloss'] ?? null;
                if ($definition === null && !empty($value)) {
                    foreach ($value as $v) {
                        if (is_string($v)) {
                            $definition = $v;
                            break;
                        }
                    }
                }
            }

            if ($definition === null) {
                return null;
            }

            $entry = [
                'term' => $term,
                'definition' => (string) $definition,
            ];

            $reading = $this->findField($value, 'reading', $fieldMap);
            if ($reading !== null && $reading !== '') {
                $entry['reading'] = (string) $reading;
            }

            $pos = $this->findField($value, 'pos', $fieldMap);
            if ($pos !== null && $pos !== '') {
                $entry['pos'] = (string) $pos;
            }

            return $entry;
        }

        return null;
    }

    /**
     * Find a field value using custom mapping or default patterns.
     *
     * @param array<string, mixed>       $item      JSON item
     * @param string                     $fieldType Field type (term, definition, etc.)
     * @param array<string, string>|null $fieldMap  Custom field mapping
     *
     * @return mixed Field value or null
     */
    private function findField(array $item, string $fieldType, ?array $fieldMap): mixed
    {
        // Use custom mapping if provided
        if ($fieldMap !== null && isset($fieldMap[$fieldType])) {
            return $item[$fieldMap[$fieldType]] ?? null;
        }

        // Try default field names
        $patterns = self::DEFAULT_FIELD_MAP[$fieldType] ?? [];
        foreach ($patterns as $pattern) {
            if (isset($item[$pattern])) {
                return $item[$pattern];
            }
            // Try case-insensitive
            foreach ($item as $key => $value) {
                if (strtolower($key) === strtolower($pattern)) {
                    return $value;
                }
            }
        }

        return null;
    }
}

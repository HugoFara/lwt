<?php

/**
 * StarDict Dictionary Importer
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
 * Importer for StarDict dictionary files.
 *
 * Parses .ifo (info), .idx (index), and .dict (data) files.
 * Supports both compressed (.dict.dz) and uncompressed (.dict) formats.
 *
 * @since 3.0.0
 */
class StarDictImporter implements ImporterInterface
{
    /**
     * Dictionary metadata from .ifo file.
     *
     * @var array<string, string>
     */
    private array $info = [];

    /**
     * {@inheritdoc}
     */
    public function parse(string $filePath, array $options = []): iterable
    {
        $basePath = $this->getBasePath($filePath);

        // Parse .ifo file
        $this->parseIfo($basePath . '.ifo');

        // Get index entries from .idx file
        $indexEntries = $this->parseIdx($basePath . '.idx');

        // Open dictionary data file
        $dictPath = $this->findDictFile($basePath);
        if ($dictPath === null) {
            throw new RuntimeException("Dictionary data file not found for: $basePath");
        }

        $dictHandle = $this->openDictFile($dictPath);
        if ($dictHandle === false) {
            throw new RuntimeException("Cannot open dictionary file: $dictPath");
        }

        try {
            foreach ($indexEntries as $entry) {
                $definition = $this->readDefinition($dictHandle, $entry['offset'], $entry['size']);
                if ($definition !== null) {
                    yield [
                        'term' => $entry['term'],
                        'definition' => $definition,
                    ];
                }
            }
        } finally {
            fclose($dictHandle);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedExtensions(): array
    {
        return ['ifo', 'idx', 'dict', 'dz'];
    }

    /**
     * {@inheritdoc}
     */
    public function canImport(string $filePath): bool
    {
        $basePath = $this->getBasePath($filePath);

        // Check all required files exist
        $ifoPath = $basePath . '.ifo';
        $idxPath = $basePath . '.idx';

        if (!file_exists($ifoPath) || !is_readable($ifoPath)) {
            return false;
        }

        if (!file_exists($idxPath) || !is_readable($idxPath)) {
            return false;
        }

        $dictPath = $this->findDictFile($basePath);
        if ($dictPath === null) {
            return false;
        }

        return true;
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
     * Get dictionary metadata.
     *
     * @param string $filePath Path to any StarDict file
     *
     * @return array<string, string> Dictionary info
     */
    public function getInfo(string $filePath): array
    {
        $basePath = $this->getBasePath($filePath);
        $this->parseIfo($basePath . '.ifo');
        return $this->info;
    }

    /**
     * Get the base path (without extension) for a StarDict file.
     *
     * @param string $filePath Path to any StarDict file
     *
     * @return string Base path
     */
    private function getBasePath(string $filePath): string
    {
        // Remove common extensions
        $path = $filePath;

        if (str_ends_with($path, '.dict.dz')) {
            $path = substr($path, 0, -8);
        } elseif (str_ends_with($path, '.dz')) {
            $path = substr($path, 0, -3);
        } else {
            $extensions = ['.ifo', '.idx', '.dict', '.syn'];
            foreach ($extensions as $ext) {
                if (str_ends_with($path, $ext)) {
                    $path = substr($path, 0, -strlen($ext));
                    break;
                }
            }
        }

        return $path;
    }

    /**
     * Parse the .ifo (info) file.
     *
     * @param string $ifoPath Path to .ifo file
     *
     * @return void
     *
     * @throws RuntimeException If file is invalid
     */
    private function parseIfo(string $ifoPath): void
    {
        if (!file_exists($ifoPath)) {
            throw new RuntimeException("IFO file not found: $ifoPath");
        }

        $content = file_get_contents($ifoPath);
        if ($content === false) {
            throw new RuntimeException("Cannot read IFO file: $ifoPath");
        }

        $lines = explode("\n", $content);

        // First line should be magic header
        $firstLine = trim($lines[0] ?? '');
        if (!str_starts_with($firstLine, 'StarDict\'s dict iance')) {
            // Try alternative format
            if ($firstLine !== 'StarDict\'s dict ifo file') {
                throw new RuntimeException("Invalid StarDict IFO file format");
            }
        }

        $this->info = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, '=')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $this->info[trim($parts[0])] = trim($parts[1]);
            }
        }
    }

    /**
     * Parse the .idx (index) file.
     *
     * @param string $idxPath Path to .idx file
     *
     * @return Generator<array{term: string, offset: int, size: int}>
     */
    private function parseIdx(string $idxPath): Generator
    {
        if (!file_exists($idxPath)) {
            throw new RuntimeException("IDX file not found: $idxPath");
        }

        $content = file_get_contents($idxPath);
        if ($content === false) {
            throw new RuntimeException("Cannot read IDX file: $idxPath");
        }

        $length = strlen($content);
        $pos = 0;

        // Determine offset/size format (32-bit by default)
        $idxOffsetBits = isset($this->info['idxoffsetbits']) ?
            (int) $this->info['idxoffsetbits'] : 32;

        while ($pos < $length) {
            // Read null-terminated term
            $termEnd = strpos($content, "\0", $pos);
            if ($termEnd === false) {
                break;
            }

            $term = substr($content, $pos, $termEnd - $pos);
            $pos = $termEnd + 1;

            // Read offset and size (4 bytes each for 32-bit)
            if ($pos + 8 > $length) {
                break;
            }

            if ($idxOffsetBits === 64) {
                // 64-bit offset
                if ($pos + 12 > $length) {
                    break;
                }
                $offsetData = substr($content, $pos, 8);
                $pos += 8;
                // PHP doesn't have native 64-bit unpack, use 32-bit parts
                $offsetParts = unpack('N2', $offsetData);
                if ($offsetParts === false) {
                    break;
                }
                $offset = ((int)$offsetParts[1] << 32) | (int)$offsetParts[2];
            } else {
                // 32-bit offset
                $offsetData = substr($content, $pos, 4);
                $pos += 4;
                $offsetUnpacked = unpack('N', $offsetData);
                if ($offsetUnpacked === false) {
                    break;
                }
                $offset = (int)$offsetUnpacked[1];
            }

            $sizeData = substr($content, $pos, 4);
            $pos += 4;
            $sizeUnpacked = unpack('N', $sizeData);
            if ($sizeUnpacked === false) {
                break;
            }
            $size = (int)$sizeUnpacked[1];

            if ($term !== '' && $size > 0) {
                yield [
                    'term' => $term,
                    'offset' => $offset,
                    'size' => $size,
                ];
            }
        }
    }

    /**
     * Find the dictionary data file (.dict or .dict.dz).
     *
     * @param string $basePath Base path without extension
     *
     * @return string|null Path to dict file or null
     */
    private function findDictFile(string $basePath): ?string
    {
        // Try uncompressed first
        $dictPath = $basePath . '.dict';
        if (file_exists($dictPath) && is_readable($dictPath)) {
            return $dictPath;
        }

        // Try gzip compressed
        $dictDzPath = $basePath . '.dict.dz';
        if (file_exists($dictDzPath) && is_readable($dictDzPath)) {
            return $dictDzPath;
        }

        return null;
    }

    /**
     * Open the dictionary data file.
     *
     * @param string $dictPath Path to dict file
     *
     * @return resource|false File handle or false
     */
    private function openDictFile(string $dictPath)
    {
        if (str_ends_with($dictPath, '.dz')) {
            // Gzip compressed - use zlib wrapper
            return gzopen($dictPath, 'rb');
        }

        return fopen($dictPath, 'rb');
    }

    /**
     * Read a definition from the dictionary file.
     *
     * @param resource $handle File handle
     * @param int      $offset Byte offset
     * @param int      $size   Data size
     *
     * @return string|null Definition text
     */
    private function readDefinition($handle, int $offset, int $size): ?string
    {
        if (fseek($handle, $offset) === -1) {
            return null;
        }

        $data = fread($handle, $size);
        if ($data === false) {
            return null;
        }

        // StarDict can have different data types (m, l, g, t, etc.)
        // For simplicity, treat all as plain text
        // Remove type markers if present
        $sameTypeSequence = $this->info['sametypesequence'] ?? null;

        if ($sameTypeSequence !== null) {
            // All entries have same type, no per-entry markers
            return $this->cleanDefinition($data);
        }

        // Check for type marker at start
        if (strlen($data) > 1) {
            $typeMarker = $data[0];
            if (ctype_alpha($typeMarker)) {
                // Has type marker, skip it
                $data = substr($data, 1);

                // Find end of this type's data (null byte)
                $nullPos = strpos($data, "\0");
                if ($nullPos !== false) {
                    $data = substr($data, 0, $nullPos);
                }
            }
        }

        return $this->cleanDefinition($data);
    }

    /**
     * Clean up a definition string.
     *
     * @param string $definition Raw definition
     *
     * @return string Cleaned definition
     */
    private function cleanDefinition(string $definition): string
    {
        // Remove null bytes
        $definition = str_replace("\0", '', $definition);

        // Convert to UTF-8 if needed
        if (!mb_check_encoding($definition, 'UTF-8')) {
            $converted = mb_convert_encoding($definition, 'UTF-8', 'auto');
            if ($converted !== false) {
                $definition = $converted;
            }
        }

        // Clean up whitespace
        $definition = trim($definition);

        // Remove Pango markup tags if present
        $definition = strip_tags($definition);

        return $definition;
    }
}

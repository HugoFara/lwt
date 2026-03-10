<?php

/**
 * Curated Dictionary Import Service
 *
 * Downloads curated dictionary archives from verified URLs,
 * extracts them, and imports entries via the existing import pipeline.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Dictionary\Application\Services
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Dictionary\Application\Services;

use Lwt\Modules\Dictionary\Application\DictionaryFacade;
use RuntimeException;
use ZipArchive;

/**
 * Service for importing curated dictionaries from remote URLs.
 *
 * Validates URLs against the curated registry (SSRF defense),
 * downloads the archive, extracts it, delegates to the appropriate
 * importer, and cleans up temp files.
 *
 * @since 3.0.0
 */
class CuratedDictImportService
{
    /** Maximum download size (100 MB). */
    private const MAX_DOWNLOAD_BYTES = 100 * 1024 * 1024;

    /** HTTP download timeout in seconds. */
    private const DOWNLOAD_TIMEOUT = 120;

    /** Maximum files allowed in a ZIP archive. */
    private const MAX_ZIP_FILES = 500;

    private DictionaryFacade $facade;

    /** @var string|null Override path for testing */
    private ?string $registryPath;

    public function __construct(
        DictionaryFacade $facade,
        ?string $registryPath = null
    ) {
        $this->facade = $facade;
        $this->registryPath = $registryPath;
    }

    /**
     * Import a curated dictionary from a remote URL.
     *
     * @param int    $languageId Target language ID
     * @param string $url        Download URL (must be in curated registry)
     * @param string $format     Dictionary format (stardict, csv)
     * @param string $name       Dictionary name
     *
     * @return array{success: bool, dictId?: int, imported?: int, error?: string}
     */
    public function importFromUrl(
        int $languageId,
        string $url,
        string $format,
        string $name
    ): array {
        // Validate URL is in curated registry
        if (!$this->isCuratedUrl($url)) {
            return ['success' => false, 'error' => 'URL is not in the curated dictionary registry'];
        }

        $tempFiles = [];

        try {
            // Increase time limit for large downloads
            set_time_limit(300);

            // Download archive
            $archivePath = $this->downloadToTemp($url);
            $tempFiles[] = $archivePath;

            // Determine if extraction is needed
            $urlPath = parse_url($url, PHP_URL_PATH);
            $ext = strtolower(pathinfo(is_string($urlPath) ? $urlPath : '', PATHINFO_EXTENSION));

            if ($ext === 'zip') {
                $extractDir = $this->extractZip($archivePath);
                $tempFiles[] = $extractDir;
                $importFile = $this->findImportFile($extractDir, $format);
            } else {
                // Direct file (e.g. CSV) - use as-is
                $importFile = $archivePath;
            }

            // Get importer and parse
            $importer = $this->facade->getImporter($format, basename($importFile));

            if (!$importer->canImport($importFile)) {
                return ['success' => false, 'error' => 'Downloaded file is not a valid ' . $format . ' dictionary'];
            }

            // Create dictionary record and import entries
            $dictId = $this->facade->create($languageId, $name, $format);
            $entries = $importer->parse($importFile);
            $count = $this->facade->addEntriesBatch($dictId, $entries);

            if ($count === 0) {
                // Clean up empty dictionary
                $this->facade->delete($dictId);
                return ['success' => false, 'error' => 'No entries found in the dictionary file'];
            }

            return [
                'success' => true,
                'dictId' => $dictId,
                'imported' => $count,
            ];
        } catch (RuntimeException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        } finally {
            $this->cleanup(...$tempFiles);
        }
    }

    /**
     * Check whether a URL appears in the curated dictionaries registry.
     */
    public function isCuratedUrl(string $url): bool
    {
        $registry = $this->loadRegistry();
        if ($registry === null) {
            return false;
        }

        foreach ($registry as $group) {
            $sources = isset($group['sources']) && is_array($group['sources'])
                ? $group['sources'] : [];
            /** @var list<array<string, mixed>> $sources */
            foreach ($sources as $source) {
                if (isset($source['url']) && $source['url'] === $url) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Download a URL to a temporary file.
     *
     * @return string Path to the downloaded temp file
     *
     * @throws RuntimeException On download failure
     */
    private function downloadToTemp(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => self::DOWNLOAD_TIMEOUT,
                'user_agent' => 'LWT/3.0 (+https://github.com/HugoFara/lwt)',
                'max_redirects' => 5,
                'ignore_errors' => false,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'lwt_dict_');
        if ($tempPath === false) {
            throw new RuntimeException('Failed to create temporary file');
        }

        // Download with size limit
        $source = @fopen($url, 'r', false, $context);
        if ($source === false) {
            unlink($tempPath);
            throw new RuntimeException('Failed to download dictionary from ' . $url);
        }

        $dest = fopen($tempPath, 'w');
        if ($dest === false) {
            fclose($source);
            unlink($tempPath);
            throw new RuntimeException('Failed to write temporary file');
        }

        $totalBytes = 0;
        while (!feof($source)) {
            $chunk = fread($source, 8192);
            if ($chunk === false) {
                break;
            }
            $totalBytes += strlen($chunk);
            if ($totalBytes > self::MAX_DOWNLOAD_BYTES) {
                fclose($source);
                fclose($dest);
                unlink($tempPath);
                throw new RuntimeException('Download exceeds maximum size of 100 MB');
            }
            fwrite($dest, $chunk);
        }

        fclose($source);
        fclose($dest);

        if ($totalBytes === 0) {
            unlink($tempPath);
            throw new RuntimeException('Downloaded file is empty');
        }

        return $tempPath;
    }

    /**
     * Extract a ZIP archive to a temporary directory.
     *
     * @return string Path to the extraction directory
     *
     * @throws RuntimeException On extraction failure
     */
    private function extractZip(string $zipPath): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension is required for ZIP import');
        }

        /**
         * @psalm-suppress UndefinedDocblockClass
         * @var \ZipArchive $zip
         */
        $zip = new ZipArchive();
        $result = $zip->open($zipPath);
        if ($result !== true) {
            throw new RuntimeException('Failed to open ZIP archive (error code: ' . (string) $result . ')');
        }

        // Safety check: limit file count
        /**
         * @psalm-suppress UndefinedDocblockClass
         * @var int $numFiles
         */
        $numFiles = $zip->numFiles;
        if ($numFiles > self::MAX_ZIP_FILES) {
            $zip->close();
            throw new RuntimeException('ZIP archive contains too many files (' . $numFiles . ')');
        }

        // Safety check: no path traversal
        for ($i = 0; $i < $numFiles; $i++) {
            /** @var string|false $entryName */
            $entryName = $zip->getNameIndex($i);
            if ($entryName === false) {
                continue;
            }
            if (str_contains($entryName, '..')) {
                $zip->close();
                throw new RuntimeException('ZIP archive contains unsafe path: ' . $entryName);
            }
        }

        $extractDir = sys_get_temp_dir() . '/lwt_dict_' . bin2hex(random_bytes(8));
        if (!mkdir($extractDir, 0700, true)) {
            $zip->close();
            throw new RuntimeException('Failed to create extraction directory');
        }

        if (!$zip->extractTo($extractDir)) {
            $zip->close();
            $this->removeDir($extractDir);
            throw new RuntimeException('Failed to extract ZIP archive');
        }

        $zip->close();
        return $extractDir;
    }

    /**
     * Find the importable file within an extracted directory.
     *
     * @param string $directory Extracted directory path
     * @param string $format    Expected format (stardict, csv)
     *
     * @return string Path to the importable file
     *
     * @throws RuntimeException If no importable file found
     */
    public function findImportFile(string $directory, string $format): string
    {
        $extensions = match ($format) {
            'stardict' => ['ifo'],
            'csv', 'tsv' => ['csv', 'tsv', 'txt'],
            'json' => ['json'],
            default => throw new RuntimeException('Unsupported format: ' . $format),
        };

        // Recursively scan for matching files
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, $extensions, true)) {
                    return $file->getPathname();
                }
            }
        }

        throw new RuntimeException(
            'No ' . $format . ' file found in the downloaded archive (looked for .' .
            implode(', .', $extensions) . ')'
        );
    }

    /**
     * Load the curated dictionaries registry.
     *
     * @return list<array<string, mixed>>|null
     */
    private function loadRegistry(): ?array
    {
        $path = $this->registryPath ?? (dirname(__DIR__, 4) . '/data/curated_dictionaries.json');
        if (!file_exists($path)) {
            return null;
        }
        $json = file_get_contents($path);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['dictionaries'])) {
            return null;
        }
        /** @var list<array<string, mixed>> */
        return $data['dictionaries'];
    }

    /**
     * Clean up temporary files and directories.
     */
    private function cleanup(string ...$paths): void
    {
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }
    }

    /**
     * Recursively remove a directory.
     */
    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        /** @var \SplFileInfo $item */
        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }
}

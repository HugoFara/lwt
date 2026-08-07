<?php

/**
 * Codebase invariant: JSON embedded in a page must not be able to close its
 * own <script> block.
 *
 * PHP version 8.1
 *
 * @category Tests
 * @package  Lwt
 * @license  Unlicense <http://unlicense.org/>
 */

declare(strict_types=1);

namespace Lwt\Tests\Core;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * `json_encode` does not escape `<`, `>` or `/`, so a `</script>` inside any
 * user-controlled field closes a `<script type="application/json">` config
 * block early and everything after it is parsed as markup.
 *
 * The codebase standardises on `JSON_HEX_TAG | JSON_HEX_AMP` for these blocks.
 * Individual sites have drifted from that more than once, so this test asserts
 * the invariant across the whole tree instead of per call site.
 */
final class JsonScriptBlockEscapingTest extends TestCase
{
    /**
     * Collect every PHP file under src/.
     *
     * @return list<string> Absolute paths
     */
    private function sourceFiles(): array
    {
        $root = dirname(__DIR__, 3) . '/src';
        $files = [];

        /** @var RecursiveDirectoryIterator $dirIterator */
        $dirIterator = new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS);
        /** @var iterable<\SplFileInfo> $iterator */
        $iterator = new RecursiveIteratorIterator($dirIterator);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);
        return $files;
    }

    /**
     * Extract the arguments of every `json_encode(` call in a source string.
     *
     * Brace matching rather than a regex, because these calls routinely span
     * a dozen lines of array literal.
     *
     * @param string $source File contents
     *
     * @return list<array{offset: int, call: string}>
     */
    private function jsonEncodeCalls(string $source): array
    {
        $calls = [];
        $offset = 0;

        while (($start = strpos($source, 'json_encode', $offset)) !== false) {
            $open = strpos($source, '(', $start);
            if ($open === false) {
                break;
            }

            $depth = 1;
            $i = $open + 1;
            $length = strlen($source);
            while ($i < $length && $depth > 0) {
                if ($source[$i] === '(') {
                    $depth++;
                } elseif ($source[$i] === ')') {
                    $depth--;
                }
                $i++;
            }

            $calls[] = ['offset' => $start, 'call' => substr($source, $start, $i - $start)];
            $offset = $i;
        }

        return $calls;
    }

    /**
     * Every json_encode feeding a <script> block sets JSON_HEX_TAG.
     */
    public function testJsonInScriptBlocksEscapesAngleBrackets(): void
    {
        $offenders = [];

        foreach ($this->sourceFiles() as $path) {
            $source = file_get_contents($path);
            if ($source === false) {
                continue;
            }

            foreach ($this->jsonEncodeCalls($source) as $call) {
                if (str_contains($call['call'], 'JSON_HEX_TAG')) {
                    continue;
                }

                // Only calls whose output lands inside a <script> element
                // matter; API responses and outbound HTTP bodies do not.
                $preceding = substr($source, 0, $call['offset']);
                $scriptOpen = strripos($preceding, '<script');
                if ($scriptOpen === false) {
                    continue;
                }
                $scriptClose = strripos($preceding, '</script>');
                if ($scriptClose !== false && $scriptClose > $scriptOpen) {
                    continue;
                }

                $line = substr_count($preceding, "\n") + 1;
                $offenders[] = str_replace(dirname(__DIR__, 3) . '/', '', $path) . ':' . $line;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "json_encode into a <script> block must pass JSON_HEX_TAG | JSON_HEX_AMP.\n"
            . "Offending call sites:\n  " . implode("\n  ", $offenders)
        );
    }
}

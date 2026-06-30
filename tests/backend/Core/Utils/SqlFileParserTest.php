<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Utils;

use Lwt\Shared\Infrastructure\Database\SqlFileParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SqlFileParser class
 */
final class SqlFileParserTest extends TestCase
{
    /**
     * Test parseFile method
     */
    public function testParseFile(): void
    {
        // Create a temporary SQL file
        $sqlContent = "-- Test SQL file\n" .
                      "CREATE TABLE test (id INT);\n" .
                      "INSERT INTO test VALUES (1);\n" .
                      "-- Comment line\n" .
                      "SELECT * FROM test;";

        $tempFile = sys_get_temp_dir() . '/test_sql_' . uniqid() . '.sql';
        file_put_contents($tempFile, $sqlContent);

        // Parse the file
        $queries = SqlFileParser::parseFile($tempFile);

        // Should return an array of queries
        $this->assertIsArray($queries);
        $this->assertNotEmpty($queries);

        // Queries should be separated
        $this->assertGreaterThan(0, count($queries));

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test parseFile with non-existent file
     */
    public function testParseFileNonexistent(): void
    {
        $result = SqlFileParser::parseFile('/nonexistent/file.sql');

        // Should return empty array
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Statements must be split regardless of the file's line endings.
     *
     * Regression test for a CRLF schema file producing a single un-split blob
     * that mysqli rejects with error 1064 on a Linux host (issue #241).
     */
    #[DataProvider('lineEndingProvider')]
    public function testParseFileSplitsStatementsForAnyLineEnding(string $eol): void
    {
        $sqlContent = "-- Test SQL file" . $eol .
                      "CREATE TABLE a (id INT);" . $eol .
                      "CREATE TABLE b (id INT);" . $eol .
                      "INSERT INTO a VALUES (1);";

        $tempFile = sys_get_temp_dir() . '/test_sql_' . uniqid() . '.sql';
        file_put_contents($tempFile, $sqlContent);

        $queries = SqlFileParser::parseFile($tempFile);
        unlink($tempFile);

        // Each statement must come back individually, not concatenated.
        $this->assertSame(
            ['CREATE TABLE a (id INT)', 'CREATE TABLE b (id INT)', 'INSERT INTO a VALUES (1)'],
            $queries
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function lineEndingProvider(): array
    {
        return [
            'LF (Unix)' => ["\n"],
            'CRLF (Windows)' => ["\r\n"],
            'CR (classic Mac)' => ["\r"],
        ];
    }
}

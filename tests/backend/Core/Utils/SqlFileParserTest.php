<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Utils;

require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Database/SqlFileParser.php';

use Lwt\Shared\Infrastructure\Database\SqlFileParser;
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
}

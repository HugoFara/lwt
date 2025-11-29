<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Utils;

require_once __DIR__ . '/../../../../src/backend/Core/Globals.php';
require_once __DIR__ . '/../../../../src/backend/Core/Utils/sql_file_parser.php';

use Lwt\Core\Globals;
use PHPUnit\Framework\TestCase;

Globals::initialize();

/**
 * Tests for sql_file_parser.php functions
 */
final class SqlFileParserTest extends TestCase
{
    /**
     * Test parseSQLFile function
     */
    public function testParseSQLFile(): void
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
        $queries = parseSQLFile($tempFile);

        // Should return an array of queries
        $this->assertIsArray($queries);
        $this->assertNotEmpty($queries);

        // Queries should be separated
        $this->assertGreaterThan(0, count($queries));

        // Clean up
        unlink($tempFile);
    }

    /**
     * Test parseSQLFile with non-existent file
     */
    public function testParseSQLFileNonexistent(): void
    {
        $result = parseSQLFile('/nonexistent/file.sql');

        // Should return empty array or handle gracefully
        $this->assertTrue($result === false || (is_array($result) && empty($result)));
    }
}

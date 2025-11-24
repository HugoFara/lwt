<?php declare(strict_types=1);

require __DIR__ . "/../../connect.inc.php";
$GLOBALS['dbname'] = "test_" . $dbname;
require_once __DIR__ . '/../../inc/database_connect.php';

use PHPUnit\Framework\TestCase;


function user_logging()
{
    include __DIR__ . "/../../connect.inc.php";
    $db_schema = __DIR__ . "../../db/schema/baseline.sql";
    $dbname = "test_" . $dbname;
    $command = "mysql -u $userid -p$passwd -h $server -e 'USE $dbname'";
    exec($command, $output, $returnValue);
    if ($returnValue == 1049) {
        // Execute the SQL file to install the database
        $command = "mysql -u $userid -p$passwd -h $server $dbname < $db_schema";
        exec($command, $output, $returnValue);

        if ($returnValue != 0) {
            die("Cannot login!");
        }
    }
    return array($userid, $passwd, $server, $dbname);

}


class DatabaseConnectTest extends TestCase
{

    public function testDatabaseInstallation()
    {
        global $DBCONNECTION;
        list($userid, $passwd, $server, $dbname) = user_logging();

        // Connect to the database
        $DBCONNECTION = connect_to_database(
            $server, $userid, $passwd, $dbname, $socket ?? ""
        );
        $this->assertTrue(
            mysqli_connect_errno() === 0, 
            'Could not connect to the database: ' . mysqli_connect_error()
        );
    }

    public function testPrefixSQLQuery()
    {
        $value = prefixSQLQuery("CREATE TABLE `languages` test;", "prefix");
        $this->assertEquals("CREATE TABLE `prefixlanguages` test;", $value);
        $value = prefixSQLQuery("ALTER TABLE languages test;", "prefix");
        $this->assertEquals("ALTER TABLE prefixlanguages test;", $value);
    }

    /**
     * Test SQL string escaping with various inputs
     */
    public function testConvertStringToSqlsyntax(): void
    {
        global $DBCONNECTION;

        // Ensure DB connection exists
        if (!$DBCONNECTION) {
            list($userid, $passwd, $server, $dbname) = user_logging();
            $DBCONNECTION = connect_to_database(
                $server, $userid, $passwd, $dbname, $socket ?? ""
            );
        }

        // Basic string
        $result = convert_string_to_sqlsyntax('test');
        $this->assertEquals("'test'", $result);

        // String with single quote (SQL injection attempt)
        $result = convert_string_to_sqlsyntax("test'OR'1'='1");
        $this->assertStringContainsString("\\'", $result);
        $this->assertStringStartsWith("'", $result);
        $this->assertStringEndsWith("'", $result);

        // String with double quote
        $result = convert_string_to_sqlsyntax('test"value');
        $this->assertStringStartsWith("'", $result);
        $this->assertStringEndsWith("'", $result);

        // Empty string should return NULL
        $result = convert_string_to_sqlsyntax('');
        $this->assertEquals('NULL', $result);

        // String with only whitespace should return NULL
        $result = convert_string_to_sqlsyntax('   ');
        $this->assertEquals('NULL', $result);

        // String with line endings (should be normalized - the \n is escaped as \\n in the result)
        $result = convert_string_to_sqlsyntax("line1\r\nline2");
        $this->assertStringContainsString("line1\\nline2", $result);

        // String with backslash
        $result = convert_string_to_sqlsyntax('test\\value');
        $this->assertStringContainsString("\\\\", $result);

        // Unicode characters
        $result = convert_string_to_sqlsyntax('日本語');
        $this->assertStringContainsString('日本語', $result);

        // SQL comment attempt
        $result = convert_string_to_sqlsyntax("test'; DROP TABLE users; --");
        $this->assertStringContainsString("\\'", $result);
    }

    /**
     * Test SQL string escaping that never returns NULL
     */
    public function testConvertStringToSqlsyntaxNonull(): void
    {
        global $DBCONNECTION;

        // Ensure DB connection exists
        if (!$DBCONNECTION) {
            list($userid, $passwd, $server, $dbname) = user_logging();
            $DBCONNECTION = connect_to_database(
                $server, $userid, $passwd, $dbname, $socket ?? ""
            );
        }

        // Basic string
        $result = convert_string_to_sqlsyntax_nonull('test');
        $this->assertEquals("'test'", $result);

        // Empty string should return empty quoted string, NOT NULL
        $result = convert_string_to_sqlsyntax_nonull('');
        $this->assertEquals("''", $result);
        $this->assertNotEquals('NULL', $result);

        // Whitespace should be trimmed but still quoted
        $result = convert_string_to_sqlsyntax_nonull('   ');
        $this->assertEquals("''", $result);

        // String with quotes
        $result = convert_string_to_sqlsyntax_nonull("test'value");
        $this->assertStringContainsString("\\'", $result);

        // String with line endings (the \n is escaped as \\n in the result)
        $result = convert_string_to_sqlsyntax_nonull("line1\r\nline2");
        $this->assertStringContainsString("line1\\nline2", $result);
    }

    /**
     * Test SQL string escaping without trimming
     */
    public function testConvertStringToSqlsyntaxNotrimNonull(): void
    {
        global $DBCONNECTION;

        // Ensure DB connection exists
        if (!$DBCONNECTION) {
            list($userid, $passwd, $server, $dbname) = user_logging();
            $DBCONNECTION = connect_to_database(
                $server, $userid, $passwd, $dbname, $socket ?? ""
            );
        }

        // String with leading/trailing spaces should preserve them
        $result = convert_string_to_sqlsyntax_notrim_nonull('  test  ');
        $this->assertStringContainsString('  test  ', $result);

        // Empty string
        $result = convert_string_to_sqlsyntax_notrim_nonull('');
        $this->assertEquals("''", $result);

        // Line endings should still be normalized (the \n is escaped as \\n in the result)
        $result = convert_string_to_sqlsyntax_notrim_nonull("line1\r\nline2");
        $this->assertStringContainsString("line1\\nline2", $result);
    }

    /**
     * Test regex to SQL conversion
     */
    public function testConvertRegexpToSqlsyntax(): void
    {
        global $DBCONNECTION;

        // Ensure DB connection exists
        if (!$DBCONNECTION) {
            list($userid, $passwd, $server, $dbname) = user_logging();
            $DBCONNECTION = connect_to_database(
                $server, $userid, $passwd, $dbname, $socket ?? ""
            );
        }

        // Basic regex pattern
        $result = convert_regexp_to_sqlsyntax('[a-z]+');
        $this->assertStringStartsWith("'", $result);
        $this->assertStringEndsWith("'", $result);

        // Hex escape sequences (e.g., \x{1234})
        $result = convert_regexp_to_sqlsyntax('\\x{41}'); // 'A' in hex
        $this->assertStringContainsString('A', $result);

        // Character class with dash
        $result = convert_regexp_to_sqlsyntax('[a-z]');
        $this->assertStringContainsString('[a-z]', $result);
    }

    /**
     * Test language ID validation
     */
    public function testValidateLang(): void
    {
        global $DBCONNECTION;

        // Ensure DB connection exists
        if (!$DBCONNECTION) {
            list($userid, $passwd, $server, $dbname) = user_logging();
            $DBCONNECTION = connect_to_database(
                $server, $userid, $passwd, $dbname, $socket ?? ""
            );
        }

        // Empty string should return empty
        $result = validateLang('');
        $this->assertEquals('', $result);

        // Test with a language that doesn't exist (should return empty)
        // Using a numeric value to avoid SQL errors
        $result = validateLang('99999');
        $this->assertEquals('', $result);

        // Note: Non-numeric values cause SQL errors in the current implementation
        // This is a potential bug that should be fixed by sanitizing input before SQL
    }

    /**
     * Test text ID validation
     */
    public function testValidateText(): void
    {
        global $DBCONNECTION;

        // Ensure DB connection exists
        if (!$DBCONNECTION) {
            list($userid, $passwd, $server, $dbname) = user_logging();
            $DBCONNECTION = connect_to_database(
                $server, $userid, $passwd, $dbname, $socket ?? ""
            );
        }

        // Empty string should return empty
        $result = validateText('');
        $this->assertEquals('', $result);

        // Test with a text that doesn't exist (should return empty)
        // Using a numeric value to avoid SQL errors
        $result = validateText('99999');
        $this->assertEquals('', $result);

        // Note: Non-numeric values cause SQL errors in the current implementation
        // This is a potential bug that should be fixed by sanitizing input before SQL
    }

    /**
     * Test prepare_textdata function (line ending normalization)
     */
    public function testPrepareTextdata(): void
    {
        // Windows line endings to Unix
        $this->assertEquals("line1\nline2", prepare_textdata("line1\r\nline2"));

        // Multiple line endings
        $this->assertEquals("a\nb\nc", prepare_textdata("a\r\nb\r\nc"));

        // Unix line endings unchanged
        $this->assertEquals("line1\nline2", prepare_textdata("line1\nline2"));

        // Empty string
        $this->assertEquals('', prepare_textdata(''));

        // No line endings
        $this->assertEquals('single line', prepare_textdata('single line'));
    }

}
?>

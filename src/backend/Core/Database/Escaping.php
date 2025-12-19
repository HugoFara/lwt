<?php declare(strict_types=1);
/**
 * \file
 * \brief SQL string escaping and text preparation utilities.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-database-escaping.html
 * @since    3.0.0
 */

namespace Lwt\Database;

use Lwt\Core\Globals;

/**
 * SQL escaping and text preparation utilities.
 *
 * Provides methods for escaping strings for safe SQL queries
 * and preparing text data for database storage.
 *
 * @since 3.0.0
 */
class Escaping
{
    /**
     * Get the database connection, asserting it's not null.
     *
     * @return \mysqli The active database connection
     *
     * @throws \RuntimeException If database connection is not initialized
     */
    private static function getConnection(): \mysqli
    {
        $conn = Globals::getDbConnection();
        if ($conn === null) {
            throw new \RuntimeException('Database connection not initialized');
        }
        return $conn;
    }

    /**
     * Replace Windows line return ("\r\n") by Linux ones ("\n").
     *
     * @param string $s Input string
     *
     * @return string Adapted string.
     */
    public static function prepareTextdata(string $s): string
    {
        return str_replace("\r\n", "\n", $s);
    }

    /**
     * Prepare text data for JavaScript with SQL escaping.
     *
     * @param string $s Input string
     *
     * @return string JS-safe escaped string
     *
     * @deprecated 3.0.0 Use json_encode() instead for JavaScript output.
     *                   This function incorrectly mixes SQL and JS escaping.
     */
    public static function prepareTextdataJs(string $s): string
    {
        $s = self::toSqlSyntax($s);
        if ($s == "NULL") {
            return "''";
        }
        return str_replace("'", "\\'", $s);
    }

    /**
     * Prepares a string to be properly recognized as a string by SQL.
     *
     * @param string|int|float $data Input data
     *
     * @return string Properly escaped and trimmed string. "NULL" if the input string is empty.
     */
    public static function toSqlSyntax(string|int|float $data): string
    {
        $result = "NULL";
        $data = trim(self::prepareTextdata((string)$data));
        if ($data != "") {
            $result = "'" . mysqli_real_escape_string(
                self::getConnection(),
                $data
            ) . "'";
        }
        return $result;
    }

    /**
     * Prepares a string to be properly recognized as a string by SQL.
     *
     * @param string $data Input string
     *
     * @return string Properly escaped and trimmed string (never NULL)
     */
    public static function toSqlSyntaxNoNull(string $data): string
    {
        $data = trim(self::prepareTextdata($data));
        return "'" . mysqli_real_escape_string(self::getConnection(), $data) . "'";
    }

    /**
     * Prepares a string to be properly recognized as a string by SQL.
     *
     * @param string $data Input string
     *
     * @return string Properly escaped string (no trim, never NULL)
     */
    public static function toSqlSyntaxNoTrimNoNull(string $data): string
    {
        return "'" .
        mysqli_real_escape_string(self::getConnection(), self::prepareTextdata($data)) .
        "'";
    }

    /**
     * Format a value for SQL output (e.g., backup files, SQL dumps).
     *
     * Unlike prepared statements which pass values separately from queries,
     * this method produces properly escaped SQL literals for use in generated
     * SQL statements (like INSERT INTO ... VALUES(...)).
     *
     * @param string|int|float|null $value Database value to format
     *
     * @return string SQL literal: "NULL" for null, "'escaped'" for strings
     */
    public static function formatValueForSqlOutput(string|int|float|null $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        // Convert to string and escape for SQL
        $stringValue = (string)$value;
        return "'" . mysqli_real_escape_string(self::getConnection(), $stringValue) . "'";
    }

    /**
     * Convert a regexp pattern to SQL-safe format.
     *
     * @param string $input Regexp pattern
     *
     * @return string SQL-safe escaped pattern
     */
    public static function regexpToSqlSyntax(string $input): string
    {
        $output = preg_replace_callback(
            "/\\\\x\\{([\\da-z]+)\\}/ui",
            function ($a) {
                $num = $a[1];
                $dec = hexdec($num);
                return "&#$dec;";
            },
            preg_replace(
                array('/\\\\(?![-xtfrnvup])/u', '/(?<=[[^])[\\\]-/u'),
                array('', '-'),
                $input
            )
        );
        return self::toSqlSyntaxNoNull(
            html_entity_decode($output, ENT_NOQUOTES, 'UTF-8')
        );
    }
}

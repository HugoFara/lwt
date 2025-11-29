<?php

/**
 * \file
 * \brief SQL file parsing utilities.
 *
 * Provides functionality for parsing SQL files into individual queries.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-sql-file-parser.html
 * @since    3.0.0 Split from kernel_utility.php
 */

namespace Lwt\Database;

/**
 * SQL file parser for reading and splitting SQL files into individual queries.
 *
 * @since 3.0.0
 */
class SqlFileParser
{
    /**
     * Parse a SQL file by returning an array of the different queries it contains.
     *
     * @param string $filename File name
     *
     * @return string[]
     *
     * @psalm-return list{0?: string,...}
     */
    public static function parseFile(string $filename): array
    {
        $handle = @fopen($filename, 'r');
        if ($handle === false) {
            return array();
        }
        $queries_list = array();
        $curr_content = '';
        while ($stream = fgets($handle)) {
            // Skip comments
            if (str_starts_with($stream, '-- ')) {
                continue;
            }
            // Add stream to accumulator
            $curr_content .= $stream;
            // Get queries
            $queries = explode(';' . PHP_EOL, $curr_content);
            // Replace line by remainders of the last element (incomplete line)
            $curr_content = array_pop($queries);
            foreach ($queries as $query) {
                $queries_list[] = trim($query);
            }
        }
        // Add final query if there's any remaining content
        if (!empty(trim($curr_content))) {
            $queries_list[] = trim($curr_content);
        }
        if (!feof($handle)) {
            // Throw error
        }
        fclose($handle);
        return $queries_list;
    }
}

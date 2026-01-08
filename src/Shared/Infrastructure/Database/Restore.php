<?php declare(strict_types=1);
/**
 * \file
 * \brief Database restore operations.
 *
 * This file contains functions for restoring database backups
 * and truncating user data.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Database
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0 Split from database_operations.php
 */

namespace Lwt\Shared\Infrastructure\Database;

use Lwt\Core\Globals;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Shared\Infrastructure\Database\SqlValidator;

/**
 * Database restore and truncation operations.
 *
 * @since 3.0.0
 */
class Restore
{
    /**
     * Restore the database from a file.
     *
     * @param resource $handle       Backup file handle
     * @param string   $title        File title
     * @param bool     $validateSql  Whether to validate SQL statements (default true)
     *
     * @return string Human-readable status message
     *
     * @since 2.0.3-fork Function was broken
     * @since 2.5.3-fork Function repaired
     * @since 2.7.0-fork $handle should be an *uncompressed* file.
     * @since 2.9.1-fork It can read SQL with more or less than one instruction a line
     * @since 3.0.0 Added SQL validation for security hardening
     */
    public static function restoreFile($handle, string $title, bool $validateSql = true): string
    {
        $message = "";
        $install_status = [
            "queries" => 0,
            "successes" => 0,
            "errors" => 0,
            "drops" => 0,
            "inserts" => 0,
            "creates" => 0
        ];
        $start = true;
        $curr_content = '';
        $queries_list = [];

        while ($stream = fgets($handle)) {
            // Check file header
            if ($start) {
                if (
                    !str_starts_with($stream, "-- lwt-backup-")
                    && !str_starts_with($stream, "-- lwt-exp_version-backup-")
                ) {
                    $message = "Error: Invalid $title Restore file " .
                    "(possibly not created by LWT backup)";
                    $install_status["errors"] = 1;
                    break;
                }
                $start = false;
                continue;
            }
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

        if (!feof($handle) && $install_status["errors"] == 0) {
            $message = "Error: cannot read the end of the demo file!";
            $install_status["errors"] = 1;
        }
        fclose($handle);

        // Validate all queries before executing any (security hardening)
        if ($validateSql && $install_status["errors"] == 0) {
            $validator = new SqlValidator();
            foreach ($queries_list as $query) {
                $trimmedQuery = trim($query);
                if ($trimmedQuery !== '' && !str_starts_with($trimmedQuery, '-- ')) {
                    if (!$validator->validate($trimmedQuery)) {
                        $message = "Security Error: " . ($validator->getFirstError() ?? "Invalid SQL detected");
                        $install_status["errors"] = 1;
                        break;
                    }
                }
            }
        }

        // Now run all queries
        $connection = Globals::getDbConnection();
        if ($install_status["errors"] == 0 && $connection !== null) {
            foreach ($queries_list as $query) {
                $sql_line = trim(
                    str_replace("\r", "", str_replace("\n", "", $query))
                );
                if ($sql_line != "") {
                    if (!str_starts_with($query, '-- ')) {
                        $res = mysqli_query(
                            $connection,
                            $query
                        );
                        $install_status["queries"]++;
                        if ($res == false) {
                            $install_status["errors"]++;
                        } else {
                            $install_status["successes"]++;
                            if (str_starts_with($query, "INSERT INTO")) {
                                $install_status["inserts"]++;
                            } elseif (str_starts_with($query, "DROP TABLE")) {
                                $install_status["drops"]++;
                            } elseif (str_starts_with($query, "CREATE TABLE")) {
                                $install_status["creates"]++;
                            }
                        }
                    }
                }
            }
        }

        /** @psalm-suppress TypeDoesNotContainType - Value can change in loop */
        if ($install_status["errors"] == 0) {
            // Drop legacy textitems table if it exists (replaced by textitems2)
            Connection::execute("DROP TABLE IF EXISTS textitems");
            Migrations::checkAndUpdate();
            Migrations::reparseAllTexts();
            Maintenance::optimizeDatabase();
            TagsFacade::getAllTermTags(true);
            TagsFacade::getAllTextTags(true);
            $message = "Success: $title restored";
        } elseif ($message == "") {
            $message = "Error: $title NOT restored";
        }

        $message .= sprintf(
            " - %d queries - %d successful (%d/%d tables dropped/created, " .
            "%d records added), %d failed.",
            $install_status["queries"],
            $install_status["successes"],
            $install_status["drops"],
            $install_status["creates"],
            $install_status["inserts"],
            $install_status["errors"]
        );
        return $message;
    }

    /**
     * Truncate the database, remove all data belonging by the current user.
     *
     * Keep settings.
     *
     * @return void
     */
    public static function truncateUserDatabase(): void
    {
        // Delete from tables in correct order to respect foreign key constraints.
        // Child tables (with FKs) must be deleted from before parent tables.
        // Use DELETE instead of TRUNCATE because TRUNCATE fails when FK constraints
        // exist on a table, even if those constraints would allow the operation.

        // Level 1: Tables with FKs to multiple parents
        Connection::execute('DELETE FROM ' . Globals::table('archtexttags'));
        Connection::execute('DELETE FROM ' . Globals::table('texttags'));
        Connection::execute('DELETE FROM ' . Globals::table('wordtags'));
        Connection::execute('DELETE FROM ' . Globals::table('textitems2'));
        Connection::execute('DELETE FROM ' . Globals::table('feedlinks'));

        // Level 2: Tables with FKs to languages only
        Connection::execute('DELETE FROM ' . Globals::table('sentences'));
        Connection::execute('DELETE FROM ' . Globals::table('newsfeeds'));
        Connection::execute('DELETE FROM ' . Globals::table('texts'));
        Connection::execute('DELETE FROM ' . Globals::table('archivedtexts'));
        Connection::execute('DELETE FROM ' . Globals::table('words'));

        // Level 3: Parent tables with no FKs to other content tables
        Connection::execute('DELETE FROM ' . Globals::table('tags'));
        Connection::execute('DELETE FROM ' . Globals::table('tags2'));
        Connection::execute('DELETE FROM ' . Globals::table('languages'));

        QueryBuilder::table('settings')
            ->where('StKey', '=', 'currenttext')
            ->delete();
        Maintenance::optimizeDatabase();
        TagsFacade::getAllTermTags(true);
        TagsFacade::getAllTextTags(true);
    }
}

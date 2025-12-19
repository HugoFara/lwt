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

namespace Lwt\Database;

use Lwt\Core\Globals;
use Lwt\Services\TagService;

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
     * @param resource $handle Backup file handle
     * @param string   $title  File title
     *
     * @return string Human-readable status message
     *
     * @since 2.0.3-fork Function was broken
     * @since 2.5.3-fork Function repaired
     * @since 2.7.0-fork $handle should be an *uncompressed* file.
     * @since 2.9.1-fork It can read SQL with more or less than one instruction a line
     */
    public static function restoreFile($handle, string $title): string
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

        // Now run all queries
        if ($install_status["errors"] == 0) {
            foreach ($queries_list as $query) {
                $sql_line = trim(
                    str_replace("\r", "", str_replace("\n", "", $query))
                );
                if ($sql_line != "") {
                    if (!str_starts_with($query, '-- ')) {
                        $res = mysqli_query(
                            Globals::getDbConnection(),
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
            TagService::getAllTermTags(true);
            TagService::getAllTextTags(true);
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
        QueryBuilder::table('archivedtexts')->truncate();
        QueryBuilder::table('archtexttags')->truncate();
        QueryBuilder::table('feedlinks')->truncate();
        QueryBuilder::table('languages')->truncate();
        QueryBuilder::table('textitems2')->truncate();
        QueryBuilder::table('newsfeeds')->truncate();
        QueryBuilder::table('sentences')->truncate();
        QueryBuilder::table('tags')->truncate();
        QueryBuilder::table('tags2')->truncate();
        QueryBuilder::table('texts')->truncate();
        QueryBuilder::table('texttags')->truncate();
        QueryBuilder::table('words')->truncate();
        QueryBuilder::table('wordtags')->truncate();
        QueryBuilder::table('settings')
            ->where('StKey', '=', 'currenttext')
            ->delete();
        Maintenance::optimizeDatabase();
        TagService::getAllTermTags(true);
        TagService::getAllTextTags(true);
    }
}

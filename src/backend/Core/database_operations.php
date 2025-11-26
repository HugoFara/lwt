<?php

/**
 * \file
 * \brief Database backup and restore operations.
 *
 * This file contains functions for restoring database backups
 * and truncating user data.
 *
 * PHP version 8.1
 *
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since   2.10.0-fork Split from text_helpers.php
 */

/**
 * Restore the database from a file.
 *
 * @param resource $handle Backup file handle
 * @param string   $title  File title
 *
 * @return string Human-readable status message
 *
 * @global string $trbpref Database table prefix
 * @global int    $debug   Debug status
 * @global string $dbname  Database name
 *
 * @since 2.0.3-fork Function was broken
 * @since 2.5.3-fork Function repaired
 * @since 2.7.0-fork $handle should be an *uncompressed* file.
 * @since 2.9.1-fork It can read SQL with more or less than one instruction a line
 */
function restore_file($handle, $title): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $message = "";
    $install_status = array(
        "queries" => 0,
        "successes" => 0,
        "errors" => 0,
        "drops" => 0,
        "inserts" => 0,
        "creates" => 0
    );
    $start = true;
    $curr_content = '';
    $queries_list = array();
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
        //var_dump("queries", $queries);
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
                        \Lwt\Core\LWT_Globals::getDbConnection(),
                        prefixSQLQuery($query, $tbpref)
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
    if ($install_status["errors"] == 0) {
        runsql("DROP TABLE IF EXISTS {$tbpref}textitems", '');
        check_update_db();
        reparse_all_texts();
        optimizedb();
        get_tags(1);
        get_texttags(1);
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
 * @global $tbpref
 */
function truncateUserDatabase(): void
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    runsql("TRUNCATE {$tbpref}archivedtexts", '');
    runsql("TRUNCATE {$tbpref}archtexttags", '');
    runsql("TRUNCATE {$tbpref}feedlinks", '');
    runsql("TRUNCATE {$tbpref}languages", '');
    runsql("TRUNCATE {$tbpref}textitems2", '');
    runsql("TRUNCATE {$tbpref}newsfeeds", '');
    runsql("TRUNCATE {$tbpref}sentences", '');
    runsql("TRUNCATE {$tbpref}tags", '');
    runsql("TRUNCATE {$tbpref}tags2", '');
    runsql("TRUNCATE {$tbpref}texts", '');
    runsql("TRUNCATE {$tbpref}texttags", '');
    runsql("TRUNCATE {$tbpref}words", '');
    runsql("TRUNCATE {$tbpref}wordtags", '');
    runsql("DELETE FROM {$tbpref}settings where StKey = 'currenttext'", '');
    optimizedb();
    get_tags(1);
    get_texttags(1);
}

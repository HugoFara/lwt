<?php

/**
 * \file
 * \brief All the files needed for a LWT session.
 *
 * By requiring this file, you start a session, connect to the
 * database and declare a lot of useful functions.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since   2.0.3-fork
 */

require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/Feed/feeds.php';
require_once __DIR__ . '/Tag/tags.php';
require_once __DIR__ . '/UI/ui_helpers.php';
require_once __DIR__ . '/Export/export_helpers.php';
require_once __DIR__ . '/Text/text_helpers.php';

// Split modules for focused functionality (since 3.0.0-fork)
require_once __DIR__ . '/Http/param_helpers.php';
require_once __DIR__ . '/Utils/string_utilities.php';
require_once __DIR__ . '/Media/media_helpers.php';
require_once __DIR__ . '/Text/text_navigation.php';
require_once __DIR__ . '/Word/dictionary_links.php';
require_once __DIR__ . '/Test/test_helpers.php';

/**
 * Return all different database prefixes that are in use.
 *
 * @return string[] A list of prefixes.
 *
 * @psalm-return list<string>
 */
function getprefixes(): array
{
    $prefix = array();
    $res = do_mysqli_query(
        str_replace(
            '_',
            "\\_",
            "SHOW TABLES LIKE " . convert_string_to_sqlsyntax_nonull('%_settings')
        )
    );
    while ($row = mysqli_fetch_row($res)) {
        $prefix[] = substr((string) $row[0], 0, -9);
    }
    mysqli_free_result($res);
    return $prefix;
}

function getWordTagList(int $wid, string $before = ' ', int $brack = 1, int $tohtml = 1): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $lbrack = $rbrack = '';
    if ($brack) {
        $lbrack = "[";
        $rbrack = "]";
    }
    $r = get_first_value(
        "SELECT IFNULL(
            GROUP_CONCAT(DISTINCT TgText ORDER BY TgText separator ', '),
            ''
        ) AS value
        FROM (
            (
                {$tbpref}words
                LEFT JOIN {$tbpref}wordtags
                ON WoID = WtWoID
            )
            LEFT JOIN {$tbpref}tags
            ON TgID = WtTgID
        )
        WHERE WoID = $wid"
    );
    if ($r != '') {
        $r = $before . $lbrack . $r . $rbrack;
    }
    if ($tohtml) {
        $r = tohtml($r);
    }
    return $r;
}

/**
 * Return the last inserted ID in the database
 *
 * @return int
 *
 * @since 2.6.0-fork Officially returns a int in documentation, as it was the case
 */
function get_last_key()
{
    return (int)get_first_value('SELECT LAST_INSERT_ID() AS value');
}

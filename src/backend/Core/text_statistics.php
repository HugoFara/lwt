<?php

/**
 * \file
 * \brief Text statistics and word count functions.
 *
 * This file contains functions for calculating text statistics,
 * word counts, and "words to do" progress tracking.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since   2.10.0-fork Split from text_helpers.php
 */

/**
 * Return statistics about a list of text ID.
 *
 * It is useful for unknown percent with this fork.
 *
 * The echo is an output array{0: int, 1: int, 2: int,
 * 3: int, 4: int, 5: int}
 * Total number of words, number of expression, statistics, total unique,
 * number of unique expressions, unique statistics
 *
 * @param string $texts_id Texts ID separated by comma
 *
 * @global string $tbpref Table name prefix
 *
 * @return ((float|int|null|string)[]|float|int|null|string)[][]
 *
 * @psalm-return array{total: array<float|int|string, float|int|null|string>, expr: array<float|int|string, float|int|null|string>, stat: array<float|int|string, array<float|int|string, float|int|null|string>>, totalu: array<float|int|string, float|int|null|string>, expru: array<float|int|string, float|int|null|string>, statu: array<float|int|string, array<float|int|string, float|int|null|string>>}
 */
function return_textwordcount($texts_id): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();

    $r = array(
        // Total for text
        'total' => array(),
        'expr' => array(),
        'stat' => array(),
        // Unique words
        'totalu' => array(),
        'expru' => array(),
        'statu' => array()
    );
    $res = do_mysqli_query(
        "SELECT Ti2TxID AS text, COUNT(DISTINCT LOWER(Ti2Text)) AS value,
        COUNT(LOWER(Ti2Text)) AS total
		FROM {$tbpref}textitems2
		WHERE Ti2WordCount = 1 AND Ti2TxID IN($texts_id)
		GROUP BY Ti2TxID"
    );
    while ($record = mysqli_fetch_assoc($res)) {
        $r["total"][$record['text']] = $record['total'];
        $r["totalu"][$record['text']] = $record['value'];
    }
    mysqli_free_result($res);
    $res = do_mysqli_query(
        "SELECT Ti2TxID AS text, COUNT(DISTINCT Ti2WoID) AS value,
        COUNT(Ti2WoID) AS total
		FROM {$tbpref}textitems2
		WHERE Ti2WordCount > 1 AND Ti2TxID IN({$texts_id})
		GROUP BY Ti2TxID"
    );
    while ($record = mysqli_fetch_assoc($res)) {
        $r["expr"][$record['text']] = $record['total'];
        $r["expru"][$record['text']] = $record['value'];
    }
    mysqli_free_result($res);
    $res = do_mysqli_query(
        "SELECT Ti2TxID AS text, COUNT(DISTINCT Ti2WoID) AS value,
        COUNT(Ti2WoID) AS total, WoStatus AS status
		FROM {$tbpref}textitems2, {$tbpref}words
		WHERE Ti2WoID != 0 AND Ti2TxID IN({$texts_id}) AND Ti2WoID = WoID
		GROUP BY Ti2TxID, WoStatus"
    );
    while ($record = mysqli_fetch_assoc($res)) {
        $r["stat"][$record['text']][$record['status']] = $record['total'];
        $r["statu"][$record['text']][$record['status']] = $record['value'];
    }
    mysqli_free_result($res);
    return $r;
}

/**
 * Compute and echo word statistics about a list of text ID.
 *
 * It is useful for unknown percent with this fork.
 *
 * The echo is an output array{0: int, 1: int, 2: int,
 * 3: int, 4: int, 5: int}
 * Total number of words, number of expression, statistics, total unique,
 * number of unique expressions, unique statistics
 *
 * @param string $textID Text IDs separated by comma
 *
 * @global string $tbpref Table name prefix
 *
 * @deprecated 2.9.0 Use return_textwordcount instead.
 */
function textwordcount($textID): void
{
    echo json_encode(return_textwordcount($textID));
}


/**
 * Return the number of words left to do in this text.
 *
 * @param int $textid Text ID
 *
 * @return int Number of words
 */
function todo_words_count($textid): int
{
    $count = get_first_value(
        "SELECT COUNT(DISTINCT LOWER(Ti2Text)) AS value
        FROM " . \Lwt\Core\LWT_Globals::table('textitems2') . "
        WHERE Ti2WordCount=1 AND Ti2WoID=0 AND Ti2TxID=$textid"
    );
    if ($count === null) {
        return 0;
    }
    return (int) $count;
}


/**
 * Prepare HTML interactions for the words left to do in this text.
 *
 * @param int $textid Text ID
 *
 * @return string HTML result
 *
 * @global string $tbpref
 *
 * @since 2.7.0-fork Adapted to use LibreTranslate dictionary as well.
 */
function todo_words_content($textid): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $c = todo_words_count($textid);
    if ($c <= 0) {
        return '<span title="No unknown word remaining" class="status0" ' .
        'style="padding: 0 5px; margin: 0 5px;">' . $c . '</span>';
    }

    $dict = (string) get_first_value(
        "SELECT LgGoogleTranslateURI AS value
        FROM {$tbpref}languages, {$tbpref}texts
        WHERE LgID = TxLgID and TxID = $textid"
    );
    $tl = $sl = "";
    if ($dict) {
        // @deprecated(2.5.2-fork) For future version of LWT: do not use translator uri
        // to find language code
        if (str_starts_with($dict, '*')) {
            $dict = substr($dict, 1);
        }
        if (str_starts_with($dict, 'ggl.php')) {
            // We just need to form a valid URL
            $dict = "http://" . $dict;
        }
        parse_str(parse_url($dict, PHP_URL_QUERY), $url_query);
        if (
            array_key_exists('lwt_translator', $url_query)
            && $url_query['lwt_translator'] == "libretranslate"
        ) {
            $tl = $url_query['target'];
            $sl = $url_query['source'];
        } else {
            // Defaulting to Google Translate query style
            $tl = $url_query['tl'];
            $sl = $url_query['sl'];
        }
    }

    $res = '<span title="Number of unknown words" class="status0" ' .
    'style="padding: 0 5px; margin: 0 5px;">' . $c . '</span>' .
    '<img src="/assets/icons/script-import.png" ' .
    'onclick="showRightFrames(\'bulk_translate_words.php?tid=' . $textid .
    '&offset=0&sl=' . $sl . '&tl=' . $tl . '\');" ' .
    'style="cursor: pointer; vertical-align:middle" title="Lookup New Words" ' .
    'alt="Lookup New Words" />';

    $show_buttons = (int) getSettingWithDefault('set-words-to-do-buttons');
    if ($show_buttons != 2) {
        $res .= '<input type="button" onclick="iknowall(' . $textid .
        ');" value="Set All to Known" />';
    }
    if ($show_buttons != 1) {
        $res .= '<input type="button" onclick="ignoreall(' . $textid .
        ');" value="Ignore All" />';
    }
    return $res;
}

/**
 * Prepare HTML interactions for the words left to do in this text.
 *
 * @param string|int $textid Text ID
 *
 * @return string HTML result
 *
 * @since 2.7.0-fork Adapted to use LibreTranslate dictionary as well.
 *
 * @deprecated Since 2.10.0, use todo_words_content instead
 */
function texttodocount2($textid): string
{
    if (is_string($textid)) {
        $textid = (int) $textid;
    }
    return todo_words_content($textid);
}

<?php

/**
 * \file
 * \brief Text navigation utilities.
 *
 * Functions for navigating between texts (previous/next arrows).
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-text-navigation.html
 * @since    3.0.0
 */

require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/tags.php';

/**
 * Return navigation arrows to previous and next texts.
 *
 * @param int    $textid  ID of the current text
 * @param string $url     Base URL to append before $textid
 * @param bool   $onlyann Restrict to annotated texts only
 * @param string $add     Some content to add before the output
 *
 * @return string Arrows to previous and next texts.
 */
function getPreviousAndNextTextLinks($textid, $url, $onlyann, $add): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $currentlang = validateLang(
        (string) processDBParam("filterlang", 'currentlanguage', '', false)
    );
    $wh_lang = '';
    if ($currentlang != '') {
        $wh_lang = ' AND TxLgID=' . $currentlang;
    }

    $currentquery = (string) processSessParam("query", "currenttextquery", '', false);
    $currentquerymode = (string) processSessParam(
        "query_mode",
        "currenttextquerymode",
        'title,text',
        false
    );
    $currentregexmode = getSettingWithDefault("set-regex-mode");
    $wh_query = $currentregexmode . 'LIKE ';
    if ($currentregexmode == '') {
        $wh_query .= convert_string_to_sqlsyntax(
            str_replace("*", "%", mb_strtolower($currentquery, 'UTF-8'))
        );
    } else {
        $wh_query .= convert_string_to_sqlsyntax($currentquery);
    }
    switch ($currentquerymode) {
        case 'title,text':
            $wh_query = ' AND (TxTitle ' . $wh_query . ' OR TxText ' . $wh_query . ')';
            break;
        case 'title':
            $wh_query = ' AND (TxTitle ' . $wh_query . ')';
            break;
        case 'text':
            $wh_query = ' AND (TxText ' . $wh_query . ')';
            break;
    }
    if ($currentquery == '') {
        $wh_query = '';
    }

    $currenttag1 = validateTextTag(
        (string) processSessParam("tag1", "currenttexttag1", '', false),
        $currentlang
    );
    $currenttag2 = validateTextTag(
        (string) processSessParam("tag2", "currenttexttag2", '', false),
        $currentlang
    );
    $currenttag12 = (string) processSessParam("tag12", "currenttexttag12", '', false);
    $wh_tag1 = null;
    $wh_tag2 = null;
    if ($currenttag1 == '' && $currenttag2 == '') {
        $wh_tag = '';
    } else {
        if ($currenttag1 != '') {
            if ($currenttag1 == -1) {
                $wh_tag1 = "group_concat(TtT2ID) IS NULL";
            } else {
                $wh_tag1 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag1 . "/%'";
            }
        }
        if ($currenttag2 != '') {
            if ($currenttag2 == -1) {
                $wh_tag2 = "group_concat(TtT2ID) IS NULL";
            } else {
                $wh_tag2 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag2 . "/%'";
            }
        }
        if ($currenttag1 != '' && $currenttag2 == '') {
            $wh_tag = " having (" . $wh_tag1 . ') ';
        } elseif ($currenttag2 != '' && $currenttag1 == '') {
            $wh_tag = " having (" . $wh_tag2 . ') ';
        } else {
            $wh_tag = " having ((" . $wh_tag1 . ($currenttag12 ? ') AND (' : ') OR (') . $wh_tag2 . ')) ';
        }
    }

    $currentsort = (int) processDBParam("sort", 'currenttextsort', '1', true);
    $sorts = array('TxTitle','TxID desc','TxID asc');
    $lsorts = count($sorts);
    if ($currentsort < 1) {
        $currentsort = 1;
    }
    if ($currentsort > $lsorts) {
        $currentsort = $lsorts;
    }

    if ($onlyann) {
        $sql =
        'SELECT TxID
        FROM (
            (' . $tbpref . 'texts
                LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID
            )
            LEFT JOIN ' . $tbpref . 'tags2 ON T2ID = TtT2ID
        ), ' . $tbpref . 'languages
        WHERE LgID = TxLgID AND LENGTH(TxAnnotatedText) > 0 '
        . $wh_lang . $wh_query . '
        GROUP BY TxID ' . $wh_tag . '
        ORDER BY ' . $sorts[$currentsort - 1];
    } else {
        $sql =
        'SELECT TxID
        FROM (
            (' . $tbpref . 'texts
                LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID
            )
            LEFT JOIN ' . $tbpref . 'tags2 ON T2ID = TtT2ID
        ), ' . $tbpref . 'languages
        WHERE LgID = TxLgID ' . $wh_lang . $wh_query . '
        GROUP BY TxID ' . $wh_tag . '
        ORDER BY ' . $sorts[$currentsort - 1];
    }

    $list = array(0);
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        array_push($list, (int) $record['TxID']);
    }
    mysqli_free_result($res);
    array_push($list, 0);
    $listlen = count($list);
    for ($i = 1; $i < $listlen - 1; $i++) {
        if ($list[$i] == $textid) {
            if ($list[$i - 1] !== 0) {
                $title = tohtml(getTextTitle($list[$i - 1]));
                $prev = '<a href="' . $url . $list[$i - 1] . '" target="_top"><img src="/assets/icons/navigation-180-button.png" title="Previous Text: ' . $title . '" alt="Previous Text: ' . $title . '" /></a>';
            } else {
                $prev = '<img src="/assets/icons/navigation-180-button-light.png" title="No Previous Text" alt="No Previous Text" />';
            }
            if ($list[$i + 1] !== 0) {
                $title = tohtml(getTextTitle($list[$i + 1]));
                $next = '<a href="' . $url . $list[$i + 1] .
                '" target="_top"><img src="/assets/icons/navigation-000-button.png" title="Next Text: ' . $title . '" alt="Next Text: ' . $title . '" /></a>';
            } else {
                $next = '<img src="/assets/icons/navigation-000-button-light.png" title="No Next Text" alt="No Next Text" />';
            }
            return $add . $prev . ' ' . $next;
        }
    }
    return $add . '<img src="/assets/icons/navigation-180-button-light.png" title="No Previous Text" alt="No Previous Text" />
    <img src="/assets/icons/navigation-000-button-light.png" title="No Next Text" alt="No Next Text" />';
}

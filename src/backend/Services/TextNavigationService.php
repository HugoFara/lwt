<?php

/**
 * Text Navigation Service - Navigation utilities for previous/next texts.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0 Migrated from Core/Text/text_navigation.php
 */

namespace Lwt\Services {

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Validation;
use Lwt\Database\Settings;

/**
 * Service class for text navigation.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TextNavigationService
{
    /**
     * Database table prefix.
     *
     * @var string
     */
    private string $tbpref;

    /**
     * Constructor - initialize table prefix.
     */
    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    /**
     * Return navigation arrows to previous and next texts.
     *
     * @param int    $textId  ID of the current text
     * @param string $url     Base URL to append before $textId
     * @param bool   $onlyAnn Restrict to annotated texts only
     * @param string $add     Some content to add before the output
     *
     * @return string Arrows to previous and next texts.
     */
    public function getPreviousAndNextTextLinks(int $textId, string $url, bool $onlyAnn, string $add): string
    {
        $currentlang = Validation::language(
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
        $currentregexmode = Settings::getWithDefault("set-regex-mode");
        $wh_query = $currentregexmode . 'LIKE ';
        if ($currentregexmode == '') {
            $wh_query .= Escaping::toSqlSyntax(
                str_replace("*", "%", mb_strtolower($currentquery, 'UTF-8'))
            );
        } else {
            $wh_query .= Escaping::toSqlSyntax($currentquery);
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

        $currenttag1 = Validation::textTag(
            (string) processSessParam("tag1", "currenttexttag1", '', false),
            $currentlang
        );
        $currenttag2 = Validation::textTag(
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

        if ($onlyAnn) {
            $sql = 'SELECT TxID
            FROM (
                (' . $this->tbpref . 'texts
                    LEFT JOIN ' . $this->tbpref . 'texttags ON TxID = TtTxID
                )
                LEFT JOIN ' . $this->tbpref . 'tags2 ON T2ID = TtT2ID
            ), ' . $this->tbpref . 'languages
            WHERE LgID = TxLgID AND LENGTH(TxAnnotatedText) > 0 '
            . $wh_lang . $wh_query . '
            GROUP BY TxID ' . $wh_tag . '
            ORDER BY ' . $sorts[$currentsort - 1];
        } else {
            $sql = 'SELECT TxID
            FROM (
                (' . $this->tbpref . 'texts
                    LEFT JOIN ' . $this->tbpref . 'texttags ON TxID = TtTxID
                )
                LEFT JOIN ' . $this->tbpref . 'tags2 ON T2ID = TtT2ID
            ), ' . $this->tbpref . 'languages
            WHERE LgID = TxLgID ' . $wh_lang . $wh_query . '
            GROUP BY TxID ' . $wh_tag . '
            ORDER BY ' . $sorts[$currentsort - 1];
        }

        $list = array(0);
        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            array_push($list, (int) $record['TxID']);
        }
        mysqli_free_result($res);
        array_push($list, 0);
        $listlen = count($list);
        for ($i = 1; $i < $listlen - 1; $i++) {
            if ($list[$i] == $textId) {
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
}

} // End namespace Lwt\Services

namespace {

// =============================================================================
// GLOBAL FUNCTION WRAPPERS (for backward compatibility)
// =============================================================================

use Lwt\Services\TextNavigationService;
use Lwt\Database\Connection;

/**
 * Get the title of a text by its ID.
 *
 * @param int $textId Text ID
 *
 * @return string Text title, or empty string if not found
 */
function getTextTitle(int $textId): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $sql = "SELECT TxTitle AS value FROM {$tbpref}texts WHERE TxID = " . (int) $textId;
    $res = Connection::query($sql);
    $record = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    return $record ? (string) $record['value'] : '';
}

/**
 * Return navigation arrows to previous and next texts.
 *
 * @param int    $textid  ID of the current text
 * @param string $url     Base URL to append before $textid
 * @param bool   $onlyann Restrict to annotated texts only
 * @param string $add     Some content to add before the output
 *
 * @return string Arrows to previous and next texts.
 *
 * @see TextNavigationService::getPreviousAndNextTextLinks()
 */
function getPreviousAndNextTextLinks(int $textid, string $url, bool|int $onlyann, string $add): string
{
    $service = new TextNavigationService();
    return $service->getPreviousAndNextTextLinks($textid, $url, (bool) $onlyann, $add);
}

} // End global namespace

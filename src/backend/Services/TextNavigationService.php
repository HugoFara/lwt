<?php declare(strict_types=1);
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
use Lwt\Core\Http\InputValidator;
use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;
use Lwt\Database\UserScopedQuery;
use Lwt\Database\Validation;
use Lwt\Database\Settings;
use Lwt\View\Helper\IconHelper;

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
        $params = [];

        $currentlang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );
        $wh_lang = '';
        if ($currentlang != '') {
            $wh_lang = ' AND TxLgID = ?';
            $params[] = $currentlang;
        }

        $currentquery = InputValidator::getStringWithSession("query", "currenttextquery");
        $currentquerymode = InputValidator::getStringWithSession(
            "query_mode",
            "currenttextquerymode",
            'title,text'
        );
        $currentregexmode = Settings::getWithDefault("set-regex-mode");
        $wh_query = '';
        if ($currentquery != '') {
            $queryParam = $currentregexmode == ''
                ? str_replace("*", "%", mb_strtolower($currentquery, 'UTF-8'))
                : $currentquery;

            $likeClause = $currentregexmode . 'LIKE ?';
            switch ($currentquerymode) {
                case 'title,text':
                    $wh_query = ' AND (TxTitle ' . $likeClause . ' OR TxText ' . $likeClause . ')';
                    $params[] = $queryParam;
                    $params[] = $queryParam;
                    break;
                case 'title':
                    $wh_query = ' AND (TxTitle ' . $likeClause . ')';
                    $params[] = $queryParam;
                    break;
                case 'text':
                    $wh_query = ' AND (TxText ' . $likeClause . ')';
                    $params[] = $queryParam;
                    break;
            }
        }

        $currenttag1 = Validation::textTag(
            InputValidator::getStringWithSession("tag1", "currenttexttag1"),
            $currentlang
        );
        $currenttag2 = Validation::textTag(
            InputValidator::getStringWithSession("tag2", "currenttexttag2"),
            $currentlang
        );
        $currenttag12 = InputValidator::getStringWithSession("tag12", "currenttexttag12");
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

        $currentsort = InputValidator::getIntWithDb("sort", 'currenttextsort', 1);
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
                (texts
                    LEFT JOIN texttags ON TxID = TtTxID
                )
                LEFT JOIN tags2 ON T2ID = TtT2ID
            ), languages
            WHERE LgID = TxLgID AND LENGTH(TxAnnotatedText) > 0 '
            . $wh_lang . $wh_query . '
            GROUP BY TxID ' . $wh_tag . '
            ORDER BY ' . $sorts[$currentsort - 1]
            . UserScopedQuery::forTablePrepared('texts', $params);
        } else {
            $sql = 'SELECT TxID
            FROM (
                (texts
                    LEFT JOIN texttags ON TxID = TtTxID
                )
                LEFT JOIN tags2 ON T2ID = TtT2ID
            ), languages
            WHERE LgID = TxLgID ' . $wh_lang . $wh_query . '
            GROUP BY TxID ' . $wh_tag . '
            ORDER BY ' . $sorts[$currentsort - 1]
            . UserScopedQuery::forTablePrepared('texts', $params);
        }

        $list = array(0);
        $rows = Connection::preparedFetchAll($sql, $params);
        foreach ($rows as $record) {
            array_push($list, (int) $record['TxID']);
        }
        array_push($list, 0);
        $listlen = count($list);
        for ($i = 1; $i < $listlen - 1; $i++) {
            if ($list[$i] == $textId) {
                if ($list[$i - 1] !== 0) {
                    $title = htmlspecialchars(getTextTitle($list[$i - 1]), ENT_QUOTES, 'UTF-8');
                    $prev = '<a href="' . $url . $list[$i - 1] . '" target="_top">' . IconHelper::render('circle-chevron-left', ['title' => 'Previous Text: ' . $title, 'alt' => 'Previous Text: ' . $title]) . '</a>';
                } else {
                    $prev = IconHelper::render('circle-chevron-left', ['title' => 'No Previous Text', 'alt' => 'No Previous Text', 'class' => 'icon-muted']);
                }
                if ($list[$i + 1] !== 0) {
                    $title = htmlspecialchars(getTextTitle($list[$i + 1]), ENT_QUOTES, 'UTF-8');
                    $next = '<a href="' . $url . $list[$i + 1] .
                    '" target="_top">' . IconHelper::render('circle-chevron-right', ['title' => 'Next Text: ' . $title, 'alt' => 'Next Text: ' . $title]) . '</a>';
                } else {
                    $next = IconHelper::render('circle-chevron-right', ['title' => 'No Next Text', 'alt' => 'No Next Text', 'class' => 'icon-muted']);
                }
                return $add . $prev . ' ' . $next;
            }
        }
        return $add . IconHelper::render('circle-chevron-left', ['title' => 'No Previous Text', 'alt' => 'No Previous Text', 'class' => 'icon-muted']) . '
        ' . IconHelper::render('circle-chevron-right', ['title' => 'No Next Text', 'alt' => 'No Next Text', 'class' => 'icon-muted']);
    }
}

} // End namespace Lwt\Services

namespace {

// =============================================================================
// GLOBAL FUNCTION WRAPPERS (for backward compatibility)
// =============================================================================

use Lwt\Services\TextNavigationService;
use Lwt\Database\QueryBuilder;

/**
 * Get the title of a text by its ID.
 *
 * @param int $textId Text ID
 *
 * @return string Text title, or empty string if not found
 */
function getTextTitle(int $textId): string
{
    $result = QueryBuilder::table('texts')
        ->where('TxID', '=', $textId)
        ->valuePrepared('TxTitle');
    return $result !== null ? (string) $result : '';
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

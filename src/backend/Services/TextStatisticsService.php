<?php declare(strict_types=1);
/**
 * Text Statistics Service - Word count and statistics functions.
 *
 * This service handles text statistics, word counts, and "words to do" progress tracking.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0 Migrated from Core/Text/text_statistics.php
 */

namespace Lwt\Services;

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Settings;
use Lwt\Database\UserScopedQuery;
use Lwt\View\Helper\IconHelper;

/**
 * Service class for text statistics operations.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TextStatisticsService
{

    /**
     * Return statistics about a list of text IDs.
     *
     * It is useful for unknown percent with this fork.
     *
     * @param string $textsId Texts ID separated by comma
     *
     * @return array{total: array, expr: array, stat: array, totalu: array, expru: array, statu: array}
     *               Total number of words, number of expressions, statistics, total unique,
     *               number of unique expressions, unique statistics
     */
    public function getTextWordCount(string $textsId): array
    {
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

        // Raw SQL needed for complex aggregation with DISTINCT LOWER()
        // textitems2 inherits user context via Ti2TxID -> texts FK
        $bindings = [];
        $sql = "SELECT Ti2TxID AS text, COUNT(DISTINCT LOWER(Ti2Text)) AS unique_cnt,
            COUNT(LOWER(Ti2Text)) AS total
            FROM textitems2
            WHERE Ti2WordCount = 1 AND Ti2TxID IN($textsId)
            GROUP BY Ti2TxID";
        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $r["total"][$record['text']] = $record['total'];
            $r["totalu"][$record['text']] = $record['unique_cnt'];
        }
        mysqli_free_result($res);

        // Raw SQL needed for complex aggregation with DISTINCT
        // textitems2 inherits user context via Ti2TxID -> texts FK
        $sql = "SELECT Ti2TxID AS text, COUNT(DISTINCT Ti2WoID) AS unique_cnt,
            COUNT(Ti2WoID) AS total
            FROM textitems2
            WHERE Ti2WordCount > 1 AND Ti2TxID IN({$textsId})
            GROUP BY Ti2TxID";
        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $r["expr"][$record['text']] = $record['total'];
            $r["expru"][$record['text']] = $record['unique_cnt'];
        }
        mysqli_free_result($res);

        // Raw SQL needed for complex aggregation with DISTINCT and implicit JOIN
        // textitems2 inherits user context via Ti2TxID -> texts FK
        // words has user scope (WoUsID), need to apply user filtering
        $bindings = [];
        $sql = "SELECT Ti2TxID AS text, COUNT(DISTINCT Ti2WoID) AS unique_cnt,
            COUNT(Ti2WoID) AS total, WoStatus AS status
            FROM textitems2, words
            WHERE Ti2WoID != 0 AND Ti2TxID IN({$textsId}) AND Ti2WoID = WoID"
            . UserScopedQuery::forTablePrepared('words', $bindings, 'words') .
            " GROUP BY Ti2TxID, WoStatus";
        $res = Connection::query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $r["stat"][$record['text']][$record['status']] = $record['total'];
            $r["statu"][$record['text']][$record['status']] = $record['unique_cnt'];
        }
        mysqli_free_result($res);

        return $r;
    }

    /**
     * Return the number of words left to do in this text.
     *
     * @param int $textId Text ID
     *
     * @return int Number of words
     */
    public function getTodoWordsCount(int $textId): int
    {
        // Raw SQL needed for COUNT(DISTINCT LOWER())
        // textitems2 inherits user context via Ti2TxID -> texts FK
        $count = Connection::fetchValue(
            "SELECT COUNT(DISTINCT LOWER(Ti2Text)) AS cnt
            FROM textitems2
            WHERE Ti2WordCount=1 AND Ti2WoID IS NULL AND Ti2TxID=$textId",
            'cnt'
        );
        if ($count === null) {
            return 0;
        }
        return (int) $count;
    }

    /**
     * Prepare HTML interactions for the words left to do in this text.
     *
     * @param int $textId Text ID
     *
     * @return string HTML result
     *
     * @since 2.7.0-fork Adapted to use LibreTranslate dictionary as well.
     */
    public function getTodoWordsContent(int $textId): string
    {
        $c = $this->getTodoWordsCount($textId);
        if ($c <= 0) {
            return '<span title="No unknown word remaining" class="status0 word-count-badge">' .
            $c . '</span>';
        }

        // Raw SQL with implicit JOIN
        // languages and texts both have user scope (LgUsID, TxUsID)
        $bindings = [];
        $sql = "SELECT LgGoogleTranslateURI
            FROM languages, texts
            WHERE LgID = TxLgID and TxID = $textId"
            . UserScopedQuery::forTablePrepared('languages', $bindings, 'languages')
            . UserScopedQuery::forTablePrepared('texts', $bindings, 'texts');
        $dict = (string) Connection::fetchValue($sql, 'LgGoogleTranslateURI');
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

        $bulkTranslateUrl = 'bulk_translate_words.php?tid=' . $textId .
            '&offset=0&sl=' . $sl . '&tl=' . $tl;
        $res = '<span title="Number of unknown words" class="status0 word-count-badge">' .
        $c . '</span>' .
        IconHelper::render('file-down', [
            'class' => 'bulk-translate-icon',
            'data-action' => 'bulk-translate',
            'data-url' => htmlspecialchars($bulkTranslateUrl, ENT_QUOTES, 'UTF-8'),
            'title' => 'Lookup New Words',
            'alt' => 'Lookup New Words'
        ]);

        $show_buttons = (int) Settings::getWithDefault('set-words-to-do-buttons');
        if ($show_buttons != 2) {
            $res .= '<input type="button" data-action="know-all" data-text-id="' . $textId .
            '" value="Set All to Known" />';
        }
        if ($show_buttons != 1) {
            $res .= '<input type="button" data-action="ignore-all" data-text-id="' . $textId .
            '" value="Ignore All" />';
        }
        return $res;
    }
}

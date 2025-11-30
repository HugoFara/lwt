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

namespace Lwt\Services {

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Settings;

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

        $res = Connection::query(
            "SELECT Ti2TxID AS text, COUNT(DISTINCT LOWER(Ti2Text)) AS value,
            COUNT(LOWER(Ti2Text)) AS total
            FROM {$this->tbpref}textitems2
            WHERE Ti2WordCount = 1 AND Ti2TxID IN($textsId)
            GROUP BY Ti2TxID"
        );
        while ($record = mysqli_fetch_assoc($res)) {
            $r["total"][$record['text']] = $record['total'];
            $r["totalu"][$record['text']] = $record['value'];
        }
        mysqli_free_result($res);

        $res = Connection::query(
            "SELECT Ti2TxID AS text, COUNT(DISTINCT Ti2WoID) AS value,
            COUNT(Ti2WoID) AS total
            FROM {$this->tbpref}textitems2
            WHERE Ti2WordCount > 1 AND Ti2TxID IN({$textsId})
            GROUP BY Ti2TxID"
        );
        while ($record = mysqli_fetch_assoc($res)) {
            $r["expr"][$record['text']] = $record['total'];
            $r["expru"][$record['text']] = $record['value'];
        }
        mysqli_free_result($res);

        $res = Connection::query(
            "SELECT Ti2TxID AS text, COUNT(DISTINCT Ti2WoID) AS value,
            COUNT(Ti2WoID) AS total, WoStatus AS status
            FROM {$this->tbpref}textitems2, {$this->tbpref}words
            WHERE Ti2WoID != 0 AND Ti2TxID IN({$textsId}) AND Ti2WoID = WoID
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
     * Return the number of words left to do in this text.
     *
     * @param int $textId Text ID
     *
     * @return int Number of words
     */
    public function getTodoWordsCount(int $textId): int
    {
        $count = Connection::fetchValue(
            "SELECT COUNT(DISTINCT LOWER(Ti2Text)) AS value
            FROM {$this->tbpref}textitems2
            WHERE Ti2WordCount=1 AND Ti2WoID=0 AND Ti2TxID=$textId"
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

        $dict = (string) Connection::fetchValue(
            "SELECT LgGoogleTranslateURI AS value
            FROM {$this->tbpref}languages, {$this->tbpref}texts
            WHERE LgID = TxLgID and TxID = $textId"
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

        $bulkTranslateUrl = 'bulk_translate_words.php?tid=' . $textId .
            '&offset=0&sl=' . $sl . '&tl=' . $tl;
        $res = '<span title="Number of unknown words" class="status0 word-count-badge">' .
        $c . '</span>' .
        '<img src="/assets/icons/script-import.png" class="bulk-translate-icon" ' .
        'data-action="bulk-translate" data-url="' . htmlspecialchars($bulkTranslateUrl, ENT_QUOTES, 'UTF-8') . '" ' .
        'title="Lookup New Words" alt="Lookup New Words" />';

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

} // End namespace Lwt\Services

namespace {

// =============================================================================
// GLOBAL FUNCTION WRAPPERS (for backward compatibility)
// =============================================================================

use Lwt\Services\TextStatisticsService;

/**
 * Return statistics about a list of text ID.
 *
 * @param string $textsId Texts ID separated by comma
 *
 * @return array Statistics data
 *
 * @see TextStatisticsService::getTextWordCount()
 */
function return_textwordcount(string|int $textsId): array
{
    $service = new TextStatisticsService();
    return $service->getTextWordCount((string) $textsId);
}

/**
 * Compute and echo word statistics about a list of text ID.
 *
 * @param string $textID Text IDs separated by comma
 *
 * @deprecated 2.9.0 Use return_textwordcount instead.
 */
function textwordcount(string $textID): void
{
    echo json_encode(return_textwordcount($textID));
}

/**
 * Return the number of words left to do in this text.
 *
 * @param int $textid Text ID
 *
 * @return int Number of words
 *
 * @see TextStatisticsService::getTodoWordsCount()
 */
function todo_words_count(int $textid): int
{
    $service = new TextStatisticsService();
    return $service->getTodoWordsCount($textid);
}

/**
 * Prepare HTML interactions for the words left to do in this text.
 *
 * @param int $textid Text ID
 *
 * @return string HTML result
 *
 * @see TextStatisticsService::getTodoWordsContent()
 */
function todo_words_content(int $textid): string
{
    $service = new TextStatisticsService();
    return $service->getTodoWordsContent($textid);
}

/**
 * Prepare HTML interactions for the words left to do in this text.
 *
 * @param string|int $textid Text ID
 *
 * @return string HTML result
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

} // End global namespace

<?php declare(strict_types=1);
/**
 * Text Reading Service - Functions for displaying text in reading view.
 *
 * Functions for displaying text with word statuses, translations, and annotations.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0 Migrated from Core/Text/text_display.php
 */

namespace Lwt\Services {

use Lwt\Core\Globals;
use Lwt\Core\StringUtils;
use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;
use Lwt\Services\ExportService;
use Lwt\Services\TagService;

/**
 * Service class for text reading display.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress UnusedClass - Service class for text reading functionality
 */
class TextReadingService
{
    /**
     * Print the output when the word is a term.
     *
     * @param int                   $actcode       Action code, > 1 for multiword
     * @param bool                  $showAll       Show all words or not
     * @param string                $spanid        ID for this span element
     * @param string                $hidetag       Hide tag string
     * @param int                   $currcharcount Current number of characters
     * @param array<string, string> $record        Various data
     * @param array                 $exprs         Current expressions (passed by reference)
     *
     * @return void
     */
    public function echoTerm(
        int $actcode,
        bool $showAll,
        string $spanid,
        string $hidetag,
        int $currcharcount,
        array $record,
        array &$exprs = array()
    ): void {
        $actcode = (int)$record['Code'];
        if ($actcode > 1) {
            // A multiword, $actcode is the number of words composing it
            if (empty($exprs) || $exprs[sizeof($exprs) - 1][1] != $record['TiText']) {
                $exprs[] = array($actcode, $record['TiText'], $actcode);
            }

            if (isset($record['WoID'])) {
                $attributes = array(
                    'id' => $spanid,
                    'class' => implode(
                        " ",
                        [
                            $hidetag, "click", "mword", ($showAll ? 'mwsty' : 'wsty'),
                            "order" . $record['Ti2Order'],
                            'word' . $record['WoID'], 'status' . $record['WoStatus'],
                            'TERM' . StringUtils::toClassName($record['TiTextLC'])
                        ]
                    ),
                    'data_pos' => $currcharcount,
                    'data_order' => $record['Ti2Order'],
                    'data_wid' => $record['WoID'],
                    'data_trans' => htmlspecialchars(
                        ExportService::replaceTabNewline($record['WoTranslation'] ?? '') .
                        (($tags = TagService::getWordTagList((int)$record['WoID'], false)) ? ' [' . $tags . ']' : ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ),
                    'data_rom' => htmlspecialchars($record['WoRomanization'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'data_status' => $record['WoStatus'],
                    'data_code' => $actcode,
                    'data_text' => htmlspecialchars($record['TiText'] ?? '', ENT_QUOTES, 'UTF-8')
                );
                $span = '<span';
                foreach ($attributes as $attr_name => $val) {
                    $span .= ' ' . $attr_name . '="' . $val . '"';
                }
                $span .= '>';
                if ($showAll) {
                    $span .= $actcode;
                } else {
                    $span .= htmlspecialchars($record['TiText'] ?? '', ENT_QUOTES, 'UTF-8');
                }
                $span .= '</span>';
                echo $span;
            }
        } else {
            // Single word
            if (isset($record['WoID'])) {
                // Word found status 1-5|98|99
                $attributes = array(
                    'id' => $spanid,
                    'class' => implode(
                        " ",
                        [
                            $hidetag, "click", "word", "wsty", "word" . $record['WoID'],
                            'status' . $record['WoStatus'],
                            'TERM' . StringUtils::toClassName($record['TiTextLC'])
                        ]
                    ),
                    'data_pos' => $currcharcount,
                    'data_order' => $record['Ti2Order'],
                    'data_wid' => $record['WoID'],
                    'data_trans' => htmlspecialchars(
                        ExportService::replaceTabNewline($record['WoTranslation'] ?? '') .
                        (($tags = TagService::getWordTagList((int)$record['WoID'], false)) ? ' [' . $tags . ']' : ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ),
                    'data_rom' => htmlspecialchars($record['WoRomanization'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'data_status' => $record['WoStatus']
                );
            } else {
                // Not registered word (status 0)
                $attributes = array(
                    'id' => $spanid,
                    'class' => implode(
                        " ",
                        [
                            $hidetag, "click", "word", "wsty", "status0",
                            "TERM" . StringUtils::toClassName($record['TiTextLC'])
                        ]
                    ),
                    'data_pos' => $currcharcount,
                    'data_order' => $record['Ti2Order'],
                    'data_trans' => '',
                    'data_rom' => '',
                    'data_status' => '0',
                    'data_wid' => ''
                );
            }
            foreach ($exprs as $expr) {
                $attributes['data_mw' . $expr[0]] = htmlspecialchars($expr[1] ?? '', ENT_QUOTES, 'UTF-8');
            }
            $span = '<span';
            foreach ($attributes as $attr_name => $val) {
                $span .= ' ' . $attr_name . '="' . $val . '"';
            }
            $span .= '>' . htmlspecialchars($record['TiText'] ?? '', ENT_QUOTES, 'UTF-8') . '</span>';
            echo $span;
            for ($i = sizeof($exprs) - 1; $i >= 0; $i--) {
                $exprs[$i][2]--;
                if ($exprs[$i][2] < 1) {
                    unset($exprs[$i]);
                    $exprs = array_values($exprs);
                }
            }
        }
    }

    /**
     * Check if a new sentence SPAN should be started.
     *
     * @param int $sid     Sentence ID
     * @param int $old_sid Old sentence ID
     *
     * @return int Sentence ID
     */
    public function parseSentence(int $sid, int $old_sid): int
    {
        if ($sid == $old_sid) {
            return $sid;
        }
        if ($sid != 0) {
            echo '</span>';
        }
        $sid = $old_sid;
        echo '<span id="sent_', $sid, '">';
        return $sid;
    }

    /**
     * Process each text item (can be punctuation, term, etc...)
     *
     * @param array $record        Text item information
     * @param int   $showAll       Show all words or not (0 or 1)
     * @param int   $currcharcount Current number of characters
     * @param bool  $hide          Should some item be hidden, depends on $showAll
     * @param array $exprs         Current expressions
     *
     * @return void
     */
    public function parseItem(
        array $record,
        int $showAll,
        int $currcharcount,
        bool $hide,
        array &$exprs = array()
    ): void {
        $actcode = (int)$record['Code'];
        $spanid = 'ID-' . $record['Ti2Order'] . '-' . $actcode;

        // Check if item should be hidden
        $hidetag = $hide ? ' hide' : '';

        if ($record['TiIsNotWord'] != 0) {
            // The current item is not a term (likely punctuation)
            echo "<span id=\"$spanid\" class=\"$hidetag\">" .
            str_replace("¶", '<br />', htmlspecialchars($record['TiText'] ?? '', ENT_QUOTES, 'UTF-8')) . '</span>';
        } else {
            // A term (word or multi-word)
            $this->echoTerm(
                $actcode,
                (bool)$showAll,
                $spanid,
                $hidetag,
                $currcharcount,
                $record,
                $exprs
            );
        }
    }

    /**
     * Get all words and start the iterate over them.
     *
     * @param int $textId  ID of the text
     * @param int $showAll Show all words or not (0 or 1)
     *
     * @return void
     */
    public function mainWordLoop(int $textId, int $showAll): void
    {
        $res = QueryBuilder::table('textitems2')
            ->selectRaw('CASE WHEN `Ti2WordCount`>0 THEN Ti2WordCount ELSE 1 END AS Code')
            ->selectRaw('CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN Ti2Text ELSE `WoText` END AS TiText')
            ->selectRaw('CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN LOWER(Ti2Text) ELSE `WoTextLC` END AS TiTextLC')
            ->select(['Ti2Order', 'Ti2SeID'])
            ->selectRaw('CASE WHEN `Ti2WordCount`>0 THEN 0 ELSE 1 END AS TiIsNotWord')
            ->selectRaw('CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN CHAR_LENGTH(Ti2Text) ELSE CHAR_LENGTH(`WoTextLC`) END AS TiTextLength')
            ->select(['WoID', 'WoText', 'WoStatus', 'WoTranslation', 'WoRomanization'])
            ->leftJoin('words', 'Ti2WoID', '=', 'WoID')
            ->where('Ti2TxID', '=', $textId)
            ->orderBy('Ti2Order', 'ASC')
            ->orderBy('Ti2WordCount', 'DESC')
            ->get();
        $currcharcount = 0;
        $hidden_items = array();
        $exprs = array();
        $cnt = 1;
        $sid = 0;
        $last = -1;

        // Loop over words and punctuation
        while ($record = mysqli_fetch_assoc($res)) {
            $sid = $this->parseSentence($sid, (int) $record['Ti2SeID']);
            if ($cnt < $record['Ti2Order']) {
                echo '<span id="ID-' . $cnt++ . '-1"></span>';
            }
            if ($showAll) {
                $hide = isset($record['WoID'])
                && array_key_exists((int) $record['WoID'], $hidden_items);
            } else {
                $hide = $record['Ti2Order'] <= $last;
            }

            $this->parseItem($record, $showAll, $currcharcount, $hide, $exprs);
            if ((int)$record['Code'] == 1) {
                $currcharcount += $record['TiTextLength'];
                $cnt++;
            }
            $last = max(
                $last,
                (int) $record['Ti2Order'] + ((int)$record['Code'] - 1) * 2
            );
            if ($showAll) {
                if (
                    isset($record['WoID'])
                    && !array_key_exists((int) $record['WoID'], $hidden_items)
                ) {
                    $hidden_items[(int) $record['WoID']] = (int) $record['Ti2Order']
                    + ((int)$record['Code'] - 1) * 2;
                }
                // Clean the already finished items
                $hidden_items = array_filter(
                    $hidden_items,
                    fn ($val) => $val >= $record['Ti2Order'],
                );
            }
        }

        mysqli_free_result($res);
        echo '<span id="totalcharcount" class="hide">' . $currcharcount . '</span>';
    }
}

} // End namespace Lwt\Services

namespace {

} // End global namespace

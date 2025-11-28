<?php

/**
 * \file
 * \brief Text display functions for rendering words in reading view.
 *
 * Functions for displaying text with word statuses, translations, and annotations.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-text-display.html
 * @since    3.0.0
 */

require_once __DIR__ . '/../Globals.php';
require_once __DIR__ . '/../Database/Connection.php';
require_once __DIR__ . '/../UI/ui_helpers.php';

use Lwt\Database\Connection;

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
function echo_term(
    int $actcode,
    bool $showAll,
    string $spanid,
    string $hidetag,
    int $currcharcount,
    array $record,
    array &$exprs = array()
) {
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
                'TERM' . strToClassName($record['TiTextLC'])]
            ),
            'data_pos' => $currcharcount,
            'data_order' => $record['Ti2Order'],
            'data_wid' => $record['WoID'],
            'data_trans' => tohtml(
                repl_tab_nl($record['WoTranslation']) .
                getWordTagList($record['WoID'], ' ', 1, 0)
            ),
            'data_rom' => tohtml($record['WoRomanization']),
            'data_status' => $record['WoStatus'],
                'data_code' =>  $actcode,
                'data_text' => tohtml($record['TiText'])
            );
            $span = '<span';
            foreach ($attributes as $attr_name => $val) {
                $span .= ' ' . $attr_name . '="' . $val . '"';
            }
            $span .= '>';
            if ($showAll) {
                $span .= $actcode;
            } else {
                $span .= tohtml($record['TiText']);
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
                    'TERM' . strToClassName($record['TiTextLC'])
                ]
            ),
            'data_pos' => $currcharcount,
            'data_order' => $record['Ti2Order'],
            'data_wid' => $record['WoID'],
            'data_trans' => tohtml(
                repl_tab_nl($record['WoTranslation']) .
                getWordTagList($record['WoID'], ' ', 1, 0)
            ),
            'data_rom' => tohtml($record['WoRomanization']),
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
                    "TERM" . strToClassName($record['TiTextLC'])
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
            $attributes['data_mw' . $expr[0]] = tohtml($expr[1]);
        }
        $span = '<span';
        foreach ($attributes as $attr_name => $val) {
            $span .= ' ' . $attr_name . '="' . $val . '"';
        }
        $span .= '>' . tohtml($record['TiText']) . '</span>';
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
function sentence_parser($sid, $old_sid)
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
 * Process each text item (can be punction, term, etc...)
 *
 * @param array $record        Text item information
 * @param 0|1   $showAll       Show all words or not
 * @param int   $currcharcount Current number of caracters
 * @param bool  $hide          Should some item be hidden, depends on $showAll
 * @param array $exprs         Current expressions
 *
 * @return void
 *
 * @since 2.5.0-fork
 * @since 2.8.0-fork Take a new optional arguent $exprs
 */
function item_parser(
    $record,
    $showAll,
    $currcharcount,
    $hide,
    &$exprs = array()
): void {
    $actcode = (int)$record['Code'];
    $spanid = 'ID-' . $record['Ti2Order'] . '-' . $actcode;

    // Check if item should be hidden
    $hidetag = $hide ? ' hide' : '';

    if ($record['TiIsNotWord'] != 0) {
        // The current item is not a term (likely punctuation)
        echo "<span id=\"$spanid\" class=\"$hidetag\">" .
        str_replace("¶", '<br />', tohtml($record['TiText'])) . '</span>';
    } else {
        // A term (word or multi-word)
        echo_term(
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
 * @param int $textid  ID of the text
 * @param int $showAll Show all words or not (0 or 1)
 *
 * @return void
 */
function main_word_loop($textid, $showAll): void
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();

    $sql =
    "SELECT
     CASE WHEN `Ti2WordCount`>0 THEN Ti2WordCount ELSE 1 END AS Code,
     CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN Ti2Text ELSE `WoText` END AS TiText,
     CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN LOWER(Ti2Text) ELSE `WoTextLC` END AS TiTextLC,
     Ti2Order, Ti2SeID,
     CASE WHEN `Ti2WordCount`>0 THEN 0 ELSE 1 END AS TiIsNotWord,
     CASE
        WHEN CHAR_LENGTH(Ti2Text)>0
        THEN CHAR_LENGTH(Ti2Text)
        ELSE CHAR_LENGTH(`WoTextLC`)
     END AS TiTextLength,
     WoID, WoText, WoStatus, WoTranslation, WoRomanization
     FROM {$tbpref}textitems2 LEFT JOIN {$tbpref}words ON Ti2WoID = WoID
     WHERE Ti2TxID = $textid
     ORDER BY Ti2Order asc, Ti2WordCount desc";

    $res = Connection::query($sql);
    $currcharcount = 0;
    $hidden_items = array();
    $exprs = array();
    $cnt = 1;
    $sid = 0;
    $last = -1;

    // Loop over words and punctuation
    while ($record = mysqli_fetch_assoc($res)) {
        $sid = sentence_parser($sid, $record['Ti2SeID']);
        if ($cnt < $record['Ti2Order']) {
            echo '<span id="ID-' . $cnt++ . '-1"></span>';
        }
        if ($showAll) {
            $hide = isset($record['WoID'])
            && array_key_exists((int) $record['WoID'], $hidden_items);
        } else {
            $hide = $record['Ti2Order'] <= $last;
        }

        item_parser($record, $showAll, $currcharcount, $hide, $exprs);
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
                && !array_key_exists((int) $record['WoID'], $hidden_items) // !$hide
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

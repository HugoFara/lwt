<?php

/**
 * \file
 * \brief Show text header frame
 *
 * Call: do_text_text.php?text=[textid]
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/do-text-text.html
 * @since   1.0.3
 */

require_once 'Core/session_utility.php';

use Lwt\Database\Settings;

/**
 * Get the record for this text in the database.
 *
 * @param string|int $textid ID of the text
 *
 * @return array{TxLgID: int, TxTitle: string, TxAnnotatedText: string,
 * TxPosition: int}|false|null Record corresponding to this text.
 *
 * @global string $tbpref Table name prefix
 *
 * @psalm-return array<string, float|int|null|string>|false|null
 */
function get_text_data($textid)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $sql =
    'SELECT TxLgID, TxTitle, TxAnnotatedText, TxPosition
    FROM ' . $tbpref . 'texts
    WHERE TxID = ' . $textid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    return $record;
}

/**
 * Get the record for this text in the database.
 *
 * @param string $textid ID of the text
 *
 * @return array{TxLgID: int, TxTitle: string, TxAnnotatedText: string,
 * TxPosition: int}|false|null Record corresponding to this text.
 *
 * @global string $tbpref Table name prefix
 *
 * @deprecated Use get_text_data instead.
 *
 * @psalm-return array<string, float|int|null|string>|false|null
 */
function getTextData($textid)
{
    return get_text_data($textid);
}

/**
 * Return the settings relative to this language.
 *
 * @param int $langid Language ID as defined in the database.
 *
 * @return array{LgName: string, LgDict1URI: string,
 * LgDict2URI: string, LgGoogleTranslateURI: string, LgTextSize: int,
 * LgRegexpWordCharacters: string, LgRemoveSpaces: int,
 * LgRightToLeft: int, Lg}|false|null Record corresponding to this language.
 *
 * @global string $tbpref Table name prefix
 *
 * @psalm-return array<string, float|int|null|string>|false|null
 */
function get_language_settings($langid)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $sql =
    'SELECT LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI,
    LgTextSize, LgRegexpWordCharacters, LgRemoveSpaces, LgRightToLeft
    FROM ' . $tbpref . 'languages
    WHERE LgID = ' . $langid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    return $record;
}

/**
 * Return the settings relative to this language.
 *
 * @param int $langid Language ID as defined in the database.
 *
 * @return array{LgName: string, LgDict1URI: string,
 * LgDict2URI: string, LgGoogleTranslateURI: string, LgTextSize: int,
 * LgRemoveSpaces: int, LgRightToLeft: int}|false|null Record corresponding to this language.
 *
 * @global string $tbpref Table name prefix
 *
 * @deprecated Use get_language_settings instead.
 *
 * @psalm-return array<string, float|int|null|string>|false|null
 */
function getLanguagesSettings($langid)
{
    return get_language_settings($langid);
}


/**
 * Print the output when the word is a term (word or multi-word).
 *
 * @param int                   $actcode       Action code, number of words forming
 *                                             the term (> 1 for multiword)
 * @param bool                  $showAll       Show all words or not
 * @param string                $spanid        ID for this span element
 * @param string                $hidetag       Hide tag string
 * @param int                   $currcharcount Current number of characters
 * @param array<string, string> $record        Various data
 * @param array                 $exprs         Current expressions
 *
 * @return void
 *
 * @since 2.8.0-fork Takes a new argument $exprs
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
 * Print the output when the word is a term.
 *
 * @param int                   $actcode       Action code, > 1 for multiword
 * @param bool                  $showAll       Show all words or not
 * @param int                   $hideuntil     Unused, kept for backwards compatibility
 * @param string                $spanid        ID for this span element
 * @param string                $hidetag       Hide tag string
 * @param int                   $currcharcount Current number of characters
 * @param array<string, string> $record        Various data
 *
 * @since 2.2.1 Return 0 instead of a new value for $hideuntil
 *
 * @deprecated Use echo_term instead.
 */
function echoTerm(
    int $actcode,
    bool $showAll,
    int $hideuntil,
    string $spanid,
    string $hidetag,
    int $currcharcount,
    array $record
): int {
    echo_term($actcode, $showAll, $spanid, $hidetag, $currcharcount, $record);
    return 0;
}


/**
 * Process each word (can be punction, term, etc...). Caused laggy texts, replaced by wordParser.
 *
 * @param string[] $record        Record information
 * @param 0|1      $showAll       Show all words or not
 * @param int      $currcharcount Current number of caracters
 *
 * @return int New number of caracters
 *
 * @deprecated Use sentenceParser and wordParser instead.
 */
function wordProcessor($record, $showAll, $currcharcount): int
{
    $cnt = 1;
    $sid = 0;

    if ($sid != (int) $record['Ti2SeID']) {
        $sid = $record['Ti2SeID'];
        echo '<span id="sent_', $sid, '">';
    }
    $actcode = (int)$record['Code'];
    $spanid = 'ID-' . $record['Ti2Order'] . '-' . $actcode;

    // Check if work should be hidden
    $hidetag = '';

    if ($cnt < $record['Ti2Order']) {
        echo '<span id="ID-' . $cnt . '-1"></span>';
    }
    // The current word is not a term
    if ($record['TiIsNotWord'] != 0) {
        echo '<span id="' . $spanid . '" class="' . $hidetag . '">' .
        str_replace("¶", '<br />', tohtml($record['TiText'])) .
        '</span>';
    } else {
        echo_term(
            $actcode,
            (bool)$showAll,
            $spanid,
            $hidetag,
            $currcharcount,
            $record
        );
    }

    if ($actcode == 1) {
        $currcharcount += (int)$record['TiTextLength'];
    }

    return $currcharcount;
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
 * Check if a new sentence SPAN should be started.
 *
 * @param int $sid     Sentence ID
 * @param int $old_sid Old sentence ID
 *
 * @return int Sentence ID
 *
 * @deprecated Use sentence_parser instead.
 */
function sentenceParser($sid, $old_sid)
{
    return sentence_parser($sid, $old_sid);
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
 * Process each text item (can be punction, term, etc...)
 *
 * @param string[] $record        Record information
 * @param 0|1      $showAll       Show all words or not
 * @param int      $currcharcount Current number of caracters
 * @param int      $hideuntil     Should the value be hidden or not
 *
 * @return int New value for $hideuntil
 *
 * @deprecated Use item_parser instead (since 2.5.0-fork).
 */
function word_parser($record, $showAll, $currcharcount, $hideuntil): int
{
    $actcode = (int)$record['Code'];
    $spanid = 'ID-' . $record['Ti2Order'] . '-' . $actcode;

    // Check if item should be hidden
    $hidetag = '';
    if ($record['Ti2Order'] <= $hideuntil) {
        if (!$showAll || ($showAll && $actcode > 1)) {
            $hidetag = ' hide';
        }
    } else {
        $hidetag = '';
        $hideuntil = -1;
    }

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
            $record
        );
        if ($hideuntil == -1) {
            $hideuntil = (int)$record['Ti2Order'] + ($actcode - 1) * 2;
        }
    }

    return $hideuntil;
}

/**
 * Process each word (can be punction, term, etc...)
 *
 * @param string[] $record        Record information
 * @param 0|1      $showAll       Show all words or not
 * @param int      $currcharcount Current number of caracters
 * @param int      $hideuntil     Should the value be hidden or not
 *
 * @return int New value for $hideuntil
 *
 * @deprecated Use word_parser instead.
 */
function wordParser($record, $showAll, $currcharcount, $hideuntil): int
{
    return word_parser($record, $showAll, $currcharcount, $hideuntil);
}

/**
 * Get all words and start the iterate over them.
 *
 * @param string $textid  ID of the text
 * @param 0|1    $showAll Show all words or not
 *
 * @return void
 *
 * @global string $tbpref Table name prefix
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

    $res = do_mysqli_query($sql);
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

/**
 * Get all words and start the iterate over them.
 *
 * @param string $textid  ID of the text
 * @param 0|1    $showAll Show all words or not
 *
 * @return void
 *
 * @global string $tbpref Table name prefix
 *
 * @deprecated Use main_word_loop instead.
 */
function mainWordLoop($textid, $showAll): void
{
    main_word_loop($textid, $showAll);
}

/**
 * Prepare style for showing word status. Write a now STYLE object
 *
 * @param int       $showLearning 1 to show learning translations
 * @param int<1, 4> $mode_trans   Annotation position
 * @param int       $textsize     Text font size
 * @param bool      $ann_exists   Does annotations exist for this text
 *
 * @return void
 */
function do_text_text_style(int $showLearning, int $mode_trans, int $textsize, bool $ann_exists): void
{
    $displaystattrans = (int)Settings::getWithDefault('set-display-text-frame-term-translation');
    $pseudo_element = ($mode_trans < 3) ? 'after' : 'before';
    $data_trans = $ann_exists ? 'data_ann' : 'data_trans';
    $stat_arr = array(1, 2, 3, 4, 5, 98, 99);
    $ruby = $mode_trans == 2 || $mode_trans == 4;

    echo '<style>';
    if ($showLearning) {
        foreach ($stat_arr as $value) {
            if (checkStatusRange($value, $displaystattrans)) {
                echo '.wsty.status', $value, ':',
                $pseudo_element, ',.tword.content', $value, ':',
                $pseudo_element,'{content: attr(',$data_trans,');}';
                echo '.tword.content', $value,':',
                $pseudo_element,'{color:rgba(0,0,0,0)}',"\n";
            }
        }
    }
    if ($ruby) {
        echo '.wsty {',
        ($mode_trans == 4 ? 'margin-top: 0.2em;' : 'margin-bottom: 0.2em;'),
        'text-align: center;
            display: inline-block;',
        ($mode_trans == 2 ? 'vertical-align: top;' : ''),
        '}',"\n";

        echo '.wsty:', $pseudo_element,
        '{
            display: block !important;',
        ($mode_trans == 2 ? 'margin-top: -0.05em;' : 'margin-bottom: -0.15em;'),
        '}',"\n";
    }
    $ann_textsize = array(100 => 50, 150 => 50, 200 => 40, 250 => 25);
    echo '.tword:', $pseudo_element,
    ',.wsty:', $pseudo_element,
    '{',
    ($ruby ? 'text-align: center;' : ''),
    'font-size:' . $ann_textsize[$textsize] . '%;',
    ($mode_trans == 1 ? 'margin-left: 0.2em;' : ''),
    ($mode_trans == 3 ? 'margin-right: 0.2em;' : ''),
    ($ann_exists ? '' : '
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        display: inline-block;
        vertical-align: -25%;'),
    '}';

    echo '.hide {' .
        'display:none !important;
    }';
    echo '.tword:',
    $pseudo_element, ($ruby ? ',.word:' : ',.wsty:'),
    $pseudo_element, '{max-width:15em;}';
    echo '</style>';
}

/**
 * Prepare style for showing word status. Write a now STYLE object
 *
 * @param int       $showLearning 1 to show learning translations
 * @param int<1, 4> $mode_trans   Annotation position
 * @param int       $textsize     Text font size
 * @param bool      $ann_exists   Does annotations exist for this text
 *
 * @return void
 *
 * @deprecated Use do_text_text_style instead.
 */
function prepareStyle(int $showLearning, int $mode_trans, int $textsize, bool $ann_exists): void
{
    do_text_text_style($showLearning, $mode_trans, $textsize, $ann_exists);
}

/**
 * Print JavaScript-formatted content.
 *
 * @param array<string, mixed> Associative array of all global variables for JS
 *
 * @return void
 */
function do_text_text_javascript(array $var_array): void
{
    ?>
<script type="text/javascript">
    //<![CDATA[

    /// Map global variables as a JSON object
    const new_globals = <?php echo json_encode($var_array); ?>;

    // Set global variables
    for (let key in new_globals) {
        if (typeof new_globals[key] !== 'string') {
            for (let subkey1 in new_globals[key]) {
                if (typeof new_globals[key] !== 'string') {
                    for (let subkey2 in new_globals[key][subkey1]) {
                        window[key][subkey1][subkey2] = new_globals[key][subkey1][subkey2];
                    }
                } else {
                    window[key][subkey1] = new_globals[key][subkey1];
                }
            }
        } else {
            window[key] = new_globals[key];
        }
    }
    LANG = getLangFromDict(LWT_DATA.language.translator_link);
    LWT_DATA.text.reading_position = -1;
    // Note from 2.10.0: is the next line necessary on text?
    LWT_DATA.test.answer_opened = false;
    // Change the language of the current frame
    if (LANG && LANG != LWT_DATA.language.translator_link) {
        $("html").attr('lang', LANG);
    }

    if (LWT_DATA.settings.jQuery_tooltip) {
        $(function () {
            $('#overDiv').tooltip();
            $('#thetext').tooltip_wsty_init();
        });
    }


    /**
     * Save the current reading position.
     * @global {string} LWT_DATA.text.id Text ID
     *
     * @since 2.0.3-fork
     */
    function saveCurrentPosition() {
        let pos = 0;
        // First position from the top
        const top_pos = $(window).scrollTop() - $('.wsty').not('.hide').eq(0).height();
        $('.wsty').not('.hide').each(function() {
            if ($(this).offset().top >= top_pos) {
                pos = $(this).attr('data_pos');
                return;
            }
        });
        saveReadingPosition(LWT_DATA.text.id, pos);
    }

    $(document).ready(prepareTextInteractions);
    $(document).ready(goToLastPosition);
    $(window).on('beforeunload', saveCurrentPosition);
    //]]>
</script>
    <?php
}

/**
 * Print JavaScript-formatted content.
 *
 * @param array<string, mixed> Associative array of all global variables for JS
 *
 * @return void
 *
 * @deprecated Use do_text_text_javascript instead.
 */
function do_text_javascript(array $var_array): void
{
    do_text_text_javascript($var_array);
}

/**
 * Main function for displaying sentences. It will print HTML content.
 *
 * @param string|int $textid    ID of the requiered text
 * @param bool       $only_body If true, only show the inner body. If false, create a complete HTML document.
 */
function do_text_text_content($textid, $only_body = true): void
{
    // Text settings
    $record = get_text_data($textid);
    $title = $record['TxTitle'];
    $langid = (int)$record['TxLgID'];
    $ann = (string) $record['TxAnnotatedText'];
    $pos = $record['TxPosition'];

    // Language settings
    $record = get_language_settings($langid);
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    $wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
    $textsize = $record['LgTextSize'];
    $removeSpaces = $record['LgRemoveSpaces'];
    $rtlScript = (bool)$record['LgRightToLeft'];

    // User settings
    $showAll = Settings::getZeroOrOne('showallwords', 1);
    $showLearning = Settings::getZeroOrOne('showlearningtranslations', 1);

    /**
     * Annotation position between 0 and 4
     */
    $mode_trans = (int) Settings::getWithDefault('set-text-frame-annotation-position');
    /**
     * Ruby annotations
     */
    $ruby = $mode_trans == 2 || $mode_trans == 4;

    if (!$only_body) {
        // Start the page with a HEAD and opens a BODY tag
        pagestart_nobody($title);
    }
    ?>
    <script type="text/javascript" src="/assets/js/jquery.hoverIntent.js" charset="utf-8"></script>
    <?php
    $visit_status = Settings::getWithDefault('set-text-visit-statuses-via-key');
    if ($visit_status == '') {
        $visit_status = '0';
    }
    $var_array = array(
        // Change globals from jQuery hover
        'LWT_DATA' => array(

            'language' => array(
                'id'              => $langid,
                'dict_link1'      => $wb1,
                'dict_link2'      => $wb2,
                'translator_link' => $wb3,
                'delimiter'       => tohtml(
                    str_replace(
                        array('\\',']','-','^'),
                        array('\\\\','\\]','\\-','\\^'),
                        Settings::getWithDefault('set-term-translation-delimiters')
                    )
                ),
                'word_parsing'    => $record['LgRegexpWordCharacters'],
                'rtl'             => $rtlScript
            ),

            'text' => array(
                'id'               => $textid,
                'reading_position' => $pos,
                'annotations'      => json_decode(annotation_to_json($ann))
            ),

            'settings' => array(
                'jQuery_tooltip'     => (
                    Settings::getWithDefault('set-tooltip-mode') == 2 ? 1 : 0
                ),
                'hts'                => Settings::getWithDefault('set-hts'),
                'word_status_filter' => makeStatusClassFilter((int)$visit_status),
                'annotations_mode'   => $mode_trans
            ),
        )
    );
    do_text_text_javascript($var_array);
    do_text_text_style($showLearning, $mode_trans, (int)$textsize, strlen($ann) > 0);
    ?>

    <div id="thetext" <?php echo ($rtlScript ? 'dir="rtl"' : '') ?>>
        <p style="margin-bottom: 10px;
            <?php echo $removeSpaces ? 'word-break:break-all;' : ''; ?>
            font-size: <?php echo $textsize; ?>%;
            line-height: <?php echo $ruby ? '1' : '1.4'; ?>;"
        >
            <!-- Start displaying words -->
            <?php main_word_loop($textid, $showAll); ?></span>
        </p>
        <p style="font-size:<?php echo $textsize; ?>%;line-height: 1.4; margin-bottom: 300px;">&nbsp;</p>
    </div>
    <?php
    if (!$only_body) {
        pageend();
    }
    flush();
}

/*
Uncoment to use as a page, deprecated behavior in LWT-fork, will be removed in 3.0.0

 if (isset($_REQUEST['text'])) {
    do_text_text_content($_REQUEST['text'], false);
}
*/
?>

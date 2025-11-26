<?php

/**
 * \file
 * \brief Sentence operations and retrieval functions.
 *
 * This file contains functions for finding, formatting, and displaying
 * sentences containing specific words.
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
 * Return a SQL string to find sentences containing a word.
 *
 * @param string $wordlc Word to look for in lowercase
 * @param int    $lid    Language ID
 *
 * @return string Query in SQL format
 */
function sentences_containing_word_lc_query($wordlc, $lid): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $mecab_str = null;
    $res = do_mysqli_query(
        "SELECT LgRegexpWordCharacters, LgRemoveSpaces
        FROM {$tbpref}languages
        WHERE LgID = $lid"
    );
    $record = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    $removeSpaces = $record["LgRemoveSpaces"];
    if ('MECAB' == strtoupper(trim((string) $record["LgRegexpWordCharacters"]))) {
        $mecab_file = sys_get_temp_dir() . "/" . $tbpref . "mecab_to_db.txt";
        //$mecab_args = ' -F {%m%t\\t -U {%m%t\\t -E \\n ';
        // For instance, "このラーメン" becomes "この    6    68\nラーメン    7    38"
        $mecab_args = ' -F %m\\t%t\\t%h\\n -U %m\\t%t\\t%h\\n -E EOP\\t3\\t7\\n ';
        if (file_exists($mecab_file)) {
            unlink($mecab_file);
        }
        $fp = fopen($mecab_file, 'w');
        fwrite($fp, $wordlc . "\n");
        fclose($fp);
        $mecab = get_mecab_path($mecab_args);
        $handle = popen($mecab . $mecab_file, "r");
        if (!feof($handle)) {
            $row = fgets($handle, 256);
            // Format string removing numbers.
            // MeCab tip: 2 = hiragana, 6 = kanji, 7 = katakana, 8 = kazu
            $mecab_str = "\t" . preg_replace_callback(
                '([2678]?)\t[0-9]+$',
                function ($matches) {
                    return isset($matches[1]) ? "\t" : "";
                },
                $row
            );
        }
        pclose($handle);
        unlink($mecab_file);
        $sql
        = "SELECT SeID, SeText,
        concat(
            '\\t',
            group_concat(Ti2Text ORDER BY Ti2Order asc SEPARATOR '\\t'),
            '\\t'
        ) val
         FROM {$tbpref}sentences, {$tbpref}textitems2
         WHERE lower(SeText)
         LIKE " . convert_string_to_sqlsyntax("%$wordlc%") . "
         AND SeID = Ti2SeID AND SeLgID = $lid AND Ti2WordCount<2
         GROUP BY SeID HAVING val
         LIKE " . convert_string_to_sqlsyntax_notrim_nonull("%$mecab_str%") . "
         ORDER BY CHAR_LENGTH(SeText), SeText";
    } else {
        if ($removeSpaces == 1) {
            $pattern = convert_string_to_sqlsyntax($wordlc);
        } else {
            $pattern = convert_regexp_to_sqlsyntax(
                '(^|[^' . $record["LgRegexpWordCharacters"] . '])'
                 . remove_spaces($wordlc, $removeSpaces)
                 . '([^' . $record["LgRegexpWordCharacters"] . ']|$)'
            );
        }
        $sql
        = "SELECT DISTINCT SeID, SeText
         FROM {$tbpref}sentences
         WHERE SeText RLIKE $pattern AND SeLgID = $lid
         ORDER BY CHAR_LENGTH(SeText), SeText";
    }
    return $sql;
}

/**
 * Perform a SQL query to find sentences containing a word.
 *
 * @param int|null $wid    Word ID or mode
 *                         - null: use $wordlc instead, simple search
 *                         - -1: use $wordlc with a more complex search
 *                         - 0 or above: sentences containing $wid
 * @param string   $wordlc Word to look for in lowercase
 * @param int      $lid    Language ID
 * @param int      $limit  Maximum number of sentences to return
 *
 * @return mysqli_result|false Query result or false on failure
 */
function sentences_from_word($wid, $wordlc, $lid, $limit = -1): \mysqli_result|false
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    if (empty($wid)) {
        $sql = "SELECT DISTINCT SeID, SeText
        FROM {$tbpref}sentences, {$tbpref}textitems2
        WHERE LOWER(Ti2Text) = " . convert_string_to_sqlsyntax($wordlc) . "
        AND Ti2WoID = 0 AND SeID = Ti2SeID AND SeLgID = $lid
        ORDER BY CHAR_LENGTH(SeText), SeText";
    } elseif ($wid == -1) {
        $sql = sentences_containing_word_lc_query($wordlc, $lid);
    } else {
        $sql
        = "SELECT DISTINCT SeID, SeText
         FROM {$tbpref}sentences, {$tbpref}textitems2
         WHERE Ti2WoID = $wid AND SeID = Ti2SeID AND SeLgID = $lid
         ORDER BY CHAR_LENGTH(SeText), SeText";
    }
    if ($limit) {
        $sql .= " LIMIT 0,$limit";
    }
    return do_mysqli_query($sql);
}

/**
 * Format the sentence(s) $seid containing $wordlc highlighting $wordlc.
 *
 * @param int    $seid   Sentence ID
 * @param string $wordlc Term text in lower case
 * @param int    $mode   * Up to 1: return only the current sentence
 *                       * Above 1: return previous sentence and current sentence
 *                       * Above 2: return previous, current and next sentence
 *
 * @return string[] [0]=html, word in bold, [1]=text, word in {}
 *
 * @global string $tbpref Database table prefix.
 *
 * @psalm-return list{string, string}
 */
function getSentence($seid, $wordlc, $mode): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $res = do_mysqli_query(
        "SELECT
        CONCAT(
            '​', group_concat(Ti2Text ORDER BY Ti2Order asc SEPARATOR '​'), '​'
        ) AS SeText, Ti2TxID AS SeTxID, LgRegexpWordCharacters,
        LgRemoveSpaces, LgSplitEachChar
        FROM {$tbpref}textitems2, {$tbpref}languages
        WHERE Ti2LgID = LgID AND Ti2WordCount < 2 AND Ti2SeID = $seid"
    );
    $record = mysqli_fetch_assoc($res);
    $removeSpaces = (int)$record["LgRemoveSpaces"] == 1;
    $splitEachChar = (int)$record['LgSplitEachChar'] != 0;
    $txtid = $record["SeTxID"];
    if (
        ($removeSpaces && !$splitEachChar)
        || 'MECAB' == strtoupper(trim((string) $record["LgRegexpWordCharacters"]))
    ) {
        $text = $record["SeText"];
        $wordlc = '[​]*' . preg_replace('/(.)/u', "$1[​]*", $wordlc);
        $pattern = "/(?<=[​])($wordlc)(?=[​])/ui";
    } else {
        $text = str_replace(array('​​','​','\r'), array('\r','','​'), $record["SeText"]);
        if ($splitEachChar) {
            $pattern = "/($wordlc)/ui";
        } else {
            $pattern = '/(?<![' . $record["LgRegexpWordCharacters"] . '])(' .
            remove_spaces($wordlc, $removeSpaces) . ')(?![' .
            $record["LgRegexpWordCharacters"] . '])/ui';
        }
    }
    $se = str_replace('​', '', preg_replace($pattern, '<b>$0</b>', $text));
    $sejs = str_replace('​', '', preg_replace($pattern, '{$0}', $text));
    if ($mode > 1) {
        if ($removeSpaces && !$splitEachChar) {
            $prevseSent = get_first_value(
                "SELECT concat(
                    '​',
                    group_concat(Ti2Text order by Ti2Order asc SEPARATOR '​'),
                    '​'
                ) AS value
                from {$tbpref}sentences, {$tbpref}textitems2
                where Ti2SeID = SeID and SeID < $seid and SeTxID = $txtid
                and trim(SeText) not in ('¶', '')
                group by SeID
                order by SeID desc"
            );
        } else {
            $prevseSent = get_first_value(
                "SELECT SeText as value from {$tbpref}sentences
                where SeID < $seid and SeTxID = $txtid
                and trim(SeText) not in ('¶', '')
                order by SeID desc"
            );
        }
        if (isset($prevseSent)) {
            $se = preg_replace($pattern, '<b>$0</b>', $prevseSent) . $se;
            $sejs = preg_replace($pattern, '{$0}', $prevseSent) . $sejs;
        }
    }
    if ($mode > 2) {
        if ($removeSpaces && !$splitEachChar) {
            $nextSent = get_first_value(
                "SELECT concat(
                    '​',
                    group_concat(Ti2Text order by Ti2Order asc SEPARATOR '​'),
                    '​'
                ) as value
                from {$tbpref}sentences, {$tbpref}textitems2
                where Ti2SeID = SeID and SeID > $seid
                and SeTxID = $txtid and trim(SeText) not in ('¶','')
                group by SeID
                order by SeID asc"
            );
        } else {
            $nextSent = get_first_value(
                "SELECT SeText as value
                FROM {$tbpref}sentences
                where SeID > $seid AND SeTxID = $txtid
                and trim(SeText) not in ('¶','')
                order by SeID asc"
            );
        }
        if (isset($nextSent)) {
            $se .= preg_replace($pattern, '<b>$0</b>', $nextSent);
            $sejs .= preg_replace($pattern, '{$0}', $nextSent);
        }
    }
    mysqli_free_result($res);
    if ($removeSpaces) {
        $se = str_replace('​', '', $se);
        $sejs = str_replace('​', '', $sejs);
    }
     // [0]=html, word in bold, [1]=text, word in {}
    return array($se, $sejs);
}


/**
 * Return sentences containing a word.
 *
 * @param int      $lang   Language ID
 * @param string   $wordlc Word to look for in lowercase
 * @param int|null $wid    Word ID
 *                         - null: use $wordlc instead, simple search
 *                         - -1: use $wordlc with a more complex search
 *                         - 0 or above: find sentences containing $wid
 * @param int|null $mode   Sentences to get:
 *                         - Up to 1 is 1 sentence,
 *                         - 2 is previous and current sentence,
 *                         - 3 is previous, current and next one
 * @param int      $limit  Maximum number of sentences to return
 *
 * @return string[][] Array of sentences found
 *
 * @psalm-return list{0?: array{0: string, 1: string},...}
 */
function sentences_with_word($lang, $wordlc, $wid, $mode = 0, $limit = 20): array
{
    $r = array();
    $res = sentences_from_word($wid, $wordlc, $lang, $limit);
    $last = '';
    if (is_null($mode)) {
        $mode = (int) getSettingWithDefault('set-term-sentence-count');
    }
    while ($record = mysqli_fetch_assoc($res)) {
        if ($last != $record['SeText']) {
            $sent = getSentence($record['SeID'], $wordlc, $mode);
            if (mb_strstr($sent[1], '}', false, 'UTF-8')) {
                $r[] = $sent;
            }
        }
        $last = $record['SeText'];
    }
    mysqli_free_result($res);
    return $r;
}

/**
 * Prepare the area to for examples sentences of a word.
 */
function example_sentences_area($lang, $termlc, $selector, $wid): void
{
    ?>
<div id="exsent">
    <!-- Interactable text -->
    <div id="exsent-interactable">
        <span class="click" onclick="do_ajax_show_sentences(
            <?php echo $lang; ?>, <?php echo prepare_textdata_js($termlc); ?>,
            <?php echo htmlentities(json_encode($selector)); ?>, <?php echo $wid; ?>);">
            <img src="/assets/icons/sticky-notes-stack.png" title="Show Sentences" alt="Show Sentences" />
            Show Sentences
        </span>
    </div>
    <!-- Loading icon -->
    <img id="exsent-waiting" style="display: none;" src="/assets/icons/waiting2.gif" />
    <!-- Displayed output -->
    <div id="exsent-sentences" style="display: none;">
        <p><b>Sentences in active texts with <i><?php echo tohtml($termlc) ?></i></b></p>
        <p>
            (Click on
            <img src="/assets/icons/tick-button.png" title="Choose" alt="Choose" />
            to copy sentence into above term)
        </p>
    </div>
</div>
    <?php
}

/**
 * Show 20 sentences containg $wordlc.
 *
 * @param int      $lang      Language ID
 * @param string   $wordlc    Term in lower case.
 * @param int|null $wid       Word ID
 * @param string   $jsctlname Path for the textarea of the sentence of the word being
 *                            edited.
 * @param int      $mode      * Up to 1: return only the current sentence
 *                            * Above 1: return previous and current sentence
 *                            * Above 2: return previous, current and next sentence
 *
 * @return string HTML-formatted string of which elements are candidate sentences to use.
 *
 * @global string $tbpref Database table prefix
 */
function get20Sentences($lang, $wordlc, $wid, $jsctlname, $mode): string
{
    $r = '<p><b>Sentences in active texts with <i>' . tohtml($wordlc) . '</i></b></p>
    <p>(Click on <img src="/assets/icons/tick-button.png" title="Choose" alt="Choose" />
    to copy sentence into above term)</p>';
    $sentences = sentences_with_word($lang, $wordlc, $wid, $mode);
    foreach ($sentences as $sentence) {
        $r .= '<span class="click" onclick="{' . $jsctlname . '.value=' .
            prepare_textdata_js($sentence[1]) . '; makeDirty();}">
        <img src="/assets/icons/tick-button.png" title="Choose" alt="Choose" />
        </span> &nbsp;' . $sentence[0] . '<br />';
    }
    $r .= '</p>';
    return $r;
}

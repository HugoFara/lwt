<?php

/**
 * \file
 * \brief Text and sentence processing helper functions.
 *
 * This file contains functions for text/sentence processing,
 * MeCab integration, expression handling, and annotation management.
 *
 * PHP version 8.1
 *
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since   2.10.0-fork Split from session_utility.php
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
 * @return ((float|int|null|string)[]|float|int|null|string)[][] Statistics under the form of an array
 *
 * @psalm-return array{total: array<float|int|string, float|int|null|string>, expr: array<float|int|string, float|int|null|string>, stat: array<float|int|string, array<float|int|string, float|int|null|string>>, totalu: array<float|int|string, float|int|null|string>, expru: array<float|int|string, float|int|null|string>, statu: array<float|int|string, array<float|int|string, float|int|null|string>>}
 */
function return_textwordcount($texts_id): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();

    $r = array(
        // Total for text
        'total'=> array(),
        'expr'=> array(),
        'stat'=> array(),
        // Unique words
        'totalu' => array(),
        'expru' => array(),
        'statu'=> array()
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
 *
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
        if (array_key_exists('lwt_translator', $url_query)
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
    if ('MECAB'== strtoupper(trim((string) $record["LgRegexpWordCharacters"]))) {
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
 * @return mysqli_result|true Query
 */
function sentences_from_word($wid, $wordlc, $lid, $limit=-1): bool|\mysqli_result
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    if (empty($wid)) {
        $sql = "SELECT DISTINCT SeID, SeText
        FROM {$tbpref}sentences, {$tbpref}textitems2
        WHERE LOWER(Ti2Text) = " . convert_string_to_sqlsyntax($wordlc) . "
        AND Ti2WoID = 0 AND SeID = Ti2SeID AND SeLgID = $lid
        ORDER BY CHAR_LENGTH(SeText), SeText";
    } else if ($wid == -1) {
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
    if (($removeSpaces && !$splitEachChar)
        || 'MECAB'== strtoupper(trim((string) $record["LgRegexpWordCharacters"]))
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
function sentences_with_word($lang, $wordlc, $wid, $mode=0, $limit=20): array
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


/**
 * Return a dictionary of languages name - id
 *
 * @return array<string, int>
 */
function get_languages(): array
{
    $langs = array();
    $sql = "SELECT LgID, LgName FROM " . \Lwt\Core\LWT_Globals::table('languages') . " WHERE LgName<>''";
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $langs[(string)$record['LgName']] = (int)$record['LgID'];
    }
    mysqli_free_result($res);
    return $langs;
}


/**
 * Get language name from its ID
 *
 * @param string|int $lid Language ID
 *
 * @return string Language name
 */
function getLanguage($lid)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    if (is_int($lid)) {
        $lg_id = $lid;
    } else if (isset($lid) && trim($lid) != '' && ctype_digit($lid)) {
        $lg_id = (int) $lid;
    } else {
        return '';
    }
    $r = get_first_value(
        "SELECT LgName AS value
        FROM {$tbpref}languages
        WHERE LgID = $lg_id"
    );
    if (isset($r)) {
        return (string)$r;
    }
    return '';
}


/**
 * Try to get language code from its ID
 *
 * @param int   $lg_id           Language ID
 * @param array $languages_table Table of languages, usually LWT_LANGUAGES_ARRAY
 *
 * @return string If found, two-letter code (e. g. BCP 47) or four-letters for the langugae. '' otherwise.
 *
 * @global string $tbpref
 */
function getLanguageCode($lg_id, $languages_table)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $query = "SELECT LgName, LgGoogleTranslateURI
    FROM {$tbpref}languages
    WHERE LgID = $lg_id";

    $res = do_mysqli_query($query);
    $record = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    $lg_name = (string) $record["LgName"];
    $translator_uri = (string) $record["LgGoogleTranslateURI"];

    // If we are using a standard language name, use it
    if (array_key_exists($lg_name, $languages_table)) {
        return $languages_table[$lg_name][1];
    }

    // Otherwise, use the translator URL
    $lgFromDict = langFromDict($translator_uri);
    if ($lgFromDict != '') {
        return $lgFromDict;
    }
    return '';
}

/**
 * Return a right-to-left direction indication in HTML if language is right-to-left.
 *
 * @param string|int|null $lid Language ID
 *
 * @return string ' dir="rtl" '|''
 */
function getScriptDirectionTag($lid): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    if (!isset($lid)) {
        return '';
    }
    if (is_string($lid)) {
        if (trim($lid) == '' || !is_numeric($lid)) {
            return '';
        }
        $lg_id = (int) $lid;
    } else {
        $lg_id = $lid;
    }
    $r = get_first_value(
        "SELECT LgRightToLeft as value
        from {$tbpref}languages
        where LgID = $lg_id"
    );
    if (isset($r) && $r) {
        return ' dir="rtl" ';
    }
    return '';
}

/**
 * Find all occurences of an expression using MeCab.
 *
 * @param string     $text Text to insert
 * @param string|int $lid  Language ID
 * @param int        $len  Number of words in the expression
 *
 * @return (int|string)[][] Each found multi-word details
 *
 * @global string $tbpref Table name prefix
 *
 * @psalm-return list{0?: array{SeID: int, TxID: int, position: int, term: string},...}
 */
function findMecabExpression($text, $lid): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();

    $db_to_mecab = tempnam(sys_get_temp_dir(), "{$tbpref}db_to_mecab");
    $mecab_args = " -F %m\\t%t\\t\\n -U %m\\t%t\\t\\n -E \\t\\n ";

    $mecab = get_mecab_path($mecab_args);
    $sql = "SELECT SeID, SeTxID, SeFirstPos, SeText FROM {$tbpref}sentences
    WHERE SeLgID = $lid AND
    SeText LIKE " . convert_string_to_sqlsyntax_notrim_nonull("%$text%");
    $res = do_mysqli_query($sql);

    $parsed_text = '';
    $fp = fopen($db_to_mecab, 'w');
    fwrite($fp, $text);
    fclose($fp);
    $handle = popen($mecab . $db_to_mecab, "r");
    while (!feof($handle)) {
        $row = fgets($handle, 16132);
        $arr = explode("\t", $row, 4);
        // Not a word (punctuation)
        if (!empty($arr[0]) && $arr[0] != "EOP"
            && in_array($arr[1], ["2", "6", "7"])
        ) {
            $parsed_text .= $arr[0] . ' ';
        }
    }

    $occurences = array();
    // For each sentence in database containing $text
    while ($record = mysqli_fetch_assoc($res)) {
        $sent = trim((string) $record['SeText']);
        $fp = fopen($db_to_mecab, 'w');
        fwrite($fp, $sent . "\n");
        fclose($fp);

        $handle = popen($mecab . $db_to_mecab, "r");
        $parsed_sentence = '';
        // For each word in sentence
        while (!feof($handle)) {
            $row = fgets($handle, 16132);
            $arr = explode("\t", $row, 4);
            // Not a word (punctuation)
            if (!empty($arr[0]) && $arr[0] != "EOP"
                && in_array($arr[1], ["2", "6", "7"])
            ) {
                $parsed_sentence .= $arr[0] . ' ';
            }
        }

        // Finally we check if parsed text is in parsed sentence
        $seek = mb_strpos($parsed_sentence, $parsed_text);
        // For each occurence of multi-word in sentence
        while ($seek !== false) {
            // pos = Number of words * 2 + initial position
            $pos = preg_match_all('/ /', mb_substr($parsed_sentence, 0, $seek)) * 2 +
            (int) $record['SeFirstPos'];
            // Ti2WoID,Ti2LgID,Ti2TxID,Ti2SeID,Ti2Order,Ti2WordCount,Ti2Text
            $occurences[] = [
                "SeID" => (int) $record['SeID'],
                "TxID" => (int) $record['SeTxID'],
                "position" => $pos,
                "term" => $text
            ];
            $seek = mb_strpos($parsed_sentence, $parsed_text, $seek + 1);
        }
        pclose($handle);
    }
    mysqli_free_result($res);
    unlink($db_to_mecab);

    return $occurences;
}

/**
 * Insert an expression to the database using MeCab.
 *
 * @param string     $text Text to insert
 * @param string|int $lid  Language ID
 * @param string|int $wid  Word ID
 * @param int        $len  Number of words in the expression
 *
 * @return string[][] Append text and values to insert to the database
 *
 * @since 2.5.0-fork Function added.
 *
 * @deprecated Since 2.10.0 Use insertMecabExpression
 *
 * @global string $tbpref Table name prefix
 *
 * @psalm-return list{array<int, string>, list{0?: string,...}}
 */
function insert_expression_from_mecab($text, $lid, $wid, $len): array
{
    $occurences = findMecabExpression($text, $lid);

    $mwords = array();
    foreach ($occurences as $occ) {
        $mwords[$occ['SeTxID']] = array();
        if (getSettingZeroOrOne('showallwords', 1)) {
            $mwords[$occ['SeTxID']][$occ['position']] = "&nbsp;$len&nbsp";
        } else {
            $mwords[$occ['SeTxID']][$occ['position']] = $occ['term'];
        }
    }
    $flat_mwords = array_reduce(
        $mwords, function ($carry, $item) {
            return $carry + $item;
        }, []
    );

    $sqlarr = array();
    foreach ($occurences as $occ) {
        $sqlarr[] = "(" . implode(
            ",",
            [
            $wid, $lid, $occ["SeTxID"], $occ["SeID"],
            $occ["position"], $len,
            convert_string_to_sqlsyntax_notrim_nonull($occ["term"])
            ]
        ) . ")";
    }

    return array($flat_mwords, array(), $sqlarr);
}

/**
 * Insert an expression to the database using MeCab.
 *
 * @param string $textlc Text to insert in lower case
 * @param string $lid    Language ID
 * @param string $wid    Word ID
 * @param int    $len    Number of words in the expression
 * @param int    $mode   If equal to 0, add data in the output
 *
 * @return array{string[], string[]} Append text and SQL array.
 *
 * @since 2.5.0-fork Function deprecated.
 *                   $mode is unnused, data are always returned.
 *                   The second return argument is always empty array.
 *
 * @deprecated Use insertMecabExpression instead.
 *
 * @global string $tbpref Table name prefix
 *
 * @psalm-return array{0: array<int, string>, 1: list<string>}
 */
function insertExpressionFromMeCab($textlc, $lid, $wid, $len, $mode): array
{
    return insert_expression_from_mecab($textlc, $lid, $wid, $len);
}


/**
 * Find all occurences of an expression, do not use parsers like MeCab.
 *
 * @param string     $textlc Text to insert in lower case
 * @param string|int $lid    Language ID
 *
 * @return (int|null|string)[][] Each inserted mutli-word details
 *
 * @global string $tbpref Table name prefix
 *
 * @psalm-return list{0?: array{SeID: int, SeTxID: int, position: int, term: null|string, term_display: null|string},...}
 */
function findStandardExpression($textlc, $lid): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $occurences = array();
    $res = do_mysqli_query("SELECT * FROM {$tbpref}languages WHERE LgID=$lid");
    $record = mysqli_fetch_assoc($res);
    $removeSpaces = $record["LgRemoveSpaces"] == 1;
    $splitEachChar = $record['LgSplitEachChar'] != 0;
    $termchar = $record['LgRegexpWordCharacters'];
    mysqli_free_result($res);
    if ($removeSpaces && !$splitEachChar) {
        $sql = "SELECT
        GROUP_CONCAT(Ti2Text ORDER BY Ti2Order SEPARATOR ' ') AS SeText, SeID,
        SeTxID, SeFirstPos, SeTxID
        FROM {$tbpref}textitems2
        JOIN {$tbpref}sentences
        ON SeID=Ti2SeID AND SeLgID = Ti2LgID
        WHERE Ti2LgID = $lid
        AND SeText LIKE " . convert_string_to_sqlsyntax_notrim_nonull("%$textlc%") . "
        AND Ti2WordCount < 2
        GROUP BY SeID";
    } else {
        $sql = "SELECT * FROM {$tbpref}sentences
        WHERE SeLgID = $lid AND SeText LIKE " .
        convert_string_to_sqlsyntax_notrim_nonull("%$textlc%");
    }

    if ($splitEachChar) {
        $textlc = (string) preg_replace('/([^\s])/u', "$1 ", $textlc);
    }
    $wis = $textlc;
    $res = do_mysqli_query($sql);
    $notermchar = "/[^$termchar]($textlc)[^$termchar]/ui";
    // For each sentence in the language containing the query
    $matches = null;
    while ($record = mysqli_fetch_assoc($res)){
        $string = ' ' . $record['SeText'] . ' ';
        if ($splitEachChar) {
            $string = preg_replace('/([^\s])/u', "$1 ", $string);
        } else if ($removeSpaces && empty($rSflag)) {
            $rSflag = preg_match(
                '/(?<=[ ])(' . preg_replace('/(.)/ui', "$1[ ]*", $textlc) .
                ')(?=[ ])/ui',
                $string, $ma
            );
            if (!empty($ma[1])) {
                $textlc = trim($ma[1]);
                $notermchar = "/[^$termchar]($textlc)[^$termchar]/ui";
            }
        }
        $last_pos = mb_strripos($string, $textlc, 0, 'UTF-8');
        // For each occurence of query in sentence
        while ($last_pos !== false) {
            if ($splitEachChar || $removeSpaces
                || preg_match($notermchar, " $string ", $matches, 0, $last_pos - 1)
            ) {
                // Number of terms before group
                $cnt = preg_match_all(
                    "/([$termchar]+)/u",
                    mb_substr($string, 0, $last_pos, 'UTF-8'),
                    $_
                );
                $pos = 2 * $cnt + (int) $record['SeFirstPos'];
                $txt = '';
                if ($matches[1] != $textlc) {
                    $txt = $splitEachChar ? $wis : $matches[1];
                }
                if ($splitEachChar || $removeSpaces) {
                    $display = $wis;
                } else {
                    $display = $matches[1];
                }
                $occurences[] = [
                    "SeID" => (int) $record['SeID'],
                    "SeTxID" => (int) $record['SeTxID'],
                    "position" => $pos,
                    "term" => $txt,
                    "term_display" => $display
                ];
            }
            // Cut the sentence to before the right-most term starts
            $string = mb_substr($string, 0, $last_pos, 'UTF-8');
            $last_pos = mb_strripos($string, $textlc, 0, 'UTF-8');
        }
    }
    mysqli_free_result($res);
    return $occurences;
}

/**
 * Insert an expression without using a tool like MeCab.
 *
 * @param string     $textlc Text to insert in lower case
 * @param string|int $lid    Language ID
 * @param string|int $wid    Word ID
 * @param int        $len    Number of words in the expression
 * @param mixed      $mode   Unnused
 *
 * @return (null|string)[][] Append text, empty and sentence id
 *
 * @since 2.5.0-fork Mode is unnused and data are always added to the output.
 * @since 2.5.2-fork Fixed multi-words insertion for languages using no space.
 *
 * @deprecated Since 2.10.0-fork, use insertStandardExpression
 *
 * @psalm-return list{array<int, null|string>, array<never, never>, list{0?: string,...}}
 */
function insert_standard_expression($textlc, $lid, $wid, $len, $mode): array
{
    $occurences = findStandardExpression($textlc, $lid);

    $mwords = array();
    foreach ($occurences as $occ) {
        $mwords[$occ['SeTxID']] = array();
        if (getSettingZeroOrOne('showallwords', 1)) {
            $mwords[$occ['SeTxID']][$occ['position']] = "&nbsp;$len&nbsp";
        } else {
            $mwords[$occ['SeTxID']][$occ['position']] = $occ['term_display'];
        }
    }
    $flat_mwords = array_reduce(
        $mwords, function ($carry, $item) {
            return $carry + $item;
        }, []
    );

    $sqlarr = array();
    foreach ($occurences as $occ) {
        $sqlarr[] = "(" . implode(
            ",",
            [
            $wid, $lid, $occ["SeTxID"], $occ["SeID"],
            $occ["position"], $len,
            convert_string_to_sqlsyntax_notrim_nonull($occ["term"])
            ]
        ) . ")";
    }

    return array($flat_mwords, array(), $sqlarr);
}


/**
 * Prepare a JavaScript dialog to insert a new expression. Use elements in
 * global JavaScript scope.
 *
 * @deprecated Use newMultiWordInteractable instead. The new function does not
 * use global JS variables.
 *
 * @return void
 */
function new_expression_interactable($hex, $appendtext, $sid, $len): void
{
    $showAll = (bool) getSettingZeroOrOne('showallwords', 1);
    $showType = $showAll ? "m" : '';

    ?>
<script type="text/javascript">
    newExpressionInteractable(
        <?php echo json_encode($appendtext); ?>,
        ' class="click mword <?php echo $showType; ?>wsty TERM<?php echo $hex; ?> word' +
    woid + ' status' + status + '" data_trans="' + trans + '" data_rom="' +
    roman + '" data_code="<?php echo $len; ?>" data_status="' +
    status + '" data_wid="' + woid +
    '" title="' + title + '"' ,
        <?php echo json_encode($len); ?>,
        <?php echo json_encode($hex); ?>,
        <?php echo json_encode($showAll); ?>
    );
 </script>
    <?php
    flush();
}


/**
 * Prepare a JavaScript dialog to insert a new expression.
 *
 * @param string   $hex        Lowercase text, formatted version of the text.
 * @param string[] $appendtext Text to append
 * @param int      $wid        Term ID
 * @param int      $len        Words count.
 *
 * @return void
 *
 * @global string $tbpref Database table prefix.
 *
 * @since 2.10.0-fork Fixes a bug inserting wrong title in tooltip
 */
function new_expression_interactable2($hex, $appendtext, $wid, $len): void
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $showAll = (bool)getSettingZeroOrOne('showallwords', 1);
    $showType = $showAll ? "m" : "";

    $sql = "SELECT * FROM {$tbpref}words WHERE WoID=$wid";
    $res = do_mysqli_query($sql);

    $record = mysqli_fetch_assoc($res);

    $attrs = array(
        "class" => "click mword {$showType}wsty TERM$hex word$wid status" .
        $record["WoStatus"],
        "data_trans" => $record["WoTranslation"],
        "data_rom" => $record["WoRomanization"],
        "data_code" => $len,
        "data_status" => $record["WoStatus"],
        "data_wid" => $wid
    );
    mysqli_free_result($res);

    $term = array_values($appendtext)[0];

    ?>
<script type="text/javascript">
    let term = <?php echo json_encode($attrs); ?>;

    let title = '';
    if (window.parent.LWT_DATA.settings.jQuery_tooltip) {
        title = make_tooltip(
            <?php echo json_encode($term); ?>, term.data_trans, term.data_rom,
            parseInt(term.data_status, 10)
        );
    }
    term['title'] = title;
    let attrs = "";
    Object.entries(term).forEach(([k, v]) => attrs += " " + k + '="' + v + '"');
    // keys(term).map((k) => k + '="' + term[k] + '"').join(" ");

    newExpressionInteractable(
        <?php echo json_encode($appendtext); ?>,
        attrs,
        <?php echo json_encode($len); ?>,
        <?php echo json_encode($hex); ?>,
        <?php echo json_encode($showAll); ?>
    );
 </script>
    <?php
    flush();
}



/**
 * Prepare a JavaScript dialog to insert a new expression.
 *
 * @param string     $hex        Lowercase text, formatted version of the text.
 * @param string[][] $multiwords Multi-words to happen, format [textid][position][text]
 * @param int        $wid        Term ID
 * @param int        $len        Words count.
 *
 * @return void
 *
 * @global string $tbpref Database table prefix.
 *
 * @since 2.10.0-fork Fixes a bug inserting wrong title in tooltip
 */
function newMultiWordInteractable($hex, $multiwords, $wid, $len): void
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $showAll = (bool)getSettingZeroOrOne('showallwords', 1);
    $showType = $showAll ? "m" : "";

    $sql = "SELECT * FROM {$tbpref}words WHERE WoID=$wid";
    $res = do_mysqli_query($sql);

    $record = mysqli_fetch_assoc($res);

    $attrs = array(
        "class" => "click mword {$showType}wsty TERM$hex word$wid status" .
        $record["WoStatus"],
        "data_trans" => $record["WoTranslation"],
        "data_rom" => $record["WoRomanization"],
        "data_code" => $len,
        "data_status" => $record["WoStatus"],
        "data_wid" => $wid
    );
    mysqli_free_result($res);

    ?>
<script type="text/javascript">
    (function () {
        let term = <?php echo json_encode($attrs); ?>;

        const multiWords = <?php echo json_encode($multiwords); ?>;

        let title = '';
        if (window.parent.LWT_DATA.settings.jQuery_tooltip) {
            title = make_tooltip(
                multiWords[window.parent.LWT_DATA.text.id][0], term.data_trans,
                term.data_rom, parseInt(term.data_status, 10)
            );
        }
        term['title'] = title;
        let attrs = "";
        Object.entries(term).forEach(([k, v]) => attrs += " " + k + '="' + v + '"');
        // keys(term).map((k) => k + '="' + term[k] + '"').join(" ");

        newExpressionInteractable(
            multiWords[window.parent.LWT_DATA.text.id],
            attrs,
            term.data_code,
            <?php echo json_encode($hex); ?>,
            <?php echo json_encode($showAll); ?>
        );
    })()
 </script>
    <?php
    flush();
}

/**
 * Alter the database to add a new word
 *
 * @param string     $textlc Text in lower case
 * @param string|int $lid    Language ID
 * @param int        $len    Number of words in the expression
 * @param int        $mode   Function mode
 *                           - 0: Default mode, do nothing special
 *                           - 1: Runs an expresion inserter interactable
 *                           - 2: Return the sql output
 *
 * @return null|string If $mode == 2 return values to insert in textitems2, nothing otherwise.
 *
 * @global string $tbpref Table name prefix
 */
function insertExpressions($textlc, $lid, $wid, $len, $mode): null|string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $regexp = (string)get_first_value(
        "SELECT LgRegexpWordCharacters AS value
        FROM {$tbpref}languages WHERE LgID=$lid"
    );

    if ('MECAB' == strtoupper(trim($regexp))) {
        $occurences = findMecabExpression($textlc, $lid);
    } else {
        $occurences = findStandardExpression($textlc, $lid);
    }

    // Update the term visually through JS
    if ($mode == 0) {
        $appendtext = array();
        foreach ($occurences as $occ) {
            $appendtext[$occ['SeTxID']] = array();
            if (getSettingZeroOrOne('showallwords', 1)) {
                $appendtext[$occ['SeTxID']][$occ['position']] = "&nbsp;$len&nbsp";
            } else {
                if ('MECAB' == strtoupper(trim($regexp))) {
                    $appendtext[$occ['SeTxID']][$occ['position']] = $occ['term'];
                } else {
                    $appendtext[$occ['SeTxID']][$occ['position']] = $occ['term_display'];
                }
            }
        }
        $hex = strToClassName(prepare_textdata($textlc));
        newMultiWordInteractable($hex, $appendtext, $wid, $len);
    }
    $sqltext = null;
    if (!empty($occurences)) {
        $sqlarr = array();
        foreach ($occurences as $occ) {
            $sqlarr[] = "(" . implode(
                ",",
                [
                $wid, $lid, $occ["SeTxID"], $occ["SeID"],
                $occ["position"], $len,
                convert_string_to_sqlsyntax_notrim_nonull($occ["term"])
                ]
            ) . ")";
        }
        $sqltext = '';
        if ($mode != 2) {
            $sqltext .=
            "INSERT INTO {$tbpref}textitems2
             (Ti2WoID,Ti2LgID,Ti2TxID,Ti2SeID,Ti2Order,Ti2WordCount,Ti2Text)
             VALUES ";
        }
        $sqltext .= implode(',', $sqlarr);
        unset($sqlarr);
    }

    if ($mode == 2) {
        return $sqltext;
    }
    if (isset($sqltext)) {
        do_mysqli_query($sqltext);
    }
    return null;
}


/**
 * Restore the database from a file.
 *
 * @param resource $handle Backup file handle
 * @param string   $title  File title
 *
 * @return string Human-readable status message
 *
 * @global string $trbpref Database table prefix
 * @global int    $debug   Debug status
 * @global string $dbname  Database name
 *
 * @since 2.0.3-fork Function was broken
 * @since 2.5.3-fork Function repaired
 * @since 2.7.0-fork $handle should be an *uncompressed* file.
 * @since 2.9.1-fork It can read SQL with more or less than one instruction a line
 */
function restore_file($handle, $title): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $message = "";
    $install_status = array(
        "queries" => 0,
        "successes" => 0,
        "errors" => 0,
        "drops" => 0,
        "inserts" => 0,
        "creates" => 0
    );
    $start = true;
    $curr_content = '';
    $queries_list = array();
    while ($stream = fgets($handle)) {
        // Check file header
        if ($start) {
            if (!str_starts_with($stream, "-- lwt-backup-")
                && !str_starts_with($stream, "-- lwt-exp_version-backup-")
            ) {
                $message = "Error: Invalid $title Restore file " .
                "(possibly not created by LWT backup)";
                $install_status["errors"] = 1;
                break;
            }
            $start = false;
            continue;
        }
        // Skip comments
        if (str_starts_with($stream, '-- ')) {
            continue;
        }
        // Add stream to accumulator
        $curr_content .= $stream;
        // Get queries
        $queries = explode(';' . PHP_EOL, $curr_content);
        // Replace line by remainders of the last element (incomplete line)
        $curr_content = array_pop($queries);
        //var_dump("queries", $queries);
        foreach ($queries as $query) {
            $queries_list[] = trim($query);
        }
    }
    if (!feof($handle) && $install_status["errors"] == 0) {
        $message = "Error: cannot read the end of the demo file!";
        $install_status["errors"] = 1;
    }
    fclose($handle);
    // Now run all queries
    if ($install_status["errors"] == 0) {
        foreach ($queries_list as $query) {
            $sql_line = trim(
                str_replace("\r", "", str_replace("\n", "", $query))
            );
            if ($sql_line != "") {
                if (!str_starts_with($query, '-- ')) {
                    $res = mysqli_query(
                        \Lwt\Core\LWT_Globals::getDbConnection(),
                        prefixSQLQuery($query, $tbpref)
                    );
                    $install_status["queries"]++;
                    if ($res == false) {
                        $install_status["errors"]++;
                    } else {
                        $install_status["successes"]++;
                        if (str_starts_with($query,  "INSERT INTO")) {
                            $install_status["inserts"]++;
                        } else if (str_starts_with($query, "DROP TABLE")) {
                            $install_status["drops"]++;
                        } else if (str_starts_with($query, "CREATE TABLE")) {
                            $install_status["creates"]++;
                        }
                    }
                }
            }
        }
    }
    if ($install_status["errors"] == 0) {
        runsql("DROP TABLE IF EXISTS {$tbpref}textitems", '');
        check_update_db();
        reparse_all_texts();
        optimizedb();
        get_tags(1);
        get_texttags(1);
        $message = "Success: $title restored";
    } else if ($message == "") {
        $message = "Error: $title NOT restored";
    }
    $message .= sprintf(
        " - %d queries - %d successful (%d/%d tables dropped/created, " .
        "%d records added), %d failed.",
        $install_status["queries"], $install_status["successes"],
        $install_status["drops"], $install_status["creates"],
        $install_status["inserts"], $install_status["errors"]
    );
    return $message;
}


/**
 * Uses provided annotations, and annotations from database to update annotations.
 *
 * @param int    $textid Id of the text on which to update annotations
 * @param string $oldann Old annotations
 *
 * @return string Updated annotations for this text.
 */
function recreate_save_ann($textid, $oldann): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // Get the translations from $oldann:
    $oldtrans = array();
    $olditems = preg_split('/[\n]/u', $oldann);
    foreach ($olditems as $olditem) {
        $oldvals = preg_split('/[\t]/u', $olditem);
        if ((int)$oldvals[0] > -1) {
            $trans = '';
            if (count($oldvals) > 3) {
                $trans = $oldvals[3];
            }
            $oldtrans[$oldvals[0] . "\t" . $oldvals[1]] = $trans;
        }
    }
    // Reset the translations from $oldann in $newann and rebuild in $ann:
    $newann = create_ann($textid);
    $newitems = preg_split('/[\n]/u', $newann);
    $ann = '';
    foreach ($newitems as $newitem) {
        $newvals = preg_split('/[\t]/u', $newitem);
        if ((int)$newvals[0] > -1) {
            $key = $newvals[0] . "\t";
            if (isset($newvals[1])) {
                $key .= $newvals[1];
            }
            if (isset($oldtrans[$key])) {
                $newvals[3] = $oldtrans[$key];
            }
            $item = implode("\t", $newvals);
        } else {
            $item = $newitem;
        }
        $ann .= $item . "\n";
    }
    runsql(
        "UPDATE {$tbpref}texts
        SET TxAnnotatedText = " . convert_string_to_sqlsyntax($ann) . "
        WHERE TxID = $textid",
        ""
    );
    return (string)get_first_value(
        "SELECT TxAnnotatedText AS value
        FROM {$tbpref}texts
        where TxID = $textid"
    );
}

/**
 * Create new annotations for a text.
 *
 * @param int $textid Id of the text to create annotations for
 *
 * @return string Annotations for the text
 *
 * @since 2.9.0 Annotations "position" change, they are now equal to Ti2Order
 * it was shifted by one index before.
 */
function create_ann($textid): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $ann = '';
    $sql =
    "SELECT
    CASE WHEN Ti2WordCount>0 THEN Ti2WordCount ELSE 1 END AS Code,
    CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN Ti2Text ELSE WoText END AS TiText,
    Ti2Order,
    CASE WHEN Ti2WordCount > 0 THEN 0 ELSE 1 END AS TiIsNotWord,
    WoID, WoTranslation
    FROM (
        {$tbpref}textitems2
        LEFT JOIN {$tbpref}words
        ON Ti2WoID = WoID AND Ti2LgID = WoLgID
    )
    WHERE Ti2TxID = $textid
    ORDER BY Ti2Order ASC, Ti2WordCount DESC";
    $until = 0;
    $res = do_mysqli_query($sql);
    // For each term (includes blanks)
    while ($record = mysqli_fetch_assoc($res)) {
        $actcode = (int)$record['Code'];
        $order = (int)$record['Ti2Order'];
        if ($order <= $until) {
            continue;
        }
        $savenonterm = '';
        $saveterm = '';
        $savetrans = '';
        $savewordid = '';
        $until = $order;
        if ($record['TiIsNotWord'] != 0) {
            $savenonterm = $record['TiText'];
        } else {
            $until = $order + 2 * ($actcode - 1);
            $saveterm = $record['TiText'];
            if (isset($record['WoID'])) {
                $savetrans = $record['WoTranslation'];
                $savewordid = $record['WoID'];
            }
        }
        // Append the annotation
        $ann .= process_term(
            $savenonterm, $saveterm, $savetrans, $savewordid, $order
        );
    }
    mysqli_free_result($res);
    return $ann;
}

// -------------------------------------------------------------

function create_save_ann($textid): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $ann = create_ann($textid);
    runsql(
        'update ' . $tbpref . 'texts set ' .
        'TxAnnotatedText = ' . convert_string_to_sqlsyntax($ann) . '
        where TxID = ' . $textid, ""
    );
    return (string)get_first_value(
        "select TxAnnotatedText as value
        from " . $tbpref . "texts
        where TxID = " . $textid
    );
}

/**
 * Truncate the database, remove all data belonging by the current user.
 *
 * Keep settings.
 *
 * @global $tbpref
 */
function truncateUserDatabase(): void
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    runsql("TRUNCATE {$tbpref}archivedtexts", '');
    runsql("TRUNCATE {$tbpref}archtexttags", '');
    runsql("TRUNCATE {$tbpref}feedlinks", '');
    runsql("TRUNCATE {$tbpref}languages", '');
    runsql("TRUNCATE {$tbpref}textitems2", '');
    runsql("TRUNCATE {$tbpref}newsfeeds", '');
    runsql("TRUNCATE {$tbpref}sentences", '');
    runsql("TRUNCATE {$tbpref}tags", '');
    runsql("TRUNCATE {$tbpref}tags2", '');
    runsql("TRUNCATE {$tbpref}texts", '');
    runsql("TRUNCATE {$tbpref}texttags", '');
    runsql("TRUNCATE {$tbpref}words", '');
    runsql("TRUNCATE {$tbpref}wordtags", '');
    runsql("DELETE FROM {$tbpref}settings where StKey = 'currenttext'", '');
    optimizedb();
    get_tags(1);
    get_texttags(1);
}

// -------------------------------------------------------------

function process_term($nonterm, $term, $trans, $wordid, $line): string
{
    $r = '';
    if ($nonterm != '') {
        $r = "-1\t$nonterm\n";
    }
    if ($term != '') {
        $r .=  "$line\t$term\t" . trim($wordid) . "\t" .
        get_first_translation($trans) . "\n";
    }
    return $r;
}

// -------------------------------------------------------------

function get_first_translation($trans): string
{
    $arr = preg_split('/[' . get_sepas()  . ']/u', $trans);
    if (count($arr) < 1) {
        return '';
    }
    $r = trim($arr[0]);
    if ($r == '*') {
        $r = "";
    }
    return $r;
}

// -------------------------------------------------------------

function get_annotation_link($textid): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    if (get_first_value('select length(TxAnnotatedText) as value from ' . $tbpref . 'texts where TxID=' . $textid) > 0) {
        return ' &nbsp;<a href="print_impr_text.php?text=' . $textid .
        '" target="_top"><img src="/assets/icons/tick.png" title="Annotated Text" alt="Annotated Text" /></a>';
    } else {
        return '';
    }
}

/**
 * Like trim, but in place (modify variable)
 *
 * @param string $value Value to be trimmed
 */
function trim_value(&$value): void
{
    $value = trim($value);
}


/**
 * Parses text be read by an automatic audio player.
 *
 * Some non-phonetic alphabet will need this, currently only Japanese
 * is supported, using MeCab.
 *
 * @param  string $text Text to be converted
 * @param  string $lgid Language ID
 * @return string Parsed text in a phonetic format.
 */
function phoneticReading($text, $lgid)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $sentence_split = get_first_value(
        "SELECT LgRegexpWordCharacters AS value FROM {$tbpref}languages
        WHERE LgID = $lgid"
    );

    // For now we only support phonetic text with MeCab
    if ($sentence_split != "mecab") {
        return $text;
    }

    // Japanese is an exception
    $mecab_file = sys_get_temp_dir() . "/" . $tbpref . "mecab_to_db.txt";
    $mecab_args = ' -O yomi ';
    if (file_exists($mecab_file)) {
        unlink($mecab_file);
    }
    $fp = fopen($mecab_file, 'w');
    fwrite($fp, $text . "\n");
    fclose($fp);
    $mecab = get_mecab_path($mecab_args);
    $handle = popen($mecab . $mecab_file, "r");
    /**
     * @var string $mecab_str Output string
     */
    $mecab_str = '';
    while (($line = fgets($handle, 4096)) !== false) {
        $mecab_str .= $line;
    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
    pclose($handle);
    unlink($mecab_file);
    return $mecab_str;
}

/**
 * Parses text be read by an automatic audio player.
 *
 * Some non-phonetic alphabet will need this, currently only Japanese
 * is supported, using MeCab.
 *
 * @param  string $text Text to be converted
 * @param  string $lang Language code (usually BCP 47 or ISO 639-1)
 * @return string Parsed text in a phonetic format.
 *
 * @since 2.9.0 Any language starting by "ja" or "jp" is considered phonetic.
 */
function phonetic_reading($text, $lang)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // Many languages are already phonetic
    if (!str_starts_with($lang, "ja") && !str_starts_with($lang, "jp")) {
        return $text;
    }

    // Japanese is an exception
    $mecab_file = sys_get_temp_dir() . "/" . $tbpref . "mecab_to_db.txt";
    $mecab_args = ' -O yomi ';
    if (file_exists($mecab_file)) {
        unlink($mecab_file);
    }
    $fp = fopen($mecab_file, 'w');
    fwrite($fp, $text . "\n");
    fclose($fp);
    $mecab = get_mecab_path($mecab_args);
    $handle = popen($mecab . $mecab_file, "r");
    /**
     * @var string $mecab_str Output string
     */
    $mecab_str = '';
    while (($line = fgets($handle, 4096)) !== false) {
        $mecab_str .= $line;
    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
    pclose($handle);
    unlink($mecab_file);
    return $mecab_str;
}


/**
 * Refresh a text.
 *
 * @deprecated No longer used, incompatible with new database system.
 * @since      1.6.25-fork Not compatible with the database
 */
function refreshText($word,$tid): string
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    // $word : only sentences with $word
    // $tid : textid
    // only to be used when $showAll = 0 !
    $out = '';
    $wordlc = trim(mb_strtolower($word, 'UTF-8'));
    if ($wordlc == '') {
        return '';
    }
    $sql =
    'SELECT distinct TiSeID FROM ' . $tbpref . 'textitems
    WHERE TiIsNotWord = 0 AND TiTextLC = ' . convert_string_to_sqlsyntax($wordlc) . '
    AND TiTxID = ' . $tid . '
    ORDER BY TiSeID';
    $res = do_mysqli_query($sql);
    $inlist = '(';
    while ($record = mysqli_fetch_assoc($res)) {
        if ($inlist == '(') {
            $inlist .= $record['TiSeID'];
        } else {
            $inlist .= ',' . $record['TiSeID'];
        }
    }
    mysqli_free_result($res);
    if ($inlist == '(') {
        return '';
    } else {
        $inlist =  ' WHERE TiSeID in ' . $inlist . ') ';
    }
    $sql =
    'SELECT TiWordCount AS Code, TiOrder, TiIsNotWord, WoID
    FROM (' . $tbpref . 'textitems
        LEFT JOIN ' . $tbpref . 'words ON (TiTextLC = WoTextLC) AND (TiLgID = WoLgID)
    ) ' . $inlist . '
    ORDER BY TiOrder asc, TiWordCount desc';

    $res = do_mysqli_query($sql);

    $hideuntil = -1;
    $hidetag = "removeClass('hide');";

    while ($record = mysqli_fetch_assoc($res)) {  // MAIN LOOP
        $actcode = (int)$record['Code'];
        $order = (int)$record['TiOrder'];
        $notword = (int)$record['TiIsNotWord'];
        $termex = isset($record['WoID']);
        $spanid = 'ID-' . $order . '-' . $actcode;

        if ($hideuntil > 0 ) {
            if ($order <= $hideuntil ) {
                $hidetag = "addClass('hide');";
            } else {
                $hideuntil = -1;
                $hidetag = "removeClass('hide');";
            }
        }

        if ($notword != 0) {  // NOT A TERM
            $out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
        } else {   // A TERM
            if ($actcode > 1) {   // A MULTIWORD FOUND
                if ($termex) {  // MULTIWORD FOUND - DISPLAY
                    if ($hideuntil == -1) { $hideuntil = $order + ($actcode - 1) * 2;
                    }
                    $out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
                } else {  // MULTIWORD PLACEHOLDER - NO DISPLAY
                    $out .= "$('#" . $spanid . "',context).addClass('hide');\n";
                }
            } // ($actcode > 1) -- A MULTIWORD FOUND
            else {  // ($actcode == 1)  -- A WORD FOUND
                $out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
            }
        }
    } //  MAIN LOOP
    mysqli_free_result($res);
    return $out;
}

/**
 * Create an HTML media player, audio or video.
 *
 * @param string $path   URL or local file path
 * @param int    $offset Offset from the beginning of the video
 *
 * @return void
 */
function makeMediaPlayer($path, $offset=0)
{
    if ($path == '') {
        return;
    }
    /**
    * File extension (if exists)
    */
    $extension = substr($path, -4);
    if ($extension == '.mp3' || $extension == '.wav' || $extension == '.ogg') {
        makeAudioPlayer($path, $offset);
    } else {
        makeVideoPlayer($path, $offset);
    }
}


/**
 * Create an embed video player
 *
 * @param string $path   URL or local file path
 * @param int    $offset Offset from the beginning of the video
 */
function makeVideoPlayer($path, $offset=0): void
{
    $online = false;
    $url = null;
    if (preg_match(
        "/(?:https:\/\/)?www\.youtube\.com\/watch\?v=([\d\w]+)/iu",
        $path, $matches
    )
    ) {
        // Youtube video
        $domain = "https://www.youtube.com/embed/";
        $id = $matches[1];
        $url = $domain . $id . "?t=" . $offset;
        $online = true;
    } else if (preg_match(
        "/(?:https:\/\/)?youtu\.be\/([\d\w]+)/iu",
        $path, $matches
    )
    ) {
        // Youtube video
        $domain = "https://www.youtube.com/embed/";
        $id = $matches[1];
        $url = $domain . $id . "?t=" . $offset;
        $online = true;
    } else if (preg_match(
        "/(?:https:\/\/)?dai\.ly\/([^\?]+)/iu",
        $path, $matches
    )
    ) {
        // Dailymotion
        $domain = "https://www.dailymotion.com/embed/video/";
        $id = $matches[1];
        $url = $domain . $id;
        $online = true;
    } else if (preg_match(
        "/(?:https:\/\/)?vimeo\.com\/(\d+)/iu",
        // Vimeo
        $path, $matches
    )
    ) {
        $domain = "https://player.vimeo.com/video/";
        $id = $matches[1];
        $url = $domain . $id . "#t=" . $offset . "s";
        $online = true;
    }

    if ($online) {
        // Online video player in iframe
        ?>
<iframe style="width: 100%; height: 30%;"
src="<?php echo $url ?>"
title="Video player"
frameborder="0"
allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
allowfullscreen type="text/html">
</iframe>
        <?php
    } else {
        // Local video player
        // makeAudioPlayer($path, $offset);
        $type = "video/" . pathinfo($path, PATHINFO_EXTENSION);
        $title = pathinfo($path, PATHINFO_FILENAME);
        ?>
<video preload="auto" controls title="<?php echo $title ?>"
style="width: 100%; height: 300px; display: block; margin-left: auto; margin-right: auto;">
    <source src="<?php echo $path; ?>" type="<?php echo $type; ?>">
    <p>Your browser does not support video tags.</p>
</video>
        <?php
    }
}


/**
 * Create an HTML audio player.
 *
 * @param string $audio  Audio URL
 * @param int    $offset Offset from the beginning of the video
 *
 * @return void
 */
function makeAudioPlayer($audio, $offset=0)
{
    if ($audio == '') {
        return;
    }
    $audio = trim($audio);
    $repeatMode = (bool) getSettingZeroOrOne('currentplayerrepeatmode', 0);
    $currentplayerseconds = getSetting('currentplayerseconds');
    if ($currentplayerseconds == '') {
        $currentplayerseconds = 5;
    }
    $currentplaybackrate = getSetting('currentplaybackrate');
    if ($currentplaybackrate == '') {
        $currentplaybackrate = 10;
    }
    ?>
<link type="text/css" href="<?php print_file_path('css/jplayer.css');?>" rel="stylesheet" />
<script type="text/javascript" src="/assets/js/jquery.jplayer.js"></script>
<table style="margin-top: 5px; margin-left: auto; margin-right: auto;" cellspacing="0" cellpadding="0">
    <tr>
        <td class="center borderleft" style="padding-left:10px;">
            <span id="do-single" class="click<?php echo ($repeatMode ? '' : ' hide'); ?>"
                style="color:#09F;font-weight: bold;" title="Toggle Repeat (Now ON)">
                <img src="/assets/icons/arrow-repeat.png" alt="Toggle Repeat (Now ON)" title="Toogle Repeat (Now ON)" style="width:24px;height:24px;">
            </span>
            <span id="do-repeat" class="click<?php echo ($repeatMode ? ' hide' : ''); ?>"
                style="color:grey;font-weight: bold;" title="Toggle Repeat (Now OFF)">
                <img src="/assets/icons/arrow-norepeat.png" alt="Toggle Repeat (Now OFF)" title="Toggle Repeat (Now OFF)" style="width:24px;height:24px;">
            </span>
        </td>
        <td class="center bordermiddle">&nbsp;</td>
        <td class="bordermiddle">
            <div id="jquery_jplayer_1" class="jp-jplayer"></div>
            <div class="jp-audio-container">
                <div id="jp_container_1" class="jp-audio">
                    <div class="jp-type-single">
                        <div id="jp_interface_1" class="jp-interface">
                            <ul class="jp-controls">
                                <li><a href="#" class="jp-play">play</a></li>
                                <li><a href="#" class="jp-pause">pause</a></li>
                                <li><a href="#" class="jp-stop">stop</a></li>
                                <li><a href="#" class="jp-mute">mute</a></li>
                                <li><a href="#" class="jp-unmute">unmute</a></li>
                            </ul>
                            <div class="jp-progress-container">
                                <div class="jp-progress">
                                    <div class="jp-seek-bar">
                                        <div class="jp-play-bar">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="jp-volume-bar-container">
                                <div class="jp-volume-bar">
                                    <div class="jp-volume-bar-value">
                                    </div>
                                </div>
                            </div>
                            <div class="jp-current-time">
                            </div>
                            <div class="jp-duration">
                            </div>
                        </div>
                        <div id="jp_playlist_1" class="jp-playlist">
                        </div>
                    </div>
                </div>
            </div>
        </td>
        <td class="center bordermiddle">&nbsp;</td>
        <td class="center bordermiddle">
            <select id="backtime" name="backtime" onchange="{do_ajax_save_setting('currentplayerseconds',document.getElementById('backtime').options[document.getElementById('backtime').selectedIndex].value);}">
                <?php echo get_seconds_selectoptions($currentplayerseconds); ?>
            </select>
            <br />
            <span id="backbutt" class="click">
                <img src="/assets/icons/arrow-circle-225-left.png" alt="Rewind n seconds" title="Rewind n seconds" />
            </span>&nbsp;&nbsp;
            <span id="forwbutt" class="click">
                <img src="/assets/icons/arrow-circle-315.png" alt="Forward n seconds" title="Forward n seconds" />
            </span>
            <span id="playTime" class="hide"></span>
        </td>
        <td class="center bordermiddle">&nbsp;</td>
        <td class="center borderright" style="padding-right:10px;">
            <select id="playbackrate" name="playbackrate">
                <?php echo get_playbackrate_selectoptions($currentplaybackrate); ?>
            </select>
            <br />
            <span id="slower" class="click">
                <img src="/assets/icons/minus.png" alt="Slower" title="Slower" style="margin-top:3px" />
            </span>
            &nbsp;
            <span id="stdspeed" class="click">
                <img src="/assets/icons/status-away.png" alt="Normal" title="Normal" style="margin-top:3px" />
            </span>
            &nbsp;
            <span id="faster" class="click">
                <img src="/assets/icons/plus.png" alt="Faster" title="Faster" style="margin-top:3px" />
            </span>
        </td>
    </tr>
</table>
<!-- Audio controls once that page was loaded -->
<script type="text/javascript">
    //<![CDATA[

    const MEDIA = <?php echo prepare_textdata_js(encodeURI($audio)); ?>;
    const MEDIA_OFFSET = <?php echo $offset; ?>;

    /**
     * Get the extension of a file.
     *
     * @param {string} file File path
     *
     * @returns {string} File extension
     */
    function get_extension(file) {
        return file.split('.').pop();
    }

    /**
     * Import audio data when jPlayer is ready.
     *
     * @returns {undefined}
     */
    function addjPlayerMedia () {
        const ext = get_extension(MEDIA);
        let media_obj = {};
        if (ext == 'mp3') {
            media_obj['mp3'] = MEDIA;
        } else if (ext == 'ogg') {
            media_obj['oga'] = media_obj['ogv'] = media_obj['mp3'] = MEDIA;
        } else if (ext == 'wav') {
            media_obj['wav'] = media_obj['mp3'] = MEDIA;
        } else if (ext == 'mp4') {
            media_obj['mp4'] = MEDIA;
        } else if (ext == 'webm') {
            media_obj['webma'] = media_obj['webmv'] = MEDIA;
        } else {
            media_obj['mp3'] = MEDIA;
        }
        $(this)
        .jPlayer("setMedia", media_obj)
        .jPlayer("pause", MEDIA_OFFSET);
    }

    /**
     * Prepare media interactions with jPlayer.
     *
     * @returns {void}
     */
    function prepareMediaInteractions() {

        $("#jquery_jplayer_1").jPlayer({
            ready: addjPlayerMedia,
            swfPath: "js",
            noVolume: {
                ipad: /^no$/, iphone: /^no$/, ipod: /^no$/,
                android_pad: /^no$/, android_phone: /^no$/,
                blackberry: /^no$/, windows_ce: /^no$/, iemobile: /^no$/, webos: /^no$/,
                playbook: /^no$/
            }
        });

        $("#jquery_jplayer_1")
        .on($.jPlayer.event.timeupdate, function(event) {
            $("#playTime").text(Math.floor(event.jPlayer.status.currentTime));
        });

        $("#jquery_jplayer_1")
        .on($.jPlayer.event.play, function(event) {
            lwt_audio_controller.setCurrentPlaybackRate();
        });

        $("#slower").on('click', lwt_audio_controller.setSlower);
        $("#faster").on('click', lwt_audio_controller.setFaster);
        $("#stdspeed").on('click', lwt_audio_controller.setStdSpeed);
        $("#backbutt").on('click', lwt_audio_controller.clickBackward);
        $("#forwbutt").on('click', lwt_audio_controller.clickForward);
        $("#do-single").on('click', lwt_audio_controller.clickSingle);
        $("#do-repeat").on('click', lwt_audio_controller.clickRepeat);
        $("#playbackrate").on('change', lwt_audio_controller.setNewPlaybackRate);
        $("#backtime").on('change', lwt_audio_controller.setNewPlayerSeconds);

        if (<?php echo json_encode($repeatMode); ?>) {
            lwt_audio_controller.clickRepeat();
        }
    }

    $(document).ready(prepareMediaInteractions);
    //]]>
</script>
    <?php
}

?>

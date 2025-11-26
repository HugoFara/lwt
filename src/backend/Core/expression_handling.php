<?php

/**
 * \file
 * \brief Expression and multi-word handling functions.
 *
 * This file contains functions for finding and inserting multi-word
 * expressions, including MeCab integration for Japanese text processing.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since    2.10.0-fork Split from text_helpers.php
 */

/**
 * Find all occurences of an expression using MeCab.
 *
 * @param string     $text Text to insert
 * @param string|int $lid  Language ID
 *
 * @return (int|string)[][]
 *
 * @global string $tbpref Table name prefix
 *
 * @psalm-return list<array{SeID: int, TxID: int, position: int<min, max>, term: string}>
 */
function findMecabExpression($text, $lid): array
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();

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
        if (
            !empty($arr[0]) && $arr[0] != "EOP"
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
            if (
                !empty($arr[0]) && $arr[0] != "EOP"
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
        $txId = $occ['SeTxID'] ?? $occ['TxID'] ?? 0;
        $mwords[$txId] = array();
        if (getSettingZeroOrOne('showallwords', 1)) {
            $mwords[$txId][$occ['position']] = "&nbsp;$len&nbsp";
        } else {
            $mwords[$txId][$occ['position']] = $occ['term'];
        }
    }
    $flat_mwords = array_reduce(
        $mwords,
        function ($carry, $item) {
            return $carry + $item;
        },
        []
    );

    $sqlarr = array();
    foreach ($occurences as $occ) {
        $txId = $occ["SeTxID"] ?? $occ["TxID"] ?? 0;
        $sqlarr[] = "(" . implode(
            ",",
            [
            $wid, $lid, $txId, $occ["SeID"],
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
 * @return (int|null|string)[][]
 *
 * @global string $tbpref Table name prefix
 *
 * @psalm-return list<array{SeID: int, SeTxID: int, position: int<min, max>, term: null|string, term_display: null|string}>
 */
function findStandardExpression($textlc, $lid): array
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
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
    $rSflag = false; // Flag to prevent repeat space-removal processing
    while ($record = mysqli_fetch_assoc($res)) {
        $string = ' ' . $record['SeText'] . ' ';
        if ($splitEachChar) {
            $string = preg_replace('/([^\s])/u', "$1 ", $string);
        } elseif ($removeSpaces && !$rSflag) {
            preg_match(
                '/(?<=[ ])(' . preg_replace('/(.)/ui', "$1[ ]*", $textlc) .
                ')(?=[ ])/ui',
                $string,
                $ma
            );
            if (!empty($ma[1])) {
                $textlc = trim($ma[1]);
                $notermchar = "/[^$termchar]($textlc)[^$termchar]/ui";
                $rSflag = true; // Pattern found, stop further processing
            }
        }
        $last_pos = mb_strripos($string, $textlc, 0, 'UTF-8');
        // For each occurence of query in sentence
        while ($last_pos !== false) {
            if (
                $splitEachChar || $removeSpaces
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
        $txId = $occ['SeTxID'] ?? $occ['TxID'] ?? 0;
        $mwords[$txId] = array();
        if (getSettingZeroOrOne('showallwords', 1)) {
            $mwords[$txId][$occ['position']] = "&nbsp;$len&nbsp";
        } else {
            $mwords[$txId][$occ['position']] = $occ['term_display'] ?? $occ['term'] ?? '';
        }
    }
    $flat_mwords = array_reduce(
        $mwords,
        function ($carry, $item) {
            return $carry + $item;
        },
        []
    );

    $sqlarr = array();
    foreach ($occurences as $occ) {
        $txId = $occ["SeTxID"] ?? $occ["TxID"] ?? 0;
        $sqlarr[] = "(" . implode(
            ",",
            [
            $wid, $lid, $txId, $occ["SeID"],
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
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
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
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
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
function insertExpressions($textlc, $lid, $wid, $len, $mode): string|null
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
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
            $txId = $occ['SeTxID'] ?? $occ['TxID'] ?? 0;
            $appendtext[$txId] = array();
            if (getSettingZeroOrOne('showallwords', 1)) {
                $appendtext[$txId][$occ['position']] = "&nbsp;$len&nbsp";
            } else {
                if ('MECAB' == strtoupper(trim($regexp))) {
                    $appendtext[$txId][$occ['position']] = $occ['term'];
                } else {
                    $appendtext[$txId][$occ['position']] = $occ['term_display'] ?? $occ['term'];
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
            $txId = $occ["SeTxID"] ?? $occ["TxID"] ?? 0;
            $sqlarr[] = "(" . implode(
                ",",
                [
                $wid, $lid, $txId, $occ["SeID"],
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

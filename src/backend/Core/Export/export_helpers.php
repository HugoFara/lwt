<?php

/**
 * \file
 * \brief Export helper functions for Anki, TSV, and flexible exports.
 *
 * This file contains functions for exporting vocabulary data
 * in various formats including Anki, TSV, and custom flexible formats.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since    3.0.0 Split from session_utility.php
 */

// -------------------------------------------------------------

/**
 * Export terms to Anki format.
 *
 * @param string $sql SQL query to retrieve terms
 *
 * @return never
 */
function anki_export(string $sql)
{
    // WoID, LgRightToLeft, LgRegexpWordCharacters, LgName, WoText, WoTranslation, WoRomanization, WoSentence, taglist
    $res = do_mysqli_query($sql);
    if (!($res instanceof \mysqli_result)) {
        header('Content-type: text/plain; charset=utf-8');
        header("Content-disposition: attachment; filename=lwt_anki_export_" . date('Y-m-d-H-i-s') . ".txt");
        exit();
    }
    $x = '';
    while ($record = mysqli_fetch_assoc($res)) {
        if ('MECAB' == strtoupper(trim((string) $record['LgRegexpWordCharacters']))) {
            $termchar = '一-龥ぁ-ヾ';
        } else {
            $termchar = (string)$record['LgRegexpWordCharacters'];
        }
        $rtlScript = (int)$record['LgRightToLeft'] === 1;
        $span1 = ($rtlScript ? '<span dir="rtl">' : '');
        $span2 = ($rtlScript ? '</span>' : '');
        $lpar = ($rtlScript ? ']' : '[');
        $rpar = ($rtlScript ? '[' : ']');
        $sent = tohtml(repl_tab_nl((string)$record["WoSentence"]));
        $sent1 = str_replace(
            "{",
            '<span style="font-weight:600; color:#0000ff;">' . $lpar,
            str_replace(
                "}",
                $rpar . '</span>',
                mask_term_in_sentence($sent, $termchar)
            )
        );
        $sent2 = str_replace("{", '<span style="font-weight:600; color:#0000ff;">', str_replace("}", '</span>', $sent));
        $x .= $span1 . tohtml(repl_tab_nl((string)$record["WoText"])) . $span2 . "\t" .
        tohtml(repl_tab_nl((string)$record["WoTranslation"])) . "\t" .
        tohtml(repl_tab_nl((string)$record["WoRomanization"])) . "\t" .
        $span1 . $sent1 . $span2 . "\t" .
        $span1 . $sent2 . $span2 . "\t" .
        tohtml(repl_tab_nl((string)$record["LgName"])) . "\t" .
        tohtml($record["WoID"]) . "\t" .
        tohtml($record["taglist"]) .
        "\r\n";
    }
    mysqli_free_result($res);
    header('Content-type: text/plain; charset=utf-8');
    header("Content-disposition: attachment; filename=lwt_anki_export_" . date('Y-m-d-H-i-s') . ".txt");
    echo $x;
    exit();
}

// -------------------------------------------------------------

/**
 * Export terms to TSV format.
 *
 * @param string $sql SQL query to retrieve terms
 *
 * @return never
 */
function tsv_export(string $sql)
{
    // WoID, LgName, WoText, WoTranslation, WoRomanization, WoSentence, WoStatus, taglist
    $res = do_mysqli_query($sql);
    if (!($res instanceof \mysqli_result)) {
        header('Content-type: text/plain; charset=utf-8');
        header(
            "Content-disposition: attachment; filename=lwt_tsv_export_" .
            date('Y-m-d-H-i-s') . ".txt"
        );
        exit();
    }
    $x = '';
    while ($record = mysqli_fetch_assoc($res)) {
        $x .= repl_tab_nl((string)$record["WoText"]) . "\t" .
        repl_tab_nl((string)$record["WoTranslation"]) . "\t" .
        repl_tab_nl((string)$record["WoSentence"]) . "\t" .
        repl_tab_nl((string)$record["WoRomanization"]) . "\t" .
        ($record["WoStatus"] ?? '') . "\t" .
        repl_tab_nl((string)$record["LgName"]) . "\t" .
        ($record["WoID"] ?? '') . "\t" .
        ($record["taglist"] ?? '') . "\r\n";
    }
    mysqli_free_result($res);
    header('Content-type: text/plain; charset=utf-8');
    header(
        "Content-disposition: attachment; filename=lwt_tsv_export_" .
        date('Y-m-d-H-i-s') . ".txt"
    );
    echo $x;
    exit();
}

// -------------------------------------------------------------

/**
 * Export terms using a flexible export template.
 *
 * @param string $sql SQL query to retrieve terms
 *
 * @return never
 */
function flexible_export(string $sql)
{
    // WoID, LgName, LgExportTemplate, LgRightToLeft, WoText, WoTextLC, WoTranslation, WoRomanization, WoSentence, WoStatus, taglist
    $res = do_mysqli_query($sql);
    if (!($res instanceof \mysqli_result)) {
        header('Content-type: text/plain; charset=utf-8');
        header(
            "Content-disposition: attachment; filename=lwt_flexible_export_" .
            date('Y-m-d-H-i-s') . ".txt"
        );
        exit();
    }
    $x = '';
    while ($record = mysqli_fetch_assoc($res)) {
        if (isset($record['LgExportTemplate'])) {
            $woid = (string)$record['WoID'];
            $langname = repl_tab_nl((string)$record['LgName']);
            $rtlScript = (int)$record['LgRightToLeft'] === 1;
            $span1 = ($rtlScript ? '<span dir="rtl">' : '');
            $span2 = ($rtlScript ? '</span>' : '');
            $term = repl_tab_nl((string)$record['WoText']);
            $term_lc = repl_tab_nl((string)$record['WoTextLC']);
            $transl = repl_tab_nl((string)$record['WoTranslation']);
            $rom = repl_tab_nl((string)$record['WoRomanization']);
            $sent_raw = repl_tab_nl((string)$record['WoSentence']);
            $sent = str_replace('{', '', str_replace('}', '', $sent_raw));
            $sent_c = mask_term_in_sentence_v2($sent_raw);
            $sent_d = str_replace('{', '[', str_replace('}', ']', $sent_raw));
            $sent_x = str_replace('{', '{{c1::', str_replace('}', '}}', $sent_raw));
            $sent_y = str_replace(
                '{',
                '{{c1::',
                str_replace('}', '::' . $transl . '}}', $sent_raw)
            );
            $status = (string)$record['WoStatus'];
            $taglist = trim((string)$record['taglist']);
            $xx = repl_tab_nl((string)$record['LgExportTemplate']);
            $xx = str_replace('%w', $term, $xx);
            $xx = str_replace('%t', $transl, $xx);
            $xx = str_replace('%s', $sent, $xx);
            $xx = str_replace('%c', $sent_c, $xx);
            $xx = str_replace('%d', $sent_d, $xx);
            $xx = str_replace('%r', $rom, $xx);
            $xx = str_replace('%a', $status, $xx);
            $xx = str_replace('%k', $term_lc, $xx);
            $xx = str_replace('%z', $taglist, $xx);
            $xx = str_replace('%l', $langname, $xx);
            $xx = str_replace('%n', $woid, $xx);
            $xx = str_replace('%%', '%', $xx);
            $xx = str_replace('$w', $span1 . tohtml($term) . $span2, $xx);
            $xx = str_replace('$t', tohtml($transl), $xx);
            $xx = str_replace('$s', $span1 . tohtml($sent) . $span2, $xx);
            $xx = str_replace('$c', $span1 . tohtml($sent_c) . $span2, $xx);
            $xx = str_replace('$d', $span1 . tohtml($sent_d) . $span2, $xx);
            $xx = str_replace('$x', $span1 . tohtml($sent_x) . $span2, $xx);
            $xx = str_replace('$y', $span1 . tohtml($sent_y) . $span2, $xx);
            $xx = str_replace('$r', tohtml($rom), $xx);
            $xx = str_replace('$k', $span1 . tohtml($term_lc) . $span2, $xx);
            $xx = str_replace('$z', tohtml($taglist), $xx);
            $xx = str_replace('$l', tohtml($langname), $xx);
            $xx = str_replace('$$', '$', $xx);
            $xx = str_replace('\t', "\t", $xx);
            $xx = str_replace('\n', "\n", $xx);
            $xx = str_replace('\r', "\r", $xx);
            $xx = str_replace('\\\\', '\\', $xx);
            $x .= $xx;
        }
    }
    mysqli_free_result($res);
    header('Content-type: text/plain; charset=utf-8');
    header(
        "Content-disposition: attachment; filename=lwt_flexible_export_" .
        date('Y-m-d-H-i-s') . ".txt"
    );
    echo $x;
    exit();
}

// -------------------------------------------------------------

/**
 * Mask the term in a sentence by replacing it with "[...]".
 *
 * @param string $s Sentence with term marked by {} brackets
 *
 * @return string Sentence with term replaced by "[...]"
 */
function mask_term_in_sentence_v2(string $s): string
{
    $l = mb_strlen($s, 'utf-8');
    $r = '';
    $on = 0;
    for ($i = 0; $i < $l; $i++) {
        $c = mb_substr($s, $i, 1, 'UTF-8');
        if ($c == '}') {
            $on = 0;
            continue;
        }
        if ($c == '{') {
            $on = 1;
            $r .= '[...]';
            continue;
        }
        if ($on == 0) {
            $r .= $c;
        }
    }
    return $r;
}

/**
 * Replace all white space characters by a simple space ' '.
 * The output string is also trimmed.
 *
 * @param string $s String to parse
 *
 * @return string String with only simple whitespaces.
 */
function repl_tab_nl(string $s): string
{
    $s = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $s);
    $s = preg_replace('/\s/u', ' ', $s);
    $s = preg_replace('/\s{2,}/u', ' ', $s);
    return trim($s);
}

// -------------------------------------------------------------

/**
 * Mask the term in a sentence by replacing characters with bullets.
 *
 * @param string $s         Sentence with term marked by {} brackets
 * @param string $regexword Regex pattern for word characters
 *
 * @return string Sentence with term characters replaced by bullets
 */
function mask_term_in_sentence(string $s, string $regexword): string
{
    $l = mb_strlen($s, 'utf-8');
    $r = '';
    $on = 0;
    for ($i = 0; $i < $l; $i++) {
        $c = mb_substr($s, $i, 1, 'UTF-8');
        if ($c == '}') {
            $on = 0;
        }
        if ($on) {
            if (preg_match('/[' . $regexword . ']/u', $c)) {
                $r .= '•';
            } else {
                $r .= $c;
            }
        } else {
            $r .= $c;
        }
        if ($c == '{') {
            $on = 1;
        }
    }
    return $r;
}

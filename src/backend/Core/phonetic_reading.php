<?php

/**
 * \file
 * \brief Phonetic reading functions.
 *
 * This file contains functions for converting text to phonetic representations,
 * primarily for Japanese text using MeCab.
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
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
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
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
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
function refreshText($word, $tid): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
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

        if ($hideuntil > 0) {
            if ($order <= $hideuntil) {
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
                    if ($hideuntil == -1) {
                        $hideuntil = $order + ($actcode - 1) * 2;
                    }
                    $out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
                } else {  // MULTIWORD PLACEHOLDER - NO DISPLAY
                    $out .= "$('#" . $spanid . "',context).addClass('hide');\n";
                }
            } else {
                // ($actcode == 1)  -- A WORD FOUND
                $out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
            }
        }
    } //  MAIN LOOP
    mysqli_free_result($res);
    return $out;
}

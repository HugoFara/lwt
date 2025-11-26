<?php

/**
 * \file
 * \brief Language utility functions.
 *
 * This file contains functions for retrieving language information,
 * language codes, and script direction.
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
    } elseif (isset($lid) && trim($lid) != '' && ctype_digit($lid)) {
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

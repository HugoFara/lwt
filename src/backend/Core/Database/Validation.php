<?php

/**
 * \file
 * \brief Database ID and tag validation utilities.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-database-validation.html
 * @since    3.0.0
 */

namespace Lwt\Database;

use Lwt\Core\LWT_Globals;

/**
 * Database ID and tag validation utilities.
 *
 * Provides methods for validating language IDs, text IDs, and tag IDs
 * against the database to ensure they exist.
 *
 * @since 3.0.0
 */
class Validation
{
    /**
     * Validate a language ID.
     *
     * @param string $currentlang Language ID to validate
     *
     * @return string '' if the language is not valid, $currentlang otherwise
     */
    public static function language(string $currentlang): string
    {
        if ($currentlang == '' || !is_numeric($currentlang)) {
            return '';
        }
        // Cast to integer for safety against SQL injection
        $currentlang_int = (int)$currentlang;
        $sql_string = 'SELECT count(LgID) AS value
        FROM ' . LWT_Globals::getTablePrefix() . 'languages
        WHERE LgID=' . $currentlang_int;
        if (get_first_value($sql_string) == 0) {
            return '';
        }
        return (string)$currentlang_int;
    }

    /**
     * Validate a text ID.
     *
     * @param string $currenttext Text ID to validate
     *
     * @return string '' if the text is not valid, $currenttext otherwise
     */
    public static function text(string $currenttext): string
    {
        if ($currenttext == '' || !is_numeric($currenttext)) {
            return '';
        }
        // Cast to integer for safety against SQL injection
        $currenttext_int = (int)$currenttext;
        $sql_string = 'SELECT count(TxID) AS value
        FROM ' . LWT_Globals::getTablePrefix() . 'texts WHERE TxID=' .
        $currenttext_int;
        if (get_first_value($sql_string) == 0) {
            return '';
        }
        return (string)$currenttext_int;
    }

    /**
     * Validate a tag ID for words.
     *
     * @param string $currenttag  Tag ID to validate
     * @param string $currentlang Optional language ID filter
     *
     * @return string '' if invalid, $currenttag otherwise
     */
    public static function tag(string $currenttag, string $currentlang): string
    {
        $tbpref = LWT_Globals::getTablePrefix();
        if ($currenttag != '' && $currenttag != '-1') {
            // Sanitize inputs to prevent SQL injection
            if (!is_numeric($currenttag)) {
                return '';
            }
            $currenttag_int = (int)$currenttag;

            $lang_condition = '';
            if ($currentlang != '') {
                if (!is_numeric($currentlang)) {
                    return '';
                }
                $currentlang_int = (int)$currentlang;
                $lang_condition = " AND WoLgID = " . $currentlang_int;
            }

            $sql = "SELECT (
                " . $currenttag_int . " IN (
                    SELECT TgID
                    FROM {$tbpref}words, {$tbpref}tags, {$tbpref}wordtags
                    WHERE TgID = WtTgID AND WtWoID = WoID" .
                    $lang_condition .
                    " group by TgID order by TgText
                )
            ) AS value";
            $r = get_first_value($sql);
            if ($r == 0) {
                $currenttag = '';
            }
        }
        return $currenttag;
    }

    /**
     * Validate a tag ID for archived texts.
     *
     * @param string $currenttag  Tag ID to validate
     * @param string $currentlang Optional language ID filter
     *
     * @return string '' if invalid, $currenttag otherwise
     */
    public static function archTextTag(string $currenttag, string $currentlang): string
    {
        $tbpref = LWT_Globals::getTablePrefix();
        if ($currenttag != '' && $currenttag != '-1') {
            // Sanitize inputs to prevent SQL injection
            if (!is_numeric($currenttag)) {
                return '';
            }
            $currenttag_int = (int)$currenttag;

            if ($currentlang == '') {
                $sql = "select (
                    " . $currenttag_int . " in (
                        select T2ID
                        from {$tbpref}archivedtexts,
                        {$tbpref}tags2,
                        {$tbpref}archtexttags
                        where T2ID = AgT2ID and AgAtID = AtID
                        group by T2ID order by T2Text
                    )
                ) as value";
            } else {
                if (!is_numeric($currentlang)) {
                    return '';
                }
                $currentlang_int = (int)$currentlang;
                $sql = "select (
                    " . $currenttag_int . " in (
                        select T2ID
                        from {$tbpref}archivedtexts,
                        {$tbpref}tags2,
                        {$tbpref}archtexttags
                        where T2ID = AgT2ID and AgAtID = AtID and AtLgID = " . $currentlang_int . "
                        group by T2ID order by T2Text
                    )
                ) as value";
            }
            $r = get_first_value($sql);
            if ($r == 0) {
                $currenttag = '';
            }
        }
        return $currenttag;
    }

    /**
     * Validate a tag ID for texts.
     *
     * @param string $currenttag  Tag ID to validate
     * @param string $currentlang Optional language ID filter
     *
     * @return string '' if invalid, $currenttag otherwise
     */
    public static function textTag(string $currenttag, string $currentlang): string
    {
        $tbpref = LWT_Globals::getTablePrefix();
        if ($currenttag != '' && $currenttag != '-1') {
            // Sanitize inputs to prevent SQL injection
            if (!is_numeric($currenttag)) {
                return '';
            }
            $currenttag_int = (int)$currenttag;

            if ($currentlang == '') {
                $sql = "select (
                    $currenttag_int in (
                        select T2ID
                        from {$tbpref}texts, {$tbpref}tags2, {$tbpref}texttags
                        where T2ID = TtT2ID and TtTxID = TxID
                        group by T2ID
                        order by T2Text
                    )
                ) as value";
            } else {
                if (!is_numeric($currentlang)) {
                    return '';
                }
                $currentlang_int = (int)$currentlang;
                $sql = "select (
                    $currenttag_int in (
                        select T2ID
                        from {$tbpref}texts, {$tbpref}tags2, {$tbpref}texttags
                        where T2ID = TtT2ID and TtTxID = TxID and TxLgID = $currentlang_int
                        group by T2ID order by T2Text
                    )
                ) as value";
            }
            $r = get_first_value($sql);
            if ($r == 0) {
                $currenttag = '';
            }
        }
        return $currenttag;
    }
}

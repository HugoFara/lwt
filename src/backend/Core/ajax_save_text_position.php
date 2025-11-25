<?php

/**
 * \file
 * \brief Save text or audio position (Read Text Screen)
 *
 * Call: inc/ajax_save_text_position.php?...
 *          ...textid=[textid]&position=[text position]
 *          ...textid=[textid]&audioposition=[audio position]
 *
 * @package Lwt
 * @author  andreask7 <andreask7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-ajax-save-setting.html
 * @since   1.6.0-fork
 */

require_once __DIR__ . '/session_utility.php';

/**
 * Save the reading position of the text.
 *
 * @param int $textid   Text ID
 * @param int $position Position in text to save
 *
 * @return void
 *
 * @global string $tbpref Database table prefix
 */
function save_text_position($textid, $position)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    runsql(
        "UPDATE {$tbpref}texts
        SET TxPosition = $position
        WHERE TxID = $textid",
        ""
    );
}

/**
 * Save the audio position in the text.
 *
 * @param int $textid        Text ID
 * @param int $audioposition Audio position
 *
 * @return void
 *
 * @global string $tbpref Database table prefix
 */
function save_audio_position($textid, $audioposition)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    runsql(
        "UPDATE {$tbpref}texts
        SET TxAudioPosition = $audioposition
        WHERE TxID = $textid",
        ""
    );
}

if (getreq('textid') != '') {
    chdir('..');
    $textid = (int)$_REQUEST['id'];
    if (getreq('position')) {
        save_text_position(
            $textid,
            is_numeric(getreq('position')) ? (int)getreq('position') : null
        );
    } else if (getreq('audioposition')) {
        save_audio_position(
            $textid,
            is_numeric(getreq('audioposition')) ? (int)getreq('audioposition') : null
        );
    }
}
?>

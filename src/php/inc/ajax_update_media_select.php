<?php


/**
 * \file
 * \brief Updating media select in edit_texts.php
 *
 * Call: inc/ajax_update_media_select.php
 *
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-ajax-update-media-select.html
 * @since   1.1.0
 *
 * @deprecated 2.9.0 Use the REST API instead.
 */

require_once __DIR__ . '/session_utility.php';

/**
 * Change the current working directory and find media path
 *
 * @return string Media path
 */
function do_ajax_update_media_select()
{
    chdir('..');
    return selectmediapath('TxAudioURI');
}

echo do_ajax_update_media_select();

?>
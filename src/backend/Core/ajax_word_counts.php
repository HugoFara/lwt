<?php

/**
 * \file
 * \brief Calculating Word Counts, Ajax call in edit_texts.php
 *
 * Call: inc/ajax_word_counts.php?id=[textid1,textid2,...]
 *
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-ajax-word-counts.html
 * @since   1.0.3
 */

require_once __DIR__ . '/session_utility.php';

/**
 * Do the word count for a specific text.
 *
 * @param string $textid Text IDs seperated by comma
 *
 * @return void
 *
 * @deprecated 2.9.0 Use the REST API instead
 */
function do_ajax_word_counts($textid)
{
    chdir('..');
    echo json_encode(return_textwordcount($textid));
}

if (isset($_POST["id"])) {
    do_ajax_word_counts($_POST["id"]);
}

?>

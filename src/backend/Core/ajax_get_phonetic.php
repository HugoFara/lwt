<?php
/**
 * \file
 * \brief Make the phonetic translation of a word.
 *
 * Call: inc/ajax_get_phonetic.php?text=[text_string]&lang=[language_string]
 *
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-ajax-get-phonetic.html
 * @since   2.3.0-fork
 *
 * @deprecated Use ajax.php with action_type=get_phonetic instead. Deprecated in 2.9.0.
 */
require_once 'session_utility.php';

if (isset($_GET['text']) && isset($_GET['lang'])) {
    echo phonetic_reading(getreq('text'), getreq('lang'));
}

?>

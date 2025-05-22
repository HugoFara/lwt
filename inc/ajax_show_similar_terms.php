<?php

/**
 * \file
 * \brief Show similar terms
 *
 * Call: ajax_show_similar_terms?...
 *    ... lang=[langid] ... language
 *    ... word=[word] ... word
 *
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-ajax-show-similar-terms.html
 * @since   1.5.18
 *
 * @deprecated Since 2.9.0, it is recommended to use the REST API.
 */

require_once __DIR__ . '/simterms.php';

/**
 * Return the terms similar to a word.
 *
 * @param int    $langid Language ID
 * @param string $word   Word to be looked at
 *
 * @return string A list of similar terms
 */
function do_ajax_show_similar_terms($langid, $word)
{
    chdir('..');
    return print_similar_terms($langid, $word);
}

if (isset($_POST['lang']) && isset($_POST['word'])) {
    echo do_ajax_show_similar_terms((int)$_POST['lang'], $_POST['word']);
}

?>

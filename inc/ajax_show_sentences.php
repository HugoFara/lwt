<?php

/**
 * \file
 * \brief Show sentences in edit_texts.php, etc.
 *
 * Call: inc/ajax_show_sentences.php?...
 *    ... lang=[langid] ... language
 *    ... word=[word] ... word in lowercase
 *    ... ctl=[ctl] ... sentence js control
 *
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-ajax-show-imported-terms.html
 * @since   1.2.0
 *
 * @deprecated 2.9.0 Use the REST API instead.
 */

require_once __DIR__ . '/session_utility.php';

/**
 * Return the sentences associated to this word.
 *
 * @param int    $langid Language ID
 * @param int    $wid    Word ID
 * @param string $word   Word translation
 * @param string $ctl    Path for the textarea of the sentence of the word being
 *                       edited.
 *
 * @return string
 *
 * @deprecated 2.9.0 Use the REST API instead.
 */
function do_ajax_show_sentences($langid, $wid, $word, $ctl)
{
    chdir('..');

    return get20Sentences(
        $langid,
        $word,
        $wid,
        $ctl,
        (int) getSettingWithDefault('set-term-sentence-count')
    );
}

if (isset($_POST['lang']) && isset($_POST['word'])
    && isset($_POST['woid']) && isset($_POST['ctl'])
) {
    echo do_ajax_show_sentences(
        (int)$_POST['lang'], (int)$_POST['woid'], $_POST['word'], $_POST['ctl']
    );
}
?>
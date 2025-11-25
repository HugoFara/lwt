<?php

/**
 * Google Translate interface
 *
 * Call: ggl.php?text=[text]&sl=[source language]&tl=[target language] ... translate text
 *     ... sent=[int] ... single sentence mode.
 *
 * PHP version 8.1
 *
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/ggl.html
 * @since   1.6.0
 * @since   2.7.0 Refactored with functional paradigm
 */
namespace Lwt\Interface;

require_once 'inc/session_utility.php';
require_once 'inc/google_time_token.php' ;
require_once 'inc/classes/GoogleTranslate.php';

use Lwt\Classes\GoogleTranslate as GoogleTranslate;

use function Lwt\Includes\getGoogleTimeToken;

/**
 * Translate a single sentence using Google Translate.
 *
 * @param string $text        Text to translate
 * @param string $translation Sentence translation
 *
 * @return void
 */
function translate_sentence($text, $translation): void
{
    ?>
    <h2>Sentence Translation</h2>
    <span title="Translated via Google Translate">
        <?php echo tohtml($translation); ?>
    </span>
    <p>Original sentence: </p>
    <blockquote><?php echo tohtml($text); ?></blockquote>
    <?php
}

/**
 * Translate input text using Google Translate.
 *
 * @param string $text   Text to translate
 * @param array  $file   Array of translated terms
 * @param string $sl     Source language (e. g. "es")
 * @param string $tl     Target language (e. g. "en")
 *
 * @return void
 */
function translate_term($text, $file, $sl, $tl): void
{
    global $tbpref;
    $lg_id = getSetting('currentlangage');
    $voiceApi = get_first_value(
        "SELECT LgTTSVoiceAPI AS value FROM {$tbpref}languages
        WHERE LgID = $lg_id"
    );
    ?>
<h2 title="Translate with Google Translate">
    Word translation: <?php echo tohtml($text) ?>
    <img id="textToSpeech" style="cursor: pointer;" title="Click to read!"
    src="<?php print_file_path('icn/speaker-volume.png'); ?>" ></img>

    <img id="del_translation" style="cursor: pointer;"
    title="Empty Translation Field" onclick="deleteTranslation ();"
    src="<?php print_file_path('icn/broom.png'); ?>" ></img>
</h2>

<script type="text/javascript">
    $('#textToSpeech').on('click', function () {
            const txt = <?php echo json_encode($text); ?>;
            speechDispatcher(txt, <?php echo json_encode($lg_id); ?>);
    });

    $(document).ready(function() {
        if (window.parent.frames['ro'] === undefined && window.opener === undefined)
            $('#del_translation').remove();
    });
</script>
    <?php
    foreach ($file as $word){
        echo '<span class="click" onclick="addTranslation(' .
        prepare_textdata_js($word) . ');">' .
        '<img src="icn/tick-button.png" title="Copy" alt="Copy" /> &nbsp; ' .
        tohtml($word) . '</span><br />';
    }
    ?>
    <p>
        (Click on <img src="icn/tick-button.png" title="Choose" alt="Choose" />
        to copy word(s) into above term)<br />&nbsp;
    </p>
    <hr />
    <form action="ggl.php" method="get">
        Unhappy?<br/>Change term:
        <input type="text" name="text" maxlength="250" size="15"
        value="<?php echo tohtml($text); ?>">
        <input type="hidden" name="sl" value="<?php echo tohtml($sl); ?>">
        <input type="hidden" name="tl" value="<?php echo tohtml($tl); ?>">
        <input type="submit" value="Translate via Google Translate">
    </form>
    <?php
}

/**
 * Translate input text using Google Translate.
 *
 * @param string $text Text to translate
 * @param string $sl Source language (e. g. "es")
 * @param string $tl Target language (e. g. "en")
 * @param bool $sentence_mode Set to true for full sentence translation
 *
 * @return void
 */
function translate_text($text, $sl, $tl, $sentence_mode): void
{
    $file = GoogleTranslate::staticTranslate(
        $text, $sl, $tl, getGoogleTimeToken()
    );

    if ($file === false) {
        my_die("Unable to get translation from Google!");
    }

    $gglink = makeOpenDictStr(
        createTheDictLink(
            "https://translate.google.com/?sl=$sl&tl=$tl&text=lwt_term&lwt_popup=true",
            $text
        ),
        "View on Google Translate"
    );

    if ($sentence_mode) {
        translate_sentence($text, $file[0]);
    } else {
        translate_term($text, $file, $sl, $tl);
    }

    echo $gglink;
}

/**
 * Translate input text using Google Translate.
 *
 * @param string $text Text to translate
 *
 * @return void
 */
function do_content($text): void
{
    header('Pragma: no-cache');
    header('Expires: 0');
    pagestart_nobody('Google Translate');
    if (trim($text) != '') {
        translate_text($text, $_GET["sl"], $_GET["tl"], isset($_GET['sent']));
    } else {
        echo '<p class="msgblue">Term is not set!</p>';
    }
    pageend();
}

if (isset($_GET["text"])) {
    do_content($_GET["text"]);
}
?>

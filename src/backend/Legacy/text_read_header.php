<?php

/**
 * \file
 * \brief Responsible for drawing the header when reading texts
 *
 * Call: do_text_header.php?text=[textid]
 *
 * PHP version 8.1
 *
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/do-text-header.html
 * @since   1.0.3
 */

require_once 'Core/session_utility.php';
// To get the BCP 47 language tag
require_once 'Core/langdefs.php' ;

/**
 * Get the text and language data associated with the text.
 *
 * @param string|int $textid ID of the text
 *
 * @global string $tbpref Table name prefix
 *
 * @since 2.0.3-fork
 *
 * @return (float|int|null|string)[]|false|null LgName, TxLgID, TxText, TxTitle, TxAudioURI, TxSourceURI, TxAudioPosition for the text.
 *
 * @psalm-return array<string, float|int|null|string>|false|null
 */
function getData($textid)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $sql =
    'SELECT LgName, TxLgID, TxText, TxTitle, TxAudioURI, TxSourceURI, TxAudioPosition
    FROM ' . $tbpref . 'texts
    JOIN ' . $tbpref . 'languages ON TxLgID = LgID
    WHERE TxID = ' . $textid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    return $record;
}

/**
 * Print the main title row.
 *
 * @param int    $textid Text ID
 * @param string $langid Language ID to navigate between
 *                       texts of same language
 *
 * @since 2.0.4-fork
 */
function do_header_row($textid, $langid): void
{
    ?>
<div class="flex-header">
    <div>
    <a href="/texts" target="_top">
        <?php echo_lwt_logo(); ?>
    </a>
    </div>
    <div>
        <?php
        echo getPreviousAndNextTextLinks(
            $textid,
            '/text/read?start=',
            false,
            ''
        );
    ?>
    </div>
    <div>
        <a href="/test?text=<?php echo $textid; ?>" target="_top">
            <img src="/assets/icons/question-balloon.png" title="Test" alt="Test" />
        </a>
        <a href="/text/print-plain?text=<?php echo $textid; ?>" target="_top">
            <img src="/assets/icons/printer.png" title="Print" alt="Print" />
        </a>
        <?php echo get_annotation_link($textid); ?>
        <a target="_top" href="/texts?chg=<?php echo $textid; ?>">
            <img src="/assets/icons/document--pencil.png" title="Edit Text" alt="Edit Text" />
        </a>
    </div>
    <div>
        <a
            href="/word/new?text=<?php echo $textid; ?>&amp;lang=<?php echo $langid; ?>"
            target="ro" onclick="showRightFrames();"
        >
            <img src="/assets/icons/sticky-note--plus.png" title="New Term" alt="New Term" />
        </a>
    </div>
    <div>
        <?php quickMenu(); ?>
    </div>
</div>
    <?php
}

/**
 * Print the title of the text.
 *
 * @param string $title     Title of the text
 * @param string $sourceURI URL of the text (if any)
 *
 * @since 2.0.4-fork
 */
function do_title($title, $sourceURI): void
{
    ?>
    <h1>READ ▶
        <?php
    echo tohtml($title);
    if (isset($sourceURI) && !str_starts_with(trim($sourceURI), '#')) {
        ?>
        <a href="<?php echo $sourceURI ?>" target="_blank">
            <img src="<?php echo get_file_path('assets/icons/chain.png') ?>" title="Text Source" alt="Text Source" />
        </a>
            <?php
    }
    ?>
    </h1>
    <?php
}

/**
 * Prepare user settings for this text.
 *
 * @param string|int $textid Text ID
 *
 * @since 2.0.4-fork
 */
function do_settings($textid): void
{
    // User settings
    $showAll = getSettingZeroOrOne('showallwords', 1);
    $showLearning = getSettingZeroOrOne('showlearningtranslations', 1);

    ?>
<div class="flex-spaced">
    <div>
        Unknown words:
        <span id="learnstatus"><?php echo todo_words_content((int) $textid); ?></span>
    </div>
    <div
    title="[Show All] = ON: ALL terms are shown, and all multi-word terms are shown as superscripts before the first word. The superscript indicates the number of words in the multi-word term.
[Show All] = OFF: Multi-word terms now hide single words and shorter or overlapping multi-word terms.">
        <label for="showallwords">Show All</label>&nbsp;
        <input type="checkbox" id="showallwords" <?php echo get_checked($showAll); ?>
        onclick="showAllwordsClick();" />
</div>
    <div
    title="[Learning Translations] = ON: Terms with Learning Level&nbsp;1 display their translations under the term.
[Learning Translations] = OFF: No translations are shown in the reading mode.">
        <label for="showlearningtranslations">Translations</label>&nbsp;
        <input type="checkbox" id="showlearningtranslations"
        <?php echo get_checked($showLearning); ?> onclick="showAllwordsClick();" />
</div>
    <div id="thetextid" class="hide"><?php echo $textid; ?></div>
    <div><button id="readTextButton">Read in browser</button></div>
</div>
    <?php
}

/**
 * Prints javascript data and function to read text
 * in your browser.
 *
 * @param string $text         Text to read
 * @param string $languageName Full name of the language (i. e.: "English")
 *
 * @global string $tbpref
 *
 * @since 2.0.3-fork
 * @since 2.9.1-fork Function may work even when the language name was manually changed.
 */
function browser_tts($text, $languageName): void
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $lg_id = (int) get_first_value(
        "SELECT LgID as value
        FROM {$tbpref}languages
        WHERE LgName = " . convert_string_to_sqlsyntax($languageName)
    );
    $languageCode = getLanguageCode($lg_id, LWT_LANGUAGES_ARRAY);
    // Phonetic reading for this text. As it can be long do not use AJAX
    $phoneticText = phoneticReading($text, $lg_id);
    $voiceApi = get_first_value(
        "SELECT LgTTSVoiceAPI AS value FROM {$tbpref}languages
        WHERE LgID = $lg_id"
    );
    ?>
<script type="text/javascript">

    /// Main object for text-to-speech interaction with SpeechSynthesisUtterance
    const text_reader = {
        /// The text to read
        text: <?php echo json_encode($phoneticText); ?>,

        /// {string} ISO code for the language
        lang: getLangFromDict(LWT_DATA.language.translator_link) || <?php echo json_encode($languageCode); ?>,

        /// {string} Rate at wich the speech is done, deprecated since 2.10.0
        rate: 0.8
    };

    LWT_DATA.language.ttsVoiceApi = <?php echo json_encode($voiceApi); ?>;

    /**
     * Check browser compatibility before reading
     */
    function init_reading() {
        if (!('speechSynthesis' in window)) {
            alert('Your browser does not support speechSynthesis!');
            return;
        }
        readRawTextAloud(
            text_reader.text, getLangFromDict(LWT_DATA.language.translator_link) || text_reader.lang
        );
    }

    /** Start and stop the reading feature. */
    function toggle_reading() {
        const synth = window.speechSynthesis;
        if (synth === undefined) {
            alert('Your browser does not support speechSynthesis!');
            return;
        }
        if (synth.speaking) {
            synth.cancel();
        } else {
            init_reading();
        }
    }

    /**
     * Change the annotations display mode
     *
     * @param {string} mode The new annotation mode
     */
    function annotationModeChanged(mode) {
        console.log(mode);
        // 2.9.0: seems to be a debug function, candidate to deletion
    }
</script>
    <?php
}

/**
 * Save the position of the audio reading for a text.
 *
 * @param string|int $textid ID of the text
 *
 * @since 2.0.4-fork
 */
function save_audio_position($textid): void
{
    if (is_string($textid)) {
        $textid_int = (int) $textid;
    } else {
        $textid_int = $textid;
    }
    ?>

<script type="text/javascript">
    /**
     * Save text status, for instance audio position
     */
    function saveTextStatus() {
        // Save audio if present
        if ($("#jquery_jplayer_1") === null || $("#jquery_jplayer_1").length == 0) {
            return;
        }
        const pos = $("#jquery_jplayer_1").data("jPlayer").status.currentTime;
        const text_id = <?php echo $textid_int; ?>;
        saveAudioPosition(text_id, pos);
    }

    $(window).on('beforeunload', saveTextStatus);

    // We need to capture the text-to-speach event manually for Chrome
    $(document).ready(function() {
        $('#readTextButton').on('click', toggle_reading)
    });
</script>
    <?php
}

/**
 * Main function for displaying header. It will print HTML content.
 *
 * @param string|int $textid    ID of the required text
 * @param bool       $only_body If true, only show the inner body. If false, create a
 *                          complete HTML document.
 *
 * @since 2.0.3-fork
 */
function do_text_header_content($textid, $only_body = true): void
{
    $record = getData($textid);
    $title = (string) $record['TxTitle'];
    if (isset($record['TxAudioURI'])) {
        $media = (string) $record['TxAudioURI'];
    } else {
        $media = '';
    }
    $media = trim($media);


    saveSetting('currenttext', $textid);

    if (!$only_body) {
        pagestart_nobody($title, 'html, body {margin-bottom:0;}');
    }
    save_audio_position((int) $textid);
    do_header_row((int) $textid, $record['TxLgID']);
    do_title($title, $record['TxSourceURI']);
    do_settings($textid);
    makeMediaPlayer($media, (int) $record['TxAudioPosition']);
    browser_tts($record["TxText"], $record["LgName"]);
    if (!$only_body) {
        pageend();
    }
}

?>
<?php

/**
 * Text Reading Header View
 *
 * Variables expected:
 * - $textId: int - Text ID
 * - $langId: int - Language ID
 * - $title: string - Text title
 * - $sourceUri: string|null - Source URI
 * - $media: string - Audio URI
 * - $audioPosition: int - Audio playback position
 * - $text: string - Text content for TTS
 * - $languageName: string - Language name
 * - $showAll: int - Show all words setting (0 or 1)
 * - $showLearning: int - Show learning translations (0 or 1)
 * - $languageCode: string - BCP 47 language code
 * - $phoneticText: string - Phonetic reading of text
 * - $voiceApi: string|null - TTS voice API setting
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

?>
<script type="text/javascript">
    /**
     * Save text status, for instance audio position
     */
    function saveTextStatus() {
        const text_id = <?php echo (int) $textId; ?>;
        // Check for HTML5 audio player first (Vite mode)
        if (typeof getAudioPlayer === 'function') {
            const player = getAudioPlayer();
            if (player) {
                saveAudioPosition(text_id, player.getCurrentTime());
                return;
            }
        }
        // Fall back to jPlayer (legacy mode)
        if (typeof $ !== 'undefined' && $("#jquery_jplayer_1").length > 0) {
            const jPlayerData = $("#jquery_jplayer_1").data("jPlayer");
            if (jPlayerData && jPlayerData.status) {
                saveAudioPosition(text_id, jPlayerData.status.currentTime);
            }
        }
    }

    $(window).on('beforeunload', saveTextStatus);

    // We need to capture the text-to-speech event manually for Chrome
    $(document).ready(function() {
        $('#readTextButton').on('click', toggle_reading)
    });
</script>

<div class="flex-header">
    <div>
    <a href="/texts" target="_top">
        <?php echo \Lwt\View\Helper\PageLayoutHelper::buildLogo(); ?>
    </a>
    </div>
    <div>
        <?php
        echo getPreviousAndNextTextLinks(
            $textId,
            '/text/read?start=',
            false,
            ''
        );
        ?>
    </div>
    <div>
        <a href="/test?text=<?php echo $textId; ?>" target="_top">
            <img src="/assets/icons/question-balloon.png" title="Test" alt="Test" />
        </a>
        <a href="/text/print-plain?text=<?php echo $textId; ?>" target="_top">
            <img src="/assets/icons/printer.png" title="Print" alt="Print" />
        </a>
        <?php echo get_annotation_link($textId); ?>
        <a target="_top" href="/texts?chg=<?php echo $textId; ?>">
            <img src="/assets/icons/document--pencil.png" title="Edit Text" alt="Edit Text" />
        </a>
    </div>
    <div>
        <a
            href="/word/new?text=<?php echo $textId; ?>&amp;lang=<?php echo $langId; ?>"
            target="ro" onclick="showRightFrames();"
        >
            <img src="/assets/icons/sticky-note--plus.png" title="New Term" alt="New Term" />
        </a>
    </div>
    <div>
        <?php quickMenu(); ?>
    </div>
</div>

<h1>READ &#x25B6;
    <?php
    echo tohtml($title);
    if (isset($sourceUri) && $sourceUri !== '' && !str_starts_with(trim($sourceUri), '#')) {
        ?>
    <a href="<?php echo $sourceUri ?>" target="_blank">
        <img src="<?php echo get_file_path('assets/icons/chain.png') ?>" title="Text Source" alt="Text Source" />
    </a>
        <?php
    }
    ?>
</h1>

<div class="flex-spaced">
    <div>
        Unknown words:
        <span id="learnstatus"><?php echo todo_words_content((int) $textId); ?></span>
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
    <div id="thetextid" class="hide"><?php echo $textId; ?></div>
    <div><button id="readTextButton">Read in browser</button></div>
</div>

<?php \makeMediaPlayer($media, (int) $audioPosition); ?>

<script type="text/javascript">
    // Store PHP values for later use after Vite loads
    const _lwtPhoneticText = <?php echo json_encode($phoneticText); ?>;
    const _lwtLanguageCode = <?php echo json_encode($languageCode); ?>;
    const _lwtVoiceApi = <?php echo json_encode($voiceApi); ?>;

    /// Main object for text-to-speech interaction with SpeechSynthesisUtterance
    let text_reader = null;

    /**
     * Initialize TTS after Vite bundle is loaded.
     */
    function initTTS() {
        text_reader = {
            /// The text to read
            text: _lwtPhoneticText,

            /// {string} ISO code for the language
            lang: (typeof getLangFromDict === 'function' ? getLangFromDict(LWT_DATA.language.translator_link) : '') || _lwtLanguageCode,

            /// {string} Rate at which the speech is done, deprecated since 2.10.0
            rate: 0.8
        };

        if (typeof LWT_DATA !== 'undefined' && LWT_DATA.language) {
            LWT_DATA.language.ttsVoiceApi = _lwtVoiceApi;
        }
    }

    /**
     * Check browser compatibility before reading
     */
    function init_reading() {
        if (!('speechSynthesis' in window)) {
            alert('Your browser does not support speechSynthesis!');
            return;
        }
        if (!text_reader) {
            initTTS();
        }
        const lang = (typeof getLangFromDict === 'function' ? getLangFromDict(LWT_DATA.language.translator_link) : '') || text_reader.lang;
        if (typeof readRawTextAloud === 'function') {
            readRawTextAloud(text_reader.text, lang);
        }
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

    // Initialize TTS when Vite bundle is ready
    if (window.LWT_VITE_LOADED) {
        initTTS();
    } else {
        const checkViteTTS = setInterval(function() {
            if (window.LWT_VITE_LOADED) {
                clearInterval(checkViteTTS);
                initTTS();
            }
        }, 10);
        setTimeout(function() { clearInterval(checkViteTTS); }, 5000);
    }
</script>

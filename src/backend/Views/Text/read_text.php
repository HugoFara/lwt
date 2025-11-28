<?php

/**
 * Text Reading Content View
 *
 * Variables expected:
 * - $textId: int - Text ID
 * - $langId: int - Language ID
 * - $textTitle: string - Text title
 * - $annotatedText: string - Annotated text content
 * - $textPosition: int - Reading position
 * - $dictLink1: string - Dictionary 1 URI
 * - $dictLink2: string - Dictionary 2 URI
 * - $translatorLink: string - Google Translate URI
 * - $textSize: int - Text font size
 * - $regexpWordChars: string - Regexp word characters
 * - $removeSpaces: int - Remove spaces setting
 * - $rtlScript: bool - Right-to-left script
 * - $showAll: int - Show all words setting (0 or 1)
 * - $showLearning: int - Show learning translations (0 or 1)
 * - $modeTrans: int - Annotation position (1-4)
 * - $visitStatus: string - Visit status filter
 * - $termDelimiter: string - Term translation delimiter
 * - $tooltipMode: int - Tooltip mode (jQuery or native)
 * - $hts: string - HTS setting
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

use Lwt\Database\Settings;

// Determine annotation style
$ruby = $modeTrans == 2 || $modeTrans == 4;
$pseudoElement = ($modeTrans < 3) ? 'after' : 'before';
$dataTrans = strlen($annotatedText) > 0 ? 'data_ann' : 'data_trans';
$statArr = [1, 2, 3, 4, 5, 98, 99];
$displayStatTrans = (int)Settings::getWithDefault('set-display-text-frame-term-translation');
$annTextsize = [100 => 50, 150 => 50, 200 => 40, 250 => 25];

// Build variable array for JavaScript
$varArray = [
    'LWT_DATA' => [
        'language' => [
            'id'              => $langId,
            'dict_link1'      => $dictLink1,
            'dict_link2'      => $dictLink2,
            'translator_link' => $translatorLink,
            'delimiter'       => tohtml(
                str_replace(
                    ['\\',']','-','^'],
                    ['\\\\','\\]','\\-','\\^'],
                    $termDelimiter
                )
            ),
            'word_parsing'    => $regexpWordChars,
            'rtl'             => $rtlScript
        ],
        'text' => [
            'id'               => $textId,
            'reading_position' => $textPosition,
            'annotations'      => json_decode(annotation_to_json($annotatedText))
        ],
        'settings' => [
            'jQuery_tooltip'     => ($tooltipMode == 2 ? 1 : 0),
            'hts'                => $hts,
            'word_status_filter' => makeStatusClassFilter((int)$visitStatus),
            'annotations_mode'   => $modeTrans
        ],
    ]
];
?>
<script type="text/javascript">
    //<![CDATA[

    /// Map global variables as a JSON object
    const new_globals = <?php echo json_encode($varArray); ?>;

    /**
     * Initialize the text reading interface.
     * Called after LWT Vite bundle is loaded or immediately if not using Vite.
     */
    function initTextReading() {
        // Set global variables
        for (let key in new_globals) {
            if (typeof new_globals[key] !== 'string') {
                for (let subkey1 in new_globals[key]) {
                    if (typeof new_globals[key] !== 'string') {
                        for (let subkey2 in new_globals[key][subkey1]) {
                            window[key][subkey1][subkey2] = new_globals[key][subkey1][subkey2];
                        }
                    } else {
                        window[key][subkey1] = new_globals[key][subkey1];
                    }
                }
            } else {
                window[key] = new_globals[key];
            }
        }
        LANG = getLangFromDict(LWT_DATA.language.translator_link);
        LWT_DATA.text.reading_position = -1;
        // Note from 2.10.0: is the next line necessary on text?
        LWT_DATA.test.answer_opened = false;
        // Change the language of the current frame
        if (LANG && LANG != LWT_DATA.language.translator_link) {
            $("html").attr('lang', LANG);
        }

        if (LWT_DATA.settings.jQuery_tooltip) {
            $(function () {
                $('#overDiv').tooltip();
                $('#thetext').tooltip_wsty_init();
            });
        }

        /**
         * Save the current reading position.
         * @global {string} LWT_DATA.text.id Text ID
         *
         * @since 2.0.3-fork
         */
        function saveCurrentPosition() {
            let pos = 0;
            // First position from the top
            const top_pos = $(window).scrollTop() - $('.wsty').not('.hide').eq(0).height();
            $('.wsty').not('.hide').each(function() {
                if ($(this).offset().top >= top_pos) {
                    pos = $(this).attr('data_pos');
                    return;
                }
            });
            saveReadingPosition(LWT_DATA.text.id, pos);
        }

        $(document).ready(prepareTextInteractions);
        $(document).ready(goToLastPosition);
        $(window).on('beforeunload', saveCurrentPosition);
    }

    // Wait for Vite bundle to load before initializing
    if (window.LWT_VITE_LOADED) {
        initTextReading();
    } else {
        // Poll for Vite bundle to load (ES modules are deferred)
        const checkVite = setInterval(function() {
            if (window.LWT_VITE_LOADED) {
                clearInterval(checkVite);
                initTextReading();
            }
        }, 10);
        // Timeout after 5 seconds
        setTimeout(function() { clearInterval(checkVite); }, 5000);
    }
    //]]>
</script>

<style>
<?php if ($showLearning): ?>
    <?php foreach ($statArr as $value): ?>
        <?php if (checkStatusRange($value, $displayStatTrans)): ?>
.wsty.status<?php echo $value; ?>:<?php echo $pseudoElement; ?>,.tword.content<?php echo $value; ?>:<?php echo $pseudoElement; ?>{content: attr(<?php echo $dataTrans; ?>);}
.tword.content<?php echo $value; ?>:<?php echo $pseudoElement; ?>{color:rgba(0,0,0,0)}
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($ruby): ?>
.wsty {
    <?php echo ($modeTrans == 4 ? 'margin-top: 0.2em;' : 'margin-bottom: 0.2em;'); ?>
    text-align: center;
    display: inline-block;
    <?php echo ($modeTrans == 2 ? 'vertical-align: top;' : ''); ?>
}
.wsty:<?php echo $pseudoElement; ?> {
    display: block !important;
    <?php echo ($modeTrans == 2 ? 'margin-top: -0.05em;' : 'margin-bottom: -0.15em;'); ?>
}
<?php endif; ?>

.tword:<?php echo $pseudoElement; ?>,.wsty:<?php echo $pseudoElement; ?> {
    <?php echo ($ruby ? 'text-align: center;' : ''); ?>
    font-size:<?php echo $annTextsize[$textSize]; ?>%;
    <?php echo ($modeTrans == 1 ? 'margin-left: 0.2em;' : ''); ?>
    <?php echo ($modeTrans == 3 ? 'margin-right: 0.2em;' : ''); ?>
    <?php if (strlen($annotatedText) <= 0): ?>
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    display: inline-block;
    vertical-align: -25%;
    <?php endif; ?>
}

.hide {
    display:none !important;
}

.tword:<?php echo $pseudoElement; ?><?php echo ($ruby ? ',.word:' : ',.wsty:'); ?><?php echo $pseudoElement; ?>{max-width:15em;}
</style>

<div id="thetext" <?php echo ($rtlScript ? 'dir="rtl"' : '') ?>>
    <p style="margin-bottom: 10px;
        <?php echo $removeSpaces ? 'word-break:break-all;' : ''; ?>
        font-size: <?php echo $textSize; ?>%;
        line-height: <?php echo $ruby ? '1' : '1.4'; ?>;"
    >
        <!-- Start displaying words -->
        <?php main_word_loop($textId, $showAll); ?></span>
    </p>
    <p style="font-size:<?php echo $textSize; ?>%;line-height: 1.4; margin-bottom: 300px;">&nbsp;</p>
</div>

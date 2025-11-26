<?php

/**
 * \file
 * \brief Display an improved annotated text (text frame)
 *
 * Call: display_impr_text_text.php?text=[textid]
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/display-impr-text-text.html
 * @since    1.5.0
 */

require_once 'Core/session_utility.php';

use Lwt\Database\Settings;

/**
 * Return the annotatino of a text.
 *
 * @param int $textid Text ID
 *
 * @return string Text annotations
 */
function get_annotated_text($textid)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $ann = get_first_value(
        "SELECT TxAnnotatedText AS value
        FROM " . $tbpref . "texts
        WHERE TxID = " . $textid
    );
    return (string)$ann;
}

/**
 * Get settings for this text.
 *
 * @param int $textid Text ID
 *
 * @return (bool|int)[] Text size, and if this text is rigth-to-left.
 *
 * @psalm-return list{int, bool}
 */
function get_display_impr_text_text_data($textid): array
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $sql = "SELECT LgTextSize, LgRightToLeft
    FROM {$tbpref}texts
    JOIN {$tbpref}languages ON LgID = TxLgID
    WHERE TxID = $textid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $textsize = (int) $record['LgTextSize'];
    $rtlScript = (bool) $record['LgRightToLeft'];
    mysqli_free_result($res);

    return array($textsize, $rtlScript);
}


/**
 * Prepare JavaScript interactions for the text content.
 *
 * @return void
 */
function do_diplay_impr_text_text_js()
{
    ?>
<script type="text/javascript">
    //<![CDATA[

    /** When user clicks an annotation. */
    function click_ann() {
        const attr = $(this).attr('style');
        if(attr !== undefined && attr !== false && attr !== '') {
            $(this).removeAttr('style');
        }
        else {
            $(this).css('color', '#C8DCF0');
            $(this).css('background-color', '#C8DCF0');
        }
    }

    /** When user clicks the text. */
    function click_text() {
        const bc = $('body').css('color');
        if ($(this).css('color') != bc) {
            $(this).css('color', 'inherit');
            $(this).css('background-color', '');
        } else {
            $(this).css('color','#E5E4E2');
            $(this).css('background-color', '#E5E4E2');
        }
    }

    $(document).ready(function(){
        $('.anntransruby2').on('click', click_ann);
        $('.anntermruby').on('click', click_text);
    });
    //]]>
</script>

    <?php
}

/**
 * Make the main content for a printed text.
 *
 * @param string   $ann       Annotations separated b tabulations "\t"
 * @param int|string $textsize  Text size
 * @param bool     $rtlScript True if this text is right-to-left
 *
 * @return void
 */
function do_diplay_impr_text_text_area($ann, $textsize, $rtlScript)
{
    echo '<div id="print"' . ($rtlScript ? ' dir="rtl"' : '') . '>';
    echo '<p style="font-size:' . $textsize . '%;line-height: 1.35; margin-bottom: 10px; ">';

    $items = preg_split('/[\n]/u', $ann);
    foreach ($items as $item) {
        do_display_impr_text_text_word($item, $textsize);
    }
    echo "</p></div>";
}

/**
 * Parse the annotations (translation/romanization) and return them.
 *
 * @param string[] $vals Annotations values
 *
 * @return array{0: string, 1: string} Translation and romanization.
 *
 * @global string $tbpref Database table prefix.
 */
function get_word_annotations($vals)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $trans = '';
    $c = count($vals);
    $rom = '';
    if ($c > 2) {
        if ($vals[2] !== '') {
            $wid = (int)$vals[2];
            $rom = get_first_value(
                "SELECT WoRomanization AS value
                FROM " . $tbpref . "words WHERE WoID = " . $wid
            );
            if (!isset($rom)) {
                $rom = '';
            }
        }
    }
    if ($c > 3) {
        $trans = $vals[3];
    }
    if ($trans == '*') {
        $trans = $vals[1] . " "; // <- U+200A HAIR SPACE
    }
    return array($trans, $rom);
}

/**
 * Display a single word item.
 *
 * @param string $item     Word item, values separated by a tabulation.
 * @param string $textsize Text size
 *
 * @return void
 */
function do_display_impr_text_text_word($item, $textsize)
{
    $vals = preg_split('/[\t]/u', $item);
    if ((int)$vals[0] > -1) {
        list($trans, $rom) = get_word_annotations($vals);
        echo ' <ruby>
            <rb>
                <span class="click anntermruby" style="color:black;"' .
                ($rom == '' ? '' : (' title="' . tohtml($rom) . '"')) . '>' .
                    tohtml($vals[1]) .
                '</span>
            </rb>
            <rt>
                <span class="click anntransruby2">' . tohtml($trans) . '</span>
            </rt>
        </ruby> ';
    } elseif (count($vals) >= 2) {
        echo str_replace(
            "¶",
            '</p>
            <p style="font-size:' . $textsize .
            '%;line-height: 1.3; margin-bottom: 10px;">',
            " " . tohtml($vals[1])
        );
    }
}

/**
 * Main function to do a complete printed text text content.
 *
 * @param int|null $textid Text ID, we will use page request if not provided.
 *
 * @return void
 */
function do_display_impr_text_text_main($textid = null)
{
    if ($textid === null) {
        $textid = (int)getreq('text');
    }
    do_diplay_impr_text_text_js();
    $ann = get_annotated_text($textid);
    if ($textid == 0 || strlen($ann) <= 0) {
        header("Location: /text/edit");
        exit();
    }
    list($textsize, $rtlScript) = get_display_impr_text_text_data($textid);
    Settings::save('currenttext', $textid);
    do_diplay_impr_text_text_area($ann, $textsize, $rtlScript);
}

?>

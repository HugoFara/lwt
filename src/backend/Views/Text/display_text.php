<?php

/**
 * Text Display Content View
 *
 * Variables expected:
 * - $annotations: array - Parsed annotation items
 * - $textSize: int - Text size percentage
 * - $rtlScript: bool - Whether text is right-to-left
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress UndefinedGlobalVariable Variables are injected by including file
 */

// Variables injected from text_display_text.php:
// $annotations, $textSize, $rtlScript

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

<div id="print"<?php echo ($rtlScript ? ' dir="rtl"' : ''); ?>>
<p style="font-size:<?php echo $textSize; ?>%;line-height: 1.35; margin-bottom: 10px; ">
<?php
foreach ($annotations as $item) {
    if ($item['type'] > -1) {
        // Regular word with annotation
        echo ' <ruby>
            <rb>
                <span class="click anntermruby" style="color:black;"' .
                ($item['rom'] === '' ? '' : (' title="' . tohtml($item['rom']) . '"')) . '>' .
                    tohtml($item['text']) .
                '</span>
            </rb>
            <rt>
                <span class="click anntransruby2">' . tohtml($item['trans']) . '</span>
            </rt>
        </ruby> ';
    } else {
        // Punctuation or paragraph marker
        echo str_replace(
            "¶",
            '</p>
            <p style="font-size:' . $textSize .
            '%;line-height: 1.3; margin-bottom: 10px;">',
            " " . tohtml($item['text'])
        );
    }
}
?>
</p>
</div>

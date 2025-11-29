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
// JavaScript moved to reading/annotation_interactions.ts

?>
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

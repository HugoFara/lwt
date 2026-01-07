<?php declare(strict_types=1);
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

namespace Lwt\Views\Text;

// Type assertions for view variables
/** @var array<int, array{type: int, text?: string, rom?: string, trans?: string}> $annotations */
$annotations = $annotations ?? [];
$textSize = (int) ($textSize ?? 100);

// JavaScript moved to reading/annotation_interactions.ts

?>
<div id="print"<?php echo ($rtlScript ? ' dir="rtl"' : ''); ?>>
<p style="font-size:<?php echo $textSize; ?>%;line-height: 1.35; margin-bottom: 10px; ">
<?php
foreach ($annotations as $item) {
    $itemRom = $item['rom'] ?? '';
    $itemText = $item['text'] ?? '';
    $itemTrans = $item['trans'] ?? '';
    if ($item['type'] > -1) {
        // Regular word with annotation
        echo ' <ruby>
            <rb>
                <span class="click anntermruby" style="color:black;"' .
                ($itemRom === '' ? '' : (' title="' . \htmlspecialchars($itemRom, ENT_QUOTES, 'UTF-8') . '"')) . '>' .
                    \htmlspecialchars($itemText, ENT_QUOTES, 'UTF-8') .
                '</span>
            </rb>
            <rt>
                <span class="click anntransruby2">' . \htmlspecialchars($itemTrans, ENT_QUOTES, 'UTF-8') . '</span>
            </rt>
        </ruby> ';
    } else {
        // Punctuation or paragraph marker
        echo str_replace(
            "¶",
            '</p>
            <p style="font-size:' . $textSize .
            '%;line-height: 1.3; margin-bottom: 10px;">',
            " " . \htmlspecialchars($itemText, ENT_QUOTES, 'UTF-8')
        );
    }
}
?>
</p>
</div>

<?php declare(strict_types=1);
/**
 * Annotated text display/print view.
 *
 * Variables expected:
 * - $textId: int - Text ID
 * - $viewData: array - View data with title, sourceUri, audioUri, textSize, rtlScript, ttsClass
 * - $ann: string - Annotation string
 *
 * @category User_Interface
 * @package  Lwt
 */

namespace Lwt\Views\TextPrint;

use Lwt\View\Helper\IconHelper;

$title = $viewData['title'];
$sourceUri = $viewData['sourceUri'];
$audioUri = $viewData['audioUri'];
$textSize = $viewData['textSize'];
$rtlScript = $viewData['rtlScript'];
$ttsClass = $viewData['ttsClass'] ?? '';
?>
<h1>ANN.TEXT &#9654; <?php echo htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8');
if (isset($sourceUri) && substr(trim($sourceUri), 0, 1) != '#') {
    echo ' <a href="' . $sourceUri . '" target="_blank">' .
         IconHelper::render('link', ['title' => 'Text Source', 'alt' => 'Text Source']) . '</a>';
}
?></h1>

<div id="printoptions" data-text-id="<?php echo $textId; ?>">
    <h2>Improved Annotated Text (Display/Print Mode)</h2>
    <div class="flex-spaced">
        <button type="button" data-action="navigate" data-url="/text/print?edit=1&amp;text=<?php echo $textId; ?>">Edit</button>
        <button type="button" data-action="confirm-navigate" data-url="/text/print?del=1&amp;text=<?php echo $textId; ?>" data-confirm="Are you sure?">Delete</button>
        <button type="button" data-action="print">Print</button>
        <button type="button" data-action="open-window" data-url="/text/display?text=<?php echo $textId; ?>">Display <?php echo (($audioUri !== '') ? ' with Audio Player' : ''); ?> in new Window</button>
    </div>
</div>
<!-- noprint -->
<div id="print"<?php echo ($rtlScript ? ' dir="rtl"' : ''); ?>>
    <p style="font-size:<?php echo $textSize; ?>%;line-height: 1.35; margin-bottom: 10px; ">
        <?php echo htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8'); ?>
        <br /><br />
    <?php
    $items = preg_split('/[\n]/u', $ann);

    foreach ($items as $item) {
        $vals = preg_split('/[\t]/u', $item);
        if ($vals[0] > -1) {
            $trans = '';
            if (count($vals) > 3) {
                $trans = $vals[3];
            }
            if ($trans === '*') {
                // U+200A HAIR SPACE
                $trans = $vals[1] . " ";
            }
            echo ' <ruby>
                <rb>
                    <span class="' . $ttsClass . 'anntermruby">' .
                        htmlspecialchars($vals[1] ?? '', ENT_QUOTES, 'UTF-8') .
                    '</span>
                </rb>
                <rt>
                    <span class="anntransruby2">' . htmlspecialchars($trans ?? '', ENT_QUOTES, 'UTF-8') . '</span>
                </rt>
            </ruby> ';
        } elseif (count($vals) >= 2) {
            echo str_replace(
                "¶",
                '</p><p style="font-size:' . $textSize . '%;line-height: 1.3; margin-bottom: 10px;">',
                " " . htmlspecialchars($vals[1] ?? '', ENT_QUOTES, 'UTF-8') . " "
            );
        }
    }
    ?>
    </p>
</div>

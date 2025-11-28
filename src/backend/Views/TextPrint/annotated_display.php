<?php
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

$title = $viewData['title'];
$sourceUri = $viewData['sourceUri'];
$audioUri = $viewData['audioUri'];
$textSize = $viewData['textSize'];
$rtlScript = $viewData['rtlScript'];
$ttsClass = $viewData['ttsClass'] ?? '';
?>
<h1>ANN.TEXT &#9654; <?php echo tohtml($title);
if (isset($sourceUri) && substr(trim($sourceUri), 0, 1) != '#') {
    echo ' <a href="' . $sourceUri . '" target="_blank">' .
         '<img src="' . get_file_path('assets/icons/chain.png') . '" title="Text Source" alt="Text Source" /></a>';
}
?></h1>

<div id="printoptions">
    <h2>Improved Annotated Text (Display/Print Mode)</h2>
    <div class="flex-spaced">
        <input type="button" value="Edit"
        onclick="location.href='/text/print?edit=1&amp;text=<?php echo $textId; ?>';" />
        <input type="button" value="Delete"
        onclick="if (confirm ('Are you sure?')) location.href='/text/print?del=1&amp;text=<?php echo $textId; ?>';" />
        <input type="button" value="Print" onclick="window.print();" />
        <input type="button"
        value="Display <?php echo (($audioUri !== '') ? ' with Audio Player' : ''); ?> in new Window"
        onclick="window.open('/text/display?text=<?php echo $textId; ?>');" />
    </div>
</div>
<!-- noprint -->
<div id="print"<?php echo ($rtlScript ? ' dir="rtl"' : ''); ?>>
    <p style="font-size:<?php echo $textSize; ?>%;line-height: 1.35; margin-bottom: 10px; ">
        <?php echo tohtml($title); ?>
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
                        tohtml($vals[1]) .
                    '</span>
                </rb>
                <rt>
                    <span class="anntransruby2">' . tohtml($trans) . '</span>
                </rt>
            </ruby> ';
        } elseif (count($vals) >= 2) {
            echo str_replace(
                "¶",
                '</p><p style="font-size:' . $textSize . '%;line-height: 1.3; margin-bottom: 10px;">',
                " " . tohtml($vals[1]) . " "
            );
        }
    }
    ?>
    </p>
</div>

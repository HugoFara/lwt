<?php
/**
 * Plain text print view.
 *
 * Variables expected:
 * - $textId: int - Text ID
 * - $viewData: array - View data with title, sourceUri, langId, textSize, rtlScript, hasAnnotation
 * - $ann: int - Annotation flags
 * - $statusRange: int - Status range
 * - $annPlacement: int - Annotation placement
 * - $showRom: bool - Show romanization
 * - $showTrans: bool - Show translation
 * - $showTags: bool - Show tags
 * - $textItems: array - Array of text items
 * - $this: TextPrintController - Controller instance
 *
 * @category User_Interface
 * @package  Lwt
 */

use Lwt\Services\TextPrintService;

$title = $viewData['title'];
$sourceUri = $viewData['sourceUri'];
$textSize = $viewData['textSize'];
$rtlScript = $viewData['rtlScript'];
$hasAnnotation = $viewData['hasAnnotation'];
?>
<div class="noprint">
<div class="flex-header">
    <div>
        <?php echo_lwt_logo(); ?>
    </div>
    <div>
        <?php echo getPreviousAndNextTextLinks($textId, '/text/print-plain?text=', false, ''); ?>
    </div>
    <div>
        <a href="/text/read?start=<?php echo $textId; ?>" target="_top">
            <img src="/assets/icons/book-open-bookmark.png" title="Read" alt="Read" />
        </a>
        <a href="/test?text=<?php echo $textId; ?>" target="_top">
            <img src="/assets/icons/question-balloon.png" title="Test" alt="Test" />
        </a>
        <?php echo get_annotation_link($textId); ?>
        <a target="_top" href="/texts?chg=<?php echo $textId; ?>">
            <img src="/assets/icons/document--pencil.png" title="Edit Text" alt="Edit Text" />
        </a>
    </div>
    <div>
        <?php quickMenu(); ?>
    </div>
</div>
<h1>PRINT &#9654; <?php
echo tohtml($title);
if (isset($sourceUri) && substr(trim($sourceUri), 0, 1) != '#') {
    echo ' <a href="' . $sourceUri . '" target="_blank">' .
         '<img src="' . get_file_path('assets/icons/chain.png') . '" title="Text Source" alt="Text Source" /></a>';
}
?></h1>
<p id="printoptions">
    Terms with <b>status(es)</b>
    <select id="status" onchange="{val=document.getElementById('status').options[document.getElementById('status').selectedIndex].value;location.href='/text/print-plain?text=<?php echo $textId; ?>&amp;status=' + val;}">
        <?php echo get_wordstatus_selectoptions($statusRange, true, true, false); ?>
    </select> ...<br />
    will be <b>annotated</b> with
    <select id="ann" onchange="{val=document.getElementById('ann').options[document.getElementById('ann').selectedIndex].value;location.href='/text/print-plain?text=<?php echo $textId; ?>&amp;ann=' + val;}">
        <option value="0"<?php echo get_selected(0, $ann); ?>>Nothing</option>
        <option value="1"<?php echo get_selected(1, $ann); ?>>Translation</option>
        <option value="5"<?php echo get_selected(5, $ann); ?>>Translation &amp; Tags</option>
        <option value="2"<?php echo get_selected(2, $ann); ?>>Romanization</option>
        <option value="3"<?php echo get_selected(3, $ann); ?>>Romanization &amp; Translation</option>
        <option value="7"<?php echo get_selected(7, $ann); ?>>Romanization, Translation &amp; Tags</option>
    </select>
    <select id="annplcmnt" onchange="{val=document.getElementById('annplcmnt').options[document.getElementById('annplcmnt').selectedIndex].value;location.href='/text/print-plain?text=<?php echo $textId; ?>&amp;annplcmnt=' + val;}">
        <option value="0"<?php echo get_selected(0, $annPlacement); ?>>behind</option>
        <option value="1"<?php echo get_selected(1, $annPlacement); ?>>in front of</option>
        <option value="2"<?php echo get_selected(2, $annPlacement); ?>>above (ruby)</option>
    </select> the term.<br />
    <input type="button" value="Print it!" onclick="window.print();" />
    (only the text below the line)
    <span class="nowrap"></span>
    <?php if ($hasAnnotation): ?>
        Or <input type="button" value="Print/Edit/Delete"
        onclick="location.href='/text/print?text=<?php echo $textId; ?>';" /> your
        <b>Improved Annotated Text</b> <?php echo get_annotation_link($textId); ?>.
    <?php else: ?>
        <input type="button" value="Create"
        onclick="location.href='/text/print?edit=1&amp;text=<?php echo $textId; ?>';" /> an
        <b>Improved Annotated Text</b> [<img src="/assets/icons/tick.png" title="Annotated Text" alt="Annotated Text" />].
    <?php endif; ?>
</p>
</div>
<!-- noprint -->
<div id="print" <?php echo ($rtlScript ? 'dir="rtl"' : ''); ?>>
<h2><?php echo tohtml($title); ?></h2>
<p style="font-size: <?php echo $textSize; ?>%; line-height: 1.35; margin-bottom: 10px; ">
<?php
// Process and output text items
$saveTerm = '';
$saveTrans = '';
$saveRom = '';
$saveTags = '';
$until = 0;

foreach ($textItems as $record) {
    $actCode = (int) $record['Code'];
    $order = (int) $record['Ti2Order'];

    if ($order <= $until) {
        continue;
    }
    if ($order > $until) {
        // Output previous term if any
        if ($saveTerm !== '') {
            echo $this->formatTermOutput(
                $saveTerm,
                $saveRom,
                $saveTrans,
                $saveTags,
                (bool) $showRom,
                (bool) $showTrans,
                (bool) $showTags,
                $annPlacement
            );
        }
        $saveTerm = '';
        $saveTrans = '';
        $saveRom = '';
        $saveTags = '';
        $until = $order;
    }

    if ($record['TiIsNotWord'] != 0) {
        // Non-word item (punctuation, etc.)
        echo str_replace(
            "¶",
            '</p><p style="font-size:' . $textSize . '%;line-height: 1.3; margin-bottom: 10px;">',
            tohtml($record['TiText'])
        );
    } else {
        // Word item
        $until = $order + 2 * ($actCode - 1);
        $saveTerm = $record['TiText'];
        $saveTrans = '';
        $saveTags = '';
        $saveRom = '';

        if (isset($record['WoID'])) {
            if ($this->getPrintService()->checkStatusInRange((int) $record['WoStatus'], $statusRange)) {
                $saveTrans = $record['WoTranslation'];
                $saveTags = $this->getPrintService()->getWordTags((int) $record['WoID']);
                if ($saveTrans === '*') {
                    $saveTrans = '';
                }
                $saveRom = trim((string) $record['WoRomanization']);
            }
        }
    }
}

// Output final term if any
if ($saveTerm !== '') {
    echo $this->formatTermOutput(
        $saveTerm,
        $saveRom,
        $saveTrans,
        $saveTags,
        (bool) $showRom,
        (bool) $showTrans,
        (bool) $showTags,
        $annPlacement
    );
}
?>
</p>
</div>

<?php declare(strict_types=1);
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

namespace Lwt\Views\TextPrint;

use Lwt\Services\TextPrintService;
use Lwt\View\Helper\SelectOptionsBuilder;
use Lwt\View\Helper\FormHelper;

$title = $viewData['title'];
$sourceUri = $viewData['sourceUri'];
$textSize = $viewData['textSize'];
$rtlScript = $viewData['rtlScript'];
$hasAnnotation = $viewData['hasAnnotation'];
?>
<div class="noprint">
<div class="flex-header">
    <div>
        <?php echo \Lwt\View\Helper\PageLayoutHelper::buildLogo(); ?>
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
        <?php echo \Lwt\View\Helper\PageLayoutHelper::buildQuickMenu(); ?>
    </div>
</div>
<h1>PRINT &#9654; <?php
echo htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8');
if (isset($sourceUri) && substr(trim($sourceUri), 0, 1) != '#') {
    echo ' <a href="' . $sourceUri . '" target="_blank">' .
         '<img src="' . get_file_path('assets/icons/chain.png') . '" title="Text Source" alt="Text Source" /></a>';
}
?></h1>
<p id="printoptions" data-text-id="<?php echo $textId; ?>">
    Terms with <b>status(es)</b>
    <select id="status" data-action="filter-status">
        <?php echo SelectOptionsBuilder::forWordStatus($statusRange, true, true, false); ?>
    </select> ...<br />
    will be <b>annotated</b> with
    <select id="ann" data-action="filter-annotation">
        <option value="0"<?php echo FormHelper::getSelected(0, $ann); ?>>Nothing</option>
        <option value="1"<?php echo FormHelper::getSelected(1, $ann); ?>>Translation</option>
        <option value="5"<?php echo FormHelper::getSelected(5, $ann); ?>>Translation &amp; Tags</option>
        <option value="2"<?php echo FormHelper::getSelected(2, $ann); ?>>Romanization</option>
        <option value="3"<?php echo FormHelper::getSelected(3, $ann); ?>>Romanization &amp; Translation</option>
        <option value="7"<?php echo FormHelper::getSelected(7, $ann); ?>>Romanization, Translation &amp; Tags</option>
    </select>
    <select id="annplcmnt" data-action="filter-placement">
        <option value="0"<?php echo FormHelper::getSelected(0, $annPlacement); ?>>behind</option>
        <option value="1"<?php echo FormHelper::getSelected(1, $annPlacement); ?>>in front of</option>
        <option value="2"<?php echo FormHelper::getSelected(2, $annPlacement); ?>>above (ruby)</option>
    </select> the term.<br />
    <button type="button" data-action="print">Print it!</button>
    (only the text below the line)
    <span class="nowrap"></span>
    <?php if ($hasAnnotation): ?>
        Or <button type="button" data-action="navigate" data-url="/text/print?text=<?php echo $textId; ?>">Print/Edit/Delete</button> your
        <b>Improved Annotated Text</b> <?php echo get_annotation_link($textId); ?>.
    <?php else: ?>
        <button type="button" data-action="navigate" data-url="/text/print?edit=1&amp;text=<?php echo $textId; ?>">Create</button> an
        <b>Improved Annotated Text</b> [<img src="/assets/icons/tick.png" title="Annotated Text" alt="Annotated Text" />].
    <?php endif; ?>
</p>
</div>
<!-- noprint -->
<div id="print" <?php echo ($rtlScript ? 'dir="rtl"' : ''); ?>>
<h2><?php echo htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8'); ?></h2>
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
            htmlspecialchars($record['TiText'] ?? '', ENT_QUOTES, 'UTF-8')
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

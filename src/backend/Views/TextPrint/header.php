<?php
/**
 * Header view for text print pages.
 *
 * Variables expected:
 * - $textId: int - Text ID
 * - $viewData: array - View data with title, sourceUri, hasAnnotation
 * - $printUrl: string - URL for print navigation
 * - $showImprov: bool - Show improved annotation link
 *
 * @category User_Interface
 * @package  Lwt
 */
?>
<div class="noprint">
    <div class="flex-header">
        <div>
            <?php echo_lwt_logo(); ?>
        </div>
        <div>
            <?php echo getPreviousAndNextTextLinks($textId, $printUrl, false, ''); ?>
        </div>
        <div>
            <a href="/text/read?start=<?php echo $textId; ?>" target="_top">
                <img src="/assets/icons/book-open-bookmark.png" title="Read" alt="Read" />
            </a>
            <a href="/test?text=<?php echo $textId; ?>" target="_top">
                <img src="/assets/icons/question-balloon.png" title="Test" alt="Test" />
            </a>
            <?php if ($showImprov): ?>
                <?php echo get_annotation_link($textId); ?>
            <?php endif; ?>
            <a target="_top" href="/texts?chg=<?php echo $textId; ?>">
                <img src="/assets/icons/document--pencil.png" title="Edit Text" alt="Edit Text" />
            </a>
        </div>
        <div>
            <?php quickMenu(); ?>
        </div>
    </div>

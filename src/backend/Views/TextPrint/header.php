<?php declare(strict_types=1);
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

namespace Lwt\Views\TextPrint;
?>
<div class="noprint">
    <div class="flex-header">
        <div>
            <?php echo \Lwt\View\Helper\PageLayoutHelper::buildLogo(); ?>
        </div>
        <div>
            <?php echo getPreviousAndNextTextLinks($textId, $printUrl, false, ''); ?>
        </div>
        <div>
            <a href="/text/read?start=<?php echo $textId; ?>" target="_top">
                <?php echo \Lwt\View\Helper\IconHelper::render('book-open', ['title' => 'Read', 'alt' => 'Read']); ?>
            </a>
            <a href="/test?text=<?php echo $textId; ?>" target="_top">
                <?php echo \Lwt\View\Helper\IconHelper::render('circle-help', ['title' => 'Test', 'alt' => 'Test']); ?>
            </a>
            <?php if ($showImprov): ?>
                <?php echo get_annotation_link($textId); ?>
            <?php endif; ?>
            <a target="_top" href="/texts?chg=<?php echo $textId; ?>">
                <?php echo \Lwt\View\Helper\IconHelper::render('file-pen', ['title' => 'Edit Text', 'alt' => 'Edit Text']); ?>
            </a>
        </div>
        <div>
            <?php echo \Lwt\View\Helper\PageLayoutHelper::buildQuickMenu(); ?>
        </div>
    </div>

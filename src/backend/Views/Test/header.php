<?php declare(strict_types=1);
/**
 * Test Header Navigation Row View
 *
 * Variables expected:
 * - $textId: int|null - Text ID if testing from a text
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Test;

use Lwt\View\Helper\PageLayoutHelper;

?>
<div class="flex-header">
    <div>
        <a href="/texts" target="_top">
            <?php echo PageLayoutHelper::buildLogo(); ?>
        </a>
    </div>
    <?php if ($textId !== null): ?>
    <div>
        <?php
        echo \getPreviousAndNextTextLinks(
            $textId,
            '/test?text=',
            false,
            ''
        );
        ?>
    </div>
    <div>
        <a href="/text/read?start=<?php echo $textId; ?>" target="_top">
            <img src="/assets/icons/book-open-bookmark.png" title="Read" alt="Read" />
        </a>
        <a href="/text/print-plain?text=<?php echo $textId; ?>" target="_top">
            <img src="/assets/icons/printer.png" title="Print" alt="Print" />
        </a>
        <?php echo \get_annotation_link($textId); ?>
    </div>
    <?php endif; ?>
    <div>
        <?php echo PageLayoutHelper::buildQuickMenu(); ?>
    </div>
</div>

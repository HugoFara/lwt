<?php

declare(strict_types=1);

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
 *
 * @var int|null $textId
 */

namespace Lwt\Views\Review;

use Lwt\Shared\UI\Helpers\PageLayoutHelper;

/** @var int|null $textId */
assert(is_string($navLinksHtml));
assert(is_string($annotationLinkHtml));

?>
<div class="flex-header">
    <div>
        <a href="/texts" target="_top">
            <?php echo PageLayoutHelper::buildLogo(); ?>
        </a>
    </div>
    <?php if ($textId !== null) : ?>
    <div>
        <?php echo $navLinksHtml; ?>
    </div>
    <div>
        <a href="/text/<?php echo $textId; ?>/read" target="_top">
            <?php echo \Lwt\Shared\UI\Helpers\IconHelper::render('book-open', ['title' => 'Read', 'alt' => 'Read']); ?>
        </a>
        <a href="/text/<?php echo $textId; ?>/print-plain" target="_top">
            <?php echo \Lwt\Shared\UI\Helpers\IconHelper::render('printer', ['title' => 'Print', 'alt' => 'Print']); ?>
        </a>
        <?php echo $annotationLinkHtml; ?>
    </div>
    <?php endif; ?>
    <div>
        <?php echo PageLayoutHelper::buildNavbar(); ?>
    </div>
</div>

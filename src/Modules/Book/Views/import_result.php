<?php

/**
 * EPUB Import Result View
 *
 * Variables expected:
 * - $message: string - Result message
 * - $messageType: string - Bulma notification class (is-success, is-danger, etc.)
 * - $bookId: int|null - Book ID if successful
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Book\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Views\Book;

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

?>

<h2 class="title is-4"><?php echo __('book.import_result_title'); ?></h2>

<div class="notification <?php echo $messageType; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>

<div class="buttons">
    <?php if ($bookId !== null) : ?>
    <a href="/book/<?php echo $bookId; ?>" class="button is-primary">
        <?php echo IconHelper::render('book', ['alt' => __('book.view_book')]); ?>
        <span class="ml-2"><?php echo __('book.view_book'); ?></span>
    </a>
    <?php endif; ?>

    <a href="/texts/new" class="button is-info is-outlined">
        <?php echo IconHelper::render('upload', ['alt' => __('book.import_another_epub')]); ?>
        <span class="ml-2"><?php echo __('book.import_another_epub'); ?></span>
    </a>

    <a href="/books" class="button is-light">
        <?php echo IconHelper::render('library', ['alt' => __('book.all_books')]); ?>
        <span class="ml-2"><?php echo __('book.all_books'); ?></span>
    </a>
</div>

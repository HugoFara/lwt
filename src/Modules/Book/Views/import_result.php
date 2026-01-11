<?php

declare(strict_types=1);

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
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

namespace Lwt\Views\Book;

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

?>

<h2 class="title is-4">Import Result</h2>

<div class="notification <?php echo $messageType; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>

<div class="buttons">
    <?php if ($bookId !== null) : ?>
    <a href="/book/<?php echo $bookId; ?>" class="button is-primary">
        <?php echo IconHelper::render('book', ['alt' => 'View Book']); ?>
        <span class="ml-2">View Book</span>
    </a>
    <?php endif; ?>

    <a href="/book/import" class="button is-info is-outlined">
        <?php echo IconHelper::render('upload', ['alt' => 'Import Another']); ?>
        <span class="ml-2">Import Another EPUB</span>
    </a>

    <a href="/books" class="button is-light">
        <?php echo IconHelper::render('library', ['alt' => 'All Books']); ?>
        <span class="ml-2">All Books</span>
    </a>
</div>

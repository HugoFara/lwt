<?php

/**
 * Book Detail View
 *
 * Variables expected:
 * - $book: array - Book data
 * - $chapters: array - Array of chapter info
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
use Lwt\Shared\UI\Helpers\FormHelper;

$actions = [
    ['url' => '/books', 'label' => __('book.all_books'), 'icon' => 'library'],
    ['url' => '/book/import', 'label' => __('book.import_epub'), 'icon' => 'file-up'],
];

?>

<h2 class="title is-4">
    <?php echo htmlspecialchars($book['title']); ?>
</h2>

<?php echo PageLayoutHelper::buildActionCard($actions); ?>

<div class="box">
    <div class="columns">
        <div class="column is-8">
            <!-- Book Info -->
            <div class="content">
                <?php if ($book['author']) : ?>
                <p><strong><?php echo __('common.author'); ?>:</strong>
                    <?php echo htmlspecialchars($book['author']); ?></p>
                <?php endif; ?>

                <?php if ($book['description']) : ?>
                <p><strong><?php echo __('common.description'); ?>:</strong>
                    <?php echo htmlspecialchars($book['description']); ?></p>
                <?php endif; ?>

                <p>
                    <strong><?php echo __('book.source'); ?>:</strong>
                    <span class="tag is-info"><?php echo strtoupper($book['sourceType']); ?></span>
                </p>

                <p>
                    <strong><?php echo __('book.col_progress'); ?>:</strong>
                    <?php
                    echo htmlspecialchars(
                        __('book.chapter_x_of_y', [
                            'current' => $book['currentChapter'],
                            'total' => $book['totalChapters'],
                        ]),
                        ENT_QUOTES
                    );
                    ?>
                    (<?php echo round($book['progress'], 1); ?>%)
                </p>

                <progress class="progress is-primary" value="<?php echo $book['progress']; ?>" max="100">
                    <?php echo round($book['progress'], 1); ?>%
                </progress>
            </div>

            <!-- Continue Reading Button -->
            <?php if (!empty($chapters)) : ?>
            <a href="/text/<?php echo $chapters[0]['id']; ?>/read" class="button is-primary is-medium">
                <?php echo IconHelper::render('book-open', ['alt' => __('book.continue_reading')]); ?>
                <span class="ml-2"><?php echo __('book.continue_reading'); ?></span>
            </a>
            <?php endif; ?>
        </div>

        <div class="column is-4">
            <!-- Actions -->
            <div class="buttons">
                <?php $confirmDelete = htmlspecialchars(__('book.confirm_delete_book'), ENT_QUOTES); ?>
                <form method="post" action="/book/<?php echo $book['id']; ?>/delete"
                      @submit="if(!confirm('<?php echo $confirmDelete; ?>')) $event.preventDefault()">
                    <?php echo FormHelper::csrfField(); ?>
                    <button type="submit" class="button is-danger is-outlined">
                        <?php echo IconHelper::render('trash-2', ['alt' => __('common.delete')]); ?>
                        <span class="ml-2"><?php echo __('book.delete_book'); ?></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Chapters List -->
<div class="box">
    <h3 class="title is-5"><?php echo __('book.chapters'); ?></h3>

    <?php if (empty($chapters)) : ?>
    <p class="has-text-grey"><?php echo __('book.no_chapters_found'); ?></p>
    <?php else : ?>
    <table class="table is-fullwidth is-hoverable">
        <thead>
            <tr>
                <th style="width: 60px;">#</th>
                <th><?php echo __('common.title'); ?></th>
                <th style="width: 100px;"><?php echo __('common.actions'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($chapters as $chapter) : ?>
            <tr class="<?php echo $chapter['num'] === $book['currentChapter'] ? 'is-selected' : ''; ?>">
                <td><?php echo $chapter['num']; ?></td>
                <td>
                    <a href="/text/<?php echo $chapter['id']; ?>/read">
                        <?php echo htmlspecialchars($chapter['title']); ?>
                    </a>
                    <?php if ($chapter['num'] === $book['currentChapter']) : ?>
                    <span class="tag is-small is-info ml-2"><?php echo __('common.current'); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/text/<?php echo $chapter['id']; ?>/read" class="button is-small is-primary">
                        <?php echo IconHelper::render('book-open', ['alt' => __('common.read')]); ?>
                    </a>
                    <a href="/texts/<?php echo $chapter['id']; ?>/edit" class="button is-small is-light">
                        <?php echo IconHelper::render('edit', ['alt' => __('common.edit')]); ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

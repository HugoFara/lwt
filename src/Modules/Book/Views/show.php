<?php

declare(strict_types=1);

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
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Book;

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\FormHelper;

$actions = [
    ['url' => '/books', 'label' => 'All Books', 'icon' => 'library'],
    ['url' => '/book/import', 'label' => 'Import EPUB', 'icon' => 'file-up'],
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
                <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                <?php endif; ?>

                <?php if ($book['description']) : ?>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($book['description']); ?></p>
                <?php endif; ?>

                <p>
                    <strong>Source:</strong>
                    <span class="tag is-info"><?php echo strtoupper($book['sourceType']); ?></span>
                </p>

                <p>
                    <strong>Progress:</strong>
                    Chapter <?php echo $book['currentChapter']; ?> of <?php echo $book['totalChapters']; ?>
                    (<?php echo round($book['progress'], 1); ?>%)
                </p>

                <progress class="progress is-primary" value="<?php echo $book['progress']; ?>" max="100">
                    <?php echo round($book['progress'], 1); ?>%
                </progress>
            </div>

            <!-- Continue Reading Button -->
            <?php if (!empty($chapters)) : ?>
            <a href="/text/<?php echo $chapters[0]['id']; ?>/read" class="button is-primary is-medium">
                <?php echo IconHelper::render('book-open', ['alt' => 'Continue']); ?>
                <span class="ml-2">Continue Reading</span>
            </a>
            <?php endif; ?>
        </div>

        <div class="column is-4">
            <!-- Actions -->
            <div class="buttons">
                <form method="post" action="/book/<?php echo $book['id']; ?>/delete"
                      onsubmit="return confirm('Delete this book and all its chapters?');">
                    <?php echo FormHelper::csrfField(); ?>
                    <button type="submit" class="button is-danger is-outlined">
                        <?php echo IconHelper::render('trash-2', ['alt' => 'Delete']); ?>
                        <span class="ml-2">Delete Book</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Chapters List -->
<div class="box">
    <h3 class="title is-5">Chapters</h3>

    <?php if (empty($chapters)) : ?>
    <p class="has-text-grey">No chapters found.</p>
    <?php else : ?>
    <table class="table is-fullwidth is-hoverable">
        <thead>
            <tr>
                <th style="width: 60px;">#</th>
                <th>Title</th>
                <th style="width: 100px;">Actions</th>
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
                    <span class="tag is-small is-info ml-2">Current</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/text/<?php echo $chapter['id']; ?>/read" class="button is-small is-primary">
                        <?php echo IconHelper::render('book-open', ['alt' => 'Read']); ?>
                    </a>
                    <a href="/texts?chg=<?php echo $chapter['id']; ?>" class="button is-small is-light">
                        <?php echo IconHelper::render('edit', ['alt' => 'Edit']); ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

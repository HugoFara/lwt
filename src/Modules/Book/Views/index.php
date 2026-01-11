<?php

/**
 * Books List View
 *
 * Variables expected:
 * - $books: array - Array of book data
 * - $pagination: array - Pagination info (total, page, perPage, totalPages)
 * - $languagesOption: string - HTML options for language select
 * - $languageId: int|null - Currently selected language ID
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

declare(strict_types=1);

namespace Lwt\Views\Book;

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\FormHelper;

$actions = [
    ['url' => '/book/import', 'label' => 'Import EPUB', 'icon' => 'file-up', 'class' => 'is-primary'],
    ['url' => '/texts?new=1', 'label' => 'New Text', 'icon' => 'circle-plus'],
    ['url' => '/texts', 'label' => 'All Texts', 'icon' => 'book-open'],
];

$message = $_GET['message'] ?? '';
?>

<h2 class="title is-4">
    My Books
    <a target="_blank" href="docs/info.html#howtotext" class="ml-2">
        <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
    </a>
</h2>

<?php echo PageLayoutHelper::buildActionCard($actions); ?>

<?php if ($message !== '') : ?>
<div class="notification is-info is-light">
    <?php echo htmlspecialchars($message); ?>
    <button class="delete" onclick="this.parentElement.remove()"></button>
</div>
<?php endif; ?>

<!-- Filter -->
<div class="box">
    <form method="get" action="/books" class="field is-horizontal">
        <div class="field-body">
            <div class="field">
                <label class="label is-small">Language</label>
                <div class="control">
                    <div class="select is-small is-fullwidth">
                        <select name="lg_id" onchange="this.form.submit()">
                            <?php echo $languagesOption; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="field">
                <label class="label is-small">&nbsp;</label>
                <div class="control">
                    <button type="submit" class="button is-small is-info">Filter</button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php if (empty($books)) : ?>
<div class="notification is-light">
    <p>No books found. Import an EPUB file or create a long text to get started.</p>
</div>
<?php else : ?>
<div class="box">
    <table class="table is-fullwidth is-hoverable">
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Chapters</th>
                <th>Progress</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book) : ?>
            <tr>
                <td>
                    <a href="/book/<?php echo $book['id']; ?>">
                        <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                    </a>
                    <?php if ($book['sourceType'] === 'epub') : ?>
                    <span class="tag is-small is-info ml-2">EPUB</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($book['author'] ?? ''); ?></td>
                <td><?php echo $book['totalChapters']; ?></td>
                <td>
                    <progress class="progress is-small is-primary"
                              value="<?php echo $book['progress']; ?>"
                              max="100"
                              title="<?php echo round($book['progress'], 1); ?>%">
                        <?php echo round($book['progress'], 1); ?>%
                    </progress>
                </td>
                <td>
                    <?php if ($book['totalChapters'] > 0) : ?>
                        <?php
                    // Get first chapter text ID
                        $firstChapterId = $book['id']; // Will need to query for actual text ID
                        ?>
                    <a href="/book/<?php echo $book['id']; ?>" class="button is-small is-primary" title="Continue Reading">
                        <?php echo IconHelper::render('book-open', ['alt' => 'Read']); ?>
                    </a>
                    <?php endif; ?>
                    <form method="post" action="/book/<?php echo $book['id']; ?>/delete"
                          style="display: inline;"
                          onsubmit="return confirm('Delete this book and all its chapters?');">
                        <?php echo FormHelper::csrfField(); ?>
                        <button type="submit" class="button is-small is-danger is-outlined" title="Delete">
                            <?php echo IconHelper::render('trash-2', ['alt' => 'Delete']); ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
    <?php if ($pagination['totalPages'] > 1) : ?>
<nav class="pagination is-centered" role="navigation">
    <a class="pagination-previous"
       href="/books?page=<?php echo max(1, $pagination['page'] - 1); ?><?php echo $languageId ? '&lg_id=' . $languageId : ''; ?>"
           <?php echo $pagination['page'] <= 1 ? 'disabled' : ''; ?>>
        Previous
    </a>
    <a class="pagination-next"
       href="/books?page=<?php echo min($pagination['totalPages'], $pagination['page'] + 1); ?><?php echo $languageId ? '&lg_id=' . $languageId : ''; ?>"
           <?php echo $pagination['page'] >= $pagination['totalPages'] ? 'disabled' : ''; ?>>
        Next
    </a>
    <ul class="pagination-list">
            <?php for ($i = 1; $i <= $pagination['totalPages']; $i++) : ?>
        <li>
            <a class="pagination-link <?php echo $i === $pagination['page'] ? 'is-current' : ''; ?>"
               href="/books?page=<?php echo $i; ?><?php echo $languageId ? '&lg_id=' . $languageId : ''; ?>">
                <?php echo $i; ?>
            </a>
        </li>
            <?php endfor; ?>
    </ul>
</nav>
    <?php endif; ?>

<?php endif; ?>

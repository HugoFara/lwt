<?php declare(strict_types=1);
/**
 * Languages Index View
 *
 * Variables expected:
 * - $languages: array of language data with stats
 * - $currentLanguageId: int current language ID
 * - $message: string optional message to display
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

namespace Lwt\Views\Language;

use Lwt\View\Helper\IconHelper;
use Lwt\View\Helper\PageLayoutHelper;

echo PageLayoutHelper::buildActionCard([
    ['url' => '/languages?new=1', 'label' => 'New Language', 'icon' => 'circle-plus', 'class' => 'is-primary'],
]);

if (empty($languages)): ?>
<p>No languages found.</p>
<?php else: ?>

<!-- Notification area for confirmation messages -->
<div id="language-notification" class="notification is-success is-light" style="display: none;">
    <button class="delete" onclick="this.parentElement.style.display='none'"></button>
    <span id="language-notification-text"></span>
</div>

<div class="columns is-multiline language-cards">
    <?php foreach ($languages as $lang): ?>
    <?php
        $isCurrent = ($currentLanguageId == $lang['id']);
        $canDelete = ($lang['textCount'] == 0 && $lang['archivedTextCount'] == 0 &&
                      $lang['wordCount'] == 0 && $lang['feedCount'] == 0);
    ?>
    <div class="column is-4-desktop is-6-tablet is-12-mobile">
        <div class="card language-card<?php echo $isCurrent ? ' is-current' : ''; ?>" data-lang-id="<?php echo $lang['id']; ?>">
            <header class="card-header">
                <p class="card-header-title">
                    <?php if ($isCurrent): ?>
                    <?php echo IconHelper::render('circle-alert', ['title' => 'Current Language', 'size' => 18]); ?>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($lang['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </p>
                <div class="card-header-icon">
                    <?php if (!$isCurrent): ?>
                    <button
                        type="button"
                        class="button is-small is-primary is-outlined set-current-language-btn"
                        data-action="set-current-language"
                        data-lang-id="<?php echo $lang['id']; ?>"
                        data-lang-name="<?php echo htmlspecialchars($lang['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        title="Set as Current Language"
                    >
                        <?php echo IconHelper::render('circle-check', ['size' => 14]); ?>
                        <span>Set as Default</span>
                    </button>
                    <?php endif; ?>
                </div>
            </header>

            <div class="card-content">
                <div class="language-stats">
                    <div class="stat-item">
                        <span class="stat-label">Texts</span>
                        <span class="stat-value">
                            <?php if ($lang['textCount'] > 0): ?>
                            <a href="/texts?page=1&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>"><?php echo $lang['textCount']; ?></a>
                            <?php else: ?>
                            0
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Archived</span>
                        <span class="stat-value">
                            <?php if ($lang['archivedTextCount'] > 0): ?>
                            <a href="/text/archived?page=1&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>"><?php echo $lang['archivedTextCount']; ?></a>
                            <?php else: ?>
                            0
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Terms</span>
                        <span class="stat-value">
                            <?php if ($lang['wordCount'] > 0): ?>
                            <a href="/words?page=1&amp;query=&amp;text=&amp;status=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1="><?php echo $lang['wordCount']; ?></a>
                            <?php else: ?>
                            0
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Feeds</span>
                        <span class="stat-value">
                            <?php if ($lang['feedCount'] > 0): ?>
                            <a href="/feeds?query=&amp;selected_feed=&amp;check_autoupdate=1&amp;filterlang=<?php echo $lang['id']; ?>"><?php echo $lang['feedCount']; ?> (<?php echo $lang['articleCount']; ?>)</a>
                            <?php else: ?>
                            0
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <?php if ($lang['hasExportTemplate']): ?>
                <div class="tags mt-3">
                    <span class="tag is-info is-light export-template-tag" title="This language has a custom export template for flexible term exports">
                        <?php echo IconHelper::render('file-down', ['size' => 12]); ?>
                        <span>Export Template</span>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <footer class="card-footer">
                <a href="/test?lang=<?php echo $lang['id']; ?>" class="card-footer-item">
                    <?php echo IconHelper::render('circle-help', ['size' => 16]); ?>
                    Test
                </a>
                <?php if ($lang['textCount'] > 0): ?>
                <a href="/languages?refresh=<?php echo $lang['id']; ?>" class="card-footer-item">
                    <?php echo IconHelper::render('zap', ['size' => 16]); ?>
                    Reparse
                </a>
                <?php endif; ?>
                <a href="/languages?chg=<?php echo $lang['id']; ?>" class="card-footer-item">
                    <?php echo IconHelper::render('file-pen', ['size' => 16]); ?>
                    Edit
                </a>
                <?php if ($canDelete): ?>
                <span class="card-footer-item click" data-action="confirm-delete" data-url="/languages?del=<?php echo $lang['id']; ?>">
                    <?php echo IconHelper::render('circle-minus', ['size' => 16]); ?>
                    Delete
                </span>
                <?php endif; ?>
            </footer>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

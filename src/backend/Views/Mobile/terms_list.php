<?php declare(strict_types=1);
/**
 * Mobile Terms List View - Terms for a sentence
 *
 * Variables expected:
 * - $action: Action code (4 or 5)
 * - $langId: Language ID
 * - $textId: Text ID
 * - $sentence: Sentence data array (id, text)
 * - $terms: Array of term records
 * - $nextSentenceId: Next sentence ID or null
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
 * @psalm-suppress UndefinedVariable - Variables are set by the including controller
 */

namespace Lwt\Views\Mobile;

/** @var int $action */
/** @var int $langId */
/** @var int $textId */
/** @var array $sentence */
/** @var array $terms */
/** @var int|null $nextSentenceId */

// Action 4 includes the opening ul tag; action 5 is for AJAX replacement
if ($action == 4): ?>
<ul id="<?php echo $action . '-' . $sentence['id']; ?>" title="<?php echo htmlspecialchars($sentence['text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>
    <li class="group">Sentence</li>
    <li><?php echo htmlspecialchars($sentence['text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></li>
    <li class="group">Terms</li>
<?php foreach ($terms as $term): ?>
    <?php if ($term['type'] === 'nonword'): ?>
    <li><?php echo htmlspecialchars($term['text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></li>
    <?php else: ?>
    <li><span class="status<?php echo $term['status']; ?>"><?php echo htmlspecialchars($term['text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span><?php echo htmlspecialchars($term['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></li>
    <?php endif; ?>
<?php endforeach; ?>
<?php if ($nextSentenceId !== null): ?>
    <li><a target="_replace" href="/mobile?action=5&amp;lang=<?php echo $langId; ?>&amp;text=<?php echo $textId; ?>&amp;sent=<?php echo $nextSentenceId; ?>">Next Sentence</a></li>
<?php endif; ?>
<?php if ($action == 4): ?>
</ul>
<?php endif; ?>

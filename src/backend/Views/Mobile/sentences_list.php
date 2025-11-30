<?php declare(strict_types=1);
/**
 * Mobile Sentences List View - List of sentences for a text
 *
 * Variables expected:
 * - $action: Action code (3)
 * - $langId: Language ID
 * - $text: Text data array (id, title, audioUri)
 * - $sentences: Array of sentence records
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
/** @var array $text */
/** @var array $sentences */

?>
<ul id="<?php echo $action . '-' . $text['id']; ?>" title="<?php echo tohtml($text['title']); ?>">
    <li class="group">Title</li>
    <li><?php echo tohtml($text['title']); ?></li>
<?php if ($text['audioUri'] !== ''): ?>
    <li class="group">Audio</li>
    <li>Play: <audio src="<?php echo $text['audioUri']; ?>" controls></audio></li>
<?php endif; ?>
    <li class="group">Text</li>
<?php foreach ($sentences as $sentence): ?>
    <li><a href="/mobile?action=4&amp;lang=<?php echo $langId; ?>&amp;text=<?php echo $text['id']; ?>&amp;sent=<?php echo $sentence['id']; ?>"><?php echo tohtml($sentence['text']); ?></a></li>
<?php endforeach; ?>
</ul>

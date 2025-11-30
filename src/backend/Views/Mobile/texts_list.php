<?php declare(strict_types=1);
/**
 * Mobile Texts List View - List of texts for a language
 *
 * Variables expected:
 * - $action: Action code (2)
 * - $langId: Language ID
 * - $langName: Language name
 * - $texts: Array of text records
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
/** @var string $langName */
/** @var array $texts */

?>
<ul id="<?php echo $action . '-' . $langId; ?>" title="All <?php echo tohtml($langName); ?> Texts">
<?php foreach ($texts as $text): ?>
    <li><a href="/mobile?action=3&amp;lang=<?php echo $langId; ?>&amp;text=<?php echo $text['id']; ?>"><?php echo tohtml($text['title']); ?></a></li>
<?php endforeach; ?>
</ul>

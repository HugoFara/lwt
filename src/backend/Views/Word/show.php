<?php declare(strict_types=1);
/**
 * Word Show View - Displays term details
 *
 * Variables expected:
 * - $word: array - Word details (text, translation, sentence, romanization, status, langId)
 * - $tags: string - Word tags
 * - $scrdir: string - Script direction tag
 * - $ann: string - Annotation to highlight
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

namespace Lwt\Views\Word;

?>
<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
    <td class="td1 right word-show-label">Term:</td>
    <td class="td1 word-show-term" <?php echo $scrdir; ?>><b><?php echo htmlspecialchars($word['text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></b></td>
</tr>
<tr>
    <td class="td1 right">Translation:</td>
    <td class="td1 word-show-value"><b><?php
    if (!empty($ann)) {
        echo \Lwt\Core\Utils\strReplaceFirst(
            htmlspecialchars($ann ?? '', ENT_QUOTES, 'UTF-8'),
            '<span class="word-show-highlight">' . htmlspecialchars($ann ?? '', ENT_QUOTES, 'UTF-8') . '</span>',
            htmlspecialchars($word['translation'] ?? '', ENT_QUOTES, 'UTF-8')
        );
    } else {
        echo htmlspecialchars($word['translation'] ?? '', ENT_QUOTES, 'UTF-8');
    }
    ?></b></td>
</tr>
<?php if ($tags !== '') : ?>
<tr>
    <td class="td1 right">Tags:</td>
    <td class="td1 word-show-value"><?php echo \Lwt\View\Helper\TagHelper::render($tags); ?></td>
</tr>
<?php endif; ?>
<?php if ($word['romanization'] !== '') : ?>
<tr>
    <td class="td1 right">Romaniz.:</td>
    <td class="td1 word-show-value"><b><?php echo htmlspecialchars($word['romanization'] ?? '', ENT_QUOTES, 'UTF-8'); ?></b></td>
</tr>
<?php endif; ?>
<tr>
    <td class="td1 right">Sentence<br />Term in {...}:</td>
    <td class="td1" <?php echo $scrdir; ?>><?php echo htmlspecialchars($word['sentence'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
</tr>
<tr>
    <td class="td1 right">Status:</td>
    <td class="td1"><?php echo get_colored_status_msg($word['status']); ?></td>
</tr>
</table>

<div data-lwt-cleanup-frames="true" hidden></div>

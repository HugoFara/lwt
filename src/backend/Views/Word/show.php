<?php

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
    <td class="td1 right" style="width:30px;">Term:</td>
    <td class="td1" style="font-size:120%; border-top-right-radius:inherit;" <?php echo $scrdir; ?>><b><?php echo tohtml($word['text']); ?></b></td>
</tr>
<tr>
    <td class="td1 right">Translation:</td>
    <td class="td1" style="font-size:120%;"><b><?php
    if (!empty($ann)) {
        echo str_replace_first(
            tohtml($ann),
            '<span style="color:red">' . tohtml($ann) . '</span>',
            tohtml($word['translation'])
        );
    } else {
        echo tohtml($word['translation']);
    }
    ?></b></td>
</tr>
<?php if ($tags !== '') : ?>
<tr>
    <td class="td1 right">Tags:</td>
    <td class="td1" style="font-size:120%;"><b><?php echo tohtml($tags); ?></b></td>
</tr>
<?php endif; ?>
<?php if ($word['romanization'] !== '') : ?>
<tr>
    <td class="td1 right">Romaniz.:</td>
    <td class="td1" style="font-size:120%;"><b><?php echo tohtml($word['romanization']); ?></b></td>
</tr>
<?php endif; ?>
<tr>
    <td class="td1 right">Sentence<br />Term in {...}:</td>
    <td class="td1" <?php echo $scrdir; ?>><?php echo tohtml($word['sentence']); ?></td>
</tr>
<tr>
    <td class="td1 right">Status:</td>
    <td class="td1"><?php echo get_colored_status_msg($word['status']); ?></td>
</tr>
</table>

<script type="text/javascript">
    cleanupRightFrames();
</script>

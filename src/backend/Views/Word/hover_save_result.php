<?php

/**
 * Hover Save Result View - Shows result after saving a word via hover
 *
 * Variables expected:
 * - $word: string - The word text (SQL-escaped)
 * - $wordRaw: string - The raw word text
 * - $status: int - Word status
 * - $translation: string - Translation text
 * - $wid: int - Word ID
 * - $hex: string - Hex class name for the term
 * - $textId: int - Text ID
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

use Lwt\Database\Escaping;

?>
<p>Status: <?php echo get_colored_status_msg($status); ?></p><br />
<?php if ($translation != '*'): ?>
<p>Translation: <b><?php echo tohtml($translation); ?></b></p>
<?php endif; ?>

<script type="text/javascript">
    const context = window.parent.document;
    let title = '';
    if (window.parent.LWT_DATA.settings.jQuery_tooltip)
        title = make_tooltip(
            <?php echo Escaping::prepareTextdataJs($wordRaw); ?>,
            <?php echo Escaping::prepareTextdataJs($translation); ?>,
            '',
            '<?php echo $status; ?>'
        );
    $('.TERM<?php echo $hex; ?>', context)
    .removeClass('status0')
    .addClass('status<?php echo $status; ?> word<?php echo $wid; ?>')
    .attr('data_status', '<?php echo $status; ?>')
    .attr('data_wid', '<?php echo $wid; ?>')
    .attr('title', title)
    .attr('data_trans','<?php echo tohtml($translation); ?>');
    $('#learnstatus', context)
    .html('<?php echo addslashes(todo_words_content($textId)); ?>');
    cleanupRightFrames();
</script>

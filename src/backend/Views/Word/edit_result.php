<?php

/**
 * Word Edit Result View - Shows result after saving/updating a word
 *
 * Variables expected:
 * - $message: string - Result message
 * - $wid: int - Word ID
 * - $textId: int - Text ID
 * - $hex: string|null - Hex class name for the term (for new words)
 * - $translation: string - Translation text
 * - $status: int - Word status
 * - $oldStatus: int - Previous status (for updates)
 * - $romanization: string - Romanization
 * - $text: string - Original text
 * - $fromAnn: string - From annotation flag
 * - $isNew: bool - Whether this is a new word
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
<p>OK: <?php echo tohtml($message); ?></p>

<?php if ($fromAnn === ""): ?>
<script type="text/javascript">
    const context = window.parent.document.getElementById('frame-l');
    const contexth = window.parent.document.getElementById('frame-h');
    const woid = <?php echo json_encode($wid); ?>;
    const status = <?php echo json_encode($status); ?>;
    const trans = <?php echo json_encode($translation . \Lwt\Services\TagService::getWordTagListFormatted($wid, ' ', true, false)); ?>;
    const roman = <?php echo json_encode($romanization); ?>;
    let title;
    if (window.parent.LWT_DATA.settings.jQuery_tooltip) {
        title = '';
    } else {
        title = make_tooltip(
            <?php echo json_encode($text); ?>,
            trans, roman, status
        );
    }
    <?php if ($isNew): ?>
        $('.TERM<?php echo $hex; ?>', context)
        .removeClass('status0')
        .addClass('word' + woid + ' ' + 'status' + status)
        .attr('data_trans', trans)
        .attr('data_rom', roman)
        .attr('data_status', status)
        .attr('title', title)
        .attr('data_wid', woid);
    <?php else: ?>
        $('.word' + woid, context)
        .removeClass('status<?php echo $oldStatus; ?>')
        .addClass('status' + status)
        .attr('data_trans', trans)
        .attr('data_rom', roman)
        .attr('data_status', status)
        .attr('title', title);
    <?php endif; ?>
    $('#learnstatus', contexth)
    .html('<?php echo addslashes(todo_words_content($textId)); ?>');

    cleanupRightFrames();
</script>
<?php else: ?>
<script type="text/javascript">
    window.opener.do_ajax_edit_impr_text(
        <?php echo $fromAnn; ?>,
        <?php echo json_encode($textlc ?? ''); ?>,
        <?php echo $wid; ?>
    );
</script>
<?php endif; ?>

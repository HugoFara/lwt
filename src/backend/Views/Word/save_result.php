<?php

/**
 * Word Save Result View - Shows result after saving a word
 *
 * Variables expected:
 * - $message: string - Result message
 * - $success: bool - Whether save was successful
 * - $wid: int - Word ID (if successful)
 * - $textId: int - Text ID
 * - $hex: string - Hex class name for the term
 * - $translation: string - Translation text
 * - $status: int - Word status
 * - $romanization: string - Romanization
 * - $text: string - Original text
 * - $len: int - Word count (1 for single word)
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
<p><?php echo $message; ?></p>

<?php if ($success && $len == 1): ?>
<script type="text/javascript">
    var context = window.parent.document;
    var woid = <?php echo Escaping::prepareTextdataJs($wid); ?>;
    var status = <?php echo Escaping::prepareTextdataJs($status); ?>;
    var trans = <?php echo Escaping::prepareTextdataJs($translation . \Lwt\Services\TagService::getWordTagListFormatted($wid, ' ', true, false)); ?>;
    var roman = <?php echo Escaping::prepareTextdataJs($romanization); ?>;
    var title = '';
    if (window.parent.LWT_DATA.settings.jQuery_tooltip) {
        title = make_tooltip(
                <?php echo Escaping::prepareTextdataJs($text); ?>,
            trans, roman, status
        );
    }

    if($('.TERM<?php echo $hex; ?>', context).length){
        $('.TERM<?php echo $hex; ?>', context)
        .removeClass('status0')
        .addClass('word' + woid + ' ' + 'status' + status)
        .attr('data_trans',trans)
        .attr('data_rom',roman)
        .attr('data_status',status)
        .attr('data_wid',woid)
        .attr('title',title);
        $('#learnstatus', context).html('<?php echo addslashes(todo_words_content($textId)); ?>');
    }
</script>
<?php endif; ?>

<?php if ($success): ?>
<script type="text/javascript">
    cleanupRightFrames();
</script>
<?php endif; ?>

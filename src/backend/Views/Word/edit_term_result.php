<?php

/**
 * Edit Term Result View - Shows result after updating a word during testing
 *
 * Variables expected:
 * - $message: string - Result message
 * - $wid: int - Word ID
 * - $translation: string - Translation text
 * - $status: int - Word status
 * - $romanization: string - Romanization
 * - $text: string - Term text
 * - $sent1: string - Formatted sentence for display
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

<script type="text/javascript">
//<![CDATA[
var context = window.parent.document;
var woid = <?php echo json_encode($wid); ?>;
if(window.parent.location.href.includes('type=table')) {
    // Table Test
    $('#STAT' + woid, context).html(<?php echo json_encode(make_status_controls_test_table(1, (int) $status, $wid)); ?>);
    $('#TERM' + woid, context).html(<?php echo json_encode(tohtml($text)); ?>);
    $('#TRAN' + woid, context).html(<?php echo json_encode(tohtml($translation)); ?>);
    $('#ROMA' + woid, context).html(<?php echo json_encode(tohtml($romanization)); ?>);
    $('#SENT' + woid, context).html(<?php echo json_encode($sent1); ?>);
} else {
    // Normal Test
    var wotext = <?php echo json_encode($text); ?>;
    var status = <?php echo json_encode($status); ?>;
    var trans = <?php echo json_encode($translation . \Lwt\Services\TagService::getWordTagListFormatted($wid, ' ', true, false)); ?>;
    var roman = <?php echo json_encode($romanization); ?>;
    $('.word' + woid, context).attr('data_text',wotext).attr('data_trans',trans).attr('data_rom',roman).attr('data_status',status);
}
cleanupRightFrames();
//]]>
</script>

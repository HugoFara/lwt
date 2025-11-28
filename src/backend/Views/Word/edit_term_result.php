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

use Lwt\Database\Escaping;

?>
<p>OK: <?php echo tohtml($message); ?></p>

<script type="text/javascript">
//<![CDATA[
var context = window.parent.document;
var woid = <?php echo Escaping::prepareTextdataJs($wid); ?>;
if(window.parent.location.href.includes('type=table')) {
    // Table Test
    $('#STAT' + woid, context).html(<?php echo Escaping::prepareTextdataJs(make_status_controls_test_table(1, (int) $status, $wid)); ?>);
    $('#TERM' + woid, context).html(<?php echo Escaping::prepareTextdataJs(tohtml($text)); ?>);
    $('#TRAN' + woid, context).html(<?php echo Escaping::prepareTextdataJs(tohtml($translation)); ?>);
    $('#ROMA' + woid, context).html(<?php echo Escaping::prepareTextdataJs(tohtml($romanization)); ?>);
    $('#SENT' + woid, context).html(<?php echo Escaping::prepareTextdataJs($sent1); ?>);
} else {
    // Normal Test
    var wotext = <?php echo Escaping::prepareTextdataJs($text); ?>;
    var status = <?php echo Escaping::prepareTextdataJs($status); ?>;
    var trans = <?php echo Escaping::prepareTextdataJs($translation . getWordTagList($wid, ' ', 1, 0)); ?>;
    var roman = <?php echo Escaping::prepareTextdataJs($romanization); ?>;
    $('.word' + woid, context).attr('data_text',wotext).attr('data_trans',trans).attr('data_rom',roman).attr('data_status',status);
}
cleanupRightFrames();
//]]>
</script>

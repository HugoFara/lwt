<?php

/**
 * Word Delete Multi Result View - Shows result after deleting a multi-word expression
 *
 * Variables expected:
 * - $term: string - The deleted term text
 * - $wid: int - Word ID that was deleted
 * - $textId: int - Text ID
 * - $rowsAffected: int - Number of affected rows
 * - $showAll: bool - Whether to show all words setting
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
<p>OK, term deleted (<?php echo $rowsAffected; ?>).</p>

<script type="text/javascript">
//<![CDATA[
let context = window.parent.document;
$('.word<?php echo $wid; ?>', context).each(
    function() {
        sid = $(this).parent();
        $(this).remove();
        if (<?php echo json_encode(!$showAll); ?>) {
            $('*', sid).removeClass('hide');
            $('.mword', sid).each(function() {
                if ($(this).not('.hide').length){
                    u = parseInt($(this).attr('data_code')) * 2 + parseInt($(this).attr('data_order')) -1;
                    $(this).nextUntil('[id^="ID-' + u + '-"]',sid).addClass('hide');
                }
            });
        }
    }
);
$('#learnstatus', context).html(<?php echo json_encode(todo_words_content((int) $textId)); ?>);
cleanupRightFrames();
//]]>
</script>

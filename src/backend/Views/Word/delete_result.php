<?php

/**
 * Word Delete Result View - Shows result after deleting a word
 *
 * Variables expected:
 * - $term: string - The deleted term text
 * - $wid: int - Word ID that was deleted
 * - $textId: int - Text ID
 * - $message: string - Result message
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
<p>OK, term deleted, now unknown (<?php echo tohtml($message); ?>).</p>

<script type="text/javascript">
//<![CDATA[
/**
 * Make the visual effects to delete a word from the page.
 *
 * @param {int} wid Word ID
 * @param {string} term Term text
 * @param {string} todoContent Updated todo content
 * @returns {undefined}
 */
function delete_word(wid, term, todoContent) {
    const context = window.parent.document;
    const elem = $('.word' + wid, context);
    let title = "";
    if (!window.parent.document.LWT_DATA.settings.jQuery_tooltip) {
        const ann = elem.attr('data_ann');
        title = make_tooltip(
            term,
            ann + (ann ? ' / ' : '') + elem.attr('data_trans'),
            elem.attr('data_rom'),
            elem.attr('data_status')
        );
    }
    elem
    .removeClass('status99 status98 status1 status2 status3 status4 status5 word' + wid)
    .addClass('status0')
    .attr('data_status', '0')
    .attr('data_trans', '')
    .attr('data_rom', '')
    .attr('data_wid', '')
    .attr('title', title)
    .removeAttr("data_img");
    $('#learnstatus', context).html(todoContent);

    cleanupRightFrames();
}

delete_word(
    <?php echo $wid; ?>,
    <?php echo json_encode($term); ?>,
    <?php echo json_encode(todo_words_content($textId)); ?>
);
//]]>
</script>

<?php

/**
 * Word Status Change Result View - Shows result after changing word status
 *
 * Variables expected:
 * - $wid: int - Word ID
 * - $textId: int - Text ID
 * - $status: int - New status value
 * - $term: string - Term text
 * - $translation: string - Translation text
 * - $romanization: string - Romanization
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

$todoContent = todo_words_content((int) $textId);
?>
<p id='status_change_log'>Term status updating...</p>

<script type="text/javascript">
function word_update_error() {
    $('#status_change_log').text("Word status update failed!");
    cleanupRightFrames();
}

function update_word_display(wid, status, word, trans, roman, todoContent) {
    let context = window.parent.document.getElementById('frame-l');
    let contexth = window.parent.document.getElementById('frame-h');
    let title = '';
    if (!window.parent.LWT_DATA.settings.jQuery_tooltip) {
        title = make_tooltip(word, trans, roman, status);
    }
    $('.word' + wid, context)
    .removeClass('status98 status99 status1 status2 status3 status4 status5')
    .addClass('status' + status)
    .attr('data_status', status)
    .attr('title', title);
    $('#learnstatus', contexth).html(todoContent);
}

function apply_word_update(wid, status) {
    $('#status_change_log').text('Term status changed to ' + status);
    update_word_display(
        wid, status,
        <?php echo json_encode($term); ?>,
        <?php echo json_encode($translation); ?>,
        <?php echo json_encode($romanization); ?>,
        <?php echo json_encode($todoContent); ?>
    );
    cleanupRightFrames();
}

// Send AJAX request to update status
const wordid = parseInt(<?php echo $wid; ?>, 10);
const status = parseInt(<?php echo $status; ?>, 10);
$.post(
    'api.php/v1/terms/' + wordid + '/status/' + status,
    {},
    function (data) {
        if (data == "" || "error" in data) {
            word_update_error();
        } else {
            apply_word_update(wordid, status);
        }
    },
    "json"
);
</script>

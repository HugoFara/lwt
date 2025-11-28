<?php

/**
 * Bulk Save Result View - Shows result after saving bulk translated words
 *
 * Variables expected:
 * - $tid: int - Text ID
 * - $cleanUp: bool - Whether to clean up right frames
 * - $tooltipMode: int - Tooltip display mode (1 = show)
 * - $newWords: array - Array of newly created words with keys:
 *     - WoID: int - Word ID
 *     - WoTextLC: string - Lowercase word text
 *     - WoStatus: int - Word status
 *     - translation: string - Word translation
 *     - hex: string - Hex class name for CSS
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
<p id="displ_message">
    <img src="/assets/icons/waiting2.gif" /> Updating Texts
</p>
<script type="text/javascript">
    const context = window.parent.document;
    const tooltip = <?php echo json_encode($tooltipMode == 1); ?>;

    function change_term(term) {
        $(".TERM" + term.hex, context)
        .removeClass("status0")
        .addClass("status" + term.WoStatus)
        .addClass("word" + term.WoID)
        .attr("data_wid", term.WoID)
        .attr("data_status", term.WoStatus)
        .attr("data_trans", term.translation);
        if (tooltip) {
            $(".TERM" + term.hex, context).each(
                function() {
                    this.title = make_tooltip(
                        $(this).text(), $(this).attr('data_trans'),
                        $(this).attr('data_rom'), $(this).attr('data_status')
                    );
                }
            );
        } else {
            $(".TERM" + term.hex, context).attr('title', '');
        }

    }
    <?php
    foreach ($newWords as $word) {
        echo "change_term(" . json_encode($word) . ");";
    }
    ?>

    $('#learnstatus', context)
    .html('<?php echo addslashes(todo_words_content($tid)); ?>');
    $('#displ_message').remove();
    if (<?php echo json_encode($cleanUp); ?>) {
        cleanupRightFrames();
    }
</script>

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
    (function() {
        const useTooltip = <?php echo json_encode($tooltipMode == 1); ?>;
        const words = <?php echo json_encode(array_values($newWords)); ?>;

        words.forEach(function(term) {
            updateBulkWordInDOM(term, useTooltip);
        });

        updateLearnStatus(<?php echo json_encode(todo_words_content($tid)); ?>);
        document.getElementById('displ_message')?.remove();

        if (<?php echo json_encode($cleanUp); ?>) {
            cleanupRightFrames();
        }
    })();
</script>

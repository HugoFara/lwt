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
    deleteMultiWordFromDOM(<?php echo $wid; ?>, <?php echo json_encode($showAll); ?>);
    completeWordOperation(<?php echo json_encode(todo_words_content((int) $textId)); ?>);
</script>

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
    deleteWordFromDOM(<?php echo $wid; ?>, <?php echo json_encode($term); ?>);
    completeWordOperation(<?php echo json_encode(todo_words_content($textId)); ?>);
</script>

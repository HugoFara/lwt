<?php

/**
 * Word Insert Ignore Result View - Shows result after marking word as ignored
 *
 * Variables expected:
 * - $term: string - Term text
 * - $wid: int - New word ID
 * - $hex: string - Hex class name for the term
 * - $textId: int - Text ID
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
<p>OK, this term will be ignored!</p>

<script type="text/javascript">
    markWordIgnoredInDOM(<?php echo $wid; ?>, <?php echo json_encode($hex); ?>, <?php echo json_encode($term); ?>);
    completeWordOperation(<?php echo json_encode(todo_words_content((int) $textId)); ?>);
</script>

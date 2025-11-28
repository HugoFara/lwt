<?php

/**
 * All Words Well-Known Result View
 *
 * Variables expected:
 * - $status: int - Status applied (98=ignored, 99=well-known)
 * - $count: int - Number of words modified
 * - $textId: int - Text ID
 * - $javascript: string - JavaScript code to update the UI
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
<p>
<?php
if ($status == 98) {
    if ($count > 1) {
        echo "Ignored all $count words!";
    } elseif ($count == 1) {
        echo "Ignored 1 word.";
    } else {
        echo "No new word ignored!";
    }
} else {
    if ($count > 1) {
        echo "You know all $count words well!";
    } elseif ($count == 1) {
        echo "1 new word added as known";
    } else {
        echo "No new known word added!";
    }
}
?>
</p>

<script type="text/javascript">
    //<![CDATA[
    const context = window.parent.document;
    <?php echo $javascript; ?>
    $('#learnstatus', context).html('<?php echo addslashes(todo_words_content($textId)); ?>');
    window.parent.setTimeout(window.parent.cClick, 1000);
    //]]>
</script>

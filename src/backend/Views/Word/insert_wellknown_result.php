<?php

/**
 * Word Insert Well-known Result View - Shows result after marking word as well-known
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
<p>OK, you know this term well!</p>

<script type="text/javascript">
//<![CDATA[
var context = window.parent.document.getElementById('frame-l');
var contexth = window.parent.document.getElementById('frame-h');
var title = make_tooltip(<?php echo json_encode($term); ?>,'*','','99');
$('.TERM<?php echo $hex; ?>', context)
.removeClass('status0')
.addClass('status99 word<?php echo $wid; ?>')
.attr('data_status','99')
.attr('data_wid','<?php echo $wid; ?>')
.attr('title',title);
$('#learnstatus', contexth).html('<?php echo addslashes(todo_words_content((int) $textId)); ?>');

cleanupRightFrames();
//]]>
</script>

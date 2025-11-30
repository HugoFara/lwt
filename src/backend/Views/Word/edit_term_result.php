<?php

/**
 * Edit Term Result View - Shows result after updating a word during testing
 *
 * Variables expected:
 * - $message: string - Result message
 * - $wid: int - Word ID
 * - $translation: string - Translation text
 * - $status: int - Word status
 * - $romanization: string - Romanization
 * - $text: string - Term text
 * - $sent1: string - Formatted sentence for display
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
<p>OK: <?php echo tohtml($message); ?></p>

<script type="application/json" data-lwt-edit-term-result-config>
<?php echo json_encode([
    'wid' => $wid,
    'text' => $text,
    'translation' => $translation,
    'translationWithTags' => $translation . \Lwt\Services\TagService::getWordTagListFormatted($wid, ' ', true, false),
    'romanization' => $romanization,
    'status' => $status,
    'sentence' => $sent1,
    'statusControlsHtml' => make_status_controls_test_table(1, (int) $status, $wid)
]); ?>
</script>

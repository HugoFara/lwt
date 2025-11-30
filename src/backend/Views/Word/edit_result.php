<?php

/**
 * Word Edit Result View - Shows result after saving/updating a word
 *
 * Variables expected:
 * - $message: string - Result message
 * - $wid: int - Word ID
 * - $textId: int - Text ID
 * - $hex: string|null - Hex class name for the term (for new words)
 * - $translation: string - Translation text
 * - $status: int - Word status
 * - $oldStatus: int - Previous status (for updates)
 * - $romanization: string - Romanization
 * - $text: string - Original text
 * - $fromAnn: string - From annotation flag
 * - $isNew: bool - Whether this is a new word
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

$config = [
    'wid' => $wid,
    'status' => $status,
    'translation' => $translation . \Lwt\Services\TagService::getWordTagListFormatted($wid, ' ', true, false),
    'romanization' => $romanization,
    'text' => $text,
    'textId' => $textId,
    'isNew' => $isNew
];

if ($fromAnn === "") {
    // Normal mode
    if ($isNew) {
        $config['hex'] = $hex;
    } else {
        $config['oldStatus'] = $oldStatus;
    }
    $config['todoContent'] = todo_words_content($textId);
} else {
    // Annotation mode
    $config['fromAnn'] = (int)$fromAnn;
    $config['textlc'] = $textlc ?? '';
}

?>
<p>OK: <?php echo tohtml($message); ?></p>

<script type="application/json" data-lwt-edit-result-config>
<?php echo json_encode($config); ?>
</script>

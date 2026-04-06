<?php

/**
 * Insert Status Result View - Shows result after inserting word with status (wellknown/ignore)
 *
 * Variables expected:
 * - $wid: int - Word ID
 * - $textId: int - Text ID
 * - $ord: int - Word order position
 * - $status: int - Word status (98 for ignore, 99 for wellknown)
 * - $hex: string - Hex class name for the term
 * - $word: string - The word text
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Views;

// Type assertions for variables passed from controller
assert(is_int($wid));
assert(is_int($textId));
assert(is_int($ord));
assert(is_int($status));
assert(is_string($hex));
assert(is_string($word));
assert(is_string($todoContent));

$message = $status === 99
    ? __('vocabulary.result.word_marked_well_known')
    : __('vocabulary.result.word_marked_ignored');
?>
<p><?php echo $message; ?></p>

<script type="application/json" data-lwt-insert-status-result-config>
<?php echo json_encode([
    'wid' => $wid,
    'hex' => $hex,
    'status' => $status,
    'word' => $word,
    'textId' => $textId,
    'ord' => $ord,
    'todoContent' => $todoContent
], JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

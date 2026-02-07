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

declare(strict_types=1);

namespace Lwt\Views\Word;

use Lwt\Modules\Vocabulary\Application\Helpers\StatusHelper;

// Type assertions for variables passed from controller
assert(is_string($message));
assert(is_int($wid));
assert(is_string($translation));
assert(is_int($status));
assert(is_string($romanization));
assert(is_string($text));
assert(is_string($sent1));
assert(is_string($tagList));

?>
<p>OK: <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>

<script type="application/json" data-lwt-edit-term-result-config>
<?php
$statusAbbr = StatusHelper::getAbbr($status);
$formattedTags = $tagList !== '' ? ' [' . str_replace(',', ', ', $tagList) . ']' : '';
echo json_encode([
    'wid' => $wid,
    'text' => $text,
    'translation' => $translation,
    'translationWithTags' => $translation . $formattedTags,
    'romanization' => $romanization,
    'status' => $status,
    'sentence' => $sent1,
    'statusControlsHtml' => StatusHelper::buildReviewTableControls(1, $status, $wid, $statusAbbr)
], JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

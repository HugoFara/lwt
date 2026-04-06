<?php

/**
 * All Words Well-Known Result View
 *
 * Variables expected:
 * - $status: int - Status applied (98=ignored, 99=well-known)
 * - $count: int - Number of words modified
 * - $textId: int - Text ID
 * - $wordsData: array - Array of word data for DOM updates
 * - $useTooltips: bool - Whether tooltips are enabled
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Views\Word;

// Type assertions for variables passed from controller
assert(is_int($status));
assert(is_int($count));
assert(is_int($textId));
assert(is_array($wordsData));
assert(is_bool($useTooltips));
assert(is_string($todoContent));

?>
<p>
<?php
if ($status == 98) {
    if ($count > 1) {
        echo __('vocabulary.result.ignored_all', ['count' => $count]);
    } elseif ($count == 1) {
        echo __('vocabulary.result.ignored_one');
    } else {
        echo __('vocabulary.result.ignored_none');
    }
} else {
    if ($count > 1) {
        echo __('vocabulary.result.know_all', ['count' => $count]);
    } elseif ($count == 1) {
        echo __('vocabulary.result.know_one');
    } else {
        echo __('vocabulary.result.know_none');
    }
}
?>
</p>

<script type="application/json" data-lwt-all-wellknown-config>
<?php echo json_encode([
    'words' => $wordsData,
    'useTooltips' => $useTooltips,
    'todoContent' => $todoContent
], JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

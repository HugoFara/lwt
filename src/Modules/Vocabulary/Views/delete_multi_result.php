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

declare(strict_types=1);

namespace Lwt\Views\Word;

// Type assertions for variables passed from controller
assert(is_int($wid));
assert(is_int($textId));
assert(is_bool($deleted));
assert(is_bool($showAll));
assert(is_string($todoContent));

?>
<p>OK, term deleted.</p>

<script type="application/json" data-lwt-delete-multi-result-config>
<?php echo json_encode(
    [
    'wid' => $wid,
    'showAll' => $showAll,
    'todoContent' => $todoContent
    ],
    JSON_HEX_TAG | JSON_HEX_AMP
); ?></script>

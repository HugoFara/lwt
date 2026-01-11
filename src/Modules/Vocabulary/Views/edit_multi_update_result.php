<?php

declare(strict_types=1);

/**
 * Multi-Word Update Result View - JavaScript to update UI after multi-word edit
 *
 * Variables expected:
 * - $termJson: string - JSON encoded term data
 * - $oldStatusValue: int - Previous status value
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

// Type assertions for variables passed from controller
assert(is_string($termJson));
assert(is_int($oldStatusValue));

// Decode the term JSON to include in our config
/**
 * @var array{woid: int, text: string, translation: string, romanization: string, status: int} $termData
*/
$termData = json_decode($termJson, true);
?>
<script type="application/json" data-lwt-edit-multi-update-result-config>
<?php echo json_encode(
    [
    'wid' => $termData['woid'],
    'text' => $termData['text'],
    'translation' => $termData['translation'],
    'romanization' => $termData['romanization'],
    'status' => $termData['status'],
    'oldStatus' => $oldStatusValue
    ],
    JSON_HEX_TAG | JSON_HEX_AMP
); ?></script>

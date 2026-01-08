<?php declare(strict_types=1);
/**
 * Status Change Config View - JavaScript config for status change page
 *
 * Variables expected:
 * - $wordId: int - Word ID
 * - $newStatus: int - New status
 * - $statusChange: int - Status change direction
 * - $testStatus: array - Test progress data
 * - $ajax: bool - Whether using AJAX mode
 * - $waitTime: int - Wait time before reload
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

namespace Lwt\Views\Review;

?>
<script type="application/json" data-lwt-status-change-result-config>
<?php echo json_encode([
    'wordId' => $wordId,
    'newStatus' => $newStatus,
    'statusChange' => $statusChange,
    'testStatus' => $testStatus,
    'ajax' => $ajax,
    'waitTime' => $waitTime
]); ?>
</script>

<?php declare(strict_types=1);
/**
 * AJAX Test Config View - JavaScript config for AJAX-based tests
 *
 * Variables expected:
 * - $reviewData: array - Review data for JavaScript
 * - $waitTime: int - Edit frame waiting time
 * - $startTime: int - Test start time
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

namespace Lwt\Views\Test;

$timeData = [
    'wait_time' => $waitTime,
    'time' => time(),
    'start_time' => $startTime,
    'show_timer' => $reviewData['total_tests'] > 0 ? 0 : 1
];
?>
<script type="application/json" data-lwt-ajax-test-config>
<?php echo json_encode([
    'reviewData' => $reviewData,
    'timeData' => $timeData
]); ?>
</script>

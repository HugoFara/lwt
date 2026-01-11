<?php

declare(strict_types=1);

/**
 * Review Finished View - Shows completion message
 *
 * Variables expected:
 * - $totalTests: int - Total reviews done
 * - $tomorrowTests: int - Reviews due tomorrow
 * - $hidden: bool - Whether to hide initially (for AJAX)
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @var int $totalTests
 * @var int $tomorrowTests
 * @var bool $hidden
 */

namespace Lwt\Views\Review;

// Validate and cast injected variables
assert(isset($totalTests) && is_int($totalTests));
assert(isset($tomorrowTests) && is_int($tomorrowTests));
assert(isset($hidden) && is_bool($hidden));

$display = $hidden ? 'none' : 'inherit';
?>
<p id="test-finished-area" class="has-text-centered" style="display: <?php echo $display; ?>;">
    <img src="/assets/images/ok.png" alt="Done!" />
    <br /><br />
    <span class="has-text-danger has-text-weight-bold">
        <span id="tests-done-today">
            Nothing <?php echo $totalTests > 0 ? 'more ' : ''; ?>to review here!
        </span>
        <br /><br />
        <span id="tests-tomorrow">
            Tomorrow you'll find here <?php echo $tomorrowTests; ?>
            review<?php echo $tomorrowTests == 1 ? '' : 's'; ?>!
        </span>
    </span>
</p>

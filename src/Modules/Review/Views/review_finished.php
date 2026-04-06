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
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 *
 * @var int $totalTests
 * @var int $tomorrowTests
 * @var bool $hidden
 */

namespace Lwt\Views\Review;

use Lwt\Shared\UI\Helpers\IconHelper;

// Validate and cast injected variables
assert(isset($totalTests) && is_int($totalTests));
assert(isset($tomorrowTests) && is_int($tomorrowTests));
assert(isset($hidden) && is_bool($hidden));

$display = $hidden ? 'none' : 'inherit';
?>
<p id="test-finished-area" class="has-text-centered" style="display: <?php echo $display; ?>;">
<?php
    $doneLabel = __('review.card.correct');
    $finishedMsg = $totalTests > 0
        ? __('review.finished.nothing_more')
        : __('review.finished.nothing');
    $tomorrowUnit = $tomorrowTests == 1
        ? __('review.finished.tomorrow_one')
        : __('review.finished.tomorrow_many');
?>
    <?php echo IconHelper::render(
        'circle-check',
        ['size' => 64, 'alt' => $doneLabel, 'class' => 'has-text-success']
    ); ?>
    <br /><br />
    <span class="has-text-danger has-text-weight-bold">
        <span id="tests-done-today">
            <?php echo \htmlspecialchars($finishedMsg, ENT_QUOTES, 'UTF-8'); ?>
        </span>
        <br /><br />
        <span id="tests-tomorrow">
            Tomorrow you'll find here <?php echo $tomorrowTests; ?>
            <?php echo \htmlspecialchars($tomorrowUnit, ENT_QUOTES, 'UTF-8'); ?>!
        </span>
    </span>
</p>

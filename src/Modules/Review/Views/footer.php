<?php

declare(strict_types=1);

/**
 * Review Footer View - Progress bar and statistics
 *
 * Variables expected:
 * - $remaining: int - Not yet reviewed count
 * - $wrong: int - Wrong answers count
 * - $correct: int - Correct answers count
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
 * @var int $remaining
 * @var int $wrong
 * @var int $correct
 */

namespace Lwt\Views\Review;

use Lwt\Shared\UI\Helpers\IconHelper;

// Ensure variables are integers
$remainingInt = (int) ($remaining ?? 0);
$wrongInt = (int) ($wrong ?? 0);
$correctInt = (int) ($correct ?? 0);

$total = $wrongInt + $correctInt + $remainingInt;
$divisor = $total > 0 ? $total / 100.0 : 1.0;
$lRemaining = (int) round($remainingInt / $divisor, 0);
$lWrong = (int) round($wrongInt / $divisor, 0);
$lCorrect = (int) round($correctInt / $divisor, 0);
?>
<footer id="footer">
    <span class="test-footer-stat">
        <?php echo IconHelper::render('clock', ['title' => 'Elapsed Time', 'alt' => 'Elapsed Time']); ?>
        <span id="timer" title="Elapsed Time"></span>
    </span>
    <span class="test-footer-stat test-progress-bar">
        <span id="not-tested-box" class="test-progress-notyet"
            title="Not yet reviewed"
            style="width:<?php echo $lRemaining; ?>px"></span><span
            id="wrong-tests-box" class="test-progress-wrong"
            title="Wrong"
            style="width:<?php echo $lWrong; ?>px"></span><span
            id="correct-tests-box" class="test-progress-correct"
            title="Correct"
            style="width:<?php echo $lCorrect; ?>px"></span>
    </span>
    <span class="test-footer-stat">
        <span title="Total reviews" id="total_tests"><?php echo $total; ?></span>
        =
        <span class="todosty" title="Not yet reviewed" id="not-tested"><?php echo $remainingInt; ?></span>
        +
        <span class="donewrongsty" title="Wrong" id="wrong-tests"><?php echo $wrongInt; ?></span>
        +
        <span class="doneoksty" title="Correct" id="correct-tests"><?php echo $correctInt; ?></span>
    </span>
</footer>

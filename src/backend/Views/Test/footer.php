<?php declare(strict_types=1);
/**
 * Test Footer View - Progress bar and statistics
 *
 * Variables expected:
 * - $remaining: int - Not yet tested count
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
 */

namespace Lwt\Views\Test;

use Lwt\Shared\UI\Helpers\IconHelper;

// Type assertions for variables from controller extract()
$remaining = (int) ($remaining ?? 0);
$wrong = (int) ($wrong ?? 0);
$correct = (int) ($correct ?? 0);

$total = $wrong + $correct + $remaining;
$divisor = $total > 0 ? $total / 100.0 : 1.0;
$lRemaining = (int) round($remaining / $divisor, 0);
$lWrong = (int) round($wrong / $divisor, 0);
$lCorrect = (int) round($correct / $divisor, 0);
?>
<footer id="footer">
    <span class="test-footer-stat">
        <?php echo IconHelper::render('clock', ['title' => 'Elapsed Time', 'alt' => 'Elapsed Time']); ?>
        <span id="timer" title="Elapsed Time"></span>
    </span>
    <span class="test-footer-stat test-progress-bar">
        <span id="not-tested-box" class="test-progress-notyet"
            title="Not yet tested"
            style="width:<?php echo $lRemaining; ?>px"></span><span
            id="wrong-tests-box" class="test-progress-wrong"
            title="Wrong"
            style="width:<?php echo $lWrong; ?>px"></span><span
            id="correct-tests-box" class="test-progress-correct"
            title="Correct"
            style="width:<?php echo $lCorrect; ?>px"></span>
    </span>
    <span class="test-footer-stat">
        <span title="Total number of tests" id="total_tests"><?php echo $total; ?></span>
        =
        <span class="todosty" title="Not yet tested" id="not-tested"><?php echo $remaining; ?></span>
        +
        <span class="donewrongsty" title="Wrong" id="wrong-tests"><?php echo $wrong; ?></span>
        +
        <span class="doneoksty" title="Correct" id="correct-tests"><?php echo $correct; ?></span>
    </span>
</footer>

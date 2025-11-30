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

$total = $wrong + $correct + $remaining;
$divisor = $total > 0 ? $total / 100.0 : 1.0;
$lRemaining = round($remaining / $divisor, 0);
$lWrong = round($wrong / $divisor, 0);
$lCorrect = round($correct / $divisor, 0);
?>
<footer id="footer">
    <span class="test-footer-stat">
        <img src="/assets/icons/clock.png" title="Elapsed Time" alt="Elapsed Time" />
        <span id="timer" title="Elapsed Time"></span>
    </span>
    <span class="test-footer-stat">
        <img id="not-tested-box" class="borderl"
            src="<?php echo \get_file_path('icn/test_notyet.png'); ?>"
            title="Not yet tested" alt="Not yet tested" height="10"
            width="<?php echo $lRemaining; ?>" /><img
            id="wrong-tests-box" class="bordermiddle"
            src="<?php echo \get_file_path('icn/test_wrong.png'); ?>"
            title="Wrong" alt="Wrong" height="10"
            width="<?php echo $lWrong; ?>" /><img
            id="correct-tests-box" class="borderr"
            src="<?php echo \get_file_path('icn/test_correct.png'); ?>"
            title="Correct" alt="Correct" height="10"
            width="<?php echo $lCorrect; ?>" />
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

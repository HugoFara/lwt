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
 * @link     https://hugofara.github.io/lwt/developer/api
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
<?php
$elapsedLabel = htmlspecialchars(__('review.progress.elapsed_time'), ENT_QUOTES, 'UTF-8');
$notYetLabel = htmlspecialchars(__('review.progress.not_yet_reviewed'), ENT_QUOTES, 'UTF-8');
$wrongLabel = htmlspecialchars(__('review.progress.wrong'), ENT_QUOTES, 'UTF-8');
$correctLabel = htmlspecialchars(__('review.progress.correct'), ENT_QUOTES, 'UTF-8');
$totalLabel = htmlspecialchars(__('review.progress.total_reviews'), ENT_QUOTES, 'UTF-8');
?>
<footer id="footer">
    <span class="test-footer-stat">
        <?php echo IconHelper::render(
            'clock',
            ['title' => __('review.progress.elapsed_time'), 'alt' => __('review.progress.elapsed_time')]
        ); ?>
        <span id="timer" title="<?php echo $elapsedLabel; ?>"></span>
    </span>
    <span class="test-footer-stat test-progress-bar">
        <span id="not-tested-box" class="test-progress-notyet"
            title="<?php echo $notYetLabel; ?>"
            style="width:<?php echo $lRemaining; ?>px"></span><span
            id="wrong-tests-box" class="test-progress-wrong"
            title="<?php echo $wrongLabel; ?>"
            style="width:<?php echo $lWrong; ?>px"></span><span
            id="correct-tests-box" class="test-progress-correct"
            title="<?php echo $correctLabel; ?>"
            style="width:<?php echo $lCorrect; ?>px"></span>
    </span>
    <span class="test-footer-stat">
        <span title="<?php echo $totalLabel; ?>" id="total_tests"><?php echo $total; ?></span>
        =
        <span class="todosty" title="<?php echo $notYetLabel; ?>"
            id="not-tested"><?php echo $remainingInt; ?></span>
        +
        <span class="donewrongsty" title="<?php echo $wrongLabel; ?>"
            id="wrong-tests"><?php echo $wrongInt; ?></span>
        +
        <span class="doneoksty" title="<?php echo $correctLabel; ?>"
            id="correct-tests"><?php echo $correctInt; ?></span>
    </span>
</footer>

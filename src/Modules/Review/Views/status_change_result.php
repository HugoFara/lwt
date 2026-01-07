<?php declare(strict_types=1);
/**
 * Status Change Result View - Shows result after status update
 *
 * Variables expected:
 * - $wordText: string - Word text
 * - $oldStatus: int - Previous status
 * - $newStatus: int - New status
 * - $oldScore: int - Previous score
 * - $newScore: int - New score
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

use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\View\Helper\StatusHelper;

PageLayoutHelper::renderPageStart("Term: " . $wordText, false);

if ($oldStatus == $newStatus) {
    echo '<p>Status ' . StatusHelper::buildColoredMessage(
        $newStatus,
        StatusHelper::getName($newStatus),
        StatusHelper::getAbbr($newStatus)
    ) . ' not changed.</p>';
} else {
    echo '<p>Status changed from ' . StatusHelper::buildColoredMessage(
        $oldStatus,
        StatusHelper::getName($oldStatus),
        StatusHelper::getAbbr($oldStatus)
    ) . ' to ' . StatusHelper::buildColoredMessage(
        $newStatus,
        StatusHelper::getName($newStatus),
        StatusHelper::getAbbr($newStatus)
    ) . '.</p>';
}

echo "<p>Old score was $oldScore, new score is now $newScore.</p>";

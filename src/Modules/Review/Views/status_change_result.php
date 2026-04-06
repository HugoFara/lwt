<?php

declare(strict_types=1);

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
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 *
 * @var string $wordText
 * @var int $oldStatus
 * @var int $newStatus
 * @var int $oldScore
 * @var int $newScore
 */

namespace Lwt\Views\Review;

use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Modules\Vocabulary\Application\Helpers\StatusHelper;

// Validate and cast injected variables
assert(isset($wordText) && is_string($wordText));
assert(isset($oldStatus) && is_int($oldStatus));
assert(isset($newStatus) && is_int($newStatus));
assert(isset($oldScore) && is_int($oldScore));
assert(isset($newScore) && is_int($newScore));

PageLayoutHelper::renderPageStart("Term: " . $wordText, false);

$newStatusBadge = StatusHelper::buildColoredMessage(
    $newStatus,
    StatusHelper::getName($newStatus),
    StatusHelper::getAbbr($newStatus)
);

if ($oldStatus == $newStatus) {
    echo '<p>' . __('review.status.unchanged', ['status' => $newStatusBadge]) . '</p>';
} else {
    $oldStatusBadge = StatusHelper::buildColoredMessage(
        $oldStatus,
        StatusHelper::getName($oldStatus),
        StatusHelper::getAbbr($oldStatus)
    );
    echo '<p>' . __('review.status.changed', [
        'old' => $oldStatusBadge,
        'new' => $newStatusBadge,
    ]) . '</p>';
}

echo '<p>' . __('review.status.score_summary', [
    'old' => (string) $oldScore,
    'new' => (string) $newScore,
]) . '</p>';

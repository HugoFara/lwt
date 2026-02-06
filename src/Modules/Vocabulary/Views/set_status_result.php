<?php

/**
 * Set Status Result View - Shows result after setting word status
 *
 * Variables expected:
 * - $wid: int - Word ID
 * - $textId: int - Text ID
 * - $ord: int - Word order position
 * - $status: int - New word status
 * - $term: \Lwt\Modules\Vocabulary\Domain\Term - Term entity
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Views;

use Lwt\Core\StringUtils;
use Lwt\Modules\Text\Application\Services\TextStatisticsService;
use Lwt\Modules\Vocabulary\Application\Helpers\StatusHelper;

// Type assertions for variables passed from controller
assert(is_int($wid));
assert(is_int($textId));
assert(is_int($ord));
assert(is_int($status));
assert($term instanceof \Lwt\Modules\Vocabulary\Domain\Term);

$hex = StringUtils::toClassName($term->textLowercase());
?>
<p>Status: <?php echo StatusHelper::getColoredMessage($status); ?></p>

<script type="application/json" data-lwt-set-status-result-config>
<?php echo json_encode([
    'wid' => $wid,
    'hex' => $hex,
    'status' => $status,
    'translation' => $term->translation(),
    'text' => $term->text(),
    'textId' => $textId,
    'ord' => $ord,
    'todoContent' => (new TextStatisticsService())->getTodoWordsContent($textId)
], JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

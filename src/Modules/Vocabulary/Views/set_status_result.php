<?php declare(strict_types=1);
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

namespace Lwt\Modules\Vocabulary\Views;

use Lwt\Core\StringUtils;
use Lwt\Modules\Text\Application\Services\TextStatisticsService;

/** @var int $wid */
/** @var int $textId */
/** @var int $ord */
/** @var int $status */
/** @var \Lwt\Modules\Vocabulary\Domain\Term $term */

$hex = StringUtils::toClassName($term->textLowercase());
?>
<p>Status: <?php echo get_colored_status_msg($status); ?></p>

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

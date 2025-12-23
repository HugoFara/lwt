<?php declare(strict_types=1);
/**
 * Word Insert Well-known Result View - Shows result after marking word as well-known
 *
 * Variables expected:
 * - $term: string - Term text
 * - $wid: int - New word ID
 * - $hex: string - Hex class name for the term
 * - $textId: int - Text ID
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

namespace Lwt\Views\Word;

use Lwt\Services\TextStatisticsService;

?>
<p>OK, you know this term well!</p>

<script type="application/json" data-lwt-insert-wellknown-result-config>
<?php echo json_encode([
    'wid' => (int) $wid,
    'hex' => $hex,
    'term' => $term,
    'todoContent' => (new TextStatisticsService())->getTodoWordsContent($textId)
]); ?>
</script>

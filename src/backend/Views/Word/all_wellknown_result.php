<?php declare(strict_types=1);
/**
 * All Words Well-Known Result View
 *
 * Variables expected:
 * - $status: int - Status applied (98=ignored, 99=well-known)
 * - $count: int - Number of words modified
 * - $textId: int - Text ID
 * - $wordsData: array - Array of word data for DOM updates
 * - $useTooltips: bool - Whether tooltips are enabled
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
<p>
<?php
if ($status == 98) {
    if ($count > 1) {
        echo "Ignored all $count words!";
    } elseif ($count == 1) {
        echo "Ignored 1 word.";
    } else {
        echo "No new word ignored!";
    }
} else {
    if ($count > 1) {
        echo "You know all $count words well!";
    } elseif ($count == 1) {
        echo "1 new word added as known";
    } else {
        echo "No new known word added!";
    }
}
?>
</p>

<script type="application/json" data-lwt-all-wellknown-config>
<?php echo json_encode([
    'words' => $wordsData,
    'useTooltips' => $useTooltips,
    'todoContent' => (new TextStatisticsService())->getTodoWordsContent($textId)
]); ?>
</script>

<?php declare(strict_types=1);
/**
 * Word Status Change Result View - Shows result after changing word status
 *
 * Variables expected:
 * - $wid: int - Word ID
 * - $textId: int - Text ID
 * - $status: int - New status value
 * - $term: string - Term text
 * - $translation: string - Translation text
 * - $romanization: string - Romanization
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

$todoContent = (new TextStatisticsService())->getTodoWordsContent($textId);
?>
<p id='status_change_log'>Term status updating...</p>

<script type="application/json" id="word-status-config">
<?php echo json_encode([
    'wid' => $wid,
    'status' => $status,
    'term' => $term,
    'translation' => $translation,
    'romanization' => $romanization,
    'todoContent' => $todoContent
]); ?>
</script>

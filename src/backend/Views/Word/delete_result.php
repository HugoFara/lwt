<?php declare(strict_types=1);
/**
 * Word Delete Result View - Shows result after deleting a word
 *
 * Variables expected:
 * - $term: string - The deleted term text
 * - $wid: int - Word ID that was deleted
 * - $textId: int - Text ID
 * - $message: string - Result message
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
<p>OK, term deleted, now unknown (<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>).</p>

<script type="application/json" data-lwt-delete-result-config>
<?php echo json_encode([
    'wid' => (int) $wid,
    'term' => $term,
    'todoContent' => (new TextStatisticsService())->getTodoWordsContent($textId)
]); ?>
</script>

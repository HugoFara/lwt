<?php declare(strict_types=1);
/**
 * Bulk Save Result View - Shows result after saving bulk translated words
 *
 * Variables expected:
 * - $tid: int - Text ID
 * - $cleanUp: bool - Whether to clean up right frames
 * - $tooltipMode: int - Tooltip display mode (1 = show)
 * - $newWords: array - Array of newly created words with keys:
 *     - WoID: int - Word ID
 *     - WoTextLC: string - Lowercase word text
 *     - WoStatus: int - Word status
 *     - translation: string - Word translation
 *     - hex: string - Hex class name for CSS
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

use Lwt\Shared\Infrastructure\Database\Escaping;
use Lwt\Modules\Text\Application\Services\TextStatisticsService;
use Lwt\Shared\UI\Helpers\IconHelper;

?>
<p id="displ_message">
    <?php echo IconHelper::render('loader-2', ['class' => 'icon-spin', 'alt' => 'Loading...']); ?> Updating Texts
</p>

<script type="application/json" data-lwt-bulk-save-result-config>
<?php echo json_encode([
    'words' => $newWords,
    'useTooltip' => ($tooltipMode == 1),
    'cleanUp' => $cleanUp,
    'todoContent' => (new TextStatisticsService())->getTodoWordsContent($tid)
]); ?>
</script>

<?php declare(strict_types=1);
/**
 * Word Save Result View - Shows result after saving a word
 *
 * Variables expected:
 * - $message: string - Result message
 * - $success: bool - Whether save was successful
 * - $wid: int - Word ID (if successful)
 * - $textId: int - Text ID
 * - $hex: string - Hex class name for the term
 * - $translation: string - Translation text
 * - $status: int - Word status
 * - $romanization: string - Romanization
 * - $text: string - Original text
 * - $len: int - Word count (1 for single word)
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

use Lwt\Modules\Text\Application\Services\TextStatisticsService;

// Type assertions for variables passed from controller
assert(is_string($message));
assert(is_bool($success));
assert(is_int($wid));
assert(is_int($textId));
assert(is_string($hex));
assert(is_string($translation));
assert(is_int($status));
assert(is_string($romanization));
assert(is_string($text));
assert(is_int($len));

?>
<p><?php echo $message; ?></p>

<?php if ($success && $len == 1):
    $tagList = \Lwt\Modules\Tags\Application\TagsFacade::getWordTagList($wid, false);
?>
<script type="application/json" data-lwt-save-result-config>
<?php echo json_encode([
    'wid' => $wid,
    'status' => $status,
    'translation' => $translation . ($tagList !== '' ? ' [' . $tagList . ']' : ''),
    'romanization' => $romanization,
    'text' => $text,
    'hex' => $hex,
    'textId' => $textId,
    'todoContent' => (new TextStatisticsService())->getTodoWordsContent($textId)
], JSON_HEX_TAG | JSON_HEX_AMP); ?>
</script>
<?php endif; ?>

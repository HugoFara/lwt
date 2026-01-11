<?php

declare(strict_types=1);

/**
 * Word Edit Result View - Shows result after saving/updating a word
 *
 * Variables expected:
 * - $message: string - Result message
 * - $wid: int - Word ID
 * - $textId: int - Text ID
 * - $hex: string|null - Hex class name for the term (for new words)
 * - $translation: string - Translation text
 * - $status: int - Word status
 * - $oldStatus: int - Previous status (for updates)
 * - $romanization: string - Romanization
 * - $text: string - Original text
 * - $fromAnn: string - From annotation flag
 * - $isNew: bool - Whether this is a new word
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

use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Modules\Text\Application\Services\TextStatisticsService;

// Type assertions for variables passed from controller
assert(is_string($message));
assert(is_int($wid));
assert(is_int($textId));
assert($hex === null || is_string($hex));
assert(is_string($translation));
assert(is_int($status));
assert(is_int($oldStatus));
assert(is_string($romanization));
assert(is_string($text));
assert(is_string($fromAnn));
assert(is_bool($isNew));
/** @var string $textlc */

$tagList = TagsFacade::getWordTagList($wid, false);
$tagFormatted = $tagList !== '' ? ' [' . str_replace(',', ', ', $tagList) . ']' : '';

$config = [
    'wid' => $wid,
    'status' => $status,
    'translation' => $translation . $tagFormatted,
    'romanization' => $romanization,
    'text' => $text,
    'textId' => $textId,
    'isNew' => $isNew
];

if ($fromAnn === "") {
    // Normal mode
    if ($isNew) {
        $config['hex'] = $hex;
    } else {
        $config['oldStatus'] = $oldStatus;
    }
    $config['todoContent'] = (new TextStatisticsService())->getTodoWordsContent($textId);
} else {
    // Annotation mode
    $config['fromAnn'] = (int)$fromAnn;
    /** @psalm-suppress PossiblyUndefinedVariable */
    $config['textlc'] = $textlc;
}

?>
<p>OK: <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>

<script type="application/json" data-lwt-edit-result-config>
<?php echo json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

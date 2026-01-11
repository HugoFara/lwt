<?php

/**
 * Hover Save Result View - Shows result after saving a word via hover
 *
 * Variables expected:
 * - $word: string - The word text (SQL-escaped)
 * - $wordRaw: string - The raw word text
 * - $status: int - Word status
 * - $translation: string - Translation text
 * - $wid: int - Word ID
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
 *
 * @psalm-suppress PossiblyUndefinedVariable - Variables are set by the including controller
 */

declare(strict_types=1);

namespace Lwt\Views\Word;

use Lwt\Modules\Text\Application\Services\TextStatisticsService;
use Lwt\View\Helper\StatusHelper;

// Type assertions for view variables
$status = (int) ($status ?? 0);
$translation = (string) ($translation ?? '');
$textId = (int) ($textId ?? 0);

?>
<p>Status: <?php echo StatusHelper::getColoredMessage($status); ?></p><br />
<?php if ($translation != '*') : ?>
<p>Translation: <b><?php echo htmlspecialchars($translation, ENT_QUOTES, 'UTF-8'); ?></b></p>
<?php endif; ?>

<script type="application/json" data-lwt-hover-save-result-config>
<?php echo json_encode([
    'wid' => $wid,
    'hex' => $hex,
    'status' => $status,
    'translation' => $translation,
    'wordRaw' => $wordRaw,
    'todoContent' => (new TextStatisticsService())->getTodoWordsContent($textId)
], JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

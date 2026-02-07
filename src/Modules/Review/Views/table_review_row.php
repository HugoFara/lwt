<?php

declare(strict_types=1);

/**
 * Table Test Row View - Single row in test table
 *
 * Variables expected:
 * - $word: array - Word record
 * - $regexWord: string - Regex for word characters
 * - $textSize: int - Text size percentage
 * - $rtl: bool - Right-to-left script
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
 * @var array<string, mixed> $word
 * @var string $regexWord
 * @var int $textSize
 * @var bool $rtl
 */

namespace Lwt\Views\Review;

use Lwt\Shared\Infrastructure\Utilities\StringUtils;
use Lwt\Modules\Vocabulary\Application\Services\ExportService;
use Lwt\Modules\Vocabulary\Application\Helpers\StatusHelper;
use Lwt\Shared\UI\Helpers\IconHelper;

// Validate and cast injected variables
assert(isset($word) && is_array($word));
assert(isset($regexWord) && is_string($regexWord));
assert(isset($textSize) && is_int($textSize));
assert(isset($rtl) && is_bool($rtl));

$isRtl = $rtl;
$span1 = $isRtl ? '<span dir="rtl">' : '';
$span2 = $isRtl ? '</span>' : '';

// Extract typed values from word array
$woId = (int) ($word['WoID'] ?? 0);
$woText = (string) ($word['WoText'] ?? '');
$woTranslation = (string) ($word['WoTranslation'] ?? '');
$woRomanization = (string) ($word['WoRomanization'] ?? '');
$woSentence = (string) ($word['WoSentence'] ?? '');
$woStatus = (int) ($word['WoStatus'] ?? 0);
$woScore = (int) ($word['Score'] ?? 0);

$sent = htmlspecialchars(ExportService::replaceTabNewline($woSentence), ENT_QUOTES, 'UTF-8');
$sent1 = str_replace(
    "{",
    ' <b>[',
    str_replace(
        "}",
        ']</b> ',
        ExportService::maskTermInSentence($sent, $regexWord)
    )
);
?>
<tr>
    <td class="has-text-centered" nowrap="nowrap">
        <a href="edit_tword.php?wid=<?php echo $woId; ?>" target="ro"
            data-action="show-right-frames">
            <?php echo IconHelper::render('file-pen-line', ['title' => 'Edit Term', 'alt' => 'Edit Term']); ?>
        </a>
    </td>
    <td class="has-text-centered" nowrap="nowrap">
        <span id="STAT<?php echo $woId; ?>">
            <?php echo StatusHelper::buildReviewTableControls(
                $woScore,
                $woStatus,
                $woId,
                StatusHelper::getAbbr($woStatus)
            ); ?>
        </span>
    </td>
    <td class="has-text-centered" style="font-size:<?php echo $textSize; ?>%;">
        <?php echo $span1; ?>
        <span id="TERM<?php echo $woId; ?>">
            <?php echo \htmlspecialchars($woText, ENT_QUOTES, 'UTF-8'); ?>
        </span>
        <?php echo $span2; ?>
    </td>
    <td class="has-text-centered">
        <span id="TRAN<?php echo $woId; ?>">
            <?php echo StringUtils::parseInlineMarkdown($woTranslation); ?>
        </span>
    </td>
    <td class="has-text-centered">
        <span id="ROMA<?php echo $woId; ?>">
            <?php echo \htmlspecialchars($woRomanization, ENT_QUOTES, 'UTF-8'); ?>
        </span>
    </td>
    <td class="has-text-centered test-sentence-cell">
        <?php echo $span1; ?>
        <span id="SENT<?php echo $woId; ?>"><?php echo $sent1; ?></span>
        <?php echo $span2; ?>
    </td>
</tr>

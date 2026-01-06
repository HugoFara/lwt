<?php declare(strict_types=1);
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
 */

namespace Lwt\Views\Test;

use Lwt\Core\StringUtils;
use Lwt\Modules\Vocabulary\Application\Services\ExportService;
use Lwt\View\Helper\StatusHelper;
use Lwt\Shared\UI\Helpers\IconHelper;

// Type assertions for view variables
/** @var array $wordInput */
$wordInput = $word ?? [];
/** @var array{WoID: int, WoText: string, WoTranslation: string, WoRomanization: string, WoSentence: string, WoStatus: int, Score: int} $word */
$word = array_merge(['WoID' => 0, 'WoText' => '', 'WoTranslation' => '', 'WoRomanization' => '', 'WoSentence' => '', 'WoStatus' => 0, 'Score' => 0], $wordInput);
$regexWord = (string) ($regexWord ?? '');
$textSize = (int) ($textSize ?? 100);

$span1 = $rtl ? '<span dir="rtl">' : '';
$span2 = $rtl ? '</span>' : '';

$sent = htmlspecialchars(ExportService::replaceTabNewline($word['WoSentence']), ENT_QUOTES, 'UTF-8');
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
    <td class="td1 center" nowrap="nowrap">
        <a href="edit_tword.php?wid=<?php echo $word['WoID']; ?>" target="ro"
            data-action="show-right-frames">
            <?php echo IconHelper::render('file-pen-line', ['title' => 'Edit Term', 'alt' => 'Edit Term']); ?>
        </a>
    </td>
    <td class="td1 center" nowrap="nowrap">
        <span id="STAT<?php echo $word['WoID']; ?>">
            <?php echo StatusHelper::buildTestTableControls(
                $word['Score'],
                $word['WoStatus'],
                $word['WoID'],
                StatusHelper::getAbbr($word['WoStatus'])
            ); ?>
        </span>
    </td>
    <td class="td1 center" style="font-size:<?php echo $textSize; ?>%;">
        <?php echo $span1; ?>
        <span id="TERM<?php echo $word['WoID']; ?>">
            <?php echo \htmlspecialchars($word['WoText'], ENT_QUOTES, 'UTF-8'); ?>
        </span>
        <?php echo $span2; ?>
    </td>
    <td class="td1 center">
        <span id="TRAN<?php echo $word['WoID']; ?>">
            <?php echo StringUtils::parseInlineMarkdown($word['WoTranslation']); ?>
        </span>
    </td>
    <td class="td1 center">
        <span id="ROMA<?php echo $word['WoID']; ?>">
            <?php echo \htmlspecialchars($word['WoRomanization'], ENT_QUOTES, 'UTF-8'); ?>
        </span>
    </td>
    <td class="td1 center test-sentence-cell">
        <?php echo $span1; ?>
        <span id="SENT<?php echo $word['WoID']; ?>"><?php echo $sent1; ?></span>
        <?php echo $span2; ?>
    </td>
</tr>

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

use Lwt\Services\ExportService;
use Lwt\View\Helper\StatusHelper;

$span1 = $rtl ? '<span dir="rtl">' : '';
$span2 = $rtl ? '</span>' : '';

$sent = \tohtml(ExportService::replaceTabNewline($word['WoSentence'] ?? ''));
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
            <img src="/assets/icons/sticky-note--pencil.png" title="Edit Term" alt="Edit Term" />
        </a>
    </td>
    <td class="td1 center" nowrap="nowrap">
        <span id="STAT<?php echo $word['WoID']; ?>">
            <?php echo StatusHelper::buildTestTableControls(
                $word['Score'],
                $word['WoStatus'],
                $word['WoID'],
                StatusHelper::getAbbr($word['WoStatus']),
                \get_file_path('assets/icons/placeholder.png')
            ); ?>
        </span>
    </td>
    <td class="td1 center" style="font-size:<?php echo $textSize; ?>%;">
        <?php echo $span1; ?>
        <span id="TERM<?php echo $word['WoID']; ?>">
            <?php echo \tohtml($word['WoText']); ?>
        </span>
        <?php echo $span2; ?>
    </td>
    <td class="td1 center">
        <span id="TRAN<?php echo $word['WoID']; ?>">
            <?php echo \tohtml($word['WoTranslation']); ?>
        </span>
    </td>
    <td class="td1 center">
        <span id="ROMA<?php echo $word['WoID']; ?>">
            <?php echo \tohtml($word['WoRomanization'] ?? ''); ?>
        </span>
    </td>
    <td class="td1 center test-sentence-cell">
        <?php echo $span1; ?>
        <span id="SENT<?php echo $word['WoID']; ?>"><?php echo $sent1; ?></span>
        <?php echo $span2; ?>
    </td>
</tr>

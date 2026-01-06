<?php declare(strict_types=1);
/**
 * Long Text Import Check/Preview View
 *
 * Variables expected:
 * - $langId: int - Language ID
 * - $title: string - Text title
 * - $sourceUri: string - Source URI
 * - $textTags: string|null - JSON encoded text tags
 * - $texts: array - Array of text arrays (sentences per text)
 * - $textCount: int - Number of texts
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

namespace Lwt\Views\Text;

// JavaScript moved to forms/form_initialization.ts (uses data-lwt-form-check and data-lwt-dirty)

// Type assertions for view variables
$langId = (int) ($langId ?? 0);
$title = (string) ($title ?? '');
$sourceUri = (string) ($sourceUri ?? '');
$textTags = isset($textTags) ? (string) $textTags : null;
/** @var array<int, array<int, string>> $texts */
$texts = $texts ?? [];
$textCount = (int) ($textCount ?? 0);
$scrdir = (string) ($scrdir ?? '');

$plural = ($textCount == 1 ? '' : 's');
$shorter = ($textCount == 1 ? ' ' : ' shorter ');
?>
<form enctype="multipart/form-data" action="/text/import-long" method="post" data-lwt-form-check="true" data-lwt-dirty>
<input type="hidden" name="LgID" value="<?php echo $langId; ?>" />
<input type="hidden" name="TxTitle" value="<?php echo \htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" />
<input type="hidden" name="TxSourceURI" value="<?php echo \htmlspecialchars($sourceUri, ENT_QUOTES, 'UTF-8'); ?>" />
<input type="hidden" name="TextTags" value="<?php echo \htmlspecialchars($textTags ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
<input type="hidden" name="TextCount" value="<?php echo $textCount; ?>" />
<table class="tab1" cellspacing="0" cellpadding="5">
    <tr>
        <td class="td1" colspan="2">
            <?php echo "This long text will be split into " . $textCount . $shorter . "text" . $plural . " - as follows:"; ?>
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="Cancel" data-action="cancel-navigate" data-url="index.php" />
            <span class="nowrap"></span>
            <input type="button" value="Go Back" data-action="cancel-back" />
            <span class="nowrap"></span>
            <input type="submit" name="op" value="Create <?php echo $textCount; ?> text<?php echo $plural; ?>" />
        </td>
    </tr>
    <?php
    $textNo = -1;
    foreach ($texts as $item) {
        $textNo++;
        $textString = str_replace("¶", "\n", implode(" ", $item));
        $bytes = strlen($textString);
        ?>
    <tr>
        <td class="td1 right">
            <b>Text <?php echo $textNo + 1; ?>:</b>
            <br /><br /><br />
            Length:<br /><?php echo $bytes; ?><br />Bytes
        </td>
        <td class="td1">
            <textarea readonly="readonly"
                <?php echo $scrdir; ?>
            name="text[<?php echo $textNo; ?>]" cols="60" rows="10"><?php echo $textString; ?></textarea>
        </td>
    </tr>
        <?php
    }
    ?>
</table>
</form>

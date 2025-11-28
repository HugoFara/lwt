<?php

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

$plural = ($textCount == 1 ? '' : 's');
$shorter = ($textCount == 1 ? ' ' : ' shorter ');
?>
<script type="text/javascript">
//<![CDATA[
    $(document).ready(lwtFormCheck.askBeforeExit);
    lwtFormCheck.makeDirty();
//]]>
</script>
<form enctype="multipart/form-data" action="/text/import-long" method="post">
<input type="hidden" name="LgID" value="<?php echo $langId; ?>" />
<input type="hidden" name="TxTitle" value="<?php echo tohtml($title); ?>" />
<input type="hidden" name="TxSourceURI" value="<?php echo tohtml($sourceUri); ?>" />
<input type="hidden" name="TextTags" value="<?php echo tohtml($textTags); ?>" />
<input type="hidden" name="TextCount" value="<?php echo $textCount; ?>" />
<table class="tab1" cellspacing="0" cellpadding="5">
    <tr>
        <td class="td1" colspan="2">
            <?php echo "This long text will be split into " . $textCount . $shorter . "text" . $plural . " - as follows:"; ?>
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='index.php';}" />
            <span class="nowrap"></span>
            <input type="button" value="Go Back" onclick="{lwtFormCheck.resetDirty(); history.back();}" />
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
                <?php echo getScriptDirectionTag($langId); ?>
            name="text[<?php echo $textNo; ?>]" cols="60" rows="10"><?php echo $textString; ?></textarea>
        </td>
    </tr>
        <?php
    }
    ?>
</table>
</form>

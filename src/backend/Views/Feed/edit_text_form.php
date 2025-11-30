<?php declare(strict_types=1);
/**
 * Feed Edit Text Form View
 *
 * Renders the form for editing feed items before creating texts.
 *
 * Variables expected:
 * - $texts: array of text data (TxTitle, TxText, TxSourceURI, TxAudioURI)
 * - $row: array feed link and feed data (NfLgID, NfID)
 * - $count: int starting form counter (passed by reference)
 * - $tagName: string tag name for the text
 * - $nfId: int feed ID
 * - $maxTexts: int maximum texts setting
 * - $languages: array of language records
 * - $scrdir: string script direction HTML attribute
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

namespace Lwt\Views\Feed;

foreach ($texts as $text):
?>
<table class="tab3" cellspacing="0" cellpadding="5">
    <tr>
        <td class="td1 right">
            <input class="markcheck" type="checkbox" name="Nf_count[<?php echo $count; ?>]" value="<?php echo $count; ?>" checked="checked" />
            &nbsp; &nbsp; &nbsp; Title:
        </td>
        <td class="td1">
            <input type="text" class="notempty" name="feed[<?php echo $count; ?>][TxTitle]" value="<?php echo tohtml($text['TxTitle']); ?>" maxlength="200" size="60" />
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Language:</td>
        <td class="td1">
            <select name="feed[<?php echo $count; ?>][TxLgID]" class="notempty setfocus">
                <?php foreach ($languages as $rowLang): ?>
                <option value="<?php echo $rowLang['LgID']; ?>"<?php
                    if ($row['NfLgID'] === $rowLang['LgID']) {
                        echo ' selected="selected"';
                    }
                ?>><?php echo $rowLang['LgName']; ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Text:</td>
        <td class="td1">
            <textarea
                <?php echo $scrdir; ?>
            name="feed[<?php echo $count; ?>][TxText]" class="notempty checkbytes"
            cols="60" rows="20"
            ><?php echo tohtml($text['TxText']); ?></textarea>
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Source URI:</td>
        <td class="td1">
            <input type="text" class="checkurl"
            name="feed[<?php echo $count; ?>][TxSourceURI]"
            value="<?php echo $text['TxSourceURI']; ?>" maxlength="1000"
            size="60" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Tags:</td>
        <td class="td1">
            <ul name="feed[<?php echo $count; ?>][TagList][]"
            style="width:340px;margin-top:0px;margin-bottom:0px;margin-left:2px;">
                <li>
                    <?php echo $tagName; ?>
                </li>
            </ul>
            <input type="hidden" name="feed[<?php echo $count; ?>][Nf_ID]" value="<?php echo $nfId; ?>" />
            <input type="hidden" name="feed[<?php echo $count; ?>][Nf_Max_Texts]" value="<?php echo $maxTexts; ?>" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Audio-URI:</td>
        <td class="td1">
            <input type="text" name="feed[<?php echo $count; ?>][TxAudioURI]" value="<?php echo $text['TxAudioURI']; ?>" maxlength="200" size="60" />
        </td>
    </tr>
</table>
<?php
    $count++;
endforeach;

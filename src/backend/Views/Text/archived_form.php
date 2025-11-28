<?php

/**
 * Archived Text Edit Form View
 *
 * Variables expected:
 * - $textId: int - Archived text ID
 * - $record: array - Archived text record with keys: AtLgID, AtTitle, AtText, AtAudioURI, AtSourceURI, annotlen
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
 * @psalm-suppress UndefinedVariable - Variables are set by the including controller
 */

namespace Lwt\Views\Text;

/** @var int $textId */
/** @var array $record */

?>
<script type="text/javascript" charset="utf-8">
    $(document).ready(lwtFormCheck.askBeforeExit);
</script>

<h2>Edit Archived Text</h2>

<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>#rec<?php echo $textId; ?>" method="post">
    <input type="hidden" name="AtID" value="<?php echo $textId; ?>" />
    <table class="tab3" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1 right">Language:</td>
            <td class="td1">
                <select name="AtLgID" class="notempty setfocus">
                    <?php echo get_languages_selectoptions($record['AtLgID'], "[Choose...]"); ?>
                </select>
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
            <td class="td1 right">Title:</td>
            <td class="td1">
                <input type="text" class="notempty checkoutsidebmp" data_info="Title" name="AtTitle" value="<?php echo tohtml($record['AtTitle']); ?>" maxlength="200" size="60" />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
            <td class="td1 right">Text:</td>
            <td class="td1">
                <textarea name="AtText" class="notempty checkbytes checkoutsidebmp" data_maxlength="65000" data_info="Text" cols="60" rows="20"><?php echo tohtml($record['AtText']); ?></textarea>
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
            <td class="td1 right">Ann.Text:</td>
            <td class="td1">
                <?php if ($record['annotlen']): ?>
                <img src="/assets/icons/tick.png" title="With Annotation" alt="With Annotation" /> Exists - May be partially or fully lost if you change the text!
                <?php else: ?>
                <img src="/assets/icons/cross.png" title="No Annotation" alt="No Annotation" /> - None
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Source URI:</td>
            <td class="td1">
                <input type="text" class="checkurl checkoutsidebmp" data_info="Source URI" name="AtSourceURI" value="<?php echo tohtml($record['AtSourceURI']); ?>" maxlength="1000" size="60" />
            </td>
        </tr>
        <tr>
            <td class="td1 right">Tags:</td>
            <td class="td1">
                <?php echo getArchivedTextTags($textId); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Audio-URI:</td>
            <td class="td1">
                <input type="text" class="checkoutsidebmp" data_info="Audio-URI" name="AtAudioURI" value="<?php echo tohtml($record['AtAudioURI']); ?>" maxlength="200" size="60" />
                <span id="mediaselect"><?php echo \selectmediapath('AtAudioURI'); ?></span>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">
                <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='/text/archived#rec<?php echo $textId; ?>';}" />
                <input type="submit" name="op" value="Change" />
            </td>
        </tr>
    </table>
</form>

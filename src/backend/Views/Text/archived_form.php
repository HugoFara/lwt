<?php declare(strict_types=1);
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

use Lwt\View\Helper\IconHelper;

/** @var int $textId */
/** @var array $record */

// JavaScript moved to forms/form_initialization.ts (auto-detects form.validate)

?>
<h2>Edit Archived Text</h2>

<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>#rec<?php echo $textId; ?>" method="post">
    <input type="hidden" name="AtID" value="<?php echo $textId; ?>" />
    <table class="tab3" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1 right">Language:</td>
            <td class="td1">
                <select name="AtLgID" class="notempty setfocus">
                    <?php echo \Lwt\View\Helper\SelectOptionsBuilder::forLanguages($languages, $record['AtLgID'], '[Choose...]'); ?>
                </select>
                <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Title:</td>
            <td class="td1">
                <input type="text" class="notempty checkoutsidebmp" data_info="Title" name="AtTitle" value="<?php echo htmlspecialchars($record['AtTitle'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="200" size="60" />
                <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Text:</td>
            <td class="td1">
                <textarea name="AtText" class="notempty checkbytes checkoutsidebmp" data_maxlength="65000" data_info="Text" cols="60" rows="20"><?php echo htmlspecialchars($record['AtText'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Ann.Text:</td>
            <td class="td1">
                <?php if ($record['annotlen']): ?>
                <?php echo IconHelper::render('check', ['title' => 'With Annotation', 'alt' => 'With Annotation']); ?> Exists - May be partially or fully lost if you change the text!
                <?php else: ?>
                <?php echo IconHelper::render('x', ['title' => 'No Annotation', 'alt' => 'No Annotation']); ?> - None
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Source URI:</td>
            <td class="td1">
                <input type="text" class="checkurl checkoutsidebmp" data_info="Source URI" name="AtSourceURI" value="<?php echo htmlspecialchars($record['AtSourceURI'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="1000" size="60" />
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
                <input type="text" class="checkoutsidebmp" data_info="Audio-URI" name="AtAudioURI" value="<?php echo htmlspecialchars($record['AtAudioURI'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="200" size="60" />
                <span id="mediaselect"><?php echo \selectmediapath('AtAudioURI'); ?></span>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">
                <input type="button" value="Cancel" data-action="cancel-navigate" data-url="/text/archived#rec<?php echo $textId; ?>" />
                <input type="submit" name="op" value="Change" />
            </td>
        </tr>
    </table>
</form>

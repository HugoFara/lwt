<?php declare(strict_types=1);
/**
 * Text Edit Form View - Display form for creating/editing texts
 *
 * Variables expected:
 * - $textId: int - Text ID (0 for new text)
 * - $text: Lwt\Classes\Text - Text object
 * - $annotated: bool - Whether the text has annotations
 * - $languageData: array - Mapping of language ID to language code
 * - $isNew: bool - Whether this is a new text
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
/** @var \Lwt\Classes\Text $text */
/** @var bool $annotated */
/** @var array $languageData */
/** @var array $languages */
/** @var bool $isNew */

use Lwt\View\Helper\SelectOptionsBuilder;
use Lwt\View\Helper\IconHelper;

?>
<h2>
    <?php echo $isNew ? "New" : "Edit"; ?> Text
    <a target="_blank" href="docs/info.html#howtotext">
        <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
    </a>
</h2>
<script type="application/json" id="text-edit-config">
<?php echo json_encode(['languageData' => $languageData]); ?>
</script>
<div class="flex-spaced">
    <div style="<?php echo $isNew ? "display: none" : ''; ?>">
        <a href="/texts?new=1">
            <?php echo IconHelper::render('circle-plus', ['alt' => 'New']); ?>
            New Text
        </a>
    </div>
    <div>
        <a href="/text/import-long">
            <?php echo IconHelper::render('circle-plus', ['alt' => 'New']); ?>
            Long Text Import
        </a>
    </div>
    <div>
        <a href="/feeds?page=1&amp;check_autoupdate=1">
            <?php echo IconHelper::render('circle-plus', ['alt' => 'New']); ?>
            Newsfeed Import
        </a>
    </div>
    <div>
        <a href="/texts?query=&amp;page=1">
            <?php echo IconHelper::render('archive', ['alt' => 'Active']); ?>
            Active Texts
        </a>
    </div>
    <div style="<?php echo $isNew ? "" : 'display: none'; ?>">
        <a href="/text/archived?query=&amp;page=1">
            <?php echo IconHelper::render('archive-x', ['alt' => 'Archived']); ?>
            Archived Texts
        </a>
    </div>
</div>
<form class="validate" method="post"
action="/texts<?php echo $isNew ? '' : '#rec' . $textId; ?>" >
    <input type="hidden" name="TxID" value="<?php echo $textId; ?>" />
    <table class="tab1" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1 right">Language:</td>
            <td class="td1">
                <select name="TxLgID" id="TxLgID" class="notempty setfocus"
                data-action="change-language">
                <?php echo SelectOptionsBuilder::forLanguages($languages, $text->lgid, "[Choose...]"); ?>
                </select>
                <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Title:</td>
            <td class="td1">
                <input type="text" class="notempty checkoutsidebmp respinput"
                data_info="Title" name="TxTitle" id="TxTitle"
                value="<?php echo \htmlspecialchars($text->title ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="200" />
                <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">
                Text:<br /><br />(max.<br />65,000<br />bytes)
            </td>
            <td class="td1">
            <textarea <?php echo $scrdir; ?>
            name="TxText" id="TxText"
            class="notempty checkbytes checkoutsidebmp respinput"
            data_maxlength="65000" data_info="Text" rows="20"
            ><?php echo \htmlspecialchars($text->text ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
            </td>
        </tr>
        <tr <?php echo $isNew ? 'style="display: none;"' : ''; ?>>
            <td class="td1 right">Ann. Text:</td>
            <td class="td1">
                <?php
                if ($annotated) {
                    echo IconHelper::render('check', ['title' => 'With Improved Annotation', 'alt' => 'With Improved Annotation']) . ' ' .
                    'Exists - May be partially or fully lost if you change the text!<br />' .
                    '<input type="button" value="Print/Edit..." data-action="navigate" data-url="/text/print?text=' .
                    $textId . '" />';
                } else {
                    echo IconHelper::render('x', ['title' => 'No Improved Annotation', 'alt' => 'No Improved Annotation']) . ' ' .
                    '- None | <input type="button" value="Create/Print..." data-action="navigate" data-url="print_impr_text.php?edit=1&amp;text=' .
                    $textId . '" />';
                }
                ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Source URI:</td>
            <td class="td1">
                <input type="url" class="checkurl checkoutsidebmp respinput"
                data_info="Source URI" name="TxSourceURI"
                value="<?php echo \htmlspecialchars($text->source ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                maxlength="1000" />
            </td>
        </tr>
        <tr>
            <td class="td1 right">Tags:</td>
            <td class="td1">
                <?php echo \Lwt\Services\TagService::getTextTagsHtml($textId); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right" title="A soundtrack or a video to be display while reading">
                Media URI:
            </td>
            <td class="td1">
                <input type="text" class="checkoutsidebmp respinput"
                data_info="Audio-URI" name="TxAudioURI" maxlength="2048"
                value="<?php echo \htmlspecialchars($text->media_uri ?? '', ENT_QUOTES, 'UTF-8'); ?>"  />
                <span id="mediaselect">
                    <?php echo \selectmediapath('TxAudioURI'); ?>
                </span>
            </td>
        </tr>
        <?php if ($isNew && defined('YT_API_KEY') && YT_API_KEY != null) {
            \Lwt\Text_From_Youtube\do_form_fragment();
        } ?>
        <tr>
            <td class="td1 right" colspan="2">
                <input type="button" value="Cancel"
                data-action="cancel-form" data-url="/texts<?php echo $isNew ? '' : '#rec' . $textId; ?>" />
                <input type="submit" name="op" value="Check" />
                <input type="submit" name="op"
                value="<?php echo $isNew ? 'Save' : 'Change'; ?>" />
                <input type="submit" name="op"
                value="<?php echo $isNew ? 'Save' : 'Change'; ?> and Open" />
            </td>
        </tr>
    </table>
</form>
<?php if ($isNew && defined('YT_API_KEY') && YT_API_KEY != null) {
    \Lwt\Text_From_Youtube\do_js();
} ?>

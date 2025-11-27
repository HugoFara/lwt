<?php

/**
 * Tag Form View - New/Edit tag form
 *
 * Variables expected:
 * - $mode: 'new' or 'edit'
 * - $tag: array with 'id', 'text', 'comment' (for edit mode, null for new)
 * - $service: TagService instance
 * - $formFieldPrefix: 'Tg' or 'T2'
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

namespace Lwt\Views\Tags;

/** @var string $mode */
/** @var array|null $tag */
/** @var \Lwt\Services\TagService $service */
/** @var string $formFieldPrefix */

$isEdit = $mode === 'edit';
$pageTitle = $isEdit ? 'Edit Tag' : 'New Tag';
$formName = $isEdit ? 'edittag' : 'newtag';
$actionUrl = $isEdit && $tag !== null ?
    $_SERVER['PHP_SELF'] . '#rec' . $tag['id'] :
    $_SERVER['PHP_SELF'];
$cancelUrl = $isEdit && $tag !== null ?
    $service->getBaseUrl() . '#rec' . $tag['id'] :
    $service->getBaseUrl();
$submitValue = $isEdit ? 'Change' : 'Save';

$tagText = $isEdit && $tag !== null ? tohtml($tag['text']) : '';
$tagComment = $isEdit && $tag !== null ? tohtml($tag['comment']) : '';

?>
<h2><?php echo $pageTitle; ?></h2>
<script type="text/javascript" charset="utf-8">
    $(document).ready(lwtFormCheck.askBeforeExit);
</script>
<form name="<?php echo $formName; ?>" class="validate" action="<?php echo $actionUrl; ?>" method="post">
<?php if ($isEdit && $tag !== null): ?>
<input type="hidden" name="<?php echo $formFieldPrefix; ?>ID" value="<?php echo $tag['id']; ?>" />
<?php endif; ?>
<table class="tab1" cellspacing="0" cellpadding="5">
    <tr>
        <td class="td1 right">Tag:</td>
        <td class="td1">
            <input class="notempty <?php echo $isEdit ? '' : 'setfocus '; ?>noblanksnocomma checkoutsidebmp respinput"
            type="text" name="<?php echo $formFieldPrefix; ?>Text" data_info="Tag"
            value="<?php echo $tagText; ?>" maxlength="20" size="20" />
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Comment:</td>
        <td class="td1">
            <textarea class="textarea-noreturn checklength checkoutsidebmp respinput"
            data_maxlength="200" data_info="Comment"
            name="<?php echo $formFieldPrefix; ?>Comment" cols="40" rows="3"><?php echo $tagComment; ?></textarea>
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="Cancel"
                onclick="{lwtFormCheck.resetDirty(); location.href='<?php echo $cancelUrl; ?>';}" />
            <input type="submit" name="op" value="<?php echo $submitValue; ?>" />
        </td>
    </tr>
</table>
</form>

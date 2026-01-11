<?php

/**
 * Tag Form View - New/Edit tag form
 *
 * Variables expected:
 * - $mode: 'new' or 'edit'
 * - $tag: array with 'id', 'text', 'comment' (for edit mode, null for new)
 * - $service: TagsFacade instance
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
 */

declare(strict_types=1);

namespace Lwt\Views\Tags;

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Modules\Tags\Application\TagsFacade;

// Type assertions for variables from controller extract()
$mode = (string) ($mode ?? 'new');
/** @var array{id: int, text: string, comment: string|null}|null $tag */
$tag = $tag ?? null;
/** @var TagsFacade $service */
$service = $service;
$formFieldPrefix = (string) ($formFieldPrefix ?? 'Tg');

$isEdit = $mode === 'edit';
$pageTitle = $isEdit ? 'Edit Tag' : 'New Tag';
$formName = $isEdit ? 'edittag' : 'newtag';
$phpSelf = htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8');
$actionUrl = $isEdit && $tag !== null ?
    $phpSelf . '#rec' . $tag['id'] :
    $phpSelf;
$cancelUrl = $isEdit && $tag !== null ?
    $service->getBaseUrl() . '#rec' . $tag['id'] :
    $service->getBaseUrl();
$submitValue = $isEdit ? 'Change' : 'Save';

$tagText = $isEdit && $tag !== null ? htmlspecialchars($tag['text'] ?? '', ENT_QUOTES, 'UTF-8') : '';
$tagComment = $isEdit && $tag !== null ? htmlspecialchars($tag['comment'] ?? '', ENT_QUOTES, 'UTF-8') : '';

?>
<h2 class="title is-4"><?php echo $pageTitle; ?></h2>

<form name="<?php echo $formName; ?>" class="validate" action="<?php echo $actionUrl; ?>" method="post"
      x-data="{
          tagText: '<?php echo $tagText; ?>',
          tagComment: '<?php echo $tagComment; ?>',
          charCount: <?php echo strlen($tagComment); ?>
      }">
    <?php if ($isEdit && $tag !== null) : ?>
    <input type="hidden" name="<?php echo $formFieldPrefix; ?>ID" value="<?php echo $tag['id']; ?>" />
    <?php endif; ?>

    <div class="box">
        <!-- Tag Name -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="<?php echo $formFieldPrefix; ?>Text">Tag</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <input type="text"
                               class="input notempty noblanksnocomma checkoutsidebmp <?php echo $isEdit ? '' : 'setfocus'; ?>"
                               id="<?php echo $formFieldPrefix; ?>Text"
                               name="<?php echo $formFieldPrefix; ?>Text"
                               data_info="Tag"
                               value="<?php echo $tagText; ?>"
                               maxlength="20"
                               placeholder="Enter tag name"
                               x-model="tagText"
                               required />
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
                <p class="help">Maximum 20 characters. No spaces or commas allowed.</p>
            </div>
        </div>

        <!-- Comment -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="<?php echo $formFieldPrefix; ?>Comment">Comment</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <textarea class="textarea textarea-noreturn checklength checkoutsidebmp"
                                  id="<?php echo $formFieldPrefix; ?>Comment"
                                  name="<?php echo $formFieldPrefix; ?>Comment"
                                  data_maxlength="200"
                                  data_info="Comment"
                                  rows="3"
                                  placeholder="Optional comment about this tag"
                                  x-model="tagComment"
                                  @input="charCount = $event.target.value.length"><?php echo $tagComment; ?></textarea>
                    </div>
                    <p class="help">
                        <span :class="charCount > 200 ? 'has-text-danger' : 'has-text-grey'"
                              x-text="charCount + '/200 characters'"></span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="field is-grouped is-grouped-right">
        <div class="control">
            <button type="button"
                    class="button is-light"
                    data-action="cancel-navigate"
                    data-url="<?php echo htmlspecialchars($cancelUrl, ENT_QUOTES, 'UTF-8'); ?>">
                Cancel
            </button>
        </div>
        <div class="control">
            <button type="submit" name="op" value="<?php echo $submitValue; ?>" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                </span>
                <span><?php echo $submitValue; ?></span>
            </button>
        </div>
    </div>
</form>

<?php

/**
 * New Word Form View
 *
 * Variables expected:
 * - $lang: int - Language ID
 * - $textId: int - Text ID
 * - $scrdir: string - Script direction tag
 * - $showRoman: bool - Show romanization field
 * - $showSimilarTerms: bool - Show similar terms row
 * - $dictLinksHtml: string - Dictionary links HTML
 * - $wordTagsHtml: string - Word tags HTML
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 *
 * @psalm-suppress PossiblyUndefinedVariable Variables passed from controller
 */

declare(strict_types=1);

namespace Lwt\Views\Word;

use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

// Type assertions for variables passed from controller
assert(is_int($lang));
assert(is_int($textId));
assert(is_string($scrdir));
assert(is_bool($showRoman));
assert(is_bool($showSimilarTerms));
assert(is_string($dictLinksHtml));
assert(is_string($wordTagsHtml));

$actions = [
    ['url' => '/words', 'label' => __('vocabulary.actions.my_terms'), 'icon' => 'list', 'class' => 'is-primary'],
    ['url' => '/word/upload', 'label' => __('vocabulary.actions.import_terms'), 'icon' => 'upload'],
    ['url' => '/term-tags', 'label' => __('vocabulary.actions.term_tags'), 'icon' => 'tags'],
];
echo PageLayoutHelper::buildActionCard($actions);

$phWord = htmlspecialchars(__('vocabulary.form.placeholder_word'), ENT_QUOTES, 'UTF-8');
$phLemma = htmlspecialchars(__('vocabulary.form.placeholder_lemma_optional'), ENT_QUOTES, 'UTF-8');
$phTrans = htmlspecialchars(__('vocabulary.form.placeholder_translation'), ENT_QUOTES, 'UTF-8');
$phRom = htmlspecialchars(__('vocabulary.form.placeholder_romanization'), ENT_QUOTES, 'UTF-8');
$phSent = htmlspecialchars(__('vocabulary.form.placeholder_sentence'), ENT_QUOTES, 'UTF-8');
$phNotes = htmlspecialchars(__('vocabulary.form.placeholder_notes'), ENT_QUOTES, 'UTF-8');

?>

<form name="newword" class="validate" action="/word/new" method="post"
data-lwt-clear-frame="true">
    <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
    <input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
    <input type="hidden" name="tid" value="<?php echo $textId; ?>" />

    <div class="box">
        <div class="field">
            <label class="label" for="WoText"><?= __('vocabulary.form.new_term') ?></label>
            <div class="control has-icons-right">
                <input <?php echo $scrdir; ?>
                       class="input notempty setfocus checkoutsidebmp"
                       data_info="New Term"
                       type="text"
                       name="WoText"
                       id="WoText"
                       value=""
                       maxlength="250"
                       placeholder="<?= $phWord ?>" />
                <span class="icon is-small is-right">
                    <?php echo IconHelper::render('circle-x', [
                        'title' => __('vocabulary.common.field_required'),
                        'alt' => __('vocabulary.common.required')
                    ]); ?>
                </span>
            </div>
        </div>

        <div class="field">
            <label class="label" for="WoLemma"><?= __('vocabulary.common.lemma') ?></label>
            <div class="control">
                <input <?php echo $scrdir; ?>
                       type="text"
                       class="input checkoutsidebmp checklength"
                       data_maxlength="250"
                       data_info="Lemma"
                       name="WoLemma"
                       id="WoLemma"
                       value=""
                       maxlength="250"
                       placeholder="<?= $phLemma ?>" />
            </div>
        </div>

<?php if ($showSimilarTerms) : ?>
        <div class="field">
            <label class="label"><?= __('vocabulary.form.similar_terms') ?></label>
            <div class="control">
                <span id="simwords" class="is-size-7">&nbsp;</span>
            </div>
        </div>
<?php endif; ?>

        <div class="field">
            <label class="label"><?= __('vocabulary.common.translation') ?></label>
            <div class="control">
                <textarea class="textarea textarea-noreturn checklength checkoutsidebmp"
                          data_maxlength="500"
                          data_info="Translation"
                          name="WoTranslation"
                          rows="3"
                          placeholder="<?= $phTrans ?>"></textarea>
            </div>
        </div>

        <div class="field">
            <label class="label"><?= __('vocabulary.common.tags') ?></label>
            <div class="control">
                <?php echo $wordTagsHtml; ?>
            </div>
        </div>

<?php if ($showRoman) : ?>
        <div class="field">
            <label class="label"><?= __('vocabulary.common.romanization') ?></label>
            <div class="control">
                <input type="text"
                       class="input checkoutsidebmp"
                       data_info="Romanization"
                       name="WoRomanization"
                       value=""
                       maxlength="100"
                       placeholder="<?= $phRom ?>" />
            </div>
        </div>
<?php endif; ?>

        <div class="field">
            <label class="label"><?= __('vocabulary.form.sentence_label') ?></label>
            <div class="control">
                <textarea <?php echo $scrdir; ?>
                          name="WoSentence"
                          id="WoSentence"
                          rows="3"
                          class="textarea textarea-noreturn checklength checkoutsidebmp"
                          data_maxlength="1000"
                          data_info="Sentence"
                          placeholder="<?= $phSent ?>"></textarea>
            </div>
            <p class="help"><?= __('vocabulary.form.help_sentence_braces') ?></p>
        </div>

        <div class="field">
            <label class="label"><?= __('vocabulary.common.notes') ?></label>
            <div class="control">
                <textarea name="WoNotes"
                          id="WoNotes"
                          rows="3"
                          class="textarea textarea-noreturn checklength checkoutsidebmp"
                          data_maxlength="1000"
                          data_info="Notes"
                          placeholder="<?= $phNotes ?>"></textarea>
            </div>
        </div>

        <div class="field">
            <label class="label"><?= __('vocabulary.common.status') ?></label>
            <div class="control">
                <?php echo SelectOptionsBuilder::forWordStatusRadio(1, true); ?>
            </div>
        </div>

        <div class="field">
            <label class="label"><?= __('vocabulary.form.dictionary_lookup') ?></label>
            <div class="control">
                <?php echo $dictLinksHtml; ?>
            </div>
        </div>

        <div class="field is-grouped is-grouped-right mt-5">
            <div class="control">
                <button type="submit" name="op" value="Save" class="button is-primary">
                    <span class="icon is-small">
                        <?php echo IconHelper::render('save', ['alt' => __('vocabulary.common.save')]); ?>
                    </span>
                    <span><?= __('vocabulary.common.save') ?></span>
                </button>
            </div>
        </div>
    </div>
</form>

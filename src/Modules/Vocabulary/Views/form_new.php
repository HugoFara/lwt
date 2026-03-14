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
    ['url' => '/words', 'label' => 'My Terms', 'icon' => 'list', 'class' => 'is-primary'],
    ['url' => '/word/upload', 'label' => 'Import Terms', 'icon' => 'upload'],
    ['url' => '/term-tags', 'label' => 'Term Tags', 'icon' => 'tags'],
];
echo PageLayoutHelper::buildActionCard($actions);

?>

<form name="newword" class="validate" action="/word/new" method="post"
data-lwt-clear-frame="true">
    <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
    <input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
    <input type="hidden" name="tid" value="<?php echo $textId; ?>" />

    <div class="box">
        <div class="field">
            <label class="label" for="WoText">New Term</label>
            <div class="control has-icons-right">
                <input <?php echo $scrdir; ?>
                       class="input notempty setfocus checkoutsidebmp"
                       data_info="New Term"
                       type="text"
                       name="WoText"
                       id="WoText"
                       value=""
                       maxlength="250"
                       placeholder="Enter a word or expression" />
                <span class="icon is-small is-right">
                    <?php echo IconHelper::render('circle-x', [
                        'title' => 'Field must not be empty',
                        'alt' => 'Required'
                    ]); ?>
                </span>
            </div>
        </div>

        <div class="field">
            <label class="label" for="WoLemma">Lemma</label>
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
                       placeholder="Base form (optional)" />
            </div>
        </div>

<?php if ($showSimilarTerms) : ?>
        <div class="field">
            <label class="label">Similar Terms</label>
            <div class="control">
                <span id="simwords" class="is-size-7">&nbsp;</span>
            </div>
        </div>
<?php endif; ?>

        <div class="field">
            <label class="label">Translation</label>
            <div class="control">
                <textarea class="textarea textarea-noreturn checklength checkoutsidebmp"
                          data_maxlength="500"
                          data_info="Translation"
                          name="WoTranslation"
                          rows="3"
                          placeholder="Meaning in your language"></textarea>
            </div>
        </div>

        <div class="field">
            <label class="label">Tags</label>
            <div class="control">
                <?php echo $wordTagsHtml; ?>
            </div>
        </div>

<?php if ($showRoman) : ?>
        <div class="field">
            <label class="label">Romanization</label>
            <div class="control">
                <input type="text"
                       class="input checkoutsidebmp"
                       data_info="Romanization"
                       name="WoRomanization"
                       value=""
                       maxlength="100"
                       placeholder="Pronunciation / transliteration" />
            </div>
        </div>
<?php endif; ?>

        <div class="field">
            <label class="label">Sentence</label>
            <div class="control">
                <textarea <?php echo $scrdir; ?>
                          name="WoSentence"
                          id="WoSentence"
                          rows="3"
                          class="textarea textarea-noreturn checklength checkoutsidebmp"
                          data_maxlength="1000"
                          data_info="Sentence"
                          placeholder="Example sentence with term in {curly braces}"></textarea>
            </div>
            <p class="help">Wrap the term in {curly braces}, e.g. "I {love} languages."</p>
        </div>

        <div class="field">
            <label class="label">Notes</label>
            <div class="control">
                <textarea name="WoNotes"
                          id="WoNotes"
                          rows="3"
                          class="textarea textarea-noreturn checklength checkoutsidebmp"
                          data_maxlength="1000"
                          data_info="Notes"
                          placeholder="Personal notes (optional)"></textarea>
            </div>
        </div>

        <div class="field">
            <label class="label">Status</label>
            <div class="control">
                <?php echo SelectOptionsBuilder::forWordStatusRadio(1, true); ?>
            </div>
        </div>

        <div class="field">
            <label class="label">Dictionary Lookup</label>
            <div class="control">
                <?php echo $dictLinksHtml; ?>
            </div>
        </div>

        <div class="field is-grouped is-grouped-right mt-5">
            <div class="control">
                <button type="submit" name="op" value="Save" class="button is-primary">
                    <span class="icon is-small">
                        <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                    </span>
                    <span>Save</span>
                </button>
            </div>
        </div>
    </div>
</form>

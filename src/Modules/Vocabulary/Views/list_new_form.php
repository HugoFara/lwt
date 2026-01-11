<?php

/**
 * New word form for the words list page
 *
 * Variables expected:
 * - $lgid: Language ID
 * - $scrdir: Script direction attribute
 * - $showRoman: Whether to show romanization
 * - $languageName: Language name
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress PossiblyUndefinedVariable Variables passed from controller
 */

declare(strict_types=1);

namespace Lwt\Views\Word;

use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\IconHelper;

// Type assertions for variables passed from controller
assert(is_int($lgid));
assert(is_string($scrdir));
assert(is_bool($showRoman));
assert(is_string($languageName));

?>
<h2>New Term</h2>
<form name="newword" class="validate" action="/words/edit" method="post" data-lwt-form-check="true">
<?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
<input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lgid; ?>" />
<table class="table is-bordered">
<tr>
<td class="has-text-right">Language:</td>
<td class=""><?php echo htmlspecialchars($languageName, ENT_QUOTES, 'UTF-8'); ?></td>
</tr>
<tr>
<td class="has-text-right">Term:</td>
<td class="">
    <input <?php echo $scrdir; ?> class="notempty setfocus checkoutsidebmp"
    data_info="Term" type="text" name="WoText" id="WoText" value="" maxlength="250" />
    <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?></td>
</tr>
<?php echo (new FindSimilarTerms())->getTableRow(); ?>
<tr>
<td class="has-text-right">Translation:</td>
<td class="">
    <textarea class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" data_info="Translation" name="WoTranslation" cols="40" rows="3"></textarea></td>
</tr>
<tr>
<td class="has-text-right">Tags:</td>
<td class="">
<?php echo \Lwt\Modules\Tags\Application\TagsFacade::getWordTagsHtml(0); ?>
</td>
</tr>
<tr class="<?php echo ($showRoman ? '' : 'is-hidden'); ?>">
<td class="has-text-right">Romaniz.:</td>
<td class=""><input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" value="" maxlength="100" size="40" /></td>
</tr>
<tr>
<td class="has-text-right">Sentence<br />Term in {...}:</td>
<td class=""><textarea <?php echo $scrdir; ?> name="WoSentence" id="WoSentence" cols="40" rows="3" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence"></textarea></td>
</tr>
<tr>
<td class="has-text-right">Status:</td>
<td class="">
<?php echo SelectOptionsBuilder::forWordStatusRadio(1); ?>
</td>
</tr>
<tr>
<td class="has-text-right" colspan="2">  &nbsp;
<?php
/** @psalm-suppress PossiblyUndefinedVariable */
echo (string)createDictLinksInEditWin2(
    $lgid,
    'WoSentence',
    'WoText'
); ?>
    &nbsp; &nbsp;
<input type="button" value="Cancel" data-action="cancel-navigate" data-url="/words/edit" />
<input type="submit" name="op" value="Save" /></td>
</tr>
</table>
</form>

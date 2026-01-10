<?php declare(strict_types=1);
/**
 * Edit Term Form View - For editing word while testing
 *
 * Variables expected:
 * - $wid: int - Word ID
 * - $lang: int - Language ID
 * - $term: string - Term text
 * - $termlc: string - Lowercase term
 * - $scrdir: string - Script direction tag
 * - $showRoman: bool - Show romanization field
 * - $transl: string - Current translation
 * - $sentence: string - Example sentence
 * - $rom: string - Romanization
 * - $status: int - Current status
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

namespace Lwt\Views\Word;

use Lwt\Modules\Text\Application\Services\SentenceService;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\IconHelper;

// Type assertions for variables passed from controller
assert(is_int($wid));
assert(is_int($lang));
assert(is_string($term));
assert(is_string($termlc));
assert(is_string($lemma));
assert(is_string($scrdir));
assert(is_bool($showRoman));
assert(is_string($transl));
assert(is_string($sentence));
assert(is_string($notes));
assert(is_string($rom));
assert(is_int($status));

$phpSelf = htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<form name="editword" class="validate" action="<?php echo $phpSelf; ?>" method="post"
data-lwt-form-check="true" data-lwt-clear-frame="true">
<input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
<input type="hidden" name="WoID" value="<?php echo $wid; ?>" />
<input type="hidden" name="WoOldStatus" value="<?php echo $status; ?>" />
<input type="hidden" name="WoTextLC" value="<?php echo htmlspecialchars($termlc, ENT_QUOTES, 'UTF-8'); ?>" />
<table class="table is-bordered is-fullwidth">
    <tr title="Only change uppercase/lowercase!">
        <td class="has-text-right"><b>Edit Term:</b></td>
        <td class=""><input <?php echo $scrdir; ?> class="notempty checkoutsidebmp" data_info="Term" type="text" name="WoText" id="wordfield" value="<?php echo htmlspecialchars($term, ENT_QUOTES, 'UTF-8'); ?>" maxlength="250" size="35" /> <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
    </td></tr>
    <tr>
        <td class="has-text-right">Lemma:</td>
        <td class=""><input <?php echo $scrdir; ?> type="text" class="checkoutsidebmp checklength" data_maxlength="250" data_info="Lemma" name="WoLemma" id="WoLemma" value="<?php echo htmlspecialchars($lemma, ENT_QUOTES, 'UTF-8'); ?>" maxlength="250" size="35" placeholder="Base form (e.g., 'run' for 'running')" /></td>
    </tr>
        <?php echo (new FindSimilarTerms())->getTableRow(); ?>
    <tr>
        <td class="has-text-right">Translation:</td>
        <td class=""><textarea name="WoTranslation" class="setfocus textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" data_info="Translation" cols="35" rows="3"><?php echo htmlspecialchars($transl, ENT_QUOTES, 'UTF-8'); ?></textarea></td>
    </tr>
    <tr>
        <td class="has-text-right">Tags:</td>
        <td class="">
            <?php echo \Lwt\Modules\Tags\Application\TagsFacade::getWordTagsHtml($wid); ?>
        </td>
    </tr>
    <tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
        <td class="has-text-right">Romaniz.:</td>
        <td class=""><input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" maxlength="100" size="35" value="<?php echo htmlspecialchars($rom, ENT_QUOTES, 'UTF-8'); ?>" /></td>
    </tr>
    <tr>
        <td class="has-text-right">Sentence<br />Term in {...}:</td>
        <td class=""><textarea <?php echo $scrdir; ?> name="WoSentence" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence" cols="35" rows="3"><?php echo htmlspecialchars($sentence, ENT_QUOTES, 'UTF-8'); ?></textarea></td>
    </tr>
    <tr>
        <td class="has-text-right">Notes:</td>
        <td class=""><textarea name="WoNotes" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Notes" cols="35" rows="3"><?php echo htmlspecialchars($notes, ENT_QUOTES, 'UTF-8'); ?></textarea></td>
    </tr>
        <?php echo (new FindSimilarTerms())->getTableRow(); ?>
    <tr>
        <td class="has-text-right">Status:</td>
        <td class="">
            <?php echo SelectOptionsBuilder::forWordStatusRadio($status); ?>
        </td>
    </tr>
    <tr>
        <td class="has-text-right" colspan="2">
            <?php echo (new \Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter())->createDictLinksInEditWin($lang, $term, 'document.forms[0].WoSentence', true); ?>
            &nbsp; &nbsp; &nbsp;
            <input type="submit" name="op" value="Change" />
        </td>
    </tr>
</table>
</form>
<?php
// Display example sentence button
echo (new SentenceService())->renderExampleSentencesArea($lang, $termlc, 'document.forms.editword.WoSentence', $wid);
?>

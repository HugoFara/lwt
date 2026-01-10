<?php declare(strict_types=1);
/**
 * New Word Form View
 *
 * Variables expected:
 * - $lang: int - Language ID
 * - $textId: int - Text ID
 * - $scrdir: string - Script direction tag
 * - $showRoman: bool - Show romanization field
 * - $dictService: DictionaryService - Dictionary service instance
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
 * @psalm-suppress PossiblyUndefinedVariable Variables passed from controller
 */

namespace Lwt\Views\Word;

use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter;

// Type assertions for variables passed from controller
assert(is_int($lang));
assert(is_int($textId));
assert(is_string($scrdir));
assert(is_bool($showRoman));
assert($dictService instanceof DictionaryAdapter);

$phpSelf = htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<form name="newword" class="validate" action="<?php echo $phpSelf; ?>" method="post"
data-lwt-clear-frame="true">
    <input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
    <input type="hidden" name="tid" value="<?php echo $textId; ?>" />
    <table class="tab2" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1 right"><b>New Term:</b></td>
            <td class="td1"><input <?php echo $scrdir; ?>
            class="notempty setfocus checkoutsidebmp" data_info="New Term"
            type="text" name="WoText" id="WoText" value="" maxlength="250" size="35" />
            <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?></td>
        </tr>
        <tr>
            <td class="td1 right">Lemma:</td>
            <td class="td1"><input <?php echo $scrdir; ?>
            type="text" class="checkoutsidebmp checklength" data_maxlength="250"
            data_info="Lemma" name="WoLemma" id="WoLemma" value="" maxlength="250" size="35"
            placeholder="Base form (optional)" /></td>
        </tr>
        <?php echo (new FindSimilarTerms())->getTableRow(); ?>
        <tr>
            <td class="td1 right">Translation:</td>
            <td class="td1">
                <textarea class="textarea-noreturn checklength checkoutsidebmp"
                data_maxlength="500" data_info="Translation" name="WoTranslation" cols="35" rows="3"></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Tags:</td>
            <td class="td1">
            <?php echo TagsFacade::getWordTagsHtml(0); ?>
        </td>
        </tr>
        <tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
            <td class="td1 right">Romaniz.:</td>
            <td class="td1">
                <input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" value="" maxlength="100" size="35" />
            </td>
        </tr>
        <tr>
            <td class="td1 right">Sentence<br />Term in {...}:</td>
            <td class="td1">
                <textarea <?php echo $scrdir; ?> name="WoSentence" id="WoSentence" cols="35" rows="3" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence"></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Notes:</td>
            <td class="td1">
                <textarea name="WoNotes" id="WoNotes" cols="35" rows="3" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Notes"></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Status:</td>
            <td class="td1">
                <?php echo SelectOptionsBuilder::forWordStatusRadio(1); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">  &nbsp;
                <?php /** @psalm-suppress PossiblyUndefinedVariable */ echo $dictService->createDictLinksInEditWin3($lang, 'WoSentence', 'WoText'); ?>
                &nbsp; &nbsp;
                <input type="submit" name="op" value="Save" />
            </td>
        </tr>
    </table>
</form>

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
 */

namespace Lwt\Views\Word;

use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\IconHelper;

/** @var int $lang */

$phpSelf = $_SERVER['PHP_SELF'] ?? '';
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
                <?php echo $dictService->createDictLinksInEditWin3($lang, 'WoSentence', 'WoText'); ?>
                &nbsp; &nbsp;
                <input type="submit" name="op" value="Save" />
            </td>
        </tr>
    </table>
</form>

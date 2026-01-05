<?php declare(strict_types=1);
/**
 * Multi-Word Edit Form View - Form for editing an existing multi-word expression
 *
 * Variables expected:
 * - $term: object - Term object with id, lgid, text, textlc properties
 * - $tid: int - Text ID
 * - $ord: int - Text order position
 * - $scrdir: string - Script direction tag
 * - $sentence: string - Example sentence
 * - $notes: string - Notes text
 * - $transl: string - Translation text
 * - $romanization: string - Romanization text
 * - $status: int - Current status (adjusted for editing)
 * - $originalStatus: int - Original status from database
 * - $showRoman: bool - Whether to show romanization field
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
 * @psalm-suppress TypeDoesNotContainType Defensive null checks
 */

namespace Lwt\Views\Word;

use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Services\SentenceService;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\IconHelper;

?>
<form name="editword" class="validate" action="/word/edit-multi" method="post"
data-lwt-form-check="true" data-lwt-clear-frame="true">
<input type="hidden" name="WoLgID" id="langfield" value="<?php echo $term->lgid; ?>" />
<input type="hidden" name="WoID" value="<?php echo $term->id; ?>" />
<input type="hidden" name="WoOldStatus" value="<?php echo $originalStatus; ?>" />
<input type="hidden" name="WoStatus" value="<?php echo $status; ?>" />
<input type="hidden" name="WoTextLC" value="<?php echo htmlspecialchars($term->textlc ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
<input type="hidden" name="tid" value="<?php echo $tid; ?>" />
<input type="hidden" name="ord" value="<?php echo $ord; ?>" />
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr title="Only change uppercase/lowercase!">
        <td class="td1 right"><b>Edit Term:</b></td>
        <td class="td1">
            <input <?php echo $scrdir; ?> class="notempty checkoutsidebmp"
            data_info="Term" type="text" name="WoText" id="wordfield"
            value="<?php echo htmlspecialchars($term->text ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="250" size="35" />
            <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
        </td>
    </tr>
    <?php echo (new FindSimilarTerms())->getTableRow(); ?>
    <tr>
        <td class="td1 right">Translation:</td>
        <td class="td1">
            <textarea name="WoTranslation" class="setfocus textarea-noreturn checklength checkoutsidebmp"
            data_maxlength="500" data_info="Translation" cols="35" rows="3"><?php echo htmlspecialchars($transl ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Tags:</td>
        <td class="td1">
            <?php echo \Lwt\Modules\Tags\Application\TagsFacade::getWordTagsHtml($term->id); ?>
        </td>
    </tr>
    <tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
        <td class="td1 right">Romaniz.:</td>
        <td class="td1">
            <input type="text" class="checkoutsidebmp" data_info="Romanization"
            name="WoRomanization" maxlength="100" size="35"
            value="<?php echo htmlspecialchars($romanization ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
        </td>
    </tr>
    <tr>
        <td class="td1 right">Sentence<br />Term in {...}:</td>
        <td class="td1">
            <textarea <?php echo $scrdir; ?> name="WoSentence"
            class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000"
            data_info="Sentence" cols="35" rows="3"><?php echo htmlspecialchars($sentence ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Notes:</td>
        <td class="td1">
            <textarea name="WoNotes"
            class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000"
            data_info="Notes" cols="35" rows="3"><?php echo htmlspecialchars($notes ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </td>
    </tr>
    <tr>
        <td class="td1 right">Status:</td>
        <td class="td1">
            <?php echo SelectOptionsBuilder::forWordStatusRadio($originalStatus); ?>
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <?php echo (new \Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter())->createDictLinksInEditWin(
                $term->lgid ?? 0,
                $term->text ?? '',
                'document.forms[0].WoSentence',
                !InputValidator::hasFromGet('nodict')
            ); ?>
            &nbsp; &nbsp; &nbsp;
            <input type="submit" name="op" value="Change" />
        </td>
    </tr>
</table>
</form>
<?php
// Display example sentences button
echo (new SentenceService())->renderExampleSentencesArea($term->lgid ?? 0, $term->textlc, 'document.forms.editword.WoSentence', $term->id);
?>

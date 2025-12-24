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

use Lwt\Services\SentenceService;
use Lwt\Services\SimilarTermsService;
use Lwt\View\Helper\SelectOptionsBuilder;
use Lwt\View\Helper\IconHelper;

/** @var int $wid */
/** @var int $lang */
/** @var string $term */
/** @var string $termlc */
/** @var string $scrdir */
/** @var bool $showRoman */
/** @var string $transl */
/** @var string $sentence */
/** @var string $notes */
/** @var string $rom */
/** @var int $status */

?>
<form name="editword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post"
data-lwt-form-check="true" data-lwt-clear-frame="true">
<input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
<input type="hidden" name="WoID" value="<?php echo $wid; ?>" />
<input type="hidden" name="WoOldStatus" value="<?php echo $status; ?>" />
<input type="hidden" name="WoTextLC" value="<?php echo htmlspecialchars($termlc, ENT_QUOTES, 'UTF-8'); ?>" />
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr title="Only change uppercase/lowercase!">
        <td class="td1 right"><b>Edit Term:</b></td>
        <td class="td1"><input <?php echo $scrdir; ?> class="notempty checkoutsidebmp" data_info="Term" type="text" name="WoText" id="wordfield" value="<?php echo htmlspecialchars($term, ENT_QUOTES, 'UTF-8'); ?>" maxlength="250" size="35" /> <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
    </td></tr>
        <?php echo (new SimilarTermsService())->printSimilarTermsTabRow(); ?>
    <tr>
        <td class="td1 right">Translation:</td>
        <td class="td1"><textarea name="WoTranslation" class="setfocus textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" data_info="Translation" cols="35" rows="3"><?php echo htmlspecialchars($transl, ENT_QUOTES, 'UTF-8'); ?></textarea></td>
    </tr>
    <tr>
        <td class="td1 right">Tags:</td>
        <td class="td1">
            <?php echo \Lwt\Services\TagService::getWordTagsHtml($wid); ?>
        </td>
    </tr>
    <tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
        <td class="td1 right">Romaniz.:</td>
        <td class="td1"><input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" maxlength="100" size="35" value="<?php echo htmlspecialchars($rom ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></td>
    </tr>
    <tr>
        <td class="td1 right">Sentence<br />Term in {...}:</td>
        <td class="td1"><textarea <?php echo $scrdir; ?> name="WoSentence" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence" cols="35" rows="3"><?php echo htmlspecialchars($sentence, ENT_QUOTES, 'UTF-8'); ?></textarea></td>
    </tr>
    <tr>
        <td class="td1 right">Notes:</td>
        <td class="td1"><textarea name="WoNotes" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Notes" cols="35" rows="3"><?php echo htmlspecialchars($notes, ENT_QUOTES, 'UTF-8'); ?></textarea></td>
    </tr>
        <?php echo (new SimilarTermsService())->printSimilarTermsTabRow(); ?>
    <tr>
        <td class="td1 right">Status:</td>
        <td class="td1">
            <?php echo SelectOptionsBuilder::forWordStatusRadio($status); ?>
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <?php echo (new \Lwt\Services\DictionaryService())->createDictLinksInEditWin($lang, $term, 'document.forms[0].WoSentence', true); ?>
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

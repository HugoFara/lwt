<?php declare(strict_types=1);
/**
 * Edit Word Form View - For creating new word from reading screen
 *
 * Variables expected:
 * - $lang: int - Language ID
 * - $textId: int - Text ID
 * - $ord: int - Word order
 * - $fromAnn: string - From annotation flag
 * - $term: string - Term text
 * - $termlc: string - Lowercase term
 * - $scrdir: string - Script direction tag
 * - $showRoman: bool - Show romanization field
 * - $sentence: string - Example sentence
 * - $transUri: string - Translation API URI
 * - $langShort: string - Short language code
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

use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Modules\Text\Application\Services\SentenceService;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;

$phpSelf = htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8');
?>

<script type="application/json" id="word-form-config">
<?php echo json_encode([
    'transUri' => $transUri,
    'langShort' => $langShort,
    'lang' => $lang,
]); ?>
</script>
<form name="newword" class="validate" action="<?php echo $phpSelf; ?>" method="post"
data-lwt-form-check="true" data-lwt-clear-frame="true">
<input type="hidden" name="fromAnn" value="<?php echo $fromAnn; ?>" />
<input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
<input type="hidden" name="WoTextLC" value="<?php echo htmlspecialchars($termlc, ENT_QUOTES, 'UTF-8'); ?>" />
<input type="hidden" name="tid" value="<?php echo $textId; ?>" />
<input type="hidden" name="ord" value="<?php echo $ord; ?>" />
<table class="tab2" cellspacing="0" cellpadding="5">
   <tr title="Only change uppercase/lowercase!">
       <td class="td1 right"><b>New Term:</b></td>
       <td class="td1">
           <input <?php echo $scrdir; ?> class="notempty checkoutsidebmp"
           data_info="New Term" type="text"
           name="WoText" id="wordfield" value="<?php echo htmlspecialchars($term, ENT_QUOTES, 'UTF-8'); ?>"
           maxlength="250" size="35" />
           <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
       </td>
   </tr>
   <?php echo (new FindSimilarTerms())->getTableRow(); ?>
   <tr>
       <td class="td1 right">Translation:</td>
       <td class="td1">
           <textarea name="WoTranslation"
           class="setfocus textarea-noreturn checklength checkoutsidebmp"
           data_maxlength="500"
           data_info="Translation" cols="35" rows="3"></textarea>
       </td>
   </tr>
   <tr>
       <td class="td1 right">Tags:</td>
       <td class="td1">
           <?php echo \Lwt\Modules\Tags\Application\TagsFacade::getWordTagsHtml(0); ?>
       </td>
   </tr>
   <tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
       <td class="td1 right">Romaniz.:</td>
       <td class="td1">
           <input type="text" class="checkoutsidebmp" data_info="Romanization"
           name="WoRomanization"
           value="" maxlength="100" size="35" />
       </td>
   </tr>
   <tr>
       <td class="td1 right">Sentence<br />Term in {...}:</td>
       <td class="td1">
           <textarea <?php echo $scrdir; ?> name="WoSentence"
           class="textarea-noreturn checklength checkoutsidebmp"
           data_maxlength="1000" data_info="Sentence" cols="35"
           rows="3"><?php echo htmlspecialchars($sentence, ENT_QUOTES, 'UTF-8'); ?></textarea>
       </td>
   </tr>
   <tr>
       <td class="td1 right">Notes:</td>
       <td class="td1">
           <textarea name="WoNotes"
           class="textarea-noreturn checklength checkoutsidebmp"
           data_maxlength="1000" data_info="Notes" cols="35"
           rows="3"></textarea>
       </td>
   </tr>
   <?php echo (new FindSimilarTerms())->getTableRow(); ?>
   <tr>
       <td class="td1 right">Status:</td>
       <td class="td1">
           <?php echo SelectOptionsBuilder::forWordStatusRadio(1); ?>
       </td>
   </tr>
   <tr>
       <td class="td1 right" colspan="2">
           <?php echo (new \Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter())->createDictLinksInEditWin(
               $lang,
               $term,
               'document.forms[0].WoSentence',
               !InputValidator::hasFromGet('nodict')
           ); ?>
       &nbsp; &nbsp; &nbsp;
       <input type="submit" name="op" value="Save" /></td>
   </tr>
</table>
</form>
<?php
// Display example sentence button
echo (new SentenceService())->renderExampleSentencesArea($lang, $termlc, 'document.forms.newword.WoSentence', 0);
?>

<?php declare(strict_types=1);
/**
 * Edit Word Form View - For editing an existing word
 *
 * Variables expected:
 * - $lang: int - Language ID
 * - $textId: int - Text ID
 * - $ord: int - Word order
 * - $wid: int - Word ID
 * - $fromAnn: string - From annotation flag
 * - $term: string - Term text
 * - $termlc: string - Lowercase term
 * - $scrdir: string - Script direction tag
 * - $showRoman: bool - Show romanization field
 * - $wordData: array - Current word data from database
 * - $sentence: string - Example sentence
 * - $status: int - Current status
 * - $transl: string - Current translation
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
use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\IconHelper;

// Type assertions for variables passed from controller
assert(is_int($lang));
assert(is_int($wid));
assert(is_string($fromAnn));
assert(is_string($term));
assert(is_string($termlc));
assert(is_string($scrdir));
assert(is_bool($showRoman));
/** @var array{WoStatus: int, WoLemma?: string, WoRomanization?: string, WoNotes?: string} $wordData */
assert(is_array($wordData));
assert(is_string($sentence));
assert(is_int($status));
assert(is_string($transl));

$phpSelf = htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<form name="editword" class="validate" action="<?php echo $phpSelf; ?>" method="post"
data-lwt-form-check="true" data-lwt-clear-frame="true">
<input type="hidden" name="WoLgID" id="langfield" value="<?php echo $lang; ?>" />
<input type="hidden" name="fromAnn" value="<?php echo $fromAnn; ?>" />
<input type="hidden" name="WoID" value="<?php echo $wid; ?>" />
<input type="hidden" name="WoOldStatus" value="<?php echo $wordData['WoStatus']; ?>" />
<input type="hidden" name="WoTextLC" value="<?php echo htmlspecialchars($termlc, ENT_QUOTES, 'UTF-8'); ?>" />
<input type="hidden" name="tid" value="<?php echo InputValidator::getString('tid'); ?>" />
<input type="hidden" name="ord" value="<?php echo InputValidator::getString('ord'); ?>" />
<table class="table is-bordered is-fullwidth">
   <tr title="Only change uppercase/lowercase!">
       <td class="has-text-right"><b>Edit Term:</b></td>
       <td class="">
           <input <?php echo $scrdir; ?> class="notempty checkoutsidebmp"
           data_info="Term" type="text"
           name="WoText" id="WoText"
           value="<?php echo htmlspecialchars($term, ENT_QUOTES, 'UTF-8'); ?>" maxlength="250" size="35" />
           <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
       </td>
   </tr>
   <tr>
       <td class="has-text-right">Lemma:</td>
       <td class="">
           <input <?php echo $scrdir; ?> type="text"
           class="checkoutsidebmp checklength" data_maxlength="250"
           data_info="Lemma" name="WoLemma" id="WoLemma"
           value="<?php echo htmlspecialchars($wordData['WoLemma'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="250" size="35"
           placeholder="Base form (e.g., 'run' for 'running')" />
       </td>
   </tr>
   <?php echo (new FindSimilarTerms())->getTableRow(); ?>
   <tr>
       <td class="has-text-right">Translation:</td>
       <td class="">
           <textarea name="WoTranslation"
           class="setfocus textarea-noreturn checklength checkoutsidebmp"
           data_maxlength="500" data_info="Translation" cols="35"
           rows="3"><?php echo htmlspecialchars($transl, ENT_QUOTES, 'UTF-8'); ?></textarea>
       </td>
   </tr>
   <tr>
       <td class="has-text-right">Tags:</td>
       <td class="">
           <?php echo \Lwt\Modules\Tags\Application\TagsFacade::getWordTagsHtml($wid); ?>
       </td>
   </tr>
   <tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
       <td class="has-text-right">Romaniz.:</td>
       <td class="">
           <input type="text" class="checkoutsidebmp"
           data_info="Romanization" name="WoRomanization" maxlength="100"
           size="35"
           value="<?php echo htmlspecialchars($wordData['WoRomanization'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
       </td>
   </tr>
   <tr>
       <td class="has-text-right">Sentence<br />Term in {...}:</td>
       <td class="">
           <textarea <?php echo $scrdir; ?> name="WoSentence" id="WoSentence"
           class="textarea-noreturn checklength checkoutsidebmp"
           data_maxlength="1000" data_info="Sentence" cols="35"
           rows="3"><?php echo htmlspecialchars($sentence, ENT_QUOTES, 'UTF-8'); ?></textarea>
       </td>
   </tr>
   <tr>
       <td class="has-text-right">Notes:</td>
       <td class="">
           <textarea name="WoNotes" id="WoNotes"
           class="textarea-noreturn checklength checkoutsidebmp"
           data_maxlength="1000" data_info="Notes" cols="35"
           rows="3"><?php echo htmlspecialchars($wordData['WoNotes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
       </td>
   </tr>
   <tr>
       <td class="has-text-right">Status:</td>
       <td class="">
           <?php echo SelectOptionsBuilder::forWordStatusRadio($status); ?>
       </td>
   </tr>
   <tr>
       <td class="has-text-right" colspan="2">
           <?php
           /** @psalm-suppress PossiblyUndefinedVariable */
           if ($fromAnn !== '') {
               echo (string)createDictLinksInEditWin2(
                   $lang,
                   'WoSentence',
                   'WoText'
               );
           } else {
               echo (new \Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter())->createDictLinksInEditWin(
                   $lang,
                   $term,
                   'WoSentence',
                   !InputValidator::hasFromGet('nodict')
               );
           }
           ?>
           &nbsp; &nbsp; &nbsp;
           <input type="submit" name="op" value="Change" />
       </td>
   </tr>
</table>
</form>
<?php
// Display example sentences button
echo (new SentenceService())->renderExampleSentencesArea($lang, $termlc, 'WoSentence', $wid);
?>

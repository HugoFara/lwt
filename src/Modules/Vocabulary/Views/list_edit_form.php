<?php

/**
 * Edit word form for the words list page
 *
 * Variables expected:
 * - $word: Word data array with WoID, WoLgID, WoText, etc.
 * - $scrdir: Script direction attribute
 * - $showRoman: Whether to show romanization
 * - $transl: Translation value (with '*' replaced by '')
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Views\Word;

use Lwt\Modules\Text\Application\Services\SentenceService;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\IconHelper;

// Type assertions for variables passed from controller
/**
 * @var array{
 *     WoID: int, WoLgID: int, WoText: string, WoTextLC: string, WoStatus: int,
 *     LgName?: string, WoLemma?: string, WoRomanization?: string, WoSentence?: string
 * } $word
*/
assert(is_array($word));
assert(is_string($scrdir));
assert(is_bool($showRoman));
assert(is_string($transl));

?>
<h2>Edit Term</h2>
<form name="editword" class="validate" action="/words/edit#rec<?php echo $word['WoID']; ?>" method="post">
<input type="hidden" name="WoID" value="<?php echo $word['WoID']; ?>" />
<input type="hidden" name="WoLgID" id="langfield" value="<?php echo $word['WoLgID']; ?>" />
<input type="hidden" name="WoOldStatus" value="<?php echo $word['WoStatus']; ?>" />
<table class="table is-bordered">
<tr>
   <td class="has-text-right">Language:</td>
   <td class=""><?php echo htmlspecialchars($word['LgName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
</tr>
<tr title="Normally only change uppercase/lowercase here!">
   <td class="has-text-right">Term:</td>
   <td class="">
       <input <?php echo $scrdir; ?>
              class="notempty setfocus checkoutsidebmp"
              data_info="Term"
              type="text"
              name="WoText"
              id="WoText"
              value="<?php echo htmlspecialchars($word['WoText'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              maxlength="250"
              size="40" />
       <?php echo IconHelper::render('circle-x', [
           'title' => 'Field must not be empty',
           'alt' => 'Field must not be empty'
       ]); ?>
   </td>
</tr>
<tr>
   <td class="has-text-right">Lemma:</td>
   <td class="">
       <input <?php echo $scrdir; ?> type="text"
       class="checkoutsidebmp checklength" data_maxlength="250"
       data_info="Lemma" name="WoLemma" id="WoLemma"
       value="<?php echo htmlspecialchars($word['WoLemma'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="250" size="40"
       placeholder="Base form (e.g., 'run' for 'running')" />
   </td>
</tr>
<?php echo (new FindSimilarTerms())->getTableRow(); ?>
<tr>
   <td class="has-text-right">Translation:</td>
   <td class="">
       <textarea class="textarea-noreturn checklength checkoutsidebmp"
                 data_maxlength="500"
                 data_info="Translation"
                 name="WoTranslation"
                 cols="40"
                 rows="3"><?php echo htmlspecialchars($transl, ENT_QUOTES, 'UTF-8'); ?></textarea>
   </td>
</tr>
<tr>
   <td class="has-text-right">Tags:</td>
   <td class="">
       <?php echo \Lwt\Modules\Tags\Application\TagsFacade::getWordTagsHtml($word['WoID']); ?>
   </td>
</tr>
<tr class="<?php echo ($showRoman ? '' : 'is-hidden'); ?>">
   <td class="has-text-right">Romaniz.:</td>
   <td class="">
       <input type="text"
              class="checkoutsidebmp"
              data_info="Romanization"
              name="WoRomanization"
              maxlength="100"
              size="40"
              value="<?php echo htmlspecialchars($word['WoRomanization'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
   </td>
</tr>
<tr>
   <td class="has-text-right">Sentence<br />Term in {...}:</td>
   <td class="">
       <textarea <?php echo $scrdir; ?>
                 class="textarea-noreturn checklength checkoutsidebmp"
                 data_maxlength="1000"
                 data_info="Sentence"
                 name="WoSentence"
                 id="WoSentence"
                 cols="40"
                 rows="3"><?php echo htmlspecialchars($word['WoSentence'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
   </td>
</tr>
<tr>
   <td class="has-text-right">Status:</td>
   <td class="">
       <?php echo SelectOptionsBuilder::forWordStatusRadio($word['WoStatus']); ?>
   </td>
</tr>
<tr>
   <td class="has-text-right" colspan="2">  &nbsp;
       <?php
        echo (new \Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter())
           ->createDictLinksInEditWin2($word['WoLgID'], 'WoSentence', 'WoText');
        ?>
       &nbsp; &nbsp;
       <input type="button"
              value="Cancel"
              data-action="cancel-navigate"
              data-url="/words/edit#rec<?php echo $word['WoID']; ?>" />
       <input type="submit" name="op" value="Change" />
   </td>
</tr>
</table>
</form>
<?php
// Display example sentence button
echo (new SentenceService())->renderExampleSentencesArea(
    $word['WoLgID'],
    $word['WoTextLC'],
    'WoSentence',
    $word['WoID']
);
?>

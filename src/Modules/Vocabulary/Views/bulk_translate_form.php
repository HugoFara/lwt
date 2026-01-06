<?php declare(strict_types=1);
/**
 * Bulk Translate Form View - Form for bulk translating unknown words
 *
 * Variables expected:
 * - $tid: int - Text ID
 * - $sl: string|null - Source language code
 * - $tl: string|null - Target language code
 * - $pos: int - Current offset position
 * - $dictionaries: array - Dictionary URIs with keys: dict1, dict2, translate
 * - $terms: array - Array of terms to translate with keys: word, Ti2LgID
 * - $nextOffset: int|null - Next offset if more terms exist, null if last page
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

use Lwt\Shared\UI\Helpers\IconHelper;

?>
<script type="application/json" id="bulk-translate-config">
<?php echo json_encode([
    'dictionaries' => $dictionaries,
    'sourceLanguage' => $sl,
    'targetLanguage' => $tl
]); ?>
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<form name="form1" action="/word/bulk-translate" method="post"
      x-data="bulkTranslateApp()">

    <!-- Controls Panel -->
    <div class="box notranslate mb-4">
        <div id="google_translate_element" class="mb-3"></div>

        <div class="level">
            <div class="level-left">
                <div class="level-item">
                    <div class="buttons are-small">
                        <button type="button"
                                class="button is-info is-outlined"
                                @click="markAll()">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('check-square', ['alt' => 'Mark All']); ?>
                            </span>
                            <span>Mark All</span>
                        </button>
                        <button type="button"
                                class="button is-outlined"
                                @click="markNone()">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('square', ['alt' => 'Mark None']); ?>
                            </span>
                            <span>Mark None</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="level-right">
                <div class="level-item">
                    <div class="field has-addons">
                        <div class="control">
                            <span class="button is-static is-small">Marked Terms</span>
                        </div>
                        <div class="control">
                            <div class="select is-small">
                                <select @change="handleTermToggles($event.target.value); $event.target.selectedIndex = 0;">
                                    <option value="0" selected>[Choose...]</option>
                                    <optgroup label="Change Status">
                                        <option value="1">Set Status To [1]</option>
                                        <option value="2">Set Status To [2]</option>
                                        <option value="3">Set Status To [3]</option>
                                        <option value="4">Set Status To [4]</option>
                                        <option value="5">Set Status To [5]</option>
                                        <option value="99">Set Status To [WKn]</option>
                                        <option value="98">Set Status To [Ign]</option>
                                    </optgroup>
                                    <option value="6">Set To Lowercase</option>
                                    <option value="7">Delete Translation</option>
                                </select>
                            </div>
                        </div>
                        <div class="control">
                            <button type="submit" class="button is-primary is-small">
                                <span class="icon is-small">
                                    <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                                </span>
                                <span x-text="submitButtonText">Save</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Table -->
    <div class="table-container">
        <table class="table is-fullwidth is-striped is-hoverable">
            <thead>
                <tr class="notranslate">
                    <th class="has-text-centered" style="width: 60px;">Mark</th>
                    <th style="min-width: 8em;">Term</th>
                    <th>Translation</th>
                    <th class="has-text-centered" style="width: 100px;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $cnt = 0;
            foreach ($terms as $record) {
                $cnt++;
                $value = \htmlspecialchars($record['word'] ?? '', ENT_QUOTES, 'UTF-8');
                ?>
                <tr>
                    <td class="has-text-centered notranslate">
                        <label class="checkbox">
                            <input name="marked[<?php echo $cnt ?>]"
                                   type="checkbox"
                                   class="markcheck"
                                   checked
                                   value="<?php echo $cnt ?>" />
                        </label>
                    </td>
                    <td id="Term_<?php echo $cnt ?>" class="notranslate">
                        <span class="term tag is-medium is-light"><?php echo $value ?></span>
                    </td>
                    <td class="trans" id="Trans_<?php echo $cnt ?>">
                        <?php echo mb_strtolower($value, 'UTF-8') ?>
                    </td>
                    <td class="has-text-centered notranslate">
                        <div class="select is-small">
                            <select id="Stat_<?php echo $cnt ?>" name="term[<?php echo $cnt ?>][status]">
                                <option value="1" selected>[1]</option>
                                <option value="2">[2]</option>
                                <option value="3">[3]</option>
                                <option value="4">[4]</option>
                                <option value="5">[5]</option>
                                <option value="99">[WKn]</option>
                                <option value="98">[Ign]</option>
                            </select>
                        </div>
                        <input type="hidden"
                               id="Text_<?php echo $cnt ?>"
                               name="term[<?php echo $cnt ?>][text]"
                               value="<?php echo $value ?>" />
                        <input type="hidden"
                               name="term[<?php echo $cnt ?>][lg]"
                               value="<?php echo \htmlspecialchars($record['Ti2LgID'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
    </div>

    <!-- Hidden fields -->
    <input type="hidden" name="tid" value="<?php echo $tid ?>" />
    <?php if ($nextOffset !== null) : ?>
    <input type="hidden" name="offset" value="<?php echo $nextOffset ?>" />
    <input type="hidden" name="sl" value="<?php echo $sl ?>" />
    <input type="hidden" name="tl" value="<?php echo $tl ?>" />
    <?php endif; ?>
</form>

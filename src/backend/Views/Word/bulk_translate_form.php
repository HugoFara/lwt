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

?>
<script type="application/json" id="bulk-translate-config">
<?php echo json_encode([
    'dictionaries' => $dictionaries,
    'sourceLanguage' => $sl,
    'targetLanguage' => $tl
]); ?>
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <form name="form1" action="/word/bulk-translate" method="post">
    <span class="notranslate">
        <div id="google_translate_element"></div>
        <table class="tab3" cellspacing="0">
            <tr class="notranslate">
                <th class="th1 center" colspan="3">
                    <input type="button" value="Mark All" data-action="bulk-mark-all" />
                    <input type="button" value="Mark None" data-action="bulk-mark-none" />
                    <br />
                </th>
            </tr>
            <tr class="notranslate">
                <td class="td1">Marked Terms: </td>
                <td class="td1">
                    <select data-action="bulk-term-toggles">
                        <option value="0" selected="selected">
                            [Choose...]
                        </option>
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
                </td>
                <td class="td1" style="min-width: 45px;">
                    <input  type="submit" value="Save" />
                </td>
            </tr>
        </table>
    </span>
    <table class="tab3" cellspacing="0">
        <tr class="notranslate">
            <th class="th1">Mark</th>
            <th class="th1" style="min-width:5em;">Term</th>
            <th class="th1">Translation</th>
            <th class="th1">Status</th>
        </tr>
    <?php
    $cnt = 0;
    foreach ($terms as $record) {
        $cnt++;
        $value = \tohtml($record['word']);
        ?>
        <tr>
        <td class="td1 center notranslate">
            <input name="marked[<?php echo $cnt ?>]" type="checkbox" class="markcheck" checked="checked" value="<?php echo $cnt ?>" />
        </td>
        <td id="Term_<?php echo $cnt ?>" class="td1 left notranslate">
            <span class="term"><?php echo $value ?></span>
        </td>
        <td class="td1 trans" id="Trans_<?php echo $cnt ?>">
            <?php echo mb_strtolower($value, 'UTF-8') ?>
        </td>
        <td class="td1 center notranslate">
            <select id="Stat_<?php echo $cnt ?>" name="term[<?php echo $cnt ?>][status]">
                <option value="1" selected="selected">[1]</option>
                <option value="2">[2]</option>
                <option value="3">[3]</option>
                <option value="4">[4]</option>
                <option value="5">[5]</option>
                <option value="99">[WKn]</option>
                <option value="98">[Ign]</option>
            </select>
            <input type="hidden" id="Text_<?php echo $cnt ?>" name="term[<?php echo $cnt ?>][text]" value="<?php echo $value ?>" />
            <input type="hidden" name="term[<?php echo $cnt ?>][lg]" value="<?php echo \tohtml($record['Ti2LgID']) ?>" />
        </td>
        </tr>
        <?php
    }
    ?>
    </table>
    <input type="hidden" name="tid" value="<?php echo $tid ?>" />
    <?php if ($nextOffset !== null) : ?>
    <input type="hidden" name="offset" value="<?php echo $nextOffset ?>" />
    <input type="hidden" name="sl" value="<?php echo $sl ?>" />
    <input type="hidden" name="tl" value="<?php echo $tl ?>" />
    <?php endif; ?>
    </form>

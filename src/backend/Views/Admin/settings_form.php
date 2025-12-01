<?php declare(strict_types=1);
/**
 * Settings Form View
 *
 * Variables expected:
 * - $settings: array of current settings values
 * - $themes: array of available themes (from ThemeService)
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

namespace Lwt\Views\Admin;

use Lwt\View\Helper\SelectOptionsBuilder;

?>
<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" data-lwt-settings-form>
<table class="tab1" cellspacing="0" cellpadding="5">
    <!-- ******************************************************* -->
    <tr>
        <th class="th1">Group</th>
        <th class="th1">Description</th>
        <th class="th1" colspan="2">Value</th>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <th class="th1 center" rowspan="1">Appearance</th>
        <td class="td1 center">Theme</td>
        <td class="td1 center">
            <select name="set-theme-dir" class="notempty respinput">
                <?php echo SelectOptionsBuilder::forThemes($themes, $settings['set-theme-dir']); ?>
            </select>
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <th class="th1 center" rowspan="7">Read Text Screen</th>
        <td class="td1 center">
            Height of left top frame <wbr /><b>without</b> audioplayer
        </td>
        <td class="td1 center">
            <input class="notempty posintnumber right setfocus respinput"
            type="number" min="0"
            name="set-text-h-frameheight-no-audio" data_info="Height of left top frame without audioplayer"
            value="<?php echo htmlspecialchars($settings['set-text-h-frameheight-no-audio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="3"
            size="3" /><br />Pixel </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">
            Height of left top frame <wbr /><b>with</b> audioplayer
        </td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0"
            name="set-text-h-frameheight-with-audio"
            data_info="Height of left top frame with audioplayer"
            value="<?php echo htmlspecialchars($settings['set-text-h-frameheight-with-audio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="3" size="3" /><br />
            Pixel
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Width of left frames</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0"
            name="set-text-l-framewidth-percent" data_info="Width of left frames"
            value="<?php echo htmlspecialchars($settings['set-text-l-framewidth-percent'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="2" size="2" />
            <br />Percent
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Height of right top frame</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0"
            name="set-text-r-frameheight-percent"  data_info="Height of right top frame"
            value="<?php echo htmlspecialchars($settings['set-text-r-frameheight-percent'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="2" size="2" />
            <br />Percent
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Button(s) for "words to do"</td>
        <td class="td1 center">
            <select name="set-words-to-do-buttons" class="notempty respinput">
            <?php echo SelectOptionsBuilder::forWordsToDoButtons($settings['set-words-to-do-buttons']); ?>
            </select>
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Tooltips</td>
        <td class="td1 center">
            <select name="set-tooltip-mode" class="notempty respinput">
            <?php echo SelectOptionsBuilder::forTooltipType($settings['set-tooltip-mode']); ?>
            </select>
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">New Term Translations per Page</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0"
            name="set-ggl-translation-per-page"  data_info="New Term Translations per Page"
            value="<?php echo htmlspecialchars($settings['set-ggl-translation-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="4" size="4" />
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <th class="th1 center middle" rowspan="5">Test<br />Screen</th>
        <td class="td1 center">Height of left top frame</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0"
            name="set-test-h-frameheight" data_info="Height of left top frame"
            value="<?php echo htmlspecialchars($settings['set-test-h-frameheight'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="3" size="3" />
            <br />Pixel
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Width of left frames</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0"
            name="set-test-l-framewidth-percent"  data_info="Width of left frames"
            value="<?php echo htmlspecialchars($settings['set-test-l-framewidth-percent'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="2" size="2" />
            <br />Percent
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Height of right top frame</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0"
            name="set-test-r-frameheight-percent"  data_info="Height of right top frame"
            value="<?php echo htmlspecialchars($settings['set-test-r-frameheight-percent'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="2" size="2" /><br />
            Percent
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">
            Waiting time after assessment to display next test
        </td>
        <td class="td1 center">
            <input class="notempty zeroposintnumber right respinput"
            type="number" min="0"
            name="set-test-main-frame-waiting-time"
            data_info="Waiting time after assessment to display next test"
            value="<?php echo htmlspecialchars($settings['set-test-main-frame-waiting-time'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="4" size="4" /><br />
            Milliseconds
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">
            Waiting Time to clear the message/edit frame
        </td>
        <td class="td1 center">
            <input class="notempty zeroposintnumber right respinput"
            type="number" min="0"
            name="set-test-edit-frame-waiting-time"
            data_info="Waiting Time to clear the message/edit frame"
            value="<?php echo htmlspecialchars($settings['set-test-edit-frame-waiting-time'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="8" size="8" /><br />
            Milliseconds
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <th class="th1 center" rowspan="3">Reading</th>
        <td class="td1 center">
            Visit only saved terms with status(es)...<br />
            (via keystrokes RIGHT, SPACE, LEFT, etc.)
        </td>
        <td class="td1 center">
            <select name="set-text-visit-statuses-via-key" class="respinput">
            <?php
            echo SelectOptionsBuilder::forWordStatus(
                $settings['set-text-visit-statuses-via-key'],
                true,
                true,
                true
            );
            ?>
            </select>
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Display translations of terms with status(es)</td>
        <td class="td1 center">
            <select name="set-display-text-frame-term-translation" class="respinput">
            <?php
            echo SelectOptionsBuilder::forWordStatus(
                $settings['set-display-text-frame-term-translation'],
                true,
                true,
                true
            );
            ?>
            </select>
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Position of translations</td>
        <td class="td1 center">
            <select name="set-text-frame-annotation-position" class="notempty respinput">
            <?php echo SelectOptionsBuilder::forAnnotationPosition($settings['set-text-frame-annotation-position']); ?>
            </select>
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <th class="th1 center">Testing</th>
        <td class="td1 center">Number of sentences <br />displayed from text, if available</td>
        <td class="td1 center">
            <select name="set-test-sentence-count" class="notempty respinput">
            <?php echo SelectOptionsBuilder::forSentenceCount($settings['set-test-sentence-count']); ?>
            </select>
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <th class="th1 center">Term Sentence<br />Generation</th>
        <td class="td1 center">Number of sentences <br />generated from text, if available</td>
        <td class="td1 center">
            <select name="set-term-sentence-count" class="notempty respinput">
            <?php echo SelectOptionsBuilder::forSentenceCount($settings['set-term-sentence-count']); ?>
            </select>
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <th class="th1 center">Similar<br />Terms</th>
        <td class="td1 center">
            Similar terms to be displayed<br />while adding/editing a term
        </td>
        <td class="td1 center">
            <input class="notempty zeroposintnumber right respinput"
            type="number" min="0"
            name="set-similar-terms-count"
            data_info="Similar terms to be displayed while adding/editing a term"
            value="<?php echo htmlspecialchars($settings['set-similar-terms-count'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="1" size="1" />
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <th class="th1 center">Term<br />Translations</th>
        <td class="td1 center">
            List of characters that<br />delimit different translations<br />
            (used in annotation selection)
        </td>
        <td class="td1 center">
            <input class="notempty center respinput" type="text"
            name="set-term-translation-delimiters"
            value="<?php echo htmlspecialchars($settings['set-term-translation-delimiters'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="8" size="8" />
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <th class="th1 center" rowspan="2">Text to Speech</th>
        <td class="td1 center">Save Audio Files to Disk</td>
        <td class="td1 center">
            <input type="checkbox" name="set-tts" value="1"
            <?php echo ((int)$settings['set-tts'] ? "checked" : ""); ?>  />
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Read word aloud</td>
        <td class="td1 center">
            <select name="set-hts" class="notempty respinput">
            <?php echo SelectOptionsBuilder::forHoverTranslation($settings['set-hts']); ?>
            </select>
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <th class="th1 center" rowspan="7">Text, Term,<br />Newsfeed &amp;<br />Tag Tables</th>
        <td class="td1 center">Texts per Page</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0" name="set-texts-per-page" data_info="Texts per Page"
            value="<?php echo htmlspecialchars($settings['set-texts-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="4" size="4" />
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Archived Texts per Page</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0" name="set-archivedtexts-per-page"
            data_info="Archived Texts per Page"
            value="<?php echo htmlspecialchars($settings['set-archivedtexts-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="4" size="4" />
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Terms per Page</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0" name="set-terms-per-page" data_info="Terms per Page"
            value="<?php echo htmlspecialchars($settings['set-terms-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="4" size="4" />
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Tags per Page</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0" name="set-tags-per-page" data_info="Tags per Page"
            value="<?php echo htmlspecialchars($settings['set-tags-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="4" size="4" />
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Feed Articles per Page</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0" name="set-articles-per-page" data_info="Feed Articles per Page"
            value="<?php echo htmlspecialchars($settings['set-articles-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="4" size="4" />
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Feeds per Page</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0" name="set-feeds-per-page" data_info="Feeds per Page"
            value="<?php echo htmlspecialchars($settings['set-feeds-per-page'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="4" size="4" />
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">Query Mode</td>
        <td class="td1 center">
            <select name="set-regex-mode" class="respinput">
            <?php echo SelectOptionsBuilder::forRegexMode($settings['set-regex-mode']); ?>
            </select>
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <th class="th1 center" rowspan="3">Newsfeeds</th>
        <td class="td1 center">Max Articles per Feed <b>with</b> cached text</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0" name="set-max-articles-with-text"
            data_info="Max Articles per Feed with cached text"
            value="<?php echo htmlspecialchars($settings['set-max-articles-with-text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="4" size="4" />
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">
            Max Articles per Feed <b>without</b> cached text
        </td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0" name="set-max-articles-without-text"
            data_info="Max Articles per Feed without cached text"
            value="<?php echo htmlspecialchars($settings['set-max-articles-without-text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="4" size="4" />
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 center">
            Max Texts per Feed<br />(older Texts are moved into "Text Archive")
        </td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0" name="set-max-texts-per-feed" data_info="Max Texts per Feed"
            value="<?php echo htmlspecialchars($settings['set-max-texts-per-feed'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
            maxlength="4" size="4" />
        </td>
        <td class="td1 center">
            <img src="/assets/icons/status-busy.png" title="Field must not be empty"
            alt="Field must not be empty" />
        </td>
    </tr>
    <!-- ******************************************************* -->
    <tr>
        <td class="td1 right" colspan="4">
            <input type="button" value="&lt;&lt; Back"
            data-action="settings-navigate" data-url="index.php" />
            <span class="nowrap"></span>
            <input type="button" value="Reset all settings to default"
            data-action="settings-navigate" data-url="/admin/settings?op=reset" />
            <span class="nowrap"></span>
            <input type="submit" name="op" value="Save" />
        </td>
    </tr>
    <!-- ******************************************************* -->
</table>
</form>

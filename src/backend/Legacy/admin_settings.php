<?php

/**
 * Preferences / Settings
 *
 * Call: /admin/settings?....
 *      ... op=Save ... do save
 *      ... op=Reset ... do reset to defaults
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 */

namespace Lwt\Interface\Settings;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Http/param_helpers.php';
require_once 'Core/Media/media_helpers.php';

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Settings;

if (isset($_REQUEST['op'])) {
    if ($_REQUEST['op'] == 'Save') {
        Settings::save(
            'set-theme-dir',
            $_REQUEST['set-theme-dir']
        );
    } else {
        $tbpref = Globals::getTablePrefix();
        Connection::execute("DELETE FROM {$tbpref}settings WHERE StKey LIKE 'set-%'");
    }
}
pagestart('Settings/Preferences', true);
$message = '';

if (isset($_REQUEST['op'])) {
    if ($_REQUEST['op'] == 'Save') {
        Settings::save(
            'set-text-h-frameheight-no-audio',
            $_REQUEST['set-text-h-frameheight-no-audio']
        );

        Settings::save(
            'set-text-h-frameheight-with-audio',
            $_REQUEST['set-text-h-frameheight-with-audio']
        );

        Settings::save(
            'set-text-l-framewidth-percent',
            $_REQUEST['set-text-l-framewidth-percent']
        );

        Settings::save(
            'set-text-r-frameheight-percent',
            $_REQUEST['set-text-r-frameheight-percent']
        );

        Settings::save(
            'set-test-h-frameheight',
            $_REQUEST['set-test-h-frameheight']
        );

        Settings::save(
            'set-test-l-framewidth-percent',
            $_REQUEST['set-test-l-framewidth-percent']
        );

        Settings::save(
            'set-test-r-frameheight-percent',
            $_REQUEST['set-test-r-frameheight-percent']
        );

        Settings::save(
            'set-words-to-do-buttons',
            $_REQUEST['set-words-to-do-buttons']
        );

        Settings::save(
            'set-tooltip-mode',
            $_REQUEST['set-tooltip-mode']
        );

        Settings::save(
            'set-ggl-translation-per-page',
            $_REQUEST['set-ggl-translation-per-page']
        );

        Settings::save(
            'set-test-main-frame-waiting-time',
            $_REQUEST['set-test-main-frame-waiting-time']
        );

        Settings::save(
            'set-test-edit-frame-waiting-time',
            $_REQUEST['set-test-edit-frame-waiting-time']
        );

        Settings::save(
            'set-test-sentence-count',
            $_REQUEST['set-test-sentence-count']
        );

        Settings::save(
            'set-term-sentence-count',
            $_REQUEST['set-term-sentence-count']
        );

        Settings::save(
            'set-tts',
            (string)(
                array_key_exists('set-tts', $_REQUEST) &&
                (int)$_REQUEST['set-tts'] ?
                1 : 0
            )
        );

        Settings::save(
            'set-hts',
            $_REQUEST['set-hts']
        );

        Settings::save(
            'set-archivedtexts-per-page',
            $_REQUEST['set-archivedtexts-per-page']
        );

        Settings::save(
            'set-texts-per-page',
            $_REQUEST['set-texts-per-page']
        );

        Settings::save(
            'set-terms-per-page',
            $_REQUEST['set-terms-per-page']
        );

        Settings::save(
            'set-regex-mode',
            $_REQUEST['set-regex-mode']
        );

        Settings::save(
            'set-tags-per-page',
            $_REQUEST['set-tags-per-page']
        );

        Settings::save(
            'set-articles-per-page',
            $_REQUEST['set-articles-per-page']
        );

        Settings::save(
            'set-feeds-per-page',
            $_REQUEST['set-feeds-per-page']
        );

        Settings::save(
            'set-max-articles-with-text',
            $_REQUEST['set-max-articles-with-text']
        );

        Settings::save(
            'set-max-articles-without-text',
            $_REQUEST['set-max-articles-without-text']
        );

        Settings::save(
            'set-max-texts-per-feed',
            $_REQUEST['set-max-texts-per-feed']
        );

        Settings::save(
            'set-text-visit-statuses-via-key',
            $_REQUEST['set-text-visit-statuses-via-key']
        );

        Settings::save(
            'set-display-text-frame-term-translation',
            $_REQUEST['set-display-text-frame-term-translation']
        );

        Settings::save(
            'set-text-frame-annotation-position',
            $_REQUEST['set-text-frame-annotation-position']
        );

        Settings::save(
            'set-term-translation-delimiters',
            $_REQUEST['set-term-translation-delimiters']
        );

        Settings::save(
            'set-mobile-display-mode',
            $_REQUEST['set-mobile-display-mode']
        );

        Settings::save(
            'set-similar-terms-count',
            $_REQUEST['set-similar-terms-count']
        );

        $message = 'Settings saved';
    } else {
        $message = 'All Settings reset to default values';
    }
}

echo error_message_with_hide($message, true);

?>
<script type="text/javascript" charset="utf-8">
    $(document).ready(lwtFormCheck.askBeforeExit);
</script>
<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
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
                <?php
                echo get_themes_selectoptions(
                    Settings::getWithDefault('set-theme-dir')
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
        <th class="th1 center" rowspan="7">Read Text Screen</th>
        <td class="td1 center">
            Height of left top frame <wbr /><b>without</b> audioplayer
        </td>
        <td class="td1 center">
            <input class="notempty posintnumber right setfocus respinput"
            type="number" min="0"
            name="set-text-h-frameheight-no-audio" data_info="Height of left top frame without audioplayer"
            value="<?php echo tohtml(Settings::getWithDefault('set-text-h-frameheight-no-audio')); ?>" maxlength="3"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-text-h-frameheight-with-audio')); ?>"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-text-l-framewidth-percent')); ?>"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-text-r-frameheight-percent')); ?>" maxlength="2" size="2" />
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
            <?php
            echo get_words_to_do_buttons_selectoptions(
                Settings::getWithDefault('set-words-to-do-buttons')
            );
            ?>
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
        <?php
        echo get_tooltip_selectoptions(
            Settings::getWithDefault('set-tooltip-mode')
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
        <td class="td1 center">New Term Translations per Page</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0"
            name="set-ggl-translation-per-page"  data_info="New Term Translations per Page"
            value="<?php echo tohtml(Settings::getWithDefault('set-ggl-translation-per-page')); ?>"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-test-h-frameheight')); ?>"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-test-l-framewidth-percent')); ?>"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-test-r-frameheight-percent')); ?>"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-test-main-frame-waiting-time')); ?>"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-test-edit-frame-waiting-time')); ?>"
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
        <th class="th1 center">Frame Set<br />Display Mode</th>
        <td class="td1 center">Select how frame sets are<br />displayed on different devices</td>
        <td class="td1 center">
            <select name="set-mobile-display-mode" class="respinput">
            <?php
            echo get_mobile_display_mode_selectoptions(
                Settings::getWithDefault('set-mobile-display-mode') // , true, true, true what is it???
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
        <th class="th1 center" rowspan="3">Reading</th>
        <td class="td1 center">
            Visit only saved terms with status(es)...<br />
            (via keystrokes RIGHT, SPACE, LEFT, etc.)
        </td>
        <td class="td1 center">
            <select name="set-text-visit-statuses-via-key" class="respinput">
            <?php
            echo get_wordstatus_selectoptions(
                Settings::getWithDefault('set-text-visit-statuses-via-key'),
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
            echo get_wordstatus_selectoptions(
                Settings::getWithDefault('set-display-text-frame-term-translation'),
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
            <?php
            echo get_annotation_position_selectoptions(
                Settings::getWithDefault('set-text-frame-annotation-position')
            );
            ?>
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
            <?php
            echo get_sentence_count_selectoptions(
                Settings::getWithDefault('set-test-sentence-count')
            );
            ?>
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
            <?php
            echo get_sentence_count_selectoptions(
                Settings::getWithDefault('set-term-sentence-count')
            );
            ?>
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
            value="<?php echo tohtml(Settings::getWithDefault('set-similar-terms-count')); ?>"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-term-translation-delimiters')); ?>" maxlength="8" size="8" />
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
            <?php echo ((int)Settings::getWithDefault('set-tts') ? "checked" : ""); ?>  />
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
            <?php
            echo get_hts_selectoptions(
                Settings::getWithDefault('set-hts')
            );
            ?>
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
            value="<?php echo tohtml(Settings::getWithDefault('set-texts-per-page')); ?>"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-archivedtexts-per-page')); ?>" maxlength="4" size="4" />
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
            value="<?php echo tohtml(Settings::getWithDefault('set-terms-per-page')); ?>" maxlength="4" size="4" />
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
            value="<?php echo tohtml(Settings::getWithDefault('set-tags-per-page')); ?>"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-articles-per-page')); ?>"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-feeds-per-page')); ?>"
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
            <?php
            echo get_regex_selectoptions(
                Settings::getWithDefault('set-regex-mode')
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
        <th class="th1 center" rowspan="3">Newsfeeds</th>
        <td class="td1 center">Max Articles per Feed <b>with</b> cached text</td>
        <td class="td1 center">
            <input class="notempty posintnumber right respinput" type="number"
            min="0" name="set-max-articles-with-text"
            data_info="Max Articles per Feed with cached text"
            value="<?php echo tohtml(Settings::getWithDefault('set-max-articles-with-text')); ?>"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-max-articles-without-text')); ?>"
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
            value="<?php echo tohtml(Settings::getWithDefault('set-max-texts-per-feed')); ?>"
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
            onclick="{lwtFormCheck.resetDirty(); location.href='index.php';}" />
            <span class="nowrap"></span>
            <input type="button" value="Reset all settings to default"
            onclick="{lwtFormCheck.resetDirty(); location.href='/admin/settings?op=reset';}" />
            <span class="nowrap"></span>
            <input type="submit" name="op" value="Save" />
        </td>
    </tr>
    <!-- ******************************************************* -->
</table>
</form>

<?php
pageend();
?>

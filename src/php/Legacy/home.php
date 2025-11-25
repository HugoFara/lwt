<?php

/**
 * \file
 * \brief LWT Start screen and main menu
 *
 * Call: index.php
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/index.html
 * @since   1.0.3
 *
 * "Learning with Texts" (LWT) is free and unencumbered software
 * released into the PUBLIC DOMAIN.
 *
 * Anyone is free to copy, modify, publish, use, compile, sell, or
 * distribute this software, either in source code form or as a
 * compiled binary, for any purpose, commercial or non-commercial,
 * and by any means.
 *
 * In jurisdictions that recognize copyright laws, the author or
 * authors of this software dedicate any and all copyright
 * interest in the software to the public domain. We make this
 * dedication for the benefit of the public at large and to the
 * detriment of our heirs and successors. We intend this
 * dedication to be an overt act of relinquishment in perpetuity
 * of all present and future rights to this software under
 * copyright law.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE
 * AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS BE LIABLE
 * FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * For more information, please refer to [http://unlicense.org/].
 */

// Connection check is now handled by front controller (index.php)

require_once 'inc/session_utility.php';

/**
 * Prepare the different SPAN opening tags
 *
 * @return string[] 3 different span levels
 *
 * @global string $tbpref       Database table prefix
 * @global string $fixed_tbpref Fixed database table prefix
 *
 * @psalm-return list{'<span title="Manage Table Sets" onclick="location.href='table_set_management.php';" class="click">'|'<span>', string, '<span title="Select Table Set" onclick="location.href='start.php';" class="click">'|'<span>'}
 */
function get_span_groups(): array
{
    global $tbpref, $fixed_tbpref;

    if ($tbpref == '') {
        $span2 = "<i>Default</i> Table Set</span>";
    } else {
        $span2 = "Table Set: <i>" . tohtml(substr($tbpref, 0, -1)) . "</i></span>";
    }

    if ($fixed_tbpref) {
        $span1 = '<span>';
        $span3 = '<span>';
    } else {
        $span1 = '<span title="Manage Table Sets" onclick="location.href=\'table_set_management.php\';" class="click">';
        if (count(getprefixes()) > 0) {
            $span3 = '<span title="Select Table Set" onclick="location.href=\'start.php\';" class="click">';
        } else {
            $span3 = '<span>';
        }
    }
    return array($span1, $span2, $span3);
}

/**
 * Display the current text options.
 *
 * @return void
 *
 * @global string $tbpref Database table prefix
 */
function do_current_text_info($textid)
{
    global $tbpref;
    $txttit = get_first_value(
        'SELECT TxTitle AS value
        FROM ' . $tbpref . 'texts
        WHERE TxID=' . $textid
    );
    if (!isset($txttit)) {
        return;
    }
    $txtlng = get_first_value(
        'SELECT TxLgID AS value FROM ' . $tbpref . 'texts WHERE TxID=' . $textid
    );
    $lngname = getLanguage($txtlng);
    $annotated = (int)get_first_value(
        "SELECT LENGTH(TxAnnotatedText) AS value
        FROM " . $tbpref . "texts
        WHERE TxID = " . $textid
    ) > 0;
    ?>

 <div style="height: 85px;">
    Last Text (<?php echo tohtml($lngname); ?>):<br />
    <i><?php echo tohtml($txttit); ?></i>
    <br />
    <a href="do_text.php?start=<?php echo $textid; ?>">
        <img src="icn/book-open-bookmark.png" title="Read" alt="Read" />&nbsp;Read
    </a>
    &nbsp; &nbsp;
    <a href="do_test.php?text=<?php echo $textid; ?>">
        <img src="icn/question-balloon.png" title="Test" alt="Test" />&nbsp;Test
    </a>
    &nbsp; &nbsp;
    <a href="print_text.php?text=<?php echo $textid; ?>">
        <img src="icn/printer.png" title="Print" alt="Print" />&nbsp;Print
    </a>
    <?php
    if ($annotated) {
        ?>
    &nbsp; &nbsp;
    <a href="print_impr_text.php?text=<?php echo $textid; ?>">
        <img src="icn/tick.png" title="Improved Annotated Text" alt="Improved Annotated Text" />&nbsp;Ann. Text
    </a>
        <?php
    }
    ?>
 </div>
    <?php
}

/**
 * Echo a select element to switch between languages.
 *
 * @return void
 */
function do_language_selectable($langid)
{
    ?>
<div for="filterlang">Language:
    <select id="filterlang" onchange="{setLang(document.getElementById('filterlang'),'index.php');}">
        <?php echo get_languages_selectoptions($langid, '[Select...]'); ?>
    </select>
</div>
    <?php
}

/**
 * When on a WordPress server, make a logout button
 *
 * @return void
 */
function wordpress_logout_link()
{
    if (isset($_SESSION['LWT-WP-User'])) {
        ?>

<div class="menu">
    <a href="wp_lwt_stop.php">
        <span style="font-size:115%; font-weight:bold; color:red;">LOGOUT</span> (from WordPress and LWT)
    </a>
</div>
        <?php
    }
}


/**
 * Return a lot of different server state variables.
 *
 * @return (false|float|string|string[])[] {0: string, 1: float, 2: string[], 3: string, 4: string, 5: string}
 * Table prefix, database size, server software, apache version, PHP version, MySQL
 * version
 *
 * @deprecated Use get_server_data_table, will be removed in 3.0.0.
 *
 * @psalm-return list{string, float, non-empty-list<string>, string, false|string, string}
 * @global string $tbpref Database table prefix
 * @global string $dbname Database name
 */
function get_server_data(): array
{
    global $tbpref, $dbname;
    $dbaccess_format = convert_string_to_sqlsyntax($dbname);
    $data_table = array();
    $data_table["prefix"] = convert_string_to_sqlsyntax_nonull($tbpref);
    $data_table["db_size"] = (float)get_first_value(
        "SELECT ROUND(SUM(data_length+index_length)/1024/1024, 1) AS value
        FROM information_schema.TABLES
        WHERE table_schema = $dbaccess_format
        AND table_name IN (
            '{$tbpref}archivedtexts', '{$tbpref}archtexttags', '{$tbpref}feedlinks', '{$tbpref}languages',
            '{$tbpref}newsfeeds', '{$tbpref}sentences', '{$tbpref}settings', '{$tbpref}tags', '{$tbpref}tags2',
            '{$tbpref}textitems2', '{$tbpref}texts', '{$tbpref}texttags', '{$tbpref}words', '{$tbpref}wordtags'
        )"
    );
    if (!isset($data_table["db_size"])) {
        $data_table["db_size"] = 0.0;
    }

    $data_table["serversoft"] = explode(' ', $_SERVER['SERVER_SOFTWARE']);
    $data_table["apache"] = "Apache/?";
    if (substr($data_table["serversoft"][0], 0, 7) == "Apache/") {
        $data_table["apache"] = $data_table["serversoft"][0];
    }
    $data_table["php"] = phpversion();
    $data_table["mysql"] = (string)get_first_value("SELECT VERSION() as value");
    return array(
        $data_table["prefix"], $data_table["db_size"], $data_table["serversoft"],
        $data_table["apache"], $data_table["php"], $data_table["mysql"]
    );
}

/**
 * Load the content of warnings for visual display.
 *
 * @return void
 */
function index_load_warnings()
{
    ?>
<script type="text/javascript">
    //<![CDATA[
    const loadWarnings = {
        cookiesDisabled: function () {
            if (!areCookiesEnabled()) {
                $('#cookies_disabled')
                .html('*** Cookies are not enabled! Please enable them! ***');
            }
        },

        shouldUpdate: function (from_version, to_version) {
            const regex = /^(\d+)\.(\d+)\.(\d+)(?:-[\w.-]+)?/;
            const match1 = from_version.match(regex);
            const match2 = to_version.match(regex);
            let level1, level2;

            for (let i = 1; i < 4; i++) {
                level1 = parseInt(match1[i], 10);
                level2 = parseInt(match2[i], 10);
                if (level1 < level2) {
                    return true;
                } else if (level1 > level2) {
                    return false;
                }
            }

            return null;
        },

        outdatedPHP: function (php_version) {
            const php_min_version = '8.0.0';
            if (loadWarnings.shouldUpdate(php_version, php_min_version)) {
                $('#php_update_required').html(
                    '*** Your PHP version is ' + php_version + ', but version ' +
                    php_min_version + ' is required. Please update it. ***'
                )
            }
        },

        updateLWT: function(lwt_version) {
            $.getJSON(
                'https://api.github.com/repos/hugofara/lwt/releases/latest'
            ).done(function (data) {
                const latest_version = data.tag_name;
                if (loadWarnings.shouldUpdate(lwt_version, latest_version)) {
                    $('#lwt_new_version').html(
                        '*** An update for LWT is available: ' +
                        latest_version +', your version is ' + lwt_version +
                        '. <a href="https://github.com/HugoFara/lwt/releases/tag/' +
                        latest_version + '">Download</a>.***'
                    );
                }
            });
        }
    }

    loadWarnings.cookiesDisabled();
    loadWarnings.outdatedPHP(<?php echo json_encode(phpversion()); ?>);
    loadWarnings.updateLWT(<?php echo json_encode(LWT_APP_VERSION); ?>);
    //]]>
</script>
    <?php
}

/**
 * Display the main body of the page.
 *
 * @return void
 *
 * @global string $tbpref Database table prefix
 * @global int    $debug  Debug mode enabled
 */
function index_do_main_page()
{
    global $tbpref, $debug;

    $currentlang = null;
    if (is_numeric(getSetting('currentlanguage'))) {
        $currentlang = (int) getSetting('currentlanguage');
    }

    $currenttext = null;
    if (is_numeric(getSetting('currenttext'))) {
        $currenttext = (int) getSetting('currenttext');
    }

    $langcnt = (int) get_first_value("SELECT COUNT(*) AS value FROM {$tbpref}languages");

    pagestart_nobody(
        "Home",
        "
        body {
            max-width: 1920px;
            margin: 20px;
        }"
    );
    echo_lwt_logo();
    echo '<h1>Learning With Texts (LWT)</h1>
    <h2>Home' . ($debug ? ' <span class="red">DEBUG</span>' : '') . '</h2>';

    ?>
<div class="red"><p id="php_update_required"></p></div>
<div class="red"><p id="cookies_disabled"></p></div>
<div class="msgblue"><p id="lwt_new_version"></p></div>

<p style="text-align: center;">Welcome to your language learning app!</p>

<div style="display: flex; justify-content: space-evenly; flex-wrap: wrap;">
    <div class="menu">
        <?php
        if ($langcnt == 0) {
            ?>
        <div><p>Hint: The database seems to be empty.</p></div>
        <a href="install_demo.php">Install the LWT demo database</a>
        <a href="edit_languages.php?new=1">Define the first language you want to learn</a>
            <?php
        } else if ($langcnt > 0) {
            do_language_selectable($currentlang);
            if ($currenttext !== null) {
                do_current_text_info($currenttext);
            }
        }
        ?>
            <a href="edit_languages.php">Languages</a>
    </div>

    <div class="menu">
        <a href="edit_texts.php">Texts</a>
        <a href="edit_archivedtexts.php">Text Archive</a>

        <a href="edit_texttags.php">Text Tags</a>
        <a href="check_text.php">Check Text</a>
        <a href="long_text_import.php">Import Long Text</a>
    </div>

    <div class="menu">
        <a href="edit_words.php" title="View and edit saved words and expressions">Terms</a>
        <a href="edit_tags.php">Term Tags</a>
        <a href="upload_words.php">Import Terms</a>
    </div>

    <div class="menu">
        <a href="do_feeds.php?check_autoupdate=1">Newsfeeds</a>
        <a href="backup_restore.php" title="Backup, restore or empty database">Database</a>
    </div>

    <div class="menu">
        <a href="statistics.php" title="Text statistics">Statistics</a>
        <a href="docs/info.html">Help</a>
        <a href="server_data.php" title="Various data useful for debug">Server Data</a>
    </div>

    <div class="menu">
        <a href="settings.php">Settings</a>
        <a href="text_to_speech_settings.php" title="Text-to-Speech settings">Text-to-Speech</a>
        <a href="mobile.php" title="Mobile LWT is a legacy function">Mobile LWT (Deprecated)</a>
    </div>

    <?php wordpress_logout_link(); ?>

</div>
<p>
    This is LWT Version <?php echo get_version(); ?>,
    <a href="start.php"><?php echo ($tbpref == '' ? 'default table set' : 'table prefixed with "' . $tbpref . '"') ?></a>.
    </p>
<br style="clear: both;" />
<footer>
    <p class="small">
        <a target="_blank" href="http://unlicense.org/" style="vertical-align: top;">
            <img alt="Public Domain" title="Public Domain" src="img/public_domain.png" style="display: inline;" />
        </a>
        <a href="https://sourceforge.net/projects/learning-with-texts/" target="_blank">"Learning with Texts" (LWT)</a> is free
        and unencumbered software released into the
        <a href="https://en.wikipedia.org/wiki/Public_domain_software" target="_blank">PUBLIC DOMAIN</a>.
        <a href="http://unlicense.org/" target="_blank">More information and detailed Unlicense ...</a>
    </p>
</footer>
    <?php
    index_load_warnings();
    pageend();
}

index_do_main_page();

?>

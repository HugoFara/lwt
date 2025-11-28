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
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/index.html
 * @since    1.0.3
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

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Tag/tags.php';
require_once 'Core/Text/text_helpers.php';
require_once 'Core/Language/language_utilities.php';

use Lwt\Services\HomeService;

require_once __DIR__ . '/../Services/HomeService.php';

/**
 * Prepare the different SPAN opening tags
 *
 * @return string[]
 *
 * @deprecated Use HomeService::getTableSetSpanGroups() instead
 *
 * @psalm-return list{'<span title="Manage Table Sets" onclick="location.href='/admin/tables';" class="click">'|'<span>', string, '<span title="Select Table Set" onclick="location.href='/mobile/start';" class="click">'|'<span>'}
 */
function get_span_groups(): array
{
    $homeService = new HomeService();
    $groups = $homeService->getTableSetSpanGroups();
    return array($groups['span1'], $groups['span2'], $groups['span3']);
}

/**
 * Display the current text options.
 *
 * @param int              $textid      Text ID
 * @param HomeService|null $homeService Optional HomeService instance for dependency injection
 *
 * @return void
 */
function do_current_text_info(int $textid, ?HomeService $homeService = null): void
{
    if ($homeService === null) {
        $homeService = new HomeService();
    }

    $textInfo = $homeService->getCurrentTextInfo($textid);
    if ($textInfo === null) {
        return;
    }

    $lngname = $textInfo['language_name'];
    $txttit = $textInfo['title'];
    $annotated = $textInfo['annotated'];
    ?>

 <div style="height: 85px;">
    Last Text (<?php echo tohtml($lngname); ?>):<br />
    <i><?php echo tohtml($txttit); ?></i>
    <br />
    <a href="/text/read?start=<?php echo $textid; ?>">
        <img src="/assets/icons/book-open-bookmark.png" title="Read" alt="Read" />&nbsp;Read
    </a>
    &nbsp; &nbsp;
    <a href="/test?text=<?php echo $textid; ?>">
        <img src="/assets/icons/question-balloon.png" title="Test" alt="Test" />&nbsp;Test
    </a>
    &nbsp; &nbsp;
    <a href="/text/print-plain?text=<?php echo $textid; ?>">
        <img src="/assets/icons/printer.png" title="Print" alt="Print" />&nbsp;Print
    </a>
    <?php
    if ($annotated) {
        ?>
    &nbsp; &nbsp;
    <a href="/text/print?text=<?php echo $textid; ?>">
        <img src="/assets/icons/tick.png" title="Improved Annotated Text" alt="Improved Annotated Text" />&nbsp;Ann. Text
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
function do_language_selectable(int $langid)
{
    ?>
<div for="filterlang">Language:
    <select id="filterlang" onchange="{setLang(document.getElementById('filterlang'),'/');}"">
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
    <a href="/wordpress/stop">
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
 * @deprecated Use ServerDataService::getServerData() instead, will be removed in 3.0.0.
 *
 * @psalm-return list{string, float, non-empty-list<string>, string, false|string, string}
 */
function get_server_data(): array
{
    $homeService = new HomeService();
    $data = $homeService->getServerData();
    return array(
        $data['prefix'],
        $data['db_size'],
        $data['server_software'],
        $data['apache'],
        $data['php'],
        $data['mysql']
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
 * @param HomeService|null $homeService Optional HomeService instance for dependency injection
 *
 * @return void
 */
function index_do_main_page(?HomeService $homeService = null): void
{
    if ($homeService === null) {
        $homeService = new HomeService();
    }

    $dashboardData = $homeService->getDashboardData();
    $tbpref = $dashboardData['table_prefix'];
    $debug = $dashboardData['is_debug'];
    $currentlang = $dashboardData['current_language_id'];
    $currenttext = $dashboardData['current_text_id'];
    $langcnt = $dashboardData['language_count'];

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
        <a href="/admin/install-demo">Install the LWT demo database</a>
        <a href="/languages?new=1">Define the first language you want to learn</a>
            <?php
        } elseif ($langcnt > 0) {
            do_language_selectable($currentlang);
            if ($currenttext !== null) {
                do_current_text_info($currenttext, $homeService);
            }
        }
        ?>
            <a href="/languages">Languages</a>
    </div>

    <div class="menu">
        <a href="/texts">Texts</a>
        <a href="/text/archived">Text Archive</a>

        <a href="/tags/text">Text Tags</a>
        <a href="/text/check">Check Text</a>
        <a href="/text/import-long">Import Long Text</a>
    </div>

    <div class="menu">
        <a href="/words/edit" title="View and edit saved words and expressions">Terms</a>
        <a href="/tags">Term Tags</a>
        <a href="/word/upload">Import Terms</a>
    </div>

    <div class="menu">
        <a href="/feeds?check_autoupdate=1">Newsfeeds</a>
        <a href="/admin/backup" title="Backup, restore or empty database">Database</a>
    </div>

    <div class="menu">
        <a href="/admin/statistics" title="Text statistics">Statistics</a>
        <a href="docs/info.html">Help</a>
        <a href="/admin/server-data" title="Various data useful for debug">Server Data</a>
    </div>

    <div class="menu">
        <a href="/admin/settings">Settings</a>
        <a href="/admin/settings/tts" title="Text-to-Speech settings">Text-to-Speech</a>
        <a href="/mobile" title="Mobile LWT is a legacy function">Mobile LWT (Deprecated)</a>
    </div>

    <?php wordpress_logout_link(); ?>

</div>
<p>
    This is LWT Version <?php echo get_version(); ?>,
    <a href="/mobile/start"><?php echo ($tbpref == '' ? 'default table set' : 'table prefixed with "' . $tbpref . '"') ?></a>.
    </p>
<br style="clear: both;" />
<footer>
    <p class="small">
        <a target="_blank" href="http://unlicense.org/" style="vertical-align: top;">
            <img alt="Public Domain" title="Public Domain" src="/assets/images/public_domain.png" style="display: inline;" />
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

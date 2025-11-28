<?php

/**
 * Home Page View
 *
 * Variables expected:
 * - $dashboardData: array Dashboard data from HomeService
 * - $homeService: HomeService instance
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

namespace Lwt\Views\Home;

/**
 * Display the current text options.
 *
 * @param int         $textid      Text ID
 * @param array|null  $textInfo    Text information array
 *
 * @return void
 */
function renderCurrentTextInfo(int $textid, ?array $textInfo): void
{
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
    <?php if ($annotated): ?>
    &nbsp; &nbsp;
    <a href="/text/print?text=<?php echo $textid; ?>">
        <img src="/assets/icons/tick.png" title="Improved Annotated Text" alt="Improved Annotated Text" />&nbsp;Ann. Text
    </a>
    <?php endif; ?>
</div>
    <?php
}

/**
 * Echo a select element to switch between languages.
 *
 * @param int $langid Current language ID
 *
 * @return void
 */
function renderLanguageSelector(int $langid): void
{
    ?>
<div for="filterlang">Language:
    <select id="filterlang" onchange="{setLang(document.getElementById('filterlang'),'/');}">
        <?php echo get_languages_selectoptions($langid, '[Select...]'); ?>
    </select>
</div>
    <?php
}

/**
 * When on a WordPress server, make a logout button.
 *
 * @param bool $isWordPress Whether WordPress session is active
 *
 * @return void
 */
function renderWordPressLogout(bool $isWordPress): void
{
    if ($isWordPress) {
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
 * Load the content of warnings for visual display.
 *
 * @return void
 */
function renderWarningsScript(): void
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

// Extract variables from dashboard data
$tbpref = $dashboardData['table_prefix'];
$debug = $dashboardData['is_debug'];
$currentlang = $dashboardData['current_language_id'] ?? 0;
$currenttext = $dashboardData['current_text_id'];
$langcnt = $dashboardData['language_count'];
$isWordPress = $dashboardData['is_wordpress'];
$currentTextInfo = $dashboardData['current_text_info'];
?>

<div class="red"><p id="php_update_required"></p></div>
<div class="red"><p id="cookies_disabled"></p></div>
<div class="msgblue"><p id="lwt_new_version"></p></div>

<p style="text-align: center;">Welcome to your language learning app!</p>

<div style="display: flex; justify-content: space-evenly; flex-wrap: wrap;">
    <div class="menu">
        <?php if ($langcnt == 0): ?>
        <div><p>Hint: The database seems to be empty.</p></div>
        <a href="/admin/install-demo">Install the LWT demo database</a>
        <a href="/languages?new=1">Define the first language you want to learn</a>
        <?php elseif ($langcnt > 0): ?>
        <?php renderLanguageSelector($currentlang); ?>
        <?php
        if ($currenttext !== null) {
            renderCurrentTextInfo($currenttext, $currentTextInfo);
        }
        ?>
        <?php endif; ?>
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

    <?php renderWordPressLogout($isWordPress); ?>

</div>
<p>
    This is LWT Version <?php echo get_version(); ?>,
    <a href="/mobile/start"><?php echo ($tbpref == '' ? 'default table set' : 'table prefixed with "' . $tbpref . '"'); ?></a>.
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
<?php renderWarningsScript(); ?>

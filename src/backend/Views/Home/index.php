<?php declare(strict_types=1);
/**
 * Home Page View
 *
 * Variables expected:
 * - $dashboardData: array Dashboard data from HomeService
 * - $homeService: HomeService instance
 * - $languages: array Languages data for select dropdown
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

use Lwt\View\Helper\SelectOptionsBuilder;

use const Lwt\Core\LWT_APP_VERSION;
use function Lwt\Core\get_version;

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
<div class="home-last-text">
    Last Text (<?php echo htmlspecialchars($lngname ?? '', ENT_QUOTES, 'UTF-8'); ?>):<br />
    <i><?php echo htmlspecialchars($txttit ?? '', ENT_QUOTES, 'UTF-8'); ?></i>
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
 * @param int   $langid    Current language ID
 * @param array $languages Languages data from LanguageService
 *
 * @return void
 */
function renderLanguageSelector(int $langid, array $languages): void
{
    ?>
<div for="filterlang">Language:
    <select id="filterlang" data-action="set-lang" data-redirect="/">
        <?php echo SelectOptionsBuilder::forLanguages($languages, $langid, '[Select...]'); ?>
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
        <span class="logout-text">LOGOUT</span> (from WordPress and LWT)
    </a>
</div>
        <?php
    }
}

/**
 * Load the content of warnings for visual display.
 *
 * Outputs a JSON config element that is read by home_warnings.ts.
 *
 * @return void
 */
function renderWarningsScript(): void
{
    $config = [
        'phpVersion' => phpversion(),
        'lwtVersion' => LWT_APP_VERSION,
    ];
    ?>
<script type="application/json" id="home-warnings-config">
<?php echo json_encode($config, JSON_UNESCAPED_SLASHES); ?>
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

<p class="center">Welcome to your language learning app!</p>

<div class="home-menu-container">
    <div class="menu menu-languages" data-menu-id="languages">
        <div class="menu-header">Languages</div>
        <div class="menu-content">
            <?php if ($langcnt == 0): ?>
            <div><p>Hint: The database seems to be empty.</p></div>
            <a href="/admin/install-demo">Install the LWT demo database</a>
            <a href="/languages?new=1">Define the first language you want to learn</a>
            <?php elseif ($langcnt > 0): ?>
            <?php renderLanguageSelector($currentlang, $languages); ?>
            <?php
            if ($currenttext !== null) {
                renderCurrentTextInfo($currenttext, $currentTextInfo);
            }
            ?>
            <?php endif; ?>
            <a href="/languages">Manage Languages</a>
        </div>
    </div>

    <div class="menu menu-texts" data-menu-id="texts">
        <div class="menu-header">Texts</div>
        <div class="menu-content">
            <a href="/texts">My Texts</a>
            <a href="/text/archived">Text Archive</a>
            <a href="/tags/text">Text Tags</a>
            <a href="/text/check">Check Text</a>
            <a href="/text/import-long">Import Long Text</a>
        </div>
    </div>

    <div class="menu menu-terms" data-menu-id="terms">
        <div class="menu-header">Vocabulary</div>
        <div class="menu-content">
            <a href="/words/edit" title="View and edit saved words and expressions">My Terms</a>
            <a href="/tags">Term Tags</a>
            <a href="/word/upload">Import Terms</a>
        </div>
    </div>

    <div class="menu menu-feeds" data-menu-id="feeds">
        <div class="menu-header">Content</div>
        <div class="menu-content">
            <a href="/feeds?check_autoupdate=1">Newsfeeds</a>
            <a href="/admin/backup" title="Backup, restore or empty database">Database</a>
        </div>
    </div>

    <div class="menu menu-admin" data-menu-id="admin">
        <div class="menu-header">Information</div>
        <div class="menu-content">
            <a href="/admin/statistics" title="Text statistics">Statistics</a>
            <a href="docs/info.html">Help</a>
            <a href="/admin/server-data" title="Various data useful for debug">Server Data</a>
        </div>
    </div>

    <div class="menu menu-settings" data-menu-id="settings">
        <div class="menu-header">Settings</div>
        <div class="menu-content">
            <a href="/admin/settings">General Settings</a>
            <a href="/admin/settings/tts" title="Text-to-Speech settings">Text-to-Speech</a>
            <a href="/mobile" title="Mobile LWT is a legacy function">Mobile LWT (Deprecated)</a>
        </div>
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
        <a target="_blank" href="http://unlicense.org/" class="footer-license-link">
            <img alt="Public Domain" title="Public Domain" src="/assets/images/public_domain.png" class="footer-license-icon" />
        </a>
        <a href="https://sourceforge.net/projects/learning-with-texts/" target="_blank">"Learning with Texts" (LWT)</a> is free
        and unencumbered software released into the
        <a href="https://en.wikipedia.org/wiki/Public_domain_software" target="_blank">PUBLIC DOMAIN</a>.
        <a href="http://unlicense.org/" target="_blank">More information and detailed Unlicense ...</a>
    </p>
</footer>
<?php renderWarningsScript(); ?>

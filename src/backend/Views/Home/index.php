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
<div class="box has-background-light home-last-text">
    <p class="has-text-weight-medium mb-2">
        Last Text (<?php echo htmlspecialchars($lngname ?? '', ENT_QUOTES, 'UTF-8'); ?>):
    </p>
    <p class="is-italic mb-3"><?php echo htmlspecialchars($txttit ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
    <div class="buttons are-small">
        <a href="/text/read?start=<?php echo $textid; ?>" class="button is-link is-light">
            <span class="icon is-small"><img src="/assets/icons/book-open-bookmark.png" alt="Read" /></span>
            <span>Read</span>
        </a>
        <a href="/test?text=<?php echo $textid; ?>" class="button is-info is-light">
            <span class="icon is-small"><img src="/assets/icons/question-balloon.png" alt="Test" /></span>
            <span>Test</span>
        </a>
        <a href="/text/print-plain?text=<?php echo $textid; ?>" class="button is-light">
            <span class="icon is-small"><img src="/assets/icons/printer.png" alt="Print" /></span>
            <span>Print</span>
        </a>
        <?php if ($annotated): ?>
        <a href="/text/print?text=<?php echo $textid; ?>" class="button is-success is-light">
            <span class="icon is-small"><img src="/assets/icons/tick.png" alt="Annotated" /></span>
            <span>Ann. Text</span>
        </a>
        <?php endif; ?>
    </div>
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
<div class="field">
    <label class="label" for="filterlang">Language</label>
    <div class="control">
        <div class="select is-fullwidth">
            <select id="filterlang" data-action="set-lang" data-redirect="/">
                <?php echo SelectOptionsBuilder::forLanguages($languages, $langid, '[Select...]'); ?>
            </select>
        </div>
    </div>
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
<div class="card menu menu-logout">
    <div class="card-content has-text-centered">
        <a href="/wordpress/stop" class="button is-danger is-outlined">
            <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
            <span><strong>LOGOUT</strong> (from WordPress and LWT)</span>
        </a>
    </div>
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

<!-- System notifications -->
<div class="notification is-danger is-light" id="php_update_required_box" style="display: none;">
    <p id="php_update_required"></p>
</div>
<div class="notification is-warning is-light" id="cookies_disabled_box" style="display: none;">
    <p id="cookies_disabled"></p>
</div>
<div class="notification is-info is-light" id="lwt_new_version_box" style="display: none;">
    <p id="lwt_new_version"></p>
</div>

<!-- Welcome message -->
<section class="hero is-small is-primary is-bold mb-5">
    <div class="hero-body py-4">
        <p class="title is-4 has-text-centered">Welcome to your language learning app!</p>
    </div>
</section>

<!-- Main menu grid -->
<div class="columns is-multiline is-centered home-menu-container">
    <!-- Languages Card -->
    <div class="column is-one-third-desktop is-half-tablet">
        <div class="card menu menu-languages" data-menu-id="languages">
            <header class="card-header menu-header">
                <p class="card-header-title">
                    <span class="icon-text">
                        <span class="icon"><i data-lucide="languages"></i></span>
                        <span>Languages</span>
                    </span>
                </p>
                <button class="card-header-icon" aria-label="toggle menu">
                    <span class="icon"><i data-lucide="chevron-down"></i></span>
                </button>
            </header>
            <div class="card-content menu-content">
                <?php if ($langcnt == 0): ?>
                <div class="notification is-warning is-light">
                    <p>The database seems to be empty.</p>
                </div>
                <a href="/admin/install-demo" class="button is-fullwidth is-info is-outlined mb-2">
                    <span class="icon"><i data-lucide="database"></i></span>
                    <span>Install Demo Database</span>
                </a>
                <a href="/languages?new=1" class="button is-fullwidth is-primary is-outlined mb-2">
                    <span class="icon"><i data-lucide="plus"></i></span>
                    <span>Define First Language</span>
                </a>
                <?php elseif ($langcnt > 0): ?>
                <?php renderLanguageSelector($currentlang, $languages); ?>
                <?php
                if ($currenttext !== null) {
                    renderCurrentTextInfo($currenttext, $currentTextInfo);
                }
                ?>
                <?php endif; ?>
                <a href="/languages" class="button is-fullwidth is-link is-light">
                    <span class="icon"><i data-lucide="settings"></i></span>
                    <span>Manage Languages</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Texts Card -->
    <div class="column is-one-third-desktop is-half-tablet">
        <div class="card menu menu-texts" data-menu-id="texts">
            <header class="card-header menu-header">
                <p class="card-header-title">
                    <span class="icon-text">
                        <span class="icon"><i data-lucide="book-open"></i></span>
                        <span>Texts</span>
                    </span>
                </p>
                <button class="card-header-icon" aria-label="toggle menu">
                    <span class="icon"><i data-lucide="chevron-down"></i></span>
                </button>
            </header>
            <div class="card-content menu-content">
                <a href="/texts" class="button is-fullwidth is-success is-light mb-2">
                    <span class="icon"><i data-lucide="file-text"></i></span>
                    <span>My Texts</span>
                </a>
                <a href="/text/archived" class="button is-fullwidth is-light mb-2">
                    <span class="icon"><i data-lucide="archive"></i></span>
                    <span>Text Archive</span>
                </a>
                <a href="/tags/text" class="button is-fullwidth is-light mb-2">
                    <span class="icon"><i data-lucide="tags"></i></span>
                    <span>Text Tags</span>
                </a>
                <a href="/text/check" class="button is-fullwidth is-light mb-2">
                    <span class="icon"><i data-lucide="check-circle"></i></span>
                    <span>Check Text</span>
                </a>
                <a href="/text/import-long" class="button is-fullwidth is-light">
                    <span class="icon"><i data-lucide="upload"></i></span>
                    <span>Import Long Text</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Vocabulary Card -->
    <div class="column is-one-third-desktop is-half-tablet">
        <div class="card menu menu-terms" data-menu-id="terms">
            <header class="card-header menu-header">
                <p class="card-header-title">
                    <span class="icon-text">
                        <span class="icon"><i data-lucide="book-marked"></i></span>
                        <span>Vocabulary</span>
                    </span>
                </p>
                <button class="card-header-icon" aria-label="toggle menu">
                    <span class="icon"><i data-lucide="chevron-down"></i></span>
                </button>
            </header>
            <div class="card-content menu-content">
                <a href="/words/edit" class="button is-fullwidth is-link is-light mb-2" title="View and edit saved words and expressions">
                    <span class="icon"><i data-lucide="list"></i></span>
                    <span>My Terms</span>
                </a>
                <a href="/tags" class="button is-fullwidth is-light mb-2">
                    <span class="icon"><i data-lucide="tag"></i></span>
                    <span>Term Tags</span>
                </a>
                <a href="/word/upload" class="button is-fullwidth is-light">
                    <span class="icon"><i data-lucide="upload"></i></span>
                    <span>Import Terms</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Content Card -->
    <div class="column is-one-third-desktop is-half-tablet">
        <div class="card menu menu-feeds" data-menu-id="feeds">
            <header class="card-header menu-header">
                <p class="card-header-title">
                    <span class="icon-text">
                        <span class="icon"><i data-lucide="rss"></i></span>
                        <span>Content</span>
                    </span>
                </p>
                <button class="card-header-icon" aria-label="toggle menu">
                    <span class="icon"><i data-lucide="chevron-down"></i></span>
                </button>
            </header>
            <div class="card-content menu-content">
                <a href="/feeds?check_autoupdate=1" class="button is-fullwidth is-warning is-light mb-2">
                    <span class="icon"><i data-lucide="newspaper"></i></span>
                    <span>Newsfeeds</span>
                </a>
                <a href="/admin/backup" class="button is-fullwidth is-light" title="Backup, restore or empty database">
                    <span class="icon"><i data-lucide="database"></i></span>
                    <span>Database</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Information Card -->
    <div class="column is-one-third-desktop is-half-tablet">
        <div class="card menu menu-admin" data-menu-id="admin">
            <header class="card-header menu-header">
                <p class="card-header-title">
                    <span class="icon-text">
                        <span class="icon"><i data-lucide="info"></i></span>
                        <span>Information</span>
                    </span>
                </p>
                <button class="card-header-icon" aria-label="toggle menu">
                    <span class="icon"><i data-lucide="chevron-down"></i></span>
                </button>
            </header>
            <div class="card-content menu-content">
                <a href="/admin/statistics" class="button is-fullwidth is-info is-light mb-2" title="Text statistics">
                    <span class="icon"><i data-lucide="bar-chart-2"></i></span>
                    <span>Statistics</span>
                </a>
                <a href="docs/info.html" class="button is-fullwidth is-light mb-2">
                    <span class="icon"><i data-lucide="help-circle"></i></span>
                    <span>Help</span>
                </a>
                <a href="/admin/server-data" class="button is-fullwidth is-light" title="Various data useful for debug">
                    <span class="icon"><i data-lucide="server"></i></span>
                    <span>Server Data</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Settings Card -->
    <div class="column is-one-third-desktop is-half-tablet">
        <div class="card menu menu-settings" data-menu-id="settings">
            <header class="card-header menu-header">
                <p class="card-header-title">
                    <span class="icon-text">
                        <span class="icon"><i data-lucide="settings"></i></span>
                        <span>Settings</span>
                    </span>
                </p>
                <button class="card-header-icon" aria-label="toggle menu">
                    <span class="icon"><i data-lucide="chevron-down"></i></span>
                </button>
            </header>
            <div class="card-content menu-content">
                <a href="/admin/settings" class="button is-fullwidth is-light mb-2">
                    <span class="icon"><i data-lucide="sliders"></i></span>
                    <span>General Settings</span>
                </a>
                <a href="/admin/settings/tts" class="button is-fullwidth is-light" title="Text-to-Speech settings">
                    <span class="icon"><i data-lucide="volume-2"></i></span>
                    <span>Text-to-Speech</span>
                </a>
            </div>
        </div>
    </div>

    <?php renderWordPressLogout($isWordPress); ?>
</div>

<!-- Version info -->
<p class="has-text-centered has-text-grey is-size-7 mt-4">
    LWT Version <?php echo get_version(); ?> &mdash;
    <?php echo ($tbpref == '' ? 'default table set' : 'table prefixed with "' . $tbpref . '"'); ?>
</p>

<!-- Footer -->
<footer class="footer mt-5 py-4">
    <div class="content has-text-centered is-size-7">
        <p>
            <a target="_blank" href="http://unlicense.org/" class="footer-license-link">
                <img alt="Public Domain" title="Public Domain" src="/assets/images/public_domain.png" class="footer-license-icon" />
            </a>
            <a href="https://sourceforge.net/projects/learning-with-texts/" target="_blank">"Learning with Texts" (LWT)</a> is free
            and unencumbered software released into the
            <a href="https://en.wikipedia.org/wiki/Public_domain_software" target="_blank">PUBLIC DOMAIN</a>.
            <a href="http://unlicense.org/" target="_blank">More information and detailed Unlicense ...</a>
        </p>
    </div>
</footer>
<?php renderWarningsScript(); ?>

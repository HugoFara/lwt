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
 * Load the content of warnings and initial data for visual display.
 *
 * Outputs a JSON config element that is read by home_app.ts.
 *
 * @param array|null $lastTextInfo Current text info for Alpine.js initial state
 *
 * @return void
 */
function renderHomeConfig(?array $lastTextInfo): void
{
    $config = [
        'phpVersion' => phpversion(),
        'lwtVersion' => LWT_APP_VERSION,
        'lastText' => $lastTextInfo,
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

// Prepare last text info for Alpine.js
$lastTextInfo = null;
if ($currentTextInfo !== null && $currenttext !== null) {
    $lastTextInfo = [
        'id' => $currenttext,
        'title' => $currentTextInfo['title'],
        'language_id' => $currentTextInfo['language_id'],
        'language_name' => $currentTextInfo['language_name'],
        'annotated' => $currentTextInfo['annotated'],
    ];
}
?>

<!-- Alpine.js Home App Container -->
<div x-data="homeApp()" x-cloak>

<!-- System notifications -->
<div class="notification is-danger is-light" x-show="warnings.phpOutdated.visible" x-transition>
    <p x-html="warnings.phpOutdated.message"></p>
</div>
<div class="notification is-warning is-light" x-show="warnings.cookiesDisabled.visible" x-transition>
    <p x-html="warnings.cookiesDisabled.message"></p>
</div>
<div class="notification is-info is-light" x-show="warnings.updateAvailable.visible" x-transition>
    <p x-html="warnings.updateAvailable.message"></p>
</div>

<!-- Language change notification -->
<div class="notification is-success is-light" x-show="languageNotification.visible" x-transition>
    <button class="delete" @click="languageNotification.visible = false"></button>
    <p x-text="languageNotification.message"></p>
</div>

<!-- Welcome message -->
<section class="hero is-small is-primary is-bold mb-5">
    <div class="hero-body py-4">
        <p class="title is-4 has-text-centered">Welcome to your language learning app!</p>
    </div>
</section>

<?php if ($langcnt == 0): ?>
<!-- Empty database: Getting Started section -->
<section class="section py-4 mb-5">
    <div class="container">
        <div class="box has-background-warning-light">
            <h3 class="title is-4 has-text-centered mb-4">
                <span class="icon-text">
                    <span class="icon"><i data-lucide="rocket"></i></span>
                    <span>Get Started</span>
                </span>
            </h3>
            <p class="has-text-centered mb-4">Your database is empty. Choose one of the options below to begin:</p>
            <div class="columns is-centered">
                <div class="column is-narrow">
                    <a href="/admin/install-demo" class="button is-medium is-info">
                        <span class="icon"><i data-lucide="database"></i></span>
                        <span>Install Demo Database</span>
                    </a>
                    <p class="help has-text-centered mt-2">Try LWT with sample texts</p>
                </div>
                <div class="column is-narrow">
                    <a href="/languages?new=1" class="button is-medium is-primary">
                        <span class="icon"><i data-lucide="plus"></i></span>
                        <span>Create Your First Language</span>
                    </a>
                    <p class="help has-text-centered mt-2">Start from scratch</p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php elseif ($langcnt > 0): ?>
<!-- Language selection and current text section -->
<section class="section py-4 mb-5">
    <div class="container">
        <div class="box has-background-link-light">
            <div class="columns is-vcentered">
                <!-- Language selector on the left -->
                <div class="column is-narrow">
                    <div class="field">
                        <label class="label" for="filterlang">Language</label>
                        <div class="control">
                            <div class="select is-medium">
                                <select id="filterlang" data-action="set-lang" data-ajax="true" data-redirect="/">
                                    <?php echo SelectOptionsBuilder::forLanguages($languages, $currentlang, '[Select...]'); ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current text in the center -->
                <div class="column">
                    <template x-if="lastText">
                        <div>
                            <p class="title is-5 mb-2" x-text="lastText.title"></p>
                            <div class="buttons">
                                <a :href="'/text/read?start=' + lastText.id" class="button is-link">
                                    <span class="icon"><i data-lucide="book-open"></i></span>
                                    <span>Read</span>
                                </a>
                                <a :href="'/test?text=' + lastText.id" class="button is-info is-light">
                                    <span class="icon"><i data-lucide="circle-help"></i></span>
                                    <span>Test</span>
                                </a>
                                <a :href="'/text/print-plain?text=' + lastText.id" class="button is-light">
                                    <span class="icon"><i data-lucide="printer"></i></span>
                                    <span>Print</span>
                                </a>
                                <template x-if="lastText.annotated">
                                    <a :href="'/text/print?text=' + lastText.id" class="button is-success is-light">
                                        <span class="icon"><i data-lucide="check"></i></span>
                                        <span>Ann. Text</span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template x-if="!lastText">
                        <p class="has-text-grey-light is-italic">No text selected for this language</p>
                    </template>
                </div>

                <!-- Language management buttons on the right -->
                <div class="column is-narrow">
                    <div class="buttons">
                        <a href="/languages" class="button is-link is-light" title="Manage Languages">
                            <span class="icon"><i data-lucide="settings"></i></span>
                            <span>Manage</span>
                        </a>
                        <a href="/languages?new=1" class="button is-primary is-light" title="Add New Language">
                            <span class="icon"><i data-lucide="plus"></i></span>
                            <span>New</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Main menu grid -->
<div class="columns is-multiline is-centered home-menu-container">
    <!-- Texts Card -->
    <div class="column is-one-third-desktop is-half-tablet">
        <div class="card menu menu-texts"
             :class="{ 'collapsed': isCollapsed('texts') }">
            <header class="card-header menu-header" @click="toggleMenu('texts')">
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
                    <span>Texts</span>
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
        <div class="card menu menu-terms"
             :class="{ 'collapsed': isCollapsed('terms') }">
            <header class="card-header menu-header" @click="toggleMenu('terms')">
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
                    <span>Terms</span>
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
        <div class="card menu menu-feeds"
             :class="{ 'collapsed': isCollapsed('feeds') }">
            <header class="card-header menu-header" @click="toggleMenu('feeds')">
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
        <div class="card menu menu-admin"
             :class="{ 'collapsed': isCollapsed('admin') }">
            <header class="card-header menu-header" @click="toggleMenu('admin')">
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
        <div class="card menu menu-settings"
             :class="{ 'collapsed': isCollapsed('settings') }">
            <header class="card-header menu-header" @click="toggleMenu('settings')">
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
                <a href="/admin/settings" class="button is-fullwidth is-light">
                    <span class="icon"><i data-lucide="sliders"></i></span>
                    <span>Settings</span>
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

<!-- Footer - Alpine.js Component -->
<footer class="footer mt-5 py-4" x-data="footer()">
    <div class="content has-text-centered is-size-7">
        <p>
            <a target="_blank" :href="licenseUrl" class="footer-license-link">
                <img alt="Public Domain" title="Public Domain" :src="licenseImageUrl" class="footer-license-icon" />
            </a>
            <a :href="links.project.href" target="_blank" x-text="links.project.text"></a> is free
            and unencumbered software released into the
            <a :href="links.publicDomain.href" target="_blank" x-text="links.publicDomain.text"></a>.
            <a :href="links.unlicense.href" target="_blank" x-text="links.unlicense.text"></a>
        </p>
    </div>
</footer>

</div><!-- End Alpine.js container -->

<?php renderHomeConfig($lastTextInfo); ?>

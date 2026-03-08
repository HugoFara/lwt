<?php

/**
 * Home Page View
 *
 * Variables expected:
 * - $dashboardData: array Dashboard data from HomeFacade
 * - $homeFacade: HomeFacade instance
 * - $languages: array Languages data for select dropdown
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Home\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Home\Views;

use Lwt\Shared\Infrastructure\ApplicationInfo;
use Lwt\Shared\Infrastructure\Http\UrlUtilities;


/**
 * When on a WordPress server, make a logout button.
 *
 * @param bool   $isWordPress Whether WordPress session is active
 * @param string $base        The application base path
 *
 * @return void
 */
function renderWordPressLogout(bool $isWordPress, string $base): void
{
    if ($isWordPress) {
        ?>
<div class="card menu menu-logout">
    <div class="card-content has-text-centered">
        <a href="<?php echo $base; ?>/wordpress/stop" class="button is-danger is-outlined">
            <span class="icon"><i data-lucide="log-out" style="width:16px;height:16px"></i></span>
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
 * @param string     $base         The application base path
 * @param int        $textCount    Number of texts for current language
 * @param int        $currentlang  Current language ID
 *
 * @return void
 */
function renderHomeConfig(?array $lastTextInfo, string $base, int $textCount, int $currentlang): void
{
    $config = [
        'phpVersion' => phpversion(),
        'lwtVersion' => ApplicationInfo::VERSION,
        'lastText' => $lastTextInfo,
        'basePath' => $base,
        'textCount' => $textCount,
        'currentLanguageId' => $currentlang,
    ];
    ?>
<script type="application/json" id="home-warnings-config">
    <?php echo json_encode($config, JSON_UNESCAPED_SLASHES); ?>
</script>
    <?php
}

// Validate injected variables from controller
assert(isset($dashboardData) && is_array($dashboardData));
assert(isset($languages) && is_array($languages));
/**
 * @var array<int, array{id: int, name: string}> $languages
 * @psalm-var list<array{id: int, name: string}> $languages
 */
/** @var array|null $lastTextInfo - Pre-computed by controller */

// Extract variables from dashboard data with proper types
/** @var int $currentlang */
$currentlang = $dashboardData['current_language_id'] ?? 0;
/** @var int $langcnt */
$langcnt = $dashboardData['language_count'] ?? 0;
/** @var bool $isWordPress */
$isWordPress = $dashboardData['is_wordpress'] ?? false;
/** @var int $textCount */
$textCount = $dashboardData['current_language_text_count'] ?? 0;

// Get base path for URL generation
$base = UrlUtilities::getBasePath();
?>

<!-- Alpine.js Home App Container -->
<div x-data="homeApp()" x-cloak>

<!-- System notifications -->
<div class="notification is-danger is-light" x-show="warnings.phpOutdated.visible" x-transition>
    <p>
        Your PHP version is <strong x-text="warnings.phpOutdated.phpVersion"></strong>,
        but version <strong x-text="warnings.phpOutdated.minVersion"></strong> is required.
        Please update PHP.
    </p>
</div>
<div class="notification is-warning is-light" x-show="warnings.cookiesDisabled.visible" x-transition>
    <p x-text="warnings.cookiesDisabled.message"></p>
</div>
<div class="notification is-info is-light" x-show="warnings.updateAvailable.visible" x-transition>
    <p>
        An update for LWT is available: <strong x-text="warnings.updateAvailable.latestVersion"></strong>
        (your version: <span x-text="warnings.updateAvailable.currentVersion"></span>).
        <a :href="warnings.updateAvailable.downloadUrl" class="button is-small is-info is-outlined ml-2">Download</a>
    </p>
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

<?php if ($langcnt == 0) : ?>
<!-- Empty database: Select a language -->
<section class="section py-6">
    <div class="container">
        <div class="has-text-centered">
            <a href="<?php echo $base; ?>/languages/new" class="button is-large is-primary">
                <span class="icon"><i data-lucide="languages"></i></span>
                <span>Select a language to learn</span>
            </a>
        </div>
    </div>
</section>
<?php elseif ($langcnt > 0 && $textCount == 0) : ?>
<!-- Has language but no texts: Add a text -->
<section class="section py-6">
    <div class="container">
        <div class="has-text-centered">
            <a href="<?php echo $base; ?>/texts/new" class="button is-large is-primary">
                <span class="icon"><i data-lucide="plus"></i></span>
                <span>Add a text to read</span>
            </a>
        </div>
    </div>
</section>
<?php elseif ($langcnt > 0) : ?>
<!-- Current text section -->
<section class="section py-4 mb-4">
    <div class="container">
        <!-- Language tabs -->
        <div class="tabs is-boxed is-medium mb-4">
            <ul>
                <?php /** @var array{id: int, name: string} $lang */ foreach ($languages as $lang) : ?>
                <li :class="{ 'is-active': currentLanguageId === <?php echo $lang['id']; ?> }">
                    <a @click.prevent="switchLanguage(<?php echo $lang['id']; ?>, '<?php echo htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8'); ?>')"
                       href="#">
                        <span><?php echo htmlspecialchars($lang['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Text cards (single row, horizontal scroll) -->
        <div style="display: flex; gap: 0.75rem; overflow-x: auto; padding-bottom: 0.5rem;"
             x-data="librarySearch()">
            <!-- Current text card -->
            <div style="flex-shrink: 0;">
                <template x-if="lastText">
                    <div class="box has-background-link-light" style="width: 280px; min-height: 180px;">
                        <p class="title is-5 mb-3" x-text="lastText.title"></p>
                        <!-- Statistics bar - colors match word status highlights -->
                        <div
                            class="mb-3"
                            x-show="lastText.stats && lastText.stats.total > 0"
                            :title="getStatsTitle()"
                        >
                            <div style="display: flex; height: 12px; border-radius: 6px;
                                overflow: hidden; background: #ddd;">
                                <div
                                    style="background: #5ABAFF;"
                                    :style="{ width: getStatPercent('unknown') + '%' }"
                                ></div>
                                <div
                                    style="background: #E85A3C;"
                                    :style="{ width: getStatPercent('s1') + '%' }"
                                ></div>
                                <div
                                    style="background: #E8893C;"
                                    :style="{ width: getStatPercent('s2') + '%' }"
                                ></div>
                                <div
                                    style="background: #E8B83C;"
                                    :style="{ width: getStatPercent('s3') + '%' }"
                                ></div>
                                <div
                                    style="background: #E8E23C;"
                                    :style="{ width: getStatPercent('s4') + '%' }"
                                ></div>
                                <div
                                    style="background: #66CC66;"
                                    :style="{ width: getStatPercent('s5') + '%' }"
                                ></div>
                                <div
                                    style="background: #CCFFCC;"
                                    :style="{ width: getStatPercent('s99') + '%' }"
                                ></div>
                                <div
                                    style="background: #888888;"
                                    :style="{ width: getStatPercent('s98') + '%' }"
                                ></div>
                            </div>
                        </div>
                        <div class="buttons">
                            <a :href="basePath + '/text/' + lastText.id + '/read'" class="button is-link is-medium">
                                <span class="icon"><i data-lucide="book-open"></i></span>
                                <span>Read</span>
                            </a>
                            <a
                                :href="basePath + '/review?text=' + lastText.id"
                                class="button is-info is-light is-medium"
                            >
                                <span class="icon"><i data-lucide="circle-help"></i></span>
                                <span>Review</span>
                            </a>
                        </div>
                        <template x-if="lastText.annotated">
                            <a
                                :href="basePath + '/text/' + lastText.id + '/print'"
                                class="button is-success is-light is-small"
                            >
                                <span class="icon"><i data-lucide="check"></i></span>
                                <span>Ann. Text</span>
                            </a>
                        </template>
                    </div>
                </template>
                <template x-if="!lastText">
                    <div class="box has-background-light" style="width: 280px; min-height: 180px;">
                        <p class="has-text-grey is-italic">No text selected for this language</p>
                    </div>
                </template>
            </div>

            <!-- New text card -->
            <div style="flex-shrink: 0;">
                <a
                    href="<?php echo $base; ?>/texts/new"
                    class="box has-background-primary-light has-text-centered"
                    style="width: 180px; min-height: 180px; display: flex;
                        flex-direction: column; justify-content: center; align-items: center;"
                >
                    <span class="icon is-large has-text-primary">
                        <i data-lucide="plus" style="width: 48px; height: 48px;"></i>
                    </span>
                    <p class="mt-3 has-text-weight-semibold">New Text</p>
                </a>
            </div>

            <!-- Search library card -->
            <div style="flex-shrink: 0;">
                <div
                    @click="open = true"
                    class="box has-background-warning-light has-text-centered"
                    style="width: 180px; min-height: 180px; display: flex;
                        flex-direction: column; justify-content: center; align-items: center;
                        cursor: pointer;"
                >
                    <span class="icon is-large has-text-warning-dark">
                        <i data-lucide="search" style="width: 48px; height: 48px;"></i>
                    </span>
                    <p class="mt-3 has-text-weight-semibold">Search Library</p>
                </div>
            </div>

            <!-- Library search modal -->
            <div class="modal" :class="{ 'is-active': open }">
                <div class="modal-background" @click="close()"></div>
                <div class="modal-card" style="max-width: 600px; width: 90vw;">
                    <header class="modal-card-head">
                        <p class="modal-card-title">Search Project Gutenberg</p>
                        <button class="delete" aria-label="close" @click="close()"></button>
                    </header>
                    <section class="modal-card-body">
                        <form @submit.prevent="search()" class="mb-4">
                            <div class="field has-addons">
                                <div class="control is-expanded">
                                    <input
                                        x-model="query"
                                        class="input"
                                        type="text"
                                        placeholder="Search by title or author..."
                                    />
                                </div>
                                <div class="control">
                                    <button
                                        type="submit"
                                        class="button is-warning"
                                        :class="{ 'is-loading': loading && !searched }"
                                        :disabled="loading"
                                    >
                                        <span class="icon"><i data-lucide="search"></i></span>
                                        <span>Search</span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Error message -->
                        <div x-show="error" class="notification is-danger is-light" x-text="error"></div>

                        <!-- Results count -->
                        <p
                            x-show="searched && !error && !loading"
                            class="has-text-grey is-size-7 mb-2"
                        >
                            <span x-text="totalCount"></span> books found
                        </p>

                        <!-- Results list -->
                        <div
                            x-show="results.length > 0"
                            style="max-height: 400px; overflow-y: auto;"
                        >
                            <template x-for="book in results" :key="book.id">
                                <div class="box p-3 mb-2" style="cursor: default;">
                                    <div class="is-flex is-justify-content-space-between is-align-items-start">
                                        <div style="flex: 1; min-width: 0;">
                                            <p
                                                class="has-text-weight-semibold is-size-6"
                                                x-text="book.title"
                                                style="overflow: hidden; text-overflow: ellipsis;"
                                            ></p>
                                            <p
                                                class="has-text-grey is-size-7"
                                                x-text="formatAuthors(book.authors)"
                                            ></p>
                                            <p class="has-text-grey-light is-size-7">
                                                <span x-text="formatDownloads(book.downloadCount)"></span> downloads
                                            </p>
                                        </div>
                                        <button
                                            @click="importBook(book)"
                                            class="button is-primary is-small ml-3"
                                            :class="{ 'is-loading': importing === book.id }"
                                            :disabled="importing !== null"
                                        >
                                            <span class="icon"><i data-lucide="download"></i></span>
                                            <span>Import</span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Load more -->
                        <button
                            x-show="hasMore && results.length > 0"
                            @click="loadMore()"
                            class="button is-small is-fullwidth mt-2"
                            :class="{ 'is-loading': loading }"
                            :disabled="loading"
                        >
                            Load more
                        </button>

                        <!-- No results -->
                        <p
                            x-show="searched && results.length === 0 && !loading && !error"
                            class="has-text-grey is-italic"
                        >
                            No books found. Try a different search term.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </div>
</section>

<?php endif; ?>

<?php if ($langcnt > 0 && $textCount > 0) : ?>
<!-- Main menu grid (only shown when user has texts) -->
<section class="section py-4">
<div class="container">
<div class="columns is-multiline is-centered home-menu-container">
    <!-- Languages Card -->
    <div class="column is-one-third-desktop is-half-tablet">
        <div class="card menu menu-languages"
             :class="{ 'collapsed': isCollapsed('languages') }">
            <header class="card-header menu-header" @click="toggleMenu('languages')">
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
                <a
                    href="<?php echo $base; ?>/languages"
                    class="button is-fullwidth is-link is-light mb-2"
                    title="Manage Languages"
                >
                    <span class="icon"><i data-lucide="list"></i></span>
                    <span>Manage Languages</span>
                </a>
                <a
                    href="<?php echo $base; ?>/languages/new"
                    class="button is-fullwidth is-link is-light"
                    title="Add New Language"
                >
                    <span class="icon"><i data-lucide="plus"></i></span>
                    <span>New Language</span>
                </a>
            </div>
        </div>
    </div>

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
                <a href="<?php echo $base; ?>/texts" class="button is-fullwidth is-success is-light mb-2">
                    <span class="icon"><i data-lucide="file-text"></i></span>
                    <span>Texts</span>
                </a>
                <a href="<?php echo $base; ?>/tags/text" class="button is-fullwidth is-success is-light mb-2">
                    <span class="icon"><i data-lucide="tags"></i></span>
                    <span>Text Tags</span>
                </a>
                <a href="<?php echo $base; ?>/feeds?check_autoupdate=1" class="button is-fullwidth is-success is-light">
                    <span class="icon"><i data-lucide="newspaper"></i></span>
                    <span>Newsfeeds</span>
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
                <a
                    href="<?php echo $base; ?>/words/edit"
                    class="button is-fullwidth is-link is-light mb-2"
                    title="View and edit saved words and expressions"
                >
                    <span class="icon"><i data-lucide="list"></i></span>
                    <span>Terms</span>
                </a>
                <a href="<?php echo $base; ?>/tags" class="button is-fullwidth is-link is-light mb-2">
                    <span class="icon"><i data-lucide="tag"></i></span>
                    <span>Term Tags</span>
                </a>
                <a href="<?php echo $base; ?>/word/upload" class="button is-fullwidth is-link is-light">
                    <span class="icon"><i data-lucide="upload"></i></span>
                    <span>Import Terms</span>
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
                <a
                    href="<?php echo $base; ?>/admin/statistics"
                    class="button is-fullwidth is-info is-light mb-2"
                    title="Text statistics"
                >
                    <span class="icon"><i data-lucide="bar-chart-2"></i></span>
                    <span>Statistics</span>
                </a>
                <a href="<?php echo $base; ?>/docs/info.html" class="button is-fullwidth is-info is-light mb-2">
                    <span class="icon"><i data-lucide="help-circle"></i></span>
                    <span>Help</span>
                </a>
                <a
                    href="<?php echo $base; ?>/admin/server-data"
                    class="button is-fullwidth is-info is-light"
                    title="Various data useful for debug"
                >
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
                <a href="<?php echo $base; ?>/admin/settings" class="button is-fullwidth is-light mb-2">
                    <span class="icon"><i data-lucide="sliders"></i></span>
                    <span>Settings</span>
                </a>
                <a
                    href="<?php echo $base; ?>/admin/backup"
                    class="button is-fullwidth is-light"
                    title="Backup, restore or empty database"
                >
                    <span class="icon"><i data-lucide="database"></i></span>
                    <span>Database</span>
                </a>
            </div>
        </div>
    </div>

    <?php renderWordPressLogout($isWordPress, $base); ?>
</div>
</div>
</section>
<?php endif; ?>

<!-- Version info -->
<p class="has-text-centered has-text-grey is-size-7 mt-4">
    LWT Version <?php echo ApplicationInfo::getVersion(); ?>
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

<?php renderHomeConfig($lastTextInfo, $base, $textCount, $currentlang); ?>

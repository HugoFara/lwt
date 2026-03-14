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

/**
 * Render the suggestions card grid (reused for onboarding and main page).
 *
 * Must be called inside a `gutenbergSuggestions` Alpine scope.
 *
 * @return void
 */
function renderSuggestionsGrid(): void
{
    ?>
    <!-- Loading state -->
    <div x-show="loading && books.length === 0" class="has-text-centered py-4">
        <span class="icon is-large has-text-grey-light">
            <i data-lucide="loader" style="width: 32px; height: 32px; animation: spin 1s linear infinite;"></i>
        </span>
    </div>

    <!-- Error -->
    <div x-show="error" class="notification is-danger is-light is-size-7" x-text="error"></div>

    <!-- Books grid (horizontal scroll) -->
    <div
        :style="books.length > 0
            ? 'display: flex; flex-wrap: nowrap; gap: 0.75rem; overflow-x: auto; padding-bottom: 0.5rem;'
            : 'display: none;'"
    >
        <template x-for="book in books" :key="book.id">
            <div
                class="box p-3"
                style="flex: 0 0 220px; width: 220px; min-width: 220px; min-height: 140px;
                    display: flex; flex-direction: column; justify-content: space-between;"
            >
                <div>
                    <div class="is-flex is-align-items-center mb-1" style="gap: 0.4rem;">
                        <p
                            class="has-text-weight-semibold is-size-7"
                            x-text="book.title"
                            style="overflow: hidden; text-overflow: ellipsis;
                                display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"
                        ></p>
                    </div>
                    <span
                        x-show="book.difficultyTier"
                        class="tag is-rounded mb-1"
                        style="font-size: 0.65rem;"
                        :class="tierClass(book.difficultyTier || '')"
                        x-text="tierLabel(book.difficultyTier || '')"
                    ></span>
                    <p
                        class="has-text-grey is-size-7"
                        x-text="formatAuthors(book.authors)"
                        style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                    ></p>
                </div>
                <div class="mt-2">
                    <button
                        @click="previewBook(book)"
                        class="button is-info is-small is-fullwidth"
                        :class="{ 'is-loading': previewLoading && previewBookId === book.id }"
                        :disabled="previewLoading && previewBookId === book.id"
                    >
                        <span class="icon"><i data-lucide="bar-chart-2"></i></span>
                        <span>Preview</span>
                    </button>
                </div>
                <!-- Preview panel -->
                <template x-if="previewBookId === book.id && !previewLoading">
                    <div class="mt-2 pt-2" style="border-top: 1px solid #eee;">
                        <template x-if="previewError">
                            <p class="has-text-danger is-size-7" x-text="previewError"></p>
                        </template>
                        <template x-if="previewData && !previewError">
                            <div>
                                <progress
                                    class="progress is-small mb-2"
                                    :class="coverageClass(previewData.difficulty_label)"
                                    :value="previewData.coverage_percent"
                                    max="100"
                                ></progress>
                                <p class="is-size-7">
                                    You know
                                    <strong x-text="previewData.coverage_percent + '%'"></strong>
                                    of unique words
                                </p>
                                <button
                                    @click="importBook(book)"
                                    class="button is-primary is-small is-fullwidth mt-2"
                                    :class="{ 'is-loading': importing === book.id }"
                                    :disabled="importing !== null"
                                >
                                    <span class="icon"><i data-lucide="download"></i></span>
                                    <span>Import</span>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- Load more -->
    <div x-show="hasMore && books.length > 0" class="has-text-centered mt-2">
        <button
            @click="loadMore()"
            class="button is-small is-light"
            :class="{ 'is-loading': loading }"
            :disabled="loading"
        >
            <span class="icon"><i data-lucide="chevron-right"></i></span>
            <span>Load more</span>
        </button>
    </div>
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
<?php elseif ($langcnt > 0) : ?>
<!-- Current text section -->
<section class="section py-4 mb-4">
    <div class="container">
        <!-- Text cards (single row, horizontal scroll) -->
        <div style="display: flex; gap: 0.75rem; overflow-x: auto; padding-bottom: 0.5rem;">
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
                    <div class="box has-background-light" style="width: 280px; min-height: 180px;
                        display: flex; flex-direction: column; justify-content: center; align-items: center;">
                        <span class="icon is-large has-text-grey-light mb-2">
                            <i data-lucide="book-open" style="width: 36px; height: 36px;"></i>
                        </span>
                        <p class="has-text-grey is-size-7 has-text-centered">
                            Add a text or import a book to start reading
                        </p>
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

            <!-- Search library card (plain HTML, opens modal via DOM event) -->
            <div style="flex-shrink: 0;">
                <div
                    data-action="open-library-search"
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
        </div>

        <!-- Gutenberg suggestions (second row) -->
        <div x-data="gutenbergSuggestions" x-cloak class="mt-4">
            <template x-if="books.length > 0 || loading">
                <div>
                    <p class="is-size-6 has-text-grey mb-2">
                        <span class="icon-text">
                            <span class="icon"><i data-lucide="book-open-text"></i></span>
                            <span>Suggested from Project Gutenberg</span>
                        </span>
                    </p>
                    <?php renderSuggestionsGrid(); ?>
                </div>
            </template>
        </div>
    </div>
</section>

<?php endif; ?>

<?php if ($langcnt > 0) : ?>
<?php renderWordPressLogout($isWordPress, $base); ?>
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

<!-- Library search modal (separate Alpine scope, outside homeApp) -->
<div x-data="librarySearch" @open-library-search.document="open = true" x-cloak>
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
                                    <div class="is-flex is-align-items-center" style="gap: 0.5rem;">
                                        <p
                                            class="has-text-weight-semibold is-size-6"
                                            x-text="book.title"
                                            style="overflow: hidden; text-overflow: ellipsis;"
                                        ></p>
                                        <span
                                            x-show="book.difficultyTier"
                                            class="tag is-rounded is-small"
                                            :class="tierClass(book.difficultyTier || '')"
                                            x-text="tierLabel(book.difficultyTier || '')"
                                        ></span>
                                    </div>
                                    <p
                                        class="has-text-grey is-size-7"
                                        x-text="formatAuthors(book.authors)"
                                    ></p>
                                    <p class="has-text-grey-light is-size-7">
                                        <span x-text="formatDownloads(book.downloadCount)"></span> downloads
                                    </p>
                                </div>
                                <div class="buttons are-small ml-3" style="flex-shrink: 0;">
                                    <button
                                        @click="togglePreview(book)"
                                        class="button is-info is-outlined is-small"
                                        :class="{ 'is-loading': previewLoading && previewBookId === book.id }"
                                        :disabled="previewLoading && previewBookId === book.id"
                                        title="Analyze difficulty"
                                    >
                                        <span class="icon"><i data-lucide="bar-chart-2"></i></span>
                                    </button>
                                    <button
                                        @click="importBook(book)"
                                        class="button is-primary is-small"
                                        :class="{ 'is-loading': importing === book.id }"
                                        :disabled="importing !== null"
                                    >
                                        <span class="icon"><i data-lucide="download"></i></span>
                                        <span>Import</span>
                                    </button>
                                </div>
                            </div>
                            <!-- Preview panel -->
                            <template x-if="previewBookId === book.id && !previewLoading">
                                <div class="mt-3 pt-3" style="border-top: 1px solid #eee;">
                                    <template x-if="previewError">
                                        <p class="has-text-danger is-size-7" x-text="previewError"></p>
                                    </template>
                                    <template x-if="previewData && !previewError">
                                        <div>
                                            <progress
                                                class="progress is-small mb-2"
                                                :class="coverageClass(previewData.difficulty_label)"
                                                :value="previewData.coverage_percent"
                                                max="100"
                                            ></progress>
                                            <p class="is-size-7">
                                                You know
                                                <strong x-text="previewData.coverage_percent + '%'"></strong>
                                                of unique words
                                                (<span x-text="previewData.known_words"></span>/<span x-text="previewData.total_unique_words"></span>)
                                            </p>
                                            <div x-show="previewData.sample_unknown_words.length > 0" class="mt-2">
                                                <p class="has-text-grey is-size-7 mb-1">Unknown words in sample:</p>
                                                <div class="tags">
                                                    <template x-for="w in previewData.sample_unknown_words" :key="w">
                                                        <span class="tag is-light is-small" x-text="w"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
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

<?php renderHomeConfig($lastTextInfo, $base, $textCount, $currentlang); ?>

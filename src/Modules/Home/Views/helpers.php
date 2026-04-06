<?php

/**
 * Home Page View Helpers
 *
 * Pure helper functions used by index.php to render parts of the
 * home page. Kept in a separate file so the view itself only
 * contains side-effect HTML output, satisfying PSR-12.
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
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Globals;

/**
 * When on a WordPress server, render a logout button.
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
            <span><?= __('home.wp_logout') ?></span>
        </a>
    </div>
</div>
        <?php
    }
}

/**
 * Output the JSON config element read by home_app.ts.
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
    // Only admins should see the "new LWT version available" notification,
    // and only when the admin setting is enabled.
    $isAdmin = !Globals::isMultiUserEnabled() || Globals::isCurrentUserAdmin();
    $checkForUpdates = $isAdmin
        && Settings::getWithDefault('set-check-for-updates') !== '0';

    $config = [
        'phpVersion' => phpversion(),
        'lwtVersion' => ApplicationInfo::VERSION,
        'lastText' => $lastTextInfo,
        'basePath' => $base,
        'textCount' => $textCount,
        'currentLanguageId' => $currentlang,
        'checkForUpdates' => $checkForUpdates,
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
                        <span><?= __('home.preview') ?></span>
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
                                <p class="is-size-7" x-text="coverageLabel(previewData)"></p>
                                <button
                                    @click="importBook(book)"
                                    class="button is-primary is-small is-fullwidth mt-2"
                                    :class="{ 'is-loading': importing === book.id }"
                                    :disabled="importing !== null"
                                >
                                    <span class="icon"><i data-lucide="download"></i></span>
                                    <span><?= __('home.import') ?></span>
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
            <span><?= __('home.load_more') ?></span>
        </button>
    </div>
    <?php
}

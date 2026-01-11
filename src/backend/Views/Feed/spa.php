<?php

/**
 * Feed Manager SPA View - Alpine.js Single Page Application
 *
 * This view provides a reactive feed management interface with:
 * - Feed list with filtering, sorting, and pagination
 * - Article browsing with import functionality
 * - Create/edit feed forms
 * - Bulk actions
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

declare(strict_types=1);

namespace Lwt\Views\Feed;

use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\IconHelper;

?>

<!-- Notifications -->
<div
    x-data="feedNotifications()"
    class="notification-container"
    style="position: fixed; top: 1rem; right: 1rem; z-index: 100; max-width: 400px;"
>
    <template x-for="notification in notifications" :key="notification.id">
        <div class="notification" :class="getClass(notification.type)" x-transition>
            <button class="delete" @click="dismiss(notification.id)"></button>
            <span x-text="notification.message"></span>
        </div>
    </template>
</div>

<!-- Main Alpine.js container -->
<div id="feed-manager-app" x-data="{ get store() { return $store.feedManager; } }" x-cloak>

    <!-- Loading state -->
    <div x-show="store.isLoading && store.viewMode === 'list'" class="has-text-centered py-6">
        <span class="icon is-large">
            <?php echo IconHelper::render('loader-2', ['class' => 'animate-spin', 'alt' => 'Loading']); ?>
        </span>
        <p class="mt-2">Loading feeds...</p>
    </div>

    <!-- ===================================================================
         FEED LIST VIEW
         =================================================================== -->
    <template x-if="store.viewMode === 'list'">
        <div>
            <!-- Action buttons -->
            <?php
            echo PageLayoutHelper::buildActionCard([
                [
                    'url' => '#', 'label' => 'New Feed', 'icon' => 'circle-plus',
                    'class' => 'is-primary', 'attrs' => '@click.prevent="store.showCreateForm()"'
                ],
                ['url' => '/feeds/wizard', 'label' => 'Feed Wizard', 'icon' => 'wand-2'],
            ]);
            ?>

            <!-- Filter bar -->
            <div x-data="feedFilter()" class="box mb-4">
                <div class="columns is-multiline is-vcentered">
                    <!-- Language filter -->
                    <div class="column is-narrow">
                        <div class="field has-addons">
                            <div class="control">
                                <span class="button is-static is-small">Language</span>
                            </div>
                            <div class="control">
                                <div class="select is-small">
                                    <select :value="filterLang" @change="setLang($event.target.value)">
                                        <option value="">All Languages</option>
                                        <template x-for="lang in languages" :key="lang.id">
                                            <option :value="lang.id" x-text="lang.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sort -->
                    <div class="column is-narrow">
                        <div class="field has-addons">
                            <div class="control">
                                <span class="button is-static is-small">Sort</span>
                            </div>
                            <div class="control">
                                <div class="select is-small">
                                    <select :value="sort" @change="setSort($event.target.value)">
                                        <option value="1">Name A-Z</option>
                                        <option value="2">Last Updated (Newest)</option>
                                        <option value="3">Last Updated (Oldest)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="column">
                        <div class="field has-addons">
                            <div class="control is-expanded">
                                <input class="input is-small" type="text" placeholder="Search feeds..."
                                       x-model="localQuery" @keyup.enter="search()">
                            </div>
                            <div class="control">
                                <button class="button is-small is-info" @click="search()">
                                    <?php echo IconHelper::render('search', ['class' => 'icon-sm']); ?>
                                </button>
                            </div>
                            <div class="control" x-show="localQuery">
                                <button class="button is-small" @click="clearSearch()">
                                    <?php echo IconHelper::render('x', ['class' => 'icon-sm']); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feed list -->
            <div x-data="feedList()" x-show="!store.isLoading">
                <!-- Bulk actions -->
                <div class="level mb-4" x-show="selectedCount > 0">
                    <div class="level-left">
                        <div class="level-item">
                            <span class="tag is-info is-medium" x-text="selectedCount + ' selected'"></span>
                        </div>
                        <div class="level-item">
                            <div class="buttons">
                                <button class="button is-small is-success" @click="loadSelected()">
                                    <?php echo IconHelper::render('refresh-cw', ['class' => 'icon-sm']); ?>
                                    <span>Load Selected</span>
                                </button>
                                <button class="button is-small is-danger" @click="deleteSelected()">
                                    <?php echo IconHelper::render('trash-2', ['class' => 'icon-sm']); ?>
                                    <span>Delete Selected</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-container">
                    <table class="table is-fullwidth is-hoverable">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" :checked="allSelected" @change="toggleAll()">
                                </th>
                                <th>Name</th>
                                <th>Language</th>
                                <th class="has-text-centered">Articles</th>
                                <th>Last Update</th>
                                <th style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="feed in feeds" :key="feed.id">
                                <tr>
                                    <td>
                                        <input type="checkbox" :checked="isSelected(feed.id)"
                                               @change="toggleSelection(feed.id)">
                                    </td>
                                    <td>
                                        <a href="#" @click.prevent="viewArticles(feed)" x-text="feed.name"
                                           class="has-text-weight-semibold"></a>
                                    </td>
                                    <td x-text="feed.langName"></td>
                                    <td class="has-text-centered">
                                        <span class="tag" x-text="feed.articleCount"></span>
                                    </td>
                                    <td>
                                        <span class="is-size-7" x-text="feed.lastUpdate"></span>
                                    </td>
                                    <td>
                                        <div class="buttons are-small">
                                            <button class="button is-info" @click="loadFeed(feed)"
                                                    title="Load/Update feed">
                                                <?php echo IconHelper::render('refresh-cw', ['class' => 'icon-sm']); ?>
                                            </button>
                                            <button class="button" @click="viewArticles(feed)"
                                                    title="View articles">
                                                <?php echo IconHelper::render('list', ['class' => 'icon-sm']); ?>
                                            </button>
                                            <button class="button" @click="editFeed(feed)"
                                                    title="Edit feed">
                                                <?php echo IconHelper::render('pencil', ['class' => 'icon-sm']); ?>
                                            </button>
                                            <button class="button is-danger" @click="deleteFeed(feed)"
                                                    title="Delete feed">
                                                <?php echo IconHelper::render('trash-2', ['class' => 'icon-sm']); ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div x-show="feeds.length === 0 && !store.isLoading" class="has-text-centered py-6">
                    <p class="is-size-5 has-text-grey">No feeds found</p>
                    <p class="is-size-7 has-text-grey">Create a new feed or adjust your filters</p>
                </div>

                <!-- Pagination -->
                <nav x-show="pagination.total_pages > 1" class="pagination is-centered mt-4" role="navigation">
                    <button class="pagination-previous" :disabled="pagination.page <= 1"
                            @click="goToPage(pagination.page - 1)">Previous</button>
                    <button class="pagination-next" :disabled="pagination.page >= pagination.total_pages"
                            @click="goToPage(pagination.page + 1)">Next</button>
                    <ul class="pagination-list">
                        <template x-for="p in pagination.total_pages" :key="p">
                            <li>
                                <button class="pagination-link" :class="{ 'is-current': p === pagination.page }"
                                        @click="goToPage(p)" x-text="p"></button>
                            </li>
                        </template>
                    </ul>
                </nav>
            </div>
        </div>
    </template>

    <!-- ===================================================================
         ARTICLES VIEW
         =================================================================== -->
    <template x-if="store.viewMode === 'articles'">
        <div x-data="articleList()">
            <!-- Header -->
            <div class="level mb-4">
                <div class="level-left">
                    <div class="level-item">
                        <button class="button" @click="backToList()">
                            <?php echo IconHelper::render('arrow-left', ['class' => 'icon-sm']); ?>
                            <span>Back to Feeds</span>
                        </button>
                    </div>
                    <div class="level-item">
                        <h2 class="title is-4" x-text="feed?.name || 'Articles'"></h2>
                    </div>
                </div>
            </div>

            <!-- Filter bar -->
            <div x-data="articleFilter()" class="box mb-4">
                <div class="columns is-vcentered">
                    <!-- Sort -->
                    <div class="column is-narrow">
                        <div class="field has-addons">
                            <div class="control">
                                <span class="button is-static is-small">Sort</span>
                            </div>
                            <div class="control">
                                <div class="select is-small">
                                    <select :value="sort" @change="setSort($event.target.value)">
                                        <option value="1">Date (Newest)</option>
                                        <option value="2">Date (Oldest)</option>
                                        <option value="3">Title A-Z</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="column">
                        <div class="field has-addons">
                            <div class="control is-expanded">
                                <input class="input is-small" type="text" placeholder="Search articles..."
                                       x-model="localQuery" @keyup.enter="search()">
                            </div>
                            <div class="control">
                                <button class="button is-small is-info" @click="search()">
                                    <?php echo IconHelper::render('search', ['class' => 'icon-sm']); ?>
                                </button>
                            </div>
                            <div class="control" x-show="localQuery">
                                <button class="button is-small" @click="clearSearch()">
                                    <?php echo IconHelper::render('x', ['class' => 'icon-sm']); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk actions -->
            <div class="level mb-4">
                <div class="level-left">
                    <div class="level-item" x-show="selectedCount > 0">
                        <span class="tag is-info is-medium" x-text="selectedCount + ' selected'"></span>
                    </div>
                    <div class="level-item">
                        <div class="buttons">
                            <button class="button is-small is-success" @click="importSelected()"
                                    :disabled="selectedCount === 0 || store.isSubmitting">
                                <?php echo IconHelper::render('download', ['class' => 'icon-sm']); ?>
                                <span>Import Selected</span>
                            </button>
                            <button class="button is-small is-danger" @click="deleteSelected()"
                                    :disabled="selectedCount === 0">
                                <?php echo IconHelper::render('trash-2', ['class' => 'icon-sm']); ?>
                                <span>Delete Selected</span>
                            </button>
                            <button class="button is-small is-warning" @click="deleteAll()">
                                <?php echo IconHelper::render('trash', ['class' => 'icon-sm']); ?>
                                <span>Delete All</span>
                            </button>
                            <button class="button is-small" @click="resetErrors()" title="Reset error articles">
                                <?php echo IconHelper::render('refresh-ccw', ['class' => 'icon-sm']); ?>
                                <span>Reset Errors</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div x-show="isLoading" class="has-text-centered py-6">
                <span class="icon is-large">
                    <?php echo IconHelper::render('loader-2', ['class' => 'animate-spin', 'alt' => 'Loading']); ?>
                </span>
                <p class="mt-2">Loading articles...</p>
            </div>

            <!-- Table -->
            <div class="table-container" x-show="!isLoading">
                <table class="table is-fullwidth is-hoverable">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" :checked="allSelected" @change="toggleAll()">
                            </th>
                            <th>Title</th>
                            <th>Date</th>
                            <th class="has-text-centered">Status</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="article in articles" :key="article.id">
                            <tr>
                                <td>
                                    <input type="checkbox" :checked="isSelected(article.id)"
                                           @change="toggleSelection(article.id)">
                                </td>
                                <td>
                                    <a :href="article.link" target="_blank" x-text="article.title"
                                       class="has-text-weight-semibold"></a>
                                    <p
                                        class="is-size-7 has-text-grey"
                                        x-text="article.description.substring(0, 100) + '...'"
                                    ></p>
                                </td>
                                <td>
                                    <span class="is-size-7" x-text="article.date"></span>
                                </td>
                                <td class="has-text-centered">
                                    <span class="tag" :class="getStatusClass(article.status)"
                                          x-text="getStatusText(article.status)"></span>
                                </td>
                                <td>
                                    <div class="buttons are-small">
                                        <a :href="article.link" target="_blank" class="button"
                                           title="Open article">
                                            <?php echo IconHelper::render('external-link', ['class' => 'icon-sm']); ?>
                                        </a>
                                        <template x-if="article.textId">
                                            <a :href="'/text/read/' + article.textId" class="button is-success"
                                               title="Read imported text">
                                                <?php echo IconHelper::render('book-open', ['class' => 'icon-sm']); ?>
                                            </a>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Empty state -->
            <div x-show="articles.length === 0 && !isLoading" class="has-text-centered py-6">
                <p class="is-size-5 has-text-grey">No articles found</p>
                <p class="is-size-7 has-text-grey">Load the feed to fetch articles</p>
            </div>

            <!-- Pagination -->
            <nav x-show="pagination.total_pages > 1" class="pagination is-centered mt-4" role="navigation">
                <button class="pagination-previous" :disabled="pagination.page <= 1"
                        @click="goToPage(pagination.page - 1)">Previous</button>
                <button class="pagination-next" :disabled="pagination.page >= pagination.total_pages"
                        @click="goToPage(pagination.page + 1)">Next</button>
                <ul class="pagination-list">
                    <template x-for="p in pagination.total_pages" :key="p">
                        <li>
                            <button class="pagination-link" :class="{ 'is-current': p === pagination.page }"
                                    @click="goToPage(p)" x-text="p"></button>
                        </li>
                    </template>
                </ul>
            </nav>
        </div>
    </template>

    <!-- ===================================================================
         CREATE/EDIT FORM VIEW
         =================================================================== -->
    <template x-if="store.viewMode === 'create' || store.viewMode === 'edit'">
        <div x-data="feedForm()">
            <!-- Header -->
            <div class="level mb-4">
                <div class="level-left">
                    <div class="level-item">
                        <button class="button" @click="cancel()">
                            <?php echo IconHelper::render('arrow-left', ['class' => 'icon-sm']); ?>
                            <span>Back</span>
                        </button>
                    </div>
                    <div class="level-item">
                        <h2 class="title is-4" x-text="isCreate ? 'Create New Feed' : 'Edit Feed'"></h2>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="box">
                <form @submit.prevent="submit()">
                    <!-- Language -->
                    <div class="field">
                        <label class="label">Language *</label>
                        <div class="control">
                            <div class="select">
                                <select x-model.number="feed.langId" required>
                                    <option value="">Select Language</option>
                                    <template x-for="lang in languages" :key="lang.id">
                                        <option :value="lang.id" x-text="lang.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Name -->
                    <div class="field">
                        <label class="label">Feed Name *</label>
                        <div class="control">
                            <input class="input" type="text" x-model="feed.name" required
                                   placeholder="My News Feed" maxlength="40">
                        </div>
                        <p class="help">Maximum 40 characters</p>
                    </div>

                    <!-- Source URI -->
                    <div class="field">
                        <label class="label">Feed URL *</label>
                        <div class="control">
                            <input class="input" type="url" x-model="feed.sourceUri" required
                                   placeholder="https://example.com/feed.xml">
                        </div>
                        <p class="help">RSS or Atom feed URL</p>
                    </div>

                    <!-- Article Section Tags -->
                    <div class="field">
                        <label class="label">Article Section Tags</label>
                        <div class="control">
                            <input class="input" type="text" x-model="feed.articleSectionTags"
                                   placeholder="//article | //div[@class='content']">
                        </div>
                        <p class="help">XPath selectors for article content (use | to combine)</p>
                    </div>

                    <!-- Filter Tags -->
                    <div class="field">
                        <label class="label">Filter Tags</label>
                        <div class="control">
                            <input class="input" type="text" x-model="feed.filterTags"
                                   placeholder="//nav | //aside | //footer">
                        </div>
                        <p class="help">XPath selectors for elements to remove</p>
                    </div>

                    <!-- Options -->
                    <div class="field">
                        <label class="label">Options</label>
                        <div class="control">
                            <input class="input" type="text" x-model="feed.options"
                                   placeholder="edit_text=1,autoupdate=2h,max_links=50">
                        </div>
                        <p class="help">
                            Comma-separated options (edit_text, autoupdate, max_links, max_texts, charset, tag)
                        </p>
                    </div>

                    <!-- Submit -->
                    <div class="field">
                        <div class="control">
                            <div class="buttons">
                                <button type="submit" class="button is-primary" :disabled="isSubmitting">
                                    <span class="icon" x-show="isSubmitting">
                                        <?php
                                            echo IconHelper::render('loader-2', ['class' => 'animate-spin icon-sm']);
                                        ?>
                                    </span>
                                    <span x-text="isCreate ? 'Create Feed' : 'Update Feed'"></span>
                                </button>
                                <button type="button" class="button" @click="cancel()">Cancel</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tip for advanced setup -->
            <div class="notification is-info is-light mt-4">
                <p>
                    <strong>Tip:</strong> For more advanced feed configuration with live XPath preview,
                    use the <a href="/feeds/wizard">Feed Wizard</a>.
                </p>
            </div>
        </div>
    </template>

</div>

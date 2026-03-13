<?php

/**
 * Languages Index View - Full-page current language layout.
 *
 * Variables expected:
 * - $languages: array of language data with stats
 * - $currentLanguageId: int current language ID
 * - $message: string optional message to display
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Language\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Language\Views;

use Lwt\Shared\Infrastructure\Http\UrlUtilities;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\IconHelper;

$base = UrlUtilities::getBasePath();
?>
<!-- Alpine.js Language List Component -->
<div x-data="languageList" x-init="init()" data-base-path="<?php echo $base; ?>">

    <!-- Notification area -->
    <div
        x-show="notification"
        x-transition
        class="notification"
        :class="{
            'is-success': notificationType === 'success',
            'is-danger': notificationType === 'error',
            'is-info': notificationType === 'info'
        }"
    >
        <button class="delete" @click="clearNotification()"></button>
        <span x-text="notification"></span>
    </div>

    <!-- Loading state -->
    <div x-show="store.isLoading" class="has-text-centered py-6">
        <span class="icon is-large">
            <i data-lucide="loader-2" class="animate-spin"></i>
        </span>
        <p>Loading languages...</p>
    </div>

    <!-- Error state -->
    <div x-show="store.error && !store.isLoading" class="notification is-danger">
        <button class="delete" @click="store.error = null"></button>
        <span x-text="store.error"></span>
    </div>

    <!-- Empty state -->
    <div x-show="!store.isLoading && !store.error && store.languages.length === 0" class="has-text-centered py-6">
        <p class="mb-4">No languages found. Create your first language to get started.</p>
        <a href="<?php echo $base; ?>/languages/new" class="button is-primary">
            <span class="icon"><?php
                echo IconHelper::render('circle-plus', ['alt' => 'New Language']);
            ?></span>
            <span>New Language</span>
        </a>
    </div>

    <!-- Main content (when languages exist) -->
    <div x-show="!store.isLoading && store.languages.length > 0">

        <!-- Current Language Section -->
        <template x-if="store.currentLanguage">
            <div class="box mb-5">
                <div class="level mb-4">
                    <div class="level-left">
                        <div class="level-item">
                            <h2 class="title is-4 mb-0">
                                <span class="icon mr-2 has-text-primary">
                                    <i data-lucide="languages" style="width: 24px; height: 24px;"></i>
                                </span>
                                <span x-text="store.currentLanguage.name"></span>
                            </h2>
                        </div>
                        <div class="level-item">
                            <template x-if="store.currentLanguage.hasExportTemplate">
                                <span
                                    class="tag is-info is-light"
                                    title="Custom export template available"
                                >
                                    <span class="icon">
                                        <i data-lucide="file-down" style="width: 12px; height: 12px;"></i>
                                    </span>
                                    <span>Export Template</span>
                                </span>
                            </template>
                        </div>
                    </div>
                    <div class="level-right">
                        <div class="level-item">
                            <div class="buttons">
                                <a :href="'/review?lang=' + store.currentLanguage.id"
                                   class="button is-primary is-outlined">
                                    <span class="icon">
                                        <i data-lucide="circle-help" style="width: 16px; height: 16px;"></i>
                                    </span>
                                    <span>Review</span>
                                </a>
                                <template x-if="store.currentLanguage.textCount > 0">
                                    <button
                                        type="button"
                                        class="button is-warning is-outlined"
                                        :class="{'is-loading': store.refreshingId === store.currentLanguage.id}"
                                        @click="handleRefresh(store.currentLanguage.id)"
                                    >
                                        <span class="icon">
                                            <i data-lucide="zap" style="width: 16px; height: 16px;"></i>
                                        </span>
                                        <span>Reparse</span>
                                    </button>
                                </template>
                                <a :href="'/languages/' + store.currentLanguage.id + '/edit'"
                                   class="button is-info is-outlined">
                                    <span class="icon">
                                        <i data-lucide="file-pen" style="width: 16px; height: 16px;"></i>
                                    </span>
                                    <span>Edit</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats grid -->
                <nav class="level">
                    <div class="level-item has-text-centered">
                        <a :href="'/texts?page=1&query=&filterlang=' + store.currentLanguage.id"
                           class="has-text-centered">
                            <p class="heading">Texts</p>
                            <p class="title is-5" x-text="store.currentLanguage.textCount"></p>
                        </a>
                    </div>
                    <div class="level-item has-text-centered">
                        <a :href="'/text/archived?page=1&query=&filterlang=' + store.currentLanguage.id"
                           class="has-text-centered">
                            <p class="heading">Archived</p>
                            <p class="title is-5" x-text="store.currentLanguage.archivedTextCount"></p>
                        </a>
                    </div>
                    <div class="level-item has-text-centered">
                        <a :href="'/words?lang=' + store.currentLanguage.id"
                           class="has-text-centered">
                            <p class="heading">Terms</p>
                            <p class="title is-5" x-text="store.currentLanguage.wordCount"></p>
                        </a>
                    </div>
                    <div class="level-item has-text-centered">
                        <a :href="'/feeds?query=&selected_feed=&check_autoupdate=1&filterlang=' + store.currentLanguage.id"
                           class="has-text-centered">
                            <p class="heading">Feeds</p>
                            <p class="title is-5">
                                <span x-text="store.currentLanguage.feedCount"></span>
                                (<span x-text="store.currentLanguage.articleCount"></span>)
                            </p>
                        </a>
                    </div>
                </nav>
            </div>
        </template>

        <!-- All Languages Table -->
        <div class="box">
            <div class="level mb-4">
                <div class="level-left">
                    <div class="level-item">
                        <h3 class="title is-5 mb-0">All Languages</h3>
                    </div>
                </div>
                <div class="level-right">
                    <div class="level-item">
                        <a href="<?php echo $base; ?>/languages/new" class="button is-primary">
                            <span class="icon"><?php
                                echo IconHelper::render('circle-plus', ['alt' => 'New Language']);
                            ?></span>
                            <span>New Language</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table class="table is-fullwidth is-hoverable">
                    <thead>
                        <tr>
                            <th>Language</th>
                            <th class="has-text-centered">Texts</th>
                            <th class="has-text-centered">Archived</th>
                            <th class="has-text-centered">Terms</th>
                            <th class="has-text-centered">Feeds</th>
                            <th class="has-text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="lang in store.languages" :key="lang.id">
                            <tr :style="lang.id === store.currentLanguageId
                                ? 'border-left: 3px solid hsl(171, 100%, 41%)'
                                : ''">
                                <td>
                                    <strong x-text="lang.name"></strong>
                                    <template x-if="lang.id === store.currentLanguageId">
                                        <span class="tag is-primary is-light ml-2">Current</span>
                                    </template>
                                    <template x-if="lang.hasExportTemplate">
                                        <span class="tag is-info is-light ml-1" title="Export template">
                                            <span class="icon is-small">
                                                <i data-lucide="file-down" style="width: 10px; height: 10px;"></i>
                                            </span>
                                        </span>
                                    </template>
                                </td>
                                <td class="has-text-centered">
                                    <a :href="'/texts?page=1&query=&filterlang=' + lang.id"
                                       x-text="lang.textCount"></a>
                                </td>
                                <td class="has-text-centered">
                                    <a :href="'/text/archived?page=1&query=&filterlang=' + lang.id"
                                       x-text="lang.archivedTextCount"></a>
                                </td>
                                <td class="has-text-centered">
                                    <a :href="'/words?lang=' + lang.id"
                                       x-text="lang.wordCount"></a>
                                </td>
                                <td class="has-text-centered">
                                    <a :href="'/feeds?query=&selected_feed=&check_autoupdate=1&filterlang=' + lang.id">
                                        <span x-text="lang.feedCount"></span>
                                        (<span x-text="lang.articleCount"></span>)
                                    </a>
                                </td>
                                <td class="has-text-right">
                                    <div class="buttons is-right are-small">
                                        <template x-if="lang.id !== store.currentLanguageId">
                                            <button
                                                type="button"
                                                class="button is-small is-primary is-outlined"
                                                @click="handleSetDefault(lang.id)"
                                                title="Set as Current"
                                            >
                                                <span class="icon">
                                                    <i data-lucide="circle-check" style="width: 14px; height: 14px;"></i>
                                                </span>
                                            </button>
                                        </template>
                                        <a :href="'/languages/' + lang.id + '/edit'"
                                           class="button is-small is-info is-outlined"
                                           title="Edit">
                                            <span class="icon">
                                                <i data-lucide="file-pen" style="width: 14px; height: 14px;"></i>
                                            </span>
                                        </a>
                                        <template x-if="lang.textCount > 0">
                                            <button
                                                type="button"
                                                class="button is-small is-warning is-outlined"
                                                :class="{'is-loading': store.refreshingId === lang.id}"
                                                @click="handleRefresh(lang.id)"
                                                title="Reparse Texts"
                                            >
                                                <span class="icon">
                                                    <i data-lucide="zap" style="width: 14px; height: 14px;"></i>
                                                </span>
                                            </button>
                                        </template>
                                        <template x-if="canDelete(lang)">
                                            <button
                                                type="button"
                                                class="button is-small is-danger is-outlined"
                                                @click="store.showDeleteConfirm(lang.id)"
                                                title="Delete"
                                            >
                                                <span class="icon">
                                                    <i data-lucide="circle-minus" style="width: 14px; height: 14px;"></i>
                                                </span>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <div class="modal" :class="{'is-active': store.deleteConfirmId !== null}">
        <div class="modal-background" @click="store.hideDeleteConfirm()"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">Confirm Delete</p>
                <button class="delete" aria-label="close" @click="store.hideDeleteConfirm()"></button>
            </header>
            <section class="modal-card-body">
                <template x-if="store.deleteConfirmId !== null">
                    <p>
                        Are you sure you want to delete the language
                        "<strong x-text="getLanguage(store.deleteConfirmId)?.name"></strong>"?
                    </p>
                </template>
                <p class="has-text-danger mt-2">This action cannot be undone.</p>
            </section>
            <footer class="modal-card-foot">
                <button class="button is-danger" @click="handleDelete(store.deleteConfirmId)">Delete</button>
                <button class="button" @click="store.hideDeleteConfirm()">Cancel</button>
            </footer>
        </div>
    </div>
</div>

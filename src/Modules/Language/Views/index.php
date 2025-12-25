<?php declare(strict_types=1);
/**
 * Languages Index View - Alpine.js SPA Version
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
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Language\Views;

use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\IconHelper;

?>
<!-- Alpine.js Language List Component -->
<div x-data="languageList" x-init="init()">
    <!-- Action card - inside Alpine scope for store access -->
    <div class="card action-card mb-4">
        <div class="card-content">
            <div class="buttons is-centered">
                <a href="/languages?new=1" class="button is-light is-primary">
                    <span class="icon"><?php echo IconHelper::render('circle-plus', ['alt' => 'New Language']); ?></span>
                    <span>New Language</span>
                </a>
                <a href="#" class="button is-light is-info" @click.prevent="store.openWizardModal()">
                    <span class="icon"><?php echo IconHelper::render('wand-2', ['alt' => 'Quick Setup Wizard']); ?></span>
                    <span>Quick Setup Wizard</span>
                </a>
            </div>
        </div>
    </div>

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
    <p x-show="!store.isLoading && !store.error && store.languages.length === 0">
        No languages found.
    </p>

    <!-- Language cards -->
    <div x-show="!store.isLoading && store.languages.length > 0" class="columns is-multiline language-cards">
        <template x-for="lang in store.languages" :key="lang.id">
            <div class="column is-4-desktop is-6-tablet is-12-mobile">
                <div
                    class="card language-card"
                    :class="{'is-current': lang.id === store.currentLanguageId}"
                    :data-lang-id="lang.id"
                >
                    <header class="card-header">
                        <p class="card-header-title">
                            <template x-if="lang.id === store.currentLanguageId">
                                <span class="icon mr-1" title="Current Language">
                                    <i data-lucide="circle-alert" style="width: 18px; height: 18px;"></i>
                                </span>
                            </template>
                            <span x-text="lang.name"></span>
                        </p>
                        <div class="card-header-icon">
                            <template x-if="lang.id !== store.currentLanguageId">
                                <button
                                    type="button"
                                    class="button is-small is-primary is-outlined"
                                    @click="handleSetDefault(lang.id)"
                                    title="Set as Current Language"
                                >
                                    <span class="icon">
                                        <i data-lucide="circle-check" style="width: 14px; height: 14px;"></i>
                                    </span>
                                    <span>Set as Default</span>
                                </button>
                            </template>
                        </div>
                    </header>

                    <div class="card-content">
                        <div class="language-stats">
                            <div class="stat-item">
                                <span class="stat-label">Texts</span>
                                <span class="stat-value">
                                    <template x-if="lang.textCount > 0">
                                        <a :href="'/texts?page=1&query=&filterlang=' + lang.id" x-text="lang.textCount"></a>
                                    </template>
                                    <template x-if="lang.textCount === 0">
                                        <span>0</span>
                                    </template>
                                </span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Archived</span>
                                <span class="stat-value">
                                    <template x-if="lang.archivedTextCount > 0">
                                        <a :href="'/text/archived?page=1&query=&filterlang=' + lang.id" x-text="lang.archivedTextCount"></a>
                                    </template>
                                    <template x-if="lang.archivedTextCount === 0">
                                        <span>0</span>
                                    </template>
                                </span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Terms</span>
                                <span class="stat-value">
                                    <template x-if="lang.wordCount > 0">
                                        <a :href="'/words?page=1&query=&text=&status=&filterlang=' + lang.id + '&status=&tag12=0&tag2=&tag1='" x-text="lang.wordCount"></a>
                                    </template>
                                    <template x-if="lang.wordCount === 0">
                                        <span>0</span>
                                    </template>
                                </span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Feeds</span>
                                <span class="stat-value">
                                    <template x-if="lang.feedCount > 0">
                                        <a :href="'/feeds?query=&selected_feed=&check_autoupdate=1&filterlang=' + lang.id">
                                            <span x-text="lang.feedCount"></span>
                                            (<span x-text="lang.articleCount"></span>)
                                        </a>
                                    </template>
                                    <template x-if="lang.feedCount === 0">
                                        <span>0</span>
                                    </template>
                                </span>
                            </div>
                        </div>

                        <template x-if="lang.hasExportTemplate">
                            <div class="tags mt-3">
                                <span class="tag is-info is-light export-template-tag" title="This language has a custom export template for flexible term exports">
                                    <span class="icon">
                                        <i data-lucide="file-down" style="width: 12px; height: 12px;"></i>
                                    </span>
                                    <span>Export Template</span>
                                </span>
                            </div>
                        </template>
                    </div>

                    <footer class="card-footer">
                        <a :href="'/test?lang=' + lang.id" class="card-footer-item">
                            <span class="icon">
                                <i data-lucide="circle-help" style="width: 16px; height: 16px;"></i>
                            </span>
                            <span>Test</span>
                        </a>
                        <template x-if="lang.textCount > 0">
                            <a
                                href="#"
                                class="card-footer-item"
                                :class="{'is-loading': store.refreshingId === lang.id}"
                                @click.prevent="handleRefresh(lang.id)"
                            >
                                <span class="icon">
                                    <i data-lucide="zap" style="width: 16px; height: 16px;"></i>
                                </span>
                                <span>Reparse</span>
                            </a>
                        </template>
                        <a :href="'/languages?chg=' + lang.id" class="card-footer-item">
                            <span class="icon">
                                <i data-lucide="file-pen" style="width: 16px; height: 16px;"></i>
                            </span>
                            <span>Edit</span>
                        </a>
                        <template x-if="canDelete(lang)">
                            <span class="card-footer-item click" @click="store.showDeleteConfirm(lang.id)">
                                <span class="icon">
                                    <i data-lucide="circle-minus" style="width: 16px; height: 16px;"></i>
                                </span>
                                <span>Delete</span>
                            </span>
                        </template>
                    </footer>
                </div>
            </div>
        </template>
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

<!-- Language Wizard Modal -->
<div x-data="wizardModal" x-init="init()">
    <div class="modal" :class="{'is-active': store.wizardModalOpen}">
        <div class="modal-background" @click="close()"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">
                    <span class="icon mr-2">
                        <i data-lucide="wand-2" style="width: 20px; height: 20px;"></i>
                    </span>
                    Quick Language Setup
                </p>
                <button class="delete" aria-label="close" @click="close()"></button>
            </header>
            <section class="modal-card-body">
                <p class="mb-4">
                    Choose your native language and the language you want to study.
                    We'll set up dictionary links and parsing rules automatically.
                </p>

                <!-- Error display -->
                <template x-if="error">
                    <div class="notification is-danger is-light mb-4">
                        <span x-text="error"></span>
                    </div>
                </template>

                <div class="field">
                    <label class="label">Your Native Language (L1)</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select x-model="l1" @change="handleL1Change()">
                                <option value="">-- Select your native language --</option>
                                <template x-for="lang in sortedLanguages" :key="lang">
                                    <option :value="lang" x-text="lang"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <p class="help">Used for translations and dictionary lookups</p>
                </div>

                <div class="field">
                    <label class="label">Language to Study (L2)</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select x-model="l2">
                                <option value="">-- Select the language to study --</option>
                                <template x-for="lang in sortedLanguages" :key="lang">
                                    <option :value="lang" x-text="lang" :disabled="lang === l1"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <p class="help">The language you want to read and learn</p>
                </div>
            </section>
            <footer class="modal-card-foot">
                <button
                    class="button is-primary"
                    @click="apply()"
                    :disabled="!isValid"
                >
                    <span class="icon">
                        <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                    </span>
                    <span>Create Language</span>
                </button>
                <button class="button" @click="close()">Cancel</button>
            </footer>
        </div>
    </div>
</div>

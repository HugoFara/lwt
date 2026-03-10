<?php

/**
 * Starter Vocabulary Import View
 *
 * Offers to import common words from the FrequencyWords project
 * after language creation, with optional enrichment from Wiktionary.
 *
 * Expected variables:
 * - $langName: string - Language display name
 * - $langId: int - Language ID
 * - $isAvailable: bool - Whether frequency data exists for this language
 * - $skipUrl: string - URL to skip and go to text creation
 * - $importUrl: string - AJAX endpoint for importing words
 * - $enrichUrl: string - AJAX endpoint for enrichment
 * - $csrfToken: string - CSRF token for POST requests (field: _csrf_token)
 *
 * PHP version 8.1
 */

declare(strict_types=1);

/** @var string $langName */
/** @var int $langId */
/** @var bool $isAvailable */
/** @var string $skipUrl */
/** @var string $importUrl */
/** @var string $enrichUrl */
/** @var string $csrfToken */

?>
<div x-data="starterVocab()" class="container" style="max-width: 640px;">
    <h2 class="title is-4 mb-4">Starter Vocabulary</h2>

    <?php if (!$isAvailable) : ?>
    <div class="notification is-warning">
        Starter vocabulary is not available for
        <strong><?= htmlspecialchars($langName, ENT_QUOTES, 'UTF-8') ?></strong>.
        You can import terms manually later.
    </div>
    <a class="button is-primary" href="<?= htmlspecialchars($skipUrl, ENT_QUOTES, 'UTF-8') ?>">
        Continue to Text Import
    </a>
    <?php else : ?>
    <!-- Step 1: Choose options -->
    <template x-if="step === 'choose'">
        <div class="box">
            <p class="mb-4">
                Get a head start with the most common words in
                <strong><?= htmlspecialchars($langName, ENT_QUOTES, 'UTF-8') ?></strong>.
            </p>

            <div class="field">
                <label class="label">How many words?</label>
                <div class="buttons has-addons">
                    <button class="button" :class="{'is-primary is-selected': size === 500}"
                            @click="size = 500">500</button>
                    <button class="button" :class="{'is-primary is-selected': size === 1000}"
                            @click="size = 1000">1,000</button>
                    <button class="button" :class="{'is-primary is-selected': size === 2000}"
                            @click="size = 2000">2,000</button>
                    <button class="button" :class="{'is-primary is-selected': size === 5000}"
                            @click="size = 5000">5,000</button>
                </div>
            </div>

            <div class="field">
                <label class="label">Enrichment mode</label>
                <div class="control">
                    <label class="radio">
                        <input type="radio" x-model="mode" value="translation">
                        Translation <span class="has-text-grey is-size-7">(English glosses — for beginners)</span>
                    </label>
                </div>
                <div class="control mt-1">
                    <label class="radio">
                        <input type="radio" x-model="mode" value="definition">
                        Definition <span class="has-text-grey is-size-7">(monolingual — for advanced learners)</span>
                    </label>
                </div>
                <div class="control mt-1">
                    <label class="radio">
                        <input type="radio" x-model="mode" value="none">
                        Words only <span class="has-text-grey is-size-7">(no translations, fastest)</span>
                    </label>
                </div>
            </div>

            <div class="field is-grouped mt-5">
                <div class="control">
                    <button class="button is-success" @click="startImport()">
                        Import Words
                    </button>
                </div>
                <div class="control">
                    <a class="button"
                       href="<?= htmlspecialchars($skipUrl, ENT_QUOTES, 'UTF-8') ?>">
                        Skip
                    </a>
                </div>
            </div>
        </div>
    </template>

    <!-- Step 2: Importing frequency words -->
    <template x-if="step === 'importing'">
        <div class="box">
            <p class="mb-3">
                <strong>Fetching and importing frequency words...</strong>
            </p>
            <progress class="progress is-info" max="100"></progress>
            <p class="has-text-grey is-size-7">
                This may take a few seconds depending on your connection.
            </p>
        </div>
    </template>

    <!-- Step 3: Enriching with translations/definitions -->
    <template x-if="step === 'enriching'">
        <div class="box">
            <p class="mb-3">
                <strong x-text="mode === 'translation' ? 'Fetching translations...' : 'Fetching definitions...'"></strong>
            </p>
            <progress class="progress is-success" :value="enrichProgress" max="100"></progress>
            <p class="is-size-7 mb-3">
                <span x-text="enrichStats.done"></span> of <span x-text="enrichStats.total"></span> words enriched
                <template x-if="enrichStats.failed > 0">
                    <span class="has-text-grey">(<span x-text="enrichStats.failed"></span> not found)</span>
                </template>
            </p>

            <!-- Warning message -->
            <template x-if="enrichWarning">
                <div class="notification is-warning is-light is-size-7 p-3 mb-3" x-text="enrichWarning"></div>
            </template>

            <div class="field is-grouped">
                <div class="control">
                    <button class="button is-warning is-small" @click="stopEnrichment()">
                        Stop &amp; Continue
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Step 4: Done -->
    <template x-if="step === 'done'">
        <div class="box">
            <div class="notification is-success is-light">
                <p>
                    Imported <strong x-text="result.imported"></strong> words
                    <template x-if="result.skipped > 0">
                        <span>(<span x-text="result.skipped"></span> already existed)</span>
                    </template>
                    for <strong><?= htmlspecialchars($langName, ENT_QUOTES, 'UTF-8') ?></strong>.
                </p>
                <template x-if="enrichStats.done > 0">
                    <p class="mt-1">
                        <span x-text="enrichStats.done"></span> words enriched with
                        <span x-text="mode === 'translation' ? 'translations' : 'definitions'"></span>.
                    </p>
                </template>
            </div>

            <div class="field is-grouped">
                <div class="control">
                    <a class="button is-primary"
                       href="<?= htmlspecialchars($skipUrl, ENT_QUOTES, 'UTF-8') ?>">
                        Continue to Text Import
                    </a>
                </div>
                <div class="control">
                    <a class="button" href="<?= htmlspecialchars(url('/words') . '?filterlang=' . $langId, ENT_QUOTES, 'UTF-8') ?>">
                        View Vocabulary
                    </a>
                </div>
            </div>
        </div>
    </template>

    <!-- Error state -->
    <template x-if="step === 'error'">
        <div class="box">
            <div class="notification is-danger is-light">
                <strong>Import failed:</strong> <span x-text="errorMessage"></span>
            </div>
            <div class="field is-grouped">
                <div class="control">
                    <button class="button" @click="step = 'choose'">Try Again</button>
                </div>
                <div class="control">
                    <a class="button"
                       href="<?= htmlspecialchars($skipUrl, ENT_QUOTES, 'UTF-8') ?>">
                        Skip
                    </a>
                </div>
            </div>
        </div>
    </template>

    <?php endif; ?>
</div>

<script>
function starterVocab() {
    return {
        step: 'choose',
        size: 1000,
        mode: 'translation',
        result: { imported: 0, skipped: 0, total: 0 },
        enrichStats: { done: 0, failed: 0, total: 0 },
        enrichWarning: '',
        enrichProgress: 0,
        errorMessage: '',
        _stopEnrichment: false,

        async startImport() {
            this.step = 'importing';

            try {
                const formData = new FormData();
                formData.append('count', String(this.size));
                formData.append('_csrf_token', <?= json_encode($csrfToken) ?>);

                const response = await fetch(<?= json_encode($importUrl) ?>, {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                if (!response.ok) {
                    this.errorMessage = data.error || 'Unknown error occurred.';
                    this.step = 'error';
                    return;
                }

                this.result = data;

                // If mode is 'none' or no words imported, go straight to done
                if (this.mode === 'none' || data.imported === 0) {
                    this.step = 'done';
                    return;
                }

                // Start enrichment
                this.enrichStats = { done: 0, failed: 0, total: data.imported };
                this._stopEnrichment = false;
                this.step = 'enriching';
                this.enrichNext();
            } catch (err) {
                this.errorMessage = 'Network error. Please check your connection.';
                this.step = 'error';
            }
        },

        async enrichNext() {
            if (this._stopEnrichment) {
                this.step = 'done';
                return;
            }

            try {
                const formData = new FormData();
                formData.append('mode', this.mode);
                formData.append('_csrf_token', <?= json_encode($csrfToken) ?>);

                const response = await fetch(<?= json_encode($enrichUrl) ?>, {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                if (!response.ok) {
                    // Enrichment error — still show what we have
                    this.enrichWarning = data.error || 'Enrichment encountered an error.';
                    this.step = 'done';
                    return;
                }

                // Update stats
                this.enrichStats.done = data.total - data.remaining;
                this.enrichStats.total = data.total;
                this.enrichStats.failed += data.failed;
                this.enrichProgress = data.total > 0
                    ? Math.round(((data.total - data.remaining) / data.total) * 100)
                    : 100;

                if (data.warning) {
                    this.enrichWarning = data.warning;
                }

                // Continue or finish
                if (data.remaining > 0 && !this._stopEnrichment) {
                    // Small delay to avoid hammering the server
                    setTimeout(() => this.enrichNext(), 100);
                } else {
                    this.step = 'done';
                }
            } catch (err) {
                this.enrichWarning = 'Network error during enrichment.';
                this.step = 'done';
            }
        },

        stopEnrichment() {
            this._stopEnrichment = true;
            this.step = 'done';
        }
    };
}
</script>

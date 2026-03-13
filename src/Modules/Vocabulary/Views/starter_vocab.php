<?php

/**
 * Starter Vocabulary Import View
 *
 * Offers to import common words from the FrequencyWords project
 * after language creation, with optional enrichment from Wiktionary.
 * Also supports one-click curated dictionary import.
 *
 * Expected variables:
 * - $langName: string - Language display name
 * - $langId: int - Language ID
 * - $isAvailable: bool - Whether frequency data exists for this language
 * - $skipUrl: string - URL to skip and go to text creation
 * - $importUrl: string - AJAX endpoint for importing words
 * - $enrichUrl: string - AJAX endpoint for enrichment
 * - $csrfToken: string - CSRF token for POST requests (field: _csrf_token)
 * - $curatedDictionaries: list<array<string, mixed>> - Curated dictionaries for this language
 *
 * PHP version 8.1
 */

declare(strict_types=1);

use Lwt\Shared\UI\Helpers\IconHelper;

/** @var string $langName */
/** @var int $langId */
/** @var bool $isAvailable */
/** @var string $skipUrl */
/** @var string $importUrl */
/** @var string $enrichUrl */
/** @var string $csrfToken */
/** @var list<array<string, mixed>> $curatedDictionaries */

$escapedLangName = htmlspecialchars($langName, ENT_QUOTES, 'UTF-8');
$escapedSkipUrl = htmlspecialchars($skipUrl, ENT_QUOTES, 'UTF-8');
$escapedVocabUrl = htmlspecialchars(url('/words') . '?filterlang=' . (string) $langId, ENT_QUOTES, 'UTF-8');

$downloadIcon = IconHelper::render('download', ['alt' => 'Import', 'size' => 14]);
$externalLinkIcon = IconHelper::render('external-link', ['alt' => 'Download', 'size' => 14]);

?>
<script type="application/json" id="starter-vocab-config">
<?php echo json_encode([
    'importUrl' => $importUrl,
    'enrichUrl' => $enrichUrl,
    'csrfToken' => $csrfToken,
    'langId' => $langId,
    'curatedDictionaries' => $curatedDictionaries,
    'isAvailable' => $isAvailable,
], JSON_HEX_TAG | JSON_HEX_AMP); ?>
</script>

<div x-data="starterVocab" class="container" style="max-width: 640px;">
    <h2 class="title is-4 mb-4">Starter Vocabulary</h2>

    <?php if (!$isAvailable && empty($curatedDictionaries)) : ?>
    <div class="notification is-warning">
        Starter vocabulary is not available for
        <strong><?= $escapedLangName ?></strong>.
        You can import terms manually later.
    </div>
    <a class="button is-primary" href="<?= $escapedSkipUrl ?>">
        Continue to Text Import
    </a>
    <?php else : ?>

    <!-- Step 1: Choose sources and options -->
    <template x-if="step === 'choose'">
        <div class="box">
            <p class="mb-4">
                Build starter vocabulary for
                <strong><?= $escapedLangName ?></strong>.
                Pick your sources and enrichment mode, then import.
            </p>

            <div class="field">
                <label class="label">Enrichment mode</label>
                <div class="control">
                    <label class="radio">
                        <input type="radio" x-model="mode" value="translation">
                        Translation <span class="has-text-grey is-size-7">(English glosses &mdash; for beginners)</span>
                    </label>
                </div>
                <div class="control mt-1">
                    <label class="radio">
                        <input type="radio" x-model="mode" value="definition">
                        Definition <span class="has-text-grey is-size-7">(monolingual &mdash; for advanced learners)</span>
                    </label>
                </div>
            </div>

            <hr>
            <label class="label">Sources</label>

            <?php if ($isAvailable) : ?>
            <!-- Wiktionary frequency words source -->
            <label class="box mb-3 p-4" style="cursor: pointer;"
                   :class="useWiktionary ? 'has-background-success-light' : ''">
                <div class="is-flex is-align-items-center">
                    <input type="checkbox" class="mr-3"
                           :checked="useWiktionary"
                           @change="toggleWiktionary()">
                    <div class="is-flex-grow-1">
                        <p class="has-text-weight-semibold mb-1">Most common words (Wiktionary)</p>
                        <p class="is-size-7 has-text-grey mb-2">
                            Frequency-ranked words from the
                            <a href="https://github.com/hermitdave/FrequencyWords" target="_blank" rel="noopener">FrequencyWords</a>
                            project, enriched via
                            <a href="https://kaikki.org" target="_blank" rel="noopener">Wiktionary</a>.
                        </p>
                        <template x-if="useWiktionary">
                            <div class="field">
                                <label class="label is-small">How many words?</label>
                                <div class="buttons has-addons are-small">
                                    <button :class="sizeClass(50)"
                                            @click="setSize(50)">50</button>
                                    <button :class="sizeClass(100)"
                                            @click="setSize(100)">100</button>
                                    <button :class="sizeClass(500)"
                                            @click="setSize(500)">500</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </label>
            <?php endif; ?>

            <!-- Curated dictionaries -->
            <template x-for="source in dictSources" :key="source.name">
                <label class="box mb-3 p-4" style="cursor: pointer;"
                       :class="isSourceSelected(source.url) ? 'has-background-success-light' : ''">
                    <div class="is-flex is-align-items-center">
                        <input type="checkbox" class="mr-3"
                               :checked="isSourceSelected(source.url)"
                               :disabled="!source.directDownload"
                               @change="toggleSource(source.url)">
                        <div class="is-flex-grow-1">
                            <p class="has-text-weight-semibold mb-1" x-text="source.name"></p>
                            <div class="tags mb-1">
                                <span class="tag is-light" x-text="source.entries"></span>
                                <span class="tag is-info is-light" x-text="source.format"></span>
                                <span class="tag is-success is-light" x-text="source.license"></span>
                                <span class="tag is-primary is-light"
                                      x-text="dictTypeLabel(source)"></span>
                            </div>
                            <p class="is-size-7 has-text-grey" x-text="source.notes"></p>
                            <p class="is-size-7 has-text-warning-dark" x-show="!source.directDownload">
                                Manual download required &mdash;
                                <a :href="source.url" target="_blank" rel="noopener">
                                    visit site <?= $externalLinkIcon ?>
                                </a>
                            </p>
                        </div>
                    </div>
                </label>
            </template>

            <div class="field is-grouped mt-5">
                <div class="control">
                    <button class="button is-success"
                            :disabled="!canImport()"
                            @click="startImport()">
                        <?= $downloadIcon ?>
                        <span class="ml-1">Import</span>
                    </button>
                </div>
                <div class="control">
                    <a class="button" href="<?= $escapedSkipUrl ?>">
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
                <strong x-text="enrichingLabel()"></strong>
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

    <!-- Step 4: Importing curated dictionaries -->
    <template x-if="step === 'dictImporting'">
        <div class="box">
            <p class="mb-3">
                <strong>Importing dictionaries...</strong>
            </p>
            <progress class="progress is-info"
                      :value="dictBatchCurrent" :max="dictBatchTotal"></progress>
            <p class="is-size-7 has-text-grey">
                Dictionary <span x-text="dictBatchCurrent"></span>
                of <span x-text="dictBatchTotal"></span>
            </p>
        </div>
    </template>

    <!-- Step 5: Done -->
    <template x-if="step === 'done'">
        <div class="box">
            <div class="notification is-success is-light">
                <template x-if="wiktResult.imported > 0 || wiktResult.skipped > 0">
                    <p>
                        Imported <strong x-text="wiktResult.imported"></strong> words
                        <template x-if="wiktResult.skipped > 0">
                            <span>(<span x-text="wiktResult.skipped"></span> already existed)</span>
                        </template>
                        for <strong><?= $escapedLangName ?></strong>.
                    </p>
                </template>
                <template x-if="enrichStats.done > 0">
                    <p class="mt-1">
                        <span x-text="enrichStats.done"></span> words enriched with
                        <span x-text="enrichedModeLabel()"></span>.
                    </p>
                </template>
                <template x-for="(msg, i) in dictMessages" :key="i">
                    <p class="mt-1" :class="msg.success ? '' : 'has-text-danger'" x-text="msg.text"></p>
                </template>
            </div>

            <div class="field is-grouped">
                <div class="control">
                    <a class="button is-primary" href="<?= $escapedSkipUrl ?>">
                        Continue to Text Import
                    </a>
                </div>
                <div class="control">
                    <a class="button" href="<?= $escapedVocabUrl ?>">
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
                    <button class="button" @click="retryImport()">Try Again</button>
                </div>
                <div class="control">
                    <a class="button" href="<?= $escapedSkipUrl ?>">
                        Skip
                    </a>
                </div>
            </div>
        </div>
    </template>

    <?php endif; ?>
</div>

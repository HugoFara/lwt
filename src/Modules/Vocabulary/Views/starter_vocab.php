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
$escapedVocabUrl = htmlspecialchars(url('/words') . '?filterlang=' . (int) $langId, ENT_QUOTES, 'UTF-8');

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

    <?php if ($isAvailable) : ?>
    <!-- Step 1: Choose options -->
    <template x-if="step === 'choose'">
        <div class="box">
            <p class="mb-4">
                Get a head start with the most common words in
                <strong><?= $escapedLangName ?></strong>.
            </p>

            <div class="field">
                <label class="label">How many words?</label>
                <div class="buttons has-addons">
                    <button :class="sizeClass(500)"
                            @click="setSize(500)">500</button>
                    <button :class="sizeClass(1000)"
                            @click="setSize(1000)">1,000</button>
                    <button :class="sizeClass(2000)"
                            @click="setSize(2000)">2,000</button>
                    <button :class="sizeClass(5000)"
                            @click="setSize(5000)">5,000</button>
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

    <!-- Step 4: Done -->
    <template x-if="step === 'done'">
        <div class="box">
            <div class="notification is-success is-light">
                <p>
                    Imported <strong x-text="result.imported"></strong> words
                    <template x-if="result.skipped > 0">
                        <span>(<span x-text="result.skipped"></span> already existed)</span>
                    </template>
                    for <strong><?= $escapedLangName ?></strong>.
                </p>
                <template x-if="enrichStats.done > 0">
                    <p class="mt-1">
                        <span x-text="enrichStats.done"></span> words enriched with
                        <span x-text="enrichedModeLabel()"></span>.
                    </p>
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

    <?php if (!empty($curatedDictionaries)) : ?>
    <!-- Curated Dictionary Import -->
    <template x-if="step === 'choose' || step === 'done'">
        <div class="box mt-4">
            <h3 class="title is-5 mb-3">Dictionary Import</h3>
            <p class="mb-4 has-text-grey">
                Import a full dictionary for <strong><?= $escapedLangName ?></strong>
                to get translations for all your words at once.
            </p>

            <!-- Import result notification -->
            <template x-if="dictImportResult !== null">
                <div :class="dictResultClass()" class="mb-4">
                    <button class="delete" @click="dismissDictResult()"></button>
                    <template x-if="dictImportResult.success">
                        <p>
                            <strong>Dictionary imported!</strong>
                            <span x-text="dictImportResult.imported"></span> entries added.
                        </p>
                    </template>
                    <template x-if="!dictImportResult.success">
                        <p>
                            <strong>Import failed:</strong>
                            <span x-text="dictImportResult.error"></span>
                        </p>
                    </template>
                </div>
            </template>

            <template x-for="source in dictSources" :key="source.name">
                <div class="card mb-3">
                    <div class="card-content p-4">
                        <p class="title is-6 mb-2" x-text="source.name"></p>
                        <div class="tags mb-2">
                            <span class="tag is-info is-light" x-text="source.format"></span>
                            <span class="tag is-light" x-text="source.entries"></span>
                            <span class="tag is-success is-light" x-text="source.license"></span>
                        </div>
                        <p class="is-size-7 has-text-grey" x-text="source.notes"></p>
                    </div>
                    <footer class="card-footer">
                        <a class="card-footer-item has-text-success"
                           x-show="source.directDownload"
                           @click.prevent="importDictionary(source)">
                            <span class="icon is-small mr-1" x-show="!isDictImporting(source.url)">
                                <?= $downloadIcon ?>
                            </span>
                            <span x-text="dictButtonLabel(source.url)"></span>
                        </a>
                        <a class="card-footer-item has-text-primary"
                           :href="source.url" target="_blank" rel="noopener">
                            <span class="icon is-small mr-1">
                                <?= $externalLinkIcon ?>
                            </span>
                            Download
                        </a>
                    </footer>
                </div>
            </template>
        </div>
    </template>
    <?php endif; ?>

    <?php endif; ?>
</div>

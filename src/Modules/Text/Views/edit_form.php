<?php

declare(strict_types=1);

/**
 * Text Edit Form View - Display form for creating/editing texts
 *
 * Variables expected:
 * - $textId: int - Text ID (0 for new text)
 * - $text: object{id: int, lgid: int, title: string, text: string, source: string, media_uri: string} - Text object
 * - $annotated: bool - Whether the text has annotations
 * - $languageData: array - Mapping of language ID to language code
 * - $isNew: bool - Whether this is a new text
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress UndefinedVariable - Variables are set by the including controller
 * @psalm-suppress TypeDoesNotContainType View included from different contexts
 *
 * @var int $textId
 * @var object{id: int, lgid: int, title: string, text: string, source: string, media_uri: string} $text
 * @var bool $annotated
 * @var array<int, string> $languageData
 * @var array<int, array{id: int, name: string}> $languages
 * @var bool $isNew
 * @var string $scrdir
 */

namespace Lwt\Views\Text;

use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\UI\Helpers\SearchableSelectHelper;
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Modules\Admin\Application\Services\MediaService;
use Lwt\Modules\Text\Http\YouTubeApiHandler;

// Type-safe variable extraction from controller context
assert(is_array($languageData));
assert(is_array($languages));
/** @var array<int, array{id: int|string, name: string}> $languagesTyped */
$languagesTyped = $languages;
assert(is_object($text) && property_exists($text, 'lgid'));

/** @var int $textIdTyped */
$textIdTyped = $textId;
/** @var int $textLgId */
$textLgId = $text->lgid;
/** @var string $textTitle */
$textTitle = $text->title;
/** @var string $textContent */
$textContent = $text->text;
/** @var string $textSource */
$textSource = $text->source;
/** @var string $textMediaUri */
$textMediaUri = $text->media_uri;
/** @var string $scrdirTyped */
$scrdirTyped = $scrdir;

// Build actions only for edit mode (not new text)
$actions = [];
if (!$isNew) {
    $actions[] = ['url' => '/texts/new', 'label' => 'New Text', 'icon' => 'circle-plus', 'class' => 'is-primary'];
    $actions[] = ['url' => '/book/import', 'label' => 'Import EPUB', 'icon' => 'book'];
    $actions[] = ['url' => '/books', 'label' => 'My Books', 'icon' => 'library'];
    $actions[] = ['url' => '/feeds?page=1&check_autoupdate=1', 'label' => 'Newsfeed Import', 'icon' => 'rss'];
    $actions[] = ['url' => '/texts?query=&page=1', 'label' => 'Active Texts', 'icon' => 'book-open'];
}

?>
<script type="application/json" id="text-edit-config">
<?php echo json_encode(['languageData' => $languageData]); ?>
</script>

<?php if (!$isNew) : ?>
<h2 class="title is-4 is-flex is-align-items-center">
    Edit Text
    <a target="_blank" href="docs/info.html#howtotext" class="ml-2">
        <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
    </a>
</h2>
    <?php echo PageLayoutHelper::buildActionCard($actions); ?>
<?php endif; ?>

<form class="validate" method="post" enctype="multipart/form-data"
      action="<?php echo $isNew ? '/texts/new' : '/texts#rec' . $textIdTyped; ?>"
      x-data="{
          importMode: 'manual',
          showAdvanced: <?php echo $isNew ? 'false' : 'true'; ?>
      }">
    <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
    <input type="hidden" name="TxID" value="<?php echo $textIdTyped; ?>" />

    <?php if ($isNew) : ?>
    <!-- New Text Form -->
    <div class="container mb-5" style="max-width: 500px;">
        <!-- Language (at the top) -->
        <div class="field mb-5">
            <label class="label is-medium" for="TxLgID">Language</label>
            <div class="control">
                <?php echo SearchableSelectHelper::forLanguages(
                    $languagesTyped,
                    $textLgId,
                    [
                        'name' => 'TxLgID',
                        'id' => 'TxLgID',
                        'placeholder' => '[Choose...]',
                        'required' => true,
                        'dataAction' => 'change-language',
                        'size' => 'medium'
                    ]
                ); ?>
            </div>
        </div>

        <!-- Import Method Selection -->
        <div class="field">
            <label class="label is-medium">How do you want to add your text?</label>
            <div class="control">
                <div class="buttons is-centered" style="flex-wrap: wrap;">
                    <button type="button"
                            class="button"
                            :class="importMode === 'file' ? 'is-primary is-selected' : ''"
                            @click="importMode = 'file'">
                        <span class="icon">
                            <?php echo IconHelper::render('file-up', ['alt' => 'File']); ?>
                        </span>
                        <span>Import from file</span>
                    </button>
                    <button type="button"
                            class="button"
                            :class="importMode === 'manual' ? 'is-primary is-selected' : ''"
                            @click="importMode = 'manual'">
                        <span class="icon">
                            <?php echo IconHelper::render('pencil', ['alt' => 'Manual']); ?>
                        </span>
                        <span>Paste text</span>
                    </button>
                    <button type="button"
                            class="button"
                            :class="importMode === 'url' ? 'is-primary is-selected' : ''"
                            @click="importMode = 'url'">
                        <span class="icon">
                            <?php echo IconHelper::render('link', ['alt' => 'URL']); ?>
                        </span>
                        <span>Import from URL</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- File Import Section -->
        <div x-show="importMode === 'file'" x-transition x-cloak class="mt-4" :inert="importMode !== 'file'">
            <p class="help mb-4">
                Text files, EPUB, SRT/VTT subtitles, or audio/video for transcription
            </p>

            <!-- Upload file from computer -->
            <div class="field">
                <label class="label">From your computer</label>
                <div class="file has-name is-fullwidth">
                    <label class="file-label">
                        <input class="file-input"
                               type="file"
                               name="importFile"
                               id="importFile"
                               accept=".srt,.vtt,.epub,.txt,.mp3,.mp4,.wav,.webm,.ogg,.m4a,.mkv,.flac"
                               @change="$el.closest('.file').querySelector('.file-name').textContent =
                                   $el.files[0]?.name || 'No file selected'" />
                        <span class="file-cta">
                            <span class="file-icon">
                                <?php echo IconHelper::render('file-up', ['alt' => 'Upload']); ?>
                            </span>
                            <span class="file-label">Browse...</span>
                        </span>
                        <span class="file-name">No file selected</span>
                    </label>
                </div>
                <p id="importFileStatus" class="help"></p>
            </div>

            <!-- Or select from server media folder -->
            <div class="field mt-4">
                <label class="label">Or from the server's media folder</label>
                <div class="control" id="mediaselect">
                    <?php
                    $mediaPaths = (new MediaService())->getMediaPaths();
                    $mediaJson = json_encode($mediaPaths);
                    ?>
                    <p class="help mb-2">Files in "../<?php echo htmlspecialchars($mediaPaths['base_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>/media":</p>
                    <p id="mediaSelectErrorMessage"></p>
                    <?php echo IconHelper::render('loader-2', ['id' => 'mediaSelectLoadingImg', 'alt' => 'Loading...', 'class' => 'icon-spin']); ?>
                    <select name="Dir" class="input" data-action="media-dir-select" data-target-field="TxAudioURI"></select>
                    <span class="click" data-action="refresh-media-select">
                        <?php echo IconHelper::render('refresh-cw', ['title' => 'Refresh', 'alt' => 'Refresh']); ?>
                        Refresh
                    </span>
                    <script type="application/json" data-lwt-media-select-config><?php echo $mediaJson !== false ? $mediaJson : '{}'; ?></script>
                </div>
            </div>

            <!-- Whisper Transcription Options (shown when audio/video selected) -->
            <div id="whisperOptions" class="box mt-3" style="display: none;">
                <h4 class="subtitle is-6 mb-3">
                    <?php echo IconHelper::render('mic', ['alt' => 'Transcription']); ?>
                    Transcription Options
                </h4>

                <div class="field">
                    <label class="label is-small" for="whisperLanguage">Transcription Language</label>
                    <div class="control">
                        <div class="select is-small is-fullwidth">
                            <select id="whisperLanguage" name="whisperLanguage">
                                <option value="">Auto-detect</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label class="label is-small" for="whisperModel">Model Size</label>
                    <div class="control">
                        <div class="select is-small is-fullwidth">
                            <select id="whisperModel" name="whisperModel">
                                <option value="base">Base (fast)</option>
                                <option value="small" selected>Small (balanced)</option>
                                <option value="medium">Medium (better quality)</option>
                                <option value="large">Large (best quality)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <div class="control">
                        <button type="button" class="button is-info" id="startTranscription">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('mic', ['alt' => 'Transcribe']); ?>
                            </span>
                            <span>Start Transcription</span>
                        </button>
                    </div>
                </div>

                <div id="whisperProgress" class="notification is-info is-light mt-3" style="display: none;">
                    <div class="level mb-2">
                        <div class="level-left">
                            <span id="whisperStatusText">Preparing transcription...</span>
                        </div>
                        <div class="level-right">
                            <button type="button" class="button is-small is-danger is-outlined" id="whisperCancel">
                                Cancel
                            </button>
                        </div>
                    </div>
                    <progress class="progress is-info" id="whisperProgressBar" value="0" max="100"></progress>
                </div>
            </div>

            <div id="whisperUnavailable" class="notification is-warning is-light mt-3" style="display: none;">
                <span class="icon-text">
                    <span class="icon">
                        <?php echo IconHelper::render('alert-triangle', ['alt' => 'Warning']); ?>
                    </span>
                    <span>Whisper transcription is not available. Please ensure the NLP service is running.</span>
                </span>
            </div>
        </div>

        <!-- Manual Text Entry Section -->
        <div x-show="importMode === 'manual'" x-transition class="mt-4" :inert="importMode !== 'manual'">
            <!-- Title -->
            <div class="field">
                <label class="label" for="TxTitle">Title</label>
                <div class="control">
                    <input type="text"
                           class="input notempty checkoutsidebmp"
                           data_info="Title"
                           name="TxTitle"
                           id="TxTitle"
                           value="<?php echo \htmlspecialchars($textTitle, ENT_QUOTES, 'UTF-8'); ?>"
                           maxlength="200"
                           placeholder="Enter a title"
                           required />
                </div>
            </div>

            <!-- Text Content -->
            <div class="field">
                <label class="label" for="TxText">Text</label>
                <div class="control">
                    <textarea <?php echo $scrdirTyped; ?>
                              name="TxText"
                              id="TxText"
                              class="textarea notempty checkoutsidebmp"
                              data_info="Text"
                              rows="10"
                              placeholder="Paste your text here..."
                              required><?php echo \htmlspecialchars($textContent, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>
        </div>

        <!-- URL Import Section -->
        <div x-show="importMode === 'url'" x-transition x-cloak class="mt-4" :inert="importMode !== 'url'">
            <div class="field">
                <label class="label">Video URL</label>
                <div class="control">
                    <input type="url"
                           class="input"
                           name="TxMediaURL"
                           id="TxMediaURL"
                           placeholder="https://www.youtube.com/watch?v=... or https://vimeo.com/..." />
                </div>
                <p class="help">YouTube, Vimeo, Dailymotion, Bilibili, NicoNico, or PeerTube URL. Captions will be imported if available.</p>
            </div>

            <?php if ((new YouTubeApiHandler())->formatIsConfigured()['configured']) : ?>
            <div class="field mt-3">
                <label class="label is-small">Or enter YouTube Video ID directly</label>
                <div class="control">
                    <div class="field has-addons mb-0">
                        <div class="control is-expanded">
                            <input type="text"
                                   class="input is-small"
                                   id="ytVideoId"
                                   placeholder="e.g., dQw4w9WgXcQ" />
                        </div>
                        <div class="control">
                            <button type="button" class="button is-info is-small" data-action="fetch-youtube">
                                Fetch Captions
                            </button>
                        </div>
                    </div>
                </div>
                <p id="ytDataStatus" class="help"></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Save Button -->
        <div class="field mt-5">
            <div class="control">
                <button type="submit" name="op" value="Save and Open" class="button is-primary is-medium is-fullwidth">
                    <span class="icon">
                        <?php echo IconHelper::render('book-open', ['alt' => 'Save and Open']); ?>
                    </span>
                    <span>Save &amp; Start Reading</span>
                </button>
            </div>
        </div>

        <!-- Cancel link -->
        <div class="has-text-centered mt-3">
            <a href="/" class="has-text-grey">Cancel</a>
        </div>
    </div>

    <!-- Additional Options (collapsible, for new texts) -->
    <div class="container" style="max-width: 600px;">
        <div class="box" x-data="{ open: showAdvanced }">
            <header class="is-flex is-align-items-center is-justify-content-space-between is-clickable"
                    @click="open = !open">
                <h4 class="title is-6 mb-0 is-flex is-align-items-center">
                    <span class="icon mr-2">
                        <?php echo IconHelper::render('settings', ['alt' => 'Settings']); ?>
                    </span>
                    Additional Options
                </h4>
                <span class="icon">
                    <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
                </span>
            </header>

            <div x-show="open" x-transition x-cloak class="mt-4">
                <!-- Source URI -->
                <div class="field">
                    <label class="label" for="TxSourceURI">Source URI</label>
                    <div class="control">
                        <input type="url"
                               class="input checkurl checkoutsidebmp"
                               data_info="Source URI"
                               name="TxSourceURI"
                               id="TxSourceURI"
                               value="<?php echo \htmlspecialchars($textSource, ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="1000"
                               placeholder="https://example.com/article" />
                    </div>
                    <p class="help">Link to the original source of this text.</p>
                </div>

                <!-- Tags -->
                <div class="field">
                    <label class="label">Tags</label>
                    <div class="control">
                        <?php echo \Lwt\Modules\Tags\Application\TagsFacade::getTextTagsHtml($textIdTyped); ?>
                    </div>
                    <p class="help">Organize texts with tags for easy filtering.</p>
                </div>
            </div>
        </div>
    </div>

    <?php else : ?>
    <!-- Edit Mode: Show full form -->
    <div class="box">
        <!-- Language -->
        <div class="field">
            <label class="label" for="TxLgID">
                Language
                <span class="icon has-text-danger is-small" title="Required field">
                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                </span>
            </label>
            <div class="control">
                <?php echo SearchableSelectHelper::forLanguages(
                    $languagesTyped,
                    $textLgId,
                    [
                        'name' => 'TxLgID',
                        'id' => 'TxLgID',
                        'placeholder' => '[Choose...]',
                        'required' => true,
                        'dataAction' => 'change-language'
                    ]
                ); ?>
            </div>
        </div>

        <!-- Title -->
        <div class="field">
            <label class="label" for="TxTitle">
                Title
                <span class="icon has-text-danger is-small" title="Required field">
                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                </span>
            </label>
            <div class="control">
                <input type="text"
                       class="input notempty checkoutsidebmp"
                       data_info="Title"
                       name="TxTitle"
                       id="TxTitle"
                       value="<?php echo \htmlspecialchars($textTitle, ENT_QUOTES, 'UTF-8'); ?>"
                       maxlength="200"
                       required />
            </div>
        </div>

        <!-- Text Content -->
        <div class="field">
            <label class="label" for="TxText">
                Text
                <span class="icon has-text-danger is-small" title="Required field">
                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                </span>
            </label>
            <div class="control">
                <textarea <?php echo $scrdirTyped; ?>
                          name="TxText"
                          id="TxText"
                          class="textarea notempty checkoutsidebmp"
                          data_info="Text"
                          rows="15"
                          required><?php echo \htmlspecialchars($textContent, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>

        <!-- Annotated Text (only for existing texts) -->
        <div class="field">
            <label class="label">Annotated Text</label>
            <div class="control">
                <?php if ($annotated) : ?>
                <div class="notification is-info is-light">
                    <span class="icon-text">
                        <span class="icon has-text-success">
                            <?php echo IconHelper::render('check', ['alt' => 'Has Annotation']); ?>
                        </span>
                        <span>Exists - May be partially or fully lost if you change the text!</span>
                    </span>
                    <div class="mt-2">
                        <button type="button"
                                class="button is-small is-info is-outlined"
                                data-action="navigate"
                                data-url="/text/<?php echo $textIdTyped; ?>/print">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('printer', ['alt' => 'Print']); ?>
                            </span>
                            <span>Print/Edit...</span>
                        </button>
                    </div>
                </div>
                <?php else : ?>
                <div class="notification is-light">
                    <span class="icon-text">
                        <span class="icon has-text-grey">
                            <?php echo IconHelper::render('x', ['alt' => 'No Annotation']); ?>
                        </span>
                        <span>None</span>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Source URI -->
        <div class="field">
            <label class="label" for="TxSourceURI">Source URI</label>
            <div class="control">
                <input type="url"
                       class="input checkurl checkoutsidebmp"
                       data_info="Source URI"
                       name="TxSourceURI"
                       id="TxSourceURI"
                       value="<?php echo \htmlspecialchars($textSource, ENT_QUOTES, 'UTF-8'); ?>"
                       maxlength="1000"
                       placeholder="https://example.com/article" />
            </div>
        </div>

        <!-- Tags -->
        <div class="field">
            <label class="label">Tags</label>
            <div class="control">
                <?php echo \Lwt\Modules\Tags\Application\TagsFacade::getTextTagsHtml($textIdTyped); ?>
            </div>
        </div>

        <!-- Media URI -->
        <div class="field">
            <label class="label" for="TxAudioURI">Media URI</label>
            <div class="control">
                <input type="text"
                       class="input checkoutsidebmp"
                       data_info="Audio-URI"
                       name="TxAudioURI"
                       id="TxAudioURI"
                       maxlength="2048"
                       value="<?php echo \htmlspecialchars($textMediaUri, ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="media/audio.mp3" />
            </div>
            <div class="mt-2" id="mediaselect">
                <?php echo (new MediaService())->getMediaPathSelector('TxAudioURI'); ?>
            </div>
        </div>
    </div>

    <!-- Form Actions (Edit mode) -->
    <div class="field is-grouped is-grouped-right">
        <div class="control">
            <button type="button"
                    class="button is-light"
                    data-action="cancel-form"
                    data-url="/texts#rec<?php echo $textIdTyped; ?>">
                Cancel
            </button>
        </div>
        <div class="control">
            <button type="submit" name="op" value="Check" class="button is-info is-outlined">
                <span class="icon is-small">
                    <?php echo IconHelper::render('check', ['alt' => 'Check']); ?>
                </span>
                <span>Check</span>
            </button>
        </div>
        <div class="control">
            <button type="submit" name="op" value="Change" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                </span>
                <span>Save Changes</span>
            </button>
        </div>
        <div class="control">
            <button type="submit" name="op" value="Change and Open" class="button is-success">
                <span class="icon is-small">
                    <?php echo IconHelper::render('book-open', ['alt' => 'Save and Open']); ?>
                </span>
                <span>Save &amp; Open</span>
            </button>
        </div>
    </div>
    <?php endif; ?>
</form>

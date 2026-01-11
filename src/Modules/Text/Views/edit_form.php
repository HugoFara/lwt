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
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Modules\Admin\Application\Services\MediaService;
use Lwt\Core\Integration\YouTubeImport;

// Type-safe variable extraction from controller context
/**
 * @var int $textId
*/
/**
 * @var bool $annotated
*/
assert(is_array($languageData));
/**
 * @var array<int, string> $languageData
*/
assert(is_array($languages));
/**
 * @var array<int, array{id: int, name: string}> $languagesTyped
*/
$languagesTyped = $languages;
/**
 * @var bool $isNew
*/
/**
 * @var string $scrdir
*/

// Extract typed properties from $text for use in template
assert(is_object($text) && property_exists($text, 'lgid'));
/**
 * @var int $textIdTyped
*/
$textIdTyped = $textId;
/**
 * @var int $textLgId
*/
$textLgId = $text->lgid;
/**
 * @var string $textTitle
*/
$textTitle = $text->title;
/**
 * @var string $textContent
*/
$textContent = $text->text;
/**
 * @var string $textSource
*/
$textSource = $text->source;
/**
 * @var string $textMediaUri
*/
$textMediaUri = $text->media_uri;
$scrdirTyped = $scrdir;

// Build actions based on whether this is a new or existing text
$actions = [];
if (!$isNew) {
    $actions[] = ['url' => '/texts?new=1', 'label' => 'New Text', 'icon' => 'circle-plus', 'class' => 'is-primary'];
}
$actions[] = ['url' => '/book/import', 'label' => 'Import EPUB', 'icon' => 'book'];
$actions[] = ['url' => '/books', 'label' => 'My Books', 'icon' => 'library'];
$actions[] = ['url' => '/feeds?page=1&check_autoupdate=1', 'label' => 'Newsfeed Import', 'icon' => 'rss'];
$actions[] = ['url' => '/texts?query=&page=1', 'label' => 'Active Texts', 'icon' => 'book-open'];
if ($isNew) {
    $actions[] = ['url' => '/text/archived?query=&page=1', 'label' => 'Archived Texts', 'icon' => 'archive'];
}

?>
<script type="application/json" id="text-edit-config">
<?php echo json_encode(['languageData' => $languageData]); ?>
</script>

<h2 class="title is-4 is-flex is-align-items-center">
    <?php echo $isNew ? "New" : "Edit"; ?> Text
    <a target="_blank" href="docs/info.html#howtotext" class="ml-2">
        <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
    </a>
</h2>

<?php echo PageLayoutHelper::buildActionCard($actions); ?>

<form class="validate" method="post" enctype="multipart/form-data"
      action="/texts<?php echo $isNew ? '' : '#rec' . $textIdTyped; ?>"
      x-data="{ showAnnotation: <?php echo $isNew ? 'false' : 'true'; ?> }">
    <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
    <input type="hidden" name="TxID" value="<?php echo $textIdTyped; ?>" />

    <div class="box">
        <?php if ($isNew) : ?>
        <!-- Import from File (FIRST for new texts) -->
        <div class="field">
            <label class="label">Import from File</label>
            <div class="file has-name is-fullwidth">
                <label class="file-label">
                    <input class="file-input" type="file" name="importFile" id="importFile"
                           accept=".srt,.vtt,.epub,.mp3,.mp4,.wav,.webm,.ogg,.m4a,.mkv,.flac"
                           onchange="this.closest('.file').querySelector('.file-name').textContent = this.files[0]?.name || 'No file selected'" />
                    <span class="file-cta">
                        <span class="file-icon">
                            <?php echo IconHelper::render('file-up', ['alt' => 'Upload']); ?>
                        </span>
                        <span class="file-label">Choose file...</span>
                    </span>
                    <span class="file-name">No file selected</span>
                </label>
            </div>
            <p class="help">
                Supported: EPUB books, SRT/VTT subtitles, audio/video (MP3, MP4, WAV, WebM, OGG, M4A, FLAC, MKV - requires Whisper).
            </p>
            <p id="importFileStatus" class="help"></p>

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
                    <p class="help">Leave as auto-detect or select to improve accuracy.</p>
                </div>

                <div class="field">
                    <label class="label is-small" for="whisperModel">Model Size</label>
                    <div class="control">
                        <div class="select is-small is-fullwidth">
                            <select id="whisperModel" name="whisperModel">
                                <option value="base">Base (fast, good quality)</option>
                                <option value="small" selected>Small (balanced)</option>
                                <option value="medium">Medium (better quality, slower)</option>
                                <option value="large">Large (best quality, slowest)</option>
                            </select>
                        </div>
                    </div>
                    <p class="help">Larger models are more accurate but take longer to process.</p>
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

                <!-- Transcription Progress -->
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

            <!-- Whisper Unavailable Message -->
            <div id="whisperUnavailable" class="notification is-warning is-light mt-3" style="display: none;">
                <span class="icon-text">
                    <span class="icon">
                        <?php echo IconHelper::render('alert-triangle', ['alt' => 'Warning']); ?>
                    </span>
                    <span>Whisper transcription is not available. Please ensure the NLP service is running.</span>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Language -->
        <div class="field">
            <label class="label" for="TxLgID">
                Language
                <span class="icon has-text-danger is-small" title="Required field">
                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                </span>
            </label>
            <div class="control">
                <div class="select is-fullwidth">
                    <select name="TxLgID" id="TxLgID" class="notempty setfocus"
                            data-action="change-language"
                            title="Select the language of your text"
                            required>
                        <?php echo SelectOptionsBuilder::forLanguages($languagesTyped, $textLgId, "[Choose...]"); ?>
                    </select>
                </div>
            </div>
            <p class="help">The language determines how the text will be parsed into words and sentences.</p>
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
                       placeholder="Enter a descriptive title for your text"
                       title="A short, memorable title to identify this text"
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
                          placeholder="Paste or type your text here..."
                          title="The text you want to study"
                          required><?php echo \htmlspecialchars($textContent, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <p class="help">
                Long texts (over 60KB) will be automatically split into chapters and saved as a book.
            </p>
        </div>

        <!-- Annotated Text (only for existing texts) -->
        <?php if (!$isNew) : ?>
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
                                data-url="/text/print?text=<?php echo $textIdTyped; ?>">
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
                    <div class="mt-2">
                        <button type="button"
                                class="button is-small is-outlined"
                                data-action="navigate"
                                data-url="print_impr_text.php?edit=1&amp;text=<?php echo $textIdTyped; ?>">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('plus', ['alt' => 'Create']); ?>
                            </span>
                            <span>Create/Print...</span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

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
                       placeholder="https://example.com/article"
                       title="Link to the original source of this text" />
            </div>
            <p class="help">Optional. The original webpage or document where this text came from.</p>
        </div>

        <!-- Tags -->
        <div class="field">
            <label class="label" title="Organize texts with tags for easy filtering">Tags</label>
            <div class="control">
                <?php echo \Lwt\Modules\Tags\Application\TagsFacade::getTextTagsHtml($textIdTyped); ?>
            </div>
            <p class="help">Optional. Add tags to categorize and filter your texts.</p>
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
                       placeholder="media/audio.mp3 or https://example.com/video.mp4"
                       title="Audio or video file to play while reading" />
            </div>
            <p class="help">Optional. Audio or video file to accompany the text (YouTube, Dailymotion, Vimeo URLs also supported).</p>
            <div class="mt-2" id="mediaselect">
                <?php echo (new MediaService())->getMediaPathSelector('TxAudioURI'); ?>
            </div>
        </div>

        <?php if ($isNew && YouTubeImport::isConfigured()) : ?>
        <!-- YouTube Import -->
        <div class="field">
            <label class="label" for="ytVideoId">YouTube Video</label>
            <div class="control">
                <div class="field has-addons mb-0">
                    <div class="control is-expanded">
                        <input type="text"
                               class="input"
                               id="ytVideoId"
                               placeholder="e.g., dQw4w9WgXcQ"
                               title="The video ID from a YouTube URL" />
                    </div>
                    <div class="control">
                        <button type="button" class="button is-info" data-action="fetch-youtube">
                            Fetch from YouTube
                        </button>
                    </div>
                </div>
                <input type="hidden" id="ytApiKey" value="<?php echo htmlspecialchars(YouTubeImport::getApiKey() ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
            <p class="help">Optional. Enter a YouTube video ID to import its captions as text.</p>
            <p id="ytDataStatus" class="help"></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Form Actions -->
    <div class="field is-grouped is-grouped-right">
        <div class="control">
            <button type="button"
                    class="button is-light"
                    data-action="cancel-form"
                    data-url="/texts<?php echo $isNew ? '' : '#rec' . $textIdTyped; ?>">
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
            <button type="submit" name="op" value="<?php echo $isNew ? 'Save' : 'Change'; ?>" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                </span>
                <span><?php echo $isNew ? 'Save' : 'Save Changes'; ?></span>
            </button>
        </div>
        <div class="control">
            <button type="submit" name="op" value="<?php echo $isNew ? 'Save' : 'Change'; ?> and Open" class="button is-success">
                <span class="icon is-small">
                    <?php echo IconHelper::render('book-open', ['alt' => 'Save and Open']); ?>
                </span>
                <span><?php echo $isNew ? 'Save' : 'Save'; ?> &amp; Open</span>
            </button>
        </div>
    </div>
</form>

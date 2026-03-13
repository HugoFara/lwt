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
 * @link     https://hugofara.github.io/lwt/developer/api
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

// Type-safe variable extraction from controller context
assert(is_array($languageData));
assert(is_array($languages));
/** @var array{base_path: string, paths?: string[], folders?: string[], error?: string} $mediaPaths */
assert(is_array($mediaPaths));
assert(is_string($mediaPathSelectorHtml));
assert(is_bool($youtubeConfigured));
assert(is_string($textTagsHtml));
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
      <?php if ($isNew) : ?>
      x-data="textNewForm"
      @webpage-imported="goToReview()"
      <?php else : ?>
      x-data
      <?php endif; ?>>
    <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
    <input type="hidden" name="TxID" value="<?php echo $textIdTyped; ?>" />

    <?php if ($isNew) : ?>
    <!-- New Text Form -->
    <div class="container mb-5" style="max-width: 500px;">
        <!-- Language from navbar selection -->
        <input type="hidden" name="TxLgID" id="TxLgID" value="<?php echo $textLgId; ?>" />

        <!-- Where to find texts (step 1 only) -->
        <div x-show="step === 1" x-transition class="mb-5">
            <div class="box" x-data="{ open: false }">
                <header class="is-flex is-align-items-center is-justify-content-space-between is-clickable"
                        @click="open = !open">
                    <h4 class="title is-6 mb-0 is-flex is-align-items-center">
                        <span class="icon mr-2">
                            <?php echo IconHelper::render('lightbulb', ['alt' => 'Tips']); ?>
                        </span>
                        Where to find texts?
                    </h4>
                    <span class="icon">
                        <i :class="open ? 'rotate-180' : ''" class="transition-transform" data-lucide="chevron-down"></i>
                    </span>
                </header>

                <div x-show="open" x-transition x-cloak class="mt-4 content is-small">
                    <p>Here are free sources for reading material in many languages:</p>

                    <h5 class="mb-2">Literature &amp; Public Domain</h5>
                    <ul>
                        <li>
                            <a href="https://www.gutenberg.org/" target="_blank" rel="noopener">Project Gutenberg</a>
                            &mdash; 75,000+ free e-books in 60+ languages (plain text, EPUB)
                        </li>
                        <li>
                            <a href="https://wikisource.org/" target="_blank" rel="noopener">Wikisource</a>
                            &mdash; Public domain texts in 70+ languages (novels, speeches, historical docs)
                        </li>
                    </ul>

                    <h5 class="mb-2">Simplified News (great for learners)</h5>
                    <ul>
                        <li>
                            <a href="https://www3.nhk.or.jp/news/easy/" target="_blank" rel="noopener">NHK News Web Easy</a>
                            &mdash; Simplified Japanese news with furigana
                        </li>
                        <li>
                            <a href="https://www.dw.com/de/deutsch-lernen/nachrichten/s-8030" target="_blank" rel="noopener">DW Langsam gesprochene Nachrichten</a>
                            &mdash; Slow German news with transcripts
                        </li>
                        <li>
                            <a href="https://savoirs.rfi.fr/fr/apprendre-enseigner/langue-francaise/journal-en-francais-facile" target="_blank" rel="noopener">RFI Journal en fran&ccedil;ais facile</a>
                            &mdash; Simple French news with transcripts
                        </li>
                        <li>
                            <a href="https://learningenglish.voanews.com/" target="_blank" rel="noopener">VOA Learning English</a>
                            &mdash; Slow-spoken English news (also has
                            <a href="https://learningenglish.voanews.com/rssfeeds" target="_blank" rel="noopener">RSS feeds</a>)
                        </li>
                    </ul>

                    <h5 class="mb-2">Subtitles &amp; Sentences</h5>
                    <ul>
                        <li>
                            <a href="https://www.opensubtitles.org/" target="_blank" rel="noopener">OpenSubtitles</a>
                            &mdash; Movie/TV subtitles in 100+ languages (SRT files can be imported directly)
                        </li>
                        <li>
                            <a href="https://tatoeba.org/" target="_blank" rel="noopener">Tatoeba</a>
                            &mdash; Community-curated sentences with translations in 400+ languages
                        </li>
                    </ul>

                    <h5 class="mb-2">Other Sources</h5>
                    <ul>
                        <li>
                            <a href="https://simple.wikipedia.org/" target="_blank" rel="noopener">Simple English Wikipedia</a>
                            &mdash; Articles in simplified English
                        </li>
                        <li>
                            <strong>RSS feeds</strong> &mdash; LWT can import from newsfeeds directly via
                            <a href="/feeds">Newsfeeds</a>. Most newspapers offer RSS feeds.
                        </li>
                        <li>
                            <strong>EPUB books</strong> &mdash; Import e-books via
                            <a href="/book/import">Import EPUB</a>
                        </li>
                    </ul>

                    <p class="has-text-grey mt-3">
                        Tip: Use <strong>Import from URL &rarr; Web Page</strong> above to fetch text directly from any of these sites. Just paste the article URL and click &ldquo;Fetch Content&rdquo;.
                    </p>
                </div>
            </div>
        </div>

        <!-- ═══ STEP 1: Choose Source ═══ -->
        <div x-show="step === 1" x-transition>
            <label class="label is-medium mb-3">How do you want to add your text?</label>

            <!-- Source cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.75rem;" class="mb-4">
                <div class="box has-text-centered p-3 is-clickable" :class="sourceActive('paste')" @click="selectSource('paste')" style="cursor: pointer;">
                    <span class="icon is-medium has-text-primary">
                        <?php echo IconHelper::render('pencil', ['alt' => 'Paste']); ?>
                    </span>
                    <p class="is-size-7 has-text-weight-medium mt-1">Paste Text</p>
                </div>
                <div class="box has-text-centered p-3 is-clickable" :class="sourceActive('url')" @click="selectSource('url')" style="cursor: pointer;">
                    <span class="icon is-medium has-text-primary">
                        <?php echo IconHelper::render('globe', ['alt' => 'URL']); ?>
                    </span>
                    <p class="is-size-7 has-text-weight-medium mt-1">Web / URL</p>
                </div>
                <div class="box has-text-centered p-3 is-clickable" :class="sourceActive('file')" @click="selectSource('file')" style="cursor: pointer;">
                    <span class="icon is-medium has-text-primary">
                        <?php echo IconHelper::render('file-up', ['alt' => 'File']); ?>
                    </span>
                    <p class="is-size-7 has-text-weight-medium mt-1">Upload File</p>
                </div>
                <div class="box has-text-centered p-3 is-clickable" :class="sourceActive('gutenberg')" @click="selectSource('gutenberg')" style="cursor: pointer;">
                    <span class="icon is-medium has-text-primary">
                        <?php echo IconHelper::render('book-open-text', ['alt' => 'Gutenberg']); ?>
                    </span>
                    <p class="is-size-7 has-text-weight-medium mt-1">Gutenberg</p>
                </div>
                <div class="box has-text-centered p-3 is-clickable" :class="sourceActive('feeds')" @click="selectSource('feeds')" style="cursor: pointer;">
                    <span class="icon is-medium has-text-primary">
                        <?php echo IconHelper::render('rss', ['alt' => 'Feeds']); ?>
                    </span>
                    <p class="is-size-7 has-text-weight-medium mt-1">News Feed</p>
                </div>
            </div>

        <!-- File Import Section -->
        <div x-show="source === 'file'" x-transition x-cloak class="mt-4">
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
                               @change="handleFileChange($event)" />
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
                    <?php $mediaJson = json_encode($mediaPaths); ?>
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

            <!-- Next: Review -->
            <div class="field mt-4">
                <button type="button" class="button is-primary is-fullwidth" @click="goToReview()">
                    <span>Next: Review</span>
                    <span class="icon"><?php echo IconHelper::render('arrow-right', ['alt' => 'Next']); ?></span>
                </button>
            </div>
        </div>

        <!-- URL Import Section -->
        <div x-show="source === 'url'" x-transition x-cloak class="mt-4"
             x-data="{ urlSubMode: 'webpage' }">

            <!-- Sub-mode toggle: Web page vs Video -->
            <div class="tabs is-small is-toggle is-centered mb-4">
                <ul>
                    <li :class="urlSubMode === 'webpage' ? 'is-active' : ''">
                        <a @click.prevent="urlSubMode = 'webpage'">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('globe', ['alt' => 'Web']); ?>
                            </span>
                            <span>Web Page</span>
                        </a>
                    </li>
                    <li :class="urlSubMode === 'video' ? 'is-active' : ''">
                        <a @click.prevent="urlSubMode = 'video'">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('video', ['alt' => 'Video']); ?>
                            </span>
                            <span>Video</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Web page import -->
            <div x-show="urlSubMode === 'webpage'" x-transition>
                <div class="field">
                    <label class="label">Web Page URL</label>
                    <div class="field has-addons mb-0">
                        <div class="control is-expanded">
                            <input type="url" class="input" id="webpageUrl"
                                   placeholder="https://example.com/article" />
                        </div>
                        <div class="control">
                            <button type="button" class="button is-info"
                                    data-action="fetch-webpage" id="fetchWebpageBtn">
                                Fetch Content
                            </button>
                        </div>
                    </div>
                    <p class="help">Paste any article or web page URL. The main text content will be extracted automatically.</p>
                    <p id="webpageImportStatus" class="help mt-2"></p>
                </div>
            </div>

            <!-- Video import -->
            <div x-show="urlSubMode === 'video'" x-transition>
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

                <?php if ($youtubeConfigured) : ?>
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
        </div>

        <!-- Gutenberg Browser Section -->
        <div x-show="source === 'gutenberg'" x-transition x-cloak class="mt-4">
            <div x-data="gutenbergBrowser">
                <p class="help mb-4">
                    Browse popular books from Project Gutenberg for your selected language.
                </p>

                <!-- No language & no books -->
                <div x-show="showPlaceholder()" class="notification is-warning is-light">
                    Please select a language above to browse Gutenberg books.
                </div>

                <!-- Loading state -->
                <div x-show="loading && books.length === 0" class="has-text-centered py-4">
                    <span class="icon is-large has-text-grey-light">
                        <i data-lucide="loader" style="width: 32px; height: 32px; animation: spin 1s linear infinite;"></i>
                    </span>
                </div>

                <!-- Error -->
                <div x-show="error" class="notification is-danger is-light is-size-7" x-text="error"></div>

                <!-- Books grid -->
                <div x-show="books.length > 0"
                     style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem;">
                    <template x-for="book in books" :key="book.id">
                        <div class="box p-3" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 140px;">
                            <div>
                                <p class="has-text-weight-semibold is-size-7" x-text="book.title"
                                   style="overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"></p>
                                <span x-show="book.difficultyTier"
                                      class="tag is-rounded mb-1" style="font-size: 0.65rem;"
                                      :class="bookTierClass(book)"
                                      x-text="bookTierLabel(book)"></span>
                                <p class="has-text-grey is-size-7" x-text="formatAuthors(book.authors)"
                                   style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></p>
                            </div>
                            <div class="mt-2">
                                <button type="button" @click="importBook(book)"
                                        class="button is-primary is-small is-fullwidth"
                                        :class="importingClass(book)"
                                        :disabled="isImporting()">
                                    <span class="icon"><i data-lucide="download"></i></span>
                                    <span>Preview</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Load more -->
                <div x-show="hasMore && books.length > 0" class="has-text-centered mt-3">
                    <button type="button" @click="loadMore()"
                            class="button is-small is-light"
                            :class="loadingClass()"
                            :disabled="loading">
                        <span class="icon"><i data-lucide="chevron-right"></i></span>
                        <span>Load more</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Feed Browser Section -->
        <div x-show="source === 'feeds'" x-transition x-cloak class="mt-4">
            <div x-data="feedBrowser">
                <p class="help mb-4">
                    Browse articles from your configured newsfeeds.
                    <a href="/feeds/wizard">Set up a new feed</a> if you don't have any yet.
                </p>

                <!-- Loading feeds -->
                <div x-show="loadingFeeds" class="has-text-centered py-4">
                    <span class="icon is-large has-text-grey-light">
                        <i data-lucide="loader" style="width: 32px; height: 32px; animation: spin 1s linear infinite;"></i>
                    </span>
                </div>

                <!-- Error -->
                <div x-show="error" class="notification is-danger is-light is-size-7" x-text="error"></div>

                <!-- Feed list (when no feed selected) -->
                <div x-show="!selectedFeed && !loadingFeeds">
                    <template x-if="showEmptyFeeds()">
                        <div class="notification is-info is-light">
                            <p>No feeds configured yet.</p>
                            <a href="/feeds/wizard" class="button is-info is-small mt-2">
                                <span class="icon"><i data-lucide="plus"></i></span>
                                <span>Add a feed</span>
                            </a>
                        </div>
                    </template>

                    <div x-show="feeds.length > 0" class="menu">
                        <template x-for="feed in feeds" :key="feed.id">
                            <a class="box p-3 mb-2 is-flex is-align-items-center is-justify-content-space-between"
                               style="cursor: pointer; text-decoration: none;"
                               @click="selectFeed(feed)">
                                <div>
                                    <p class="has-text-weight-semibold is-size-7" x-text="feed.name"></p>
                                    <p class="has-text-grey is-size-7" x-text="feedInfo(feed)"></p>
                                </div>
                                <span class="icon has-text-grey">
                                    <i data-lucide="chevron-right"></i>
                                </span>
                            </a>
                        </template>
                    </div>
                </div>

                <!-- Article list (when a feed is selected) -->
                <div x-show="selectedFeed">
                    <div class="is-flex is-align-items-center mb-3" style="gap: 0.5rem;">
                        <button type="button" class="button is-small is-light" @click="backToFeeds()">
                            <span class="icon"><i data-lucide="arrow-left"></i></span>
                            <span>Back</span>
                        </button>
                        <p class="has-text-weight-semibold is-size-6" x-text="selectedFeedName()"></p>
                    </div>

                    <!-- Loading articles -->
                    <div x-show="loadingArticles" class="has-text-centered py-4">
                        <span class="icon has-text-grey-light">
                            <i data-lucide="loader" style="width: 24px; height: 24px; animation: spin 1s linear infinite;"></i>
                        </span>
                    </div>

                    <template x-if="showEmptyArticles()">
                        <div class="notification is-info is-light is-size-7">
                            No articles in this feed. Try loading new articles from the
                            <a href="/feeds">Feeds page</a>.
                        </div>
                    </template>

                    <div x-show="articles.length > 0">
                        <template x-for="article in articles" :key="article.id">
                            <div class="box p-3 mb-2">
                                <div class="is-flex is-align-items-start is-justify-content-space-between" style="gap: 0.5rem;">
                                    <div style="flex: 1; min-width: 0;">
                                        <p class="has-text-weight-semibold is-size-7" x-text="article.title"
                                           style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></p>
                                        <p class="has-text-grey is-size-7" x-text="article.date"></p>
                                        <span class="tag is-rounded mt-1" style="font-size: 0.6rem;"
                                              :class="statusClass(article.status)"
                                              x-text="statusLabel(article.status)"></span>
                                    </div>
                                    <button type="button" @click="importArticle(article)"
                                            class="button is-primary is-small"
                                            :disabled="isImported(article)">
                                        <span class="icon"><i data-lucide="download"></i></span>
                                        <span>Preview</span>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- Pagination -->
                        <div x-show="showPagination()" class="is-flex is-justify-content-center mt-3" style="gap: 0.5rem;">
                            <button type="button" class="button is-small"
                                    :disabled="canGoPrev()"
                                    @click="prevPage()">
                                <span class="icon"><i data-lucide="chevron-left"></i></span>
                            </button>
                            <span class="is-size-7 is-flex is-align-items-center">
                                Page <span x-text="articlePage" class="mx-1"></span> of <span x-text="articleTotalPages" class="ml-1"></span>
                            </span>
                            <button type="button" class="button is-small"
                                    :disabled="canGoNext()"
                                    @click="nextPage()">
                                <span class="icon"><i data-lucide="chevron-right"></i></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </div><!-- end step 1 -->

        <!-- ═══ STEP 2: Review & Import ═══ -->
        <div x-show="step === 2" x-transition x-cloak>
            <!-- Back button -->
            <div class="is-flex is-align-items-center mb-4" style="gap: 0.5rem;">
                <button type="button" class="button is-small is-light" @click="goBack()">
                    <span class="icon"><?php echo IconHelper::render('arrow-left', ['alt' => 'Back']); ?></span>
                    <span>Back</span>
                </button>
                <h3 class="title is-5 mb-0">Review your text</h3>
            </div>

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
                           placeholder="Enter a title" />
                </div>
            </div>

            <!-- Text Content (hidden for file import) -->
            <div x-show="showTextArea()" class="field">
                <label class="label" for="TxText">Text</label>
                <div class="control">
                    <textarea <?php echo $scrdirTyped; ?>
                              name="TxText"
                              id="TxText"
                              class="textarea notempty checkoutsidebmp"
                              data_info="Text"
                              rows="10"
                              placeholder="Paste or edit your text here..."><?php echo \htmlspecialchars($textContent, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
            </div>

            <!-- File info (shown for file import) -->
            <div x-show="showFileInfo()" class="notification is-info is-light" x-cloak>
                <span class="icon-text">
                    <span class="icon"><?php echo IconHelper::render('file-check', ['alt' => 'File ready']); ?></span>
                    <span>Your file is ready. Text will be extracted when you save.</span>
                </span>
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
        </div><!-- end step 2 -->
    </div>

    <!-- Additional Options (step 2 only) -->
    <div x-show="step === 2" x-cloak class="container" style="max-width: 600px;">
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
                        <?php echo $textTagsHtml; ?>
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
                <?php echo $textTagsHtml; ?>
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
                <?php echo $mediaPathSelectorHtml; ?>
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

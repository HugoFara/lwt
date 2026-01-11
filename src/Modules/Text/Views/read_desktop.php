<?php

declare(strict_types=1);

/**
 * Desktop Text Reading Layout View
 *
 * Modern text reading interface using Alpine.js
 * with client-side rendering and reactive word state.
 *
 * Variables expected:
 * - $textId: int - Text ID
 * - $langId: int - Language ID (optional, will be fetched from API)
 * - $title: string - Text title (optional)
 * - $sourceUri: string|null - Source URI (optional)
 * - $media: string - Audio URI (optional)
 * - $audioPosition: int - Audio playback position (optional)
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @var int $textId
 * @var int $langId
 * @var string $title
 * @var string|null $sourceUri
 * @var string $media
 * @var int $audioPosition
 */

namespace Lwt\Views\Text;

use Lwt\Modules\Admin\Application\Services\MediaService;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

// Type-safe variable extraction from controller context
assert(is_int($textId));
assert(is_int($langId));
assert(is_string($title));
assert(is_string($media));
assert(is_int($audioPosition));
// Note: $sourceUri is typed as string|null in file-level docblock

// Book context for chapter navigation (optional)
if (!isset($bookContext) || !is_array($bookContext)) {
    $bookContext = null;
}
/**
 * @var array{
 *     bookId: int,
 *     bookTitle: string,
 *     chapterNum: int,
 *     totalChapters: int,
 *     chapterTitle: string,
 *     prevTextId: int|null,
 *     nextTextId: int|null,
 *     chapters: array<int, array{textId: int, chapterNum: int, title: string}>
 * }|null $bookContext
 */

?>
<!-- Main navigation -->
<?php echo PageLayoutHelper::buildNavbar('texts'); ?>

<div x-data="textReader" class="reading-page" x-cloak>
  <!-- Reading toolbar -->
  <div class="box py-2 px-4 mb-0" style="border-radius: 0;">
    <div class="level is-mobile">
      <div class="level-left">
        <div class="level-item">
          <strong x-text="title || 'Loading...'"></strong>
          <?php
            /**
             * @var string|null $sourceUri
             */
            $sourceUriTyped = $sourceUri;
            if (
                $sourceUriTyped !== null
                && $sourceUriTyped !== ''
                && !str_starts_with(trim($sourceUriTyped), '#')
            ) : ?>
                <?php
                echo \Lwt\Shared\UI\Helpers\IconHelper::link(
                    'external-link',
                    $sourceUriTyped,
                    ['alt' => 'Source'],
                    ['target' => '_blank', 'rel' => 'noopener', 'class' => 'ml-2', 'title' => 'Source']
                );
                ?>
            <?php endif; ?>
        </div>
      </div>
      <div class="level-right">
        <div class="level-item">
          <div class="field is-grouped is-grouped-multiline">
            <div class="control">
              <a href="/review?text=<?php echo $textId; ?>" class="button is-small">Review</a>
            </div>
            <div class="control">
              <a href="/text/print-plain?text=<?php echo $textId; ?>" class="button is-small">Print</a>
            </div>
            <div class="control">
              <a href="/texts?chg=<?php echo $textId; ?>" class="button is-small">Edit</a>
            </div>
            <div class="control">
              <button class="button is-small" :class="showAll ? 'is-info' : 'is-light'" @click="toggleShowAll">
                <span class="icon is-small">
                  <i class="fas" :class="showAll ? 'fa-check-square' : 'fa-square'"></i>
                </span>
                <span>Show All</span>
              </button>
            </div>
            <div class="control">
              <button
                class="button is-small"
                :class="showTranslations ? 'is-info' : 'is-light'"
                @click="toggleTranslations"
              >
                <span class="icon is-small">
                  <i class="fas" :class="showTranslations ? 'fa-check-square' : 'fa-square'"></i>
                </span>
                <span>Translations</span>
              </button>
            </div>
            <div class="control">
              <div class="dropdown is-hoverable is-right">
                <div class="dropdown-trigger">
                  <button class="button is-small">Actions</button>
                </div>
                <div class="dropdown-menu">
                  <div class="dropdown-content">
                    <a class="dropdown-item" @click.prevent="markAllWellKnown">Mark all Well Known</a>
                    <a class="dropdown-item" @click.prevent="markAllIgnored">Mark all Ignored</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Audio player (if media available) -->
  <?php if ($media !== '') : ?>
  <div class="box py-2 px-4 mb-0" style="border-radius: 0;">
        <?php (new MediaService())->renderMediaPlayer($media, $audioPosition); ?>
  </div>
  <?php endif; ?>

  <!-- Chapter navigation (if part of a book) -->
  <?php if ($bookContext !== null) : ?>
        <?php
        // Extract typed values for template use (types from docblock)
        $bookId = $bookContext['bookId'];
        $bookTitle = $bookContext['bookTitle'];
        $chapterNum = $bookContext['chapterNum'];
        $totalChapters = $bookContext['totalChapters'];
        $chapterTitle = $bookContext['chapterTitle'];
        $prevTextId = $bookContext['prevTextId'];
        $nextTextId = $bookContext['nextTextId'];
        /**
 * @var array<int, array{textId: int, chapterNum: int, title: string}>
*/
        $chapters = $bookContext['chapters'];
        ?>
  <div class="box py-2 px-4 mb-0" style="border-radius: 0; background: #f5f5f5;">
    <div class="level is-mobile">
      <div class="level-left">
        <div class="level-item">
          <a href="/book/<?php echo $bookId; ?>" class="has-text-grey-dark" title="View book">
            <span class="icon is-small mr-1">
              <i class="fas fa-book"></i>
            </span>
            <strong><?php echo htmlspecialchars($bookTitle); ?></strong>
          </a>
          <span class="has-text-grey ml-2">
            — Ch. <?php echo $chapterNum; ?>/<?php echo $totalChapters; ?>
            <?php if ($chapterTitle !== '') : ?>
              <em class="ml-1"><?php echo htmlspecialchars($chapterTitle); ?></em>
            <?php endif; ?>
          </span>
        </div>
      </div>
      <div class="level-right">
        <div class="level-item">
          <div class="buttons has-addons mb-0">
            <!-- Previous chapter -->
            <?php if ($prevTextId !== null) : ?>
            <a href="/text/read?text=<?php echo $prevTextId; ?>"
               class="button is-small" title="Previous chapter">
              <span class="icon is-small">
                <i class="fas fa-chevron-left"></i>
              </span>
              <span>Prev</span>
            </a>
            <?php else : ?>
            <button class="button is-small" disabled title="No previous chapter">
              <span class="icon is-small">
                <i class="fas fa-chevron-left"></i>
              </span>
              <span>Prev</span>
            </button>
            <?php endif; ?>

            <!-- Chapter dropdown -->
            <div class="dropdown is-hoverable">
              <div class="dropdown-trigger">
                <button class="button is-small">
                  <span>Ch. <?php echo $chapterNum; ?></span>
                  <span class="icon is-small">
                    <i class="fas fa-angle-down"></i>
                  </span>
                </button>
              </div>
              <div class="dropdown-menu" style="max-height: 300px; overflow-y: auto;">
                <div class="dropdown-content">
                  <?php foreach ($chapters as $chapter) : ?>
                  <a href="/text/read?text=<?php echo $chapter['textId']; ?>"
                     class="dropdown-item <?php echo ($chapter['chapterNum'] === $chapterNum) ? 'is-active' : ''; ?>">
                        <?php echo $chapter['chapterNum']; ?>.
                        <?php echo htmlspecialchars($chapter['title'] ?: 'Chapter ' . $chapter['chapterNum']); ?>
                  </a>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- Next chapter -->
            <?php if ($nextTextId !== null) : ?>
            <a href="/text/read?text=<?php echo $nextTextId; ?>"
               class="button is-small" title="Next chapter">
              <span>Next</span>
              <span class="icon is-small">
                <i class="fas fa-chevron-right"></i>
              </span>
            </a>
            <?php else : ?>
            <button class="button is-small" disabled title="No next chapter">
              <span>Next</span>
              <span class="icon is-small">
                <i class="fas fa-chevron-right"></i>
              </span>
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Loading state -->
  <div x-show="isLoading" class="has-text-centered py-6">
    <div class="loading-spinner"></div>
    <p class="mt-4 has-text-grey">Loading text...</p>
  </div>

  <!-- Error state -->
  <template x-if="error">
    <div class="notification is-danger mx-4 mt-4">
      <button class="delete" @click="error = null"></button>
      <p x-text="error"></p>
    </div>
  </template>

  <!-- Text content -->
  <div x-show="!isLoading && !error" class="reading-content p-4">
    <div
      id="thetext"
      class="content"
      :class="{ 'hide-translations': !showTranslations }"
      :style="store.rightToLeft ? 'direction: rtl' : ''"
    >
      <!-- Content rendered by JavaScript via textReader.renderTextContent() -->
    </div>
  </div>

  <!-- Word popover (info view - non-blocking) -->
  <?php require __DIR__ . '/word_popover.php'; ?>

  <!-- Word modal (edit view only) -->
  <?php require __DIR__ . '/word_modal.php'; ?>

  <!-- Multi-word modal -->
  <?php require __DIR__ . '/multi_word_modal.php'; ?>
</div>

<style>
/* Reading page specific styles */
.reading-page {
  min-height: 100vh;
}

.reading-content {
  max-width: 100%;
  margin: 0 auto;
}

#thetext {
  line-height: 1.8;
}

#thetext p {
  margin-bottom: 1rem;
}

/* Word styling */
.wsty, .mwsty {
  cursor: pointer;
  padding: 0 0.1em;
  border-radius: 3px;
}

.wsty:hover, .mwsty:hover {
  background-color: rgba(0, 0, 0, 0.1);
}

/* Status colors - underlines instead of backgrounds */
.status0 { border-bottom: solid 2px #5ABAFF; } /* Unknown - blue */
.status1 { border-bottom: solid 2px #E85A3C; } /* Level 1 - red */
.status2 { border-bottom: solid 2px #E8893C; } /* Level 2 - orange */
.status3 { border-bottom: solid 2px #E8B83C; } /* Level 3 - yellow */
.status4 { border-bottom: solid 2px #E8E23C; } /* Level 4 - pale yellow */
.status5 { border-bottom: solid 2px #66CC66; } /* Level 5 - green */
.status98 { border-bottom: dashed 1px #888888; color: #999; } /* Ignored */
.status99 { border-bottom: solid 2px #CCFFCC; } /* Well-known */

/* Hide translations class */
.hide-translations .word-ann {
  display: none !important;
}

/* Hidden items */
.hide {
  display: none !important;
}

/* Alpine.js cloak */
[x-cloak] {
  display: none !important;
}

/* Loading spinner */
.loading-spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto;
  border: 3px solid #dbdbdb;
  border-top-color: #3273dc;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>

<script type="application/json" id="text-reader-config"><?php echo json_encode(
    [
    'textId' => $textId,
    'langId' => $langId,
    ],
    JSON_HEX_TAG | JSON_HEX_AMP
); ?></script>

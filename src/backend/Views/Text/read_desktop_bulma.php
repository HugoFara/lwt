<?php declare(strict_types=1);
/**
 * Desktop Text Reading Layout View (Bulma + Alpine.js)
 *
 * Modern text reading interface using Bulma CSS and Alpine.js
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
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

namespace Lwt\Views\Text;

use Lwt\View\Helper\IconHelper;
use Lwt\View\Helper\PageLayoutHelper;

?>
<!-- Main navigation -->
<?php echo PageLayoutHelper::buildNavbar('texts'); ?>

<div x-data="textReader" class="reading-page" x-cloak>
  <!-- Reading toolbar -->
  <nav class="navbar is-light" role="navigation" aria-label="reading navigation">
    <div class="navbar-brand">
      <a class="navbar-item" href="/texts">
        <?php echo PageLayoutHelper::buildLogo(); ?>
      </a>
    </div>

    <div class="navbar-menu is-active">
      <div class="navbar-start">
        <!-- Navigation buttons -->
        <div class="navbar-item">
          <div class="buttons are-small">
            <button class="button" @click="goBack" title="Back">
              <?php echo IconHelper::render('arrow-left', ['size' => 16]); ?>
            </button>
          </div>
        </div>

        <!-- Title -->
        <div class="navbar-item">
          <span class="has-text-weight-semibold" x-text="title || 'Loading...'"></span>
          <?php if (isset($sourceUri) && $sourceUri !== '' && !str_starts_with(trim($sourceUri), '#')): ?>
          <a href="<?php echo htmlspecialchars($sourceUri, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="ml-2" rel="noopener">
            <?php echo IconHelper::render('link', ['size' => 14, 'title' => 'Text Source']); ?>
          </a>
          <?php endif; ?>
        </div>
      </div>

      <div class="navbar-end">
        <!-- Action buttons -->
        <div class="navbar-item">
          <div class="buttons are-small">
            <a href="/test?text=<?php echo $textId; ?>" class="button" title="Test">
              <?php echo IconHelper::render('circle-help', ['size' => 16]); ?>
            </a>
            <a href="/text/print-plain?text=<?php echo $textId; ?>" class="button" title="Print">
              <?php echo IconHelper::render('printer', ['size' => 16]); ?>
            </a>
            <a href="/texts?chg=<?php echo $textId; ?>" class="button" title="Edit Text">
              <?php echo IconHelper::render('file-pen', ['size' => 16]); ?>
            </a>
          </div>
        </div>

        <!-- Settings toggles -->
        <div class="navbar-item">
          <div class="field is-grouped">
            <div class="control">
              <label class="checkbox is-size-7">
                <input type="checkbox" x-model="showAll" @change="toggleShowAll">
                Show All
              </label>
            </div>
            <div class="control ml-3">
              <label class="checkbox is-size-7">
                <input type="checkbox" x-model="showTranslations" @change="toggleTranslations">
                Translations
              </label>
            </div>
          </div>
        </div>

        <!-- Bulk actions dropdown -->
        <div class="navbar-item has-dropdown is-hoverable">
          <a class="navbar-link is-size-7">
            Actions
          </a>
          <div class="navbar-dropdown is-right">
            <a class="navbar-item" @click.prevent="markAllWellKnown">
              <?php echo IconHelper::render('check-check', ['size' => 14]); ?>
              <span class="ml-2">Mark all Well Known</span>
            </a>
            <a class="navbar-item" @click.prevent="markAllIgnored">
              <?php echo IconHelper::render('eye-off', ['size' => 14]); ?>
              <span class="ml-2">Mark all Ignored</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Audio player (if media available) -->
  <?php if (isset($media) && $media !== ''): ?>
  <div class="box py-2 px-4 mb-0" style="border-radius: 0;">
    <?php \makeMediaPlayer($media, (int)($audioPosition ?? 0)); ?>
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

  <!-- Word modal -->
  <?php include __DIR__ . '/word_modal.php'; ?>
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

/* Status colors */
.status0 { background-color: #f5deb3; } /* Unknown - wheat */
.status1 { background-color: #ff6347; } /* Level 1 - tomato */
.status2 { background-color: #ffa500; } /* Level 2 - orange */
.status3 { background-color: #ffff00; } /* Level 3 - yellow */
.status4 { background-color: #90ee90; } /* Level 4 - lightgreen */
.status5 { background-color: #32cd32; } /* Level 5 - limegreen */
.status98 { background-color: transparent; color: #999; } /* Ignored */
.status99 { background-color: transparent; } /* Well-known */

/* Hide translations class */
.hide-translations .wsty[data-trans]::after,
.hide-translations .mwsty[data-trans]::after {
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

<script type="application/json" id="text-reader-config"><?php echo json_encode([
    'textId' => (int) $textId,
    'langId' => (int) ($langId ?? 0),
]); ?></script>

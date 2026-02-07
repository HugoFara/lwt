<?php

/**
 * Multi-Word Modal View (Bulma + Alpine.js)
 *
 * Displays multi-word expression form for creating/editing terms.
 * Works with the multiWordModal Alpine.js component and multiWordForm store.
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Views\Text;

?>
<div x-data="multiWordModal" x-cloak>
  <div class="modal" :class="{ 'is-active': isOpen }" role="dialog" aria-modal="true" aria-labelledby="multi-word-modal-title">
    <div class="modal-background" @click="close"></div>
    <div class="modal-card" style="max-width: 500px;">
      <header class="modal-card-head py-3">
        <p class="modal-card-title is-size-6" id="multi-word-modal-title" x-text="modalTitle"></p>
        <button class="delete" aria-label="Close dialog" @click="close" :disabled="isLoading || isSubmitting"></button>
      </header>
      <section class="modal-card-body">
        <!-- Loading overlay -->
        <div x-show="isLoading" class="has-text-centered py-4">
          <span class="icon is-large">
            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
          </span>
          <p class="mt-2">Loading...</p>
        </div>

        <!-- Form content -->
        <template x-if="!isLoading">
          <div>
            <!-- General error message -->
            <template x-if="store.errors.general">
              <div class="notification is-danger is-light mb-4">
                <button class="delete" @click="store.errors.general = null"></button>
                <span x-text="store.errors.general"></span>
              </div>
            </template>

            <!-- Multi-word text (read-only) -->
            <div class="field">
              <label class="label is-small">Multi-Word Expression</label>
              <div class="control">
                <input class="input" type="text" :value="store.formData.text" disabled>
              </div>
              <p class="help" x-text="store.formData.wordCount + ' words'"></p>
            </div>

            <!-- Translation -->
            <div class="field">
              <label class="label is-small">Translation</label>
              <div class="control">
                <textarea
                  class="textarea"
                  :class="{ 'is-danger': store.errors.translation }"
                  x-model="store.formData.translation"
                  @blur="store.validateField('translation')"
                  rows="2"
                  placeholder="Enter translation..."
                ></textarea>
              </div>
              <template x-if="store.errors.translation">
                <p class="help is-danger" x-text="store.errors.translation"></p>
              </template>
            </div>

            <!-- Romanization (if enabled for language) -->
            <template x-if="store.showRomanization">
              <div class="field">
                <label class="label is-small">Romanization</label>
                <div class="control">
                  <input
                    class="input"
                    :class="{ 'is-danger': store.errors.romanization }"
                    type="text"
                    x-model="store.formData.romanization"
                    @blur="store.validateField('romanization')"
                    placeholder="Enter romanization..."
                  >
                </div>
                <template x-if="store.errors.romanization">
                  <p class="help is-danger" x-text="store.errors.romanization"></p>
                </template>
              </div>
            </template>

            <!-- Sentence -->
            <div class="field">
              <label class="label is-small">Example Sentence</label>
              <div class="control">
                <textarea
                  class="textarea"
                  :class="{ 'is-danger': store.errors.sentence }"
                  x-model="store.formData.sentence"
                  @blur="store.validateField('sentence')"
                  rows="2"
                  placeholder="Example sentence with {term} in braces..."
                ></textarea>
              </div>
              <template x-if="store.errors.sentence">
                <p class="help is-danger" x-text="store.errors.sentence"></p>
              </template>
              <p class="help">Use {curly braces} around the term</p>
            </div>

            <!-- Status (1-5 only for multi-words) -->
            <div class="field">
              <label class="label is-small">Status</label>
              <div class="buttons are-small">
                <template x-for="s in statuses" :key="s.value">
                  <button
                    type="button"
                    class="button"
                    :class="getStatusButtonClass(s.value)"
                    @click="setStatus(s.value)"
                    x-text="s.abbr"
                    :title="s.label"
                  ></button>
                </template>
              </div>
            </div>

            <!-- Action buttons -->
            <div class="field is-grouped mt-5">
              <div class="control">
                <button
                  type="button"
                  class="button is-primary"
                  :class="{ 'is-loading': isSubmitting }"
                  :disabled="!store.canSubmit"
                  @click="save"
                >
                  <?php echo \Lwt\Shared\UI\Helpers\IconHelper::render('save', ['size' => 16]); ?>
                  <span class="ml-1">Save</span>
                </button>
              </div>
              <div class="control">
                <button type="button" class="button" @click="close" :disabled="isSubmitting">
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </template>
      </section>
    </div>
  </div>
</div>

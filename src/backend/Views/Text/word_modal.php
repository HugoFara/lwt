<?php declare(strict_types=1);
/**
 * Word Edit Modal View (Bulma + Alpine.js)
 *
 * Displays word edit form in a centered Bulma modal.
 * This modal is only used for editing - info view is handled by word_popover.
 * Works with the wordModal and wordEditForm Alpine.js components.
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

namespace Lwt\Views\Text;

?>
<div x-data="wordModal" x-cloak>
  <div class="modal" :class="{ 'is-active': isOpen }">
    <div class="modal-background" @click="close"></div>
    <div class="modal-card" style="max-width: 500px;">
      <header class="modal-card-head py-3">
        <p class="modal-card-title is-size-6" x-text="modalTitle"></p>
        <button class="delete" aria-label="close" @click="close" :disabled="isLoading"></button>
      </header>
      <section class="modal-card-body">
        <!-- Loading overlay -->
        <div x-show="formStore.isLoading" class="has-text-centered py-4">
          <span class="icon is-large">
            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
          </span>
          <p class="mt-2">Loading...</p>
        </div>

        <!-- EDIT VIEW -->
        <template x-if="viewMode === 'edit' && !formStore.isLoading">
          <div x-data="wordEditForm">
            <!-- General error message -->
            <template x-if="formStore.errors.general">
              <div class="notification is-danger is-light mb-4">
                <button class="delete" @click="formStore.errors.general = null"></button>
                <span x-text="formStore.errors.general"></span>
              </div>
            </template>

            <!-- Term (read-only) -->
            <div class="field">
              <label class="label is-small">Term</label>
              <div class="control">
                <input class="input" type="text" :value="formStore.formData.text" disabled>
              </div>
            </div>

            <!-- Translation -->
            <div class="field">
              <label class="label is-small">Translation <span class="has-text-danger">*</span></label>
              <div class="control">
                <textarea
                  class="textarea"
                  :class="{ 'is-danger': formStore.errors.translation }"
                  x-model="formStore.formData.translation"
                  @blur="validateField('translation')"
                  rows="2"
                  placeholder="Enter translation..."
                ></textarea>
              </div>
              <template x-if="formStore.errors.translation">
                <p class="help is-danger" x-text="formStore.errors.translation"></p>
              </template>
            </div>

            <!-- Romanization (if enabled for language) -->
            <template x-if="showRomanization">
              <div class="field">
                <label class="label is-small">Romanization</label>
                <div class="control">
                  <input
                    class="input"
                    :class="{ 'is-danger': formStore.errors.romanization }"
                    type="text"
                    x-model="formStore.formData.romanization"
                    @blur="validateField('romanization')"
                    placeholder="Enter romanization..."
                  >
                </div>
                <template x-if="formStore.errors.romanization">
                  <p class="help is-danger" x-text="formStore.errors.romanization"></p>
                </template>
              </div>
            </template>

            <!-- Sentence -->
            <div class="field">
              <label class="label is-small">Example Sentence</label>
              <div class="control">
                <textarea
                  class="textarea"
                  :class="{ 'is-danger': formStore.errors.sentence }"
                  x-model="formStore.formData.sentence"
                  @blur="validateField('sentence')"
                  rows="2"
                  placeholder="Example sentence with {term} in braces..."
                ></textarea>
              </div>
              <template x-if="formStore.errors.sentence">
                <p class="help is-danger" x-text="formStore.errors.sentence"></p>
              </template>
              <p class="help">Use {curly braces} around the term</p>
            </div>

            <!-- Notes -->
            <div class="field">
              <label class="label is-small">Notes</label>
              <div class="control">
                <textarea
                  class="textarea"
                  :class="{ 'is-danger': formStore.errors.notes }"
                  x-model="formStore.formData.notes"
                  @blur="validateField('notes')"
                  rows="2"
                  placeholder="Personal notes about this term..."
                ></textarea>
              </div>
              <template x-if="formStore.errors.notes">
                <p class="help is-danger" x-text="formStore.errors.notes"></p>
              </template>
            </div>

            <!-- Status -->
            <div class="field">
              <label class="label is-small">Status</label>
              <div class="buttons are-small">
                <template x-for="s in statuses" :key="s.value">
                  <button
                    type="button"
                    class="button"
                    :class="formStore.formData.status === s.value ? getStatusClass(s.value) : 'is-outlined'"
                    @click="formStore.formData.status = s.value"
                    x-text="s.abbr"
                  ></button>
                </template>
              </div>
            </div>

            <!-- Tags -->
            <div class="field">
              <label class="label is-small">Tags</label>
              <div class="control">
                <!-- Current tags -->
                <div class="tags mb-2" x-show="formStore.formData.tags.length > 0">
                  <template x-for="tag in formStore.formData.tags" :key="tag">
                    <span class="tag is-info is-light">
                      <span x-text="tag"></span>
                      <button type="button" class="delete is-small" @click="removeTag(tag)"></button>
                    </span>
                  </template>
                </div>
                <!-- Tag input with autocomplete -->
                <div class="dropdown" :class="{ 'is-active': showTagSuggestions }">
                  <div class="dropdown-trigger" style="width: 100%;">
                    <input
                      class="input is-small"
                      type="text"
                      x-model="tagInput"
                      @input="filterTags"
                      @keydown.enter.prevent="addTag(tagInput)"
                      @blur="hideTagSuggestions"
                      placeholder="Add tag..."
                    >
                  </div>
                  <div class="dropdown-menu" role="menu" style="width: 100%;">
                    <div class="dropdown-content">
                      <template x-for="tag in filteredTags" :key="tag">
                        <a href="#" class="dropdown-item" @mousedown.prevent="selectTagSuggestion(tag)" x-text="tag"></a>
                      </template>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Similar Terms -->
            <template x-if="formStore.similarTerms.length > 0">
              <div class="field">
                <label class="label is-small">Similar Terms</label>
                <div class="is-size-7">
                  <template x-for="term in formStore.similarTerms" :key="term.id">
                    <div class="is-flex is-justify-content-space-between is-align-items-center py-1" style="border-bottom: 1px solid #f0f0f0;">
                      <div>
                        <span class="has-text-weight-semibold" x-text="term.text"></span>
                        <span class="has-text-grey" x-text="term.translation ? ': ' + term.translation : ''"></span>
                      </div>
                      <button
                        type="button"
                        class="button is-small is-ghost"
                        @click="copyFromSimilar(term)"
                        title="Copy translation"
                        x-show="term.translation"
                      >
                        <?php echo \Lwt\Shared\UI\Helpers\IconHelper::render('copy', ['size' => 12]); ?>
                      </button>
                    </div>
                  </template>
                </div>
              </div>
            </template>

            <!-- Action buttons -->
            <div class="field is-grouped mt-5">
              <div class="control">
                <button
                  type="button"
                  class="button is-primary"
                  :class="{ 'is-loading': isSubmitting }"
                  :disabled="!formStore.canSubmit"
                  @click="save"
                >
                  <?php echo \Lwt\Shared\UI\Helpers\IconHelper::render('save', ['size' => 16]); ?>
                  <span class="ml-1">Save</span>
                </button>
              </div>
              <div class="control">
                <button type="button" class="button" @click="cancel" :disabled="isSubmitting">
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

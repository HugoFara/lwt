<?php declare(strict_types=1);
/**
 * Word Modal View (Bulma + Alpine.js)
 *
 * Displays word information and action buttons in a centered Bulma modal.
 * Supports two views: info (default) and edit (for creating/editing terms).
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
        <div x-show="isLoading" class="has-text-centered py-4">
          <span class="icon is-large">
            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
          </span>
          <p class="mt-2">Loading...</p>
        </div>

        <!-- INFO VIEW -->
        <template x-if="viewMode === 'info' && word && !isLoading">
          <div>
            <!-- Word text and audio -->
            <div class="is-flex is-justify-content-space-between is-align-items-center mb-3">
              <span class="is-size-4 has-text-weight-bold" x-text="word.text"></span>
              <button class="button is-small is-rounded" @click="speakWord" title="Listen">
                <?php echo \Lwt\View\Helper\IconHelper::render('volume-2', ['size' => 16]); ?>
              </button>
            </div>

            <!-- Translation/Romanization for known words -->
            <template x-if="!isUnknown && word.translation">
              <div class="mb-3">
                <p class="has-text-grey-dark" x-html="$markdown(word.translation)"></p>
                <template x-if="word.romanization">
                  <p class="is-size-7 has-text-grey" x-text="word.romanization"></p>
                </template>
              </div>
            </template>

            <!-- Notes for known words -->
            <template x-if="!isUnknown && word.notes">
              <div class="mb-3">
                <p class="is-size-7 has-text-grey mb-1">Notes:</p>
                <p class="has-text-grey-dark is-size-7" x-html="$markdown(word.notes)"></p>
              </div>
            </template>

            <!-- Tags if present -->
            <template x-if="!isUnknown && word.tags">
              <div class="mb-3">
                <span class="tag is-info is-light" x-text="word.tags"></span>
              </div>
            </template>

            <!-- Status buttons for known words -->
            <template x-if="!isUnknown">
              <div class="mb-4">
                <p class="is-size-7 has-text-grey mb-2">Status:</p>
                <div class="buttons are-small">
                  <template x-for="s in [1,2,3,4,5]" :key="s">
                    <button
                      class="button"
                      :class="isCurrentStatus(s) ? getStatusButtonClass(s) : 'is-outlined'"
                      :disabled="isLoading"
                      @click="setStatus(s)"
                      x-text="s"
                    ></button>
                  </template>
                  <button
                    class="button"
                    :class="isCurrentStatus(99) ? 'is-success' : 'is-outlined'"
                    :disabled="isLoading"
                    @click="setStatus(99)"
                  >WKn</button>
                  <button
                    class="button"
                    :class="isCurrentStatus(98) ? 'is-warning' : 'is-outlined'"
                    :disabled="isLoading"
                    @click="setStatus(98)"
                  >Ign</button>
                </div>
              </div>
            </template>

            <!-- Quick actions for unknown words -->
            <template x-if="isUnknown">
              <div class="mb-4">
                <p class="is-size-7 has-text-grey mb-2">Quick actions:</p>
                <div class="buttons">
                  <button class="button is-success" :disabled="isLoading" @click="markWellKnown">
                    <?php echo \Lwt\View\Helper\IconHelper::render('check', ['size' => 16]); ?>
                    <span class="ml-1">I know this well</span>
                  </button>
                  <button class="button is-warning" :disabled="isLoading" @click="markIgnored">
                    <?php echo \Lwt\View\Helper\IconHelper::render('x', ['size' => 16]); ?>
                    <span class="ml-1">Ignore</span>
                  </button>
                </div>
              </div>
            </template>

            <!-- Edit/Delete for known words -->
            <template x-if="!isUnknown && word.wordId">
              <div class="mb-4">
                <div class="buttons are-small">
                  <button class="button is-info is-outlined" @click="showEditForm" :disabled="isLoading">
                    <?php echo \Lwt\View\Helper\IconHelper::render('edit', ['size' => 14]); ?>
                    <span class="ml-1">Edit</span>
                  </button>
                  <button class="button is-danger is-outlined" :disabled="isLoading" @click="deleteWord">
                    <?php echo \Lwt\View\Helper\IconHelper::render('trash-2', ['size' => 14]); ?>
                    <span class="ml-1">Delete</span>
                  </button>
                </div>
              </div>
            </template>

            <!-- Edit link for unknown words -->
            <template x-if="isUnknown">
              <div class="mb-4">
                <button class="button is-info" @click="showEditForm" :disabled="isLoading">
                  <?php echo \Lwt\View\Helper\IconHelper::render('edit', ['size' => 16]); ?>
                  <span class="ml-1">Add with translation</span>
                </button>
              </div>
            </template>

            <!-- Dictionary links -->
            <div class="pt-3" style="border-top: 1px solid #dbdbdb;">
              <p class="is-size-7 has-text-grey mb-2">Lookup:</p>
              <div class="buttons are-small">
                <a :href="getDictUrl('dict1')" target="_blank" class="button is-outlined" rel="noopener">
                  <?php echo \Lwt\View\Helper\IconHelper::render('book-open', ['size' => 14]); ?>
                  <span class="ml-1">Dict 1</span>
                </a>
                <a :href="getDictUrl('dict2')" target="_blank" class="button is-outlined" rel="noopener">
                  <?php echo \Lwt\View\Helper\IconHelper::render('book-open', ['size' => 14]); ?>
                  <span class="ml-1">Dict 2</span>
                </a>
                <a :href="getDictUrl('translator')" target="_blank" class="button is-outlined" rel="noopener">
                  <?php echo \Lwt\View\Helper\IconHelper::render('languages', ['size' => 14]); ?>
                  <span class="ml-1">Translate</span>
                </a>
              </div>
            </div>
          </div>
        </template>

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
                        <?php echo \Lwt\View\Helper\IconHelper::render('copy', ['size' => 12]); ?>
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
                  <?php echo \Lwt\View\Helper\IconHelper::render('save', ['size' => 16]); ?>
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

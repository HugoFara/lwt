<?php declare(strict_types=1);
/**
 * Word Modal View (Bulma + Alpine.js)
 *
 * Displays word information and action buttons in a centered Bulma modal.
 * Works with the wordModal Alpine.js component.
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
    <div class="modal-card" style="max-width: 420px;">
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

        <template x-if="word && !isLoading">
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
                <p class="has-text-grey-dark" x-text="word.translation"></p>
                <template x-if="word.romanization">
                  <p class="is-size-7 has-text-grey" x-text="word.romanization"></p>
                </template>
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
                  <a :href="getEditUrl()" class="button is-info is-outlined">
                    <?php echo \Lwt\View\Helper\IconHelper::render('edit', ['size' => 14]); ?>
                    <span class="ml-1">Edit</span>
                  </a>
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
                <a :href="getEditUrl()" class="button is-info">
                  <?php echo \Lwt\View\Helper\IconHelper::render('edit', ['size' => 16]); ?>
                  <span class="ml-1">Add with translation</span>
                </a>
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
      </section>
    </div>
  </div>
</div>

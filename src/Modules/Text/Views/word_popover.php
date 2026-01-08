<?php declare(strict_types=1);
/**
 * Word Popover View (Alpine.js)
 *
 * Displays word information in a non-blocking popover near the clicked word.
 * Allows users to continue reading while viewing word details.
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
<div x-data="wordPopover" x-cloak>
  <!-- Popover container - positioned absolutely near the target word -->
  <div
    class="word-popover"
    x-show="isOpen"
    :style="getPositionStyle()"
    :class="'word-popover--' + position.placement"
    x-transition:enter="popover-enter"
    x-transition:enter-start="popover-enter-start"
    x-transition:enter-end="popover-enter-end"
    x-transition:leave="popover-leave"
    x-transition:leave-start="popover-leave-start"
    x-transition:leave-end="popover-leave-end"
  >
    <!-- Arrow indicator -->
    <div class="word-popover__arrow" :class="'word-popover__arrow--' + position.placement"></div>

    <!-- Popover content -->
    <div class="word-popover__content">
      <!-- Loading state -->
      <div x-show="isLoading" class="has-text-centered py-2">
        <span class="icon">
          <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
        </span>
      </div>

      <template x-if="word && !isLoading">
        <div>
          <!-- Word text and audio button -->
          <div class="is-flex is-justify-content-space-between is-align-items-center mb-2">
            <span class="is-size-5 has-text-weight-bold" x-text="word.text"></span>
            <button class="button is-small is-rounded is-ghost" @click="speakWord" title="Listen">
              <?php echo \Lwt\Shared\UI\Helpers\IconHelper::render('volume-2', ['size' => 14]); ?>
            </button>
          </div>

          <!-- Translation for known words -->
          <template x-if="!isUnknown && word.translation">
            <div class="mb-2">
              <p class="has-text-grey-dark is-size-7" x-text="word.translation"></p>
              <template x-if="word.romanization">
                <p class="is-size-7 has-text-grey" x-text="word.romanization"></p>
              </template>
            </div>
          </template>

          <!-- Status buttons for known words -->
          <template x-if="!isUnknown">
            <div class="mb-2">
              <div class="buttons are-small mb-0">
                <template x-for="s in [1,2,3,4,5]" :key="s">
                  <button
                    class="button"
                    :class="getStatusButtonClass(s)"
                    :disabled="isLoading"
                    @click="setStatus(s)"
                    x-text="s"
                  ></button>
                </template>
                <button
                  class="button"
                  :class="isCurrentStatus(99) ? 'is-success' : 'is-outlined is-success'"
                  :disabled="isLoading"
                  @click="setStatus(99)"
                >Known</button>
                <button
                  class="button"
                  :class="isCurrentStatus(98) ? 'is-warning' : 'is-outlined is-warning'"
                  :disabled="isLoading"
                  @click="setStatus(98)"
                >Ignore</button>
              </div>
            </div>
          </template>

          <!-- Quick actions for unknown words -->
          <template x-if="isUnknown">
            <div class="mb-2">
              <div class="buttons are-small mb-0">
                <button class="button is-success is-small" :disabled="isLoading" @click="markWellKnown">
                  <?php echo \Lwt\Shared\UI\Helpers\IconHelper::render('check', ['size' => 12]); ?>
                  <span class="ml-1">Known</span>
                </button>
                <button class="button is-warning is-small" :disabled="isLoading" @click="markIgnored">
                  <?php echo \Lwt\Shared\UI\Helpers\IconHelper::render('x', ['size' => 12]); ?>
                  <span class="ml-1">Ignore</span>
                </button>
              </div>
            </div>
          </template>

          <!-- Action row -->
          <div class="is-flex is-justify-content-space-between is-align-items-center pt-2 word-popover__actions">
            <div class="buttons are-small mb-0">
              <!-- Edit button -->
              <button class="button is-info is-outlined is-small" @click="openEditForm" :disabled="isLoading">
                <?php echo \Lwt\Shared\UI\Helpers\IconHelper::render('edit', ['size' => 12]); ?>
                <span class="ml-1" x-text="isUnknown ? 'Add' : 'Edit'"></span>
              </button>
              <!-- Delete button for known words -->
              <template x-if="!isUnknown && word.wordId">
                <button class="button is-danger is-outlined is-small" :disabled="isLoading" @click="deleteWord">
                  <?php echo \Lwt\Shared\UI\Helpers\IconHelper::render('trash-2', ['size' => 12]); ?>
                </button>
              </template>
            </div>

            <!-- Dictionary links -->
            <div class="buttons are-small mb-0">
              <a :href="getDictUrl('dict1')" target="_blank" class="button is-link is-outlined is-small" rel="noopener" title="Dictionary 1">
                D1
              </a>
              <a :href="getDictUrl('dict2')" target="_blank" class="button is-link is-outlined is-small" rel="noopener" title="Dictionary 2">
                D2
              </a>
              <a :href="getDictUrl('translator')" target="_blank" class="button is-link is-outlined is-small" rel="noopener" title="Translate">
                <?php echo \Lwt\Shared\UI\Helpers\IconHelper::render('languages', ['size' => 12]); ?>
              </a>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</div>

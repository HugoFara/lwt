/**
 * Test View - Client-side rendered test interface.
 *
 * Renders the entire test UI using Alpine.js and vanilla JavaScript.
 * No server-side HTML generation - fully reactive SPA-style interface.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import type { TestStoreState, TestConfig } from '../stores/test_store';
import { getTestStore } from '../stores/test_store';
import { ReviewApi, type TableTestWord } from '../../api/review';
import { speechDispatcher } from '../../core/user_interactions';

/**
 * Icon SVG paths (inline to avoid external dependencies)
 */
const ICONS = {
  arrowLeft: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>',
  clock: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
  eye: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
  eyeOff: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>',
  arrowUp: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>',
  arrowDown: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
  skipForward: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 4 15 12 5 20 5 4"/><line x1="19" x2="19" y1="5" y2="19"/></svg>',
  checkCircle: '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
  checkCheck: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 7 17l-5-5"/><path d="m22 10-7.5 7.5L13 16"/></svg>',
  info: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
  table: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18"/><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>',
  volume2: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>',
  bookOpen: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
  languages: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>',
  filePen: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22h6a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v10"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10.4 12.6a2 2 0 1 1 3 3L8 21l-4 1 1-4Z"/></svg>',
};

/**
 * Status colors for buttons
 */
const STATUS_COLORS: Record<number, { bg: string; text: string; border: string }> = {
  1: { bg: '#ff6347', text: 'white', border: '#ff6347' },
  2: { bg: '#ffa500', text: 'white', border: '#ffa500' },
  3: { bg: '#ffff00', text: 'black', border: '#ffff00' },
  4: { bg: '#90ee90', text: 'black', border: '#90ee90' },
  5: { bg: '#32cd32', text: 'white', border: '#32cd32' },
};

/**
 * Test types configuration
 */
const TEST_TYPES = [
  { id: 1, label: '..[L2]..', title: 'Show sentence with term, guess translation' },
  { id: 2, label: '..[L1]..', title: 'Show sentence with translation, guess term' },
  { id: 3, label: '..[-]..', title: 'Show sentence with hidden term, guess both' },
  { id: 4, label: '[L2]', title: 'Show term only, guess translation' },
  { id: 5, label: '[L1]', title: 'Show translation only, guess term' },
];

/**
 * Render the complete test interface.
 */
export function renderTestApp(container: HTMLElement, config: TestConfig): void {
  // Build the complete HTML structure
  container.innerHTML = buildTestAppHTML(config);

  // Initialize Alpine on the container
  Alpine.initTree(container);
}

/**
 * Build the complete test app HTML.
 */
function buildTestAppHTML(config: TestConfig): string {
  const langName = escapeHtml(config.langSettings?.name || 'L2');

  return `
    <div x-data="testApp" class="test-page" x-cloak>
      ${buildTestToolbar(langName)}
      ${buildProgressBar()}
      ${buildMainContent()}
      ${buildWordModal()}
    </div>
    ${buildStyles()}
  `;
}

/**
 * Build test toolbar HTML (below main navbar).
 */
function buildTestToolbar(langName: string): string {
  return `
    <nav class="navbar is-white has-shadow" role="navigation" aria-label="test toolbar">
      <div class="navbar-brand">
        <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false"
           @click="navbarOpen = !navbarOpen" :class="{ 'is-active': navbarOpen }">
          <span aria-hidden="true"></span>
          <span aria-hidden="true"></span>
          <span aria-hidden="true"></span>
        </a>
      </div>

      <div class="navbar-menu" :class="{ 'is-active': navbarOpen }">
        <div class="navbar-start">
          <div class="navbar-item">
            <span class="has-text-weight-semibold">Test: <span x-text="store.title"></span></span>
          </div>
        </div>

        <div class="navbar-end">
          <!-- Test type buttons -->
          <div class="navbar-item" x-show="!store.isTableMode">
            <div class="buttons are-small">
              ${TEST_TYPES.map(t => `
                <button class="button"
                        :class="{ 'is-primary': store.testType === ${t.id} }"
                        @click="switchTestType(${t.id})"
                        title="${escapeHtml(t.title)}">
                  ${t.label.replace('L2', langName)}
                </button>
              `).join('')}
            </div>
          </div>

          <!-- Table mode button -->
          <div class="navbar-item">
            <button class="button is-small"
                    :class="{ 'is-info': store.isTableMode }"
                    @click="switchToTable">
              ${ICONS.table}
              <span class="ml-1 is-hidden-mobile">Table</span>
            </button>
          </div>

          <!-- Read aloud toggle -->
          <div class="navbar-item">
            <label class="checkbox is-size-7">
              <input type="checkbox" x-model="store.readAloudEnabled" @change="saveReadAloudSetting">
              ${ICONS.volume2}
              <span class="ml-1 is-hidden-mobile">Read aloud</span>
            </label>
          </div>
        </div>
      </div>
    </nav>
  `;
}

/**
 * Build progress bar HTML.
 */
function buildProgressBar(): string {
  return `
    <div class="box py-2 px-4 mb-0 test-progress-section">
      <div class="level is-mobile">
        <div class="level-left">
          <div class="level-item">
            ${ICONS.clock}
            <span class="ml-2" x-text="store.timer.elapsed">00:00</span>
          </div>
        </div>

        <div class="level-item is-flex-grow-1 mx-4">
          <div class="test-progress">
            <div class="test-progress-remaining"
                 :style="'width: ' + progressPercent.remaining + '%'"></div>
            <div class="test-progress-wrong"
                 :style="'width: ' + progressPercent.wrong + '%'"></div>
            <div class="test-progress-correct"
                 :style="'width: ' + progressPercent.correct + '%'"></div>
          </div>
        </div>

        <div class="level-right">
          <div class="level-item">
            <span class="tag is-medium" x-text="store.progress.total"></span>
            <span class="mx-1">=</span>
            <span class="tag is-medium is-light" x-text="store.progress.remaining"></span>
            <span class="mx-1">+</span>
            <span class="tag is-medium is-danger is-light" x-text="store.progress.wrong"></span>
            <span class="mx-1">+</span>
            <span class="tag is-medium is-success is-light" x-text="store.progress.correct"></span>
          </div>
        </div>
      </div>
    </div>
  `;
}

/**
 * Build main content area HTML.
 */
function buildMainContent(): string {
  return `
    <!-- Loading state -->
    <div x-show="store.isLoading && !store.isInitialized" class="has-text-centered py-6">
      <div class="loading-spinner"></div>
      <p class="mt-4 has-text-grey">Loading test...</p>
    </div>

    <!-- Error state -->
    <template x-if="store.error">
      <div class="notification is-danger mx-4 mt-4">
        <button class="delete" @click="store.error = null"></button>
        <p x-text="store.error"></p>
      </div>
    </template>

    <!-- Word test content -->
    <div x-show="!store.isTableMode && !store.error && store.isInitialized" class="test-content p-4">
      ${buildFinishedMessage()}
      ${buildWordTestArea()}
    </div>

    <!-- Table test content -->
    <div x-show="store.isTableMode && !store.error && store.isInitialized" class="table-test-content p-4">
      <div x-data="tableTest" x-init="init()">
        ${buildTableTest()}
      </div>
    </div>
  `;
}

/**
 * Build finished message HTML.
 */
function buildFinishedMessage(): string {
  return `
    <div x-show="store.isFinished" class="has-text-centered py-6">
      <div class="notification is-success is-light">
        <p class="is-size-4 mb-4">${ICONS.checkCircle}</p>
        <p class="is-size-5 has-text-weight-bold"
           x-text="store.progress.total > 0 ? 'Nothing more to test here!' : 'Nothing to test here!'"></p>
        <p class="mt-3" x-show="store.tomorrowCount > 0">
          Tomorrow you'll find here <strong x-text="store.tomorrowCount"></strong>
          test<span x-show="store.tomorrowCount !== 1">s</span>!
        </p>
        <div class="buttons is-centered mt-5">
          <a href="/texts" class="button is-primary">Back to Texts</a>
        </div>
      </div>
    </div>
  `;
}

/**
 * Build word test area HTML.
 */
function buildWordTestArea(): string {
  return `
    <div x-show="!store.isFinished && store.currentWord" class="test-word-area">
      <!-- Loading next word -->
      <div x-show="store.isLoading" class="has-text-centered py-4">
        <div class="loading-spinner"></div>
      </div>

      <div x-show="!store.isLoading" class="has-text-centered">
        <!-- Term display -->
        <div class="test-term-display mb-5"
             :style="'font-size: ' + store.langSettings.textSize + '%; direction: ' + (store.langSettings.rtl ? 'rtl' : 'ltr')"
             x-html="store.currentWord?.group || ''">
        </div>

        <!-- Solution (hidden until revealed) -->
        <div x-show="store.answerRevealed" class="notification is-info is-light mb-5">
          <p class="is-size-4" x-text="store.currentWord?.solution || ''"></p>
        </div>

        <!-- Action buttons -->
        <div class="buttons is-centered mb-5">
          <button x-show="!store.answerRevealed"
                  class="button is-primary is-large"
                  @click="revealAnswer">
            ${ICONS.eye}
            <span class="ml-2">Show Answer</span>
            <span class="ml-2 is-size-7">(Space)</span>
          </button>
        </div>

        <!-- After answer revealed -->
        <div x-show="store.answerRevealed" class="mb-5">
          <div class="buttons is-centered">
            <button class="button is-danger" @click="decrementStatus" title="Wrong (Arrow Down)">
              ${ICONS.arrowDown}
              <span class="ml-1">Wrong</span>
            </button>
            <button class="button is-success" @click="incrementStatus" title="Correct (Arrow Up)">
              ${ICONS.arrowUp}
              <span class="ml-1">Correct</span>
            </button>
            <button class="button" @click="skipWord" title="Skip (Escape)">
              ${ICONS.skipForward}
              <span class="ml-1">Skip</span>
            </button>
          </div>
        </div>

        <!-- Status buttons -->
        <div x-show="store.answerRevealed" class="mb-5">
          <p class="is-size-7 has-text-grey mb-2">Set status directly:</p>
          <div class="buttons is-centered are-small">
            ${[1, 2, 3, 4, 5].map(s => `
              <button class="button status-btn"
                      :class="{ 'status-${s}': store.currentWord?.status === ${s} }"
                      @click="setStatus(${s})">${s}</button>
            `).join('')}
            <button class="button" @click="setStatus(98)" title="Ignore (I)">
              ${ICONS.eyeOff}
              <span class="ml-1">Ign</span>
            </button>
            <button class="button" @click="setStatus(99)" title="Well Known (W)">
              ${ICONS.checkCheck}
              <span class="ml-1">WKn</span>
            </button>
          </div>
        </div>

        <!-- Details button -->
        <div x-show="store.answerRevealed">
          <button class="button is-text is-small" @click="store.openModal()" title="Edit (E)">
            ${ICONS.info}
            <span class="ml-1">Details / Edit</span>
          </button>
        </div>
      </div>
    </div>
  `;
}

/**
 * Build table test HTML.
 */
function buildTableTest(): string {
  return `
    <!-- Loading -->
    <div x-show="isLoading" class="has-text-centered py-6">
      <div class="loading-spinner"></div>
      <p class="mt-4 has-text-grey">Loading words...</p>
    </div>

    <!-- No words -->
    <div x-show="!isLoading && words.length === 0" class="notification is-info is-light">
      <p>No words available for table testing.</p>
    </div>

    <!-- Table -->
    <div x-show="!isLoading && words.length > 0">
      <!-- Column toggles -->
      <div class="field is-grouped is-grouped-multiline mb-4">
        <div class="control">
          <label class="checkbox"><input type="checkbox" x-model="columns.edit" @change="saveColumnSettings"> Edit</label>
        </div>
        <div class="control">
          <label class="checkbox"><input type="checkbox" x-model="columns.status" @change="saveColumnSettings"> Status</label>
        </div>
        <div class="control">
          <label class="checkbox"><input type="checkbox" x-model="columns.term" @change="saveColumnSettings"> Term</label>
        </div>
        <div class="control">
          <label class="checkbox"><input type="checkbox" x-model="columns.trans" @change="saveColumnSettings"> Translation</label>
        </div>
        <div class="control">
          <label class="checkbox"><input type="checkbox" x-model="columns.rom" @change="saveColumnSettings"> Romanization</label>
        </div>
        <div class="control">
          <label class="checkbox"><input type="checkbox" x-model="columns.sentence" @change="saveColumnSettings"> Sentence</label>
        </div>
        <div class="control ml-4">
          <label class="checkbox"><input type="checkbox" x-model="hideTermContent" @change="saveColumnSettings"> Hide terms</label>
        </div>
        <div class="control">
          <label class="checkbox"><input type="checkbox" x-model="hideTransContent" @change="saveColumnSettings"> Hide translations</label>
        </div>
      </div>

      <div class="table-container">
        <table class="table is-striped is-hoverable is-fullwidth">
          <thead>
            <tr>
              <th x-show="columns.edit" class="has-text-centered" style="width: 50px;">Ed</th>
              <th x-show="columns.status" class="has-text-centered" style="width: 180px;">Status</th>
              <th x-show="columns.term" class="has-text-centered">Term</th>
              <th x-show="columns.trans" class="has-text-centered">Translation</th>
              <th x-show="columns.rom" class="has-text-centered">Rom.</th>
              <th x-show="columns.sentence">Sentence</th>
            </tr>
          </thead>
          <tbody>
            <template x-for="word in words" :key="word.id">
              <tr>
                <td x-show="columns.edit" class="has-text-centered">
                  <a :href="'/word/edit-term?wid=' + word.id" class="button is-small is-text">
                    ${ICONS.filePen}
                  </a>
                </td>
                <td x-show="columns.status" class="has-text-centered">
                  <div class="buttons are-small is-centered">
                    <template x-for="s in [1, 2, 3, 4, 5]" :key="s">
                      <button class="button status-btn"
                              :class="{ ['status-' + s]: word.status === s }"
                              @click="setWordStatus(word.id, s)"
                              x-text="s"></button>
                    </template>
                  </div>
                </td>
                <td x-show="columns.term"
                    class="has-text-centered"
                    :class="{ 'cell-hidden': hideTermContent && !revealedTerms[word.id] }"
                    @click="revealTerm(word.id)">
                  <span x-text="word.text"></span>
                </td>
                <td x-show="columns.trans"
                    class="has-text-centered"
                    :class="{ 'cell-hidden': hideTransContent && !revealedTrans[word.id] }"
                    @click="revealTrans(word.id)">
                  <span x-text="word.translation"></span>
                </td>
                <td x-show="columns.rom" class="has-text-centered" x-text="word.romanization"></td>
                <td x-show="columns.sentence" x-html="word.sentenceHtml"></td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  `;
}

/**
 * Build word modal HTML.
 */
function buildWordModal(): string {
  return `
    <div class="modal" :class="{ 'is-active': store.isModalOpen }">
      <div class="modal-background" @click="store.closeModal()"></div>
      <div class="modal-card" style="max-width: 500px;">
        <header class="modal-card-head py-3">
          <p class="modal-card-title is-size-5">Word Details</p>
          <button class="delete" aria-label="close" @click="store.closeModal()"></button>
        </header>
        <section class="modal-card-body">
          <template x-if="store.currentWord">
            <div>
              <div class="is-flex is-justify-content-space-between is-align-items-center mb-4">
                <span class="is-size-3 has-text-weight-bold"
                      :style="store.langSettings.rtl ? 'direction: rtl' : ''"
                      x-text="store.currentWord.text"></span>
                <button class="button is-small is-rounded" @click="speakWord" title="Listen">
                  ${ICONS.volume2}
                </button>
              </div>

              <div class="mb-4" x-show="store.currentWord.solution">
                <p class="has-text-grey-dark is-size-5" x-text="store.currentWord.solution"></p>
              </div>

              <div class="mb-4">
                <p class="is-size-7 has-text-grey mb-2">Look up in dictionary:</p>
                <div class="buttons">
                  <a :href="store.getDictUrl('dict1')" target="_blank" class="button is-outlined" rel="noopener">
                    ${ICONS.bookOpen} <span class="ml-1">Dictionary 1</span>
                  </a>
                  <a :href="store.getDictUrl('dict2')" target="_blank" class="button is-outlined" rel="noopener">
                    ${ICONS.bookOpen} <span class="ml-1">Dictionary 2</span>
                  </a>
                  <a :href="store.getDictUrl('translator')" target="_blank" class="button is-outlined" rel="noopener">
                    ${ICONS.languages} <span class="ml-1">Translate</span>
                  </a>
                </div>
              </div>
            </div>
          </template>
        </section>
        <footer class="modal-card-foot">
          <a :href="store.getEditUrl()" class="button is-info">
            ${ICONS.filePen} <span class="ml-1">Edit Term</span>
          </a>
          <button class="button" @click="store.closeModal()">Close</button>
        </footer>
      </div>
    </div>
  `;
}

/**
 * Build CSS styles.
 */
function buildStyles(): string {
  return `
    <style>
      .test-page {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }

      .test-progress-section {
        border-radius: 0;
      }

      .test-content, .table-test-content {
        max-width: 900px;
        margin: 0 auto;
        flex: 1;
        width: 100%;
      }

      .test-word-area {
        min-height: 400px;
        display: flex;
        flex-direction: column;
        justify-content: center;
      }

      .test-term-display {
        min-height: 100px;
        line-height: 1.6;
      }

      .test-progress {
        display: flex;
        height: 8px;
        width: 100%;
        border-radius: 4px;
        overflow: hidden;
        background: #e0e0e0;
      }

      .test-progress-remaining { background-color: #808080; transition: width 0.3s ease; }
      .test-progress-wrong { background-color: #ff6347; transition: width 0.3s ease; }
      .test-progress-correct { background-color: #32cd32; transition: width 0.3s ease; }

      .status-btn.status-1 { background-color: #ff6347 !important; color: white !important; border-color: #ff6347 !important; }
      .status-btn.status-2 { background-color: #ffa500 !important; color: white !important; border-color: #ffa500 !important; }
      .status-btn.status-3 { background-color: #ffff00 !important; color: black !important; border-color: #ffff00 !important; }
      .status-btn.status-4 { background-color: #90ee90 !important; color: black !important; border-color: #90ee90 !important; }
      .status-btn.status-5 { background-color: #32cd32 !important; color: white !important; border-color: #32cd32 !important; }

      .word-test { font-weight: bold; text-decoration: underline; }
      .word-test-hidden { background-color: #e0e0e0; padding: 0 0.5em; border-radius: 3px; }

      .cell-hidden {
        color: transparent !important;
        background-color: #f0f0f0 !important;
        cursor: pointer;
        user-select: none;
      }
      .cell-hidden:hover { background-color: #e0e0e0 !important; }
      .cell-hidden * { color: transparent !important; }

      .loading-spinner {
        width: 40px;
        height: 40px;
        margin: 0 auto;
        border: 3px solid #dbdbdb;
        border-top-color: #3273dc;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
      }

      @keyframes spin { to { transform: rotate(360deg); } }

      [x-cloak] { display: none !important; }

      @media screen and (max-width: 768px) {
        .test-progress-section .level { flex-wrap: wrap; }
        .test-progress-section .level-item:not(:last-child) { margin-bottom: 0.5rem; }
        .navbar-item .buttons { flex-wrap: wrap; justify-content: center; }
      }
    </style>
  `;
}

/**
 * Escape HTML entities.
 */
function escapeHtml(str: string): string {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

/**
 * Initialize the test application.
 */
export function initTestApp(): void {
  const container = document.getElementById('test-app');
  const configEl = document.getElementById('test-config');

  if (!container || !configEl) return;

  try {
    const config: TestConfig = JSON.parse(configEl.textContent || '{}');

    if (config.error) {
      container.innerHTML = `<div class="notification is-danger m-4">${escapeHtml(config.error)}</div>`;
      return;
    }

    // Register the Alpine components
    registerTestAppComponent(config);
    registerTableTestComponent();

    // Render the app
    renderTestApp(container, config);

  } catch (err) {
    console.error('Error initializing test app:', err);
    container.innerHTML = '<div class="notification is-danger m-4">Failed to initialize test</div>';
  }
}

/**
 * Register the main test app Alpine component.
 */
function registerTestAppComponent(config: TestConfig): void {
  Alpine.data('testApp', () => ({
    navbarOpen: false,

    get store(): TestStoreState {
      return getTestStore();
    },

    get progressPercent() {
      const total = this.store.progress.total || 1;
      return {
        remaining: (this.store.progress.remaining / total) * 100,
        wrong: (this.store.progress.wrong / total) * 100,
        correct: (this.store.progress.correct / total) * 100
      };
    },

    async init() {
      this.store.configure(config);

      // Set up keyboard handler
      document.addEventListener('keydown', (e) => this.handleKeydown(e));

      // Start fetching first word if not table mode
      if (!config.isTableMode) {
        await this.store.nextWord();
      }
    },

    revealAnswer() {
      this.store.revealAnswer();
      if (this.store.readAloudEnabled && this.store.currentWord) {
        this.speakWord();
      }
    },

    async incrementStatus() {
      await this.store.incrementStatus();
    },

    async decrementStatus() {
      await this.store.decrementStatus();
    },

    async setStatus(status: number) {
      await this.store.updateStatus(status);
    },

    async skipWord() {
      await this.store.skipWord();
    },

    switchTestType(type: number) {
      const url = new URL(window.location.href);
      url.searchParams.set('type', String(type));
      window.location.href = url.toString();
    },

    switchToTable() {
      const url = new URL(window.location.href);
      if (this.store.isTableMode) {
        url.searchParams.delete('type');
      } else {
        url.searchParams.set('type', 'table');
      }
      window.location.href = url.toString();
    },

    speakWord() {
      if (this.store.currentWord && this.store.langSettings.langCode) {
        speechDispatcher(this.store.currentWord.text, this.store.langId);
      }
    },

    saveReadAloudSetting() {
      localStorage.setItem('lwt-test-read-aloud', String(this.store.readAloudEnabled));
    },

    handleKeydown(e: KeyboardEvent) {
      if (this.store.isModalOpen) return;
      if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) return;
      if (this.store.isTableMode || this.store.isFinished) return;

      switch (e.key) {
        case ' ':
          e.preventDefault();
          if (!this.store.answerRevealed) this.revealAnswer();
          break;
        case 'Escape':
          e.preventDefault();
          if (this.store.currentWord) this.skipWord();
          break;
        case 'ArrowUp':
          e.preventDefault();
          if (this.store.answerRevealed) this.incrementStatus();
          break;
        case 'ArrowDown':
          e.preventDefault();
          if (this.store.answerRevealed) this.decrementStatus();
          break;
        case 'i': case 'I':
          e.preventDefault();
          if (this.store.currentWord) this.setStatus(98);
          break;
        case 'w': case 'W':
          e.preventDefault();
          if (this.store.currentWord) this.setStatus(99);
          break;
        case 'e': case 'E':
          e.preventDefault();
          if (this.store.currentWord) this.store.openModal();
          break;
        case '1': case '2': case '3': case '4': case '5':
          e.preventDefault();
          if (this.store.answerRevealed) this.setStatus(parseInt(e.key, 10));
          break;
      }
    }
  }));
}

/**
 * Register the table test Alpine component.
 */
function registerTableTestComponent(): void {
  Alpine.data('tableTest', () => ({
    words: [] as TableTestWord[],
    langSettings: null as any,
    columns: { edit: true, status: true, term: true, trans: true, rom: false, sentence: true },
    hideTermContent: false,
    hideTransContent: false,
    revealedTerms: {} as Record<number, boolean>,
    revealedTrans: {} as Record<number, boolean>,
    isLoading: false,

    async init() {
      this.loadColumnSettings();
      await this.loadWords();
    },

    async loadWords() {
      this.isLoading = true;
      const store = getTestStore();

      try {
        const response = await ReviewApi.getTableWords(store.testKey, store.selection);
        if (response.data) {
          this.words = response.data.words;
          this.langSettings = response.data.langSettings;
        }
      } catch (err) {
        console.error('Error loading words:', err);
      }

      this.isLoading = false;
    },

    async setWordStatus(wordId: number, status: number) {
      try {
        const response = await ReviewApi.updateStatus(wordId, status);
        if (response.data?.status !== undefined) {
          const word = this.words.find((w: TableTestWord) => w.id === wordId);
          if (word) word.status = response.data.status;
        }
      } catch (err) {
        console.error('Error updating status:', err);
      }
    },

    revealTerm(wordId: number) {
      if (this.hideTermContent) this.revealedTerms[wordId] = true;
    },

    revealTrans(wordId: number) {
      if (this.hideTransContent) this.revealedTrans[wordId] = true;
    },

    saveColumnSettings() {
      localStorage.setItem('lwt-table-test-columns', JSON.stringify({
        columns: this.columns,
        hideTermContent: this.hideTermContent,
        hideTransContent: this.hideTransContent
      }));
    },

    loadColumnSettings() {
      const saved = localStorage.getItem('lwt-table-test-columns');
      if (saved) {
        try {
          const s = JSON.parse(saved);
          if (s.columns) this.columns = { ...this.columns, ...s.columns };
          if (typeof s.hideTermContent === 'boolean') this.hideTermContent = s.hideTermContent;
          if (typeof s.hideTransContent === 'boolean') this.hideTransContent = s.hideTransContent;
        } catch (e) { /* ignore */ }
      }
    }
  }));
}

// Auto-initialize after Alpine has initialized the DOM
// We use DOMContentLoaded + check because initTestApp() needs to inject HTML and then call Alpine.initTree()
document.addEventListener('DOMContentLoaded', () => {
  // Only init if Alpine is available and we're on the test page
  const container = document.getElementById('test-app');
  const configEl = document.getElementById('test-config');
  if (container && configEl && window.Alpine) {
    initTestApp();
  }
});

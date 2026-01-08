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
import type { TestStoreState, TestConfig, LangSettings } from '../stores/test_store';
import { getTestStore } from '../stores/test_store';
import { ReviewApi, type TableTestWord } from '@modules/review/api/review_api';
import { speechDispatcher } from '@shared/utils/user_interactions';
import { saveSetting } from '@shared/utils/ajax_utilities';

/**
 * Test types configuration
 */
const TEST_TYPES = [
  { id: 1, label: 'Sentence → Translation', title: 'Show sentence with term highlighted, guess translation' },
  { id: 2, label: 'Sentence → Term', title: 'Show sentence with translation, guess the term' },
  { id: 3, label: 'Sentence → Both', title: 'Show sentence with hidden term, guess both term and translation' },
  { id: 4, label: 'Term → Translation', title: 'Show term only, guess translation' },
  { id: 5, label: 'Translation → Term', title: 'Show translation only, guess the term' },
];

/**
 * Render the complete test interface.
 */
export function renderTestApp(container: HTMLElement): void {
  // Build the complete HTML structure
  container.innerHTML = buildTestAppHTML();

  // Initialize Alpine on the container
  Alpine.initTree(container);
}

/**
 * Build the complete test app HTML.
 */
function buildTestAppHTML(): string {
  return `
    <div x-data="testApp" class="test-page" x-cloak>
      ${buildTestToolbar()}
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
function buildTestToolbar(): string {
  return `
    <div class="box py-2 px-4 mb-0" style="border-radius: 0;">
      <div class="level is-mobile">
        <div class="level-left">
          <div class="level-item">
            <strong>Test: <span x-text="store.title"></span></strong>
          </div>
        </div>
        <div class="level-right">
          <div class="level-item">
            <div class="field is-grouped is-grouped-multiline">
              <!-- Test type buttons -->
              <div class="control">
                <div class="buttons are-small">
                  ${TEST_TYPES.map(t => `
                    <button class="button"
                            :class="{ 'is-primary': store.testType === ${t.id} && !store.isTableMode }"
                            @click="switchTestType(${t.id})"
                            title="${escapeHtml(t.title)}">
                      ${escapeHtml(t.label)}
                    </button>
                  `).join('')}
                  <button class="button"
                          :class="{ 'is-primary': store.isTableMode }"
                          @click="switchToTable">
                    Table
                  </button>
                </div>
              </div>
              <div class="control">
                <label class="checkbox">
                  <input type="checkbox" x-model="store.readAloudEnabled" @change="saveReadAloudSetting">
                  Read aloud
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
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
            <span>Time: </span>
            <span x-text="store.timer.elapsed">00:00</span>
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
            <span class="tag is-medium is-light" title="Remaining words / Total words">
              <span class="has-text-weight-semibold" title="Remaining" x-text="store.progress.remaining"></span>
              <span class="mx-1 has-text-grey">/</span>
              <span class="has-text-grey" title="Total" x-text="store.progress.total"></span>
            </span>
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
            Show Answer (Space)
          </button>
        </div>

        <!-- After answer revealed -->
        <div x-show="store.answerRevealed" class="mb-5">
          <div class="buttons is-centered">
            <button class="button is-danger" @click="decrementStatus" title="Arrow Down">
              Wrong
            </button>
            <button class="button is-success" @click="incrementStatus" title="Arrow Up">
              Correct
            </button>
            <button class="button" @click="skipWord" title="Escape">
              Skip
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
            <button class="button" @click="setStatus(98)" title="Press I">
              Ignore
            </button>
            <button class="button" @click="setStatus(99)" title="Press W">
              Well Known
            </button>
          </div>
        </div>

        <!-- Details button -->
        <div x-show="store.answerRevealed">
          <button class="button is-text is-small" @click="store.openModal()" title="Press E">
            Details / Edit
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
      <!-- Context annotation toggles (affects sentence mode tests) -->
      <div class="field is-grouped is-grouped-multiline mb-4">
        <div class="control">
          <span class="has-text-grey-dark is-size-7 mr-2">Sentence context annotations:</span>
        </div>
        <div class="control">
          <label class="checkbox"><input type="checkbox" x-model="contextAnnotations.rom" @change="saveContextAnnotationSettings"> Romanization</label>
        </div>
        <div class="control">
          <label class="checkbox"><input type="checkbox" x-model="contextAnnotations.trans" @change="saveContextAnnotationSettings"> Translation</label>
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
                    Edit
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
                <button class="button is-small" @click="speakWord">
                  Listen
                </button>
              </div>

              <div class="mb-4" x-show="store.currentWord.solution">
                <p class="has-text-grey-dark is-size-5" x-text="store.currentWord.solution"></p>
              </div>

              <div class="mb-4">
                <p class="is-size-7 has-text-grey mb-2">Look up in dictionary:</p>
                <div class="buttons">
                  <a :href="store.getDictUrl('dict1')" target="_blank" class="button is-outlined" rel="noopener">
                    Dictionary 1
                  </a>
                  <a :href="store.getDictUrl('dict2')" target="_blank" class="button is-outlined" rel="noopener">
                    Dictionary 2
                  </a>
                  <a :href="store.getDictUrl('translator')" target="_blank" class="button is-outlined" rel="noopener">
                    Translate
                  </a>
                </div>
              </div>
            </div>
          </template>
        </section>
        <footer class="modal-card-foot">
          <a :href="store.getEditUrl()" class="button is-info">
            Edit Term
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

      /* Context annotation styles */
      .annotated-sentence { line-height: 2.5; }
      .annotated-sentence ruby { ruby-position: over; }
      .annotated-sentence ruby rt {
        font-size: 0.65em;
        color: #666;
        font-weight: normal;
      }
      .annotated-sentence ruby .context-trans {
        color: #888;
        font-style: italic;
      }
      .context-word {
        display: inline-block;
        text-align: center;
      }

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
    renderTestApp(container);

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
    langSettings: null as LangSettings | null,
    columns: { edit: true, status: true, term: true, trans: true, rom: false, sentence: true },
    hideTermContent: false,
    hideTransContent: false,
    contextAnnotations: { rom: false, trans: false },
    revealedTerms: {} as Record<number, boolean>,
    revealedTrans: {} as Record<number, boolean>,
    isLoading: false,

    async init() {
      this.loadColumnSettings();
      this.loadContextAnnotationSettings();
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
        } catch { /* ignore */ }
      }
    },

    saveContextAnnotationSettings() {
      // Save to server via AJAX
      saveSetting('currenttabletestsetting7', this.contextAnnotations.rom ? '1' : '0');
      saveSetting('currenttabletestsetting8', this.contextAnnotations.trans ? '1' : '0');
      // Also save to localStorage for quick reload
      localStorage.setItem('lwt-context-annotations', JSON.stringify(this.contextAnnotations));
    },

    loadContextAnnotationSettings() {
      const saved = localStorage.getItem('lwt-context-annotations');
      if (saved) {
        try {
          const s = JSON.parse(saved);
          if (typeof s.rom === 'boolean') this.contextAnnotations.rom = s.rom;
          if (typeof s.trans === 'boolean') this.contextAnnotations.trans = s.trans;
        } catch { /* ignore */ }
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

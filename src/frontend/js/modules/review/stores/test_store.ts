/**
 * Test Store - Alpine.js store for vocabulary testing state management.
 *
 * Provides centralized state management for the test interface including
 * current word, progress tracking, timer, and UI state.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { ReviewApi } from '@modules/review/api/review_api';

/**
 * Language settings for the test.
 */
export interface LangSettings {
  name: string;
  dict1Uri: string;
  dict2Uri: string;
  translateUri: string;
  textSize: number;
  rtl: boolean;
  langCode: string;
}

/**
 * Current word being tested.
 */
export interface TestWord {
  wordId: number;
  text: string;
  translation: string;
  romanization: string;
  status: number;
  sentence: string;
  solution: string;
  group: string;
}

/**
 * Test progress tracking.
 */
export interface TestProgress {
  total: number;
  remaining: number;
  wrong: number;
  correct: number;
}

/**
 * Timer state.
 */
export interface TestTimer {
  startTime: number;
  serverTime: number;
  elapsed: string;
  intervalId: number | null;
}

/**
 * Test configuration from server.
 */
export interface TestConfig {
  testKey: string;
  selection: string;
  testType: number;
  isTableMode: boolean;
  wordMode: boolean;
  langId: number;
  error?: string;
  wordRegex: string;
  langSettings: LangSettings;
  progress: TestProgress;
  timer: {
    startTime: number;
    serverTime: number;
  };
  title: string;
  property: string;
}

/**
 * Test store state interface.
 */
export interface TestStoreState {
  // Test configuration
  testKey: string;
  selection: string;
  testType: number;
  isTableMode: boolean;
  wordMode: boolean;
  langId: number;
  wordRegex: string;
  property: string;
  title: string;

  // Language settings
  langSettings: LangSettings;

  // Current word being tested
  currentWord: TestWord | null;

  // Progress tracking
  progress: TestProgress;

  // Timer
  timer: TestTimer;

  // UI state
  isLoading: boolean;
  isFinished: boolean;
  answerRevealed: boolean;
  isModalOpen: boolean;
  readAloudEnabled: boolean;
  tomorrowCount: number;
  error: string | null;
  isInitialized: boolean;

  // Methods
  configure(config: TestConfig): void;
  nextWord(): Promise<void>;
  revealAnswer(): void;
  updateStatus(status: number, isCorrect?: boolean): Promise<void>;
  incrementStatus(): Promise<void>;
  decrementStatus(): Promise<void>;
  skipWord(): Promise<void>;
  startTimer(): void;
  stopTimer(): void;
  formatElapsed(seconds: number): string;
  getDictUrl(which: 'dict1' | 'dict2' | 'translator'): string;
  getEditUrl(): string;
  openModal(): void;
  closeModal(): void;
  playSound(correct: boolean): void;
}

/**
 * Calculate new status based on current status and change direction.
 */
function calculateNewStatus(currentStatus: number, change: number): number {
  let newStatus = currentStatus + change;

  // Clamp to valid range (1-5)
  if (newStatus < 1) newStatus = 1;
  if (newStatus > 5) newStatus = 5;

  return newStatus;
}

/**
 * Create the test store data object.
 */
function createTestStore(): TestStoreState {
  return {
    // Test configuration
    testKey: '',
    selection: '',
    testType: 1,
    isTableMode: false,
    wordMode: false,
    langId: 0,
    wordRegex: '',
    property: '',
    title: '',

    // Language settings
    langSettings: {
      name: '',
      dict1Uri: '',
      dict2Uri: '',
      translateUri: '',
      textSize: 100,
      rtl: false,
      langCode: ''
    },

    // Current word being tested
    currentWord: null,

    // Progress tracking
    progress: {
      total: 0,
      remaining: 0,
      wrong: 0,
      correct: 0
    },

    // Timer
    timer: {
      startTime: 0,
      serverTime: 0,
      elapsed: '00:00',
      intervalId: null
    },

    // UI state
    isLoading: false,
    isFinished: false,
    answerRevealed: false,
    isModalOpen: false,
    readAloudEnabled: false,
    tomorrowCount: 0,
    error: null,
    isInitialized: false,

    /**
     * Configure the store with settings from server.
     * Note: Named 'configure' instead of 'init' because Alpine auto-calls init() on stores.
     */
    configure(config: TestConfig): void {
      this.testKey = config.testKey;
      this.selection = config.selection;
      this.testType = config.testType;
      this.isTableMode = config.isTableMode;
      this.wordMode = config.wordMode;
      this.langId = config.langId;
      this.wordRegex = config.wordRegex;
      this.property = config.property;
      this.title = config.title;
      this.langSettings = config.langSettings;
      this.progress = { ...config.progress };
      this.timer.startTime = config.timer.startTime;
      this.timer.serverTime = config.timer.serverTime;

      // Load read aloud preference from localStorage
      const savedReadAloud = localStorage.getItem('lwt-test-read-aloud');
      this.readAloudEnabled = savedReadAloud === 'true';

      this.isInitialized = true;
      this.startTimer();
    },

    /**
     * Fetch and display the next word.
     */
    async nextWord(): Promise<void> {
      if (this.isLoading) return;

      this.isLoading = true;
      this.answerRevealed = false;
      this.currentWord = null;
      this.error = null;

      try {
        const response = await ReviewApi.getNextWord({
          testKey: this.testKey,
          selection: this.selection,
          wordMode: this.wordMode,
          lgId: this.langId,
          wordRegex: this.wordRegex,
          type: this.testType
        });

        if (response.error) {
          this.error = response.error;
          this.isLoading = false;
          return;
        }

        if (!response.data || response.data.term_id === 0) {
          // No more words to test
          this.isFinished = true;
          this.stopTimer();

          // Fetch tomorrow count
          const tomorrowResponse = await ReviewApi.getTomorrowCount(
            this.testKey,
            this.selection
          );
          if (tomorrowResponse.data?.count) {
            this.tomorrowCount = tomorrowResponse.data.count;
          }
        } else {
          const data = response.data;
          this.currentWord = {
            wordId: typeof data.term_id === 'string'
              ? parseInt(data.term_id, 10)
              : data.term_id,
            text: data.term_text,
            translation: '', // Will be revealed with answer
            romanization: '',
            status: 1,
            sentence: '',
            solution: data.solution || '',
            group: data.group
          };
        }
      } catch (err) {
        console.error('Error fetching next word:', err);
        this.error = 'Failed to load next word';
      }

      this.isLoading = false;
    },

    /**
     * Reveal the answer for the current word.
     */
    revealAnswer(): void {
      if (this.answerRevealed || !this.currentWord) return;
      this.answerRevealed = true;
    },

    /**
     * Update the status of the current word.
     *
     * @param status    New status value (1-5)
     * @param isCorrect Whether the user answered correctly (true=knew it, false=didn't know)
     */
    async updateStatus(status: number, isCorrect: boolean = true): Promise<void> {
      if (!this.currentWord || this.isLoading) return;

      this.isLoading = true;

      try {
        const response = await ReviewApi.updateStatus(
          this.currentWord.wordId,
          status
        );

        if (response.error) {
          this.error = response.error;
          this.isLoading = false;
          return;
        }

        // Update progress
        this.progress.remaining--;
        if (isCorrect) {
          this.progress.correct++;
        } else {
          this.progress.wrong++;
        }

        // Play feedback sound
        this.playSound(isCorrect);

        // Reset loading state before fetching next word
        // (nextWord() checks isLoading and returns early if true)
        this.isLoading = false;

        // Fetch next word
        await this.nextWord();
      } catch (err) {
        console.error('Error updating status:', err);
        this.error = 'Failed to update status';
        this.isLoading = false;
      }
    },

    /**
     * Increment the current word's status.
     */
    async incrementStatus(): Promise<void> {
      if (!this.currentWord || !this.answerRevealed) return;

      const newStatus = calculateNewStatus(this.currentWord.status, 1);
      await this.updateStatus(newStatus, true);
    },

    /**
     * Decrement the current word's status.
     */
    async decrementStatus(): Promise<void> {
      if (!this.currentWord || !this.answerRevealed) return;

      const newStatus = calculateNewStatus(this.currentWord.status, -1);
      await this.updateStatus(newStatus, false);
    },

    /**
     * Skip the current word without changing its status.
     */
    async skipWord(): Promise<void> {
      if (!this.currentWord || this.isLoading) return;

      // Update with same status (no change)
      await this.updateStatus(this.currentWord.status);
    },

    /**
     * Start the elapsed timer.
     */
    startTimer(): void {
      if (this.timer.intervalId !== null) return;

      const updateTimer = () => {
        const now = Math.floor(Date.now() / 1000);
        const clientOffset = now - this.timer.serverTime;
        const elapsed = now - this.timer.startTime - clientOffset;
        this.timer.elapsed = this.formatElapsed(Math.max(0, elapsed));
      };

      // Update immediately
      updateTimer();

      // Then update every second
      this.timer.intervalId = window.setInterval(updateTimer, 1000);
    },

    /**
     * Stop the elapsed timer.
     */
    stopTimer(): void {
      if (this.timer.intervalId !== null) {
        window.clearInterval(this.timer.intervalId);
        this.timer.intervalId = null;
      }
    },

    /**
     * Format seconds as MM:SS or HH:MM:SS.
     */
    formatElapsed(seconds: number): string {
      const hours = Math.floor(seconds / 3600);
      const minutes = Math.floor((seconds % 3600) / 60);
      const secs = seconds % 60;

      const pad = (n: number) => n.toString().padStart(2, '0');

      if (hours > 0) {
        return `${pad(hours)}:${pad(minutes)}:${pad(secs)}`;
      }
      return `${pad(minutes)}:${pad(secs)}`;
    },

    /**
     * Get dictionary URL for the current word.
     */
    getDictUrl(which: 'dict1' | 'dict2' | 'translator'): string {
      if (!this.currentWord) return '#';

      let template = '';
      switch (which) {
        case 'dict1':
          template = this.langSettings.dict1Uri;
          break;
        case 'dict2':
          template = this.langSettings.dict2Uri;
          break;
        case 'translator':
          template = this.langSettings.translateUri;
          break;
      }

      if (!template) return '#';

      return template.replace('lwt_term', encodeURIComponent(this.currentWord.text));
    },

    /**
     * Get edit URL for the current word.
     */
    getEditUrl(): string {
      if (!this.currentWord) return '#';
      return `/word/edit-term?wid=${this.currentWord.wordId}`;
    },

    /**
     * Open the word details modal.
     */
    openModal(): void {
      this.isModalOpen = true;
    },

    /**
     * Close the word details modal.
     */
    closeModal(): void {
      this.isModalOpen = false;
    },

    /**
     * Play success or failure sound.
     */
    playSound(correct: boolean): void {
      const soundId = correct ? 'success_sound' : 'failure_sound';
      const audio = document.getElementById(soundId) as HTMLAudioElement | null;
      if (audio) {
        audio.currentTime = 0;
        audio.play().catch(() => {
          // Ignore autoplay errors
        });
      }
    }
  };
}

/**
 * Initialize the test store as an Alpine.js store.
 */
export function initTestStore(): void {
  Alpine.store('test', createTestStore());
}

/**
 * Get the test store instance.
 */
export function getTestStore(): TestStoreState {
  return Alpine.store('test') as TestStoreState;
}

// Register the store immediately
initTestStore();

// Expose for global access
declare global {
  interface Window {
    getTestStore: typeof getTestStore;
  }
}

window.getTestStore = getTestStore;

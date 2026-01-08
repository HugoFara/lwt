/**
 * Home page Alpine.js application.
 *
 * Provides reactive state management for the home page dashboard
 * including collapsible menus, system warnings, and language selection.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { initIcons } from '@shared/icons/lucide_icons';
import type { LanguageChangedEvent, TextStats } from '@modules/language/stores/language_settings';

const STORAGE_KEY = 'lwt_collapsed_menus';

interface LastTextInfo {
  id: number;
  title: string;
  language_id: number;
  language_name: string;
  annotated: boolean;
  stats?: TextStats;
}

interface HomeWarningsConfig {
  phpVersion: string;
  lwtVersion: string;
  lastText: LastTextInfo | null;
}

interface Warning {
  type: 'danger' | 'warning' | 'info';
  message: string;
  visible: boolean;
}

interface PHPWarning extends Warning {
  phpVersion: string;
  minVersion: string;
}

interface UpdateWarning extends Warning {
  currentVersion: string;
  latestVersion: string;
  downloadUrl: string;
}

interface LanguageNotification {
  message: string;
  visible: boolean;
}

interface HomeData {
  // Menu state
  collapsedMenus: string[];

  // Last text info (dynamically updated when language changes)
  lastText: LastTextInfo | null;

  // Language change notification
  languageNotification: LanguageNotification;

  // Warnings
  warnings: {
    phpOutdated: PHPWarning;
    cookiesDisabled: Warning;
    updateAvailable: UpdateWarning;
  };

  // Methods
  init(): void;
  loadMenuState(): void;
  saveMenuState(): void;
  isCollapsed(menuId: string): boolean;
  toggleMenu(menuId: string): void;
  initWarnings(): void;
  initLanguageChangeListener(): void;
  handleLanguageChange(event: LanguageChangedEvent): void;
  checkCookies(): void;
  checkPHPVersion(version: string): void;
  checkLWTUpdate(currentVersion: string): void;
  shouldUpdate(fromVersion: string, toVersion: string): boolean | null;
}

/**
 * Alpine.js data component for the home page.
 */
export function homeData(): HomeData {
  return {
    collapsedMenus: [],

    lastText: null,

    languageNotification: {
      message: '',
      visible: false
    },

    warnings: {
      phpOutdated: {
        type: 'danger',
        message: '',
        visible: false,
        phpVersion: '',
        minVersion: ''
      },
      cookiesDisabled: {
        type: 'warning',
        message: '',
        visible: false
      },
      updateAvailable: {
        type: 'info',
        message: '',
        visible: false,
        currentVersion: '',
        latestVersion: '',
        downloadUrl: ''
      }
    },

    init() {
      // Load collapsed menus from localStorage
      this.loadMenuState();

      // Initialize warnings and last text from config
      this.initWarnings();

      // Listen for language changes
      this.initLanguageChangeListener();
    },

    loadMenuState() {
      try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === null) {
          // First visit: collapse all except Texts
          this.collapsedMenus = ['terms', 'feeds', 'admin', 'settings'];
          this.saveMenuState();
        } else {
          this.collapsedMenus = JSON.parse(stored);
        }
      } catch {
        this.collapsedMenus = [];
      }
    },

    saveMenuState() {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(this.collapsedMenus));
      } catch {
        // localStorage not available
      }
    },

    isCollapsed(menuId: string): boolean {
      return this.collapsedMenus.includes(menuId);
    },

    toggleMenu(menuId: string) {
      const index = this.collapsedMenus.indexOf(menuId);
      if (index > -1) {
        this.collapsedMenus.splice(index, 1);
      } else {
        this.collapsedMenus.push(menuId);
      }
      this.saveMenuState();
    },

    initWarnings() {
      const configElement = document.getElementById('home-warnings-config');
      if (!configElement) {
        return;
      }

      try {
        const config: HomeWarningsConfig = JSON.parse(configElement.textContent || '{}');

        // Load initial last text info
        this.lastText = config.lastText;

        // Check all warnings
        this.checkCookies();
        this.checkPHPVersion(config.phpVersion);
        this.checkLWTUpdate(config.lwtVersion);
      } catch (e) {
        console.error('Failed to parse home warnings config:', e);
      }
    },

    initLanguageChangeListener() {
      // Listen for the custom language change event
      document.addEventListener('lwt:languageChanged', ((event: LanguageChangedEvent) => {
        this.handleLanguageChange(event);
      }) as EventListener);
    },

    handleLanguageChange(event: LanguageChangedEvent) {
      const { languageName, response } = event.detail;

      // Update the last text info
      if (response.last_text) {
        this.lastText = response.last_text;
      } else {
        this.lastText = null;
      }

      // Show notification
      this.languageNotification.message = `Language changed to "${languageName}"`;
      this.languageNotification.visible = true;

      // Refresh Lucide icons for the newly rendered template
      setTimeout(() => {
        initIcons();
      }, 0);

      // Auto-hide notification after 3 seconds
      setTimeout(() => {
        this.languageNotification.visible = false;
      }, 3000);
    },

    checkCookies() {
      // Test if cookies are enabled
      try {
        document.cookie = 'lwt_cookie_test=1; SameSite=Strict';
        const enabled = document.cookie.indexOf('lwt_cookie_test') !== -1;
        // Clean up test cookie
        document.cookie = 'lwt_cookie_test=; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Strict';

        if (!enabled) {
          this.warnings.cookiesDisabled.message =
            'Cookies are not enabled! Please enable them for LWT to work properly.';
          this.warnings.cookiesDisabled.visible = true;
        }
      } catch {
        // If we can't even try, assume cookies are disabled
        this.warnings.cookiesDisabled.message =
          'Cookies are not enabled! Please enable them for LWT to work properly.';
        this.warnings.cookiesDisabled.visible = true;
      }
    },

    checkPHPVersion(phpVersion: string) {
      const phpMinVersion = '8.0.0';
      if (this.shouldUpdate(phpVersion, phpMinVersion)) {
        this.warnings.phpOutdated.phpVersion = phpVersion;
        this.warnings.phpOutdated.minVersion = phpMinVersion;
        this.warnings.phpOutdated.visible = true;
      }
    },

    checkLWTUpdate(lwtVersion: string) {
      fetch('https://api.github.com/repos/hugofara/lwt/releases/latest')
        .then(response => response.json())
        .then((data: { tag_name: string }) => {
          const latestVersion = data.tag_name;
          if (this.shouldUpdate(lwtVersion, latestVersion)) {
            this.warnings.updateAvailable.currentVersion = lwtVersion;
            this.warnings.updateAvailable.latestVersion = latestVersion;
            this.warnings.updateAvailable.downloadUrl = `https://github.com/HugoFara/lwt/releases/tag/${latestVersion}`;
            this.warnings.updateAvailable.visible = true;
          }
        })
        .catch(() => {
          // Silently fail if GitHub API is unreachable
        });
    },

    shouldUpdate(fromVersion: string, toVersion: string): boolean | null {
      const regex = /^(\d+)\.(\d+)\.(\d+)(?:-[\w.-]+)?/;
      const match1 = fromVersion.match(regex);
      const match2 = toVersion.match(regex);

      if (!match1 || !match2) {
        return null;
      }

      for (let i = 1; i < 4; i++) {
        const level1 = parseInt(match1[i], 10);
        const level2 = parseInt(match2[i], 10);
        if (level1 < level2) {
          return true;
        } else if (level1 > level2) {
          return false;
        }
      }

      return null;
    }
  };
}

/**
 * Initialize the home page Alpine.js components.
 * This must be called before Alpine.start().
 */
export function initHomeAlpine(): void {
  // Register the home data component
  Alpine.data('homeApp', homeData);
}

// Expose for global access if needed
declare global {
  interface Window {
    Alpine: typeof Alpine;
    homeData: typeof homeData;
    initHomeAlpine: typeof initHomeAlpine;
  }
}

window.homeData = homeData;
window.initHomeAlpine = initHomeAlpine;

// Register Alpine data component immediately (before Alpine.start() in main.ts)
initHomeAlpine();

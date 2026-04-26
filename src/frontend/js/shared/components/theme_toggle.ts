/**
 * Alpine.js Theme Toggle Component
 *
 * Provides a dark/light mode toggle button in the navbar.
 * Reads counterpart theme from data attributes and saves via Settings API.
 * Supports auto-detect mode where the icon/counterpart are set dynamically
 * based on the OS color scheme preference.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { SettingsApi } from '@modules/admin/api/settings_api';
import { t } from '@shared/i18n/translator';

interface ThemeToggleData {
  init(): void;
  switchTheme(): void;
  updateIconForMode(el: HTMLElement, mode: string): void;
}

const FLASH_KEY = 'lwt:theme-flash';
const AUTO_THEME_VALUES = ['', 'themes/default/', 'dist/themes/Default/'];

function themeDirIsAuto(themeDir: string): boolean {
  return AUTO_THEME_VALUES.includes(themeDir);
}

function themeDisplayName(themeDir: string): string {
  // 'dist/themes/Dark/' -> 'Dark'
  return themeDir.split('/').filter(Boolean).pop() ?? themeDir;
}

function showThemeFlash(): void {
  const themeDir = sessionStorage.getItem(FLASH_KEY);
  if (themeDir === null) return;
  sessionStorage.removeItem(FLASH_KEY);

  const name = themeDisplayName(themeDir);
  const message = themeDirIsAuto(themeDir)
    ? t('navbar.theme_saved_auto', { theme: name })
    : t('navbar.theme_saved', { theme: name });

  const notif = document.createElement('div');
  notif.className = 'notification is-info';
  notif.setAttribute('role', 'status');

  const close = document.createElement('button');
  close.className = 'delete';
  close.setAttribute('aria-label', 'close');
  close.addEventListener('click', () => notif.remove());
  notif.appendChild(close);
  notif.appendChild(document.createTextNode(message));

  const target = document.querySelector('main') ?? document.body;
  target.insertBefore(notif, target.firstChild);
  window.setTimeout(() => notif.remove(), 4000);
}

function themeToggleData(): ThemeToggleData {
  return {
    init() {
      const el = (this as unknown as { $el: HTMLElement }).$el;
      const isAuto = el.dataset.autoTheme === 'true';

      if (isAuto) {
        // Detect effective mode from OS and update icon
        const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        this.updateIconForMode(el, isDark ? 'dark' : 'light');
        // Set counterpart dynamically
        el.dataset.themeCounterpart = isDark
          ? 'dist/themes/Default/'   // go to forced light
          : 'dist/themes/Dark/';     // go to dark
      }

      // Attach click handler imperatively. The Alpine CSP build does not bind
      // `@click` directives reliably when they share an element with `x-data`,
      // which is the layout used by the navbar toggle. Wiring up the listener
      // here from `init()` sidesteps the issue.
      el.addEventListener('click', (event) => {
        event.preventDefault();
        this.switchTheme();
      });

      // Show a confirmation toast if the previous page set one before reloading.
      showThemeFlash();
    },

    switchTheme() {
      const el = (this as unknown as { $el: HTMLElement }).$el;
      const counterpart = el.dataset.themeCounterpart;
      if (!counterpart) return;

      SettingsApi.save('set-theme-dir', counterpart).then(() => {
        sessionStorage.setItem(FLASH_KEY, counterpart);
        window.location.reload();
      });
    },

    updateIconForMode(el: HTMLElement, mode: string) {
      // Swap the lucide icon data attribute: moon for light, sun for dark
      const icon = el.querySelector('[data-lucide]');
      if (icon) {
        icon.setAttribute('data-lucide', mode === 'dark' ? 'sun' : 'moon');
        // Re-initialize icons if the icon library is available
        if (window.LWT_Icons) {
          window.LWT_Icons.init();
        }
      }
      el.title = mode === 'dark'
        ? t('navbar.switch_to_light_mode')
        : t('navbar.switch_to_dark_mode');
    }
  };
}

Alpine.data('themeToggle', themeToggleData as Parameters<typeof Alpine.data>[1]);

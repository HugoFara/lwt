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

interface ThemeToggleData {
  init(): void;
  toggle(): void;
  updateIconForMode(el: HTMLElement, mode: string): void;
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
          ? 'assets/themes/Default/'   // go to forced light
          : 'assets/themes/Dark/';     // go to dark
      }
    },

    toggle() {
      const el = (this as unknown as { $el: HTMLElement }).$el;
      const counterpart = el.dataset.themeCounterpart;
      if (!counterpart) return;

      SettingsApi.save('set-theme-dir', counterpart).then(() => {
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
      el.title = mode === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
    }
  };
}

Alpine.data('themeToggle', themeToggleData as Parameters<typeof Alpine.data>[1]);

/**
 * Alpine.js Theme Toggle Component
 *
 * Provides a dark/light mode toggle button in the navbar.
 * Reads counterpart theme from data attributes and saves via Settings API.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';
import { SettingsApi } from '@modules/admin/api/settings_api';

interface ThemeToggleData {
  toggle(): void;
}

function themeToggleData(): ThemeToggleData {
  return {
    toggle() {
      const el = (this as unknown as { $el: HTMLElement }).$el;
      const counterpart = el.dataset.themeCounterpart;
      if (!counterpart) return;

      SettingsApi.save('set-theme-dir', counterpart).then(() => {
        window.location.reload();
      });
    }
  };
}

Alpine.data('themeToggle', themeToggleData as Parameters<typeof Alpine.data>[1]);

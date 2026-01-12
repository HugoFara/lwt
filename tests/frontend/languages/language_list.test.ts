/**
 * Tests for languages/language_list.ts - Language List page interactions
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';

// Mock the dependencies using vi.hoisted to ensure mocks are defined before module import
const { mockSave, mockInitIcons } = vi.hoisted(() => ({
  mockSave: vi.fn(),
  mockInitIcons: vi.fn(),
}));

vi.mock('../../../src/frontend/js/modules/admin/api/settings_api', () => ({
  SettingsApi: {
    save: mockSave,
  },
}));

vi.mock('../../../src/frontend/js/shared/icons/lucide_icons', () => ({
  initIcons: mockInitIcons,
}));

describe('languages/language_list.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
    document.body.innerHTML = '';
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe('module import', () => {
    it('module can be imported without error', async () => {
      await expect(import('../../../src/frontend/js/modules/language/pages/language_list')).resolves.not.toThrow();
    });
  });

  describe('showNotification', () => {
    beforeEach(async () => {
      // Set up notification elements
      document.body.innerHTML = `
        <div id="language-notification" style="display: none;" class="notification">
          <span id="language-notification-text"></span>
        </div>
        <div class="language-card" data-lang-id="1">
          <div class="card-header-title">English</div>
          <div class="card-header-icon"></div>
        </div>
      `;
      // Re-import to trigger DOMContentLoaded listener registration
      vi.resetModules();
      await import('../../../src/frontend/js/modules/language/pages/language_list');
      // Dispatch DOMContentLoaded to initialize the module
      document.dispatchEvent(new Event('DOMContentLoaded'));
    });

    it('calls API with correct parameters when setting current language', async () => {
      mockSave.mockResolvedValueOnce({ error: null });

      // Trigger the click event on a set-current-language button
      const button = document.createElement('button');
      button.setAttribute('data-action', 'set-current-language');
      button.setAttribute('data-lang-id', '2');
      button.setAttribute('data-lang-name', 'Spanish');
      document.body.appendChild(button);

      const clickEvent = new MouseEvent('click', { bubbles: true });
      button.dispatchEvent(clickEvent);

      // Wait for API to be called with correct parameters
      await vi.waitFor(() => {
        expect(mockSave).toHaveBeenCalledWith('currentlanguage', '2');
      });
    });

    it('shows error notification when API call fails', async () => {
      mockSave.mockResolvedValueOnce({ error: 'Failed to save' });

      const notification = document.getElementById('language-notification')!;

      const button = document.createElement('button');
      button.setAttribute('data-action', 'set-current-language');
      button.setAttribute('data-lang-id', '2');
      button.setAttribute('data-lang-name', 'Spanish');
      document.body.appendChild(button);

      const clickEvent = new MouseEvent('click', { bubbles: true });
      button.dispatchEvent(clickEvent);

      await vi.waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });

      expect(notification.classList.contains('is-danger')).toBe(true);
    });

    it('shows error notification when API call throws', async () => {
      mockSave.mockRejectedValueOnce(new Error('Network error'));

      const notification = document.getElementById('language-notification')!;

      const button = document.createElement('button');
      button.setAttribute('data-action', 'set-current-language');
      button.setAttribute('data-lang-id', '2');
      button.setAttribute('data-lang-name', 'Spanish');
      document.body.appendChild(button);

      const clickEvent = new MouseEvent('click', { bubbles: true });
      button.dispatchEvent(clickEvent);

      await vi.waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });

      expect(notification.classList.contains('is-danger')).toBe(true);
    });

    it('auto-hides notification after 4 seconds', async () => {
      mockSave.mockResolvedValueOnce({ error: null });

      const notification = document.getElementById('language-notification')!;

      const button = document.createElement('button');
      button.setAttribute('data-action', 'set-current-language');
      button.setAttribute('data-lang-id', '2');
      button.setAttribute('data-lang-name', 'Spanish');
      document.body.appendChild(button);

      const clickEvent = new MouseEvent('click', { bubbles: true });
      button.dispatchEvent(clickEvent);

      await vi.waitFor(() => {
        expect(notification.style.display).toBe('block');
      });

      // Fast-forward 4 seconds
      vi.advanceTimersByTime(4000);

      expect(notification.style.display).toBe('none');
    });

    it('does nothing when notification elements are not present', async () => {
      document.body.innerHTML = ''; // Remove notification elements
      mockSave.mockResolvedValueOnce({ error: null });

      const button = document.createElement('button');
      button.setAttribute('data-action', 'set-current-language');
      button.setAttribute('data-lang-id', '2');
      button.setAttribute('data-lang-name', 'Spanish');
      document.body.appendChild(button);

      const clickEvent = new MouseEvent('click', { bubbles: true });
      button.dispatchEvent(clickEvent);

      await vi.waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });

      // Should not throw error
    });
  });

  describe('updateLanguageCards', () => {
    beforeEach(async () => {
      document.body.innerHTML = `
        <div id="language-notification" style="display: none;" class="notification">
          <span id="language-notification-text"></span>
        </div>
        <div class="language-card is-current" data-lang-id="1">
          <div class="card-header-title">
            <i data-lucide="circle-alert"></i> English
          </div>
          <div class="card-header-icon"></div>
        </div>
        <div class="language-card" data-lang-id="2">
          <div class="card-header-title">Spanish</div>
          <div class="card-header-icon">
            <button class="button set-current-language-btn" data-action="set-current-language" data-lang-id="2" data-lang-name="Spanish">
              Set as Default
            </button>
          </div>
        </div>
        <div class="language-card" data-lang-id="3">
          <div class="card-header-title">French</div>
          <div class="card-header-icon">
            <button class="button set-current-language-btn" data-action="set-current-language" data-lang-id="3" data-lang-name="French">
              Set as Default
            </button>
          </div>
        </div>
      `;
      vi.resetModules();
      await import('../../../src/frontend/js/modules/language/pages/language_list');
      document.dispatchEvent(new Event('DOMContentLoaded'));
    });

    it('updates card classes when language changes', async () => {
      mockSave.mockResolvedValueOnce({ error: null });

      const card1 = document.querySelector('[data-lang-id="1"]')!;
      const card2 = document.querySelector('[data-lang-id="2"]')!;

      // Initially card1 is current
      expect(card1.classList.contains('is-current')).toBe(true);
      expect(card2.classList.contains('is-current')).toBe(false);

      // Click to set card2 as current
      const button = card2.querySelector('.set-current-language-btn') as HTMLElement;
      button.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      await vi.waitFor(() => {
        expect(mockSave).toHaveBeenCalledWith('currentlanguage', '2');
      });

      // Card2 should now be current
      expect(card1.classList.contains('is-current')).toBe(false);
      expect(card2.classList.contains('is-current')).toBe(true);
    });

    it('adds indicator icon to new current language card', async () => {
      mockSave.mockResolvedValueOnce({ error: null });

      const card2 = document.querySelector('[data-lang-id="2"]')!;
      const button = card2.querySelector('.set-current-language-btn') as HTMLElement;

      button.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      await vi.waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });

      const card2Title = card2.querySelector('.card-header-title')!;
      expect(card2Title.querySelector('[data-lucide="circle-alert"]')).not.toBeNull();
    });

    it('removes indicator icon from old current language card', async () => {
      mockSave.mockResolvedValueOnce({ error: null });

      const card1 = document.querySelector('[data-lang-id="1"]')!;
      const card2 = document.querySelector('[data-lang-id="2"]')!;

      // Initially card1 has the indicator icon
      expect(card1.querySelector('[data-lucide="circle-alert"]')).not.toBeNull();

      const button = card2.querySelector('.set-current-language-btn') as HTMLElement;
      button.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      await vi.waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });

      const card1Title = card1.querySelector('.card-header-title')!;
      expect(card1Title.querySelector('[data-lucide="circle-alert"]')).toBeNull();
    });

    it('removes Set as Default button from new current language', async () => {
      mockSave.mockResolvedValueOnce({ error: null });

      const card2 = document.querySelector('[data-lang-id="2"]')!;
      const button = card2.querySelector('.set-current-language-btn') as HTMLElement;

      button.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      await vi.waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });

      const card2Icon = card2.querySelector('.card-header-icon')!;
      expect(card2Icon.innerHTML).toBe('');
    });

    it('adds Set as Default button to old current language', async () => {
      mockSave.mockResolvedValueOnce({ error: null });

      const card1 = document.querySelector('[data-lang-id="1"]')!;
      const card2 = document.querySelector('[data-lang-id="2"]')!;

      // Initially card1 has no button
      expect(card1.querySelector('.set-current-language-btn')).toBeNull();

      const button = card2.querySelector('.set-current-language-btn') as HTMLElement;
      button.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      await vi.waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });

      expect(card1.querySelector('.set-current-language-btn')).not.toBeNull();
    });

    it('calls initIcons after updating cards', async () => {
      mockSave.mockResolvedValueOnce({ error: null });

      const card2 = document.querySelector('[data-lang-id="2"]')!;
      const button = card2.querySelector('.set-current-language-btn') as HTMLElement;

      button.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      await vi.waitFor(() => {
        expect(mockInitIcons).toHaveBeenCalled();
      });
    });
  });

  describe('handleSetCurrentLanguage', () => {
    beforeEach(async () => {
      document.body.innerHTML = `
        <div id="language-notification" style="display: none;" class="notification">
          <span id="language-notification-text"></span>
        </div>
        <div class="language-card" data-lang-id="1">
          <div class="card-header-title">English</div>
          <div class="card-header-icon">
            <button class="button set-current-language-btn" data-action="set-current-language" data-lang-id="1" data-lang-name="English">
              Set as Default
            </button>
          </div>
        </div>
      `;
      vi.resetModules();
      await import('../../../src/frontend/js/modules/language/pages/language_list');
      document.dispatchEvent(new Event('DOMContentLoaded'));
    });

    it('shows loading state while saving', async () => {
      let resolvePromise: (value: unknown) => void;
      const promise = new Promise(resolve => {
        resolvePromise = resolve;
      });
      mockSave.mockReturnValueOnce(promise);

      const button = document.querySelector('.set-current-language-btn') as HTMLButtonElement;

      button.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      // Button should be in loading state
      await vi.waitFor(() => {
        expect(button.classList.contains('is-loading')).toBe(true);
        expect(button.disabled).toBe(true);
      });

      // Resolve the promise
      resolvePromise!({ error: null });
    });

    it('restores button state on error', async () => {
      mockSave.mockResolvedValueOnce({ error: 'Failed' });

      const button = document.querySelector('.set-current-language-btn') as HTMLButtonElement;

      button.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      await vi.waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });

      expect(button.classList.contains('is-loading')).toBe(false);
      expect(button.disabled).toBe(false);
    });

    it('restores button state on exception', async () => {
      mockSave.mockRejectedValueOnce(new Error('Network error'));

      const button = document.querySelector('.set-current-language-btn') as HTMLButtonElement;

      button.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      await vi.waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });

      expect(button.classList.contains('is-loading')).toBe(false);
      expect(button.disabled).toBe(false);
    });

    it('does nothing when button has no langId', async () => {
      const button = document.createElement('button');
      button.setAttribute('data-action', 'set-current-language');
      // No data-lang-id
      document.body.appendChild(button);

      button.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      // Should not call the API
      expect(mockSave).not.toHaveBeenCalled();
    });
  });

  describe('event delegation', () => {
    beforeEach(async () => {
      document.body.innerHTML = `
        <div id="language-notification" style="display: none;" class="notification">
          <span id="language-notification-text"></span>
        </div>
        <div class="language-card" data-lang-id="1">
          <div class="card-header-title">English</div>
          <div class="card-header-icon">
            <button class="button set-current-language-btn" data-action="set-current-language" data-lang-id="1" data-lang-name="English">
              <span>Set as Default</span>
            </button>
          </div>
        </div>
      `;
      vi.resetModules();
      await import('../../../src/frontend/js/modules/language/pages/language_list');
      document.dispatchEvent(new Event('DOMContentLoaded'));
    });

    it('handles clicks on nested elements within button', async () => {
      mockSave.mockResolvedValueOnce({ error: null });

      // Click the span inside the button
      const span = document.querySelector('.set-current-language-btn span') as HTMLElement;

      span.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      await vi.waitFor(() => {
        expect(mockSave).toHaveBeenCalledWith('currentlanguage', '1');
      });
    });

    it('ignores clicks on elements without data-action', async () => {
      const otherButton = document.createElement('button');
      otherButton.textContent = 'Other Button';
      document.body.appendChild(otherButton);

      otherButton.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      expect(mockSave).not.toHaveBeenCalled();
    });
  });

  describe('iconHtml helper', () => {
    beforeEach(async () => {
      document.body.innerHTML = `
        <div id="language-notification" style="display: none;" class="notification">
          <span id="language-notification-text"></span>
        </div>
        <div class="language-card" data-lang-id="1">
          <div class="card-header-title">English</div>
          <div class="card-header-icon">
            <button class="button set-current-language-btn" data-action="set-current-language" data-lang-id="1" data-lang-name="English">
              Set as Default
            </button>
          </div>
        </div>
        <div class="language-card" data-lang-id="2">
          <div class="card-header-title">Spanish</div>
          <div class="card-header-icon"></div>
        </div>
      `;
      vi.resetModules();
      await import('../../../src/frontend/js/modules/language/pages/language_list');
      document.dispatchEvent(new Event('DOMContentLoaded'));
    });

    it('generates icon HTML with correct attributes', async () => {
      mockSave.mockResolvedValueOnce({ error: null });

      const button = document.querySelector('.set-current-language-btn') as HTMLElement;
      button.dispatchEvent(new MouseEvent('click', { bubbles: true }));

      await vi.waitFor(() => {
        expect(mockSave).toHaveBeenCalled();
      });

      // The card1 header should now have an icon with the title attribute
      const card1 = document.querySelector('[data-lang-id="1"]')!;
      const icon = card1.querySelector('.card-header-title [data-lucide="circle-alert"]');
      expect(icon).not.toBeNull();
      expect(icon?.getAttribute('title')).toBe('Current Language');
    });
  });
});

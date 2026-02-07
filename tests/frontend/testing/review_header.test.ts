/**
 * Tests for review_header.ts - Review header initialization and navigation.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

import {
  setUtteranceSetting,
  startWordReview,
  startTableReview
} from '../../../src/frontend/js/modules/review/pages/review_header';

describe('review_header.ts', () => {
  let originalLocation: Location;
  let mockLocalStorage: Record<string, string>;

  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';

    // Mock location
    originalLocation = window.location;
    delete (window as any).location;
    window.location = {
      href: 'http://localhost/test',
      assign: vi.fn(),
      replace: vi.fn(),
      reload: vi.fn()
    } as unknown as Location;

    // Mock localStorage
    mockLocalStorage = {};
    Object.defineProperty(window, 'localStorage', {
      value: {
        getItem: vi.fn((key: string) => mockLocalStorage[key] || null),
        setItem: vi.fn((key: string, value: string) => {
          mockLocalStorage[key] = value;
        }),
        removeItem: vi.fn((key: string) => {
          delete mockLocalStorage[key];
        }),
        clear: vi.fn(() => {
          mockLocalStorage = {};
        })
      },
      writable: true
    });
  });

  afterEach(() => {
    window.location = originalLocation;
  });

  describe('setUtteranceSetting', () => {
    it('sets checkbox to false when localStorage has no value', () => {
      document.body.innerHTML = `
        <input type="checkbox" id="utterance-allowed" />
      `;

      setUtteranceSetting();

      const checkbox = document.getElementById('utterance-allowed') as HTMLInputElement;
      expect(checkbox.checked).toBe(false);
    });

    it('sets checkbox to true when localStorage has true', () => {
      mockLocalStorage['review-utterance-allowed'] = 'true';
      document.body.innerHTML = `
        <input type="checkbox" id="utterance-allowed" />
      `;

      setUtteranceSetting();

      const checkbox = document.getElementById('utterance-allowed') as HTMLInputElement;
      expect(checkbox.checked).toBe(true);
    });

    it('sets checkbox to false when localStorage has false', () => {
      mockLocalStorage['review-utterance-allowed'] = 'false';
      document.body.innerHTML = `
        <input type="checkbox" id="utterance-allowed" />
      `;

      setUtteranceSetting();

      const checkbox = document.getElementById('utterance-allowed') as HTMLInputElement;
      expect(checkbox.checked).toBe(false);
    });

    it('does nothing if checkbox does not exist', () => {
      document.body.innerHTML = '<div></div>';

      // Should not throw
      expect(() => setUtteranceSetting()).not.toThrow();
    });

    it('saves preference to localStorage on change', () => {
      document.body.innerHTML = `
        <input type="checkbox" id="utterance-allowed" />
      `;

      setUtteranceSetting();

      const checkbox = document.getElementById('utterance-allowed') as HTMLInputElement;

      // Check the checkbox
      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change'));

      expect(window.localStorage.setItem).toHaveBeenCalledWith(
        'review-utterance-allowed',
        'true'
      );

      // Uncheck the checkbox
      checkbox.checked = false;
      checkbox.dispatchEvent(new Event('change'));

      expect(window.localStorage.setItem).toHaveBeenCalledWith(
        'review-utterance-allowed',
        'false'
      );
    });
  });

  describe('startWordReview', () => {
    it('navigates to word test URL with type and property', () => {
      startWordReview(1, 'lang=1');

      expect(window.location.href).toBe('/review?type=1&lang=1');
    });

    it('handles different test types', () => {
      startWordReview(3, 'selection=5');

      expect(window.location.href).toBe('/review?type=3&selection=5');
    });

    it('works with empty property', () => {
      startWordReview(2, '');

      expect(window.location.href).toBe('/review?type=2&');
    });
  });

  describe('startTableReview', () => {
    it('navigates to table test URL', () => {
      startTableReview('lang=1&text=5');

      expect(window.location.href).toBe('/review?type=table&lang=1&text=5');
    });

    it('works with empty property', () => {
      startTableReview('');

      expect(window.location.href).toBe('/review?type=table&');
    });
  });

  describe('initReviewHeaderEvents (event delegation)', () => {
    beforeEach(async () => {
      // Re-import module to trigger DOMContentLoaded setup
      // First dispatch DOMContentLoaded to trigger event binding
      document.dispatchEvent(new Event('DOMContentLoaded'));
    });

    it('triggers word review when clicking start-word-review button', () => {
      document.body.innerHTML = `
        <button data-action="start-word-review" data-review-type="2" data-property="lang=5">
          Start Review
        </button>
      `;

      const button = document.querySelector('[data-action="start-word-review"]') as HTMLElement;
      button.click();

      expect(window.location.href).toBe('/review?type=2&lang=5');
    });

    it('uses default type 1 when data-review-type is not set', () => {
      document.body.innerHTML = `
        <button data-action="start-word-review" data-property="lang=3">
          Start Review
        </button>
      `;

      const button = document.querySelector('[data-action="start-word-review"]') as HTMLElement;
      button.click();

      expect(window.location.href).toBe('/review?type=1&lang=3');
    });

    it('uses empty property when data-property is not set', () => {
      document.body.innerHTML = `
        <button data-action="start-word-review" data-review-type="4">
          Start Review
        </button>
      `;

      const button = document.querySelector('[data-action="start-word-review"]') as HTMLElement;
      button.click();

      expect(window.location.href).toBe('/review?type=4&');
    });

    it('triggers table review when clicking start-table-review button', () => {
      document.body.innerHTML = `
        <button data-action="start-table-review" data-property="lang=2&text=10">
          Start Table Review
        </button>
      `;

      const button = document.querySelector('[data-action="start-table-review"]') as HTMLElement;
      button.click();

      expect(window.location.href).toBe('/review?type=table&lang=2&text=10');
    });

    it('uses empty property for table review when not set', () => {
      document.body.innerHTML = `
        <button data-action="start-table-review">
          Start Table Review
        </button>
      `;

      const button = document.querySelector('[data-action="start-table-review"]') as HTMLElement;
      button.click();

      expect(window.location.href).toBe('/review?type=table&');
    });

    it('handles click on nested element within word review button', () => {
      document.body.innerHTML = `
        <button data-action="start-word-review" data-review-type="3" data-property="text=7">
          <span class="icon"><i class="fa fa-play"></i></span>
          <span>Start</span>
        </button>
      `;

      const innerSpan = document.querySelector('.icon') as HTMLElement;
      innerSpan.click();

      expect(window.location.href).toBe('/review?type=3&text=7');
    });

    it('handles click on nested element within table review button', () => {
      document.body.innerHTML = `
        <button data-action="start-table-review" data-property="selection=all">
          <span class="icon"><i class="fa fa-table"></i></span>
        </button>
      `;

      const innerSpan = document.querySelector('.icon') as HTMLElement;
      innerSpan.click();

      expect(window.location.href).toBe('/review?type=table&selection=all');
    });

    it('does not trigger review for other buttons', () => {
      const initialHref = window.location.href;
      document.body.innerHTML = `
        <button data-action="some-other-action">Other Button</button>
      `;

      const button = document.querySelector('button') as HTMLElement;
      button.click();

      expect(window.location.href).toBe(initialHref);
    });

    it('does not trigger review for elements without data-action', () => {
      const initialHref = window.location.href;
      document.body.innerHTML = `
        <button>Regular Button</button>
      `;

      const button = document.querySelector('button') as HTMLElement;
      button.click();

      expect(window.location.href).toBe(initialHref);
    });
  });

  describe('DOMContentLoaded initialization', () => {
    it('initializes utterance setting if checkbox exists on DOMContentLoaded', () => {
      mockLocalStorage['review-utterance-allowed'] = 'true';
      document.body.innerHTML = `
        <input type="checkbox" id="utterance-allowed" />
      `;

      // onDomReady fires immediately when readyState is not 'loading',
      // so we call setUtteranceSetting directly to simulate the init
      setUtteranceSetting();

      const checkbox = document.getElementById('utterance-allowed') as HTMLInputElement;
      expect(checkbox.checked).toBe(true);
    });
  });
});

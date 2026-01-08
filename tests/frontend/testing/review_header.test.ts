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
});

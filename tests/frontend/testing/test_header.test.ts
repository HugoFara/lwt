/**
 * Tests for test_header.ts - Test header initialization and navigation.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

import {
  setUtteranceSetting,
  resetTestFrames,
  startWordTest,
  startTestTable
} from '../../../src/frontend/js/testing/test_header';

describe('test_header.ts', () => {
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

  describe('resetTestFrames', () => {
    it('resets ro and ru frames to empty.html', () => {
      const roFrame = { location: { href: '/test/1' } };
      const ruFrame = { location: { href: '/test/2' } };

      // Mock parent window with frames
      Object.defineProperty(window, 'parent', {
        value: {
          frames: {
            ro: roFrame,
            ru: ruFrame
          }
        },
        writable: true
      });

      resetTestFrames();

      expect(roFrame.location.href).toBe('empty.html');
      expect(ruFrame.location.href).toBe('empty.html');
    });

    it('handles missing frames gracefully', () => {
      // Mock parent window without frames
      Object.defineProperty(window, 'parent', {
        value: {
          frames: {}
        },
        writable: true
      });

      // Should not throw
      expect(() => resetTestFrames()).not.toThrow();
    });

    it('handles only ro frame existing', () => {
      const roFrame = { location: { href: '/test/1' } };

      Object.defineProperty(window, 'parent', {
        value: {
          frames: {
            ro: roFrame
          }
        },
        writable: true
      });

      resetTestFrames();

      expect(roFrame.location.href).toBe('empty.html');
    });

    it('handles only ru frame existing', () => {
      const ruFrame = { location: { href: '/test/2' } };

      Object.defineProperty(window, 'parent', {
        value: {
          frames: {
            ru: ruFrame
          }
        },
        writable: true
      });

      resetTestFrames();

      expect(ruFrame.location.href).toBe('empty.html');
    });
  });

  describe('startWordTest', () => {
    beforeEach(() => {
      // Mock parent window without frames to avoid issues
      Object.defineProperty(window, 'parent', {
        value: {
          frames: {}
        },
        writable: true
      });
    });

    it('navigates to word test URL with type and property', () => {
      startWordTest(1, 'lang=1');

      expect(window.location.href).toBe('/test?type=1&lang=1');
    });

    it('handles different test types', () => {
      startWordTest(3, 'selection=5');

      expect(window.location.href).toBe('/test?type=3&selection=5');
    });

    it('works with empty property', () => {
      startWordTest(2, '');

      expect(window.location.href).toBe('/test?type=2&');
    });

    it('calls resetTestFrames before navigation', () => {
      const roFrame = { location: { href: '/old' } };
      Object.defineProperty(window, 'parent', {
        value: {
          frames: {
            ro: roFrame
          }
        },
        writable: true
      });

      startWordTest(1, 'test=1');

      expect(roFrame.location.href).toBe('empty.html');
    });
  });

  describe('startTestTable', () => {
    beforeEach(() => {
      Object.defineProperty(window, 'parent', {
        value: {
          frames: {}
        },
        writable: true
      });
    });

    it('navigates to table test URL', () => {
      startTestTable('lang=1&text=5');

      expect(window.location.href).toBe('/test?type=table&lang=1&text=5');
    });

    it('works with empty property', () => {
      startTestTable('');

      expect(window.location.href).toBe('/test?type=table&');
    });

    it('calls resetTestFrames before navigation', () => {
      const ruFrame = { location: { href: '/old' } };
      Object.defineProperty(window, 'parent', {
        value: {
          frames: {
            ru: ruFrame
          }
        },
        writable: true
      });

      startTestTable('test=1');

      expect(ruFrame.location.href).toBe('empty.html');
    });
  });
});

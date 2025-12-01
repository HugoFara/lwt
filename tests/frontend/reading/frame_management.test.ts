/**
 * Tests for frame_management.ts - Right frames show/hide/cleanup operations
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  showRightFrames,
  hideRightFrames,
  cleanupRightFrames,
  successSound,
  failureSound
} from '../../../src/frontend/js/reading/frame_management';

// Mock word_popup module
vi.mock('../../../src/frontend/js/ui/word_popup', () => ({
  cClick: vi.fn()
}));

import { cClick } from '../../../src/frontend/js/ui/word_popup';

describe('frame_management.ts', () => {
  // Store originals
  const originalTop = global.top;
  const originalParent = global.parent;

  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    vi.useFakeTimers();

    // Setup mock frames for top.frames access
    const mockFrames: any = {};
    (global as any).top = {
      frames: mockFrames
    };
    (global as any).parent = {
      document: document,
      setTimeout: vi.fn((fn: () => void, delay: number) => setTimeout(fn, delay))
    };
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
    document.body.innerHTML = '';
    (global as any).top = originalTop;
    (global as any).parent = originalParent;
  });

  // ===========================================================================
  // showRightFrames Tests
  // ===========================================================================

  describe('showRightFrames', () => {
    it('returns true when #frames-r exists', () => {
      document.body.innerHTML = '<div id="frames-r" style="right: -100%;"></div>';

      const result = showRightFrames();

      expect(result).toBe(true);
    });

    it('returns false when #frames-r does not exist', () => {
      document.body.innerHTML = '<div>No frames</div>';

      const result = showRightFrames();

      expect(result).toBe(false);
    });

    it('animates #frames-r to visible position', () => {
      document.body.innerHTML = '<div id="frames-r" style="right: -100%;"></div>';
      const rafSpy = vi.spyOn(window, 'requestAnimationFrame').mockImplementation((cb) => {
        // Simulate animation completing immediately
        cb(performance.now() + 1000);
        return 1;
      });

      showRightFrames();

      // Verify that animation was initiated via requestAnimationFrame
      expect(rafSpy).toHaveBeenCalled();
      rafSpy.mockRestore();
    });

    it('loads roUrl in upper-right frame when provided', () => {
      const mockRoFrame = { location: { href: '' } };
      (global as any).top = {
        frames: {
          ro: mockRoFrame
        }
      };
      document.body.innerHTML = '<div id="frames-r"></div>';

      showRightFrames('edit_word.php?id=1');

      expect(mockRoFrame.location.href).toBe('edit_word.php?id=1');
    });

    it('loads ruUrl in lower-right frame when provided', () => {
      const mockRuFrame = { location: { href: '' } };
      (global as any).top = {
        frames: {
          ru: mockRuFrame
        }
      };
      document.body.innerHTML = '<div id="frames-r"></div>';

      showRightFrames(undefined, 'dictionary.php');

      expect(mockRuFrame.location.href).toBe('dictionary.php');
    });

    it('loads both frames when both URLs provided', () => {
      const mockRoFrame = { location: { href: '' } };
      const mockRuFrame = { location: { href: '' } };
      (global as any).top = {
        frames: {
          ro: mockRoFrame,
          ru: mockRuFrame
        }
      };
      document.body.innerHTML = '<div id="frames-r"></div>';

      showRightFrames('upper.php', 'lower.php');

      expect(mockRoFrame.location.href).toBe('upper.php');
      expect(mockRuFrame.location.href).toBe('lower.php');
    });

    it('does not modify frames when URLs are undefined', () => {
      const mockRoFrame = { location: { href: 'original.php' } };
      (global as any).top = {
        frames: {
          ro: mockRoFrame
        }
      };
      document.body.innerHTML = '<div id="frames-r"></div>';

      showRightFrames();

      expect(mockRoFrame.location.href).toBe('original.php');
    });
  });

  // ===========================================================================
  // hideRightFrames Tests
  // ===========================================================================

  describe('hideRightFrames', () => {
    it('returns true when #frames-r exists', () => {
      document.body.innerHTML = '<div id="frames-r" style="right: 5px;"></div>';

      const result = hideRightFrames();

      expect(result).toBe(true);
    });

    it('returns false when #frames-r does not exist', () => {
      document.body.innerHTML = '<div>No frames</div>';

      const result = hideRightFrames();

      expect(result).toBe(false);
    });

    it('animates #frames-r to hidden position', () => {
      document.body.innerHTML = '<div id="frames-r" style="right: 5px;"></div>';
      const rafSpy = vi.spyOn(window, 'requestAnimationFrame').mockImplementation((cb) => {
        // Simulate animation completing immediately
        cb(performance.now() + 1000);
        return 1;
      });

      hideRightFrames();

      // Verify that animation was initiated via requestAnimationFrame
      expect(rafSpy).toHaveBeenCalled();
      rafSpy.mockRestore();
    });
  });

  // ===========================================================================
  // cleanupRightFrames Tests
  // ===========================================================================

  describe('cleanupRightFrames', () => {
    it('sets timeout to click on frames-r after 800ms', () => {
      const mockFramesR = {
        click: vi.fn()
      };
      (global as any).parent = {
        document: {
          getElementById: vi.fn((id: string) => {
            if (id === 'frames-r') return mockFramesR;
            if (id === 'frame-l') return { focus: vi.fn() };
            return null;
          })
        },
        setTimeout: vi.fn((fn: () => void, delay: number) => setTimeout(fn, delay))
      };

      cleanupRightFrames();

      expect((global as any).parent.setTimeout).toHaveBeenCalledWith(
        expect.any(Function),
        800
      );

      vi.advanceTimersByTime(800);
      expect(mockFramesR.click).toHaveBeenCalled();
    });

    it('focuses frame-l element', () => {
      const mockFrameL = { focus: vi.fn() };
      (global as any).parent = {
        document: {
          getElementById: vi.fn((id: string) => {
            if (id === 'frame-l') return mockFrameL;
            return null;
          })
        },
        setTimeout: vi.fn()
      };

      cleanupRightFrames();

      expect(mockFrameL.focus).toHaveBeenCalled();
    });

    it('calls cClick after 100ms', () => {
      (global as any).parent = {
        document: {
          getElementById: vi.fn(() => null)
        },
        setTimeout: vi.fn((fn: () => void, delay: number) => setTimeout(fn, delay))
      };

      cleanupRightFrames();

      expect((global as any).parent.setTimeout).toHaveBeenCalledWith(
        cClick,
        100
      );
    });

    it('handles missing frames-r gracefully', () => {
      (global as any).parent = {
        document: {
          getElementById: vi.fn(() => null)
        },
        setTimeout: vi.fn((fn: () => void, delay: number) => setTimeout(fn, delay))
      };

      expect(() => cleanupRightFrames()).not.toThrow();

      vi.advanceTimersByTime(800);
      // Should not throw even when frames-r is null
    });

    it('handles missing frame-l gracefully', () => {
      (global as any).parent = {
        document: {
          getElementById: vi.fn(() => null)
        },
        setTimeout: vi.fn()
      };

      expect(() => cleanupRightFrames()).not.toThrow();
    });
  });

  // ===========================================================================
  // successSound Tests
  // ===========================================================================

  describe('successSound', () => {
    it('pauses both sounds before playing success', () => {
      const mockSuccessSound = {
        pause: vi.fn(),
        play: vi.fn().mockResolvedValue(undefined)
      };
      const mockFailureSound = {
        pause: vi.fn()
      };

      document.body.innerHTML = `
        <audio id="success_sound"></audio>
        <audio id="failure_sound"></audio>
      `;

      // Replace elements with mocks
      vi.spyOn(document, 'getElementById').mockImplementation((id: string) => {
        if (id === 'success_sound') return mockSuccessSound as unknown as HTMLElement;
        if (id === 'failure_sound') return mockFailureSound as unknown as HTMLElement;
        return null;
      });

      successSound();

      expect(mockSuccessSound.pause).toHaveBeenCalled();
      expect(mockFailureSound.pause).toHaveBeenCalled();
    });

    it('plays success sound and returns promise', () => {
      const mockSuccessSound = {
        pause: vi.fn(),
        play: vi.fn().mockResolvedValue(undefined)
      };
      const mockFailureSound = {
        pause: vi.fn()
      };

      vi.spyOn(document, 'getElementById').mockImplementation((id: string) => {
        if (id === 'success_sound') return mockSuccessSound as unknown as HTMLElement;
        if (id === 'failure_sound') return mockFailureSound as unknown as HTMLElement;
        return null;
      });

      const result = successSound();

      expect(mockSuccessSound.play).toHaveBeenCalled();
      expect(result).toBeInstanceOf(Promise);
    });

    it('handles missing audio elements gracefully', () => {
      vi.spyOn(document, 'getElementById').mockReturnValue(null);

      expect(() => successSound()).not.toThrow();
    });
  });

  // ===========================================================================
  // failureSound Tests
  // ===========================================================================

  describe('failureSound', () => {
    it('pauses both sounds before playing failure', () => {
      const mockSuccessSound = {
        pause: vi.fn()
      };
      const mockFailureSound = {
        pause: vi.fn(),
        play: vi.fn().mockResolvedValue(undefined)
      };

      vi.spyOn(document, 'getElementById').mockImplementation((id: string) => {
        if (id === 'success_sound') return mockSuccessSound as unknown as HTMLElement;
        if (id === 'failure_sound') return mockFailureSound as unknown as HTMLElement;
        return null;
      });

      failureSound();

      expect(mockSuccessSound.pause).toHaveBeenCalled();
      expect(mockFailureSound.pause).toHaveBeenCalled();
    });

    it('plays failure sound and returns promise', () => {
      const mockSuccessSound = {
        pause: vi.fn()
      };
      const mockFailureSound = {
        pause: vi.fn(),
        play: vi.fn().mockResolvedValue(undefined)
      };

      vi.spyOn(document, 'getElementById').mockImplementation((id: string) => {
        if (id === 'success_sound') return mockSuccessSound as unknown as HTMLElement;
        if (id === 'failure_sound') return mockFailureSound as unknown as HTMLElement;
        return null;
      });

      const result = failureSound();

      expect(mockFailureSound.play).toHaveBeenCalled();
      expect(result).toBeInstanceOf(Promise);
    });

    it('handles missing audio elements gracefully', () => {
      vi.spyOn(document, 'getElementById').mockReturnValue(null);

      expect(() => failureSound()).not.toThrow();
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('show and hide frames work together', () => {
      document.body.innerHTML = '<div id="frames-r" style="right: -100%;"></div>';

      const showResult = showRightFrames();
      expect(showResult).toBe(true);

      const hideResult = hideRightFrames();
      expect(hideResult).toBe(true);
    });

    it('frame operations work without errors when frames container missing', () => {
      document.body.innerHTML = '<div>Empty page</div>';
      // Setup mock frames to avoid error accessing undefined
      const mockRoFrame = { location: { href: '' } };
      const mockRuFrame = { location: { href: '' } };
      (global as any).top = {
        frames: {
          ro: mockRoFrame,
          ru: mockRuFrame
        }
      };

      expect(showRightFrames('url1', 'url2')).toBe(false);
      expect(hideRightFrames()).toBe(false);
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('showRightFrames handles URL with query parameters', () => {
      const mockRoFrame = { location: { href: '' } };
      (global as any).top = {
        frames: {
          ro: mockRoFrame
        }
      };
      document.body.innerHTML = '<div id="frames-r"></div>';

      showRightFrames('edit.php?id=1&action=save&data=test%20value');

      expect(mockRoFrame.location.href).toBe('edit.php?id=1&action=save&data=test%20value');
    });

    it('showRightFrames handles empty string URLs', () => {
      const mockRoFrame = { location: { href: 'original.php' } };
      (global as any).top = {
        frames: {
          ro: mockRoFrame
        }
      };
      document.body.innerHTML = '<div id="frames-r"></div>';

      // Empty string is still a valid URL (goes to current page)
      showRightFrames('');

      expect(mockRoFrame.location.href).toBe('');
    });

    it('sound functions handle play() rejection', async () => {
      const mockSuccessSound = {
        pause: vi.fn(),
        play: vi.fn().mockRejectedValue(new Error('Autoplay blocked'))
      };
      const mockFailureSound = {
        pause: vi.fn()
      };

      vi.spyOn(document, 'getElementById').mockImplementation((id: string) => {
        if (id === 'success_sound') return mockSuccessSound as unknown as HTMLElement;
        if (id === 'failure_sound') return mockFailureSound as unknown as HTMLElement;
        return null;
      });

      // Should not throw, just reject the promise
      const result = successSound();

      await expect(result).rejects.toThrow('Autoplay blocked');
    });

    it('cleanupRightFrames uses correct timing sequence', () => {
      const calls: { fn: string; delay: number }[] = [];
      (global as any).parent = {
        document: {
          getElementById: vi.fn(() => ({ focus: vi.fn(), click: vi.fn() }))
        },
        setTimeout: vi.fn((fn: () => void, delay: number) => {
          if (delay === 800) calls.push({ fn: 'frames-r click', delay });
          if (delay === 100) calls.push({ fn: 'cClick', delay });
          return setTimeout(fn, delay);
        })
      };

      cleanupRightFrames();

      expect(calls).toContainEqual({ fn: 'frames-r click', delay: 800 });
      expect(calls).toContainEqual({ fn: 'cClick', delay: 100 });
    });
  });
});

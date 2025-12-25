/**
 * Tests for elapsed_timer.ts - Elapsed time counter for test sessions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { startElapsedTimer } from '../../../src/frontend/js/modules/review/utils/elapsed_timer';

describe('elapsed_timer.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    document.body.innerHTML = '';
  });

  describe('startElapsedTimer', () => {
    it('displays initial time of 00:00 when serverNow equals serverStart', () => {
      document.body.innerHTML = '<span id="timer"></span>';
      const now = Math.floor(Date.now() / 1000);

      startElapsedTimer(now, now, 'timer', 0);

      expect(document.getElementById('timer')!.textContent).toBe('00:00');
    });

    it('displays elapsed time based on server start time', () => {
      document.body.innerHTML = '<span id="timer"></span>';
      const now = Math.floor(Date.now() / 1000);
      const startTime = now - 65; // 1 minute 5 seconds ago

      startElapsedTimer(now, startTime, 'timer', 0);

      expect(document.getElementById('timer')!.textContent).toBe('01:05');
    });

    it('displays hours when elapsed time exceeds 60 minutes', () => {
      document.body.innerHTML = '<span id="timer"></span>';
      const now = Math.floor(Date.now() / 1000);
      const startTime = now - 3665; // 1 hour, 1 minute, 5 seconds ago

      startElapsedTimer(now, startTime, 'timer', 0);

      expect(document.getElementById('timer')!.textContent).toBe('01:01:05');
    });

    it('pads single digit values with zeros', () => {
      document.body.innerHTML = '<span id="timer"></span>';
      const now = Math.floor(Date.now() / 1000);
      const startTime = now - 5; // 5 seconds ago

      startElapsedTimer(now, startTime, 'timer', 0);

      expect(document.getElementById('timer')!.textContent).toBe('00:05');
    });

    it('updates every second when dontRun is 0', () => {
      document.body.innerHTML = '<span id="timer"></span>';
      const now = Math.floor(Date.now() / 1000);

      startElapsedTimer(now, now, 'timer', 0);

      expect(document.getElementById('timer')!.textContent).toBe('00:00');

      // Advance time by 1 second
      vi.advanceTimersByTime(1000);
      expect(document.getElementById('timer')!.textContent).toBe('00:01');

      // Advance another 5 seconds
      vi.advanceTimersByTime(5000);
      expect(document.getElementById('timer')!.textContent).toBe('00:06');
    });

    it('does not update when dontRun is truthy', () => {
      document.body.innerHTML = '<span id="timer"></span>';
      const now = Math.floor(Date.now() / 1000);

      startElapsedTimer(now, now, 'timer', 1);

      expect(document.getElementById('timer')!.textContent).toBe('00:00');

      // Advance time by 5 seconds
      vi.advanceTimersByTime(5000);

      // Should still show initial time
      expect(document.getElementById('timer')!.textContent).toBe('00:00');
    });

    it('handles missing element gracefully', () => {
      document.body.innerHTML = '';
      const now = Math.floor(Date.now() / 1000);

      // Should not throw
      expect(() => {
        startElapsedTimer(now, now, 'nonexistent', 0);
      }).not.toThrow();
    });

    it('adjusts serverStart if it is in the future', () => {
      document.body.innerHTML = '<span id="timer"></span>';
      const now = Math.floor(Date.now() / 1000);
      const futureStart = now + 100; // Start time in the future

      startElapsedTimer(now, futureStart, 'timer', 0);

      // Should show 00:00 since start time is adjusted to now
      expect(document.getElementById('timer')!.textContent).toBe('00:00');
    });

    it('displays double-digit hours correctly', () => {
      document.body.innerHTML = '<span id="timer"></span>';
      const now = Math.floor(Date.now() / 1000);
      const startTime = now - (12 * 3600 + 34 * 60 + 56); // 12:34:56

      startElapsedTimer(now, startTime, 'timer', 0);

      expect(document.getElementById('timer')!.textContent).toBe('12:34:56');
    });

    it('correctly handles 59 minutes 59 seconds', () => {
      document.body.innerHTML = '<span id="timer"></span>';
      const now = Math.floor(Date.now() / 1000);
      const startTime = now - (59 * 60 + 59); // 59:59

      startElapsedTimer(now, startTime, 'timer', 0);

      expect(document.getElementById('timer')!.textContent).toBe('59:59');

      // Advance 1 second - should roll over to hour display
      vi.advanceTimersByTime(1000);
      expect(document.getElementById('timer')!.textContent).toBe('01:00:00');
    });

    it('continues counting accurately over time', () => {
      document.body.innerHTML = '<span id="timer"></span>';
      const now = Math.floor(Date.now() / 1000);

      startElapsedTimer(now, now, 'timer', 0);

      // Advance 2 minutes and 30 seconds
      vi.advanceTimersByTime(150000);
      expect(document.getElementById('timer')!.textContent).toBe('02:30');
    });
  });
});

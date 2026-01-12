/**
 * Tests for audio_feedback.ts - Audio feedback utility functions.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { successSound, failureSound } from '../../../src/frontend/js/shared/utils/audio_feedback';

describe('audio_feedback.ts', () => {
  let mockSuccessAudio: HTMLAudioElement;
  let mockFailureAudio: HTMLAudioElement;

  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';

    // Create mock audio elements with mocked methods
    mockSuccessAudio = document.createElement('audio');
    mockSuccessAudio.id = 'success_sound';
    mockSuccessAudio.pause = vi.fn();
    mockSuccessAudio.play = vi.fn().mockResolvedValue(undefined);

    mockFailureAudio = document.createElement('audio');
    mockFailureAudio.id = 'failure_sound';
    mockFailureAudio.pause = vi.fn();
    mockFailureAudio.play = vi.fn().mockResolvedValue(undefined);
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  describe('successSound', () => {
    it('plays success sound when both audio elements exist', async () => {
      document.body.appendChild(mockSuccessAudio);
      document.body.appendChild(mockFailureAudio);

      await successSound();

      expect(mockSuccessAudio.pause).toHaveBeenCalled();
      expect(mockFailureAudio.pause).toHaveBeenCalled();
      expect(mockSuccessAudio.play).toHaveBeenCalled();
    });

    it('pauses failure audio before playing success', async () => {
      document.body.appendChild(mockSuccessAudio);
      document.body.appendChild(mockFailureAudio);
      const callOrder: string[] = [];
      mockSuccessAudio.pause = vi.fn(() => callOrder.push('success.pause'));
      mockFailureAudio.pause = vi.fn(() => callOrder.push('failure.pause'));
      mockSuccessAudio.play = vi.fn(() => {
        callOrder.push('success.play');
        return Promise.resolve();
      });

      await successSound();

      expect(callOrder).toContain('failure.pause');
      expect(callOrder.indexOf('failure.pause')).toBeLessThan(callOrder.indexOf('success.play'));
    });

    it('returns resolved promise when success audio element exists', async () => {
      document.body.appendChild(mockSuccessAudio);

      const result = successSound();

      await expect(result).resolves.toBeUndefined();
    });

    it('returns resolved promise when success audio element does not exist', async () => {
      // No audio elements in DOM

      const result = successSound();

      await expect(result).resolves.toBeUndefined();
    });

    it('handles only success audio element existing', async () => {
      document.body.appendChild(mockSuccessAudio);

      await successSound();

      expect(mockSuccessAudio.pause).toHaveBeenCalled();
      expect(mockSuccessAudio.play).toHaveBeenCalled();
    });

    it('handles only failure audio element existing', async () => {
      document.body.appendChild(mockFailureAudio);

      const result = successSound();

      expect(mockFailureAudio.pause).toHaveBeenCalled();
      await expect(result).resolves.toBeUndefined();
    });

    it('propagates play promise rejection', async () => {
      const playError = new Error('Playback failed');
      mockSuccessAudio.play = vi.fn().mockRejectedValue(playError);
      document.body.appendChild(mockSuccessAudio);

      await expect(successSound()).rejects.toThrow('Playback failed');
    });
  });

  describe('failureSound', () => {
    it('plays failure sound when both audio elements exist', async () => {
      document.body.appendChild(mockSuccessAudio);
      document.body.appendChild(mockFailureAudio);

      await failureSound();

      expect(mockSuccessAudio.pause).toHaveBeenCalled();
      expect(mockFailureAudio.pause).toHaveBeenCalled();
      expect(mockFailureAudio.play).toHaveBeenCalled();
    });

    it('pauses success audio before playing failure', async () => {
      document.body.appendChild(mockSuccessAudio);
      document.body.appendChild(mockFailureAudio);
      const callOrder: string[] = [];
      mockSuccessAudio.pause = vi.fn(() => callOrder.push('success.pause'));
      mockFailureAudio.pause = vi.fn(() => callOrder.push('failure.pause'));
      mockFailureAudio.play = vi.fn(() => {
        callOrder.push('failure.play');
        return Promise.resolve();
      });

      await failureSound();

      expect(callOrder).toContain('success.pause');
      expect(callOrder.indexOf('success.pause')).toBeLessThan(callOrder.indexOf('failure.play'));
    });

    it('returns resolved promise when failure audio element exists', async () => {
      document.body.appendChild(mockFailureAudio);

      const result = failureSound();

      await expect(result).resolves.toBeUndefined();
    });

    it('returns resolved promise when failure audio element does not exist', async () => {
      // No audio elements in DOM

      const result = failureSound();

      await expect(result).resolves.toBeUndefined();
    });

    it('handles only failure audio element existing', async () => {
      document.body.appendChild(mockFailureAudio);

      await failureSound();

      expect(mockFailureAudio.pause).toHaveBeenCalled();
      expect(mockFailureAudio.play).toHaveBeenCalled();
    });

    it('handles only success audio element existing', async () => {
      document.body.appendChild(mockSuccessAudio);

      const result = failureSound();

      expect(mockSuccessAudio.pause).toHaveBeenCalled();
      await expect(result).resolves.toBeUndefined();
    });

    it('propagates play promise rejection', async () => {
      const playError = new Error('Playback failed');
      mockFailureAudio.play = vi.fn().mockRejectedValue(playError);
      document.body.appendChild(mockFailureAudio);

      await expect(failureSound()).rejects.toThrow('Playback failed');
    });
  });

  describe('interaction between success and failure sounds', () => {
    it('calling success then failure stops success and plays failure', async () => {
      document.body.appendChild(mockSuccessAudio);
      document.body.appendChild(mockFailureAudio);

      await successSound();
      vi.clearAllMocks();

      await failureSound();

      expect(mockSuccessAudio.pause).toHaveBeenCalled();
      expect(mockFailureAudio.play).toHaveBeenCalled();
    });

    it('calling failure then success stops failure and plays success', async () => {
      document.body.appendChild(mockSuccessAudio);
      document.body.appendChild(mockFailureAudio);

      await failureSound();
      vi.clearAllMocks();

      await successSound();

      expect(mockFailureAudio.pause).toHaveBeenCalled();
      expect(mockSuccessAudio.play).toHaveBeenCalled();
    });
  });
});

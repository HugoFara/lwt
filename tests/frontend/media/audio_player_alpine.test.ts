/**
 * Tests for audio_player_alpine.ts - Alpine.js audio player component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock Alpine.js
vi.mock('alpinejs', () => ({
  default: {
    data: vi.fn()
  }
}));

// Mock saveSetting
vi.mock('../../../src/frontend/js/shared/utils/ajax_utilities', () => ({
  saveSetting: vi.fn()
}));

import Alpine from 'alpinejs';
import { audioPlayerData, initAudioPlayerAlpine, PLAYBACK_RATES, SKIP_OPTIONS } from '../../../src/frontend/js/media/audio_player_alpine';
import { saveSetting } from '../../../src/frontend/js/shared/utils/ajax_utilities';

describe('audio_player_alpine.ts', () => {
  let mockAudio: HTMLAudioElement & { _eventListeners: Record<string, ((...args: unknown[]) => unknown)[]> };

  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();

    // Create a mock audio element
    mockAudio = document.createElement('audio') as typeof mockAudio;
    mockAudio._eventListeners = {};

    // Override addEventListener to track listeners
    const originalAddEventListener = mockAudio.addEventListener.bind(mockAudio);
    mockAudio.addEventListener = vi.fn((event: string, handler: (...args: unknown[]) => unknown, options?: unknown) => {
      if (!mockAudio._eventListeners[event]) {
        mockAudio._eventListeners[event] = [];
      }
      mockAudio._eventListeners[event].push(handler);
      return originalAddEventListener(event, handler as EventListener, options as AddEventListenerOptions);
    }) as typeof mockAudio.addEventListener;

    // Mock audio properties
    Object.defineProperty(mockAudio, 'duration', { value: 120, writable: true, configurable: true });
    Object.defineProperty(mockAudio, 'currentTime', { value: 0, writable: true, configurable: true });
    Object.defineProperty(mockAudio, 'volume', { value: 1, writable: true, configurable: true });
    Object.defineProperty(mockAudio, 'muted', { value: false, writable: true, configurable: true });
    Object.defineProperty(mockAudio, 'playbackRate', { value: 1, writable: true, configurable: true });
    Object.defineProperty(mockAudio, 'src', { value: '', writable: true, configurable: true });
    Object.defineProperty(mockAudio, 'loop', { value: false, writable: true, configurable: true });

    mockAudio.play = vi.fn().mockResolvedValue(undefined);
    mockAudio.pause = vi.fn();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // Constants Tests
  // ===========================================================================

  describe('constants', () => {
    it('PLAYBACK_RATES contains expected values', () => {
      expect(PLAYBACK_RATES).toContain(0.5);
      expect(PLAYBACK_RATES).toContain(1.0);
      expect(PLAYBACK_RATES).toContain(1.5);
    });

    it('PLAYBACK_RATES is sorted ascending', () => {
      const sorted = [...PLAYBACK_RATES].sort((a, b) => a - b);
      expect(PLAYBACK_RATES).toEqual(sorted);
    });

    it('SKIP_OPTIONS contains expected values', () => {
      expect(SKIP_OPTIONS).toContain(1);
      expect(SKIP_OPTIONS).toContain(5);
      expect(SKIP_OPTIONS).toContain(30);
    });
  });

  // ===========================================================================
  // audioPlayerData Factory Tests
  // ===========================================================================

  describe('audioPlayerData', () => {
    it('creates component with default values', () => {
      const component = audioPlayerData();

      expect(component.isPlaying).toBe(false);
      expect(component.isMuted).toBe(false);
      expect(component.currentTime).toBe(0);
      expect(component.duration).toBe(0);
      expect(component.volume).toBe(1);
      expect(component.playbackRate).toBe(1.0);
      expect(component.repeatMode).toBe(false);
      expect(component.skipSeconds).toBe(5);
      expect(component.isLoaded).toBe(false);
      expect(component.audio).toBeNull();
    });
  });

  // ===========================================================================
  // Computed Properties Tests
  // ===========================================================================

  describe('computed properties', () => {
    it('progressPercent returns 0 when duration is 0', () => {
      const component = audioPlayerData();

      expect(component.progressPercent).toBe(0);
    });

    it('progressPercent calculates correct percentage', () => {
      const component = audioPlayerData();
      component.duration = 100;
      component.currentTime = 25;

      expect(component.progressPercent).toBe(25);
    });

    it('progressPercent handles negative duration', () => {
      const component = audioPlayerData();
      component.duration = -10;

      expect(component.progressPercent).toBe(0);
    });

    it('currentTimeFormatted formats time correctly', () => {
      const component = audioPlayerData();
      component.currentTime = 65;

      expect(component.currentTimeFormatted).toBe('1:05');
    });

    it('durationFormatted formats time correctly', () => {
      const component = audioPlayerData();
      component.duration = 125;

      expect(component.durationFormatted).toBe('2:05');
    });

    it('playbackRateFormatted shows rate with x suffix', () => {
      const component = audioPlayerData();
      component.playbackRate = 1.5;

      expect(component.playbackRateFormatted).toBe('1.5x');
    });
  });

  // ===========================================================================
  // formatTime Tests
  // ===========================================================================

  describe('formatTime', () => {
    it('formats 0 seconds correctly', () => {
      const component = audioPlayerData();

      expect(component.formatTime(0)).toBe('0:00');
    });

    it('formats seconds under a minute', () => {
      const component = audioPlayerData();

      expect(component.formatTime(45)).toBe('0:45');
    });

    it('formats minutes and seconds', () => {
      const component = audioPlayerData();

      expect(component.formatTime(125)).toBe('2:05');
    });

    it('pads seconds with zero', () => {
      const component = audioPlayerData();

      expect(component.formatTime(61)).toBe('1:01');
    });

    it('handles NaN', () => {
      const component = audioPlayerData();

      expect(component.formatTime(NaN)).toBe('0:00');
    });

    it('handles Infinity', () => {
      const component = audioPlayerData();

      expect(component.formatTime(Infinity)).toBe('0:00');
    });
  });

  // ===========================================================================
  // Playback Controls Tests
  // ===========================================================================

  describe('playback controls', () => {
    it('play calls audio.play', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;

      component.play();

      expect(mockAudio.play).toHaveBeenCalled();
    });

    it('play does nothing if audio is null', () => {
      const component = audioPlayerData();

      expect(() => component.play()).not.toThrow();
    });

    it('pause calls audio.pause', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;

      component.pause();

      expect(mockAudio.pause).toHaveBeenCalled();
    });

    it('pause does nothing if audio is null', () => {
      const component = audioPlayerData();

      expect(() => component.pause()).not.toThrow();
    });

    it('togglePlay pauses when playing', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.isPlaying = true;

      component.togglePlay();

      expect(mockAudio.pause).toHaveBeenCalled();
    });

    it('togglePlay plays when paused', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.isPlaying = false;

      component.togglePlay();

      expect(mockAudio.play).toHaveBeenCalled();
    });

    it('stop pauses and resets currentTime', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      (mockAudio as { currentTime: number }).currentTime = 50;

      component.stop();

      expect(mockAudio.pause).toHaveBeenCalled();
      expect(mockAudio.currentTime).toBe(0);
    });

    it('stop does nothing if audio is null', () => {
      const component = audioPlayerData();

      expect(() => component.stop()).not.toThrow();
    });
  });

  // ===========================================================================
  // Seeking Tests
  // ===========================================================================

  describe('seeking', () => {
    it('seekTo sets currentTime based on percent', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.duration = 100;

      component.seekTo(50);

      expect(mockAudio.currentTime).toBe(50);
    });

    it('seekTo does nothing if audio is null', () => {
      const component = audioPlayerData();
      component.duration = 100;

      expect(() => component.seekTo(50)).not.toThrow();
    });

    it('seekTo does nothing if duration is 0', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.duration = 0;

      component.seekTo(50);

      expect(mockAudio.currentTime).toBe(0);
    });

    it('seekFromEvent calculates percent from click position', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.duration = 100;

      const target = document.createElement('div');
      target.getBoundingClientRect = () => ({
        left: 0,
        width: 200,
        top: 0,
        right: 200,
        bottom: 20,
        height: 20,
        x: 0,
        y: 0,
        toJSON: () => ({})
      });

      const event = {
        currentTarget: target,
        clientX: 100 // 50% of 200
      } as unknown as MouseEvent;

      component.seekFromEvent(event);

      expect(mockAudio.currentTime).toBe(50);
    });

    it('seekFromEvent clamps to 0-100', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.duration = 100;

      const target = document.createElement('div');
      target.getBoundingClientRect = () => ({
        left: 0,
        width: 200,
        top: 0,
        right: 200,
        bottom: 20,
        height: 20,
        x: 0,
        y: 0,
        toJSON: () => ({})
      });

      // Click beyond the bar
      const event = {
        currentTarget: target,
        clientX: 300
      } as unknown as MouseEvent;

      component.seekFromEvent(event);

      expect(mockAudio.currentTime).toBe(100);
    });

    it('skipBackward decreases currentTime', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.skipSeconds = 5;
      (mockAudio as { currentTime: number }).currentTime = 20;

      component.skipBackward();

      expect(mockAudio.currentTime).toBe(15);
    });

    it('skipBackward does not go below 0', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.skipSeconds = 10;
      (mockAudio as { currentTime: number }).currentTime = 5;

      component.skipBackward();

      expect(mockAudio.currentTime).toBe(0);
    });

    it('skipForward increases currentTime', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.duration = 100;
      component.skipSeconds = 5;
      (mockAudio as { currentTime: number }).currentTime = 20;

      component.skipForward();

      expect(mockAudio.currentTime).toBe(25);
    });

    it('skipForward does not exceed duration', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.duration = 100;
      component.skipSeconds = 10;
      (mockAudio as { currentTime: number }).currentTime = 95;

      component.skipForward();

      expect(mockAudio.currentTime).toBe(100);
    });
  });

  // ===========================================================================
  // Volume Tests
  // ===========================================================================

  describe('volume', () => {
    it('setVolume sets audio volume', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;

      component.setVolume(0.5);

      expect(mockAudio.volume).toBe(0.5);
    });

    it('setVolume clamps to 0-1', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;

      component.setVolume(1.5);
      expect(mockAudio.volume).toBe(1);

      component.setVolume(-0.5);
      expect(mockAudio.volume).toBe(0);
    });

    it('setVolumeFromEvent calculates volume from click', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;

      const target = document.createElement('div');
      target.getBoundingClientRect = () => ({
        left: 0,
        width: 100,
        top: 0,
        right: 100,
        bottom: 20,
        height: 20,
        x: 0,
        y: 0,
        toJSON: () => ({})
      });

      const event = {
        currentTarget: target,
        clientX: 75
      } as unknown as MouseEvent;

      component.setVolumeFromEvent(event);

      expect(mockAudio.volume).toBe(0.75);
    });

    it('toggleMute toggles muted state', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      (mockAudio as { muted: boolean }).muted = false;

      component.toggleMute();

      expect(mockAudio.muted).toBe(true);
    });
  });

  // ===========================================================================
  // Playback Rate Tests
  // ===========================================================================

  describe('playback rate', () => {
    it('setPlaybackRate sets rate within bounds', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;

      component.setPlaybackRate(1.2);

      expect(component.playbackRate).toBe(1.2);
      expect(mockAudio.playbackRate).toBe(1.2);
    });

    it('setPlaybackRate clamps minimum', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;

      component.setPlaybackRate(0.3);

      expect(component.playbackRate).toBe(0.5);
    });

    it('setPlaybackRate clamps maximum', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;

      component.setPlaybackRate(2.0);

      expect(component.playbackRate).toBe(1.5);
    });

    it('setPlaybackRate saves setting', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;

      component.setPlaybackRate(1.0);

      expect(saveSetting).toHaveBeenCalledWith('currentplaybackrate', '10');
    });

    it('slower decreases rate to previous step', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.playbackRate = 1.0;

      component.slower();

      expect(component.playbackRate).toBe(0.9);
    });

    it('slower does not go below minimum', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.playbackRate = 0.5;

      component.slower();

      expect(component.playbackRate).toBe(0.5);
    });

    it('faster increases rate to next step', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.playbackRate = 1.0;

      component.faster();

      expect(component.playbackRate).toBe(1.1);
    });

    it('faster does not exceed maximum', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.playbackRate = 1.5;

      component.faster();

      expect(component.playbackRate).toBe(1.5);
    });

    it('resetSpeed sets rate to 1.0', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.playbackRate = 1.3;

      component.resetSpeed();

      expect(component.playbackRate).toBe(1.0);
    });
  });

  // ===========================================================================
  // Repeat Mode Tests
  // ===========================================================================

  describe('repeat mode', () => {
    it('toggleRepeat toggles repeatMode', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.repeatMode = false;

      component.toggleRepeat();

      expect(component.repeatMode).toBe(true);
      expect(mockAudio.loop).toBe(true);
    });

    it('toggleRepeat saves setting', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;

      component.toggleRepeat();

      expect(saveSetting).toHaveBeenCalledWith('currentplayerrepeatmode', '1');
    });

    it('toggleRepeat saves 0 when disabling', () => {
      const component = audioPlayerData();
      component.audio = mockAudio;
      component.repeatMode = true;

      component.toggleRepeat();

      expect(saveSetting).toHaveBeenCalledWith('currentplayerrepeatmode', '0');
    });
  });

  // ===========================================================================
  // Skip Seconds Tests
  // ===========================================================================

  describe('skip seconds', () => {
    it('setSkipSeconds updates skipSeconds', () => {
      const component = audioPlayerData();

      component.setSkipSeconds(10);

      expect(component.skipSeconds).toBe(10);
    });

    it('setSkipSeconds saves setting', () => {
      const component = audioPlayerData();

      component.setSkipSeconds(15);

      expect(saveSetting).toHaveBeenCalledWith('currentplayerseconds', '15');
    });
  });

  // ===========================================================================
  // Init Tests
  // ===========================================================================

  describe('init', () => {
    it('logs error if container not found', () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      const component = audioPlayerData();

      // Simulate Alpine context without $el
      const context = { ...component, $el: undefined };
      context.init.call(context);

      expect(consoleSpy).toHaveBeenCalledWith('Audio player container not found');
    });

    it('logs error if audio element not found', () => {
      const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      const component = audioPlayerData();

      const container = document.createElement('div');
      // No audio element inside

      const context = { ...component, $el: container };
      context.init.call(context);

      expect(consoleSpy).toHaveBeenCalledWith('Audio element not found');
    });

    it('loads config from data attribute', () => {
      const component = audioPlayerData();

      const container = document.createElement('div');
      container.innerHTML = `
        <audio></audio>
        <script type="application/json" data-audio-config>
          {"repeatMode": true, "skipSeconds": 10, "playbackRate": 12}
        </script>
      `;

      const context = { ...component, $el: container };
      context.init.call(context);

      expect(context.repeatMode).toBe(true);
      expect(context.skipSeconds).toBe(10);
      expect(context.playbackRate).toBe(1.2);
    });

    it('sets up audio event listeners', () => {
      const component = audioPlayerData();

      const container = document.createElement('div');
      container.appendChild(mockAudio);

      const context = { ...component, $el: container };
      context.init.call(context);

      expect(mockAudio.addEventListener).toHaveBeenCalledWith('loadedmetadata', expect.any(Function));
      expect(mockAudio.addEventListener).toHaveBeenCalledWith('timeupdate', expect.any(Function));
      expect(mockAudio.addEventListener).toHaveBeenCalledWith('play', expect.any(Function));
      expect(mockAudio.addEventListener).toHaveBeenCalledWith('pause', expect.any(Function));
      expect(mockAudio.addEventListener).toHaveBeenCalledWith('ended', expect.any(Function));
      expect(mockAudio.addEventListener).toHaveBeenCalledWith('volumechange', expect.any(Function));
    });
  });

  // ===========================================================================
  // initAudioPlayerAlpine Tests
  // ===========================================================================

  describe('initAudioPlayerAlpine', () => {
    it('registers audioPlayer component with Alpine', () => {
      initAudioPlayerAlpine();

      expect(Alpine.data).toHaveBeenCalledWith('audioPlayer', audioPlayerData);
    });
  });

  // ===========================================================================
  // Window Exposure Tests
  // ===========================================================================

  describe('window exposure', () => {
    it('exposes audioPlayerData on window', () => {
      expect(typeof window.audioPlayerData).toBe('function');
    });
  });

  // ===========================================================================
  // Destroy Tests
  // ===========================================================================

  describe('destroy', () => {
    it('exists and is callable', () => {
      const component = audioPlayerData();

      expect(typeof component.destroy).toBe('function');
      expect(() => component.destroy()).not.toThrow();
    });
  });
});

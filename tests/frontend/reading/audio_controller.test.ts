/**
 * Tests for audio_controller.ts - Legacy jPlayer audio controller
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import { lwt_audio_controller } from '../../../src/frontend/js/reading/audio_controller';

// Make jQuery available globally
(global as any).$ = $;
(global as any).jQuery = $;

// Mock the ajax_utilities module
vi.mock('../../../src/frontend/js/core/ajax_utilities', () => ({
  do_ajax_save_setting: vi.fn()
}));

import { do_ajax_save_setting } from '../../../src/frontend/js/core/ajax_utilities';

describe('audio_controller.ts (legacy jPlayer)', () => {
  let jPlayerMock: any;

  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();

    // Setup jPlayer mock
    jPlayerMock = vi.fn().mockReturnThis();
    $.fn.jPlayer = jPlayerMock;

    // Mock $.jPlayer.event
    ($ as any).jPlayer = {
      event: {
        ended: 'jPlayer_ended'
      }
    };
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // newPosition Tests
  // ===========================================================================

  describe('newPosition', () => {
    it('calls jPlayer playHead with position', () => {
      document.body.innerHTML = '<div id="jquery_jplayer_1"></div>';

      lwt_audio_controller.newPosition(50);

      expect(jPlayerMock).toHaveBeenCalledWith('playHead', 50);
    });

    it('handles position 0', () => {
      document.body.innerHTML = '<div id="jquery_jplayer_1"></div>';

      lwt_audio_controller.newPosition(0);

      expect(jPlayerMock).toHaveBeenCalledWith('playHead', 0);
    });

    it('handles position 100', () => {
      document.body.innerHTML = '<div id="jquery_jplayer_1"></div>';

      lwt_audio_controller.newPosition(100);

      expect(jPlayerMock).toHaveBeenCalledWith('playHead', 100);
    });
  });

  // ===========================================================================
  // setNewPlayerSeconds Tests
  // ===========================================================================

  describe('setNewPlayerSeconds', () => {
    it('saves selected backtime value', () => {
      document.body.innerHTML = `
        <select id="backtime">
          <option value="3">3s</option>
          <option value="5" selected>5s</option>
        </select>
      `;

      lwt_audio_controller.setNewPlayerSeconds();

      expect(do_ajax_save_setting).toHaveBeenCalledWith('currentplayerseconds', '5');
    });

    it('handles different backtime selections', () => {
      document.body.innerHTML = `
        <select id="backtime">
          <option value="10" selected>10s</option>
        </select>
      `;

      lwt_audio_controller.setNewPlayerSeconds();

      expect(do_ajax_save_setting).toHaveBeenCalledWith('currentplayerseconds', '10');
    });
  });

  // ===========================================================================
  // setNewPlaybackRate Tests
  // ===========================================================================

  describe('setNewPlaybackRate', () => {
    it('saves and applies new playback rate', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <select id="playbackrate">
          <option value="10" selected>1.0x</option>
        </select>
      `;

      lwt_audio_controller.setNewPlaybackRate();

      expect(do_ajax_save_setting).toHaveBeenCalledWith('currentplaybackrate', '10');
      expect(jPlayerMock).toHaveBeenCalledWith('option', 'playbackRate', 1.0);
    });

    it('calculates playback rate correctly (value * 0.1)', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <select id="playbackrate">
          <option value="15" selected>1.5x</option>
        </select>
      `;

      lwt_audio_controller.setNewPlaybackRate();

      expect(jPlayerMock).toHaveBeenCalledWith('option', 'playbackRate', 1.5);
    });

    it('handles slow playback rate', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <select id="playbackrate">
          <option value="5" selected>0.5x</option>
        </select>
      `;

      lwt_audio_controller.setNewPlaybackRate();

      expect(jPlayerMock).toHaveBeenCalledWith('option', 'playbackRate', 0.5);
    });
  });

  // ===========================================================================
  // setCurrentPlaybackRate Tests
  // ===========================================================================

  describe('setCurrentPlaybackRate', () => {
    it('applies current playback rate without saving', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <select id="playbackrate">
          <option value="10" selected>1.0x</option>
        </select>
      `;

      lwt_audio_controller.setCurrentPlaybackRate();

      // Use closeTo to avoid floating point precision issues
      expect(jPlayerMock).toHaveBeenCalledWith('option', 'playbackRate', 1.0);
      // Should NOT save to settings
      expect(do_ajax_save_setting).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // clickSingle Tests
  // ===========================================================================

  describe('clickSingle', () => {
    it('removes ended event handler', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <div id="do-single" class="hide"></div>
        <div id="do-repeat"></div>
      `;

      const offMock = vi.fn().mockReturnThis();
      $.fn.off = offMock;

      lwt_audio_controller.clickSingle();

      expect(offMock).toHaveBeenCalledWith('jPlayer_ended.jp-repeat');
    });

    it('hides single button and shows repeat button', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <div id="do-single"></div>
        <div id="do-repeat" class="hide"></div>
      `;

      $.fn.off = vi.fn().mockReturnThis();

      lwt_audio_controller.clickSingle();

      expect($('#do-single').hasClass('hide')).toBe(true);
      expect($('#do-repeat').hasClass('hide')).toBe(false);
    });

    it('saves repeat mode as 0', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <div id="do-single"></div>
        <div id="do-repeat"></div>
      `;

      $.fn.off = vi.fn().mockReturnThis();

      lwt_audio_controller.clickSingle();

      expect(do_ajax_save_setting).toHaveBeenCalledWith('currentplayerrepeatmode', '0');
    });
  });

  // ===========================================================================
  // clickRepeat Tests
  // ===========================================================================

  describe('clickRepeat', () => {
    it('attaches ended event handler', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <div id="do-single" class="hide"></div>
        <div id="do-repeat"></div>
      `;

      const onMock = vi.fn().mockReturnThis();
      $.fn.on = onMock;

      lwt_audio_controller.clickRepeat();

      expect(onMock).toHaveBeenCalledWith('jPlayer_ended.jp-repeat', expect.any(Function));
    });

    it('hides repeat button and shows single button', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <div id="do-single" class="hide"></div>
        <div id="do-repeat"></div>
      `;

      $.fn.on = vi.fn().mockReturnThis();

      lwt_audio_controller.clickRepeat();

      expect($('#do-repeat').hasClass('hide')).toBe(true);
      expect($('#do-single').hasClass('hide')).toBe(false);
    });

    it('saves repeat mode as 1', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <div id="do-single"></div>
        <div id="do-repeat"></div>
      `;

      $.fn.on = vi.fn().mockReturnThis();

      lwt_audio_controller.clickRepeat();

      expect(do_ajax_save_setting).toHaveBeenCalledWith('currentplayerrepeatmode', '1');
    });
  });

  // ===========================================================================
  // clickBackward Tests
  // ===========================================================================

  describe('clickBackward', () => {
    it('seeks backward by backtime seconds when playing', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <span id="playTime">30</span>
        <select id="backtime">
          <option value="5" selected>5s</option>
        </select>
      `;

      $.fn.data = vi.fn().mockReturnValue({
        jPlayer: { status: { paused: false } }
      });

      lwt_audio_controller.clickBackward();

      // 30 - 5 = 25, playing so should call 'play'
      expect(jPlayerMock).toHaveBeenCalledWith('play', 25);
    });

    it('seeks backward by backtime seconds when paused', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <span id="playTime">30</span>
        <select id="backtime">
          <option value="5" selected>5s</option>
        </select>
      `;

      $.fn.data = vi.fn().mockReturnValue({
        jPlayer: { status: { paused: true } }
      });

      lwt_audio_controller.clickBackward();

      // 30 - 5 = 25, paused so should call 'pause'
      expect(jPlayerMock).toHaveBeenCalledWith('pause', 25);
    });

    it('does not go below 0', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <span id="playTime">3</span>
        <select id="backtime">
          <option value="10" selected>10s</option>
        </select>
      `;

      $.fn.data = vi.fn().mockReturnValue({
        jPlayer: { status: { paused: true } }
      });

      lwt_audio_controller.clickBackward();

      // 3 - 10 = -7, should be clamped to 0
      expect(jPlayerMock).toHaveBeenCalledWith('pause', 0);
    });
  });

  // ===========================================================================
  // clickForward Tests
  // ===========================================================================

  describe('clickForward', () => {
    it('seeks forward by backtime seconds when playing', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <span id="playTime">30</span>
        <select id="backtime">
          <option value="5" selected>5s</option>
        </select>
      `;

      $.fn.data = vi.fn().mockReturnValue({
        jPlayer: { status: { paused: false } }
      });

      lwt_audio_controller.clickForward();

      // 30 + 5 = 35, playing so should call 'play'
      expect(jPlayerMock).toHaveBeenCalledWith('play', 35);
    });

    it('seeks forward by backtime seconds when paused', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <span id="playTime">30</span>
        <select id="backtime">
          <option value="5" selected>5s</option>
        </select>
      `;

      $.fn.data = vi.fn().mockReturnValue({
        jPlayer: { status: { paused: true } }
      });

      lwt_audio_controller.clickForward();

      // 30 + 5 = 35, paused so should call 'pause'
      expect(jPlayerMock).toHaveBeenCalledWith('pause', 35);
    });
  });

  // ===========================================================================
  // clickSlower Tests
  // ===========================================================================

  describe('clickSlower', () => {
    it('decreases playback rate by 0.1', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <span id="pbvalue">1.0</span>
      `;

      lwt_audio_controller.clickSlower();

      expect($('#pbvalue').text()).toBe('0.9');
      expect(jPlayerMock).toHaveBeenCalledWith('playbackRate', 0.9);
    });

    it('does not go below 0.5', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <span id="pbvalue">0.5</span>
      `;

      lwt_audio_controller.clickSlower();

      // 0.5 - 0.1 = 0.4 which is < 0.5, so nothing should happen
      expect($('#pbvalue').text()).toBe('0.5');
      expect(jPlayerMock).not.toHaveBeenCalled();
    });

    it('allows decreasing to 0.5', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <span id="pbvalue">0.6</span>
      `;

      lwt_audio_controller.clickSlower();

      // 0.6 - 0.1 = 0.5 which is >= 0.5, so should work
      expect($('#pbvalue').text()).toBe('0.5');
      expect(jPlayerMock).toHaveBeenCalledWith('playbackRate', 0.5);
    });
  });

  // ===========================================================================
  // clickFaster Tests
  // ===========================================================================

  describe('clickFaster', () => {
    it('increases playback rate by 0.1', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <span id="pbvalue">1.0</span>
      `;

      lwt_audio_controller.clickFaster();

      expect($('#pbvalue').text()).toBe('1.1');
      expect(jPlayerMock).toHaveBeenCalledWith('playbackRate', 1.1);
    });

    it('does not go above 4.0', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <span id="pbvalue">4.0</span>
      `;

      lwt_audio_controller.clickFaster();

      // 4.0 + 0.1 = 4.1 which is > 4.0, so nothing should happen
      expect($('#pbvalue').text()).toBe('4.0');
      expect(jPlayerMock).not.toHaveBeenCalled();
    });

    it('allows increasing to 4.0', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <span id="pbvalue">3.9</span>
      `;

      lwt_audio_controller.clickFaster();

      // 3.9 + 0.1 = 4.0 which is <= 4.0, so should work
      expect($('#pbvalue').text()).toBe('4.0');
      expect(jPlayerMock).toHaveBeenCalledWith('playbackRate', 4.0);
    });
  });

  // ===========================================================================
  // setStdSpeed Tests
  // ===========================================================================

  describe('setStdSpeed', () => {
    it('sets playback rate to 1.0x (value 10)', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <select id="playbackrate">
          <option value="8" selected>0.8x</option>
          <option value="10">1.0x</option>
        </select>
      `;

      lwt_audio_controller.setStdSpeed();

      expect($('#playbackrate').val()).toBe('10');
      expect(do_ajax_save_setting).toHaveBeenCalledWith('currentplaybackrate', '10');
      expect(jPlayerMock).toHaveBeenCalledWith('option', 'playbackRate', 1.0);
    });
  });

  // ===========================================================================
  // setSlower Tests
  // ===========================================================================

  describe('setSlower', () => {
    it('decreases playback rate select by 1', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <select id="playbackrate">
          <option value="9">0.9x</option>
          <option value="10" selected>1.0x</option>
        </select>
      `;

      lwt_audio_controller.setSlower();

      expect($('#playbackrate').val()).toBe('9');
      expect(do_ajax_save_setting).toHaveBeenCalledWith('currentplaybackrate', '9');
    });

    it('does not go below 5 (0.5x)', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <select id="playbackrate">
          <option value="5" selected>0.5x</option>
        </select>
      `;

      lwt_audio_controller.setSlower();

      // Already at 5, should not decrease
      expect(do_ajax_save_setting).not.toHaveBeenCalled();
    });

    it('allows decreasing to 6', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <select id="playbackrate">
          <option value="5">0.5x</option>
          <option value="6" selected>0.6x</option>
        </select>
      `;

      lwt_audio_controller.setSlower();

      // 6 > 5 so should allow decrease
      expect($('#playbackrate').val()).toBe('5');
    });
  });

  // ===========================================================================
  // setFaster Tests
  // ===========================================================================

  describe('setFaster', () => {
    it('increases playback rate select by 1', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <select id="playbackrate">
          <option value="10" selected>1.0x</option>
          <option value="11">1.1x</option>
        </select>
      `;

      lwt_audio_controller.setFaster();

      expect($('#playbackrate').val()).toBe('11');
      expect(do_ajax_save_setting).toHaveBeenCalledWith('currentplaybackrate', '11');
    });

    it('does not go above 15 (1.5x)', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <select id="playbackrate">
          <option value="15" selected>1.5x</option>
        </select>
      `;

      lwt_audio_controller.setFaster();

      // Already at 15, should not increase
      expect(do_ajax_save_setting).not.toHaveBeenCalled();
    });

    it('allows increasing to 15', () => {
      document.body.innerHTML = `
        <div id="jquery_jplayer_1"></div>
        <select id="playbackrate">
          <option value="14" selected>1.4x</option>
          <option value="15">1.5x</option>
        </select>
      `;

      lwt_audio_controller.setFaster();

      // 14 < 15 so should allow increase
      expect($('#playbackrate').val()).toBe('15');
    });
  });
});

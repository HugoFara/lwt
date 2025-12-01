/**
 * Legacy audio controller for jPlayer (non-Vite mode).
 *
 * For Vite mode, use the HTML5 audio player from media/html5_audio_player.ts instead.
 * This file is kept for backward compatibility with legacy (non-Vite) mode.
 *
 * @license Unlicense
 * @deprecated Use html5_audio_player.ts for new code
 */

import { do_ajax_save_setting } from '../core/ajax_utilities';

// jPlayer type definitions (legacy)
interface JPlayerStatus {
  paused: boolean;
}

interface JPlayerInstance {
  status: JPlayerStatus;
}

interface JPlayerData {
  jPlayer: JPlayerInstance;
}

// Extend JQuery interface for jPlayer plugin (legacy)
declare global {
  interface JQuery {
    jPlayer(method: string, ...args: unknown[]): JQuery;
    data(): JPlayerData;
  }

  interface JQueryStatic {
    jPlayer: {
      event: {
        ended: string;
      };
    };
  }
}

/**
 * Helper to get value from a select element
 */
function getSelectValue(id: string): string {
  const el = document.getElementById(id) as HTMLSelectElement | null;
  return el?.value || '';
}

/**
 * Helper to set value on a select element
 */
function setSelectValue(id: string, value: string | number): void {
  const el = document.getElementById(id) as HTMLSelectElement | null;
  if (el) el.value = String(value);
}

/**
 * Helper to get text content
 */
function getTextContent(id: string): string {
  const el = document.getElementById(id);
  return el?.textContent || '';
}

/**
 * Helper to set text content with animation effect
 */
function setTextWithFlash(id: string, text: string): void {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = text;
  el.style.color = '#BBB';
  setTimeout(() => {
    el.style.color = '#888';
  }, 150);
}

/**
 * Helper to toggle visibility using 'hide' class
 */
function setHidden(id: string, hidden: boolean): void {
  const el = document.getElementById(id);
  if (!el) return;
  if (hidden) {
    el.classList.add('hide');
  } else {
    el.classList.remove('hide');
  }
}

/**
 * Legacy jPlayer audio controller.
 *
 * Note: This controller still requires jQuery for jPlayer plugin methods.
 * The jPlayer calls use jQuery() directly since jPlayer is a jQuery plugin.
 *
 * @deprecated Use lwt_audio_controller from html5_audio_player.ts for Vite mode
 */
export const lwt_audio_controller = {
  /**
   * Change the position of the audio player head.
   *
   * @param position New player head position (0-100)
   */
  newPosition: function (position: number): void {
    // jPlayer requires jQuery - this is unavoidable for jPlayer integration
    const jPlayer = (window as unknown as { jQuery: JQueryStatic }).jQuery;
    if (jPlayer) {
      jPlayer('#jquery_jplayer_1').jPlayer('playHead', position);
    }
  },

  setNewPlayerSeconds: function (): void {
    const newval = getSelectValue('backtime');
    do_ajax_save_setting('currentplayerseconds', newval);
  },

  setNewPlaybackRate: function (): void {
    const newval = getSelectValue('playbackrate');
    do_ajax_save_setting('currentplaybackrate', newval);
    const jPlayer = (window as unknown as { jQuery: JQueryStatic }).jQuery;
    if (jPlayer) {
      jPlayer('#jquery_jplayer_1').jPlayer('option', 'playbackRate', parseFloat(newval) * 0.1);
    }
  },

  setCurrentPlaybackRate: function (): void {
    const val = getSelectValue('playbackrate');
    const jPlayer = (window as unknown as { jQuery: JQueryStatic }).jQuery;
    if (jPlayer) {
      jPlayer('#jquery_jplayer_1').jPlayer('option', 'playbackRate', parseFloat(val) * 0.1);
    }
  },

  clickSingle: function (): void {
    const jPlayer = (window as unknown as { jQuery: JQueryStatic }).jQuery;
    if (jPlayer && jPlayer.jPlayer) {
      jPlayer('#jquery_jplayer_1').off(jPlayer.jPlayer.event.ended + '.jp-repeat');
    }
    setHidden('do-single', true);
    setHidden('do-repeat', false);
    do_ajax_save_setting('currentplayerrepeatmode', '0');
  },

  clickRepeat: function (): void {
    const jPlayer = (window as unknown as { jQuery: JQueryStatic }).jQuery;
    if (jPlayer && jPlayer.jPlayer) {
      jPlayer('#jquery_jplayer_1')
        .on(jPlayer.jPlayer.event.ended + '.jp-repeat', function () {
          jPlayer('#jquery_jplayer_1').jPlayer('play');
        });
    }
    setHidden('do-repeat', true);
    setHidden('do-single', false);
    do_ajax_save_setting('currentplayerrepeatmode', '1');
  },

  clickBackward: function (): void {
    const t = parseInt(getTextContent('playTime'), 10);
    const b = parseInt(getSelectValue('backtime'), 10);
    let nt = t - b;
    let st: 'pause' | 'play' = 'pause';
    if (nt < 0) { nt = 0; }
    const jPlayer = (window as unknown as { jQuery: JQueryStatic }).jQuery;
    if (jPlayer) {
      const data = jPlayer('#jquery_jplayer_1').data();
      if (data && !data.jPlayer.status.paused) { st = 'play'; }
      jPlayer('#jquery_jplayer_1').jPlayer(st, nt);
    }
  },

  clickForward: function (): void {
    const t = parseInt(getTextContent('playTime'), 10);
    const b = parseInt(getSelectValue('backtime'), 10);
    const nt = t + b;
    let st: 'pause' | 'play' = 'pause';
    const jPlayer = (window as unknown as { jQuery: JQueryStatic }).jQuery;
    if (jPlayer) {
      const data = jPlayer('#jquery_jplayer_1').data();
      if (data && !data.jPlayer.status.paused) { st = 'play'; }
      jPlayer('#jquery_jplayer_1').jPlayer(st, nt);
    }
  },

  clickSlower: function (): void {
    const val = parseFloat(getTextContent('pbvalue')) - 0.1;
    if (val >= 0.5) {
      setTextWithFlash('pbvalue', val.toFixed(1));
      const jPlayer = (window as unknown as { jQuery: JQueryStatic }).jQuery;
      if (jPlayer) {
        jPlayer('#jquery_jplayer_1').jPlayer('playbackRate', val);
      }
    }
  },

  clickFaster: function (): void {
    const val = parseFloat(getTextContent('pbvalue')) + 0.1;
    if (val <= 4.0) {
      setTextWithFlash('pbvalue', val.toFixed(1));
      const jPlayer = (window as unknown as { jQuery: JQueryStatic }).jQuery;
      if (jPlayer) {
        jPlayer('#jquery_jplayer_1').jPlayer('playbackRate', val);
      }
    }
  },

  setStdSpeed: function (): void {
    setSelectValue('playbackrate', 10);
    lwt_audio_controller.setNewPlaybackRate();
  },

  setSlower: function (): void {
    let val = parseInt(getSelectValue('playbackrate'), 10);
    if (val > 5) {
      val--;
      setSelectValue('playbackrate', val);
      lwt_audio_controller.setNewPlaybackRate();
    }
  },

  setFaster: function (): void {
    let val = parseInt(getSelectValue('playbackrate'), 10);
    if (val < 15) {
      val++;
      setSelectValue('playbackrate', val);
      lwt_audio_controller.setNewPlaybackRate();
    }
  }
};

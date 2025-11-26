/**
 * All the function to make an audio controller in do_text_header.php
 *
 * @license Unlicense
 */

// Declare external functions that are defined elsewhere
declare function do_ajax_save_setting(key: string, value: string): void;

// jPlayer type definitions
interface JPlayerStatus {
  paused: boolean;
}

interface JPlayerInstance {
  status: JPlayerStatus;
}

interface JPlayerData {
  jPlayer: JPlayerInstance;
}

// Extend JQuery interface for jPlayer plugin
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
 * An audio controller.
 */
export const lwt_audio_controller = {
  /**
   * Change the position of the audio player head.
   *
   * @param position New player head position (0-100)
   */
  newPosition: function (position: number): void {
    $('#jquery_jplayer_1').jPlayer('playHead', position);
  },

  setNewPlayerSeconds: function (): void {
    const newval = $('#backtime :selected').val() as string;
    do_ajax_save_setting('currentplayerseconds', newval);
  },

  setNewPlaybackRate: function (): void {
    const newval = $('#playbackrate :selected').val() as string;
    do_ajax_save_setting('currentplaybackrate', newval);
    $('#jquery_jplayer_1').jPlayer('option', 'playbackRate', parseFloat(newval) * 0.1);
  },

  setCurrentPlaybackRate: function (): void {
    const val = $('#playbackrate :selected').val() as string;
    $('#jquery_jplayer_1').jPlayer('option', 'playbackRate', parseFloat(val) * 0.1);
  },

  clickSingle: function (): void {
    $('#jquery_jplayer_1').off($.jPlayer.event.ended + '.jp-repeat');
    $('#do-single').addClass('hide');
    $('#do-repeat').removeClass('hide');
    do_ajax_save_setting('currentplayerrepeatmode', '0');
  },

  clickRepeat: function (): void {
    $('#jquery_jplayer_1')
      .on($.jPlayer.event.ended + '.jp-repeat', function () {
        $('#jquery_jplayer_1').jPlayer('play');
      });
    $('#do-repeat').addClass('hide');
    $('#do-single').removeClass('hide');
    do_ajax_save_setting('currentplayerrepeatmode', '1');
  },

  clickBackward: function (): void {
    const t = parseInt($('#playTime').text(), 10);
    const b = parseInt($('#backtime').val() as string, 10);
    let nt = t - b;
    let st: 'pause' | 'play' = 'pause';
    if (nt < 0) { nt = 0; }
    if (!$('#jquery_jplayer_1').data().jPlayer.status.paused) { st = 'play'; }
    $('#jquery_jplayer_1').jPlayer(st, nt);
  },

  clickForward: function (): void {
    const t = parseInt($('#playTime').text(), 10);
    const b = parseInt($('#backtime').val() as string, 10);
    const nt = t + b;
    let st: 'pause' | 'play' = 'pause';
    if (!$('#jquery_jplayer_1').data().jPlayer.status.paused) { st = 'play'; }
    $('#jquery_jplayer_1').jPlayer(st, nt);
  },

  clickSlower: function (): void {
    const val = parseFloat($('#pbvalue').text()) - 0.1;
    if (val >= 0.5) {
      $('#pbvalue').text(val.toFixed(1)).css({ color: '#BBB' })
        .animate({ color: '#888' }, 150, function () {});
      $('#jquery_jplayer_1').jPlayer('playbackRate', val);
    }
  },

  clickFaster: function (): void {
    const val = parseFloat($('#pbvalue').text()) + 0.1;
    if (val <= 4.0) {
      $('#pbvalue').text(val.toFixed(1)).css({ color: '#BBB' })
        .animate({ color: '#888' }, 150, function () {});
      $('#jquery_jplayer_1').jPlayer('playbackRate', val);
    }
  },

  setStdSpeed: function (): void {
    $('#playbackrate').val(10);
    lwt_audio_controller.setNewPlaybackRate();
  },

  setSlower: function (): void {
    let val = parseInt($('#playbackrate :selected').val() as string, 10);
    if (val > 5) {
      val--;
      $('#playbackrate').val(val);
      lwt_audio_controller.setNewPlaybackRate();
    }
  },

  setFaster: function (): void {
    let val = parseInt($('#playbackrate :selected').val() as string, 10);
    if (val < 15) {
      val++;
      $('#playbackrate').val(val);
      lwt_audio_controller.setNewPlaybackRate();
    }
  }
};

/**
 * Change the position of the audio player head.
 *
 * @param p New player head
 *
 * @deprecated Since LWT 2.9.1, use lwt_audio_controller.newPosition
 */
export function new_pos(p: number): void {
  return lwt_audio_controller.newPosition(p);
}

// Expose globally for backward compatibility with PHP templates
if (typeof window !== 'undefined') {
  (window as unknown as Record<string, unknown>).lwt_audio_controller = lwt_audio_controller;
  (window as unknown as Record<string, unknown>).new_pos = new_pos;
}

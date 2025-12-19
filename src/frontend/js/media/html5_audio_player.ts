/**
 * HTML5 Audio Player
 *
 * This module provides a native HTML5 audio player with custom controls.
 *
 * @license unlicense
 * @since 3.0.0
 */

import { do_ajax_save_setting } from '../core/ajax_utilities';

/**
 * HTML5 Audio Player class
 * Wraps native HTMLAudioElement with custom UI and LWT-specific functionality
 */
export class Html5AudioPlayer {
  private audio: HTMLAudioElement;
  private container: HTMLElement;
  private repeatMode: boolean = false;
  private onTimeUpdate: ((currentTime: number) => void) | null = null;
  private onPlay: (() => void) | null = null;

  constructor(containerId: string) {
    const container = document.getElementById(containerId);
    if (!container) {
      throw new Error(`Container element with id "${containerId}" not found`);
    }
    this.container = container;
    this.audio = this.container.querySelector('audio') as HTMLAudioElement;
    if (!this.audio) {
      throw new Error('Audio element not found in container');
    }
    this.setupEventListeners();
  }

  private setupEventListeners(): void {
    // Update time display
    this.audio.addEventListener('timeupdate', () => {
      this.updateTimeDisplay();
      if (this.onTimeUpdate) {
        this.onTimeUpdate(this.audio.currentTime);
      }
    });

    // Handle play event
    this.audio.addEventListener('play', () => {
      this.updatePlayPauseButton(true);
      if (this.onPlay) {
        this.onPlay();
      }
    });

    // Handle pause event
    this.audio.addEventListener('pause', () => {
      this.updatePlayPauseButton(false);
    });

    // Handle ended event for repeat mode
    this.audio.addEventListener('ended', () => {
      if (this.repeatMode) {
        this.audio.currentTime = 0;
        this.audio.play();
      }
    });

    // Handle duration change
    this.audio.addEventListener('durationchange', () => {
      this.updateDurationDisplay();
    });

    // Progress bar click handling
    const progressContainer = this.container.querySelector('.lwt-audio-progress-container');
    if (progressContainer) {
      progressContainer.addEventListener('click', (e: Event) => {
        const mouseEvent = e as MouseEvent;
        const rect = (progressContainer as HTMLElement).getBoundingClientRect();
        const percent = (mouseEvent.clientX - rect.left) / rect.width;
        this.audio.currentTime = percent * this.audio.duration;
      });
    }

    // Volume bar click handling
    const volumeContainer = this.container.querySelector('.lwt-audio-volume-container');
    if (volumeContainer) {
      volumeContainer.addEventListener('click', (e: Event) => {
        const mouseEvent = e as MouseEvent;
        const rect = (volumeContainer as HTMLElement).getBoundingClientRect();
        const percent = (mouseEvent.clientX - rect.left) / rect.width;
        this.audio.volume = Math.max(0, Math.min(1, percent));
        this.updateVolumeDisplay();
      });
    }
  }

  private updateTimeDisplay(): void {
    const currentTimeEl = this.container.querySelector('.lwt-audio-current-time');
    const progressBar = this.container.querySelector('.lwt-audio-progress-bar') as HTMLElement;
    const playTimeEl = document.getElementById('playTime');

    if (currentTimeEl) {
      currentTimeEl.textContent = this.formatTime(this.audio.currentTime);
    }
    if (progressBar && this.audio.duration) {
      const percent = (this.audio.currentTime / this.audio.duration) * 100;
      progressBar.style.width = `${percent}%`;
    }
    if (playTimeEl) {
      playTimeEl.textContent = Math.floor(this.audio.currentTime).toString();
    }
  }

  private updateDurationDisplay(): void {
    const durationEl = this.container.querySelector('.lwt-audio-duration');
    if (durationEl) {
      durationEl.textContent = this.formatTime(this.audio.duration);
    }
  }

  private updatePlayPauseButton(isPlaying: boolean): void {
    const playBtn = this.container.querySelector('.lwt-audio-play');
    const pauseBtn = this.container.querySelector('.lwt-audio-pause');
    if (playBtn && pauseBtn) {
      if (isPlaying) {
        playBtn.classList.add('hide');
        pauseBtn.classList.remove('hide');
      } else {
        playBtn.classList.remove('hide');
        pauseBtn.classList.add('hide');
      }
    }
  }

  private updateVolumeDisplay(): void {
    const volumeBar = this.container.querySelector('.lwt-audio-volume-bar') as HTMLElement;
    const muteBtn = this.container.querySelector('.lwt-audio-mute');
    const unmuteBtn = this.container.querySelector('.lwt-audio-unmute');

    if (volumeBar) {
      volumeBar.style.width = `${this.audio.volume * 100}%`;
    }
    if (muteBtn && unmuteBtn) {
      if (this.audio.muted || this.audio.volume === 0) {
        muteBtn.classList.add('hide');
        unmuteBtn.classList.remove('hide');
      } else {
        muteBtn.classList.remove('hide');
        unmuteBtn.classList.add('hide');
      }
    }
  }

  private formatTime(seconds: number): string {
    if (isNaN(seconds) || !isFinite(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  }

  // Public API methods (compatible with lwt_audio_controller)

  play(time?: number): void {
    if (time !== undefined) {
      this.audio.currentTime = time;
    }
    this.audio.play();
  }

  pause(time?: number): void {
    if (time !== undefined) {
      this.audio.currentTime = time;
    }
    this.audio.pause();
  }

  stop(): void {
    this.audio.pause();
    this.audio.currentTime = 0;
  }

  /**
   * Set playhead position as percentage (0-100)
   */
  playHead(percent: number): void {
    if (this.audio.duration) {
      this.audio.currentTime = (percent / 100) * this.audio.duration;
    }
  }

  setPlaybackRate(rate: number): void {
    this.audio.playbackRate = rate;
  }

  getPlaybackRate(): number {
    return this.audio.playbackRate;
  }

  getCurrentTime(): number {
    return this.audio.currentTime;
  }

  getDuration(): number {
    return this.audio.duration;
  }

  isPaused(): boolean {
    return this.audio.paused;
  }

  setRepeatMode(enabled: boolean): void {
    this.repeatMode = enabled;
    this.audio.loop = enabled;
  }

  getRepeatMode(): boolean {
    return this.repeatMode;
  }

  mute(): void {
    this.audio.muted = true;
    this.updateVolumeDisplay();
  }

  unmute(): void {
    this.audio.muted = false;
    this.updateVolumeDisplay();
  }

  toggleMute(): void {
    this.audio.muted = !this.audio.muted;
    this.updateVolumeDisplay();
  }

  setVolume(volume: number): void {
    this.audio.volume = Math.max(0, Math.min(1, volume));
    this.updateVolumeDisplay();
  }

  getVolume(): number {
    return this.audio.volume;
  }

  /**
   * Set media source
   */
  setMedia(src: string): void {
    this.audio.src = src;
    this.audio.load();
  }

  /**
   * Register callback for time updates
   */
  onTimeUpdateCallback(callback: (currentTime: number) => void): void {
    this.onTimeUpdate = callback;
  }

  /**
   * Register callback for play events
   */
  onPlayCallback(callback: () => void): void {
    this.onPlay = callback;
  }

  /**
   * Get the underlying audio element
   */
  getAudioElement(): HTMLAudioElement {
    return this.audio;
  }
}

// Global player instance
let playerInstance: Html5AudioPlayer | null = null;

/**
 * Initialize the HTML5 audio player
 */
export function initHtml5AudioPlayer(containerId: string = 'lwt-audio-player'): Html5AudioPlayer | null {
  const container = document.getElementById(containerId);
  if (!container) {
    return null;
  }
  playerInstance = new Html5AudioPlayer(containerId);
  return playerInstance;
}

/**
 * Get the current player instance
 */
export function getAudioPlayer(): Html5AudioPlayer | null {
  return playerInstance;
}

/**
 * LWT Audio Controller interface for the HTML5 audio player.
 */
export const lwt_audio_controller = {
  /**
   * Change the position of the audio player head.
   *
   * @param position New player head position (0-100)
   */
  newPosition: function (position: number): void {
    if (playerInstance) {
      playerInstance.playHead(position);
    }
  },

  setNewPlayerSeconds: function (): void {
    const el = document.getElementById('backtime') as HTMLSelectElement | null;
    const newval = el?.value || '0';
    do_ajax_save_setting('currentplayerseconds', newval);
  },

  setNewPlaybackRate: function (): void {
    const el = document.getElementById('playbackrate') as HTMLSelectElement | null;
    const newval = el?.value || '10';
    do_ajax_save_setting('currentplaybackrate', newval);
    if (playerInstance) {
      playerInstance.setPlaybackRate(parseFloat(newval) * 0.1);
    }
  },

  setCurrentPlaybackRate: function (): void {
    const el = document.getElementById('playbackrate') as HTMLSelectElement | null;
    const val = el?.value || '10';
    if (playerInstance) {
      playerInstance.setPlaybackRate(parseFloat(val) * 0.1);
    }
  },

  clickSingle: function (): void {
    if (playerInstance) {
      playerInstance.setRepeatMode(false);
    }
    document.getElementById('do-single')?.classList.add('hide');
    document.getElementById('do-repeat')?.classList.remove('hide');
    do_ajax_save_setting('currentplayerrepeatmode', '0');
  },

  clickRepeat: function (): void {
    if (playerInstance) {
      playerInstance.setRepeatMode(true);
    }
    document.getElementById('do-repeat')?.classList.add('hide');
    document.getElementById('do-single')?.classList.remove('hide');
    do_ajax_save_setting('currentplayerrepeatmode', '1');
  },

  clickBackward: function (): void {
    if (!playerInstance) return;
    const t = playerInstance.getCurrentTime();
    const backEl = document.getElementById('backtime') as HTMLSelectElement | null;
    const b = parseInt(backEl?.value || '0', 10);
    let nt = t - b;
    if (nt < 0) { nt = 0; }
    const wasPaused = playerInstance.isPaused();
    if (wasPaused) {
      playerInstance.pause(nt);
    } else {
      playerInstance.play(nt);
    }
  },

  clickForward: function (): void {
    if (!playerInstance) return;
    const t = playerInstance.getCurrentTime();
    const backEl = document.getElementById('backtime') as HTMLSelectElement | null;
    const b = parseInt(backEl?.value || '0', 10);
    const nt = t + b;
    const wasPaused = playerInstance.isPaused();
    if (wasPaused) {
      playerInstance.pause(nt);
    } else {
      playerInstance.play(nt);
    }
  },

  clickSlower: function (): void {
    if (!playerInstance) return;
    const val = playerInstance.getPlaybackRate() - 0.1;
    if (val >= 0.5) {
      const pbEl = document.getElementById('pbvalue');
      if (pbEl) {
        pbEl.textContent = val.toFixed(1);
        pbEl.style.color = '#BBB';
        setTimeout(() => { pbEl.style.color = '#888'; }, 150);
      }
      playerInstance.setPlaybackRate(val);
    }
  },

  clickFaster: function (): void {
    if (!playerInstance) return;
    const val = playerInstance.getPlaybackRate() + 0.1;
    if (val <= 4.0) {
      const pbEl = document.getElementById('pbvalue');
      if (pbEl) {
        pbEl.textContent = val.toFixed(1);
        pbEl.style.color = '#BBB';
        setTimeout(() => { pbEl.style.color = '#888'; }, 150);
      }
      playerInstance.setPlaybackRate(val);
    }
  },

  setStdSpeed: function (): void {
    const el = document.getElementById('playbackrate') as HTMLSelectElement | null;
    if (el) el.value = '10';
    lwt_audio_controller.setNewPlaybackRate();
  },

  setSlower: function (): void {
    const el = document.getElementById('playbackrate') as HTMLSelectElement | null;
    if (!el) return;
    let val = parseInt(el.value, 10);
    if (val > 5) {
      val--;
      el.value = String(val);
      lwt_audio_controller.setNewPlaybackRate();
    }
  },

  setFaster: function (): void {
    const el = document.getElementById('playbackrate') as HTMLSelectElement | null;
    if (!el) return;
    let val = parseInt(el.value, 10);
    if (val < 15) {
      val++;
      el.value = String(val);
      lwt_audio_controller.setNewPlaybackRate();
    }
  },

  // Additional methods for compatibility
  play: function (): void {
    if (playerInstance) {
      playerInstance.play();
    }
  },

  pause: function (): void {
    if (playerInstance) {
      playerInstance.pause();
    }
  },

  stop: function (): void {
    if (playerInstance) {
      playerInstance.stop();
    }
  },

  mute: function (): void {
    if (playerInstance) {
      playerInstance.mute();
    }
  },

  unmute: function (): void {
    if (playerInstance) {
      playerInstance.unmute();
    }
  }
};

/**
 * Initialize the player with media and set up event handlers
 * Call this from the page after DOM is ready
 */
export function setupAudioPlayer(
  containerId: string,
  mediaUrl: string,
  offset: number = 0,
  repeatMode: boolean = false
): Html5AudioPlayer | null {
  const player = initHtml5AudioPlayer(containerId);
  if (!player) return null;

  // Set initial state
  player.setMedia(mediaUrl);
  if (offset > 0) {
    player.getAudioElement().addEventListener('loadedmetadata', () => {
      player.pause(offset);
    }, { once: true });
  }

  // Set repeat mode
  if (repeatMode) {
    player.setRepeatMode(true);
    document.getElementById('do-repeat')?.classList.add('hide');
    document.getElementById('do-single')?.classList.remove('hide');
  }

  // Set up playback rate callback
  player.onPlayCallback(() => {
    lwt_audio_controller.setCurrentPlaybackRate();
  });

  // Setup control button handlers
  document.getElementById('slower')?.addEventListener('click', lwt_audio_controller.setSlower);
  document.getElementById('faster')?.addEventListener('click', lwt_audio_controller.setFaster);
  document.getElementById('stdspeed')?.addEventListener('click', lwt_audio_controller.setStdSpeed);
  document.getElementById('backbutt')?.addEventListener('click', lwt_audio_controller.clickBackward);
  document.getElementById('forwbutt')?.addEventListener('click', lwt_audio_controller.clickForward);
  document.getElementById('do-single')?.addEventListener('click', lwt_audio_controller.clickSingle);
  document.getElementById('do-repeat')?.addEventListener('click', lwt_audio_controller.clickRepeat);
  document.getElementById('playbackrate')?.addEventListener('change', lwt_audio_controller.setNewPlaybackRate);
  document.getElementById('backtime')?.addEventListener('change', lwt_audio_controller.setNewPlayerSeconds);

  // Setup play/pause/stop/mute button handlers
  document.querySelectorAll('.lwt-audio-play').forEach((el) => {
    el.addEventListener('click', () => player.play());
  });
  document.querySelectorAll('.lwt-audio-pause').forEach((el) => {
    el.addEventListener('click', () => player.pause());
  });
  document.querySelectorAll('.lwt-audio-stop').forEach((el) => {
    el.addEventListener('click', () => player.stop());
  });
  document.querySelectorAll('.lwt-audio-mute').forEach((el) => {
    el.addEventListener('click', () => player.mute());
  });
  document.querySelectorAll('.lwt-audio-unmute').forEach((el) => {
    el.addEventListener('click', () => player.unmute());
  });

  return player;
}

/**
 * Audio player configuration from JSON.
 */
interface AudioPlayerConfig {
  containerId: string;
  mediaUrl: string;
  offset: number;
  repeatMode: boolean;
}

/**
 * Auto-initialize audio player from JSON config element.
 */
export function autoInitAudioPlayer(): void {
  const configEl = document.querySelector<HTMLScriptElement>('script[data-lwt-audio-player-config]');
  if (configEl) {
    try {
      const config = JSON.parse(configEl.textContent || '{}') as AudioPlayerConfig;
      setupAudioPlayer(
        config.containerId,
        config.mediaUrl,
        config.offset,
        config.repeatMode
      );
    } catch (e) {
      console.error('Failed to parse audio player config:', e);
    }
  }
}

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', autoInitAudioPlayer);

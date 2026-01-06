/**
 * Audio Feedback - Play success/failure sounds for user feedback.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.2.0
 */

/**
 * Play the success sound.
 *
 * Expects an audio element with id="success_sound" to exist in the DOM.
 *
 * @returns Promise on the status of sound playback
 */
export function successSound(): Promise<void> {
  const successAudio = document.getElementById('success_sound') as HTMLAudioElement | null;
  const failureAudio = document.getElementById('failure_sound') as HTMLAudioElement | null;
  successAudio?.pause();
  failureAudio?.pause();
  return successAudio?.play() ?? Promise.resolve();
}

/**
 * Play the failure sound.
 *
 * Expects an audio element with id="failure_sound" to exist in the DOM.
 *
 * @returns Promise on the status of sound playback
 */
export function failureSound(): Promise<void> {
  const successAudio = document.getElementById('success_sound') as HTMLAudioElement | null;
  const failureAudio = document.getElementById('failure_sound') as HTMLAudioElement | null;
  successAudio?.pause();
  failureAudio?.pause();
  return failureAudio?.play() ?? Promise.resolve();
}

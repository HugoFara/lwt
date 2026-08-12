/**
 * Audio Feedback - Play success/failure sounds for user feedback.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since 3.2.0
 */

/**
 * Stop both feedback sounds and play one of them from the start.
 *
 * Rewinding is what makes rapid consecutive answers audible: without it, a
 * sound already playing is simply resumed and the second answer is silent.
 *
 * @param playId ID of the audio element to play
 *
 * @returns Promise on the status of sound playback
 */
function playFeedbackSound(playId: 'success_sound' | 'failure_sound'): Promise<void> {
  const successAudio = document.getElementById('success_sound') as HTMLAudioElement | null;
  const failureAudio = document.getElementById('failure_sound') as HTMLAudioElement | null;
  successAudio?.pause();
  failureAudio?.pause();

  const target = playId === 'success_sound' ? successAudio : failureAudio;
  if (!target) {
    return Promise.resolve();
  }
  target.currentTime = 0;
  return target.play() ?? Promise.resolve();
}

/**
 * Play the success sound.
 *
 * Expects an audio element with id="success_sound" to exist in the DOM.
 *
 * @returns Promise on the status of sound playback
 */
export function successSound(): Promise<void> {
  return playFeedbackSound('success_sound');
}

/**
 * Play the failure sound.
 *
 * Expects an audio element with id="failure_sound" to exist in the DOM.
 *
 * @returns Promise on the status of sound playback
 */
export function failureSound(): Promise<void> {
  return playFeedbackSound('failure_sound');
}

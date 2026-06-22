/**
 * Self-hosted ALTCHA proof-of-work solver.
 *
 * Fetches a challenge from the server's public `/auth/altcha-challenge`
 * endpoint and brute-forces the SHA-256 secret number, returning the base64
 * solution to submit as the `altcha` field. No third-party widget or service
 * is involved — this pairs with AltchaService on the server.
 *
 * Solving is invisible to the user (runs on submit) and typically takes well
 * under a second for the server's configured difficulty.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { apiGet } from '@shared/api/client';

interface AltchaChallenge {
  algorithm?: string;
  challenge?: string;
  salt?: string;
  signature?: string;
  maxnumber?: number;
  /** Present and false when the server has captcha disabled. */
  enabled?: boolean;
}

/** Hex-encoded SHA-256 of a string, via the Web Crypto API. */
async function sha256Hex(input: string): Promise<string> {
  const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(input));
  return Array.from(new Uint8Array(digest))
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
}

/**
 * Fetch and solve a captcha challenge.
 *
 * @returns The base64 solution payload to send as the `altcha` field, or an
 *   empty string when the captcha is disabled or unavailable (the server then
 *   decides whether to accept the submission).
 */
export async function solveAltcha(): Promise<string> {
  const res = await apiGet<AltchaChallenge>('/auth/altcha-challenge');
  const challenge = res.data;
  if (
    !challenge
    || challenge.enabled === false
    || !challenge.challenge
    || !challenge.salt
    || !challenge.signature
  ) {
    return '';
  }

  const max = typeof challenge.maxnumber === 'number' ? challenge.maxnumber : 1_000_000;
  for (let number = 0; number <= max; number++) {
    const hash = await sha256Hex(challenge.salt + number);
    if (hash === challenge.challenge) {
      return btoa(
        JSON.stringify({
          algorithm: challenge.algorithm ?? 'SHA-256',
          challenge: challenge.challenge,
          number,
          salt: challenge.salt,
          signature: challenge.signature
        })
      );
    }
  }
  return '';
}

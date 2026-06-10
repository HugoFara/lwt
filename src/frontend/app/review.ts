/**
 * Review page entry for the bundled client.
 *
 * The SSR page injected `review-config` (review key, selection, type, language
 * settings, progress…) computed by the server. Here we fetch the same payload
 * from `/api/v1/review/config` using the selection from the URL
 * (`review.html?text=42`), inject it, and hand off; `reviewApp` then renders and
 * drives the session from `/api/v1/review/*`.
 *
 * Unlike the reader (a plain `x-data` component Alpine auto-mounts), the review
 * surface is mounted by `initReviewApp()`, which the module auto-runs on
 * `onDomReady` *guarded by `window.Alpine`*. In the bundle `main` is imported
 * dynamically after DOMContentLoaded, so that auto-init fires before Alpine is
 * set and bails. We therefore drive the init ourselves once Alpine signals it
 * has started (by which point the injected config blob is present).
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { bootAppPage, injectConfig } from './boot';
import { ReviewApi } from '@modules/review/api/review_api';
import { initReviewApp } from '@modules/review/components/review_view';

const params = new URLSearchParams(window.location.search);
const text = parseInt(params.get('text') ?? '0', 10) || undefined;
const lang = parseInt(params.get('lang') ?? '0', 10) || undefined;
const selection = parseInt(params.get('selection') ?? '0', 10) || undefined;

// Register before booting so it is in place well before Alpine.start() fires
// the event. `once` + the module's own (bailing) auto-init means a single init.
let initialized = false;
document.addEventListener(
  'alpine:initialized',
  () => {
    if (initialized) return;
    initialized = true;
    initReviewApp();
  },
  { once: true }
);

async function start(): Promise<void> {
  const res = await ReviewApi.getReviewConfig({ text, lang, selection });
  // initReviewApp reads #review-config; supply the fetched config, or surface a
  // load error through the same channel (it renders config.error if present).
  injectConfig('review-config', res.data ?? { error: res.error || 'Could not load review.' });
  await bootAppPage({ requireAuth: true });
}

void start();

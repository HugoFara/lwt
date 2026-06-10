/**
 * Reader page entry for the bundled client.
 *
 * The text to open comes from the URL (`read.html?text=42&lang=2`) instead of a
 * server-rendered config blob. We fill the `{{TEXT_ID}}`/`{{LANG_ID}}` tokens
 * the prerenderer left in the toolbar links, inject the reader config blob, and
 * hand off; `textReader` then loads the text from `/api/v1/texts/{id}`.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { bootAppPage, injectConfig, fillIdTokens } from './boot';
import { pageUrl } from './router';

const params = new URLSearchParams(window.location.search);
const textId = parseInt(params.get('text') ?? '0', 10) || 0;
const langId = parseInt(params.get('lang') ?? '0', 10) || 0;

if (textId <= 0) {
  // Nothing to read — go back to the library rather than boot an empty reader.
  window.location.replace(pageUrl.library());
} else {
  fillIdTokens(textId, langId);
  injectConfig('text-reader-config', { textId, langId });
  void bootAppPage({ requireAuth: true });
}

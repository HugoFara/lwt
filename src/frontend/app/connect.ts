/**
 * Connect/auth page entry for the bundled client.
 *
 * Hosts the existing `clientAuth` flow (choose server -> log in/register ->
 * store bearer token). On success it navigates to the bundled library page;
 * if a token is already stored it skips straight there.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { bootAppPage, injectConfig } from './boot';
import { pageUrl } from './router';

injectConfig('client-auth-config', {
  defaultServer: '',
  // clientAuth navigates here after login (and on auto-skip when already
  // signed in). Must be a page the WebView can resolve locally.
  homeUrl: pageUrl.library()
});

void bootAppPage({ requireAuth: false });

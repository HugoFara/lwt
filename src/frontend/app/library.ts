/**
 * Library (text list) page entry for the bundled client.
 *
 * The PHP page injected `activeLanguageId` from the server's `currentlanguage`
 * setting. Here we resolve it from the API: the server's current language, else
 * the first language. Language switching is handled by the global navbar's
 * switcher (mounted from GET /api/v1/navbar), which writes the server setting
 * and reloads — so this page just reads back the resulting current language.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { bootAppPage, injectConfig } from './boot';
import { LanguagesApi } from '@modules/language/api/languages_api';

async function start(): Promise<void> {
  const res = await LanguagesApi.list();
  const languages = res.data?.languages ?? [];
  const ids = new Set(languages.map((l) => l.id));

  const current = res.data?.currentLanguageId ?? 0;
  const activeId = (current && ids.has(current) && current) || (languages[0]?.id ?? 0);

  injectConfig('texts-grouped-config', { activeLanguageId: activeId });

  await bootAppPage({ requireAuth: true });
}

void start();

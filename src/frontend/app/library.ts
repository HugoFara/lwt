/**
 * Library (text list) page entry for the bundled client.
 *
 * The PHP page injected `activeLanguageId` from the server's `currentlanguage`
 * setting. Here we resolve it client-side: the user's last choice (persisted in
 * localStorage), else the server's current language, else the first language.
 * A small language switcher in the page header lets the user change it.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { bootAppPage, injectConfig } from './boot';
import { LanguagesApi, type LanguageListItem } from '@modules/language/api/languages_api';

const ACTIVE_LANG_KEY = 'lwt.activeLang';

function readStoredLang(): number {
  try {
    return parseInt(localStorage.getItem(ACTIVE_LANG_KEY) ?? '0', 10) || 0;
  } catch {
    return 0;
  }
}

function storeLang(id: number): void {
  try {
    localStorage.setItem(ACTIVE_LANG_KEY, String(id));
  } catch {
    // localStorage unavailable: the choice just won't persist.
  }
}

/** Populate + wire the header language switcher (`#app-lang-select`). */
function setupLanguageSwitcher(languages: LanguageListItem[], activeId: number): void {
  const select = document.getElementById('app-lang-select') as HTMLSelectElement | null;
  if (!select) return;

  select.innerHTML = '';
  for (const lang of languages) {
    const option = document.createElement('option');
    option.value = String(lang.id);
    option.textContent = lang.name;
    option.selected = lang.id === activeId;
    select.appendChild(option);
  }
  select.addEventListener('change', () => {
    const id = parseInt(select.value, 10) || 0;
    if (id > 0) {
      storeLang(id);
      // Reload so textsGroupedApp re-reads the config for the new language.
      window.location.reload();
    }
  });
  if (languages.length > 0) {
    select.hidden = false;
  }
}

async function start(): Promise<void> {
  const res = await LanguagesApi.list();
  const languages = res.data?.languages ?? [];
  const ids = new Set(languages.map((l) => l.id));

  const stored = readStoredLang();
  const current = res.data?.currentLanguageId ?? 0;
  const activeId =
    (stored && ids.has(stored) && stored) ||
    (current && ids.has(current) && current) ||
    (languages[0]?.id ?? 0);

  setupLanguageSwitcher(languages, activeId);
  injectConfig('texts-grouped-config', { activeLanguageId: activeId });

  await bootAppPage({ requireAuth: true });
}

void start();

/**
 * Build-time PHP-view -> static-HTML prerenderer for the bundled ("Model B")
 * client.
 *
 * The bundled Capacitor/F-Droid client (../lukaisu) ships the reader, library
 * and connect surfaces as static HTML that boots against a remote /api/v1 with
 * NO PHP server rendering the page. Rather than hand-copy the Alpine scaffolds
 * (which would drift from the server templates), this reads the *actual* PHP
 * view files and mechanically converts the small, fixed set of PHP constructs
 * those specific views use into static HTML:
 *
 *   - `<?php require __DIR__ . '/partial.php'; ?>`  -> inline the partial
 *   - `PageLayoutHelper::buildNavbarPlaceholder(p)` -> the `#navbar-root` div,
 *                                                       kept so the client
 *                                                       hydrates the real navbar
 *                                                       from GET /api/v1/navbar
 *   - `<?php if (...) : ?> ... <?php endif; ?>`      -> dropped (only the
 *                                                       optional source-link
 *                                                       block uses this)
 *   - `<?php foreach ([a,b,..] as $x) : ?>..endforeach`-> body repeated per item
 *   - `IconHelper::render('name', [...])`           -> `<i data-lucide=...>`
 *   - `__e('ns.key')` / `__('ns.key')`              -> the English string
 *   - `<?php echo $textId; ?>`                      -> a `{{TEXT_ID}}` token the
 *                                                       client fills at runtime
 *   - the `id="*-config"` json_encode blob          -> emptied (the client
 *                                                       injects the real config)
 *
 * Anything left over (page-header `<?php ... ?>` blocks, unhandled tags) is
 * stripped with a warning so no PHP can leak into the shipped HTML. This is
 * deliberately NOT a general PHP engine — it only understands the constructs
 * present in the three target views and their partials.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { readFileSync, existsSync } from 'fs';
import { dirname, resolve, basename } from 'path';
import { fileURLToPath } from 'url';

const REPO_ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const LOCALE_DIR = resolve(REPO_ROOT, 'locale/en');

/** Lazily-loaded `namespace -> { "flat.key": "value" }` map for the en locale. */
let localeCache = null;

function loadLocale() {
  if (localeCache) return localeCache;
  localeCache = {};
  // Namespaces are filenames; keys inside are flat dot-strings.
  const namespaces = [
    'common', 'navbar', 'text', 'review', 'vocabulary', 'language',
    'home', 'feed', 'tags', 'admin', 'user', 'book', 'dictionary'
  ];
  for (const ns of namespaces) {
    const file = resolve(LOCALE_DIR, ns + '.json');
    if (existsSync(file)) {
      try {
        localeCache[ns] = JSON.parse(readFileSync(file, 'utf8'));
      } catch {
        localeCache[ns] = {};
      }
    }
  }
  return localeCache;
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/**
 * Resolve a translation key the way the PHP Translator does: the namespace is
 * the first dot segment, the remainder is the flat key looked up in that
 * namespace's file (English). Falls back to the bare key so misses are visible.
 */
function translate(key) {
  const locale = loadLocale();
  const dot = key.indexOf('.');
  if (dot === -1) {
    return locale.common?.[key] ?? key;
  }
  const ns = key.slice(0, dot);
  const rest = key.slice(dot + 1);
  const value = locale[ns]?.[rest];
  return value !== undefined ? value : key;
}

/** Convert an `IconHelper::render('name', [...])` call to a Lucide `<i>`. */
function renderIcon(name, attrsText) {
  let size = 16;
  let cls = '';
  if (attrsText) {
    const sizeMatch = attrsText.match(/'size'\s*=>\s*(\d+)/);
    if (sizeMatch) size = parseInt(sizeMatch[1], 10);
    const classMatch = attrsText.match(/'class'\s*=>\s*'([^']*)'/);
    if (classMatch) cls = classMatch[1];
  }
  const classAttr = ('icon' + (cls ? ' ' + cls : '')).trim();
  return `<i data-lucide="${escapeHtml(name)}" class="${escapeHtml(classAttr)}" `
    + `style="width:${size}px;height:${size}px"></i>`;
}

// Matches an IconHelper::render echo, with optional FQ namespace prefix and an
// optional second array argument.
const ICON_RE =
  /<\?(?:php\s+echo\s+|=\s*)(?:\\?[\w\\]+\\)?IconHelper::render\(\s*'([^']+)'\s*(?:,\s*(\[[\s\S]*?\]))?\s*\)\s*;?\s*\?>/g;

// Matches `__e('key')` / `__('key')` in either echo form.
const I18N_RE =
  /<\?(?:php\s+echo\s+|=\s*)__e?\(\s*'([^']+)'\s*\)\s*;?\s*\?>/g;

// Matches `<?php echo $var; ?>` / `<?= $var ?>` for the simple dynamic ids.
const VAR_RE = /<\?(?:php\s+echo\s+|=\s*)\$(\w+)\s*;?\s*\?>/g;

/** Tokens the client replaces at runtime, keyed by PHP variable name. */
const VAR_TOKENS = {
  textId: '{{TEXT_ID}}',
  langId: '{{LANG_ID}}'
};

/**
 * Expand a `<?php foreach ([literal, list] as $x) : ?> body <?php endforeach; ?>`
 * by repeating the body for each literal item (the only foreach the target
 * views use — the audio player's skip-seconds buttons).
 */
function expandForeach(html) {
  const re =
    /<\?php\s+foreach\s*\(\s*\[([^\]]*)\]\s+as\s+\$(\w+)\s*\)\s*:\s*\?>([\s\S]*?)<\?php\s+endforeach;\s*\?>/g;
  return html.replace(re, (_m, listText, varName, body) => {
    const items = listText.split(',').map((s) => s.trim()).filter(Boolean);
    const itemVarRe = new RegExp(
      '<\\?(?:php\\s+echo\\s+|=\\s*)\\$' + varName + '\\s*;?\\s*\\?>', 'g'
    );
    return items.map((item) => body.replace(itemVarRe, item)).join('');
  });
}

/**
 * Prerender a single PHP view file to static HTML.
 *
 * @param {string} absPath Absolute path to the .php view.
 * @param {(msg: string) => void} [warn] Sink for "stripped unhandled PHP" notes.
 * @returns {string} Static HTML.
 */
export function prerenderPhpView(absPath, warn = () => {}) {
  let html = readFileSync(absPath, 'utf8');
  const viewDir = dirname(absPath);

  // 1. Inline `require __DIR__ . '/partial.php'` (recursively).
  html = html.replace(
    /<\?php\s+require\s+__DIR__\s*\.\s*'\/([\w.\-/]+)'\s*;\s*\?>/g,
    (_m, rel) => {
      const partial = resolve(viewDir, rel);
      if (!existsSync(partial)) {
        warn(`partial not found: ${rel} (in ${basename(absPath)})`);
        return '';
      }
      return prerenderPhpView(partial, warn);
    }
  );

  // 2. Keep the navbar mount point. The server renders an empty placeholder the
  //    client hydrates from GET /api/v1/navbar; the bundle does exactly the
  //    same, so emit the div (carrying its current-page hint) rather than the
  //    PHP call, which step 9 would otherwise strip.
  html = html.replace(
    /<\?php\s+echo\s+(?:\\?[\w\\]+\\)?PageLayoutHelper::buildNavbarPlaceholder\(\s*(?:'([^']*)')?\s*\)\s*;?\s*\?>/g,
    (_m, page = '') =>
      `<div id="navbar-root" data-navbar-root data-current-page="${escapeHtml(page ?? '')}"></div>`
  );

  // 3. Expand literal-array foreach blocks (audio skip-seconds).
  html = expandForeach(html);

  // 4. Drop `<?php if (...) : ?> ... <?php endif; ?>` blocks. In these views
  //    the only such block is the optional source-link; the client surfaces a
  //    source link from the API instead.
  html = html.replace(/<\?php\s+if\b[\s\S]*?<\?php\s+endif;\s*\?>/g, '');

  // 5. Empty the page-config json_encode blob — the client injects real config.
  html = html.replace(
    /<\?php\s+echo\s+json_encode\([\s\S]*?\)\s*;?\s*\?>/g,
    '{}'
  );

  // 6. IconHelper::render(...) -> <i data-lucide>.
  html = html.replace(ICON_RE, (_m, name, attrs) => renderIcon(name, attrs));

  // 7. __e / __ -> English string.
  html = html.replace(I18N_RE, (_m, key) => escapeHtml(translate(key)));

  // 8. Simple dynamic ids -> runtime tokens.
  html = html.replace(VAR_RE, (m, name) => {
    if (name in VAR_TOKENS) return VAR_TOKENS[name];
    warn(`unhandled $${name} (in ${basename(absPath)}) -> stripped`);
    return '';
  });

  // 8b. Rewrite `printFilePath("sounds/...")` to a bundled relative path. The
  //     app build copies assets/sounds into dist-app/sounds, so the review
  //     feedback `<audio>` sources resolve. Other printFilePath targets (e.g.
  //     server-only CSS) are intentionally left for step 9 to strip.
  html = html.replace(
    /<\?php\s+(?:\\?[\w\\]+\\)?StringUtils::printFilePath\(\s*["'](sounds\/[\w.\-/]+)["']\s*\)\s*;?\s*\?>/g,
    (_m, path) => './' + path
  );

  // 9. Strip anything still left (page-header blocks, unhandled constructs).
  html = html.replace(/<\?(?:php|=)[\s\S]*?\?>/g, (m) => {
    const snippet = m.replace(/\s+/g, ' ').slice(0, 60);
    warn(`stripped leftover PHP (in ${basename(absPath)}): ${snippet}`);
    return '';
  });

  // 10. Drop `<link>`/`<source>` tags whose URL emptied out when a PHP helper
  //     that produced a server path (printFilePath, UrlUtilities::url) was
  //     stripped: `href=""` would refetch the current document, and a
  //     `<source src="">` in an <audio> has no bundled asset to point at (e.g.
  //     review feedback sounds are not bundled — playback is simply silent).
  html = html.replace(/<link\b[^>]*\bhref=""[^>]*>/g, '');
  html = html.replace(/<source\b[^>]*\bsrc=""[^>]*>/g, '');

  return html.trim();
}

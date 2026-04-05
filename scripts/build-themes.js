/**
 * Theme CSS minifier and asset copier.
 *
 * This script processes theme folders from src/frontend/css/themes/ to dist/themes/.
 * CSS files are minified, other files (images, etc.) are copied as-is.
 * Also builds all base CSS files from src/frontend/css/base/ to dist/css/
 * and copies the legacy pgm.js to dist/js/.
 *
 * Usage: node scripts/build-themes.js
 */

import { readdir, readFile, writeFile, mkdir, copyFile, stat } from 'fs/promises';
import { join, extname } from 'path';
import { existsSync } from 'fs';

const THEMES_SRC = 'src/frontend/css/themes';
const THEMES_DEST = 'dist/themes';
const BASE_CSS_SRC = 'src/frontend/css/base';
const BASE_CSS_DEST = 'dist/css';
const LEGACY_JS_SRC = 'assets/js/pgm.js';
const LEGACY_JS_DEST = 'dist/js/pgm.js';

/**
 * Simple CSS minifier - removes comments, extra whitespace, and newlines.
 * @param {string} css - The CSS content to minify
 * @returns {string} Minified CSS
 */
function minifyCSS(css) {
  return css
    // Remove comments
    .replace(/\/\*[\s\S]*?\*\//g, '')
    // Remove newlines and carriage returns
    .replace(/[\r\n]+/g, '')
    // Collapse multiple spaces to single space
    .replace(/\s+/g, ' ')
    // Remove spaces around special characters
    .replace(/\s*([{}:;,>~+])\s*/g, '$1')
    // Remove trailing semicolons before closing braces
    .replace(/;}/g, '}')
    // Trim
    .trim();
}

/**
 * Process a single theme folder.
 * @param {string} themeName - Name of the theme folder
 */
async function processTheme(themeName) {
  const srcDir = join(THEMES_SRC, themeName);
  const destDir = join(THEMES_DEST, themeName);

  // Create destination directory if it doesn't exist
  if (!existsSync(destDir)) {
    await mkdir(destDir, { recursive: true });
  }

  // Read all files in the theme folder
  const files = await readdir(srcDir);

  for (const file of files) {
    const srcPath = join(srcDir, file);
    const destPath = join(destDir, file);

    // Skip directories
    const fileStat = await stat(srcPath);
    if (fileStat.isDirectory()) {
      continue;
    }

    if (extname(file).toLowerCase() === '.css') {
      // Minify CSS files
      const css = await readFile(srcPath, 'utf-8');
      const minified = minifyCSS(css);
      await writeFile(destPath, minified);
    } else {
      // Copy other files as-is
      await copyFile(srcPath, destPath);
    }
  }
}

/**
 * Build all base CSS files from src/frontend/css/base/ to dist/css/.
 */
async function buildBaseCSS() {
  if (!existsSync(BASE_CSS_DEST)) {
    await mkdir(BASE_CSS_DEST, { recursive: true });
  }

  const files = await readdir(BASE_CSS_SRC);
  const cssFiles = files.filter(f => extname(f).toLowerCase() === '.css');

  for (const file of cssFiles) {
    const css = await readFile(join(BASE_CSS_SRC, file), 'utf-8');
    await writeFile(join(BASE_CSS_DEST, file), minifyCSS(css));
  }

  console.log(`Built ${cssFiles.length} base CSS files: ${cssFiles.join(', ')}`);
}

/**
 * Copy legacy pgm.js to dist/js/ for backward compatibility.
 */
async function copyLegacyJS() {
  if (!existsSync(LEGACY_JS_SRC)) {
    return;
  }
  const destDir = join(LEGACY_JS_DEST, '..');
  if (!existsSync(destDir)) {
    await mkdir(destDir, { recursive: true });
  }
  await copyFile(LEGACY_JS_SRC, LEGACY_JS_DEST);
}

/**
 * Main function - process all themes.
 */
async function main() {
  // Build base CSS and copy legacy JS
  await buildBaseCSS();
  await copyLegacyJS();

  // Ensure destination directory exists
  if (!existsSync(THEMES_DEST)) {
    await mkdir(THEMES_DEST, { recursive: true });
  }

  // Get all theme folders
  const entries = await readdir(THEMES_SRC, { withFileTypes: true });
  const themes = entries.filter(e => e.isDirectory()).map(e => e.name);

  for (const theme of themes) {
    await processTheme(theme);
  }

  console.log(`Processed ${themes.length} themes: ${themes.join(', ')}`);
}

main().catch(err => {
  console.error('Error building themes:', err);
  process.exit(1);
});

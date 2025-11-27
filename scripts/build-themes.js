/**
 * Theme CSS minifier and asset copier.
 *
 * This script processes theme folders from src/frontend/css/themes/ to assets/themes/.
 * CSS files are minified, other files (images, etc.) are copied as-is.
 *
 * Usage: node scripts/build-themes.js
 */

import { readdir, readFile, writeFile, mkdir, copyFile, stat } from 'fs/promises';
import { join, extname } from 'path';
import { existsSync } from 'fs';

const THEMES_SRC = 'src/frontend/css/themes';
const THEMES_DEST = 'assets/themes';

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
      console.log(`  ✓ ${file} (minified)`);
    } else {
      // Copy other files as-is
      await copyFile(srcPath, destPath);
      console.log(`  ✓ ${file} (copied)`);
    }
  }
}

/**
 * Main function - process all themes.
 */
async function main() {
  console.log('Building themes...\n');

  // Ensure destination directory exists
  if (!existsSync(THEMES_DEST)) {
    await mkdir(THEMES_DEST, { recursive: true });
  }

  // Get all theme folders
  const entries = await readdir(THEMES_SRC, { withFileTypes: true });
  const themes = entries.filter(e => e.isDirectory()).map(e => e.name);

  for (const theme of themes) {
    console.log(`Processing theme: ${theme}`);
    await processTheme(theme);
    console.log('');
  }

  console.log(`Done! Built ${themes.length} themes.`);
}

main().catch(err => {
  console.error('Error building themes:', err);
  process.exit(1);
});

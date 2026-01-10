#!/usr/bin/env node
/**
 * Generate PWA icons from the source LWT icon.
 *
 * This script creates 192x192 and 512x512 PNG icons required for PWA
 * installation from the existing lwt_icon_big.png.
 *
 * Usage: node scripts/generate-pwa-icons.js
 *
 * Requires: sharp (npm install -D sharp)
 */

import { existsSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const rootDir = resolve(__dirname, '..');

const SOURCE_ICON = resolve(rootDir, 'assets/images/lwt_icon_big.png');
const OUTPUT_DIR = resolve(rootDir, 'assets/images');

const SIZES = [
  { size: 192, name: 'pwa-icon-192.png' },
  { size: 512, name: 'pwa-icon-512.png' },
];

async function generateIcons() {
  // Check if source exists
  if (!existsSync(SOURCE_ICON)) {
    console.error(`Source icon not found: ${SOURCE_ICON}`);
    process.exit(1);
  }

  // Try to import sharp
  let sharp;
  try {
    sharp = (await import('sharp')).default;
  } catch {
    console.error('Error: sharp module not found.');
    console.error('Please install it with: npm install -D sharp');
    console.error('');
    console.error('Alternatively, manually create these icons:');
    SIZES.forEach(({ size, name }) => {
      console.error(`  - ${OUTPUT_DIR}/${name} (${size}x${size})`);
    });
    process.exit(1);
  }

  console.log('Generating PWA icons...');

  for (const { size, name } of SIZES) {
    const outputPath = resolve(OUTPUT_DIR, name);

    try {
      await sharp(SOURCE_ICON)
        .resize(size, size, {
          fit: 'contain',
          background: { r: 255, g: 255, b: 255, alpha: 0 },
        })
        .png()
        .toFile(outputPath);

      console.log(`  Created: ${name} (${size}x${size})`);
    } catch (error) {
      console.error(`  Failed to create ${name}:`, error.message);
    }
  }

  console.log('Done!');
}

generateIcons();

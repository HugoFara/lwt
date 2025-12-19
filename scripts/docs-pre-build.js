/**
 * Documentation Pre-build Script
 *
 * Copies root markdown files to the docs-src directory before VitePress build.
 * This allows files like CHANGELOG.md, CONTRIBUTING.md, and UNLICENSE.md to
 * remain in the repository root while being included in the documentation.
 */

import { mkdirSync, existsSync, readFileSync, writeFileSync, cpSync } from 'fs'
import { dirname, join } from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)
const rootDir = join(__dirname, '..')
const docsDir = join(rootDir, 'docs-src')

/**
 * Files to copy from root to docs-src
 * Each entry has:
 *   - src: Source path relative to project root
 *   - dest: Destination path relative to docs-src
 *   - frontmatter: Optional frontmatter to prepend
 */
const filesToCopy = [
  {
    src: 'CHANGELOG.md',
    dest: 'changelog.md',
    frontmatter: `---
title: Changelog
description: Version history and release notes for Learning with Texts
---

`
  },
  {
    src: 'CONTRIBUTING.md',
    dest: 'developer/contributing.md',
    frontmatter: `---
title: Contributing
description: How to contribute to the Learning with Texts project
---

`
  },
  {
    src: 'UNLICENSE.md',
    dest: 'legal/license.md',
    frontmatter: `---
title: License
description: Learning with Texts license (Public Domain / Unlicense)
---

`
  }
]

console.log('📄 Documentation Pre-build Script')
console.log('==================================')

for (const { src, dest, frontmatter } of filesToCopy) {
  const srcPath = join(rootDir, src)
  const destPath = join(docsDir, dest)

  if (existsSync(srcPath)) {
    // Ensure destination directory exists
    mkdirSync(dirname(destPath), { recursive: true })

    // Read source file
    let content = readFileSync(srcPath, 'utf-8')

    // Add frontmatter if specified
    if (frontmatter) {
      // Remove existing frontmatter if present
      content = content.replace(/^---[\s\S]*?---\n*/, '')
      content = frontmatter + content
    }

    // Escape Vue template syntax {{ }} to prevent VitePress from interpreting it
    // Only escape occurrences outside of code blocks (backticks)
    // Replace {{ with escaped version
    content = content.replace(/(?<!`)\{\{([^}`]+)\}\}(?!`)/g, (match, inner) => {
      // Use HTML entity escaping
      return `&#123;&#123;${inner}&#125;&#125;`
    })

    // Write to destination
    writeFileSync(destPath, content, 'utf-8')
    console.log(`✓ Copied ${src} -> docs-src/${dest}`)
  } else {
    console.warn(`⚠ Source file not found: ${src}`)
  }
}

// Copy assets/images to public folder for VitePress
const imagesSource = join(rootDir, 'assets', 'images')
const imagesDest = join(docsDir, 'public', 'assets', 'images')

if (existsSync(imagesSource)) {
  mkdirSync(imagesDest, { recursive: true })
  cpSync(imagesSource, imagesDest, { recursive: true })
  console.log(`✓ Copied assets/images -> docs-src/public/assets/images/`)
}

console.log('')
console.log('Pre-build complete!')

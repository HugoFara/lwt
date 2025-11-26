# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## About This Project

Learning with Texts (LWT) is a self-hosted web application for language learning by reading. This is a third-party community-maintained fork that improves upon the official SourceForge version with modern PHP support (8.1-8.4), smaller database size, better mobile support, and active development.

**Tech Stack:**

- Backend: PHP 8.1+ with MySQLi
- Frontend: Vanilla JavaScript, jQuery
- Database: MySQL/MariaDB with MyISAM engine
- Build Tools: Composer (PHP), NPM (JS/docs)

## Development Setup

### Initial Setup

```bash
git clone https://github.com/HugoFara/lwt
cd lwt
composer install --dev
npm install  # Optional, for API testing and JS documentation
```

### Database Configuration

Copy `.env.example` to `.env` and update the database credentials:

```bash
cp .env.example .env
# Edit .env with your database credentials
```

The `.env` file contains:

- `DB_HOST` - Database server (default: localhost)
- `DB_USER` - Database username (default: root)
- `DB_PASSWORD` - Database password
- `DB_NAME` - Database name (default: learning-with-texts)
- `DB_SOCKET` - Optional database socket
- `DB_TABLE_PREFIX` - Optional table prefix for multiple instances

## Common Commands

### Testing

```bash
composer test                    # Run PHPUnit tests
./vendor/bin/phpunit             # Alternative test command
npm test                         # Test REST API (requires Node.js)
```

### Code Quality

```bash
./vendor/bin/psalm               # Static analysis and security checks
php ./vendor/bin/squizlabs/phpcs.phar [file]   # Check code style
php ./vendor/bin/squizlabs/phpcbf.phar [file]  # Auto-fix code style
```

### Asset Building

```bash
npm run build                    # Build Vite JS/CSS bundles
npm run build:themes             # Build theme CSS files
npm run build:all                # Build everything (Vite + themes)
npm run dev                      # Start Vite dev server with HMR
npm run typecheck                # Run TypeScript type checking
composer build                   # Alias for npm run build:all
```

**Frontend Development Workflow:**

1. Run `npm run dev` for development with Hot Module Replacement
2. Run `npm run typecheck` to check TypeScript errors
3. Run `npm run build:all` (or `composer build`) for production build

**Note:** All frontend assets are built with npm/Node.js. TypeScript source files are in `src/frontend/js/*.ts`.

### Documentation Generation

```bash
composer info.html               # Regenerate single-file documentation
composer doc                     # Regenerate all documentation (PHP + JS + Markdown)
composer clean-doc               # Clear all generated documentation
```

## Architecture Overview

### File Structure

**Root Directory:** Contains `index.php` as the front controller entry point.

**Key Directories:**

- `assets/` - All static assets (generated and third-party)
  - `assets/icons/` - UI icons (PNG files)
  - `assets/images/` - Documentation images, logos, app icons
  - `assets/css/` - Legacy minified CSS (generated from `src/frontend/css/`)
  - `assets/css/vite/` - Vite-bundled CSS with hashed filenames
  - `assets/js/` - Legacy minified JavaScript
  - `assets/js/vite/` - Vite-bundled JavaScript with hashed filenames
  - `assets/.vite/` - Vite manifest for PHP asset loading
  - `assets/themes/` - Minified themes (generated from `src/frontend/themes/`)
  - `assets/sounds/` - Audio feedback files
  - `assets/vendor/iui/` - iUI mobile framework (third-party)

- `src/` - Source files
  - `src/backend/` - PHP application code
    - `src/backend/Controllers/` - MVC Controllers
    - `src/backend/Legacy/` - Legacy PHP files (being migrated)
    - `src/backend/Router/` - Routing system
    - `src/backend/Core/` - Core PHP modules (database, utilities)
      - `database_connect.php` - Database connection and query wrappers
      - `session_utility.php` - Session management and utility functions
      - `kernel_utility.php` - Core utilities that don't require full session
      - `ajax_*.php` - AJAX endpoints (15+ files)
      - `classes/` - PHP classes (GoogleTranslate, Language, Term, Text)
  - `src/tools/` - Build tools (minifier, markdown converter)
  - `src/frontend/` - Frontend assets (compiled to `assets/`)
    - `src/frontend/js/` - TypeScript source files (built with Vite)
    - `src/frontend/js/types/` - TypeScript type declarations
    - `src/frontend/js/third_party/` - Third-party JavaScript libraries
    - `src/frontend/css/` - CSS source files
    - `src/frontend/themes/` - Theme source files

- `resources/` - Non-runtime resources
  - `resources/anki/` - Anki flashcard export templates

- `db/` - Database schema, migrations, and seed data
  - `db/schema/baseline.sql` - Complete database schema
  - `db/migrations/` - Migration files for database updates
  - `db/seeds/demo.sql` - Demo database for testing/onboarding

- `tests/` - PHPUnit tests and API tests
- `docs/` - Markdown documentation and generated API docs
- `media/` - User media files (audio for texts)

### Database Architecture

LWT uses a MySQL/MariaDB database with MyISAM engine. Key tables:

- `languages` - Language configurations (text parsing rules, dictionaries)
- `texts` - User texts for reading
- `archivedtexts` - Archived texts with annotations
- `words` - User vocabulary with status tracking (1-5, 98=ignored, 99=well-known)
- `sentences` - Parsed sentences from texts
- `textitems2` - Word occurrences in texts (links words to sentences)
- `tags`, `tags2`, `texttags`, `wordtags` - Tagging system
- `feeds` - RSS feed sources for automatic text import
- `settings` - Application settings (key-value pairs)
- `_migrations` - Database version tracking

**Word Status Values:**

- 1-5: Learning stages (1=new, 5=learned)
- 98: Ignored words
- 99: Well-known words

### Code Organization Principles

**Frontend-Backend Split:**

- Root PHP files handle routing, HTML generation, and form processing
- `src/backend/Core/` provides business logic, database operations, and utilities
- AJAX endpoints in `src/backend/Core/ajax_*.php` provide dynamic functionality
- Client-side JS in `src/frontend/js/` handles interactivity

**Text Processing Flow:**

1. User imports/creates text (`edit_texts.php`)
2. Text is parsed into sentences and word items (`session_utility.php` functions)
3. Items stored in `sentences` and `textitems2` tables
4. Reading interface (`do_text.php`) displays annotated text
5. User clicks unknown words to look up and save translations
6. Words tracked with status and reviewed via `do_test.php`

**Theme System:**

- Themes in `src/frontend/themes/[theme-name]/` with CSS files
- Relative paths auto-adjusted during minification
- Can reference shared images from `assets/css/images/` using `../../../assets/css/theimage`
- Missing theme files fall back to `src/frontend/css/` defaults
- Generated themes output to `assets/themes/`

### REST API

Base URL: `/api.php/v1` (RESTful, currently no authentication)

Key endpoints in `api.php`:

- `/media-files` - Get audio/video file paths
- `/languages/{id}/reading-configuration` - Language parsing rules
- `/phonetic-reading` - Get phonetic transcription
- More documented in `docs/api.md`

## Working with the Codebase

### Modifying PHP Code

PHP code is spread across root files (user-facing pages) and `src/backend/Core/` (shared logic). When editing:

1. Use functions from `session_utility.php` for database queries and utilities
2. Follow the existing pattern: root files include `Core/session_utility.php` which includes `database_connect.php`
3. Use `do_mysqli_query()` wrapper instead of direct `mysqli_query()` for better error handling
4. Database queries use `$tbpref` global variable for table prefix (usually empty, but supports multi-tenant)

### Modifying JavaScript/TypeScript

1. Edit TypeScript source files in `src/frontend/js/*.ts`
2. Run `npm run dev` for development with Hot Module Replacement (HMR)
3. Run `npm run typecheck` to verify TypeScript types
4. Before committing, run `npm run build` to generate production bundles
5. TypeScript modules in `src/frontend/js/`:
   - `main.ts` - Vite entry point, imports all modules
   - `pgm.ts` - Main program logic and utilities
   - `jq_pgm.ts` - jQuery-dependent functionality
   - `text_events.ts` - Text reading interface events
   - `audio_controller.ts` - Audio playback
   - `translation_api.ts` - Translation API integration
   - `user_interactions.ts` - UI interactions
   - `overlib_interface.ts` - Popup/tooltip interface
   - `unloadformcheck.ts` - Form change tracking
   - `jq_feedwizard.ts` - Feed wizard functionality
   - `types/globals.d.ts` - Type declarations for PHP-injected globals

### Modifying CSS

1. Edit CSS source files in `src/frontend/css/`
2. CSS is imported in `main.ts` and bundled by Vite
3. Run `npm run build` to regenerate production bundles

### Creating/Editing Themes

1. Create folder `src/frontend/themes/your-theme/`
2. Add CSS files (don't need all files from `src/frontend/css/`, missing files fall back to defaults)
3. Reference images: `../../../assets/css/images/file.png` (for shared images) or `./file.png` (theme-specific)
4. Run `npm run build:themes` to regenerate theme CSS files

### Writing Tests

- PHP tests go in `tests/` directory, use PHPUnit 10.5+
- API tests in `tests/api.test.js`, use Mocha and require `npm test`
- Run tests before submitting PRs: `composer test`

### Contributing Workflow

Branches:

- `master` - Stable, bug-free releases
- `dev` - Unstable development branch
- `official` - Tracks official LWT releases

Before committing:

1. Run `composer test` and `./vendor/bin/psalm` to check for issues
2. Ensure code follows PSR standards (use phpcs/phpcbf)
3. If you modified `src/frontend/js/`, `src/frontend/css/`, or `src/frontend/themes/`, run `composer minify`
4. Update documentation if adding new features

### Version Release Process

(For maintainers)

1. Update `CHANGELOG.md` with release number and date
2. Update `LWT_APP_VERSION` and `LWT_RELEASE_DATE` in `src/backend/Core/kernel_utility.php`
3. Update `PROJECT_NUMBER` in `Doxyfile`
4. Run `composer doc` to regenerate documentation
5. Commit: `git commit -m "Regenerates documentation for release X.Y.Z"`
6. Tag: `git tag -a X.Y.Z` and push
7. Create GitHub release after CI passes

## Important Conventions

- **Character Encoding:** UTF-8 throughout (database and PHP)
- **Table Prefix:** Use `$tbpref` global variable before table names (empty string by default)
- **SQL Queries:** Always use `do_mysqli_query()` wrapper for automatic error handling
- **Settings:** Use `getSettingWithDefault()` for application settings
- **Language IDs:** Stored as `LgID` (tinyint), referenced throughout as language identifier
- **Text IDs:** Stored as `TxID` (smallint) for active texts, `AtID` for archived texts
- **Word IDs:** Stored as `WoID` (mediumint)

## Database Migrations

Migration files in `db/migrations/` track schema changes. Format: `YYYYMMDD_HHMMSS_description.sql`

The `_migrations` table tracks applied migrations. When adding migrations:

1. Create new file with timestamp and descriptive name
2. Add SQL commands for schema changes
3. Update `db/schema/baseline.sql` to reflect final schema state
4. Test on clean database install

## MeCab Support

For Japanese word-by-word translation, LWT supports MeCab integration. This is optional and requires external MeCab installation on the server.

## Version 3 Changes

Version 3 introduces major architectural changes. See `docs/developer/v3-changes.md` for full details:

- **Front Controller Pattern:** All requests routed through `index.php`
- **MVC Structure:** New controllers in `src/backend/Controllers/`
- **Routing System:** Clean URLs (e.g., `/text/read` instead of `do_text.php`)
- **LWT_Globals Class:** Type-safe access to global state (replaces `global $tbpref`, etc.)
- **Environment Config:** `.env` file support (replaces `connect.inc.php`)

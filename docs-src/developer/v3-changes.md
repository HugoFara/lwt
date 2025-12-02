# LWT v3 Changes

This document describes all the architectural and structural changes introduced in version 3.0.0 of Learning with Texts (LWT).

## Overview

Version 3 represents a major architectural refactoring of LWT, transitioning from a collection of 60+ standalone PHP files in the root directory to a proper MVC (Model-View-Controller) structure with a front controller pattern. This change improves code organization, maintainability, and sets the foundation for future improvements.

## Key Changes

### 1. Front Controller Pattern

**Before (v2):** Each PHP page was accessed directly (e.g., `do_text.php`, `edit_words.php`).

**After (v3):** All requests are routed through `index.php`, which serves as the single entry point.

- New `index.php` front controller handles all incoming requests
- Added `.htaccess` with Apache mod_rewrite rules to route requests
- Clean URLs supported (e.g., `/text/read` instead of `do_text.php`)
- Automatic 301 redirects from legacy URLs to new routes

### 2. New Directory Structure

#### Assets Reorganization

Static assets have been consolidated into the `assets/` directory:

| Old Location | New Location |
|-------------|--------------|
| `icn/` | `assets/icons/` |
| `img/` | `assets/images/` |
| `css/` | `assets/css/` |
| `js/` | `assets/js/` |
| `themes/` | `assets/themes/` |
| `sounds/` | `assets/sounds/` |
| `iui/` | `assets/vendor/iui/` |

#### PHP Source Reorganization

All PHP source files have been moved to `src/backend/`:

| Old Location | New Location |
|-------------|--------------|
| `inc/` | `src/backend/Core/` |
| Root PHP files (`do_text.php`, etc.) | `src/backend/Legacy/` |
| (new) | `src/backend/Controllers/` |
| (new) | `src/backend/Router/` |
| (new) | `src/backend/Services/` |
| (new) | `src/backend/Views/` |
| (new) | `src/backend/Api/` |

#### Frontend Source Reorganization

Frontend source files moved from `src/` to `src/frontend/`:

| Old Location | New Location |
|-------------|--------------|
| `src/js/` | `src/frontend/js/` |
| `src/css/` | `src/frontend/css/base/` |
| `src/themes/` | `src/frontend/css/themes/` |

### 3. Routing System

A new routing system has been implemented in `src/backend/Router/`:

#### Router (`src/backend/Router/Router.php`)

The `Router` class provides:

- **Route registration:** Map URL paths to handlers
- **Pattern matching:** Support for dynamic routes (e.g., `/text/{id}`)
- **Prefix routes:** Handle API endpoints with multiple sub-paths
- **Legacy URL support:** Automatic redirects from old `.php` URLs
- **HTTP method routing:** Different handlers for GET, POST, etc.
- **404/500 error handling:** Built-in error pages

#### Routes Configuration (`src/backend/Router/routes.php`)

All application routes are defined in this file, organized by feature:

- **Home:** `/`, `/index.php`
- **Text:** `/text/read`, `/text/edit`, `/texts`, `/text/display`, `/text/print`, etc.
- **Word/Term:** `/word/edit`, `/words/edit`, `/word/new`, `/word/show`, etc.
- **Test:** `/test`, `/test/set-status`
- **Language:** `/languages`, `/languages/select-pair`
- **Tags:** `/tags`, `/tags/text`
- **Feeds:** `/feeds`, `/feeds/edit`, `/feeds/wizard`
- **Admin:** `/admin/backup`, `/admin/wizard`, `/admin/statistics`, `/admin/settings`, etc.
- **Mobile:** `/mobile`, `/mobile/start`
- **API:** `/api/v1`, `/api/translate`, `/api/google`, `/api/glosbe`
- **WordPress:** `/wordpress/start`, `/wordpress/stop`

### 4. MVC Controllers

Controller classes in `src/backend/Controllers/`:

| Controller | Purpose |
|-----------|---------|
| `BaseController.php` | Abstract base class with common functionality |
| `HomeController.php` | Home page |
| `TextController.php` | Text reading and management |
| `TextPrintController.php` | Text printing (plain and annotated) |
| `WordController.php` | Word/term management |
| `TestController.php` | Testing/review interface |
| `LanguageController.php` | Language configuration |
| `TagsController.php` | Tag management |
| `FeedsController.php` | RSS feed management |
| `AdminController.php` | Admin functions (backup, settings, etc.) |
| `MobileController.php` | Mobile interface |
| `ApiController.php` | REST API endpoints |
| `TranslationController.php` | Translation API integration |
| `WordPressController.php` | WordPress integration |

#### BaseController Features

The `BaseController` provides helper methods for all controllers:

- `render()` / `endRender()` - Page rendering with LWT header/footer
- `param()` / `get()` / `post()` - Request parameter access
- `isPost()` / `isGet()` - HTTP method checks
- `redirect()` - URL redirection
- `query()` / `execute()` / `getValue()` - Database operations
- `table()` - Table name with prefix
- `escape()` / `escapeNonNull()` - SQL escaping
- `json()` - JSON response output
- `sessionParam()` / `dbParam()` - Session/settings parameter handling

### 5. Services Layer

Version 3 introduces a Services layer that extracts business logic from controllers:

| Service | Purpose |
|---------|---------|
| `BackupService.php` | Database backup and restore operations |
| `DatabaseWizardService.php` | Database setup and configuration |
| `DemoService.php` | Demo data installation |
| `FeedService.php` | RSS feed management logic |
| `HomeService.php` | Home page data and statistics |
| `LanguageService.php` | Language CRUD operations |
| `MobileService.php` | Mobile interface logic |
| `ServerDataService.php` | Server information gathering |
| `SettingsService.php` | Application settings management |
| `StatisticsService.php` | Usage statistics calculation |
| `TableSetService.php` | Table set management |
| `TagService.php` | Tag management operations |
| `TestService.php` | Test/review session logic |
| `TextDisplayService.php` | Annotated text display |
| `TextPrintService.php` | Text printing and export |
| `TextService.php` | Text CRUD and processing |
| `TranslationService.php` | Translation API integration |
| `TtsService.php` | Text-to-speech configuration |
| `WordPressService.php` | WordPress integration logic |
| `WordService.php` | Word/term management operations |
| `WordListService.php` | Word list filtering, pagination, and bulk operations |
| `WordUploadService.php` | Word import/upload operations |

Services are located in `src/backend/Services/` and follow the pattern of extracting complex business logic from controllers for better testability and maintainability.

### 6. Views Architecture

Version 3 introduces a proper Views directory structure in `src/backend/Views/`:

| Directory | Purpose |
|-----------|---------|
| `Admin/` | Admin interface templates |
| `Feed/` | Feed management templates |
| `Home/` | Home page templates |
| `Language/` | Language configuration templates |
| `Mobile/` | Mobile interface templates |
| `Tags/` | Tag management templates |
| `Text/` | Text reading/editing templates |
| `TextPrint/` | Text printing templates |
| `Word/` | Word/term templates |

Additional helper classes:

- `TestViews.php` - Test interface view logic

### 7. Legacy File Migration

All 59 root-level PHP page files have been fully migrated to the MVC pattern with Controllers, Services, and Views. The `src/backend/Legacy/` directory has been removed as all files have been migrated.

The following table shows the migration status of all original files:

| Old Filename | Migration Status |
|-------------|------------------|
| `do_text.php` | Fully migrated to `TextController` |
| `do_text_header.php` | Fully migrated to `TextController` |
| `do_text_text.php` | Fully migrated to `TextController` |
| `edit_texts.php` | Fully migrated to `TextController` |
| `display_impr_text.php` | Fully migrated to `TextController` |
| `print_impr_text.php` | Fully migrated to `TextPrintController` |
| `print_text.php` | Fully migrated to `TextPrintController` |
| `check_text.php` | Fully migrated to `TextController` |
| `edit_archivedtexts.php` | Fully migrated to `TextController` |
| `long_text_import.php` | Fully migrated to `TextController` |
| `set_text_mode.php` | Fully migrated to `TextController` |
| `do_test.php` | Fully migrated to `TestController` |
| `do_test_header.php` | Fully migrated to `TestController` |
| `do_test_table.php` | Fully migrated to `TestController` |
| `do_test_test.php` | Fully migrated to `TestController` |
| `set_test_status.php` | Fully migrated to `TestController` |
| `edit_word.php` | Fully migrated to `WordController` |
| `edit_words.php` | Fully migrated to `WordController@list` |
| `edit_mword.php` | Fully migrated to `WordController@editMulti` |
| `edit_tword.php` | Fully migrated to `WordController` |
| `delete_word.php` | Fully migrated to `WordController` |
| `delete_mword.php` | Fully migrated to `WordController` |
| `new_word.php` | Fully migrated to `WordController` |
| `show_word.php` | Fully migrated to `WordController` |
| `upload_words.php` | Fully migrated to `WordController@upload` |
| `all_words_wellknown.php` | Fully migrated to `WordController` |
| `bulk_translate_words.php` | Fully migrated to `WordController@bulkTranslate` |
| `inline_edit.php` | Fully migrated to `WordController` |
| `insert_word_wellknown.php` | Fully migrated to `WordController` |
| `insert_word_ignore.php` | Fully migrated to `WordController` |
| `set_word_status.php` | Fully migrated to `WordController` |
| `edit_languages.php` | Fully migrated to `LanguageController` |
| `select_lang_pair.php` | Fully migrated to `LanguageController` |
| `edit_tags.php` | Fully migrated to `TagsController` |
| `edit_texttags.php` | Fully migrated to `TagsController` |
| `do_feeds.php` | Fully migrated to `FeedsController` |
| `edit_feeds.php` | Fully migrated to `FeedsController` |
| `feed_wizard.php` | Fully migrated to `FeedsController` |
| `backup_restore.php` | Fully migrated to `AdminController` |
| `database_wizard.php` | Fully migrated to `AdminController` |
| `statistics.php` | Fully migrated to `AdminController` |
| `install_demo.php` | Fully migrated to `AdminController` |
| `settings.php` | Fully migrated to `AdminController` |
| `set_word_on_hover.php` | Fully migrated to `AdminController` |
| `text_to_speech_settings.php` | Fully migrated to `AdminController` |
| `table_set_management.php` | Fully migrated to `AdminController` |
| `server_data.php` | Fully migrated to `AdminController` |
| `mobile.php` | Fully migrated to `MobileController` |
| `start.php` | Fully migrated to `MobileController` |
| `api.php` | Fully migrated to `ApiController` |
| `trans.php` | Fully migrated to `TranslationController` |
| `ggl.php` | Fully migrated to `TranslationController` |
| `glosbe_api.php` | Fully migrated to `TranslationController` |
| `wp_lwt_start.php` | Fully migrated to `WordPressController` |
| `wp_lwt_stop.php` | Fully migrated to `WordPressController` |
| `index.php` (old) | Fully migrated to `HomeController` |

### 8. Apache Configuration (`.htaccess`)

New `.htaccess` file provides:

- **URL rewriting:** Routes all non-file requests to `index.php`
- **Legacy redirects:** 301 redirects from old asset paths (`/icn/`, `/css/`, etc.)
- **Security:** Denies access to sensitive files (`connect.inc.php`, `composer.json`, `.env`)
- **Performance:** GZIP compression and cache headers for static assets
- **Static file handling:** Direct serving of CSS, JS, images, and fonts

### 9. Test Suite

Test files for the routing system:

- `tests/src/backend/Router/RouterTest.php` - Unit tests for the Router class
- `tests/src/backend/Router/RoutesTest.php` - Integration tests for all routes

Test coverage includes:

- Route registration
- HTTP method routing
- Pattern matching
- Legacy URL redirects
- Prefix route handling
- 404 handling

## Backward Compatibility

Version 3 maintains full backward compatibility:

1. **Legacy URLs:** All old URLs (e.g., `do_text.php?text=1`) automatically redirect to new routes with 301 status
2. **Asset paths:** Old asset paths (e.g., `/icn/`, `/css/`) redirect to new `assets/` locations
3. **Query strings:** All query parameters are preserved during redirects
4. **API compatibility:** REST API endpoints continue to work at both old and new URLs

## Migration Guide for Developers

### Updating Internal Links

If you have custom code or bookmarks:

| Old URL | New URL |
|---------|---------|
| `do_text.php?text=1` | `/text/read?text=1` |
| `edit_words.php` | `/words/edit` |
| `do_test.php` | `/test` |
| `edit_languages.php` | `/languages` |
| `backup_restore.php` | `/admin/backup` |
| `api.php/v1/...` | `/api/v1/...` |

### Updating Include Paths

If extending LWT with custom code:

```php
// Old way
include 'inc/session_utility.php';

// New way (from root or with include_path set)
include 'src/backend/Core/session_utility.php';
```

### Creating New Controllers

To add new functionality:

1. Create a controller in `src/backend/Controllers/`
2. Extend `BaseController`
3. Add routes in `src/backend/Router/routes.php`

```php
// src/backend/Controllers/MyController.php
namespace Lwt\Controllers;

class MyController extends BaseController
{
    public function myAction(array $params): void
    {
        // Include legacy file or implement new logic
        include __DIR__ . '/../Legacy/my_file.php';
    }
}

// In routes.php
$router->register('/my/route', 'MyController@myAction');
```

### Creating New Services

To extract business logic:

1. Create a service in `src/backend/Services/`
2. Inject dependencies via constructor
3. Use from controllers

```php
// src/backend/Services/MyService.php
namespace Lwt\Services;

class MyService
{
    public function doSomething(): array
    {
        // Business logic here
        return [];
    }
}

// In controller
$service = new MyService();
$data = $service->doSomething();
```

## Statistics

| Metric | Before | After |
|--------|--------|-------|
| PHP files in root | 60+ | 1 (`index.php`) |
| Root directories | 10+ | 6 (assets, db, docs, media, src, tests) |
| Controllers | 0 | 14 |
| Services | 0 | 22 |
| View directories | 0 | 9 |
| Legacy PHP files remaining | 60+ | 0 |
| Route definitions | 0 | 80+ |
| Test files for routing | 0 | 2 (1000+ lines) |

### 10. Global Variables Refactoring

Version 3 introduces the `Globals` class to clearly identify and manage global state throughout the application.

#### The Problem with Globals

Previously, LWT used PHP global variables scattered throughout the codebase:

```php
// Old way - globals are implicit and hard to track
function someFunction() {
    global $tbpref;
    global $DBCONNECTION;
    $sql = "SELECT * FROM " . $tbpref . "words";
    // ...
}
```

This pattern had several issues:

- Dependencies were hidden inside functions
- Hard to trace where globals were initialized
- Testing required manipulating `$GLOBALS` directly
- No type safety or IDE autocompletion

#### The New Globals Class

A new class `Lwt\Core\Globals` (`src/backend/Core/Globals.php`) provides explicit, type-safe access to global state:

```php
// New way - dependencies are explicit
use Lwt\Core\Globals;

function someFunction() {
    $prefix = Globals::getTablePrefix();
    $db = Globals::getDbConnection();
    $sql = "SELECT * FROM " . $prefix . "words";
    // ...
}
```

#### Available Methods

| Method | Description | Replaces |
|--------|-------------|----------|
| `Globals::getDbConnection()` | Get the mysqli database connection | `global $DBCONNECTION` |
| `Globals::getTablePrefix()` | Get the database table prefix | `global $tbpref` |
| `Globals::table($name)` | Get prefixed table name (e.g., `table('words')` → `lwt_words`) | `$tbpref . 'words'` |
| `Globals::isTablePrefixFixed()` | Check if prefix is fixed in connect.inc.php | `global $fixed_tbpref` |
| `Globals::getDatabaseName()` | Get the database name | `global $dbname` |
| `Globals::isDebug()` | Check if debug mode is enabled | `global $debug` |
| `Globals::getDebug()` | Get debug value as integer (0 or 1) | `global $debug` |
| `Globals::shouldDisplayErrors()` | Check if error display is enabled | `global $dsplerrors` |
| `Globals::shouldDisplayTime()` | Check if execution time display is enabled | `global $dspltime` |

#### Setter Methods (for initialization)

| Method | Description |
|--------|-------------|
| `Globals::setDbConnection($conn)` | Set the database connection |
| `Globals::setTablePrefix($prefix, $fixed)` | Set the table prefix |
| `Globals::setDatabaseName($name)` | Set the database name |
| `Globals::setDebug($value)` | Set debug mode |
| `Globals::setDisplayErrors($value)` | Set error display mode |
| `Globals::setDisplayTime($value)` | Set time display mode |
| `Globals::reset()` | Reset all globals (for testing) |

#### All Global Variables Removed

All global variables have been **fully removed** from the LWT codebase. The `Globals` class is now the only way to access this state:

| Removed Global | Replacement |
|----------------|-------------|
| `$tbpref` | `Globals::getTablePrefix()` or `Globals::table('tablename')` |
| `$fixed_tbpref` | `Globals::isTablePrefixFixed()` |
| `$DBCONNECTION` | `Globals::getDbConnection()` |
| `$debug` | `Globals::isDebug()` / `Globals::getDebug()` |
| `$dbname` | `Globals::getDatabaseName()` |
| `$dsplerrors` | `Globals::shouldDisplayErrors()` |
| `$dspltime` | `Globals::shouldDisplayTime()` |

No `global $variable` declarations remain in any source files. The backward compatibility layer that previously synchronized `Globals` with `$GLOBALS` has also been removed.

#### Migration Guide

To update your code:

1. Add the use statement at the top of your file:

   ```php
   use Lwt\Core\Globals;
   ```

2. Replace global declarations with method calls:

   ```php
   // Before
   function myFunction() {
       global $tbpref, $DBCONNECTION;
       $sql = "SELECT * FROM " . $tbpref . "words";
       $result = mysqli_query($DBCONNECTION, $sql);
   }

   // After
   function myFunction() {
       $sql = "SELECT * FROM " . Globals::table('words');
       $result = mysqli_query(Globals::getDbConnection(), $sql);
   }
   ```

3. For debug checks:

   ```php
   // Before
   global $debug;
   if ($debug) { ... }

   // After
   if (Globals::isDebug()) { ... }
   ```

### 11. Environment-Based Configuration (.env)

Version 3 introduces `.env` file support for database configuration, replacing the legacy `connect.inc.php` approach.

#### The Problem with connect.inc.php

Previously, LWT used PHP files for configuration:

```php
// connect.inc.php (or connect_xampp.inc.php, connect_mamp.inc.php, etc.)
$server = "localhost";
$userid = "root";
$passwd = "";
$dbname = "learning-with-texts";
```

This approach had issues:

- PHP files can execute code, creating security risks
- Different template files for each environment (XAMPP, MAMP, etc.)
- Not compatible with modern deployment workflows
- Harder to use with Docker and container orchestration

#### The New .env Approach

LWT now supports `.env` files, the modern standard for application configuration:

```bash
# .env file
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=learning-with-texts
```

#### Configuration File Priority

LWT loads configuration in this order:

1. `.env` file in the project root (if exists) - **recommended**
2. `connect.inc.php` (legacy, for backward compatibility)

#### Available Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | Database server hostname or IP | `localhost` |
| `DB_USER` | Database username | `root` |
| `DB_PASSWORD` | Database password | (empty) |
| `DB_NAME` | Database name | `learning-with-texts` |
| `DB_SOCKET` | Database socket (optional) | (empty) |
| `DB_TABLE_PREFIX` | Table prefix for multi-instance setups | (empty) |

#### Setting Up .env

1. Copy the template file:

   ```bash
   cp .env.example .env
   ```

2. Edit `.env` with your database credentials:

   ```bash
   DB_HOST=localhost
   DB_USER=your_username
   DB_PASSWORD=your_password
   DB_NAME=your_database
   ```

3. That's it! LWT will automatically use these values.

#### Environment-Specific Examples

**Standard localhost:**

```bash
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=learning-with-texts
```

**MAMP (macOS):**

```bash
DB_HOST=localhost:8889
DB_USER=root
DB_PASSWORD=root
DB_NAME=learning-with-texts
```

**Docker:**

```bash
DB_HOST=db
DB_USER=lwt
DB_PASSWORD=secret
DB_NAME=lwt
```

**With table prefix (multiple instances):**

```bash
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=shared_database
DB_TABLE_PREFIX=lwt_
```

#### EnvLoader Class

The `Lwt\Core\EnvLoader` class (`src/backend/Core/Bootstrap/EnvLoader.php`) provides the parsing functionality:

```php
use Lwt\Core\EnvLoader;

// Load .env file
EnvLoader::load('/path/to/.env');

// Get values with defaults
$host = EnvLoader::get('DB_HOST', 'localhost');

// Get as boolean
$debug = EnvLoader::getBool('DEBUG', false);

// Get as integer
$port = EnvLoader::getInt('DB_PORT', 3306);

// Get complete database config
$config = EnvLoader::getDatabaseConfig();
```

#### Deprecated Files

The following files are deprecated and will be removed in a future version:

| Deprecated File | Replacement |
|-----------------|-------------|
| `connect.inc.php` | `.env` |
| `connect_xampp.inc.php` | `.env` with XAMPP settings |
| `connect_mamp.inc.php` | `.env` with MAMP settings |
| `connect_easyphp.inc.php` | `.env` with EasyPHP settings |
| `connect_wordpress.inc.php` | `.env` with WordPress settings |

#### Migrating to .env

To migrate from `connect.inc.php` to `.env`:

1. Create `.env` from the template:

   ```bash
   cp .env.example .env
   ```

2. Copy your values from `connect.inc.php`:

   ```php
   // Old connect.inc.php
   $server = "localhost";
   $userid = "myuser";
   $passwd = "mypass";
   $dbname = "mydb";
   ```

   Becomes:

   ```bash
   # New .env
   DB_HOST=localhost
   DB_USER=myuser
   DB_PASSWORD=mypass
   DB_NAME=mydb
   ```

3. Test that LWT works with the new configuration

4. Optionally, remove `connect.inc.php` (LWT will use `.env` exclusively)

### 12. AJAX Files Consolidation

Version 3 consolidates all legacy AJAX files into a structured API with dedicated handlers.

#### The Problem with Scattered AJAX Files

Previously, LWT had 15 separate `ajax_*.php` files:

```text
ajax_add_term_transl.php     ajax_save_impr_text.php
ajax_chg_term_status.php     ajax_save_setting.php
ajax_check_regexp.php        ajax_save_text_position.php
ajax_edit_impr_text.php      ajax_show_imported_terms.php
ajax_get_phonetic.php        ajax_show_sentences.php
ajax_get_theme.php           ajax_show_similar_terms.php
ajax_load_feed.php           ajax_update_media_select.php
ajax_word_counts.php
```

This pattern had several issues:

- **15 separate entry points** - Hard to maintain, secure, and document
- **Inconsistent response formats** - Some returned HTML, some JSON, some JavaScript
- **No HTTP status codes** - Always returned 200 OK, even on errors
- **No input validation** - Mixed usage of `$_GET`, `$_POST`, `$_REQUEST`

#### The New API Structure

All AJAX functionality has been consolidated into `src/backend/Api/V1/`:

```text
src/backend/Api/V1/
├── ApiV1.php           # Main API router
├── Endpoints.php       # Endpoint definitions
└── Handlers/
    ├── FeedHandler.php
    ├── ImportHandler.php
    ├── ImprovedTextHandler.php
    ├── LanguageHandler.php
    ├── MediaHandler.php
    ├── ReviewHandler.php
    ├── SettingsHandler.php
    ├── StatisticsHandler.php
    ├── TermHandler.php
    ├── TextHandler.php
    └── Response.php
```

#### REST Endpoint Mapping

| Old File | New REST Endpoint | Handler |
|----------|-------------------|---------|
| `ajax_add_term_transl.php` | `/api.php/v1/terms/new` | TermHandler |
| `ajax_chg_term_status.php` | `/api.php/v1/terms/{id}/status/up` or `/down` | TermHandler |
| `ajax_get_phonetic.php` | `/api.php/v1/phonetic-reading` | LanguageHandler |
| `ajax_get_theme.php` | `/api.php/v1/settings/theme-path` | SettingsHandler |
| `ajax_load_feed.php` | `/api.php/v1/feeds/{id}/load` | FeedHandler |
| `ajax_save_impr_text.php` | `/api.php/v1/texts/{id}/annotation` | TextHandler |
| `ajax_save_setting.php` | `/api.php/v1/settings` | SettingsHandler |
| `ajax_save_text_position.php` | `/api.php/v1/texts/{id}/reading-position` | TextHandler |
| `ajax_show_imported_terms.php` | `/api.php/v1/terms/imported` | ImportHandler |
| `ajax_show_sentences.php` | `/api.php/v1/sentences-with-term` | TermHandler |
| `ajax_show_similar_terms.php` | `/api.php/v1/similar-terms` | TermHandler |
| `ajax_update_media_select.php` | `/api.php/v1/media-files` | MediaHandler |
| `ajax_word_counts.php` | `/api.php/v1/texts/statistics` | StatisticsHandler |

#### Benefits

| Aspect | Before | After |
|--------|--------|-------|
| Entry points | 15 separate files | 1 centralized API |
| Response format | HTML/JSON/JS (mixed) | JSON (consistent) |
| HTTP status codes | Always 200 | 200/400/404/405 |
| Error handling | Minimal/none | Structured JSON errors |
| Code organization | Flat files | Handler classes |
| Maintainability | Hard to track | Single namespace |

#### Migration for Custom Code

If you have custom code calling the old AJAX files:

```javascript
// Old way
$.post('Core/ajax_save_setting.php', { k: 'mykey', v: 'myvalue' });

// New way
$.post('api.php/v1/settings', { key: 'mykey', value: 'myvalue' });
```

```javascript
// Old way
$.post('inc/ajax_load_feed.php', {
    NfID: feedId, NfSourceURI: uri, NfName: name, NfOptions: opts
});

// New way
$.post('api.php/v1/feeds/' + feedId + '/load', {
    name: name, source_uri: uri, options: opts
});
```

## Code Monolith Splitting

Version 3 breaks up the large monolithic PHP files into smaller, focused modules for better maintainability.

### Core Directory Organization

The `src/backend/Core/` directory is organized into subdirectories by concern:

| Directory | Purpose |
|-----------|---------|
| `Bootstrap/` | Application initialization (EnvLoader, db_bootstrap, start_session) |
| `Database/` | Database classes (Connection, DB, Escaping, Settings) |
| `Entity/` | Entity classes (Language, Term, Text) |
| `Export/` | Export functionality (Anki, TSV) |
| `Feed/` | RSS feed handling |
| `Http/` | HTTP utilities |
| `Integration/` | External integrations |
| `Language/` | Language processing |
| `Media/` | Media file handling |
| `Mobile/` | Mobile interface logic |
| `Tag/` | Tag management |
| `Test/` | Test/review logic |
| `Text/` | Text processing |
| `UI/` | UI helper functions |
| `Utils/` | General utilities |
| `Word/` | Word/term processing |

### database_connect.php Split

The original `database_connect.php` was split into focused modules:

| New File | Purpose |
|----------|---------|
| `database_connect.php` | Core database connection and query wrappers |
| `tags.php` | Tag management functions |
| `feeds.php` | RSS feed handling functions |
| `settings.php` | Application settings management |

### session_utility.php Split

The original `session_utility.php` (4300+ lines) was split into multiple files:

| New File | Lines | Purpose |
|----------|-------|---------|
| `session_utility.php` | ~1,000 | Core session functions, navigation, media handling, string utilities |
| `export_helpers.php` | ~240 | Export functions (Anki, TSV, flexible format exports) |
| `text_helpers.php` | ~2,100 | Text/sentence processing, MeCab integration, annotations, expression handling |

UI helper functions previously in `ui_helpers.php` have been migrated to proper MVC View Helper classes:

- `PageLayoutHelper` - Page headers, footers, logos
- `StatusHelper` - Status indicators and conditions
- `SelectOptionsBuilder` - Select/dropdown option generation

All functions remain in the global namespace for backward compatibility. The `session_utility.php` file requires the helper files automatically:

```php
require_once __DIR__ . '/export_helpers.php';
require_once __DIR__ . '/text_helpers.php';
```

### Language Definitions as JSON

Version 3 converts the language definitions from a PHP array to a JSON file for better maintainability and separation of data from code.

#### Before (v2)

Language definitions were hardcoded in `langdefs.php` as a PHP array:

```php
define('LWT_LANGUAGES_ARRAY', array(
    "English" => array(
        "en", "en", false,
        "\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ",
        ".!?:;",
        false, false, false
    ),
    // ... 38 more languages
));
```

#### After (v3)

Language definitions are stored in `langdefs.json` with descriptive keys:

```json
{
    "English": {
        "glosbeIso": "en",
        "googleIso": "en",
        "biggerFont": false,
        "wordCharRegExp": "\\'a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ",
        "sentSplRegExp": ".!?:;",
        "makeCharacterWord": false,
        "removeSpaces": false,
        "rightToLeft": false
    }
}
```

The `langdefs.php` file now loads the JSON and converts it to the legacy indexed array format for backward compatibility:

```php
define('LWT_LANGUAGES_ARRAY', loadLanguageDefinitions());
```

#### Benefits of this change

| Aspect | Before | After |
|--------|--------|-------|
| Data format | PHP code | Pure JSON data |
| Field names | Numeric indices (0-7) | Descriptive keys |
| Editability | Requires PHP knowledge | Human-readable JSON |
| Reusability | PHP only | Any language can parse JSON |
| Validation | Runtime errors | JSON schema validation possible |

#### Field Mapping

| Index | JSON Key | Description |
|-------|----------|-------------|
| 0 | `glosbeIso` | ISO code for Glosbe dictionary |
| 1 | `googleIso` | ISO code for Google Translate |
| 2 | `biggerFont` | Whether to use larger font size |
| 3 | `wordCharRegExp` | Regex for valid word characters |
| 4 | `sentSplRegExp` | Regex for sentence splitting |
| 5 | `makeCharacterWord` | Treat each character as a word (CJK) |
| 6 | `removeSpaces` | Remove spaces between words (CJK) |
| 7 | `rightToLeft` | Right-to-left text direction |

### 13. Database Engine Migration (MyISAM → InnoDB)

Version 3 migrates all database tables from MyISAM to InnoDB engine.

#### Changes

- All 14 permanent tables converted from MyISAM to InnoDB
- Temporary tables (`temptextitems`, `tempwords`) remain MEMORY engine
- Migration file: `db/migrations/20251130_120000_myisam_to_innodb.sql`

#### Benefits of moving to InnoDB

| Feature | MyISAM | InnoDB |
|---------|--------|--------|
| Transactions | No | Yes (ACID) |
| Locking | Table-level | Row-level |
| Foreign keys | No | Yes |
| Crash recovery | Limited | Full |

#### Migration Notes

- Existing installations: Tables are converted automatically when running database updates
- New installations: Tables are created directly with InnoDB engine
- No code changes required - queries work identically

This change prepares LWT for future improvements including foreign key constraints and transaction support for multi-step operations.

## Future Improvements

This refactoring enables:

1. **Gradual migration:** Legacy files can be incrementally converted to proper MVC
2. **Better testing:** Controllers and Services are easier to unit test
3. **Cleaner URLs:** SEO-friendly URLs without `.php` extensions
4. **Modular architecture:** Clear separation of concerns
5. **Namespace support:** PHP autoloading with PSR-4 style namespaces
6. **Explicit dependencies:** The `Globals` class makes global state visible and trackable
7. **Modern configuration:** `.env` files work with Docker, CI/CD, and modern deployment workflows
8. **API versioning:** Structured API handlers allow for future API versions

## Commit History

The v3 branch includes the following key commits (in chronological order):

1. `125edc4e` - Initial refactor: moves PHP files to src folder with router for backward compatibility
2. `e53b8387` - Moves `inc/` to `src/php/inc`
3. `cfb4cb7c` - Adds router test suite, fixes non-existing route
4. `fbc64369` - Fixes routing globals passing, adds missing `empty.html` file
5. `4369a1c0` - Implements MVC structure, fixes static assets paths
6. `48e84eac` - Moves files and static assets to unclutter the root folder
7. `f2ab173a` - Clearly separates front-end files from PHP backend
8. `15a1b011` - Renames `src/php/` to `src/backend/`, moves inc to Core
9. Recent commits - Ongoing MVC migration (feeds, word edit, admin, home controllers)

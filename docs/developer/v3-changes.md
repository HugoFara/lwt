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

All PHP source files have been moved to `src/php/`:

| Old Location | New Location |
|-------------|--------------|
| `inc/` | `src/php/inc/` |
| Root PHP files (`do_text.php`, etc.) | `src/php/Legacy/` |
| (new) | `src/php/Controllers/` |
| (new) | `src/php/Router/` |

#### Frontend Source Reorganization

Frontend source files moved from `src/` to `src/frontend/`:

| Old Location | New Location |
|-------------|--------------|
| `src/js/` | `src/frontend/js/` |
| `src/css/` | `src/frontend/css/base/` |
| `src/themes/` | `src/frontend/css/themes/` |

### 3. Routing System

A new routing system has been implemented in `src/php/Router/`:

#### Router (`src/php/Router/Router.php`)

The `Router` class provides:

- **Route registration:** Map URL paths to handlers
- **Pattern matching:** Support for dynamic routes (e.g., `/text/{id}`)
- **Prefix routes:** Handle API endpoints with multiple sub-paths
- **Legacy URL support:** Automatic redirects from old `.php` URLs
- **HTTP method routing:** Different handlers for GET, POST, etc.
- **404/500 error handling:** Built-in error pages

#### Routes Configuration (`src/php/Router/routes.php`)

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

New controller classes in `src/php/Controllers/`:

| Controller | Purpose |
|-----------|---------|
| `BaseController.php` | Abstract base class with common functionality |
| `HomeController.php` | Home page |
| `TextController.php` | Text reading and management |
| `WordController.php` | Word/term management |
| `TestController.php` | Testing/review interface |
| `LanguageController.php` | Language configuration |
| `TagsController.php` | Tag management |
| `FeedsController.php` | RSS feed management |
| `AdminController.php` | Admin functions (backup, settings, etc.) |
| `MobileController.php` | Mobile interface |
| `ApiController.php` | REST API endpoints |
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

### 5. Legacy File Migration

All 59 root-level PHP page files have been moved to `src/php/Legacy/` with renamed, more descriptive filenames:

| Old Filename | New Filename |
|-------------|-------------|
| `do_text.php` | `text_read.php` |
| `do_text_header.php` | `text_read_header.php` |
| `do_text_text.php` | `text_read_text.php` |
| `edit_texts.php` | `text_edit.php` |
| `display_impr_text.php` | `text_display.php` |
| `print_impr_text.php` | `text_print.php` |
| `print_text.php` | `text_print_plain.php` |
| `check_text.php` | `text_check.php` |
| `edit_archivedtexts.php` | `text_archived.php` |
| `long_text_import.php` | `text_import_long.php` |
| `set_text_mode.php` | `text_set_mode.php` |
| `do_test.php` | `test_index.php` |
| `do_test_header.php` | `test_header.php` |
| `do_test_table.php` | `test_table.php` |
| `do_test_test.php` | `test_test.php` |
| `set_test_status.php` | `test_set_status.php` |
| `edit_word.php` | `word_edit.php` |
| `edit_words.php` | `words_edit.php` |
| `edit_mword.php` | `word_edit_multi.php` |
| `edit_tword.php` | `word_edit_term.php` |
| `delete_word.php` | `word_delete.php` |
| `delete_mword.php` | `word_delete_multi.php` |
| `new_word.php` | `word_new.php` |
| `show_word.php` | `word_show.php` |
| `upload_words.php` | `word_upload.php` |
| `all_words_wellknown.php` | `words_all.php` |
| `bulk_translate_words.php` | Fully migrated to `WordController@bulkTranslate` |
| `inline_edit.php` | `word_inline_edit.php` |
| `insert_word_wellknown.php` | `word_insert_wellknown.php` |
| `insert_word_ignore.php` | `word_insert_ignore.php` |
| `set_word_status.php` | `word_set_status.php` |
| `edit_languages.php` | `language_edit.php` |
| `select_lang_pair.php` | `language_select_pair.php` |
| `edit_tags.php` | `tags_edit.php` |
| `edit_texttags.php` | `tags_text_edit.php` |
| `do_feeds.php` | `feeds_index.php` |
| `edit_feeds.php` | `feeds_edit.php` |
| `feed_wizard.php` | `feeds_wizard.php` |
| `backup_restore.php` | `admin_backup.php` |
| `database_wizard.php` | `admin_wizard.php` |
| `statistics.php` | `admin_statistics.php` |
| `install_demo.php` | `admin_install_demo.php` |
| `settings.php` | `admin_settings.php` |
| `set_word_on_hover.php` | `settings_hover.php` |
| `text_to_speech_settings.php` | `admin_tts_settings.php` |
| `table_set_management.php` | `admin_table_management.php` |
| `server_data.php` | `admin_server_data.php` |
| `mobile.php` | `mobile_index.php` |
| `start.php` | `mobile_start.php` |
| `api.php` | `api_v1.php` |
| `trans.php` | `api_translate.php` |
| `ggl.php` | `api_google.php` |
| `glosbe_api.php` | `api_glosbe.php` |
| `wp_lwt_start.php` | `wordpress_start.php` |
| `wp_lwt_stop.php` | `wordpress_stop.php` |
| `index.php` (old) | `home.php` |

### 6. Apache Configuration (`.htaccess`)

New `.htaccess` file provides:

- **URL rewriting:** Routes all non-file requests to `index.php`
- **Legacy redirects:** 301 redirects from old asset paths (`/icn/`, `/css/`, etc.)
- **Security:** Denies access to sensitive files (`connect.inc.php`, `composer.json`, `.env`)
- **Performance:** GZIP compression and cache headers for static assets
- **Static file handling:** Direct serving of CSS, JS, images, and fonts

### 7. Test Suite

New test files added for the routing system:

- `tests/src/php/Router/RouterTest.php` - Unit tests for the Router class
- `tests/src/php/Router/RoutesTest.php` - Integration tests for all routes

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
include 'src/php/inc/session_utility.php';
```

### Creating New Controllers

To add new functionality:

1. Create a controller in `src/php/Controllers/`
2. Extend `BaseController`
3. Add routes in `src/php/Router/routes.php`

```php
// src/php/Controllers/MyController.php
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

## Statistics

| Metric | Before | After |
|--------|--------|-------|
| PHP files in root | 60+ | 1 (`index.php`) |
| Root directories | 10+ | 6 (assets, db, docs, media, src, tests) |
| Controllers | 0 | 12 |
| Route definitions | 0 | 80+ |
| Test files for routing | 0 | 2 (1000+ lines) |

### 8. Global Variables Refactoring

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

### 9. Environment-Based Configuration (.env)

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

A new `Lwt\Core\EnvLoader` class (`src/backend/Core/EnvLoader.php`) provides the parsing functionality:

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

### 10. AJAX Files Consolidation

Version 3 consolidates all legacy AJAX files into the REST API (`api_v1.php`), eliminating 15 separate entry points.

#### The Problem with Scattered AJAX Files

Previously, LWT had 15 separate `ajax_*.php` files in `src/backend/Core/`:

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
- **Fragile `chdir('..')` calls** - Many files changed directory assuming they were in `/Core/`

#### The New Consolidated API

All AJAX functionality has been moved into `src/backend/Legacy/api_v1.php`:

| Deleted File | New REST Endpoint | HTTP Method |
|-------------|-------------------|-------------|
| `ajax_add_term_transl.php` | `/api.php/v1/terms/new` | POST |
| `ajax_chg_term_status.php` | `/api.php/v1/terms/{id}/status/up` or `/down` | POST |
| `ajax_check_regexp.php` | (removed - no active usage) | - |
| `ajax_edit_impr_text.php` | Functions in `Lwt\Ajax\Improved_Text` namespace | - |
| `ajax_get_phonetic.php` | `/api.php/v1/phonetic-reading` | GET |
| `ajax_get_theme.php` | `/api.php/v1/settings/theme-path` | GET |
| `ajax_load_feed.php` | `/api.php/v1/feeds/{id}/load` | POST |
| `ajax_save_impr_text.php` | `/api.php/v1/texts/{id}/annotation` | POST |
| `ajax_save_setting.php` | `/api.php/v1/settings` | POST |
| `ajax_save_text_position.php` | `/api.php/v1/texts/{id}/reading-position` | POST |
| `ajax_show_imported_terms.php` | `/api.php/v1/terms/imported` | GET |
| `ajax_show_sentences.php` | `/api.php/v1/sentences-with-term` | GET |
| `ajax_show_similar_terms.php` | `/api.php/v1/similar-terms` | GET |
| `ajax_update_media_select.php` | `/api.php/v1/media-files` | GET |
| `ajax_word_counts.php` | `/api.php/v1/texts/statistics` | GET |

#### Namespace Organization

Functions are organized into namespaces within `api_v1.php`:

- `Lwt\Ajax` - Main namespace with term, status, position, and settings functions
- `Lwt\Ajax\Improved_Text` - Functions for annotated text editing (`make_trans`, `edit_term_form`, etc.)
- `Lwt\Ajax\Feed` - Feed loading functions (`load_feed`, `get_feeds_list`, etc.)

#### Benefits

| Aspect | Before | After |
|--------|--------|-------|
| Entry points | 15 separate files | 1 centralized API |
| Response format | HTML/JSON/JS (mixed) | JSON (consistent) |
| HTTP status codes | Always 200 | 200/400/404/405 |
| Error handling | Minimal/none | Structured JSON errors |
| Maintainability | Hard to track | Single file to maintain |

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

### database_connect.php Split

The original `database_connect.php` was split into focused modules:

| New File | Purpose |
|----------|---------|
| `database_connect.php` | Core database connection and query wrappers |
| `tags.php` | Tag management functions |
| `feeds.php` | RSS feed handling functions |
| `settings.php` | Application settings management |

### session_utility.php Split

The original `session_utility.php` (4300+ lines) was split into 4 files:

| New File | Lines | Purpose |
|----------|-------|---------|
| `session_utility.php` | ~1,000 | Core session functions, navigation, media handling, string utilities |
| `ui_helpers.php` | ~960 | HTML/UI generation (logos, selects, pagers, page headers, status indicators) |
| `export_helpers.php` | ~240 | Export functions (Anki, TSV, flexible format exports) |
| `text_helpers.php` | ~2,100 | Text/sentence processing, MeCab integration, annotations, expression handling |

All functions remain in the global namespace for backward compatibility. The `session_utility.php` file requires all helper files automatically:

```php
require_once __DIR__ . '/ui_helpers.php';
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

## Future Improvements

This refactoring enables:

1. **Gradual migration:** Legacy files can be incrementally converted to proper MVC
2. **Better testing:** Controllers are easier to unit test
3. **Cleaner URLs:** SEO-friendly URLs without `.php` extensions
4. **Modular architecture:** Clear separation of concerns
5. **Namespace support:** PHP autoloading with PSR-4 style namespaces
6. **Explicit dependencies:** The `Globals` class makes global state visible and trackable
7. **Modern configuration:** `.env` files work with Docker, CI/CD, and modern deployment workflows

## Commit History

The v3 branch includes the following commits (in chronological order):

1. `125edc4e` - Initial refactor: moves PHP files to src folder with router for backward compatibility
2. `e53b8387` - Moves `inc/` to `src/php/inc`
3. `cfb4cb7c` - Adds router test suite, fixes non-existing route
4. `fbc64369` - Fixes routing globals passing, adds missing `empty.html` file
5. `4369a1c0` - Implements MVC structure, fixes static assets paths
6. `48e84eac` - Moves files and static assets to unclutter the root folder
7. `f2ab173a` - Clearly separates front-end files from PHP backend

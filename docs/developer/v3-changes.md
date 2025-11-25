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
| `src/css/` | `src/frontend/css/` |
| `src/themes/` | `src/frontend/themes/` |

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
| `bulk_translate_words.php` | `word_bulk_translate.php` |
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

Version 3 introduces the `LWT_Globals` class to clearly identify and manage global state throughout the application.

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

#### The New LWT_Globals Class

A new class `Lwt\Core\LWT_Globals` (`src/backend/Core/LWT_Globals.php`) provides explicit, type-safe access to global state:

```php
// New way - dependencies are explicit
use Lwt\Core\LWT_Globals;

function someFunction() {
    $prefix = LWT_Globals::getTablePrefix();
    $db = LWT_Globals::getDbConnection();
    $sql = "SELECT * FROM " . $prefix . "words";
    // ...
}
```

#### Available Methods

| Method | Description | Replaces |
|--------|-------------|----------|
| `LWT_Globals::getDbConnection()` | Get the mysqli database connection | `global $DBCONNECTION` |
| `LWT_Globals::getTablePrefix()` | Get the database table prefix | `global $tbpref` |
| `LWT_Globals::table($name)` | Get prefixed table name (e.g., `table('words')` → `lwt_words`) | `$tbpref . 'words'` |
| `LWT_Globals::isTablePrefixFixed()` | Check if prefix is fixed in connect.inc.php | `global $fixed_tbpref` |
| `LWT_Globals::getDatabaseName()` | Get the database name | `global $dbname` |
| `LWT_Globals::isDebug()` | Check if debug mode is enabled | `global $debug` |
| `LWT_Globals::getDebug()` | Get debug value as integer (0 or 1) | `global $debug` |
| `LWT_Globals::shouldDisplayErrors()` | Check if error display is enabled | `global $dsplerrors` |
| `LWT_Globals::shouldDisplayTime()` | Check if execution time display is enabled | `global $dspltime` |

#### Setter Methods (for initialization)

| Method | Description |
|--------|-------------|
| `LWT_Globals::setDbConnection($conn)` | Set the database connection |
| `LWT_Globals::setTablePrefix($prefix, $fixed)` | Set the table prefix |
| `LWT_Globals::setDatabaseName($name)` | Set the database name |
| `LWT_Globals::setDebug($value)` | Set debug mode |
| `LWT_Globals::setDisplayErrors($value)` | Set error display mode |
| `LWT_Globals::setDisplayTime($value)` | Set time display mode |
| `LWT_Globals::reset()` | Reset all globals (for testing) |

#### Backward Compatibility Maintained

The old global variables still work and are still populated:

```php
// Both of these work:
global $tbpref;
$sql = "SELECT * FROM " . $tbpref . "words";

// New recommended way:
use Lwt\Core\LWT_Globals;
$sql = "SELECT * FROM " . LWT_Globals::table('words');
```

All existing code using `global $tbpref`, `global $DBCONNECTION`, etc. continues to function. However, these are now marked as deprecated and will display deprecation notices in future versions.

#### Deprecated Global Variables

The following global variables are deprecated in favor of `LWT_Globals` methods:

| Deprecated Global | Replacement |
|-------------------|-------------|
| `$DBCONNECTION` | `LWT_Globals::getDbConnection()` |
| `$tbpref` | `LWT_Globals::getTablePrefix()` |
| `$fixed_tbpref` | `LWT_Globals::isTablePrefixFixed()` |
| `$dbname` | `LWT_Globals::getDatabaseName()` |
| `$debug` | `LWT_Globals::isDebug()` / `LWT_Globals::getDebug()` |
| `$dsplerrors` | `LWT_Globals::shouldDisplayErrors()` |
| `$dspltime` | `LWT_Globals::shouldDisplayTime()` |

#### Migration Guide

To update your code:

1. Add the use statement at the top of your file:

   ```php
   use Lwt\Core\LWT_Globals;
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
       $sql = "SELECT * FROM " . LWT_Globals::table('words');
       $result = mysqli_query(LWT_Globals::getDbConnection(), $sql);
   }
   ```

3. For debug checks:

   ```php
   // Before
   global $debug;
   if ($debug) { ... }

   // After
   if (LWT_Globals::isDebug()) { ... }
   ```

## Future Improvements

This refactoring enables:

1. **Gradual migration:** Legacy files can be incrementally converted to proper MVC
2. **Better testing:** Controllers are easier to unit test
3. **Cleaner URLs:** SEO-friendly URLs without `.php` extensions
4. **Modular architecture:** Clear separation of concerns
5. **Namespace support:** PHP autoloading with PSR-4 style namespaces
6. **Explicit dependencies:** The `LWT_Globals` class makes global state visible and trackable

## Commit History

The v3 branch includes the following commits (in chronological order):

1. `125edc4e` - Initial refactor: moves PHP files to src folder with router for backward compatibility
2. `e53b8387` - Moves `inc/` to `src/php/inc`
3. `cfb4cb7c` - Adds router test suite, fixes non-existing route
4. `fbc64369` - Fixes routing globals passing, adds missing `empty.html` file
5. `4369a1c0` - Implements MVC structure, fixes static assets paths
6. `48e84eac` - Moves files and static assets to unclutter the root folder
7. `f2ab173a` - Clearly separates front-end files from PHP backend

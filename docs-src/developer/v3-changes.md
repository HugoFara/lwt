# LWT v3 Changes

This document describes all the architectural and structural changes introduced in version 3.0.0 of Learning with Texts (LWT).

## Overview

Version 3 represents a major architectural refactoring of LWT, transitioning from a collection of 60+ standalone PHP files in the root directory to a modern, modular architecture. Key improvements include:

- **Front Controller Pattern:** All requests route through `index.php`
- **MVC Structure:** Controllers, Services, and Views organized by feature
- **Modular Architecture:** 10 feature modules following Clean Architecture principles
- **Shared Infrastructure:** Common database, HTTP, and UI utilities
- **Dependency Injection:** PSR-11 compatible container with service providers
- **Multi-User Support:** Optional data isolation for shared installations

This change improves code organization, testability, and sets the foundation for future improvements.

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

All PHP source files have been reorganized into a modular structure:

| Old Location | New Location |
|-------------|--------------|
| `inc/` | `src/backend/Core/` |
| Root PHP files (`do_text.php`, etc.) | Migrated to Controllers/Services |
| (new) | `src/backend/Controllers/` |
| (new) | `src/backend/Router/` |
| (new) | `src/backend/Services/` |
| (new) | `src/backend/Views/` |
| (new) | `src/backend/Api/` |
| (new) | `src/Shared/` (cross-cutting infrastructure) |
| (new) | `src/Modules/` (10 feature modules) |

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
- **Review:** `/review`, `/review/set-status`
- **Language:** `/languages`, `/languages/select-pair`
- **Tags:** `/tags`, `/tags/text`
- **Feeds:** `/feeds`, `/feeds/edit`, `/feeds/wizard`
- **Admin:** `/admin/backup`, `/admin/wizard`, `/admin/statistics`, `/admin/settings`, etc.
- **Mobile:** `/mobile`, `/mobile/start`
- **API:** `/api/v1`, `/api/translate`, `/api/google`, `/api/glosbe`
- **WordPress:** `/wordpress/start`, `/wordpress/stop`

### 4. MVC Controllers

Controller classes are organized in two locations:

**Backend Controllers** (`src/backend/Controllers/`):

| Controller | Purpose |
|-----------|---------|
| `BaseController.php` | Abstract base class with common functionality |
| `HomeController.php` | Home page |
| `TextController.php` | Text reading and management |
| `TextPrintController.php` | Text printing (plain and annotated) |
| `WordController.php` | Word/term management |
| `ReviewController.php` | Spaced repetition review interface |
| `LanguageController.php` | Language configuration |
| `TagsController.php` | Tag management |
| `FeedsController.php` | RSS feed management |
| `AdminController.php` | Admin functions (backup, settings, etc.) |
| `MobileController.php` | Mobile interface |
| `ApiController.php` | REST API endpoints |
| `TranslationController.php` | Translation API integration |
| `WordPressController.php` | WordPress integration |

**Module Controllers** (`src/Modules/*/Http/`):

| Module | Controller | Purpose |
|--------|------------|---------|
| Text | `TextController` | Text CRUD and reading |
| Text | `TextPrintController` | Print and export |
| Vocabulary | `VocabularyController` | Term management |
| Language | `LanguageController` | Language settings |
| Tags | `TagsController` | Tag CRUD |
| Review | `ReviewController` | Spaced repetition |
| Feed | `FeedController` | RSS feeds |
| Admin | `AdminController` | Settings, backup |
| User | `UserController` | Authentication |
| Dictionary | `DictionaryController` | Dictionary lookup |

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

Version 3 introduces a Services layer that extracts business logic from controllers.

**Backend Services** (`src/backend/Services/`):

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
| `ReviewService.php` | Review session logic |
| `TextDisplayService.php` | Annotated text display |
| `TextPrintService.php` | Text printing and export |
| `TextService.php` | Text CRUD and processing |
| `TranslationService.php` | Translation API integration |
| `TtsService.php` | Text-to-speech configuration |
| `WordPressService.php` | WordPress integration logic |
| `WordService.php` | Word/term management operations |
| `WordListService.php` | Word list filtering, pagination, and bulk operations |
| `WordUploadService.php` | Word import/upload operations |

**Module Services** (`src/Modules/*/Application/Services/`):

| Module | Key Services |
|--------|-------------|
| Text | TextReadingService, TextDisplayService, SentenceService, AnnotationService |
| Vocabulary | WordService, WordListService, TermStatusService, ExportService |
| Language | TextParsingService |
| Tags | TagService, TagAssociationService |
| Review | ReviewService, ReviewGenerationService, ScoreCalculationService |
| Feed | FeedService, ArticleService, FeedParsingService |
| User | AuthService, PasswordService, EmailService |
| Admin | MediaService, TtsService, SessionCleaner |
| Dictionary | LocalDictionaryService, TranslationService |

Services follow the pattern of extracting complex business logic for better testability. Module services are organized alongside Use Cases in the Application layer.

### 6. Views Architecture

Version 3 introduces a proper Views directory structure.

**Backend Views** (`src/backend/Views/`):

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

**Module Views** (`src/Modules/*/Views/`):

Each module contains its own Views directory with templates specific to that feature:

| Module | Template Count | Key Templates |
|--------|---------------|---------------|
| Text | 18 | read_text.php, edit_form.php, import_long_form.php |
| Vocabulary | 22 | form_edit_existing.php, list_table.php, upload_form.php |
| Language | 5 | index.php, edit_form.php, new_form.php |
| Tags | 5 | index.php, create_form.php, edit_form.php |
| Review | 6 | review_form.php, review_result.php, next_word.php |
| Feed | 8 | index.php, articles.php, import_article.php |
| User | 6 | login.php, register.php, profile.php |
| Admin | 8 | settings.php, backup.php, statistics.php |
| Home | 2 | index.php, dashboard.php |
| Dictionary | 2 | index.php, import.php |

**View Helper Classes** (`src/Shared/UI/Helpers/`):

| Class | Purpose |
|-------|---------|
| `FormHelper` | Form attribute generation (checked, selected) |
| `PageLayoutHelper` | Page layout elements (navbar, footers) |
| `IconHelper` | Lucide SVG icon rendering (replaced 97 legacy Fugue PNG icons) |
| `TagHelper` | HTML tag utilities |
| `SelectOptionsBuilder` | HTML select option building |

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
| `do_test.php` | `/review` |
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

### Creating New Modules (Recommended)

For new features, prefer creating a new module in `src/Modules/`:

1. Create the module directory structure:

```text
src/Modules/MyFeature/
├── Application/
│   ├── MyFeatureFacade.php
│   ├── Services/
│   └── UseCases/
├── Domain/
│   ├── Entities/
│   └── MyFeatureRepositoryInterface.php
├── Http/
│   └── MyFeatureController.php
├── Infrastructure/
│   └── MySqlMyFeatureRepository.php
├── Views/
└── MyFeatureServiceProvider.php
```

2. Create the ServiceProvider:

```php
// src/Modules/MyFeature/MyFeatureServiceProvider.php
namespace Lwt\Modules\MyFeature;

use Lwt\Shared\Infrastructure\Container\ServiceProviderInterface;

class MyFeatureServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(
            MyFeatureRepositoryInterface::class,
            MySqlMyFeatureRepository::class
        );
        $container->singleton(MyFeatureFacade::class);
    }

    public function boot(ContainerInterface $container): void {}
}
```

3. Register the provider in `index.php` or the container bootstrap.

4. Add routes in `src/backend/Router/routes.php`.

### Using the DI Container

Register services via the container instead of manual instantiation:

```php
use Lwt\Shared\Infrastructure\Container\Container;

// Register a service
$container = Container::getInstance();
$container->singleton(MyService::class);

// Retrieve a service (auto-wires dependencies)
$service = $container->get(MyService::class);
```

### Database Queries with Prepared Statements

Always use prepared statements for user input:

```php
use Lwt\Shared\Infrastructure\Database\Connection;

// Preferred: Prepared statements
$results = Connection::preparedFetchAll(
    "SELECT * FROM words WHERE WoLgID = ? AND WoStatus = ?",
    [$langId, $status]
);

// For complex queries: QueryBuilder
use Lwt\Shared\Infrastructure\Database\QueryBuilder;

$query = new QueryBuilder('words');
$words = $query
    ->where('WoLgID', '=', $langId)
    ->where('WoStatus', '>=', 1)
    ->orderBy('WoText')
    ->limit(100)
    ->get();
```

## Statistics

| Metric | Before | After |
|--------|--------|-------|
| PHP files in root | 60+ | 1 (`index.php`) |
| Root directories | 10+ | 6 (assets, db, docs, media, src, tests) |
| Controllers | 0 | 20+ |
| Services | 0 | 40+ |
| View directories | 0 | 9 |
| Legacy PHP files remaining | 60+ | 0 |
| Route definitions | 0 | 80+ |
| Test files for routing | 0 | 2 (1000+ lines) |
| Feature modules | 0 | 11 |
| Use case classes | 0 | 76 |
| API handlers | 0 | 20+ |

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

#### Module API Handlers

In addition to the centralized API handlers, each module provides its own API handler in `src/Modules/*/Http/`:

| Module | Handler | Key Endpoints |
|--------|---------|---------------|
| Text | `TextApiHandler` | `/api/v1/texts`, `/api/v1/texts/{id}` |
| Vocabulary | `VocabularyApiHandler` | `/api/v1/terms`, `/api/v1/terms/{id}/status` |
| Language | `LanguageApiHandler` | `/api/v1/languages`, `/api/v1/language-definitions` |
| Tags | `TagsApiHandler` | `/api/v1/tags`, `/api/v1/text-tags` |
| Review | `ReviewApiHandler` | `/api/v1/review/start`, `/api/v1/review/submit` |
| Feed | `FeedApiHandler` | `/api/v1/feeds`, `/api/v1/feeds/{id}/articles` |
| Admin | `AdminApiHandler` | `/api/v1/settings`, `/api/v1/backup` |
| User | `UserApiHandler` | `/api/v1/auth/login`, `/api/v1/auth/logout` |
| Dictionary | `DictionaryApiHandler` | `/api/v1/dictionary/lookup` |

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
| `Review/` | Review/spaced repetition logic |
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

### 14. Shared Infrastructure Layer

Version 3 introduces a `src/Shared/` directory containing cross-cutting infrastructure code that is used across all feature modules.

#### Directory Structure

```text
src/Shared/
├── Domain/
│   └── ValueObjects/
│       └── UserId.php              # User identity value object
├── Infrastructure/
│   ├── Container/                  # Dependency Injection
│   │   ├── Container.php           # PSR-11 compatible DI container
│   │   ├── ContainerInterface.php  # Container interface
│   │   ├── ServiceProviderInterface.php
│   │   ├── CoreServiceProvider.php
│   │   ├── ControllerServiceProvider.php
│   │   ├── RepositoryServiceProvider.php
│   │   ├── ContainerException.php
│   │   └── NotFoundException.php
│   ├── Database/                   # Database utilities
│   │   ├── Connection.php          # mysqli wrapper (singleton)
│   │   ├── PreparedStatement.php   # Safe parameterized queries
│   │   ├── QueryBuilder.php        # Fluent SQL builder
│   │   ├── DB.php                  # Static database helpers
│   │   ├── UserScopedQuery.php     # Auto user-filtering
│   │   ├── Settings.php            # Key-value settings
│   │   ├── Configuration.php       # Database config
│   │   ├── Escaping.php            # SQL escaping
│   │   ├── Validation.php          # Value validation
│   │   ├── Maintenance.php         # DB maintenance
│   │   ├── Migrations.php          # Migration tracking
│   │   ├── SqlFileParser.php       # SQL file parsing
│   │   ├── Restore.php             # Database restore
│   │   └── TextParsing.php         # Text parsing utilities
│   └── Http/                       # HTTP utilities
│       ├── InputValidator.php      # Type-safe request validation
│       ├── SecurityHeaders.php     # Security header management
│       └── UrlUtilities.php        # URL generation
└── UI/
    ├── Assets/
    │   └── ViteHelper.php          # Vite asset loading
    └── Helpers/
        ├── FormHelper.php          # Form attribute generation
        ├── PageLayoutHelper.php    # Page layout elements
        ├── IconHelper.php          # Lucide SVG icons
        ├── TagHelper.php           # HTML tag utilities
        └── SelectOptionsBuilder.php # Select option building
```

#### Key Components

**Database Classes:**

| Class | Purpose |
|-------|---------|
| `Connection` | Singleton mysqli wrapper with query execution |
| `PreparedStatement` | Safe parameterized query execution |
| `QueryBuilder` | Fluent SQL builder with prepared statement support |
| `DB` | Static helper methods for common database operations |
| `UserScopedQuery` | Automatic user filtering for multi-user mode |

**HTTP Utilities:**

| Class | Purpose |
|-------|---------|
| `InputValidator` | Type-safe request parameter access (`getString()`, `getInt()`, `getBool()`) |
| `SecurityHeaders` | Security header management |
| `UrlUtilities` | URL generation and manipulation |

**UI Helpers:**

| Class | Purpose |
|-------|---------|
| `FormHelper` | HTML form attributes (checked, selected) |
| `PageLayoutHelper` | Navbar, footers, menus |
| `IconHelper` | Lucide SVG icon rendering (replaces legacy PNG icons) |
| `TagHelper` | HTML tag generation |
| `SelectOptionsBuilder` | HTML select option building |
| `ViteHelper` | Vite CSS/JS bundle loading |

#### Usage Examples

```php
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Http\InputValidator;

// Database queries with prepared statements
$words = Connection::preparedFetchAll(
    "SELECT * FROM words WHERE WoLgID = ?",
    [$languageId]
);

// Fluent query builder
$texts = (new QueryBuilder('texts'))
    ->where('TxLgID', '=', $langId)
    ->orderBy('TxTitle')
    ->get();

// Type-safe input validation
$validator = new InputValidator($_REQUEST);
$textId = $validator->getInt('text', 0);
$title = $validator->getString('title', '');
```

### 15. Modular Architecture (src/Modules)

Version 3 introduces a modular architecture with 11 feature modules, each following Clean Architecture principles.

#### Module List

| Module | Purpose |
|--------|---------|
| `Admin` | Administration, settings, backup, statistics |
| `Book` | EPUB import, book management, chapter navigation |
| `Dictionary` | Dictionary integrations and local dictionaries |
| `Feed` | RSS feed management |
| `Home` | Dashboard and landing page |
| `Language` | Language configuration |
| `Review` | Spaced repetition testing |
| `Tags` | Tagging system |
| `Text` | Text reading and management |
| `User` | Authentication and user management |
| `Vocabulary` | Terms/words management |

#### Module Structure

Each module follows a consistent structure:

```text
src/Modules/[Module]/
├── Application/              # Use cases and application services
│   ├── Facade.php           # Simplified module API
│   ├── Services/            # Application services
│   └── UseCases/            # Command/query handlers
├── Domain/                  # Business logic
│   ├── Entities/            # Domain entities
│   ├── ValueObjects/        # Value objects (IDs, status)
│   └── RepositoryInterface.php
├── Http/                    # Request handling
│   ├── Controller.php       # MVC controller
│   └── ApiHandler.php       # REST API handler
├── Infrastructure/          # External integrations
│   └── MySqlRepository.php  # Repository implementation
├── Views/                   # PHP templates
└── [Module]ServiceProvider.php  # DI registration
```

#### Module Statistics

| Module | Use Cases | Services | Views |
|--------|-----------|----------|-------|
| Text | 9 | 7 | 18 |
| Vocabulary | 7 | 7 | 22 |
| Book | 2 | 2 | 4 |
| Language | 5 | 1 | 5 |
| Tags | 6 | 2 | 5 |
| Review | 5 | 3 | 6 |
| Feed | 7 | 4 | 8 |
| User | 5 | 6 | 6 |
| Admin | 7+ | 3 | 8 |
| Home | 3 | 0 | 2 |
| Dictionary | 3 | 2 | 2 |

#### Use Case Pattern

Each module uses the Use Case pattern for business operations:

```php
// src/Modules/Text/Application/UseCases/ImportText.php
namespace Lwt\Modules\Text\Application\UseCases;

class ImportText
{
    public function __construct(
        private TextRepositoryInterface $repository,
        private TextParsingService $parser
    ) {}

    public function execute(ImportTextRequest $request): Text
    {
        // Business logic here
        $text = new Text($request->title, $request->content);
        $this->parser->parse($text);
        return $this->repository->save($text);
    }
}
```

#### Module Facades

Each module provides a Facade for simplified access:

```php
use Lwt\Modules\Text\Application\TextFacade;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;

// Get text for reading
$text = $textFacade->getTextForReading($textId);

// Update term status
$vocabFacade->updateTermStatus($termId, 3);
```

### 16. Dependency Injection Container

Version 3 introduces a PSR-11 compatible dependency injection container.

#### Container Features

| Feature | Description |
|---------|-------------|
| Singleton support | Services registered as singletons are reused |
| Factory pattern | Fresh instances created on each request |
| Auto-wiring | Dependencies resolved via reflection |
| Service aliases | Alternative names for services |
| Circular detection | Prevents circular dependency loops |
| Typed retrieval | `getTyped()` for static analysis |

#### Service Providers

Each module registers its services via a ServiceProvider:

```php
// src/Modules/Text/TextServiceProvider.php
namespace Lwt\Modules\Text;

use Lwt\Shared\Infrastructure\Container\ServiceProviderInterface;

class TextServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        // Register repository interface to implementation
        $container->singleton(
            TextRepositoryInterface::class,
            MySqlTextRepository::class
        );

        // Register use cases
        $container->singleton(ImportText::class);
        $container->singleton(GetTextForReading::class);

        // Register facade
        $container->singleton(TextFacade::class);
    }

    public function boot(ContainerInterface $container): void
    {
        // Post-registration initialization
    }
}
```

#### Core Service Providers

| Provider | Purpose |
|----------|---------|
| `CoreServiceProvider` | Core services (Globals, database) |
| `ControllerServiceProvider` | All MVC controllers |
| `RepositoryServiceProvider` | Repository implementations |
| `[Module]ServiceProvider` | Module-specific services |

#### Container Usage

```php
use Lwt\Shared\Infrastructure\Container\Container;

// Get the container
$container = Container::getInstance();

// Retrieve a service
$textFacade = $container->get(TextFacade::class);

// Typed retrieval (for static analysis)
$textFacade = $container->getTyped(TextFacade::class, TextFacade::class);

// Create fresh instance
$service = $container->make(SomeService::class);
```

### 17. Multi-User Support

Version 3 adds optional multi-user support for shared installations.

#### Enabling Multi-User Mode

Set in `.env`:

```bash
MULTI_USER_ENABLED=true
```

#### User-Scoped Tables

When multi-user mode is enabled, the following tables are automatically filtered by user:

| Table | User ID Column |
|-------|----------------|
| `languages` | `LgUsID` |
| `texts` | `TxUsID` |
| `archivedtexts` | `AtUsID` |
| `words` | `WoUsID` |
| `tags` | `TgUsID` |
| `tags2` | `T2UsID` |
| `newsfeeds` | `NfUsID` |
| `settings` | `StUsID` |
| `local_dictionaries` | `LdUsID` |

#### UserId Value Object

User identity is represented by an immutable value object:

```php
use Lwt\Shared\Domain\ValueObjects\UserId;

// Create from integer
$userId = UserId::fromInt(123);

// Create for unsaved user (value 0)
$userId = UserId::unsaved();

// Get the integer value
$id = $userId->getValue();

// Check if saved
if ($userId->isSaved()) {
    // User exists in database
}
```

#### UserScopedQuery

The `UserScopedQuery` class automatically adds user filtering:

```php
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;

// Automatically adds WHERE clause for current user
$query = new UserScopedQuery('texts');
$texts = $query->where('TxLgID', '=', $langId)->get();
```

#### Accessing Current User

```php
use Lwt\Core\Globals;

// Get current user ID (may be null)
$userId = Globals::getCurrentUserId();

// Get user ID or throw exception
$userId = Globals::requireUserId();

// Check if multi-user is enabled
if (Globals::isMultiUserEnabled()) {
    // Apply user filtering
}
```

### 18. JavaScript-to-PHP Communication Modernization

Version 3 modernizes all JavaScript-to-PHP communication, replacing legacy patterns with a unified REST API approach.

#### Changes

| Aspect | Before | After |
|--------|--------|-------|
| AJAX calls | jQuery `$.post()`, `$.getJSON()` | Native `fetch()` API |
| Word operations | Frame navigation (`target="ro"`) | REST API with in-page updates |
| Result display | iframe content replacement | Result panels and modals |
| Audio control | `window.parent.frames` access | Direct module imports |

#### New Frontend API Layer

Type-safe API client modules in `src/frontend/js/`:

- `shared/api/client.ts` - Centralized fetch wrapper
- `modules/vocabulary/api/terms_api.ts` - Term/word operations
- `modules/text/api/texts_api.ts` - Text operations
- `modules/review/api/review_api.ts` - Review operations
- `modules/admin/api/settings_api.ts` - Settings operations

#### API Mode

The frontend uses **API mode by default** for word operations, providing:

- Faster status changes (no iframe reload)
- Better mobile experience
- Reduced server load
- In-page result panels instead of frame navigation

Legacy frame mode can be enabled via `LWT_DATA.settings.use_frame_mode = true` for backward compatibility.

#### Removed Legacy Files

All legacy PHP endpoint files have been removed:

| Removed File | Replacement |
|--------------|-------------|
| `set_word_status.php` | `PUT /api/v1/terms/{id}/status/{status}` |
| `delete_word.php` | `DELETE /api/v1/terms/{id}` |
| `insert_word_wellknown.php` | `POST /api/v1/terms/quick` (status=99) |
| `insert_word_ignore.php` | `POST /api/v1/terms/quick` (status=98) |
| `inline_edit.php` | `/word/inline-edit` route |
| `set_test_status.php` | `PUT /api/v1/review/status` |
| `trans.php` | Direct dictionary URLs |

### 19. Lemma Support

Version 3 adds lemma (base form) support for vocabulary items, enabling word family grouping.

#### Purpose

Lemmatization groups related word forms (e.g., "runs", "running", "ran") under a common base form. This enables:

- Word family queries for vocabulary review
- Better organization of inflected forms
- Foundation for automatic lemmatization in future versions

#### Database Changes

**Migration:** `db/migrations/20260109_200000_add_lemma_support.sql`

| Column | Type | Purpose |
|--------|------|---------|
| `WoLemma` | `VARCHAR(250)` | Lemma in original case |
| `WoLemmaLC` | `VARCHAR(250)` | Lowercase lemma for lookups |

**Index:** `idx_words_lemma` on `(WoLemmaLC, WoLgID)` for efficient word family queries.

#### Key Components

| Component | Location | Purpose |
|-----------|----------|---------|
| `Term` entity | `src/Modules/Vocabulary/Domain/Term.php` | Domain model with lemma properties |
| `MySqlTermRepository` | `src/Modules/Vocabulary/Infrastructure/` | `findByLemma()` method for word family queries |
| `WordService` | `src/Modules/Vocabulary/Application/Services/` | Lemma handling in CRUD operations |

#### Usage

```php
// Domain entity
$term->updateLemma('run');  // Sets both WoLemma and WoLemmaLC

// Repository query
$wordFamily = $repository->findByLemma($languageId, 'run');
```

#### User Interaction

- Lemma input field added to word edit forms
- Users manually enter lemmas when creating or editing terms
- Future: Automatic lemmatization via NLP service

### 20. EPUB Import with Auto-Split

Version 3 adds unified text import with EPUB file support and automatic splitting for large texts.

#### Purpose

- Import EPUB e-books directly into LWT
- Automatic chapter extraction from EPUB structure
- Auto-split large texts (>60KB) at paragraph boundaries
- Track reading progress across book chapters

#### Database Changes

**Migration:** `db/migrations/20260109_195419_add_books_table.sql`

**New `books` table:**

| Column | Type | Purpose |
|--------|------|---------|
| `BkID` | `SMALLINT` | Primary key |
| `BkUsID` | `SMALLINT` | User ID (multi-user mode) |
| `BkLgID` | `SMALLINT` | Language (FK to languages) |
| `BkTitle` | `VARCHAR(200)` | Book title |
| `BkAuthor` | `VARCHAR(200)` | Author name |
| `BkSourceType` | `ENUM` | 'text', 'epub', or 'pdf' |
| `BkSourceHash` | `CHAR(64)` | SHA-256 for duplicate detection |
| `BkTotalChapters` | `SMALLINT` | Total chapter count |
| `BkCurrentChapter` | `SMALLINT` | Current reading position |

**Columns added to `texts` table:**

| Column | Type | Purpose |
|--------|------|---------|
| `TxBkID` | `SMALLINT` | Reference to book (FK, cascade delete) |
| `TxChapterNum` | `SMALLINT` | Chapter number (1-based) |
| `TxChapterTitle` | `VARCHAR(200)` | Chapter title |

#### Key Components

| Component | Location | Purpose |
|-----------|----------|---------|
| `EpubParserService` | `src/Modules/Book/Application/Services/` | EPUB extraction using `kiwilan/php-ebook` |
| `TextSplitterService` | `src/Modules/Book/Application/Services/` | Split large texts at paragraph boundaries |
| `Book` entity | `src/Modules/Book/Domain/Book.php` | Book domain model with progress tracking |
| `ImportEpub` use case | `src/Modules/Book/Application/UseCases/` | Orchestrates EPUB import workflow |
| `BookController` | `src/Modules/Book/Http/` | Handles `/books` and `/book/import` routes |

#### Text Splitting Algorithm

```php
// TextSplitterService constants
const DEFAULT_MAX_BYTES = 60000;   // Target chunk size
const ABSOLUTE_MAX_BYTES = 65000;  // MySQL TEXT limit

// Splits at paragraph boundaries (\n\n) to keep chunks under 60KB
$chapters = $splitter->split($longText, 60000);
```

#### User Interaction

1. Navigate to `/book/import`
2. Upload EPUB file and select language
3. System extracts metadata and chapters automatically
4. Large chapters are auto-split at paragraph boundaries
5. Each chapter becomes a linked text record
6. Reading interface shows chapter navigation (prev/next/dropdown)
7. Progress tracked per book

### 21. Piper Text-to-Speech Service

Version 3 adds a Python microservice for high-quality text-to-speech using Piper TTS.

#### Purpose

- High-quality neural TTS for language learning
- Multiple voice options per language
- Voice management (download, install, delete)
- Integration with MeCab/Jieba parsers for CJK languages

#### Architecture

```text
┌─────────────────┐     HTTP      ┌─────────────────┐
│   PHP Backend   │ ───────────►  │  Python NLP     │
│   (LWT App)     │               │  (FastAPI)      │
└─────────────────┘               └─────────────────┘
                                         │
                                         ▼
                                  ┌─────────────────┐
                                  │   Piper TTS     │
                                  │   + MeCab/Jieba │
                                  └─────────────────┘
```

#### Database Changes

**Migration:** `db/migrations/20260109_180418_add_piper_voice.sql`

| Column | Type | Purpose |
|--------|------|---------|
| `LgPiperVoiceId` | `VARCHAR(100)` | Piper voice ID (e.g., 'en_US-lessac-medium') |

#### Python Microservice

**Location:** `services/nlp/`

| File | Purpose |
|------|---------|
| `app/main.py` | FastAPI entry point |
| `app/services/piper_tts.py` | Piper TTS synthesis |
| `app/services/voice_manager.py` | Voice catalog and downloads |
| `app/services/parsers/mecab.py` | Japanese text parsing |
| `app/services/parsers/jieba.py` | Chinese text parsing |
| `app/routers/tts.py` | TTS REST endpoints |
| `app/routers/parse.py` | Text parsing endpoints |

#### PHP Integration

**Handler:** `src/backend/Api/V1/Handlers/NlpServiceHandler.php`

```php
// Synthesize speech
$audioDataUrl = $handler->speak($text, $voiceId);

// Voice management
$voices = $handler->getVoices();
$handler->downloadVoice('en_US-lessac-medium');
$handler->deleteVoice('en_US-lessac-medium');

// Text parsing
$tokens = $handler->parse($text, 'mecab');
```

#### REST Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/tts/speak` | POST | Synthesize text to audio |
| `/tts/voices` | GET | List all available voices |
| `/tts/voices/installed` | GET | List installed voices |
| `/tts/voices/download` | POST | Download voice from catalog |
| `/tts/voices/{id}` | DELETE | Remove installed voice |
| `/parse/` | POST | Parse text with MeCab/Jieba |
| `/parse/available` | GET | List available parsers |

#### Docker Configuration

```yaml
# docker-compose.yml
services:
  nlp:
    build: ./services/nlp
    volumes:
      - ./services/nlp/voices:/app/voices
    environment:
      - PIPER_VOICES_DIR=/app/voices
```

#### User Interaction

1. Admin selects Piper voice for a language in TTS settings
2. User clicks speak button when reading text
3. Text sent to NLP service for synthesis
4. Audio returned as base64 data URL and played inline
5. Additional voices can be downloaded from the admin panel

### 22. Multi-Word Expression Visual Display

Version 3 enhances multi-word expression (MWE) display with connected underlines and status colors.

#### Purpose

- Visually connect words that form a multi-word expression
- Show MWE status through color-coded underlines
- Display MWE translation on hover over any word in the group

#### Implementation

**API Changes:** `src/Modules/Text/Http/TextApiHandler.php`

Multi-word data now includes:

| Field | Purpose |
|-------|---------|
| `translation` | MWE translation |
| `status` | Learning status (0-5, 98, 99) |
| `wordId` | MWE word ID |
| `position` | Starting position in text |

**Frontend Renderer:** `src/frontend/js/modules/text/pages/reading/text_renderer.ts`

```typescript
// Wraps MWE words in connected group
<span class="mw-group mw-status{status}"
      data-mw-text="multi word"
      data-mw-trans="translation">
  <span class="word">multi</span>
  <span class="word">word</span>
</span>
```

#### CSS Styling

**File:** `src/frontend/css/base/styles.css`

| Class | Style |
|-------|-------|
| `.mw-group` | Connected bottom border (2px solid) |
| `.mw-status0` | Blue (`#5ABAFF`) - new |
| `.mw-status1-5` | Orange → yellow → green gradient |
| `.mw-status99` | Light green (`#CCFFCC`) - well-known |
| `.mw-status98` | Gray dashed (`#888888`) - ignored |

Individual word underlines are hidden within groups:

```css
.mw-group .wsty,
.mw-group .word,
.mw-group .mword {
  border-bottom: none;
}
```

#### User Interaction

1. When reading text with saved multi-word expressions
2. All words in the MWE display a connected bottom border
3. Border color indicates learning status
4. Hovering over any word in the group shows MWE translation
5. Clear visual indication that words form a semantic unit

## Future Improvements

This refactoring enables:

1. **Unit testing:** Use cases, services, and repositories are fully testable in isolation
2. **Cleaner URLs:** SEO-friendly URLs without `.php` extensions
3. **Modular architecture:** 10 feature modules with clear boundaries
4. **Namespace support:** PHP autoloading with PSR-4 namespaces
5. **Explicit dependencies:** DI container makes dependencies visible and swappable
6. **Modern configuration:** `.env` files work with Docker, CI/CD, and modern deployment workflows
7. **API versioning:** Structured API handlers allow for future API versions
8. **Multi-user support:** Data isolation for shared installations
9. **Clean Architecture:** Domain, Application, and Infrastructure layers are separated
10. **Repository pattern:** Database access abstracted behind interfaces for future ORM migration

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

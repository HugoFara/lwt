# Core to MVC Migration Task

This document provides step-by-step instructions for migrating files from `src/backend/Core/` to the MVC architecture.

## Overview

The goal is to progressively clean up `src/backend/Core/` by either:

1. **Extracting logic** into Services (business logic) and Controllers (HTTP handling)
2. **Moving utilities** to appropriate namespaced classes
3. **Removing files** that are no longer needed after migration

## Directory Reference

| Directory | Purpose |
|-----------|---------|
| `src/backend/Controllers/` | HTTP request handling, parameter validation, response coordination |
| `src/backend/Services/` | Business logic, database operations, complex calculations |
| `src/backend/Views/` | PHP templates for HTML output |
| `src/backend/Core/` | Legacy utilities being migrated (target for cleanup) |
| `src/backend/Api/V1/Handlers/` | REST API endpoint handlers |

## Migration Workflow

### Step 1: Analyze the Target File

1. **Read the file completely** to understand:
   - What functions/classes it contains
   - What dependencies it has (require/include statements)
   - Who calls these functions (grep for function names across codebase)
   - Whether it handles HTTP requests directly or provides utilities

2. **Categorize the file content**:
   - **HTTP handlers**: Should become Controller methods
   - **Business logic**: Should become Service methods
   - **UI generation**: Should become View templates
   - **Utilities**: Should become namespaced helper classes
   - **Database operations**: Should move to `Database/` namespace or Service layer

### Step 2: Identify Callers

Run these searches to find all usages:

```bash
# Find all files that include/require the target file
grep -r "require.*filename" src/
grep -r "include.*filename" src/

# Find all calls to functions defined in the file
grep -r "function_name(" src/ --include="*.php"
```

### Step 3: Plan the Migration

Based on analysis, decide the migration strategy:

| Current State | Migration Target |
|---------------|------------------|
| Standalone page file | Controller + Service + View |
| Utility functions | Namespaced class in `Core/` subdirectory |
| Database helpers | `Database/` namespace class |
| HTML generation | View template file |
| Business logic mixed with UI | Split into Service + View |

### Step 4: Create New Structure

#### For Controller Migration

Create or update Controller in `src/backend/Controllers/`:

```php
<?php
namespace Lwt\Controllers;

class ExampleController extends BaseController
{
    private ExampleService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ExampleService();
    }

    public function action(array $params): void
    {
        // 1. Validate input using $this->param(), $this->paramInt(), etc.
        // 2. Call service methods for business logic
        // 3. Include view template for output
    }
}
```

Then add route in `src/backend/Router/routes.php`:

```php
$router->register('/example/action', 'ExampleController@action');
$router->registerLegacy('old_file.php', '/example/action');
```

#### For Service Migration

Create Service in `src/backend/Services/`:

```php
<?php
namespace Lwt\Services;

use Lwt\Database\Connection;
use Lwt\Database\Escaping;

class ExampleService
{
    public function doSomething(): array
    {
        // Business logic here
        return [];
    }
}
```

#### For View Migration

Create View in `src/backend/Views/Category/`:

```php
<?php
// View receives variables from controller, e.g., $data, $items
// Pure presentation logic only
?>
<div class="example">
    <?php foreach ($items as $item): ?>
        <p><?= htmlspecialchars($item['name']) ?></p>
    <?php endforeach; ?>
</div>
```

### Step 5: Update Dependencies

1. **Update all callers** to use new Service/Controller methods
2. **Remove require/include** statements for the old file where possible
3. **Update any API handlers** in `src/backend/Api/V1/Handlers/`

### Step 6: Update Tests

1. **Find existing tests**:

   ```bash
   grep -r "TargetFileName" tests/
   ```

2. **Update test imports** to use new Service/Controller classes

3. **Add new tests** for Service methods

### Step 7: Update All Callers

1. **Replace require/include statements** in all files that include the old file:
   - Remove `require_once __DIR__ . '/../Core/OldFile.php';`
   - Add `require_once __DIR__ . '/../Services/NewService.php';` if not already present
   - Add `use Lwt\Services\NewService;` statement

2. **Replace function calls** with Service method calls:
   - Old: `old_function($arg)`
   - New: `$service->newMethod($arg)` or `NewService::staticMethod($arg)`

3. **For files that call the function many times**, create a local service instance:

   ```php
   $service = new NewService();
   // Then use $service->method() throughout
   ```

4. **For Controllers**, inject the service in the constructor:

   ```php
   private NewService $service;

   public function __construct()
   {
       parent::__construct();
       $this->service = new NewService();
   }
   ```

### Step 8: Clean Up

1. **Remove the old file** from `src/backend/Core/`
2. **Remove any orphaned require/include** statements
3. **Run tests** to verify nothing is broken:

   ```bash
   composer test:no-coverage
   ./vendor/bin/psalm
   ```

## File-Specific Instructions

When the user specifies a file to migrate, analyze it and provide:

1. **File Summary**: What the file does, key functions
2. **Dependency Graph**: What includes it, what it includes
3. **Migration Plan**: Where each function/class should go
4. **Code Changes**: Specific edits needed
5. **Test Updates**: Which tests need modification
6. **Cleanup Checklist**: Final steps to remove old file

## Common Patterns

### Pattern: Functions to Service Class

**Before** (in `Core/example.php`):

```php
function do_something($id) {
    global $tbpref;
    // logic
}
```

**After** (in `Services/ExampleService.php`):

```php
namespace Lwt\Services;

use Lwt\Core\Globals;

class ExampleService
{
    public function doSomething(int $id): mixed
    {
        $prefix = Globals::getTablePrefix();
        // logic
    }
}
```

### Pattern: Page Handler to Controller

**Before** (in `Core/page.php`):

```php
require_once 'db_bootstrap.php';
pagestart('Title', true);
// handle $_REQUEST, generate HTML
pageend();
```

**After** (in `Controllers/PageController.php`):

```php
public function index(array $params): void
{
    $this->render('Title', true);
    // Call service, include view
    $this->endRender();
}
```

### Pattern: Mixed Logic to Service + View

**Before**:

```php
function show_list($items) {
    echo '<table>';
    foreach ($items as $item) {
        $processed = process($item); // business logic
        echo '<tr><td>' . $processed . '</td></tr>'; // presentation
    }
    echo '</table>';
}
```

**After Service**:

```php
public function processItems(array $items): array
{
    return array_map(fn($item) => $this->process($item), $items);
}
```

**After View**:

```php
<table>
    <?php foreach ($processedItems as $item): ?>
        <tr><td><?= htmlspecialchars($item) ?></td></tr>
    <?php endforeach; ?>
</table>
```

## Remaining Core Files

Current files in `src/backend/Core/` that may need migration:

### Bootstrap (Keep - Application initialization)

- `Bootstrap/db_bootstrap.php`
- `Bootstrap/EnvLoader.php`
- `Bootstrap/start_session.php`

### Database (Keep - Database abstraction)

- `Database/Connection.php`
- `Database/DB.php`
- `Database/Escaping.php`
- `Database/Settings.php`
- `Database/Validation.php`
- `Database/Maintenance.php`
- `Database/Migrations.php`
- `Database/Configuration.php`
- `Database/QueryBuilder.php`
- `Database/TextParsing.php`

### Entity (Keep - Domain models)

- `Entity/Language.php`
- `Entity/Term.php`
- `Entity/Text.php`
- `Entity/GoogleTranslate.php`

### Utilities (Evaluate for refactoring)

- `Utils/error_handling.php` - Consider namespaced class
- `Utils/sql_file_parser.php` - Consider namespaced class
- `Utils/debug_utilities.php` - Consider namespaced class
- `Utils/string_utilities.php` - Consider namespaced class

### Feature-Specific (Candidates for Service extraction)

- `Text/*.php` - Extract to TextService
- ~~`Word/*.php`~~ - **COMPLETED**: Migrated to `WordStatusService`, `DictionaryService`, and `ExpressionService`
  - `word_status.php` → `Services/WordStatusService.php` (static methods: `getStatuses()`, `getStatusName()`, `getStatusAbbr()`, `isValidStatus()`)
  - `word_scoring.php` → `Services/WordStatusService.php` (constants: `SCORE_FORMULA_TODAY`, `SCORE_FORMULA_TOMORROW`; static method: `makeScoreRandomInsertUpdate()`)
  - `dictionary_links.php` → `Services/DictionaryService.php` (methods: `createTheDictLink()`, `createDictLinksInEditWin()`, `makeOpenDictStr()`, `makeDictLinks()`, etc.)
  - `expression_handling.php` → `Services/ExpressionService.php` (methods: `findMecabExpression()`, `findStandardExpression()`, `insertExpressions()`, `newMultiWordInteractable()`)
- ~~`Language/*.php`~~ - **COMPLETED**: Migrated to `LanguageService` and `LanguageDefinitions`
  - `langdefs.php` → `Services/LanguageDefinitions.php` (static class for language definitions)
  - `language_utilities.php` → `Services/LanguageService.php` (methods: `getAllLanguages()`, `getLanguageName()`, `getLanguageCode()`, `getScriptDirectionTag()`)
  - `phonetic_reading.php` → `Services/LanguageService.php` (methods: `getPhoneticReadingById()`, `getPhoneticReadingByCode()`)
- ~~`Media/*.php`~~ - **COMPLETED**: Migrated to `MediaService`
  - `media_helpers.php` → `Services/MediaService.php` (methods: `searchMediaPaths()`, `getMediaPaths()`, `getMediaPathOptions()`, `getMediaPathSelector()`)
  - `media_players.php` → `Services/MediaService.php` (methods: `renderMediaPlayer()`, `renderVideoPlayer()`, `renderAudioPlayer()`, `renderHtml5AudioPlayer()`, `renderLegacyAudioPlayer()`)
- ~~`Export/*.php`~~ - **COMPLETED**: Migrated to `ExportService`
  - `export_helpers.php` → `Services/ExportService.php` (methods: `exportAnki()`, `exportTsv()`, `exportFlexible()`, `replaceTabNewline()`, `maskTermInSentence()`, `maskTermInSentenceV2()`)
- ~~`Test/*.php`~~ - **COMPLETED**: Migrated to `TestService`
  - `test_helpers.php` → `Services/TestService.php` (methods: `getTestSql()`, `buildSelectionTestSql()`, inlined SQL projection logic)
- `UI/*.php` - Keep as helpers or move to Views

### Root Files (Evaluate)

- `Globals.php` - Keep (core infrastructure)
- `version.php` - Keep (version info)
- `settings.php` - Evaluate for SettingsService
- `save_setting_redirect.php` - Move to AdminController
- `database_operations.php` - Evaluate for Database namespace

## Verification Checklist

After migration, verify:

- [ ] All tests pass: `composer test:no-coverage`
- [ ] Static analysis passes: `./vendor/bin/psalm`
- [ ] E2E tests pass: `npm run e2e` (if routes changed)
- [ ] Old file removed from `Core/`
- [ ] No orphaned require/include statements
- [ ] New code follows PSR standards
- [ ] Routes work correctly (both new and legacy URLs)

## Notes

- **Use InputValidator**: For all request parameter handling
- **Use Globals class**: Instead of `global $tbpref` etc.
- **Use Database classes**: `Connection::query()`, `Escaping::toSqlSyntax()`, etc.

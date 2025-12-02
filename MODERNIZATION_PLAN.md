# LWT Modernization Plan

**Last Updated:** 2025-12-01 (DI Container and Repository Layer implemented)
**Current Version:** 3.0.0-fork
**Target PHP Version:** 8.1-8.4

## Executive Summary

LWT carried significant technical debt from its 2007 origins. This document tracks the modernization progress and remaining work. **Major architectural transformation has been achieved** with the v3 release.

**Overall Technical Debt Score:** 3.0/10 (Low) - *Down from 6.75/10*

## Progress Overview

| Phase | Status | Completion |
|-------|--------|------------|
| Quick Wins | **COMPLETE** | 100% |
| Phase 1: Security & Safety | **PARTIAL** | ~50% |
| Phase 2: Refactoring | **SUBSTANTIAL** | ~85% |
| Phase 3: Modernization | **IN PROGRESS** | ~60% |

## Critical Issues

### 1. SQL Injection Vulnerabilities (CRITICAL - Security)

**Original State:**

- All database queries used string concatenation with `mysqli_real_escape_string()`
- 63 root PHP files + 15 AJAX endpoints affected
- Pattern: `'UPDATE table SET col=' . convert_string_to_sqlsyntax($_REQUEST['val'])`

**Current State (2025-11-28):**

- [x] Centralized `Escaping` class in `src/backend/Core/Database/Escaping.php`
- [x] All queries use `Escaping::toSqlSyntax()` for proper escaping
- [x] QueryBuilder class available with fluent interface
- [ ] **NOT MIGRATED**: Still uses string escaping, not prepared statements
- [ ] Prepared statements (`mysqli::prepare()`, `bind_param()`) not implemented

**Risk Level:** MEDIUM - String escaping is functional but not best practice

**Current Architecture:**

```php
// Current pattern (safe but not optimal)
Connection::execute(
    'INSERT INTO ' . $this->tbpref . 'words (WoLgID, WoText)
     VALUES (' . (int)$langId . ', ' . Escaping::toSqlSyntax($text) . ')'
);

// Target pattern (not yet implemented)
$stmt = $db->prepare('INSERT INTO words (WoLgID, WoText) VALUES (?, ?)');
$stmt->bind_param('is', $langId, $text);
```

**Remaining Work:**

- [ ] Implement prepared statements in `Connection` class
- [ ] Migrate 43 service files from Escaping to prepared statements
- [ ] Add parameterized query support to QueryBuilder

### 2. Monolithic File Structure (CRITICAL - Maintainability)

**Original Problem Files:**

- `inc/session_utility.php` - 4,290 lines, 80+ functions
- `inc/database_connect.php` - 2,182 lines, 46+ functions
- `edit_languages.php` - 1,528 lines
- `edit_texts.php` - 1,344 lines

**Current State (2025-11-28):** RESOLVED

- [x] **ALL legacy monolithic files eliminated**
- [x] `inc/` directory no longer exists
- [x] All root PHP files (edit_*.php, words_edit.php, etc.) removed
- [x] Full MVC architecture implemented

**New Architecture (2025-11-30):**

```text
src/backend/
├── Api/V1/                    # REST API
│   └── Handlers/              # 10 API handlers
├── Controllers/               # 14 controllers (8,223 lines total)
│   ├── BaseController.php     # Abstract base
│   ├── TextController.php
│   ├── WordController.php
│   └── ...
├── Core/                      # 31 files in 8 subdirectories
│   ├── Bootstrap/             # App initialization
│   ├── Database/              # DB classes (Connection, DB, QueryBuilder, Escaping, Settings)
│   ├── Entity/                # Data models (Language, Text, Term, GoogleTranslate)
│   ├── Http/                  # InputValidator, param_helpers
│   ├── Integration/           # External integrations
│   ├── Language/              # Language processing
│   ├── Utils/                 # Utility functions
│   ├── Globals.php            # Global state access
│   └── StringUtils.php        # String utilities
├── Router/                    # URL routing system
├── Services/                  # 36 services (18,571 lines total)
│   ├── TextService.php
│   ├── WordService.php
│   └── ...
├── Views/                     # 92 template files in 10 directories (8,499 lines)
│   ├── Admin/, Feed/, Home/, Language/, Mobile/
│   ├── Tags/, Test/, Text/, TextPrint/, Word/
│   └── ...
└── View/Helper/               # View helpers (FormHelper, ViteHelper, etc.)
```

**File Size Distribution:**

- Largest service: TextService.php (~1,500 lines) - down from 4,290
- Most files under 500 lines
- 100% strict_types across all 194 PHP files

### 3. Input Validation (HIGH - Security)

**Original Issues:**

- Direct `$_REQUEST`/`$_GET`/`$_POST` access throughout codebase
- Inconsistent type casting
- No length validation against database constraints

**Current State (2025-11-30):** SUBSTANTIAL PROGRESS

- [x] `BaseController` provides `param()`, `get()`, `post()` abstraction
- [x] Type casting used consistently (`(int)`, `(string)`)
- [x] `Validation` class for database ID existence checks
- [x] **IMPLEMENTED**: `InputValidator` class in `src/backend/Core/Http/InputValidator.php` (782 lines)
- [x] `InputValidator` provides: `getString()`, `getInt()`, `getBool()`, `getArray()`, `getEnum()`, `getUrl()`, `getUploadedFile()`, etc.
- [ ] Direct superglobal access remains in 10 files (~295 occurrences)
- [ ] 14 files now use `InputValidator` class

**Current Pattern:**

```php
// InputValidator provides type-safe validation (NEW)
use Lwt\Core\Http\InputValidator;
$textId = InputValidator::getInt('text', null, 1);  // min value 1
$query = InputValidator::getString('query', '', true);  // trimmed
$status = InputValidator::getIntEnum('status', [1, 2, 3, 4, 5, 98, 99], 1);

// BaseController provides access abstraction (still used)
protected function param(string $key, mixed $default = null): mixed {
    return $_REQUEST[$key] ?? $default;
}
```

**Remaining Work:**

- [ ] Migrate remaining direct `$_REQUEST` accesses to `InputValidator`
- [ ] Add length validation for string inputs in more places

### 4. Code Duplication (HIGH - Maintainability)

**Original Patterns:**

- 20+ `get_*_selectoptions()` functions with identical structure
- CRUD operations duplicated across files
- Delete query patterns repeated 10+ times

**Current State (2025-11-28):** LARGELY RESOLVED

- [x] 29 `get_*_selectoptions()` functions consolidated via delegation
- [x] `SelectOptionsBuilder` class handles all select option generation
- [x] `FormHelper` class provides form utilities
- [x] QueryBuilder with full CRUD support available
- [ ] DELETE queries still duplicated (42 occurrences across services)
- [ ] Services don't consistently use QueryBuilder

**New Helper Classes:**

| Class | Location | Purpose |
|-------|----------|---------|
| `FormHelper` | `View/Helper/FormHelper.php` | Form element utilities |
| `SelectOptionsBuilder` | `View/Helper/SelectOptionsBuilder.php` | Select option generation |
| `StatusHelper` | `View/Helper/StatusHelper.php` | Word status helpers |
| `PageLayoutHelper` | `View/Helper/PageLayoutHelper.php` | Page layout utilities |

## Phased Modernization Roadmap

### Phase 1: Security & Safety

#### 1.1 Prepared Statements Migration

**Priority:** P0 (Critical)
**Status:** NOT STARTED
**Effort:** Large (200+ hours)

**Current State:**

- Database classes exist (`Connection`, `DB`, `QueryBuilder`)
- String escaping via `Escaping::toSqlSyntax()` used everywhere
- No prepared statement infrastructure

**Next Steps:**

1. Add `prepare()` method to `Connection` class
2. Create `PreparedStatement` wrapper class
3. Migrate highest-risk endpoints first (API handlers)
4. Add integration tests for each converted query

**Success Criteria:**

- [ ] 100% of queries use prepared statements
- [ ] All tests pass
- [ ] SQL injection scan shows no vulnerabilities

#### 1.2 Input Validation Layer

**Priority:** P0 (Critical)
**Status:** IMPLEMENTED
**Effort:** Medium (60 hours) - DONE

**Implementation (2025-11-30):**

```php
// Located at src/backend/Core/Http/InputValidator.php (782 lines)
class InputValidator {
    public static function getString(string $key, string $default = '', bool $trim = true): string
    public static function getInt(string $key, ?int $default = null, ?int $min = null, ?int $max = null): ?int
    public static function requireInt(string $key, ?int $min = null, ?int $max = null): int
    public static function getPositiveInt(string $key, ?int $default = null): ?int
    public static function getBool(string $key, ?bool $default = null): ?bool
    public static function getArray(string $key, array $default = []): array
    public static function getIntArray(string $key, array $default = []): array
    public static function getStringArray(string $key, array $default = [], bool $trim = true): array
    public static function getEnum(string $key, array $allowed, string $default = ''): string
    public static function getUrl(string $key, string $default = ''): string
    public static function getUploadedFile(string $key): ?array
    public static function getUploadedTextContent(string $key, int $maxSize = 1048576): ?string
    public static function getHtmlSafe(string $key, string $default = ''): string
    public static function has(string $key): bool
    public static function isPost(): bool
    // ... and more
}
```

**Adoption:** 14 files now use `InputValidator`

**Success Criteria:**

- [ ] Zero direct `$_REQUEST`/`$_GET`/`$_POST` access in codebase (currently ~295 in 10 files)
- [x] InputValidator class implemented with comprehensive methods

#### 1.3 Session Security Hardening

**Priority:** P1 (High)
**Status:** NOT IMPLEMENTED
**Effort:** Small (8 hours)

**Current State:**

- `start_session.php` exists at `src/backend/Core/Bootstrap/start_session.php`
- Simple `session_start()` call with no security configuration
- No `session_set_cookie_params()` settings
- TTS cookies use `SameSite=Strict` but sessions do not

**Missing Security Settings:**

```php
// NONE of these are currently configured:
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

**Success Criteria:**

- [ ] Session cookies have HttpOnly, Secure, SameSite flags
- [ ] Sessions expire after configured timeout
- [ ] Security audit passed

#### 1.4 XSS Prevention Audit

**Priority:** P1 (High)
**Status:** PARTIAL
**Effort:** Medium (40 hours)

**Current State:**

- [x] `tohtml()` wrapper function exists in `string_utilities.php`
- [x] Uses `htmlspecialchars()` with UTF-8 encoding
- [ ] No Content-Security-Policy headers
- [ ] No security headers (X-Frame-Options, X-Content-Type-Options)
- [ ] Inconsistent usage of `tohtml()` across views

**Missing Headers:**

```php
// NONE of these headers are set:
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'self\'');
header('Strict-Transport-Security: max-age=31536000');
```

**Success Criteria:**

- [ ] All user-generated content escaped before output
- [ ] CSP headers configured
- [ ] Security headers implemented
- [ ] XSS scanner shows no vulnerabilities

### Phase 2: Refactoring

#### 2.1 Break Up Monolithic Files

**Priority:** P1 (High)
**Status:** COMPLETE
**Effort:** X-Large (300+ hours) - DONE

**Achievements:**

- [x] All legacy monolithic files eliminated
- [x] 22 service classes created
- [x] 14 controllers implemented
- [x] 57 core utility files organized into 17 subdirectories
- [x] MVC architecture fully implemented

**Actual Structure vs. Planned:**

| Planned | Actual | Status |
|---------|--------|--------|
| `Services/TextService.php` | `Services/TextService.php` | DONE |
| `Services/WordService.php` | `Services/WordService.php` | DONE |
| `Services/MediaService.php` | `Core/Media/` | DONE (different location) |
| `Services/NavigationService.php` | `Core/Text/navigation.php` | DONE |
| `Services/ExportService.php` | `Core/Export/` | DONE |
| `Services/DatabaseService.php` | `Core/Database/Connection.php` + `DB.php` | DONE |
| `Repositories/*` | NOT CREATED | SKIPPED - Services access DB directly |
| `Models/*` | `Core/Entity/*` | PARTIAL - 4 entity classes |

#### 2.2 Eliminate Code Duplication

**Priority:** P2 (Medium)
**Status:** LARGELY COMPLETE
**Effort:** Medium (60 hours) - MOSTLY DONE

**Achievements:**

- [x] `SelectOptionsBuilder` consolidates select option generation
- [x] `FormHelper` provides form utilities
- [x] `QueryBuilder` with fluent API available
- [x] `BaseController` provides common controller functionality

**Remaining Issues:**

- [ ] 56 DELETE queries still use direct SQL
- [ ] Services don't consistently use QueryBuilder (only 4 files import it)
- [ ] No `AbstractCrudController` (chose Service pattern instead)

#### 2.3 Add Type Hints

**Priority:** P2 (Medium)
**Status:** COMPLETE
**Effort:** Large (120 hours) - DONE

**Current Coverage (2025-11-30):**

- Modern code (Controllers, Services): ~95% type coverage
- Legacy utility functions: ~70% type coverage
- Entity classes: Minimal (use docblocks)
- `strict_types`: **194 of 194 PHP files** (100%)

**Psalm Configuration:**

- Level 4 (strictest) configured
- Well-tuned suppressions for legacy code
- Running in CI pipeline

**Remaining Work:**

- [x] ~~Add `declare(strict_types=1)` to all files~~ **COMPLETE**
- [ ] Convert Entity docblocks to native property types
- [ ] Achieve Psalm level 1 compliance

### Phase 3: Modernization

#### 3.1 OOP Architecture

**Priority:** P2 (Medium)
**Status:** SUBSTANTIAL PROGRESS
**Effort:** X-Large (400+ hours) - ONGOING

**Achievements (2025-12-01):**

- [x] Service Layer Pattern implemented (36 services, 18,571 lines)
- [x] Controller pattern implemented (14 controllers, 8,223 lines)
- [x] View layer organized (92 files in 10 directories, 8,499 lines)
- [x] Entity classes exist (4 classes: GoogleTranslate, Language, Term, Text)
- [x] `Globals` class for configuration access
- [x] `StringUtils` class for string utilities
- [x] `InputValidator` class for input handling
- [x] **Repository Pattern IMPLEMENTED** (2025-12-01)
- [x] **Dependency Injection Container IMPLEMENTED** (2025-12-01)
- [ ] Factory Pattern NOT implemented

**New Architecture Components (2025-12-01):**

```text
src/backend/Core/
├── Container/                    # DI Container (NEW)
│   ├── Container.php             # PSR-11 compliant DI container
│   ├── ContainerInterface.php    # PSR-11 interface
│   ├── ContainerException.php    # Container exceptions
│   ├── NotFoundException.php     # Service not found exception
│   ├── ServiceProviderInterface.php  # Service provider interface
│   └── RepositoryServiceProvider.php # Repository registration
└── Repository/                   # Repository Layer (NEW)
    ├── RepositoryInterface.php   # Base repository interface
    ├── AbstractRepository.php    # Base repository with CRUD
    └── LanguageRepository.php    # Example implementation
```

**DI Container Features:**

- PSR-11 compliant interface
- Singleton and factory bindings
- Auto-wiring via reflection
- Service aliases
- Circular dependency detection
- Service providers for organizing registrations

**Repository Pattern Features:**

- Generic CRUD operations via `RepositoryInterface`
- Uses prepared statements for security
- Column mapping for entity <-> database
- Transaction support

**Usage Example:**

```php
// DI Container
$container = Container::getInstance();
$container->singleton(LanguageRepository::class, fn() => new LanguageRepository());
$repo = $container->get(LanguageRepository::class);

// Repository Pattern
$repo = new LanguageRepository();
$language = $repo->find(1);                    // Find by ID
$languages = $repo->findBy(['name' => 'English']); // Find by criteria
$repo->save($language);                        // Insert or update
$repo->delete($language);                      // Delete
```

**Remaining Work:**

- [ ] Migrate services to use DI container
- [ ] Create repositories for other entities (Text, Word, etc.)
- [ ] Reduce static method usage in existing services

#### 3.2 Database Modernization

**Priority:** P2 (Medium)
**Status:** NOT STARTED
**Effort:** Medium (80 hours)

**Current State:**

- All 14 tables use **MyISAM** engine
- Zero foreign key constraints
- Transaction methods defined in `DB` class but unused
- 4 migration files exist (schema versioning works)

**Required Changes:**

1. **Engine Migration** - MyISAM → InnoDB
   - All tables currently MyISAM
   - MEMORY engine used for temp tables (appropriate)

2. **Add Foreign Keys:**

   ```sql
   ALTER TABLE texts ADD CONSTRAINT fk_texts_language
       FOREIGN KEY (TxLgID) REFERENCES languages(LgID);
   ALTER TABLE words ADD CONSTRAINT fk_words_language
       FOREIGN KEY (WoLgID) REFERENCES languages(LgID);
   -- Plus 10+ more relationships
   ```

3. **Enable Transactions:**
   - `DB::beginTransaction()`, `commit()`, `rollback()` exist but unused
   - Multi-step operations (text import) should use transactions

**Success Criteria:**

- [ ] All tables use InnoDB
- [ ] Foreign key constraints enforced
- [ ] Transaction usage in multi-query operations

#### 3.3 PSR-4 Autoloading

**Priority:** P3 (Low)
**Status:** IMPLEMENTED
**Effort:** Small (16 hours) - DONE

**Current Configuration:**

```json
{
    "autoload": {
        "psr-4": {
            "Lwt\\": "src/backend/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Lwt\\Tests\\": "tests/backend/"
        }
    }
}
```

**Adoption Rate (2025-11-30):**

- 204 of 194 PHP files have `Lwt\` namespace declarations
- 99 files actively use/import `Lwt\` namespaced classes
- Controllers, Services, Database classes: 100%
- Core utility functions: Delegating to namespaced classes

**Remaining Work:**

- [x] ~~Migrate remaining procedural files to namespaced classes~~ SUBSTANTIAL PROGRESS
- [ ] Complete migration of remaining procedural helpers

#### 3.4 Exception Handling

**Priority:** P3 (Low)
**Status:** NOT STARTED
**Effort:** Medium (60 hours)

**Current State:**

- Standard PHP exceptions used (`RuntimeException`)
- No custom exception hierarchy
- Error display enabled in development (`display_errors = 1`)

**Planned Exceptions:**

- [ ] `LwtException` (base)
- [ ] `DatabaseException`
- [ ] `ValidationException`
- [ ] `NotFoundException`
- [ ] `AuthenticationException`

**Success Criteria:**

- [ ] Custom exception hierarchy implemented
- [ ] No `die()` or `exit()` calls (except entry points)
- [ ] Production error display disabled
- [ ] All errors logged

## Quick Wins

### QW1: Add Composer Autoload Configuration

**Status:** COMPLETE

### QW2: Configure Session Security

**Status:** NOT DONE - Still requires implementation

### QW3: Create InputValidator Class

**Status:** COMPLETE (2025-11-30)

- `InputValidator` class implemented at `src/backend/Core/Http/InputValidator.php`
- Comprehensive validation methods: `getString()`, `getInt()`, `getBool()`, `getArray()`, `getEnum()`, `getUrl()`, `getEmail()`, `getUploadedFile()`, etc.
- 14 files now using `InputValidator`

### QW4: Add CI Pipeline for Code Quality

**Status:** COMPLETE

- Psalm runs in CI
- PHPUnit runs in CI
- Cypress E2E tests available

### QW5: Add Database Service Skeleton

**Status:** COMPLETE

- `Connection` class provides query execution
- `DB` facade provides simplified interface
- `QueryBuilder` provides fluent queries

## Success Metrics

### Phase 1 Completion

- [ ] Zero SQL injection vulnerabilities (prepared statements)
- [ ] Zero direct `$_REQUEST` access (InputValidator)
- [ ] Session security audit passed (cookie flags)
- [ ] XSS vulnerabilities: 0 (security headers + consistent escaping)

### Phase 2 Completion

- [x] Average file size < 500 lines (achieved for most files)
- [ ] Code duplication < 5% (DELETE queries still duplicated)
- [x] Type coverage: ~90% (strict_types in 100% of files)
- [ ] Test coverage: 60%+ (80 test files exist)

### Phase 3 Completion

- [x] OOP code: 80%+ (achieved via MVC)
- [ ] Database: 100% InnoDB with foreign keys
- [ ] Psalm level: 1 (currently level 4 with suppressions)
- [x] DI container in use (implemented 2025-12-01)

## Remaining High-Priority Work

### P0 (Critical - Security)

1. **Prepared Statements** - Migrate from string escaping
2. **Session Security** - Add cookie flags (HttpOnly, Secure, SameSite)
3. **Security Headers** - Add CSP, X-Frame-Options, etc.

### P1 (High)

1. ~~**InputValidator** - Centralized input validation~~ **DONE**
2. **Exception Handling** - Custom exception hierarchy
3. **Database Migration** - MyISAM to InnoDB

### P2 (Medium)

1. ~~**DI Container** - Replace static dependencies~~ **DONE** (2025-12-01)
2. ~~**Repository Layer** - Abstract database access~~ **DONE** (2025-12-01)
3. **Type Hints** - Complete coverage + strict_types
4. **QueryBuilder Adoption** - Reduce direct SQL
5. **Deprecated Function Migration** - See section below
6. **Migrate Services to DI** - Wire existing services into container

## Deprecated Global Functions

**Status (2025-11-30):** Database deprecated functions removed, utility functions delegating to classes.

### Recently Completed

- [x] **`deprecated_functions.php` removed** - All 45 database wrapper functions eliminated
- [x] All code migrated to use class-based API (`Connection`, `Escaping`, `Settings`, etc.)
- [x] Psalm stubs updated to remove deprecated function signatures
- [x] **`StringUtils` class created** - `toClassName()`, `getSeparators()`, `getFirstSeparator()`, `toHex()`
- [x] **`InputValidator` class created** - Comprehensive input validation

### Remaining Deprecated Functions

| Function | Calls | Files | Suggested Replacement | Status |
|----------|-------|-------|----------------------|--------|
| `tohtml()` | 318 | 76 | `htmlspecialchars()` or `Escaping::html()` | Pending |
| `processDBParam()`/`processSessParam()` | 45 | 6 | `InputValidator` / Request class | Pending |
| `repl_tab_nl()` | 1 | 1 | `str_replace()` inline | Pending |
| `getreq()` | 0 | 0 | `InputValidator::getString()` or `$this->param()` | **DONE** |
| `error_message_with_hide()` | 0 | 0 | Removed | **DONE** |
| `strToClassName()` | 4 | 1 | `StringUtils::toClassName()` | Delegating ✓ |
| `encodeURI()` | 1 | 1 | Keep as-is (matches JS `encodeURI()` behavior) | **Reviewed** |
| `getsess()` | 0 | 0 | Direct `$_SESSION` access | **DONE** |
| `get_sepas()` | 4 | 1 | `StringUtils::getSeparators()` | Delegating ✓ |
| `mask_term_in_sentence()` | 0 | 0 | `ExportService::maskTermInSentence()` | **DONE** |

**Total:** ~370 calls across ~80 files (down from ~550)

### Migration Priority

1. **Quick wins** (low call count, simple replacement):
   - ~~`getreq()`/`getsess()` → direct superglobal access~~ **DONE** (2025-11-29)
   - ~~`encodeURI()` → `rawurlencode()`~~ **Reviewed** - kept as-is, different behavior from `rawurlencode()`
   - ~~`mask_term_in_sentence()` → move to ExportService~~ **DONE** (2025-11-29)
   - ~~`strToClassName()` → `StringUtils::toClassName()`~~ **Delegating** (2025-11-30)
   - ~~`get_sepas()` → `StringUtils::getSeparators()`~~ **Delegating** (2025-11-30)
   - ~~`error_message_with_hide()` → removed~~ **DONE** (2025-11-30)

2. **Medium effort** (utility consolidation):
   - `repl_tab_nl()` → inline replacement (only 1 call remaining)
   - `processDBParam()`/`processSessParam()` → `InputValidator` migration

3. **Large effort** (widespread usage):
   - `tohtml()` → Keep as-is or create `Html::escape()` wrapper (318 calls)

## Inline JavaScript Migration

**Status (2025-11-30):** 39 PHP files contain inline `<script>` blocks that should be migrated to TypeScript modules.

### Files with Inline JS (by directory)

| Directory | Files | Notes |
|-----------|-------|-------|
| Views/Word/ | 16 | Result pages, forms |
| Views/Text/ | 7 | Read interface, forms |
| Views/Feed/ | 2 | Wizard steps |
| Views/Language/ | 4 | Forms, wizard |
| Views/Test/ | 3 | Test configuration |
| Views/Admin/ | 1 | TTS settings |
| Views/Mobile/ | 1 | Index |
| Views/Home/ | 1 | Index |
| Controllers/ | 1 | TranslationController |
| Services/ | 2 | MediaService, FeedService |
| Core/ | 1 | TextParsing |

### Migration Strategy

1. Extract JS to TypeScript modules in `src/frontend/js/`
2. Use Vite bundling and expose functions via `window` or custom events
3. Replace inline `<script>` blocks with data attributes + event listeners

## Timeline Update

| Phase | Original Estimate | Actual Status | Remaining |
|-------|-------------------|---------------|-----------|
| Quick Wins | 2 weeks | 95% complete | Session security |
| Phase 1 | 3-6 months | 50% complete | Security hardening |
| Phase 2 | 6-12 months | 85% complete | ~~Type hints~~ DI |
| Phase 3 | 12-18 months | 60% complete | Database, exceptions |

**Original Total Duration:** 18-24 months
**Elapsed Time:** ~12 months (estimated based on architecture changes)
**Remaining Effort:** ~300 hours for P0/P1 items

## Architecture Summary (2025-11-30)

| Component | Count | Lines |
|-----------|-------|-------|
| Controllers | 14 | 8,223 |
| Services | 36 | 18,571 |
| Views | 92 | 8,499 |
| Core Files | 31 | - |
| API Handlers | 10 | - |
| Entity Classes | 4 | - |
| **Total PHP Files** | **194** | - |

**Namespace Adoption:**
- 100% of files declare `Lwt\` namespace
- 99 files actively import `Lwt\` classes
- 100% strict_types declaration

---

## Phase 4: Future-Proof Architecture (Modular Monolith)

**Priority:** P2 (Medium)
**Status:** PLANNED
**Effort:** X-Large (400+ hours)
**Target:** Post-Phase 3 completion

### Rationale

The current MVC structure (Controllers → Services → Database) is solid but has scaling limitations:

1. **36 services in a flat directory** - Hard to navigate as complexity grows
2. **Mixed concerns** - Services often span multiple domains
3. **Tight coupling** - Direct database access in services makes testing/swapping difficult
4. **Frontend/backend mismatch** - No clear mapping between TypeScript modules and PHP services

The recommended architecture combines three production-proven patterns:

- **Modular Monolith** - Self-contained feature modules (bounded contexts)
- **Vertical Slices** - Everything for a feature in one place
- **Hexagonal Architecture** - Domain logic isolated from infrastructure

### Target Structure

```text
src/
├── Shared/                              # Cross-cutting infrastructure
│   ├── Infrastructure/
│   │   ├── Database/                    # Connection, QueryBuilder, DB facade
│   │   │   ├── Connection.php
│   │   │   ├── DB.php
│   │   │   ├── QueryBuilder.php
│   │   │   ├── PreparedStatement.php
│   │   │   └── Escaping.php
│   │   ├── Http/                        # Request/Response, InputValidator
│   │   │   ├── InputValidator.php
│   │   │   └── Request.php
│   │   └── Container/                   # DI container
│   │       ├── Container.php
│   │       └── ServiceProviderInterface.php
│   ├── Domain/
│   │   └── ValueObjects/                # Shared value objects
│   │       ├── TextId.php
│   │       ├── TermId.php
│   │       ├── LanguageId.php
│   │       └── TermStatus.php
│   └── UI/
│       ├── Helpers/                     # PageLayoutHelper, FormHelper, etc.
│       └── Assets/                      # ViteHelper, asset management
│
├── Modules/                             # Feature modules (bounded contexts)
│   │
│   ├── Text/                            # TEXT MODULE
│   │   ├── Domain/                      # Business logic (framework-agnostic)
│   │   │   ├── Text.php                 # Entity with behavior
│   │   │   ├── TextRepositoryInterface.php  # Port (interface)
│   │   │   └── TextStatus.php           # Value object
│   │   ├── Application/                 # Use cases (orchestration)
│   │   │   ├── ImportText.php           # Single-purpose use case
│   │   │   ├── ArchiveText.php
│   │   │   ├── ParseText.php
│   │   │   └── GetTextForReading.php
│   │   ├── Infrastructure/              # Adapters (implementations)
│   │   │   ├── MySqlTextRepository.php  # Implements TextRepositoryInterface
│   │   │   └── TextParsingAdapter.php
│   │   ├── Http/                        # Controllers & API handlers
│   │   │   ├── TextController.php
│   │   │   └── TextApiHandler.php
│   │   ├── Views/                       # Templates for this module
│   │   │   ├── read_desktop.php
│   │   │   ├── edit_form.php
│   │   │   └── import_form.php
│   │   └── TextServiceProvider.php      # Module DI registration
│   │
│   ├── Vocabulary/                      # VOCABULARY MODULE (Words/Terms)
│   │   ├── Domain/
│   │   │   ├── Term.php
│   │   │   ├── TermRepositoryInterface.php
│   │   │   ├── TermStatus.php
│   │   │   └── Expression.php
│   │   ├── Application/
│   │   │   ├── SaveTerm.php
│   │   │   ├── UpdateTermStatus.php
│   │   │   ├── BulkImportTerms.php
│   │   │   └── FindSimilarTerms.php
│   │   ├── Infrastructure/
│   │   │   └── MySqlTermRepository.php
│   │   ├── Http/
│   │   │   ├── WordController.php
│   │   │   └── TermApiHandler.php
│   │   ├── Views/
│   │   │   ├── list.php
│   │   │   ├── edit_form.php
│   │   │   └── upload_form.php
│   │   └── VocabularyServiceProvider.php
│   │
│   ├── Language/                        # LANGUAGE MODULE
│   │   ├── Domain/
│   │   │   ├── Language.php
│   │   │   ├── LanguageRepositoryInterface.php
│   │   │   └── ParsingRules.php
│   │   ├── Application/
│   │   │   ├── CreateLanguage.php
│   │   │   ├── GetLanguageSettings.php
│   │   │   └── ConfigureParser.php
│   │   ├── Infrastructure/
│   │   │   ├── MySqlLanguageRepository.php
│   │   │   └── MeCabAdapter.php
│   │   ├── Http/
│   │   │   ├── LanguageController.php
│   │   │   └── LanguageApiHandler.php
│   │   ├── Views/
│   │   └── LanguageServiceProvider.php
│   │
│   ├── Review/                          # REVIEW/TESTING MODULE
│   │   ├── Domain/
│   │   │   ├── ReviewSession.php
│   │   │   └── TestConfiguration.php
│   │   ├── Application/
│   │   │   ├── StartReviewSession.php
│   │   │   ├── SubmitAnswer.php
│   │   │   └── GetNextTerm.php
│   │   ├── Infrastructure/
│   │   ├── Http/
│   │   │   ├── TestController.php
│   │   │   └── ReviewApiHandler.php
│   │   ├── Views/
│   │   └── ReviewServiceProvider.php
│   │
│   ├── Feed/                            # RSS FEED MODULE
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   ├── Http/
│   │   ├── Views/
│   │   └── FeedServiceProvider.php
│   │
│   ├── Admin/                           # ADMIN MODULE
│   │   ├── Domain/
│   │   ├── Application/
│   │   │   ├── BackupDatabase.php
│   │   │   ├── RestoreDatabase.php
│   │   │   ├── UpdateSettings.php
│   │   │   └── GetStatistics.php
│   │   ├── Infrastructure/
│   │   ├── Http/
│   │   │   ├── AdminController.php
│   │   │   └── SettingsApiHandler.php
│   │   ├── Views/
│   │   └── AdminServiceProvider.php
│   │
│   └── Tags/                            # TAGS MODULE
│       ├── Domain/
│       ├── Application/
│       ├── Infrastructure/
│       ├── Http/
│       ├── Views/
│       └── TagsServiceProvider.php
│
├── Frontend/                            # TypeScript/CSS (mirrors modules)
│   ├── shared/
│   │   ├── api/                         # API client, shared types
│   │   │   ├── client.ts
│   │   │   └── types.ts
│   │   ├── components/                  # Reusable UI components
│   │   │   ├── Modal.ts
│   │   │   └── SortableTable.ts
│   │   ├── stores/                      # Shared state
│   │   └── utils/                       # Utilities
│   │
│   ├── modules/
│   │   ├── text/
│   │   │   ├── components/
│   │   │   │   ├── TextReader.ts
│   │   │   │   └── WordModal.ts
│   │   │   ├── stores/
│   │   │   │   └── wordStore.ts
│   │   │   ├── api.ts
│   │   │   └── index.ts
│   │   ├── vocabulary/
│   │   │   ├── components/
│   │   │   ├── stores/
│   │   │   └── index.ts
│   │   ├── review/
│   │   │   ├── components/
│   │   │   ├── stores/
│   │   │   └── index.ts
│   │   ├── feed/
│   │   ├── admin/
│   │   └── language/
│   │
│   ├── styles/
│   │   ├── base/
│   │   └── themes/
│   │
│   └── main.ts                          # Entry point
│
├── Api/                                 # Thin API layer (delegates to modules)
│   └── V1/
│       ├── ApiV1.php
│       └── Routes.php
│
└── Legacy/                              # Temporary home for migrating code
    └── (empty when migration complete)
```

### Key Architectural Principles

#### 1. Module Independence

Each module in `Modules/` is a **bounded context** that:

- Owns its domain logic, use cases, database access, and views
- Has a single `ServiceProvider` for DI registration
- Can be extracted to a microservice if needed
- Communicates with other modules via well-defined interfaces

```php
// Module structure follows Hexagonal Architecture
Modules/Text/
├── Domain/           # Pure business logic, NO framework dependencies
├── Application/      # Use cases that orchestrate domain objects
├── Infrastructure/   # Adapters: database, external APIs
├── Http/             # Controllers (thin, delegates to Application)
└── Views/            # Templates
```

#### 2. Dependency Rule

Dependencies point **inward** only:

```text
Http → Application → Domain ← Infrastructure
         ↓              ↓
    (uses interfaces defined in Domain)
```

- `Domain/` knows nothing about databases, HTTP, or frameworks
- `Application/` orchestrates domain objects, defines use case boundaries
- `Infrastructure/` implements domain interfaces (repositories, adapters)
- `Http/` handles HTTP concerns, delegates to Application layer

#### 3. Use Cases Replace Fat Services

Instead of:
```php
// Current: Large service with many responsibilities
class TextService {
    public function importText(...) { /* 200 lines */ }
    public function archiveText(...) { /* 100 lines */ }
    public function parseText(...) { /* 150 lines */ }
    public function getForReading(...) { /* 80 lines */ }
}
```

Use single-purpose use cases:
```php
// Future: Each use case is a single class
class ImportText {
    public function __construct(
        private TextRepositoryInterface $texts,
        private TextParser $parser
    ) {}

    public function execute(ImportTextRequest $request): Text {
        // Single responsibility: import a text
    }
}
```

#### 4. Repository Interfaces in Domain

```php
// Domain/TextRepositoryInterface.php - Port (interface)
interface TextRepositoryInterface {
    public function find(TextId $id): ?Text;
    public function save(Text $text): void;
    public function delete(TextId $id): void;
    public function findByLanguage(LanguageId $langId): array;
}

// Infrastructure/MySqlTextRepository.php - Adapter (implementation)
class MySqlTextRepository implements TextRepositoryInterface {
    public function find(TextId $id): ?Text {
        // MySQL-specific implementation
    }
}
```

#### 5. Frontend Mirrors Backend

```text
Backend:  Modules/Text/
Frontend: modules/text/
```

Each frontend module contains:
- `components/` - UI components for this feature
- `stores/` - State management (Alpine.js stores)
- `api.ts` - API client for this module's endpoints
- `index.ts` - Module entry point

### Migration Path

#### Stage 1: Create Shared Infrastructure (40 hours)

Move cross-cutting code to `Shared/`:

| Current Location | Target Location |
|------------------|-----------------|
| `Core/Database/*` | `Shared/Infrastructure/Database/` |
| `Core/Http/*` | `Shared/Infrastructure/Http/` |
| `Core/Container/*` | `Shared/Infrastructure/Container/` |
| `Core/Entity/ValueObject/*` | `Shared/Domain/ValueObjects/` |
| `View/Helper/*` | `Shared/UI/Helpers/` |

#### Stage 2: Pilot Module - Text (80 hours)

Migrate the Text feature as a proof of concept:

1. Create `Modules/Text/` directory structure
2. Extract `Text` entity to `Modules/Text/Domain/Text.php`
3. Define `TextRepositoryInterface` in Domain
4. Create use cases from `TextService` methods:
   - `ImportText`, `ArchiveText`, `ParseText`, `GetTextForReading`
5. Implement `MySqlTextRepository` in Infrastructure
6. Move `TextController` to `Modules/Text/Http/`
7. Move text views to `Modules/Text/Views/`
8. Create `TextServiceProvider` for DI registration
9. Update frontend: move `js/reading/` to `Frontend/modules/text/`

#### Stage 3: Remaining Modules (200 hours)

Apply the same pattern to other modules:

| Module | Current Services | Estimated Effort |
|--------|------------------|------------------|
| Vocabulary | WordService, WordStatusService, WordListService, DictionaryService, SimilarTermsService | 60 hours |
| Language | LanguageService, LanguageDefinitions | 30 hours |
| Review | TestService | 30 hours |
| Feed | FeedService | 25 hours |
| Admin | SettingsService, BackupService, StatisticsService, DatabaseWizardService | 35 hours |
| Tags | TagService | 20 hours |

#### Stage 4: Remove Legacy (40 hours)

1. Delete empty `Legacy/` directory
2. Update all imports to use new module paths
3. Update autoloading in `composer.json`
4. Update routing to point to new controller locations

### Benefits

| Benefit | Description |
|---------|-------------|
| **Easier navigation** | "Everything about texts is in `Modules/Text/`" |
| **Framework independence** | Domain layer survives framework migrations |
| **Microservice-ready** | Each module can be extracted when needed |
| **Better testing** | Domain logic testable without database |
| **Parallel development** | Teams can own entire modules |
| **Reduced coupling** | Changes in one module don't ripple to others |
| **Frontend/backend alignment** | Same mental model for both |

### Risks and Mitigations

| Risk | Mitigation |
|------|------------|
| Over-engineering | Start with pilot module, evaluate before proceeding |
| Breaking changes | Maintain backward-compatible routes during migration |
| Learning curve | Document patterns, provide examples |
| Migration effort | Gradual migration, one module at a time |

### Success Criteria

- [ ] All 7 modules migrated to new structure
- [ ] Zero circular dependencies between modules
- [ ] Domain layer has 100% unit test coverage
- [ ] Frontend modules mirror backend structure
- [ ] `Legacy/` directory is empty and removed
- [ ] Documentation updated with new patterns

### Namespace Updates

```json
{
    "autoload": {
        "psr-4": {
            "Lwt\\Shared\\": "src/Shared/",
            "Lwt\\Modules\\": "src/Modules/",
            "Lwt\\Api\\": "src/Api/"
        }
    }
}
```

---

**Document Owner:** LWT Maintainers
**Review Cycle:** Quarterly
**Last Review:** 2025-12-02
**Next Review:** 2026-03-02

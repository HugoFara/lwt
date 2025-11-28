# LWT Modernization Plan

**Last Updated:** 2025-11-28
**Current Version:** 3.0.0-fork
**Target PHP Version:** 8.1-8.4

## Executive Summary

LWT carried significant technical debt from its 2007 origins. This document tracks the modernization progress and remaining work. **Major architectural transformation has been achieved** with the v3 release.

**Overall Technical Debt Score:** 3.5/10 (Low to Moderate) - *Down from 6.75/10*

## Progress Overview

| Phase | Status | Completion |
|-------|--------|------------|
| Quick Wins | **COMPLETE** | 100% |
| Phase 1: Security & Safety | **PARTIAL** | ~40% |
| Phase 2: Refactoring | **SUBSTANTIAL** | ~75% |
| Phase 3: Modernization | **IN PROGRESS** | ~50% |

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

**New Architecture:**

```text
src/backend/
├── Api/V1/                    # REST API (NEW)
│   └── Handlers/              # 10+ API handlers
├── Controllers/               # 14 controllers (7,987 lines total)
│   ├── BaseController.php     # Abstract base (317 lines)
│   ├── TextController.php     # (1,060 lines)
│   ├── WordController.php     # (1,921 lines)
│   └── ...
├── Core/                      # 57 files in 17 subdirectories
│   ├── Bootstrap/             # App initialization
│   ├── Database/              # DB classes (Connection, DB, QueryBuilder, Escaping)
│   ├── Entity/                # Data models (Language, Text, Term)
│   ├── Http/                  # Parameter handling
│   ├── Text/                  # Text processing
│   ├── Word/                  # Word operations
│   └── ...
├── Router/                    # URL routing system (NEW)
├── Services/                  # 22 services (11,367 lines total)
│   ├── TextService.php        # (1,566 lines)
│   ├── WordService.php        # (1,537 lines)
│   └── ...
└── Views/                     # Template files
```

**File Size Distribution:**

- Largest service: TextService.php (1,566 lines) - down from 4,290
- Largest controller: WordController.php (1,921 lines)
- Most files under 500 lines

### 3. Input Validation (HIGH - Security)

**Original Issues:**

- Direct `$_REQUEST`/`$_GET`/`$_POST` access throughout codebase
- Inconsistent type casting
- No length validation against database constraints

**Current State (2025-11-28):** PARTIAL

- [x] `BaseController` provides `param()`, `get()`, `post()` abstraction
- [x] Type casting used consistently (`(int)`, `(string)`)
- [x] `Validation` class for database ID existence checks
- [ ] **NOT IMPLEMENTED**: Centralized `InputValidator` class
- [ ] Direct superglobal access remains in 16 files (~305 occurrences)

**Current Pattern:**

```php
// BaseController provides access abstraction (but not validation)
protected function param(string $key, mixed $default = null): mixed {
    return $_REQUEST[$key] ?? $default;
}

// Type casting in controllers
$textId = (int)$_REQUEST['text'];
```

**Remaining Work:**

- [ ] Create `InputValidator` service class
- [ ] Replace all 305 direct `$_REQUEST` accesses
- [ ] Add length validation for string inputs

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
**Status:** NOT STARTED
**Effort:** Medium (60 hours)

**Proposed Implementation:**

```php
// Create src/backend/Services/InputValidator.php
class InputValidator {
    public static function getInt(string $key, ?int $default = null): ?int
    public static function getString(string $key, int $maxLength, ?string $default = null): ?string
    public static function getArray(string $key, ?array $default = null): ?array
    public static function getLanguageId(string $key): int
    public static function getTextId(string $key): int
}
```

**Current Workaround:** `BaseController::param()` + manual type casting

**Success Criteria:**

- [ ] Zero direct `$_REQUEST`/`$_GET`/`$_POST` access in codebase
- [ ] All inputs validated before use

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

- [ ] 42 DELETE queries still use direct SQL
- [ ] Services don't consistently use QueryBuilder
- [ ] No `AbstractCrudController` (chose Service pattern instead)

#### 2.3 Add Type Hints

**Priority:** P2 (Medium)
**Status:** SUBSTANTIAL PROGRESS
**Effort:** Large (120 hours) - ONGOING

**Current Coverage:**

- Modern code (Controllers, Services): ~90% type coverage
- Legacy utility functions: ~50% type coverage
- Entity classes: Minimal (use docblocks)
- `strict_types`: 0 files declare it

**Psalm Configuration:**

- Level 4 (strictest) configured
- Well-tuned suppressions for legacy code
- Running in CI pipeline

**Remaining Work:**

- [ ] Add `declare(strict_types=1)` to all files
- [ ] Convert Entity docblocks to native property types
- [ ] Achieve Psalm level 1 compliance

### Phase 3: Modernization

#### 3.1 OOP Architecture

**Priority:** P2 (Medium)
**Status:** SUBSTANTIAL PROGRESS
**Effort:** X-Large (400+ hours) - ONGOING

**Achievements:**

- [x] Service Layer Pattern implemented (22 services)
- [x] Controller pattern implemented (14 controllers)
- [x] Entity classes exist (4 classes)
- [x] `Globals` class for configuration access
- [ ] Repository Pattern NOT implemented
- [ ] Dependency Injection container NOT implemented
- [ ] Factory Pattern NOT implemented

**Current Dependency Pattern:**

```php
// Manual dependency via static classes (current)
class LanguageService {
    public function getAllLanguages(): array {
        $sql = "SELECT * FROM " . Globals::table('languages');
        return Connection::query($sql);
    }
}

// DI container pattern (not implemented)
class LanguageService {
    public function __construct(private Connection $db) {}
}
```

**Remaining Work:**

- [ ] Implement Repository layer for database abstraction
- [ ] Add PSR-11 DI container
- [ ] Reduce static method usage

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

**Adoption Rate:**

- 39 of 190 PHP files use `Lwt\` namespace (~20%)
- Controllers, Services, Database classes: 100%
- Core utility functions: 0% (procedural)

**Remaining Work:**

- [ ] Migrate remaining procedural files to namespaced classes
- [ ] Achieve 90%+ namespace adoption

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

**Status:** NOT DONE - `BaseController::param()` is workaround

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
- [ ] Type coverage: 100% (currently ~70%)
- [ ] Test coverage: 60%+ (80 test files exist)

### Phase 3 Completion

- [x] OOP code: 80%+ (achieved via MVC)
- [ ] Database: 100% InnoDB with foreign keys
- [ ] Psalm level: 1 (currently level 4 with suppressions)
- [ ] DI container in use

## Remaining High-Priority Work

### P0 (Critical - Security)

1. **Prepared Statements** - Migrate from string escaping
2. **Session Security** - Add cookie flags (HttpOnly, Secure, SameSite)
3. **Security Headers** - Add CSP, X-Frame-Options, etc.

### P1 (High)

1. **InputValidator** - Centralized input validation
2. **Exception Handling** - Custom exception hierarchy
3. **Database Migration** - MyISAM to InnoDB

### P2 (Medium)

1. **DI Container** - Replace static dependencies
2. **Repository Layer** - Abstract database access
3. **Type Hints** - Complete coverage + strict_types
4. **QueryBuilder Adoption** - Reduce direct SQL

## Timeline Update

| Phase | Original Estimate | Actual Status | Remaining |
|-------|-------------------|---------------|-----------|
| Quick Wins | 2 weeks | 80% complete | Session security |
| Phase 1 | 3-6 months | 40% complete | Security hardening |
| Phase 2 | 6-12 months | 75% complete | Type hints, DI |
| Phase 3 | 12-18 months | 50% complete | Database, exceptions |

**Original Total Duration:** 18-24 months
**Elapsed Time:** ~12 months (estimated based on architecture changes)
**Remaining Effort:** ~400 hours for P0/P1 items

---

**Document Owner:** LWT Maintainers
**Review Cycle:** Quarterly
**Last Review:** 2025-11-28
**Next Review:** 2026-02-28

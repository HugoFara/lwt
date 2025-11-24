# LWT Modernization Plan

**Last Updated:** 2025-11-24
**Current Version:** 2.10.0-fork
**Target PHP Version:** 8.1-8.4

## Executive Summary

LWT carries significant technical debt from its 2007 origins. This document outlines a phased approach to modernize the codebase while maintaining stability and backward compatibility.

**Overall Technical Debt Score:** 6.75/10 (Moderate to High)

## Critical Issues

### 1. SQL Injection Vulnerabilities (CRITICAL - Security)

**Current State:**

- All database queries use string concatenation with `mysqli_real_escape_string()`
- 63 root PHP files + 15 AJAX endpoints affected
- Pattern: `'UPDATE table SET col=' . convert_string_to_sqlsyntax($_REQUEST['val'])`

**Risk:** Externally exploitable security vulnerability

**Target:** Migrate to prepared statements with parameterized queries

### 2. Monolithic File Structure (CRITICAL - Maintainability)

**Problem Files:**

- `inc/session_utility.php` - 4,290 lines, 80+ functions
- `inc/database_connect.php` - 2,182 lines, 46+ functions
- `edit_languages.php` - 1,528 lines
- `edit_texts.php` - 1,344 lines

**Impact:** Impossible to test, understand, or maintain

### 3. Input Validation (HIGH - Security)

**Issues:**

- Direct `$_REQUEST`/`$_GET`/`$_POST` access throughout codebase
- Inconsistent type casting
- No length validation against database constraints
- Example: `$texttags = json_encode($_REQUEST["TextTags"]);` with no validation

### 4. Code Duplication (HIGH - Maintainability)

**Patterns:**

- 20+ `get_*_selectoptions()` functions with identical structure
- CRUD operations duplicated across files
- Delete query patterns repeated 10+ times
- Form validation code copied everywhere

## Phased Modernization Roadmap

### Phase 1: Security & Safety (3-6 months)

#### 1.1 Prepared Statements Migration

**Priority:** P0 (Critical)
**Effort:** Large (200+ hours)

**Steps:**

1. Create `DatabaseService` class with prepared statement wrappers
2. Add methods: `query()`, `insert()`, `update()`, `delete()`, `select()`
3. Gradually replace `do_mysqli_query()` calls (prioritize user input paths first)
4. Add integration tests for each converted query

**Success Criteria:**

- 100% of queries use prepared statements
- All tests pass
- SQL injection scan shows no vulnerabilities

#### 1.2 Input Validation Layer

**Priority:** P0 (Critical)
**Effort:** Medium (60 hours)

**Implementation:**

```php
// Create src/Services/InputValidator.php
class InputValidator {
    public static function getInt(string $key, ?int $default = null): ?int
    public static function getString(string $key, int $maxLength, ?string $default = null): ?string
    public static function getArray(string $key, ?array $default = null): ?array
    public static function getLanguageId(string $key): int
    public static function getTextId(string $key): int
}
```

**Replace all direct `$_REQUEST` access with validation layer**

**Success Criteria:**

- Zero direct `$_REQUEST`/`$_GET`/`$_POST` access in codebase
- All inputs validated before use

#### 1.3 Session Security Hardening

**Priority:** P1 (High)
**Effort:** Small (8 hours)

**Changes to `inc/start_session.php`:**

```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

**Success Criteria:**

- Session cookies have HttpOnly, Secure, SameSite flags
- Sessions expire after 1 hour of inactivity
- No `@` error suppression operators

#### 1.4 XSS Prevention Audit

**Priority:** P1 (High)
**Effort:** Medium (40 hours)

**Tasks:**

1. Audit all `echo`, `print`, output contexts
2. Ensure `tohtml()` wrapper used consistently
3. Add Content-Security-Policy headers
4. Review JavaScript injection points

**Success Criteria:**

- All user-generated content escaped before output
- CSP headers configured
- XSS scanner shows no vulnerabilities

### Phase 2: Refactoring (6-12 months)

#### 2.1 Break Up Monolithic Files

**Priority:** P1 (High)
**Effort:** X-Large (300+ hours)

**Target Structure:**

```text
src/
├── Services/
│   ├── TextService.php          (from session_utility.php)
│   ├── WordService.php          (from session_utility.php)
│   ├── MediaService.php         (from session_utility.php)
│   ├── NavigationService.php    (from session_utility.php)
│   ├── ExportService.php        (from session_utility.php)
│   ├── FormGeneratorService.php (from session_utility.php)
│   ├── DatabaseService.php      (from database_connect.php)
│   ├── QueryService.php         (from database_connect.php)
│   └── ValidationService.php    (new)
├── Repositories/
│   ├── TextRepository.php
│   ├── WordRepository.php
│   ├── LanguageRepository.php
│   └── TagRepository.php
└── Models/
    ├── Text.php (enhance existing)
    ├── Word.php (rename from Term.php)
    ├── Language.php (enhance existing)
    └── Tag.php (new)
```

**Approach:**

1. Create service classes with static methods initially (maintain compatibility)
2. Move 5-10 functions per week
3. Update callers incrementally
4. Add unit tests for each service
5. Refactor to instance methods once all code migrated

**Success Criteria:**

- No file exceeds 500 lines
- Each class has single responsibility
- 80%+ test coverage on new services

#### 2.2 Eliminate Code Duplication

**Priority:** P2 (Medium)
**Effort:** Medium (60 hours)

**Targets:**

1. **Select Options Generator** - Create `FormHelper::selectOptions($data, $selectedId)`
2. **CRUD Pattern** - Create `AbstractCrudController` base class
3. **Query Builder** - Add fluent query builder to `DatabaseService`

**Success Criteria:**

- 50%+ reduction in duplicated code blocks
- Code complexity metrics improve

#### 2.3 Add Type Hints

**Priority:** P2 (Medium)
**Effort:** Large (120 hours)

**Coverage Targets:**

- **Current:** 41% of functions have return types
- **Phase 2 Target:** 100% of functions have full type coverage

**Process:**

1. Run Psalm at level 4
2. Add parameter types to all function signatures
3. Add return types to all functions
4. Add property types to all classes
5. Enable strict_types in all files

**Success Criteria:**

- Psalm level 1 compliance
- Zero type-related warnings in IDE

### Phase 3: Modernization (12-18 months)

#### 3.1 OOP Architecture

**Priority:** P2 (Medium)
**Effort:** X-Large (400+ hours)

**Design Patterns to Implement:**

1. **Repository Pattern** - Abstract database access
2. **Service Layer Pattern** - Business logic separation
3. **Factory Pattern** - Object creation
4. **Dependency Injection** - Remove global variables

**Migration Strategy:**

- Root PHP files become controllers
- Business logic moves to services
- Database access moves to repositories
- Views separated to templates (future: Twig/Blade)

**Success Criteria:**

- 80%+ code in classes (vs. procedural)
- Dependency injection container in use
- Zero global variables except config

#### 3.2 Database Modernization

**Priority:** P2 (Medium)
**Effort:** Medium (80 hours)

**Changes:**

1. **Engine Migration** - MyISAM → InnoDB
2. **Add Foreign Keys:**

   ```sql
   ALTER TABLE texts ADD CONSTRAINT fk_texts_language
       FOREIGN KEY (TxLgID) REFERENCES languages(LgID);
   ALTER TABLE words ADD CONSTRAINT fk_words_language
       FOREIGN KEY (WoLgID) REFERENCES languages(LgID);
   ```

3. **Add Composite Indexes:**

   ```sql
   ALTER TABLE words ADD INDEX idx_lang_status (WoLgID, WoStatus);
   ALTER TABLE texts ADD INDEX idx_lang_title (TxLgID, TxTitle(50));
   ```

4. **Enable Transactions** - Wrap multi-query operations

**Migration Plan:**

1. Create migration script
2. Test on copy of production data
3. Document rollback procedure
4. Execute during maintenance window

**Success Criteria:**

- All tables use InnoDB
- Foreign key constraints enforced
- Query performance maintained or improved

#### 3.3 PSR-4 Autoloading

**Priority:** P3 (Low)
**Effort:** Small (16 hours)

**Implementation:**

```json
// composer.json
{
    "autoload": {
        "psr-4": {
            "Lwt\\": "src/"
        }
    }
}
```

**Replace:**

```php
// Old
require_once __DIR__ . '/inc/session_utility.php';

// New
use Lwt\Services\TextService;
// Autoloaded via composer
```

**Success Criteria:**

- Zero `require_once` statements for classes
- PSR-4 compliant namespace structure

#### 3.4 Exception Handling

**Priority:** P3 (Low)
**Effort:** Medium (60 hours)

**Replace:**

```php
// Old
if ($res == false) {
    echo "Error...";
    die();
}

// New
try {
    $service->doOperation();
} catch (DatabaseException $e) {
    $logger->error($e);
    return errorResponse($e->getMessage());
}
```

**Custom Exceptions:**

- `LwtException` (base)
- `DatabaseException`
- `ValidationException`
- `NotFoundException`
- `AuthenticationException`

**Success Criteria:**

- No `die()` or `exit()` calls (except entry points)
- All errors logged
- User-friendly error pages

## Quick Wins (Immediate - 0-2 weeks)

### QW1: Add Composer Autoload Configuration

**Effort:** 1 hour

```bash
mkdir -p src/Services src/Repositories src/Models
# Update composer.json with PSR-4 autoload
composer dump-autoload
```

### QW2: Configure Session Security

**Effort:** 2 hours

- Modify `inc/start_session.php` with security settings
- Test on development environment

### QW3: Create InputValidator Class

**Effort:** 8 hours

- Create `src/Services/InputValidator.php`
- Write unit tests
- Document usage

### QW4: Add CI Pipeline for Code Quality

**Effort:** 4 hours

```yaml
# .github/workflows/quality.yml
- name: Run Psalm
  run: ./vendor/bin/psalm --show-info=false
- name: Run PHPUnit
  run: composer test
- name: Run PHPCS
  run: php ./vendor/bin/squizlabs/phpcs.phar --standard=PSR12 src/
```

### QW5: Add Database Service Skeleton

**Effort:** 8 hours

- Create `src/Services/DatabaseService.php` with prepared statement wrappers
- Document API
- Add to one file as proof of concept

## Success Metrics

### Phase 1 Completion

- [ ] Zero SQL injection vulnerabilities (RIPS/SonarQube scan)
- [ ] Zero direct `$_REQUEST` access
- [ ] Session security audit passed
- [ ] XSS vulnerabilities: 0 (OWASP ZAP scan)

### Phase 2 Completion

- [ ] Average file size < 500 lines
- [ ] Code duplication < 5% (phpmd)
- [ ] Type coverage: 100%
- [ ] Test coverage: 60%+

### Phase 3 Completion

- [ ] OOP code: 80%+
- [ ] Database: 100% InnoDB with foreign keys
- [ ] Psalm level: 1
- [ ] Response time improvement: 10%+

## Risk Mitigation

### Technical Risks

1. **Breaking Changes** - Mitigate with:
   - Comprehensive test suite before refactoring
   - Incremental changes with feature flags
   - Maintain backward compatibility layer

2. **Performance Regression** - Mitigate with:
   - Benchmark before/after each phase
   - Profile queries with slow query log
   - Load testing on production-sized data

3. **Database Migration Issues** - Mitigate with:
   - Test on production data copy
   - Document rollback procedure
   - Schedule maintenance window
   - Monitor replication lag

### Resource Risks

1. **Limited Developer Time** - Mitigate with:
   - Focus on P0/P1 items only
   - Spread work over 18 months
   - Accept community contributions

2. **Testing Burden** - Mitigate with:
   - Automate testing in CI
   - Maintain manual test checklist
   - Beta testing period before releases

## Implementation Guidelines

### Code Review Checklist

For all new code:

- [ ] Uses prepared statements (no string concatenation)
- [ ] All inputs validated
- [ ] Full type hints (parameters + return)
- [ ] No code duplication
- [ ] Unit tests included
- [ ] PSR-12 compliant
- [ ] No global variables
- [ ] Error handling with exceptions

### Commit Message Format

```text
type(scope): brief description

- Details of changes
- Reference to issue: #123

Phase: [1|2|3|QW]
Priority: [P0|P1|P2|P3]
```

### Branch Strategy

- `master` - Stable releases only
- `dev` - Active development (merge target)
- `security/*` - Security fixes (Phase 1 work)
- `refactor/*` - Refactoring work (Phase 2)
- `modernization/*` - Architecture changes (Phase 3)

## Resources Required

### Development Time (Estimates)

- **Phase 1:** 308 hours (7.7 weeks full-time)
- **Phase 2:** 480 hours (12 weeks full-time)
- **Phase 3:** 556 hours (13.9 weeks full-time)
- **Total:** 1,344 hours (33.6 weeks full-time)

### Tools Needed

- Static Analysis: Psalm (already installed)
- Code Style: PHP_CodeSniffer (already installed)
- Security Scanning: RIPS/SonarQube/Snyk
- Performance: Xdebug profiler, MySQL slow query log
- Testing: PHPUnit (already installed)

### Documentation Updates

Each phase requires:

- Update `CLAUDE.md` with new architecture
- Update `docs/contribute.md` with new patterns
- Add migration guides for breaking changes
- Update API documentation

## Timeline Summary

| Phase | Duration | Effort | Priority | Start Date |
|-------|----------|--------|----------|------------|
| Quick Wins | 2 weeks | 23 hours | Immediate | 2025-11 |
| Phase 1 | 3-6 months | 308 hours | Critical | 2025-12 |
| Phase 2 | 6-12 months | 480 hours | High | 2026-Q2 |
| Phase 3 | 12-18 months | 556 hours | Medium | 2026-Q4 |

**Total Duration:** 18-24 months
**Total Effort:** 1,344 hours

## Next Steps

1. **Immediate (This Week):**
   - Review and approve this plan
   - Execute Quick Wins (QW1-QW5)
   - Set up security scanning tools

2. **Month 1:**
   - Begin Phase 1.1 (Prepared Statements)
   - Start with highest-risk files (edit_*.php, api.php)
   - Create InputValidator class (Phase 1.2)

3. **Month 2-3:**
   - Complete critical security fixes
   - Run first security audit
   - Document new patterns in CLAUDE.md

4. **Quarterly Reviews:**
   - Assess progress against metrics
   - Adjust priorities based on findings
   - Update timeline if needed

## Approval Sign-off

- [ ] Plan Reviewed
- [ ] Budget Approved
- [ ] Timeline Accepted
- [ ] Risk Assessment Completed
- [ ] Begin Implementation

---

**Document Owner:** LWT Maintainers
**Review Cycle:** Quarterly
**Next Review:** 2026-02-24

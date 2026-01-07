# Security Audit Report - LWT v3

**Date:** 2025-12-12
**Last Updated:** 2026-01-07
**Branch:** dev
**Status:** P0 issues resolved, ready for production hardening

This document tracks security issues identified during the pre-release audit of LWT v3.

---

## Summary

| Severity | Total | Fixed | Open |
|----------|-------|-------|------|
| Critical | 8 | 8 | 0 |
| High | 13 | 11 | 2 |
| Medium | 10 | 7 | 3 |

---

## Critical Issues

### 1. No Authentication System - FIXED

**Status:** Fixed

**Resolution:**
- Authentication infrastructure implemented via `AuthService` and `AuthMiddleware`
- All routes now protected with `AUTH_MIDDLEWARE` in `src/backend/Router/routes.php`
- Session-based authentication with secure cookie settings
- User module at `src/Modules/User/` handles registration, login, and session management

---

### 2. SQL Injection in IN() Clauses - FIXED

**Status:** Fixed

**Resolution:**
- Legacy services (`FeedService.php`, `TestService.php`, `TagService.php`) have been refactored
- New module architecture uses `QueryBuilder` with prepared statements throughout
- No vulnerable `implode()` + `IN()` patterns found in current codebase

---

### 3. XSS via $_SERVER['PHP_SELF'] - FIXED

**Status:** Fixed

**Resolution:**
All instances now use `htmlspecialchars($_SERVER['PHP_SELF'] ?? '', ENT_QUOTES, 'UTF-8')`:

| File | Status |
|------|--------|
| `src/backend/Views/Feed/browse.php` | Fixed |
| `src/backend/Views/Text/archived_form.php` | Fixed |
| `src/Modules/Feed/Views/browse.php` | Fixed |
| `src/Modules/Text/Views/archived_form.php` | Fixed |
| `src/Modules/Tags/Views/tag_form.php` | Fixed |
| `src/Modules/Vocabulary/Views/form_edit_term.php` | Fixed |
| `src/Modules/Vocabulary/Views/form_edit_existing.php` | Fixed |
| `src/Modules/Vocabulary/Views/form_edit_new.php` | Fixed |
| `src/Modules/Vocabulary/Views/form_new.php` | Fixed |

**Commit:** `23e7b20f`

---

### 4. XSS in Video Player - FIXED

**Status:** Fixed (code refactored)

**Resolution:**
The vulnerable `MediaService.php:280` no longer exists. Media handling has been refactored into the module architecture with proper output escaping.

---

### 5. Debug Mode Enabled by Default - FIXED

**Status:** Fixed

**Resolution:**
Debug mode is now environment-dependent in `src/backend/Application.php:100-110`:

```php
$debug = $this->isDebugMode();
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
}
```

---

### 16. XSS via addslashes() in Alpine.js Contexts - FIXED

**Status:** Fixed

**Resolution:**
Replaced all `addslashes()` with `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` in Alpine.js contexts:

| File | Status |
|------|--------|
| `src/backend/Views/Tags/tag_form.php` | Fixed |
| `src/backend/Views/Feed/browse.php` | Fixed |
| `src/backend/Views/Feed/index.php` | Fixed |
| `src/Modules/Text/Views/print_alpine.php` | Fixed |
| `src/Modules/Tags/Views/tag_form.php` | Fixed |
| `src/Modules/Feed/Views/browse.php` | Fixed |
| `src/Modules/Feed/Views/index.php` | Fixed |

---

### 17. SQL Injection in Feed Tag Processing - FIXED

**Status:** Fixed

**Resolution:**
Converted raw string concatenation to prepared statements with parameterized placeholders in `src/Modules/Feed/Application/FeedFacade.php`:

```php
// Before (VULNERABLE)
$NfTag = '"' . implode('","', $text['TagList']) . '"';
'WHERE T2Text IN (' . $NfTag . ')'

// After (SAFE)
$tagPlaceholders = implode(',', array_fill(0, count($currentTagList), '?'));
$tagBindings = array_values(array_merge([$id], $currentTagList));
Connection::preparedExecute(
    'SELECT ?, T2ID FROM tags2 WHERE T2Text IN (' . $tagPlaceholders . ')',
    $tagBindings
);
```

---

### 18. Default Admin User Without Password - FIXED

**Status:** Fixed

**Resolution:**
1. Removed default admin user from `db/schema/baseline.sql` for fresh installations
2. Updated migration `20251212_000001_add_users_table.sql` with security documentation

Fresh installations now require explicit admin account creation through the registration page or setup wizard.

For existing installations upgrading via migration: The migration creates a placeholder admin user with NULL password hash. This account **cannot log in** because:
- The Login use case rejects users with null password hashes
- Users must register a new account with proper credentials

---

## High Priority Issues

### 6. CSRF Token Validation - FIXED

**Status:** Fixed

**Resolution:**
Created comprehensive CSRF protection middleware:

1. **CsrfMiddleware** (`src/backend/Router/Middleware/CsrfMiddleware.php`):
   - Validates tokens on POST, PUT, DELETE, PATCH requests
   - Exempts GET/OPTIONS (safe methods)
   - Exempts API requests with Bearer tokens (token itself is CSRF protection)
   - Uses timing-safe comparison (`hash_equals`)
   - Provides clear error messages (JSON for API, HTML for web)

2. **Route Integration** (`src/backend/Router/routes.php`):
   - Added `CsrfMiddleware` to `AUTH_MIDDLEWARE` and `ADMIN_MIDDLEWARE` arrays
   - All protected routes now validate CSRF tokens

3. **Form Helpers** (`src/Shared/UI/Helpers/FormHelper.php`):
   - `FormHelper::csrfField()` - generates hidden input with token
   - `FormHelper::csrfToken()` - returns raw token for AJAX headers

Usage in forms:
```php
<form method="post">
    <?php echo FormHelper::csrfField(); ?>
    <!-- form fields -->
</form>
```

Usage in AJAX:
```javascript
fetch('/api/endpoint', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken }
});
```

---

### 7. No API Rate Limiting - OPEN

**Status:** Open (deferred to post-release)

**Risk:** DoS attacks, brute force, API abuse.

**Affected:** `/api/v1/*` endpoints

**Recommendation:**
Implement per-IP rate limiting via:
- Application middleware
- Reverse proxy (nginx `limit_req`, Apache `mod_ratelimit`)

**Priority:** Can be handled at infrastructure level for self-hosted deployments.

---

### 8. Session Security Not Hardened - FIXED

**Status:** Fixed

**Resolution:**
`SessionBootstrap.php` now configures secure session cookies:

```php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isSecure,     // HTTPS-only when available
    'httponly' => true,        // Prevent JavaScript access
    'samesite' => 'Lax'        // CSRF protection
]);
```

---

### 9. Unprotected Database Wizard - FIXED

**Status:** Fixed

**Resolution:**
Database wizard route now requires authentication:

```php
$router->registerWithMiddleware('/admin/wizard', '...', AUTH_MIDDLEWARE);
```

---

### 10. Missing Backend Directory Protection - FIXED

**Status:** Fixed

**Resolution:**
Added `src/backend/.htaccess`:

```apache
<FilesMatch "\.(php|inc)$">
    Require all denied
</FilesMatch>
```

**Commit:** `9c7f7f44`

---

### 11. Missing Security Headers - FIXED

**Status:** Fixed

**Resolution:**
`SecurityHeaders.php` sends comprehensive security headers:

- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `Content-Security-Policy` (script-src, style-src, img-src, etc.)
- `Strict-Transport-Security` (HTTPS only)
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` (disables camera, microphone, geolocation, etc.)

---

### 19. CSP Policy Too Permissive - PARTIALLY FIXED

**Status:** Partially Fixed

**Resolution:**
Removed `'unsafe-eval'` from Content Security Policy in `src/Shared/Infrastructure/Http/SecurityHeaders.php`:

```php
// Before
"script-src 'self' 'unsafe-inline' 'unsafe-eval'"

// After
"script-src 'self' 'unsafe-inline'"
```

**Remaining:** `'unsafe-inline'` still needed for legacy inline scripts. Future improvement: migrate to nonce-based CSP.

**Note:** Alpine.js, jQuery, and Vite production builds do not require `unsafe-eval`. Verified via grep for `eval()` and `new Function()` calls.

---

### 20. YouTube API Key Exposed to Clients - FIXED

**Status:** Fixed

**Risk:** API key abuse, quota exhaustion, credential theft.

**Resolution:**
YouTube API calls are now proxied through the server:

- Created `src/backend/Api/V1/Handlers/YouTubeApiHandler.php` to handle YouTube API requests server-side
- Added `/api/v1/youtube/video` endpoint that accepts a `video_id` parameter
- Updated `src/frontend/js/modules/text/pages/youtube_import.ts` to call the server proxy instead of YouTube directly
- Removed the hidden API key input from `src/backend/Core/Integration/YouTubeImport.php`

The API key is now only read on the server and never exposed to clients.

---

### 21. API/Remember Tokens Stored in Plaintext - FIXED

**Status:** Fixed

**Risk:** Database breach exposes all valid tokens for account takeover.

**Resolution:**
Tokens are now hashed using SHA-256 before storage:

- Created `src/Modules/User/Application/Services/TokenHasher.php` for secure token hashing
- Updated `src/Modules/User/Application/UseCases/GenerateApiToken.php` to hash tokens before storage
- Updated `src/Modules/User/Application/UseCases/ValidateApiToken.php` to hash provided tokens before lookup
- Updated `src/Modules/User/Application/UserFacade.php` to hash remember tokens before storage and during validation

The plaintext token is returned to the user once (at generation) and only the hash is stored in the database. When validating, the provided token is hashed and compared against the stored hash.

---

### 22. No Password Reset Functionality - OPEN

**Status:** Open

**Risk:** Users cannot recover accounts; encourages weak/shared passwords.

**Affected:** User module

**Remediation:**
Implement secure email-based password reset:

1. Generate time-limited token (e.g., 1 hour expiry)
2. Send email with reset link containing token
3. Validate token and allow password change
4. Invalidate token after use

```php
// PasswordResetService.php
public function requestReset(string $email): void
{
    $user = $this->userRepository->findByEmail($email);
    if (!$user) return; // Silent fail to prevent enumeration

    $token = bin2hex(random_bytes(32));
    $expires = new DateTime('+1 hour');
    $this->storeResetToken($user->getId(), hash('sha256', $token), $expires);
    $this->emailService->sendResetLink($email, $token);
}
```

---

### 23. No Role-Based Authorization - FIXED

**Status:** Fixed

**Risk:** Any authenticated user can access admin functionality.

**Resolution:**
Created admin middleware and applied to admin routes:

- Created `src/backend/Router/Middleware/AdminMiddleware.php` that checks for admin role after authentication
- Updated `src/backend/Router/routes.php` to define `ADMIN_MIDDLEWARE` constant
- Applied `ADMIN_MIDDLEWARE` to admin-only routes: `/admin/backup`, `/admin/wizard`, `/admin/install-demo`, `/admin/settings`, `/admin/server-data`, `/admin/save-setting`
- Statistics route (`/admin/statistics`) remains accessible to regular users

The middleware:
1. First checks authentication (like `AuthMiddleware`)
2. Then verifies the user has admin role (`User::ROLE_ADMIN`)
3. Returns 403 Forbidden for API requests or redirects to home for web requests if not authorized

---

### 24. withoutUserScope() Has No Authorization Check - OPEN

**Status:** Open

**Risk:** Any code can bypass multi-user data isolation.

**Affected File:** `src/Shared/Infrastructure/Database/QueryBuilder.php` (line 225)

**Issue:**
`withoutUserScope()` disables user filtering with no permission check:

```php
// Any code can do this:
$allWords = QueryBuilder::table('words')
    ->withoutUserScope()
    ->get(); // Returns ALL users' data
```

**Remediation:**
Add authorization check:

```php
public function withoutUserScope(): self
{
    // Only allow in non-multi-user mode or for admin users
    if (Globals::isMultiUserEnabled()) {
        $userId = Globals::getCurrentUserId();
        $user = Container::get(UserRepository::class)->findById($userId);
        if ($user === null || $user->getRole() !== User::ROLE_ADMIN) {
            throw new AuthException('Admin privileges required for cross-user queries');
        }
    }
    $this->userScopeEnabled = false;
    return $this;
}
```

---

### 25. SQL Errors Expose Full Queries - OPEN

**Status:** Open

**Risk:** Information disclosure via error messages reveals table/column structure.

**Affected File:** `src/Shared/Infrastructure/Database/Connection.php` (line 87)

**Issue:**
```php
throw new \RuntimeException(
    'SQL Error [' . mysqli_errno($connection) . ']: ' .
    mysqli_error($connection) . "\nQuery: " . $sql  // Full query exposed
);
```

**Remediation:**
Use `DatabaseException` with query sanitization:

```php
throw new DatabaseException(
    'Database query failed',
    mysqli_errno($connection),
    null,
    $sql // DatabaseException sanitizes this internally
);
```

Or sanitize before including:

```php
$sanitizedQuery = preg_replace("/(['\"])([^'\"]{0,50})\\1/", "'***'", $sql);
throw new \RuntimeException('SQL Error: ' . mysqli_error($connection));
// Log full query separately: error_log("SQL Error - Query: $sql");
```

---

## Medium Priority Issues

### 12. Test Failures - FIXED

**Status:** Fixed

**Previous:** 121 test errors
**Current:** 0 test errors

**Resolution:**
- Added `UsRememberToken` columns to `db/schema/baseline.sql`
- Fixed `tests/setup_test_db.php` to handle mysqli exceptions gracefully

**Commit:** `23e7b20f`

---

### 13. Static Analysis Errors - FIXED

**Status:** Fixed

**Previous:** 31 Psalm errors
**Current:** 0 Psalm errors

**Verification:** `./vendor/bin/psalm` reports "No errors found!"

---

### 14. TypeScript Lint Errors - FIXED

**Status:** Fixed

**Previous:** 298 ESLint errors
**Current:** 0 ESLint errors

**Verification:** `npm run lint` passes cleanly.

---

### 15. SQL Strict Mode - FIXED

**Status:** Fixed

**Resolution:**
MySQL strict mode (`STRICT_ALL_TABLES`) is now enabled in `Configuration.php`:

```php
@mysqli_query($dbconnection, "SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
```

**Schema Updates:**
Added default values to columns that previously had `NOT NULL` without defaults:
- `languages.LgCharacterSubstitutions` - DEFAULT ''
- `languages.LgRegexpSplitSentences` - DEFAULT '.!?'
- `languages.LgExceptionsSplitSentences` - DEFAULT ''
- `languages.LgRegexpWordCharacters` - DEFAULT 'a-zA-ZÀ-ÖØ-öø-ȳ'
- `texts.TxAnnotatedText` - DEFAULT ''
- `archivedtexts.AtAnnotatedText` - DEFAULT ''

**Migration:** `20260107_120000_add_language_column_defaults.sql`

**Commit:** See Configuration.php, baseline.sql

---

### 26. JSON in Script Tags Missing JSON_HEX_TAG - OPEN

**Status:** Open

**Risk:** XSS via `</script>` sequences in user data breaking out of JSON blocks.

**Affected Files:**
- `src/backend/Views/Text/read_header.php` (line 44)
- `src/backend/Views/Text/edit_form.php` (line 63)
- Multiple other view files using `json_encode()` in `<script>` tags

**Issue:**
```php
<script type="application/json" id="config">
<?php echo json_encode($data); ?>
</script>
```

If `$data` contains `</script>`, it breaks out of the tag.

**Remediation:**
Always use `JSON_HEX_TAG` flag:

```php
<?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP); ?>
```

---

### 27. Legacy ErrorHandler::die() Exposes Backtraces - OPEN

**Status:** Open

**Risk:** Stack traces exposed to users, revealing file paths and code structure.

**Affected File:** `src/backend/Core/Utils/ErrorHandler.php` (line 55)

**Issue:**
```php
public static function die(string $text): never
{
    // ... output HTML error
    debug_print_backtrace();  // Always prints, even in production
}
```

**Still Used In:**
- `src/Modules/Text/Application/Services/AnnotationService.php`
- `src/Modules/Vocabulary/Application/Services/WordService.php`
- `src/Modules/Vocabulary/Http/VocabularyController.php`

**Remediation:**
1. Replace `ErrorHandler::die()` calls with proper exceptions
2. Or check debug mode before printing backtrace:

```php
if ($this->isDebugMode()) {
    debug_print_backtrace();
}
```

---

### 28. UTF-8 Collation Instead of utf8mb4 - OPEN

**Status:** Open

**Risk:** Data truncation for emoji and 4-byte Unicode characters.

**Affected Files:**
- `db/schema/baseline.sql`
- `src/Shared/Infrastructure/Database/Configuration.php`

**Issue:**
Schema uses `utf8_general_ci` which only supports 3-byte characters. Emoji and some Asian characters are 4 bytes.

**Remediation:**
1. Update baseline.sql:
```sql
DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

2. Create migration to convert existing tables:
```sql
ALTER DATABASE `learning-with-texts`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE words CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Repeat for other tables
```

---

### 29. Settings Table Lacks Composite Primary Key - FIXED

**Status:** Fixed

**Risk:** In multi-user mode, settings could conflict between users.

**Resolution:**
Created migration `db/migrations/20260107_150000_settings_composite_pk.sql`:

```sql
-- Set default value for NULL StUsID (0 for system settings)
UPDATE settings SET StUsID = 0 WHERE StUsID IS NULL;
ALTER TABLE settings MODIFY StUsID int(10) unsigned NOT NULL DEFAULT 0;

-- Change to composite primary key
ALTER TABLE settings DROP PRIMARY KEY;
ALTER TABLE settings ADD PRIMARY KEY (StKey, StUsID);
```

Also updated `db/schema/baseline.sql` for fresh installations to use `PRIMARY KEY (StKey, StUsID)`.

---

### 30. Frontend Console Logging Visible - FIXED

**Status:** Fixed

**Risk:** Debug information visible in browser DevTools.

**Resolution:**
Already configured in `vite.config.ts` (lines 36-42):

```typescript
build: {
  minify: 'terser',
  terserOptions: {
    compress: {
      drop_console: true,
      drop_debugger: true,
    },
  },
}
```

Console statements are stripped from production builds automatically.

---

### 31. No Migration File Integrity Validation - FIXED

**Status:** Fixed

**Risk:** Corrupted or tampered migration files could execute without detection.

**Resolution:**
Added SHA-256 checksum validation to `src/Shared/Infrastructure/Database/Migrations.php`:

1. **Checksum storage**: Added `checksum` column to `_migrations` table (VARCHAR(64))
2. **Checksum calculation**: `calculateChecksum()` method computes SHA-256 hash of migration files
3. **Recording**: `recordMigration()` now stores checksum when migration is applied
4. **Validation**: `validateMigrationIntegrity()` checks all applied migrations haven't been modified
5. **Integration**: Integrity check runs automatically before applying new migrations (logs warnings if files changed)

Updated schema files:
- `db/schema/baseline.sql`: Added `checksum` column to `_migrations` table
- `src/Shared/Infrastructure/Database/Migrations.php`: Added integrity validation methods

---

## Positive Findings

The codebase demonstrates strong security practices:

1. **Prepared Statements** - `QueryBuilder` and `PreparedStatement` used throughout
2. **Input Validation** - `InputValidator` provides type-safe parameter extraction
3. **HTML Escaping** - Views properly use `htmlspecialchars()` with `ENT_QUOTES` and `UTF-8`
4. **No Dangerous Functions** - No `eval()`, `exec()`, or `system()` with user input
5. **No Unsafe Deserialization** - No `unserialize()` on user data
6. **Modern PHP** - PHP 8.1+ with strict typing
7. **Environment Config** - Credentials in `.env` (gitignored)
8. **Authentication System** - Full auth infrastructure with middleware
9. **Security Headers** - Comprehensive HTTP security headers
10. **Session Hardening** - Secure cookie configuration

---

## Remaining Action Items

### Pre-Release (P0) - Must Fix

All P0 issues have been resolved:

| # | Task | Status |
|---|------|--------|
| 16 | Replace `addslashes()` with `htmlspecialchars()` in Alpine.js contexts | ✅ Fixed |
| 17 | Fix SQL injection in FeedFacade tag processing | ✅ Fixed |
| 18 | Remove/secure default admin user | ✅ Fixed |
| 6 | Implement CSRF token validation | ✅ Fixed |
| 19 | Remove `'unsafe-eval'` from CSP | ✅ Fixed |

### Pre-Release (P1) - Should Fix

| # | Task | Effort | Files |
|---|------|--------|-------|
| 24 | Add authorization check to `withoutUserScope()` | Low | `QueryBuilder.php` |
| 25 | Sanitize SQL errors before throwing | Low | `Connection.php` |
| 26 | Add `JSON_HEX_TAG` to json_encode in views | Low | Multiple view files |
| 27 | Replace ErrorHandler::die() with exceptions | Medium | 3 service files |

### Post-Release (P2) - Can Defer

| # | Task | Effort | Files |
|---|------|--------|-------|
| 7 | Add API rate limiting | Medium | Middleware or reverse proxy |
| 22 | Implement password reset | High | New service, views, email |
| 28 | Migrate utf8 to utf8mb4 | Medium | Migration + schema |

### Deployment Checklist

Before going live, verify:

- [x] All P0 issues resolved
- [ ] `APP_ENV=production` set in `.env`
- [ ] Strong database credentials (not `root`)
- [ ] HTTPS enabled with valid certificate
- [ ] `/var/logs/` protected from web access
- [ ] `npm run build:all` executed for production assets
- [ ] Backup/restore tested end-to-end
- [ ] Admin account created with strong password
- [ ] Add CSRF tokens to all POST forms (use `FormHelper::csrfField()`)

---

## Testing Commands

```bash
# Static Analysis
./vendor/bin/psalm
npm run lint
npm run typecheck

# Unit Tests
composer test           # PHP tests
npm test               # Frontend tests

# Integration Tests
composer test:reset-db  # Reset test database
composer test           # Run all tests
```

---

## References

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [SQL Injection Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)
- [XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)

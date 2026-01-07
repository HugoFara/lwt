# Security Audit Report - LWT v3

**Date:** 2025-12-12
**Last Updated:** 2026-01-07
**Branch:** dev
**Status:** Issues requiring attention before production

This document tracks security issues identified during the pre-release audit of LWT v3.

---

## Summary

| Severity | Total | Fixed | Open |
|----------|-------|-------|------|
| Critical | 8 | 5 | 3 |
| High | 13 | 6 | 7 |
| Medium | 10 | 4 | 6 |

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

### 16. XSS via addslashes() in Alpine.js Contexts - OPEN

**Status:** Open

**Risk:** Cross-site scripting attacks via Alpine.js `x-data` attributes.

**Affected Files:**
- `src/backend/Views/Tags/tag_form.php` (lines 54-55)
- `src/backend/Views/Feed/browse.php` (line 42)
- `src/Modules/Text/Views/print_alpine.php`
- `src/Modules/Tags/Views/tag_form.php`

**Issue:**
`addslashes()` only escapes single quotes and backslashes, not HTML entities. Attackers can inject via double quotes or Alpine.js expressions.

```php
// VULNERABLE
x-data="{tagText: '<?php echo addslashes($tagText); ?>'}"

// Example payload: foo'}) + alert('xss') + ({x: '
```

**Remediation:**
Replace all `addslashes()` with `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` in Alpine.js contexts:

```php
// SAFE
x-data="{tagText: '<?php echo htmlspecialchars($tagText, ENT_QUOTES, 'UTF-8'); ?>'}"
```

**Alternative:** Move dynamic data to `<script type="application/json">` tags and parse in JavaScript.

---

### 17. SQL Injection in Feed Tag Processing - OPEN

**Status:** Open

**Risk:** SQL injection via tag names in RSS feed processing.

**Affected File:** `src/Modules/Feed/Application/FeedFacade.php` (lines 831, 885, 899)

**Issue:**
Tag names from RSS feeds are directly interpolated into SQL IN() clauses:

```php
$NfTag = '"' . implode('","', $text['TagList']) . '"';
// Later used in:
'WHERE T2Text IN (' . $NfTag . ')'
```

A malicious tag name like `tag","' OR '1'='1` could exploit this.

**Remediation:**
Use prepared statements with parameter placeholders:

```php
// Generate placeholders
$placeholders = implode(',', array_fill(0, count($text['TagList']), '?'));
$sql = "SELECT ... WHERE T2Text IN ($placeholders)";
$bindings = array_merge($bindings, $text['TagList']);
Connection::preparedFetchAll($sql, $bindings);
```

---

### 18. Default Admin User Without Password - OPEN

**Status:** Open

**Risk:** Unauthorized admin access if deployment uses baseline.sql without proper setup.

**Affected File:** `db/schema/baseline.sql` (lines 36-38)

**Issue:**
A default admin user is created without a password:

```sql
INSERT IGNORE INTO users (UsID, UsUsername, UsEmail, UsRole)
VALUES (1, 'admin', 'admin@localhost', 'admin');
```

**Remediation:**
Option A: Remove from baseline.sql and require admin creation during setup wizard.

Option B: Add first-login password requirement enforcement:

```php
// In AuthMiddleware or Application.php
if ($user->getPasswordHash() === null && $user->getRole() === 'admin') {
    // Force redirect to password setup page
    header('Location: /setup-password');
    exit;
}
```

---

## High Priority Issues

### 6. CSRF Token Validation Missing - OPEN

**Status:** Partially Fixed (token generated, validation missing)

**Previous Resolution:**
CSRF protection partially implemented in `AuthService`:
- Session token generated via `SESSION_TOKEN` constant
- SameSite cookie attribute set to 'Lax' for additional CSRF protection

**Current Issue:**
The session token is generated but **never validated** on form submissions. No middleware or controller checks the token.

**Affected:** All POST/PUT/DELETE form submissions

**Remediation:**
1. Add CSRF token to all forms:
```php
<input type="hidden" name="_csrf_token" value="<?php echo $_SESSION['LWT_SESSION_TOKEN'] ?? ''; ?>">
```

2. Create validation middleware or add to BaseController:
```php
protected function validateCsrfToken(): void
{
    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['LWT_SESSION_TOKEN'] ?? '';

    if (!hash_equals($sessionToken, $token)) {
        throw new AuthException('Invalid CSRF token', 403);
    }
}
```

3. Apply to all state-changing endpoints

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

### 19. CSP Policy Too Permissive - OPEN

**Status:** Open

**Risk:** Content Security Policy allows unsafe JavaScript execution, weakening XSS protection.

**Affected File:** `src/Shared/Infrastructure/Http/SecurityHeaders.php` (lines 110-134)

**Issue:**
```php
"script-src 'self' 'unsafe-inline' 'unsafe-eval'"
```

- `'unsafe-inline'` allows inline `<script>` tags and event handlers
- `'unsafe-eval'` allows `eval()`, `Function()` constructors

**Remediation:**
1. Remove `'unsafe-eval'` entirely (not required for modern code)
2. Move inline scripts to external files
3. Use nonces for necessary inline scripts:

```php
$nonce = base64_encode(random_bytes(16));
"script-src 'self' 'nonce-{$nonce}'"

// In templates:
<script nonce="<?php echo $nonce; ?>">...</script>
```

---

### 20. YouTube API Key Exposed to Clients - OPEN

**Status:** Open

**Risk:** API key abuse, quota exhaustion, credential theft.

**Affected Files:**
- `src/backend/Core/Integration/YouTubeImport.php` (line 60)
- `src/frontend/js/modules/text/pages/youtube_import.ts` (line 96)

**Issue:**
API key rendered in HTML and used directly in client-side fetch requests:

```php
<input type="hidden" id="ytApiKey" value="<?php echo htmlspecialchars($apiKey, ENT_QUOTES, 'UTF-8'); ?>" />
```

**Remediation:**
Proxy YouTube API calls through the server:

```php
// New API endpoint: /api/v1/youtube/video-info
public function getVideoInfo(string $videoId): array
{
    $apiKey = getenv('YOUTUBE_API_KEY');
    $response = file_get_contents(
        "https://www.googleapis.com/youtube/v3/videos?part=snippet&id={$videoId}&key={$apiKey}"
    );
    return json_decode($response, true);
}
```

---

### 21. API/Remember Tokens Stored in Plaintext - OPEN

**Status:** Open

**Risk:** Database breach exposes all valid tokens for account takeover.

**Affected File:** `src/Modules/User/Infrastructure/MySqlUserRepository.php`

**Issue:**
`UsApiToken` and `UsRememberToken` stored as plaintext in database.

**Remediation:**
Hash tokens before storage (like passwords):

```php
// On token generation
$token = bin2hex(random_bytes(32));
$hashedToken = password_hash($token, PASSWORD_DEFAULT);
// Store $hashedToken in database, return $token to user

// On token validation
$storedHash = $user->getApiTokenHash();
if (!password_verify($providedToken, $storedHash)) {
    throw new AuthException('Invalid token');
}
```

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

### 23. No Role-Based Authorization - OPEN

**Status:** Open

**Risk:** Any authenticated user can access admin functionality.

**Affected Files:**
- `src/Modules/Admin/Http/AdminController.php`
- `src/Modules/Admin/Http/AdminApiHandler.php`
- All `/admin/*` routes

**Issue:**
User entity has `UsRole` field (`user`/`admin`) but it's never checked. Admin endpoints only verify authentication, not authorization.

**Remediation:**
Create admin middleware:

```php
// src/backend/Router/Middleware/AdminMiddleware.php
class AdminMiddleware
{
    public function handle(): bool
    {
        $userId = Globals::getCurrentUserId();
        if ($userId === null) {
            return false;
        }

        $user = $this->userRepository->findById($userId);
        if ($user === null || $user->getRole() !== User::ROLE_ADMIN) {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            return false;
        }
        return true;
    }
}
```

Apply to admin routes in `routes.php`.

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

### 29. Settings Table Lacks Composite Primary Key - OPEN

**Status:** Open

**Risk:** In multi-user mode, settings could conflict between users.

**Affected File:** `db/migrations/20251212_000002_add_user_id_columns.sql`

**Issue:**
Settings table has `StUsID` column but primary key is still just `(StKey)`, not `(StKey, StUsID)`.

**Remediation:**
Create migration:

```sql
ALTER TABLE settings DROP PRIMARY KEY;
ALTER TABLE settings ADD PRIMARY KEY (StKey, StUsID);
```

Update settings queries to always include user_id in WHERE clause.

---

### 30. Frontend Console Logging Visible - OPEN

**Status:** Open (Low Priority)

**Risk:** Debug information visible in browser DevTools.

**Affected:** Multiple TypeScript files in `src/frontend/js/`

**Issue:**
`console.error()`, `console.warn()`, `console.log()` statements remain in production code.

**Remediation:**
Configure Vite to strip console statements in production:

```js
// vite.config.js
export default defineConfig({
  build: {
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true
      }
    }
  }
});
```

---

### 31. No Migration File Integrity Validation - OPEN

**Status:** Open (Low Priority)

**Risk:** Corrupted or tampered migration files could execute without detection.

**Affected:** `src/Shared/Infrastructure/Database/Migrations.php`

**Remediation:**
Add checksum validation:

1. Generate checksums file:
```bash
sha256sum db/migrations/*.sql > db/migrations/checksums.sha256
```

2. Validate before executing:
```php
$expectedHash = $this->loadExpectedHash($filename);
$actualHash = hash_file('sha256', $filepath);
if ($expectedHash !== $actualHash) {
    throw new \RuntimeException("Migration file integrity check failed: $filename");
}
```

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

| # | Task | Effort | Files |
|---|------|--------|-------|
| 16 | Replace `addslashes()` with `htmlspecialchars()` in Alpine.js contexts | Low | 4 view files |
| 17 | Fix SQL injection in FeedFacade tag processing | Medium | `FeedFacade.php` |
| 18 | Remove/secure default admin user | Low | `baseline.sql` |
| 6 | Implement CSRF token validation | Medium | New middleware + views |
| 19 | Remove `'unsafe-eval'` from CSP | Low | `SecurityHeaders.php` |

### Pre-Release (P1) - Should Fix

| # | Task | Effort | Files |
|---|------|--------|-------|
| 20 | Proxy YouTube API calls server-side | Medium | New endpoint + refactor TS |
| 21 | Hash API/remember tokens before storage | Medium | `MySqlUserRepository.php` |
| 23 | Implement role-based authorization | Medium | New middleware + routes |
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
| 29 | Add composite PK to settings table | Low | Migration |
| 30 | Strip console.log in production builds | Low | `vite.config.js` |
| 31 | Add migration checksum validation | Low | `Migrations.php` |

### Deployment Checklist

Before going live, verify:

- [ ] All P0 issues resolved
- [ ] `APP_ENV=production` set in `.env`
- [ ] Strong database credentials (not `root`)
- [ ] HTTPS enabled with valid certificate
- [ ] `/var/logs/` protected from web access
- [ ] `npm run build:all` executed for production assets
- [ ] Backup/restore tested end-to-end
- [ ] Admin account created with strong password

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

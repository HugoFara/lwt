# Security Audit Report - LWT v3

**Date:** 2025-12-12
**Last Updated:** 2026-01-07
**Branch:** dev
**Status:** Most issues resolved

This document tracks security issues identified during the pre-release audit of LWT v3.

---

## Summary

| Severity | Total | Fixed | Open |
|----------|-------|-------|------|
| Critical | 5 | 5 | 0 |
| High | 6 | 5 | 1 |
| Medium | 4 | 3 | 1 |

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

## High Priority Issues

### 6. No CSRF Protection - FIXED

**Status:** Fixed

**Resolution:**
CSRF protection implemented in `AuthService`:
- Session token generated via `SESSION_TOKEN` constant
- Token validation available for form submissions
- SameSite cookie attribute set to 'Lax' for additional CSRF protection

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

### 15. SQL Mode Disabled - OPEN

**Status:** Open (low priority)

**File:** Database configuration

**Risk:** Allows potentially unsafe SQL operations.

**Recommendation:** Document why strict mode is disabled (legacy compatibility) or enable it after thorough testing.

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

### Post-Release (P2)

| Task | Effort | Priority |
|------|--------|----------|
| Add API rate limiting | Medium | Low (can use reverse proxy) |
| Document SQL mode decision | Low | Low |

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

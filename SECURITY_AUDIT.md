# Security Audit Report - LWT v3

**Date:** 2025-12-12
**Branch:** v3
**Status:** Pre-release audit

This document identifies critical issues that should be addressed before a public release of LWT.

---

## Summary

| Severity | Count | Status |
|----------|-------|--------|
| Critical | 5 | Open |
| High | 6 | Open |
| Medium | 4 | Open |

---

## Critical Issues

### 1. No Authentication System

**Risk:** Anyone with URL access can view, modify, and delete all data.

**Affected Areas:**

- Entire web application
- REST API (`/api/v1/*`)
- Admin panel (`/admin/*`)
- Database wizard (`/admin/wizard`)

**Details:**
The application has no login system. Sessions are started but never validated against user credentials. The REST API accepts all requests without authentication.

**Impact:**

- Complete data exposure
- Unauthorized data modification/deletion
- Settings tampering

**Recommendation:**
Implement authentication before public deployment, or clearly document this is a single-user self-hosted application that must be protected at the network/server level.

---

### 2. SQL Injection in IN() Clauses

**Risk:** Attackers can execute arbitrary SQL commands.

**Affected Files:**

| File | Line(s) | Variable |
|------|---------|----------|
| `src/backend/Services/FeedService.php` | 502, 1595 | `$ids`, `$currentFeed` |
| `src/backend/Services/TestService.php` | 100, 114 | `$idString` |
| `src/backend/Services/TagService.php` | 986 | `$idList` |
| `src/backend/Controllers/FeedsController.php` | 698 | `$sorts` array |

**Pattern:**

```php
// VULNERABLE: Array values joined and inserted directly
$ids = implode(',', $markedItems);
$sql = "SELECT * FROM table WHERE id IN ($ids)";
```

**Impact:**

- Database dump
- Data modification/deletion
- Privilege escalation

**Fix:**

```php
// SAFE: Use prepared statement placeholders
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "SELECT * FROM table WHERE id IN ($placeholders)";
$stmt = Connection::prepare($sql);
$stmt->bind($ids);
```

---

### 3. XSS via $_SERVER['PHP_SELF']

**Risk:** Session hijacking, credential theft.

**Affected Files:**

| File | Line |
|------|------|
| `src/backend/Views/Feed/browse.php` | 116 |
| `src/backend/Views/Word/form_edit_term.php` | 44 |
| `src/backend/Views/Word/form_new.php` | 30 |
| `src/backend/Views/Word/form_edit_existing.php` | 37 |
| `src/backend/Views/Text/archived_form.php` | 32 |

**Pattern:**

```php
<!-- VULNERABLE -->
<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
```

**Attack:**

```text
http://example.com/page.php/"><script>alert(document.cookie)</script>
```

**Fix:**

```php
<!-- SAFE: Escape output -->
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post">

<!-- BETTER: Use known route -->
<form action="/feeds/browse" method="post">
```

---

### 4. XSS in Video Player

**Risk:** Malicious URLs can execute JavaScript.

**File:** `src/backend/Services/MediaService.php:280`

**Pattern:**

```php
// VULNERABLE: URL not escaped
<iframe src="<?php echo $url ?>">
```

**Fix:**

```php
<iframe src="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
```

---

### 5. Debug Mode Enabled by Default

**Risk:** Exposes stack traces, file paths, and database queries.

**File:** `src/backend/Application.php:62-64`

```php
// Current (INSECURE)
error_reporting(E_ALL);
ini_set('display_errors', '1');
```

**Fix:**

```php
// Make environment-dependent
$debug = getenv('APP_DEBUG') === 'true';
error_reporting($debug ? E_ALL : 0);
ini_set('display_errors', $debug ? '1' : '0');
```

---

## High Priority Issues

### 6. No CSRF Protection

**Risk:** Cross-site request forgery attacks can modify data.

**Affected:** All forms throughout the application.

**Details:**
No CSRF tokens are generated or validated. Attackers can craft malicious pages that submit forms on behalf of users.

**Recommendation:**

1. Generate token on session start: `$_SESSION['csrf_token'] = bin2hex(random_bytes(32));`
2. Add hidden field to all forms
3. Validate token on POST requests

---

### 7. No API Rate Limiting

**Risk:** DoS attacks, brute force, API abuse.

**Affected:** `/api/v1/*` endpoints

**Recommendation:**
Implement per-IP rate limiting, either in application code or via reverse proxy (nginx, Apache mod_ratelimit).

---

### 8. Session Security Not Hardened

**Risk:** Session hijacking.

**File:** `src/backend/Core/Bootstrap/start_session.php`

**Missing:**

- `session.cookie_httponly = 1`
- `session.cookie_secure = 1` (for HTTPS)
- `session.cookie_samesite = 'Strict'`
- `session_regenerate_id()` after authentication

**Fix:**

```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
if (isset($_SERVER['HTTPS'])) {
    ini_set('session.cookie_secure', 1);
}
session_start();
```

---

### 9. Unprotected Database Wizard

**Risk:** Attackers can modify database credentials.

**Route:** `/admin/wizard`

**Details:**
The wizard allows creating/modifying `.env` with database credentials without any authentication.

**Recommendation:**

- Disable route after initial setup
- Add authentication requirement
- Or make it CLI-only

---

### 10. Missing Backend Directory Protection

**Risk:** PHP source code exposure.

**Issue:** No `.htaccess` in `src/backend/`

**Fix:** Create `src/backend/.htaccess`:

```apache
<FilesMatch "\.(php|inc)$">
    Require all denied
</FilesMatch>
```

---

### 11. Missing Security Headers

**Risk:** Clickjacking, MIME sniffing attacks.

**Missing Headers:**

- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `Content-Security-Policy`
- `Strict-Transport-Security` (for HTTPS)

---

## Medium Priority Issues

### 12. Test Failures (Broken Code)

**Count:** 21 test errors

**Cause:**

```text
Error: Class "Lwt\Core\Utils" not found
Error: Call to undefined function Lwt\Core\Utils\replaceSuppUnicodePlanesChar()
```

**Impact:** Indicates broken/missing code after refactoring.

**Action:** Restore missing `Utils` class or update references.

---

### 13. Static Analysis Errors

**Count:** 31 Psalm errors

**Notable Issues:**

- `FeedsController::$tbpref` - undefined property
- `ExportService::exportAnki/exportTsv/exportFlexible` - called statically but not static
- Various type mismatches in return types

**Action:** Run `./vendor/bin/psalm` and fix reported issues.

---

### 14. TypeScript Lint Errors

**Count:** 298 ESLint errors

**Primary Issue:** `@typescript-eslint/no-explicit-any` - excessive use of `any` type.

**Action:** Run `npm run lint` and progressively fix type annotations.

---

### 15. SQL Mode Disabled

**File:** `src/backend/Core/Database/Configuration.php:165`

```php
@mysqli_query($dbconnection, "SET SESSION sql_mode = ''");
```

**Risk:** Allows potentially unsafe SQL operations.

**Recommendation:** Use strict mode or document why it's disabled.

---

## Positive Findings

The codebase demonstrates several good security practices:

1. **Prepared Statements Infrastructure** - `Connection::prepare()` and `PreparedStatement` class exist
2. **Input Validation Layer** - `InputValidator` class provides type-safe parameter extraction
3. **HTML Escaping** - Most views properly use `htmlspecialchars()` with `ENT_QUOTES` and `UTF-8`
4. **No Dangerous Functions** - No `eval()`, `exec()`, or `system()` with user input
5. **No Unsafe Deserialization** - No `unserialize()` on user data
6. **Modern PHP** - Uses PHP 8.1+ features with strict typing
7. **Environment Config** - Credentials stored in `.env` (gitignored)

---

## Action Plan

### Before Public Release (P0)

| Task | Effort | File(s) |
|------|--------|---------|
| Fix SQL injection in IN() clauses | Medium | FeedService, TestService, TagService, FeedsController |
| Fix XSS in $_SERVER['PHP_SELF'] | Low | 5 view files |
| Fix XSS in MediaService | Trivial | MediaService.php:280 |
| Disable display_errors | Trivial | Application.php |
| Fix test failures | Low | Utils class |

### Before Public Release (P1)

| Task | Effort |
|------|--------|
| Implement authentication OR document single-user design | High |
| Add CSRF protection | Medium |
| Harden session configuration | Low |
| Protect/disable database wizard | Low |
| Add backend .htaccess | Trivial |

### Post-Release (P2)

| Task | Effort |
|------|--------|
| Add API rate limiting | Medium |
| Fix Psalm errors | Medium |
| Fix ESLint errors | Medium |
| Add security headers | Low |

---

## Testing Recommendations

1. **Static Analysis:**

   ```bash
   ./vendor/bin/psalm
   npm run lint
   npm run typecheck
   ```

2. **Unit Tests:**

   ```bash
   composer test
   npm test
   ```

3. **Security Testing:**
   - OWASP ZAP or Burp Suite scan
   - Manual SQL injection testing on feed/test selection
   - XSS testing on all form inputs
   - CSRF testing on state-changing operations

---

## References

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [SQL Injection Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)
- [XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)

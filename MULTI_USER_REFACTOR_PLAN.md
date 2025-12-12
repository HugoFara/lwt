# Multi-User System Refactor: Table Prefix → User ID

## Overview

Refactor LWT from table-prefix-based data isolation to a proper user_id column approach with built-in authentication.

**Current State**: Each "user" has prefixed tables (e.g., `user1_words`, `user2_words`). No actual authentication - WordPress integration only.

**Target State**: Single set of tables with `user_id` column. Built-in auth system with WordPress compatibility.

---

## Phase 1: Database Schema Foundation

### 1.1 Create Users Table

**New file**: `db/migrations/YYYYMMDD_HHMMSS_add_users_table.sql`

```sql
CREATE TABLE users (
    UsID int(10) unsigned NOT NULL AUTO_INCREMENT,
    UsUsername varchar(100) NOT NULL,
    UsEmail varchar(255) NOT NULL,
    UsPasswordHash varchar(255) DEFAULT NULL,
    UsApiToken varchar(64) DEFAULT NULL,
    UsApiTokenExpires datetime DEFAULT NULL,
    UsWordPressId int(10) unsigned DEFAULT NULL,
    UsCreated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UsLastLogin timestamp NULL DEFAULT NULL,
    UsIsActive tinyint(1) unsigned NOT NULL DEFAULT 1,
    UsRole enum('user','admin') NOT NULL DEFAULT 'user',
    PRIMARY KEY (UsID),
    UNIQUE KEY UsUsername (UsUsername),
    UNIQUE KEY UsEmail (UsEmail),
    UNIQUE KEY UsApiToken (UsApiToken),
    KEY UsWordPressId (UsWordPressId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### 1.2 Add user_id Columns

**New file**: `db/migrations/YYYYMMDD_HHMMSS_add_user_id_columns.sql`

Add to primary ownership tables:

| Table | New Column | Index |
|-------|------------|-------|
| `languages` | `LgUsID int(10) unsigned` | `KEY LgUsID` |
| `texts` | `TxUsID int(10) unsigned` | `KEY TxUsID` |
| `archivedtexts` | `AtUsID int(10) unsigned` | `KEY AtUsID` |
| `words` | `WoUsID int(10) unsigned` | `KEY WoUsID` |
| `tags` | `TgUsID int(10) unsigned` | `KEY TgUsID` |
| `tags2` | `T2UsID int(10) unsigned` | `KEY T2UsID` |
| `newsfeeds` | `NfUsID int(10) unsigned` | `KEY NfUsID` |
| `settings` | `StUsID int(10) unsigned` | Composite PK `(StKey, StUsID)` |

**NOT modified** (inherit user context via FK):

- `sentences` (via `SeTxID` → texts)
- `textitems2` (via `Ti2TxID` → texts)
- `wordtags` (via `WtWoID` → words)
- `texttags` (via `TtTxID` → texts)
- `archtexttags` (via `AgAtID` → archivedtexts)
- `feedlinks` (via `FlNfID` → newsfeeds)

### 1.3 Add Foreign Key Constraints

**New file**: `db/migrations/YYYYMMDD_HHMMSS_add_foreign_keys.sql`

- FK from all `*UsID` columns to `users(UsID)` with `ON DELETE CASCADE`
- FK for existing relationships (texts→languages, words→languages, etc.)

---

## Phase 2: User Context in Core

### 2.1 Extend Globals Class

**Modify**: `src/backend/Core/Globals.php`

Add:

```php
private static ?int $currentUserId = null;

public static function setCurrentUserId(?int $userId): void
public static function getCurrentUserId(): ?int
public static function requireUserId(): int  // throws AuthException if null
```

### 2.2 Create User Entity

**New file**: `src/backend/Core/Entity/User.php`

Properties: `id`, `username`, `email`, `passwordHash`, `apiToken`, `wordPressId`, `created`, `lastLogin`, `isActive`, `role`

---

## Phase 3: Authentication System

### 3.1 Auth Services

**New file**: `src/backend/Services/AuthService.php`

- `register(username, email, password): User`
- `login(usernameOrEmail, password): ?User`
- `logout(): void`
- `getCurrentUser(): ?User`
- `setCurrentUser(User): void`
- `validateSession(): bool`
- `generateApiToken(userId): string`
- `validateApiToken(token): ?User`
- `findOrCreateWordPressUser(wpUserId): User`

**New file**: `src/backend/Services/PasswordService.php`

- `hash(password): string` (using Argon2ID)
- `verify(password, hash): bool`
- `needsRehash(hash): bool`

### 3.2 Auth Controller

**New file**: `src/backend/Controllers/AuthController.php`

- `loginForm()` - GET /login
- `login()` - POST /login
- `registerForm()` - GET /register
- `register()` - POST /register
- `logout()` - GET /logout

### 3.3 Auth Views

**New files**:

- `src/backend/Views/Auth/login.php`
- `src/backend/Views/Auth/register.php`

---

## Phase 4: Route Protection

### 4.1 Auth Middleware

**New file**: `src/backend/Router/Middleware/AuthMiddleware.php`

```php
public function handle(): bool
{
    // 1. Check session auth (LWT_USER_ID in $_SESSION)
    // 2. Check API token (Authorization: Bearer header)
    // 3. Return true if authenticated, false otherwise
}
```

### 4.2 Router Middleware Support

**Modify**: `src/backend/Router/Router.php`

Add:

- `registerWithMiddleware(path, handler, middlewareArray, method)`
- Execute middleware chain before controller in `execute()`

### 4.3 Update Routes

**Modify**: `src/backend/Router/routes.php`

- Public routes: `/login`, `/register`, `/wordpress/start`, `/wordpress/stop`
- Protected routes: all others (wrap with `AuthMiddleware`)

---

## Phase 5: QueryBuilder Auto-Filtering

### 5.1 User Scope in QueryBuilder

**Modify**: `src/backend/Core/Database/QueryBuilder.php`

```php
private const USER_SCOPED_TABLES = [
    'languages' => 'LgUsID',
    'texts' => 'TxUsID',
    'archivedtexts' => 'AtUsID',
    'words' => 'WoUsID',
    'tags' => 'TgUsID',
    'tags2' => 'T2UsID',
    'newsfeeds' => 'NfUsID',
    'settings' => 'StUsID',
];

private bool $userScopeEnabled = true;
private string $baseTableName;

public function withoutUserScope(): static  // For admin/migration queries

private function applyUserScope(): void
{
    if (!$this->userScopeEnabled) return;
    $userId = Globals::getCurrentUserId();
    if ($userId === null) return;
    if (isset(self::USER_SCOPED_TABLES[$this->baseTableName])) {
        $this->where(self::USER_SCOPED_TABLES[$this->baseTableName], '=', $userId);
    }
}
```

Call `applyUserScope()` in: `get()`, `getPrepared()`, `first()`, `firstPrepared()`, `count()`, `delete()`, `deletePrepared()`

Auto-inject `user_id` in: `insert()`, `insertPrepared()`

### 5.2 Raw Query Helper

**New file**: `src/backend/Core/Database/UserScopedQuery.php`

For services using raw SQL - helper to add user_id conditions.

---

## Phase 6: API Authentication

### 6.1 API Auth Handler

**New file**: `src/backend/Api/V1/Handlers/AuthHandler.php`

- `POST /api/v1/auth/login` - Returns token
- `POST /api/v1/auth/register` - Creates user, returns token
- `POST /api/v1/auth/refresh` - Refresh token
- `POST /api/v1/auth/logout` - Invalidate token

### 6.2 API Auth Validation

**Modify**: `src/backend/Api/V1/ApiV1.php`

- Add `validateAuth()` method
- Skip auth for `/auth/*` endpoints
- Require auth for all other endpoints

---

## Phase 7: WordPress Integration Update

**Modify**: `src/backend/Services/WordPressService.php`

Update `handleStart()`:

1. Authenticate with WordPress (existing)
2. Get WordPress user ID
3. Call `AuthService::findOrCreateWordPressUser(wpUserId)`
4. Set LWT user in session via `AuthService::setCurrentUser()`

This links WordPress users to LWT users while maintaining existing WP auth flow.

---

## Phase 8: Data Migration

### 8.1 Migration Script

**New file**: `db/migrations/YYYYMMDD_HHMMSS_migrate_prefix_data.php`

Strategy:

1. Find all existing table prefixes (scan `_lwtgeneral` or look for `*_settings` tables)
2. For each prefix:
   - Create user account (username from prefix, or link to WP user if available)
   - Copy data from prefixed tables to main tables with user_id set
   - Or: UPDATE existing prefixed data to set user_id, then rename tables
3. Update `_lwtgeneral` to mark migration complete

### 8.2 Backward Compatibility Flag

**Modify**: `.env.example`

```
# Set to true after migration is complete
MULTI_USER_ENABLED=false
```

**Modify**: `src/backend/Core/Globals.php`

```php
public static function isMultiUserEnabled(): bool
```

---

## Phase 9: Service Layer Updates

Services needing updates for user context:

| Service | Changes Needed |
|---------|----------------|
| `LanguageService` | Use QueryBuilder (auto-scoped) |
| `TextService` | Use QueryBuilder for user filtering |
| `WordService` | Already uses prefix, switch to user_id |
| `TagService` | Use QueryBuilder |
| `FeedService` | Use QueryBuilder |
| `SettingsService` | Add user_id to settings queries |
| `TableSetService` | Deprecate or repurpose for admin |
| `HomeService` | Remove prefix selection UI |

---

## Phase 10: Testing

### Unit Tests

- `tests/backend/Services/AuthServiceTest.php`
- `tests/backend/Services/PasswordServiceTest.php`
- `tests/backend/Core/Database/QueryBuilderUserScopeTest.php`

### Integration Tests

- User isolation (User A can't see User B's data)
- WordPress user linking
- API token auth flow

### E2E Tests

- Login/logout flow
- Registration flow
- Data isolation verification

---

## Critical Files Summary

| File | Action |
|------|--------|
| `src/backend/Core/Globals.php` | Modify - add user context |
| `src/backend/Core/Database/QueryBuilder.php` | Modify - add auto user filtering |
| `src/backend/Router/Router.php` | Modify - add middleware support |
| `src/backend/Router/routes.php` | Modify - protect routes |
| `src/backend/Services/WordPressService.php` | Modify - link WP to LWT users |
| `src/backend/Services/AuthService.php` | New |
| `src/backend/Services/PasswordService.php` | New |
| `src/backend/Controllers/AuthController.php` | New |
| `src/backend/Router/Middleware/AuthMiddleware.php` | New |
| `src/backend/Core/Entity/User.php` | New |
| `src/backend/Api/V1/Handlers/AuthHandler.php` | New |
| `db/migrations/*` | New (4 migration files) |

---

## Implementation Order

1. **Database migrations** (users table, user_id columns, FK constraints)
2. **User entity + Globals extension** (user context infrastructure)
3. **AuthService + PasswordService** (core auth logic)
4. **AuthMiddleware + Router update** (route protection)
5. **AuthController + views** (login/register UI)
6. **QueryBuilder modification** (automatic user filtering)
7. **API auth** (token-based)
8. **WordPress integration update** (link WP to LWT users)
9. **Service layer updates** (use new query patterns)
10. **Data migration script** (prefix → user_id)
11. **Testing** (unit, integration, E2E)

---

## Rollback Plan

1. Feature flag `MULTI_USER_ENABLED=false` disables new auth
2. User_id columns are nullable during transition
3. Keep table prefix logic until migration verified
4. Rollback migration drops user_id columns if needed

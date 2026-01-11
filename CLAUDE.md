# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## About This Project

Learning with Texts (LWT) is a self-hosted web application for language learning by reading. This is a third-party community-maintained fork that improves upon the official SourceForge version with modern PHP support (8.1-8.4), smaller database size, better mobile support, and active development.

**Tech Stack:**

- Backend: PHP 8.1+ with MySQLi
- Frontend: TypeScript, Alpine.js, Bulma CSS, jQuery (legacy)
- Database: MySQL/MariaDB with InnoDB engine
- Build Tools: Composer (PHP), NPM with Vite (JS/CSS)

## Development Setup

### Initial Setup

```bash
git clone https://github.com/HugoFara/lwt
cd lwt
composer install --dev
npm install
```

### Database Configuration

Copy `.env.example` to `.env` and update the database credentials:

```bash
cp .env.example .env
# Edit .env with your database credentials
```

The `.env` file contains:

- `DB_HOST` - Database server (default: localhost)
- `DB_USER` - Database username (default: root)
- `DB_PASSWORD` - Database password
- `DB_NAME` - Database name (default: learning-with-texts)
- `DB_SOCKET` - Optional database socket
- `MULTI_USER_ENABLED` - Enable user_id-based data isolation (default: false)

## Common Commands

### Running the Application

```bash
# Docker (recommended for quick setup)
docker compose up                # Start app at http://localhost:8010/lwt/

# PHP built-in server (for development)
php -S localhost:8000            # Start at http://localhost:8000/
```

### Testing

```bash
# PHP tests
composer test                    # Run PHPUnit tests with coverage
composer test:no-coverage        # Run PHPUnit tests without coverage (faster)

# Run a single test file
./vendor/bin/phpunit tests/backend/Services/TextServiceTest.php

# Run a specific test method
./vendor/bin/phpunit --filter testMethodName

# Integration tests (requires test database)
composer test:setup-db           # Create test database and apply migrations
composer test:db-status          # Show test database status
composer test:reset-db           # Drop and recreate test database
composer test:integration        # Run integration tests (sets up DB automatically)

# Frontend tests (Vitest)
npm test                         # Run all frontend tests
npm run test:watch               # Watch mode for frontend tests
npm run test:coverage            # Run with coverage

# E2E tests (requires server on localhost:8000)
npm run e2e                      # Run Cypress E2E tests
npm run cy:open                  # Interactive Cypress test runner
```

**Integration Tests:** Some tests require a MySQL database with FK constraints. Run `composer test:setup-db` once to create the test database (`test_<dbname>` from your `.env`). The integration test suite includes FK cascade tests, tag service tests, and other database-dependent tests.

**When to run E2E tests:** Run `npm run e2e` after making changes to:

- Routes or URL handling (`src/backend/Router/`)
- Controllers (`src/backend/Controllers/`)
- Form handling or navigation
- REST API endpoints
- Fix the test failures, even if they are unrelated to the current changes.

### Code Quality

```bash
./vendor/bin/psalm                                   # Static analysis (default level)
composer psalm:level1                                # Strictest static analysis
npm run lint                                         # ESLint for TypeScript/JS
npm run lint:fix                                     # Auto-fix lint issues
npm run typecheck                                    # TypeScript type checking
./vendor/bin/phpcs [file]                            # PHP code style check
./vendor/bin/phpcbf [file]                           # PHP code style auto-fix
```

### Asset Building

```bash
npm run dev                      # Start Vite dev server with HMR
npm run build                    # Build Vite JS/CSS bundles
npm run build:themes             # Build theme CSS files
npm run build:all                # Build everything (Vite + themes)
composer build                   # Alias for npm run build:all
```

**Frontend Development Workflow:**

1. Run `npm run dev` for development with Hot Module Replacement
2. Run `npm run typecheck` to check TypeScript errors
3. Run `npm run build:all` for production build before committing

### Documentation Generation

```bash
composer doc                     # Regenerate all documentation (VitePress + JSDoc + phpDoc)
composer clean-doc               # Clear all generated documentation
```

## Architecture Overview

### Request Flow (v3 Front Controller)

All requests route through `index.php` → `Router` → `Controller` → `Service` → `View`:

1. `index.php` bootstraps the application and invokes the Router
2. `src/backend/Router/routes.php` maps URLs to controller methods
3. Controllers in `src/backend/Controllers/` handle request/response
4. Services in `src/backend/Services/` contain business logic
5. Views in `src/backend/Views/` render HTML output

### Key Directories

```text
src/Shared/                          # Cross-cutting infrastructure
├── Infrastructure/
│   ├── Database/                    # Connection, DB, QueryBuilder, PreparedStatement, etc.
│   ├── Http/                        # InputValidator, SecurityHeaders, UrlUtilities
│   └── Container/                   # DI Container, ServiceProviders
├── Domain/
│   └── ValueObjects/                # UserId (cross-module identity)
└── UI/
    ├── Helpers/                     # FormHelper, IconHelper, PageLayoutHelper, etc.
    └── Assets/                      # ViteHelper

src/Modules/                         # Feature modules (bounded contexts)
├── Admin/                           # Admin/settings module
├── Dictionary/                      # Dictionary lookup/translation module
├── Feed/                            # RSS feed module
├── Home/                            # Home page/dashboard module
├── Language/                        # Language configuration module
├── Review/                          # Spaced repetition testing module
├── Tags/                            # Tagging module
├── Text/                            # Text reading/import module
├── User/                            # User authentication module
└── Vocabulary/                      # Terms/words module

# Each module follows this structure:
├── Application/                     # Use cases and application services
├── Domain/                          # Entities, value objects, repository interfaces
├── Http/                            # Controllers, request handling
├── Infrastructure/                  # Repository implementations, external integrations
├── Views/                           # Module-specific view templates
└── [Module]ServiceProvider.php      # DI container registration

src/backend/
├── Controllers/                     # MVC Controllers
├── Services/                        # Business logic layer
├── Views/                           # PHP templates organized by feature
├── Router/                          # URL routing (Router.php, routes.php)
├── Api/V1/                          # REST API handlers
│   ├── Handlers/                    # Endpoint handlers by resource
│   ├── ApiV1.php                    # Main API router
│   └── Endpoints.php                # Endpoint registry
├── Core/                            # Core utilities
│   ├── Bootstrap/                   # App initialization (EnvLoader, db_bootstrap)
│   ├── Entity/                      # Domain entities (Language, Term, Text)
│   ├── Export/                      # Export functionality (Anki, TSV)
│   └── Globals.php                  # Type-safe global state access
└── View/Helper/                     # StatusHelper (business logic dependency)

src/frontend/
├── js/                              # TypeScript source (built with Vite)
│   ├── main.ts                      # Entry point
│   ├── types/                       # TypeScript declarations
│   └── *.ts                         # Feature modules
└── css/
    ├── base/                        # Core styles
    └── themes/                      # Theme overrides
```

### Database Architecture

Key tables (InnoDB engine):

- `languages` - Language configurations (parsing rules, dictionaries)
- `texts` / `archivedtexts` - User texts for reading
- `words` - User vocabulary with status tracking
- `sentences` - Parsed sentences from texts
- `textitems2` - Word occurrences linking words to sentences
- `settings` - Application settings (key-value pairs)

**Word Status Values:** 1-5 (learning stages), 98 (ignored), 99 (well-known)

### Global State Access

Use `Lwt\Core\Globals` class instead of PHP globals:

```php
use Lwt\Core\Globals;

// Database operations
$db = Globals::getDbConnection();
$tableName = Globals::table('words');  // Returns table name

// Query builder
$words = Globals::query('words')->where('WoLgID', '=', 1)->get();

// User context (for multi-user mode)
$userId = Globals::getCurrentUserId();
$userId = Globals::requireUserId();  // Throws if not authenticated
```

### REST API

Base URL: `/api/v1` (also supports legacy `/api.php/v1`)

Key endpoint groups (see `src/backend/Api/V1/Endpoints.php` for full list):

- `languages` - Language CRUD and definitions
- `texts` - Text management and statistics
- `terms` - Vocabulary CRUD, status changes, bulk operations
- `feeds` - RSS feed management
- `review` - Spaced repetition test interface
- `settings` - Application configuration
- `tags` - Term and text tagging

## Working with the Codebase

### Creating New Features

1. **Add route** in `src/backend/Router/routes.php`
2. **Create/extend controller** in `src/backend/Controllers/`
3. **Extract business logic** to `src/backend/Services/`
4. **Create view templates** in `src/backend/Views/[Feature]/`

### Modifying PHP Code

- Controllers extend `BaseController` which provides helper methods for input validation, rendering, and database access
- Use prepared statements for database queries: `Connection::preparedFetchAll($sql, [$param1, $param2])`
- Use `Globals::table('tablename')` for table names
- Use `getSettingWithDefault()` for application settings
- Use `InputValidator` for request parameter validation (accessed via `$this->param()`, `$this->paramInt()` in controllers)

**Key Namespaces:**
- Database: `Lwt\Shared\Infrastructure\Database\{Connection, DB, QueryBuilder}`
- HTTP: `Lwt\Shared\Infrastructure\Http\{InputValidator, SecurityHeaders}`
- Container: `Lwt\Shared\Infrastructure\Container\Container`
- UI Helpers: `Lwt\Shared\UI\Helpers\{FormHelper, PageLayoutHelper, IconHelper}`

### Modifying TypeScript

1. Edit files in `src/frontend/js/*.ts`
2. Run `npm run dev` for HMR during development
3. Run `npm run typecheck` before committing
4. Run `npm run build` to generate production bundles

Key modules:

- `pgm.ts` - Main program logic and utilities
- `text_events.ts` - Text reading interface
- `audio_controller.ts` - Audio playback
- `translation_api.ts` - Translation integration

### Creating/Editing Themes

1. Create folder `src/frontend/css/themes/your-theme/`
2. Add CSS files (missing files fall back to `base/` defaults)
3. Run `npm run build:themes` to generate minified themes

## Important Conventions

- **Character Encoding:** UTF-8 throughout
- **Namespaces:** PSR-4 autoloading with `Lwt\` prefix
- **ID Columns:** `LgID` (language), `TxID`/`AtID` (text/archived), `WoID` (word)
- **Database Queries:** Prefer `Connection::preparedFetchAll()` and `Connection::preparedExecute()` over manual escaping

## Database Migrations

Migration files in `db/migrations/` with format `YYYYMMDD_HHMMSS_description.sql`. The `_migrations` table tracks applied migrations.

## Contributing Workflow

Branches:

- `main` - Stable releases
- `dev` - Development branch

Before committing:

1. Run `composer test` and `./vendor/bin/psalm`
2. Run `npm run typecheck` and `npm run lint`
3. If you modified frontend assets, run `npm run build:all`

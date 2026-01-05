<?php declare(strict_types=1);
/**
 * LWT Route Configuration
 *
 * This file defines all routes for the application.
 * Routes map URL paths to controller methods.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Router
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0 All routes now use controller methods
 */

namespace Lwt\Router;

use Lwt\Router\Middleware\AuthMiddleware;

require_once __DIR__ . '/Middleware/AuthMiddleware.php';

/**
 * Auth middleware for protected routes.
 *
 * @var array<string>
 */
const AUTH_MIDDLEWARE = [AuthMiddleware::class];

/**
 * Register all application routes.
 *
 * Routes are organized into:
 * - Public routes: No authentication required (login, register, etc.)
 * - Protected routes: Require user authentication
 *
 * @param Router $router The router instance to register routes with
 *
 * @return void
 */
function registerRoutes(Router $router): void
{
    // ==================== HOME PAGE (PROTECTED) ====================

    $router->registerWithMiddleware('/', 'Lwt\\Modules\\Home\\Http\\HomeController@index', AUTH_MIDDLEWARE);
    $router->registerWithMiddleware('/index.php', 'Lwt\\Modules\\Home\\Http\\HomeController@index', AUTH_MIDDLEWARE);

    // ==================== TEXT ROUTES (PROTECTED) ====================

    // Read text (Alpine.js - client-side rendering)
    $router->registerWithMiddleware('/text/read', 'Lwt\\Modules\\Text\\Http\\TextController@read', AUTH_MIDDLEWARE);

    // Empty iframe placeholder (used in text read, test, and word pages)
    // These are static files, no auth needed
    $router->register('/empty.html', 'src/backend/Core/empty.html');
    $router->register('/text/empty.html', 'src/backend/Core/empty.html');
    $router->register('/test/empty.html', 'src/backend/Core/empty.html');
    $router->register('/word/empty.html', 'src/backend/Core/empty.html');

    // Edit texts
    $router->registerWithMiddleware('/text/edit', 'Lwt\\Modules\\Text\\Http\\TextController@edit', AUTH_MIDDLEWARE);
    $router->registerWithMiddleware('/texts', 'Lwt\\Modules\\Text\\Http\\TextController@edit', AUTH_MIDDLEWARE);

    // Display improved text
    $router->registerWithMiddleware('/text/display', 'Lwt\\Modules\\Text\\Http\\TextController@display', AUTH_MIDDLEWARE);

    // Print text (TextPrintController)
    $router->registerWithMiddleware('/text/print', 'TextPrintController@printAnnotated', AUTH_MIDDLEWARE);
    $router->registerWithMiddleware('/text/print-plain', 'TextPrintController@printPlain', AUTH_MIDDLEWARE);

    // Import long text
    $router->registerWithMiddleware('/text/import-long', 'Lwt\\Modules\\Text\\Http\\TextController@importLong', AUTH_MIDDLEWARE);

    // Set text mode
    $router->registerWithMiddleware('/text/set-mode', 'Lwt\\Modules\\Text\\Http\\TextController@setMode', AUTH_MIDDLEWARE);

    // Check text
    $router->registerWithMiddleware('/text/check', 'Lwt\\Modules\\Text\\Http\\TextController@check', AUTH_MIDDLEWARE);

    // Archived texts
    $router->registerWithMiddleware('/text/archived', 'Lwt\\Modules\\Text\\Http\\TextController@archived', AUTH_MIDDLEWARE);

    // ==================== WORD/TERM ROUTES (PROTECTED) ====================

    // Edit word
    $router->registerWithMiddleware('/word/edit', 'WordController@edit', AUTH_MIDDLEWARE);

    // Edit term while testing
    $router->registerWithMiddleware('/word/edit-term', 'WordController@editTerm', AUTH_MIDDLEWARE);

    // Edit words (list) - Alpine.js SPA version
    $router->registerWithMiddleware('/words/edit', 'WordController@listEditAlpine', AUTH_MIDDLEWARE);

    // Edit multi-word
    $router->registerWithMiddleware('/word/edit-multi', 'WordController@editMulti', AUTH_MIDDLEWARE);

    // Delete word
    // @deprecated 3.0.0 Use DELETE /api/v1/terms/{id} instead.
    //             Kept for backward compatibility with frame-based mode.
    $router->registerWithMiddleware('/word/delete', 'WordController@delete', AUTH_MIDDLEWARE);

    // All words (list view) - Alpine.js SPA version
    $router->registerWithMiddleware('/words', 'WordController@listEditAlpine', AUTH_MIDDLEWARE);

    // New word
    $router->registerWithMiddleware('/word/new', 'WordController@create', AUTH_MIDDLEWARE);

    // Show word
    $router->registerWithMiddleware('/word/show', 'WordController@show', AUTH_MIDDLEWARE);

    // Insert word (wellknown/ignore)
    // @deprecated 3.0.0 Use POST /api/v1/terms/quick with status=99 instead.
    //             Kept for backward compatibility with frame-based mode.
    $router->registerWithMiddleware('/word/insert-wellknown', 'WordController@insertWellknown', AUTH_MIDDLEWARE);
    // @deprecated 3.0.0 Use POST /api/v1/terms/quick with status=98 instead.
    //             Kept for backward compatibility with frame-based mode.
    $router->registerWithMiddleware('/word/insert-ignore', 'WordController@insertIgnore', AUTH_MIDDLEWARE);

    // Inline edit
    $router->registerWithMiddleware('/word/inline-edit', 'WordController@inlineEdit', AUTH_MIDDLEWARE);

    // Bulk translate
    $router->registerWithMiddleware('/word/bulk-translate', 'WordController@bulkTranslate', AUTH_MIDDLEWARE);

    // Create term from hover (Vocabulary module)
    $router->registerWithMiddleware(
        '/vocabulary/term-hover',
        'Lwt\\Modules\\Vocabulary\\Http\\VocabularyController@hoverCreate',
        AUTH_MIDDLEWARE
    );

    // Vocabulary module routes (new modular architecture)
    $router->registerWithMiddleware(
        '/vocabulary/similar-terms',
        'Lwt\\Modules\\Vocabulary\\Http\\VocabularyController@similarTerms',
        AUTH_MIDDLEWARE
    );

    // Vocabulary JSON API routes (for AJAX calls)
    $router->registerWithMiddleware(
        '/vocabulary/term',
        'Lwt\\Modules\\Vocabulary\\Http\\VocabularyController@getTermJson',
        AUTH_MIDDLEWARE,
        'GET'
    );
    $router->registerWithMiddleware(
        '/vocabulary/term',
        'Lwt\\Modules\\Vocabulary\\Http\\VocabularyController@createJson',
        AUTH_MIDDLEWARE,
        'POST'
    );
    $router->registerWithMiddleware(
        '/vocabulary/term',
        'Lwt\\Modules\\Vocabulary\\Http\\VocabularyController@updateJson',
        AUTH_MIDDLEWARE,
        'PUT'
    );
    $router->registerWithMiddleware(
        '/vocabulary/term/status',
        'Lwt\\Modules\\Vocabulary\\Http\\VocabularyController@updateStatus',
        AUTH_MIDDLEWARE,
        'PUT'
    );
    $router->registerWithMiddleware(
        '/vocabulary/term/delete',
        'Lwt\\Modules\\Vocabulary\\Http\\VocabularyController@delete',
        AUTH_MIDDLEWARE,
        'POST'
    );

    // Set word status
    // @deprecated 3.0.0 Use PUT /api/v1/terms/{id}/status/{status} instead.
    //             Kept for backward compatibility with frame-based mode.
    $router->registerWithMiddleware('/word/set-status', 'WordController@setStatus', AUTH_MIDDLEWARE);

    // Set all words status (wellknown/ignore)
    $router->registerWithMiddleware('/word/set-all-status', 'WordController@all', AUTH_MIDDLEWARE);

    // Upload words
    $router->registerWithMiddleware('/word/upload', 'WordController@upload', AUTH_MIDDLEWARE);

    // ==================== TEST ROUTES (PROTECTED) ====================

    // Test interface (Review module)
    $router->registerWithMiddleware('/test', 'Lwt\\Modules\\Review\\Http\\TestController@index', AUTH_MIDDLEWARE);

    // ==================== LANGUAGE ROUTES (PROTECTED) ====================

    // Edit languages (Language module)
    $router->registerWithMiddleware('/languages', 'Lwt\\Modules\\Language\\Http\\LanguageController@index', AUTH_MIDDLEWARE);

    // ==================== TAG ROUTES (PROTECTED) ====================

    // Term tags (Tags module)
    $router->registerWithMiddleware('/tags', 'Lwt\\Modules\\Tags\\Http\\TermTagController@index', AUTH_MIDDLEWARE);

    // Text tags (Tags module)
    $router->registerWithMiddleware('/tags/text', 'Lwt\\Modules\\Tags\\Http\\TextTagController@index', AUTH_MIDDLEWARE);

    // ==================== FEED ROUTES (PROTECTED) ====================

    // Feeds SPA (new Alpine.js single page application)
    $router->registerWithMiddleware('/feeds/manage', 'FeedsController@spa', AUTH_MIDDLEWARE);

    // Feeds list (legacy)
    $router->registerWithMiddleware('/feeds', 'FeedsController@index', AUTH_MIDDLEWARE);

    // Edit feeds
    $router->registerWithMiddleware('/feeds/edit', 'FeedsController@edit', AUTH_MIDDLEWARE);

    // Feed wizard
    $router->registerWithMiddleware('/feeds/wizard', 'FeedsController@wizard', AUTH_MIDDLEWARE);

    // ==================== LOCAL DICTIONARY ROUTES (PROTECTED) ====================

    // Dictionaries list
    $router->registerWithMiddleware('/dictionaries', 'LocalDictionaryController@index', AUTH_MIDDLEWARE);

    // Import wizard
    $router->registerWithMiddleware('/dictionaries/import', 'LocalDictionaryController@import', AUTH_MIDDLEWARE, 'GET');
    $router->registerWithMiddleware('/dictionaries/import', 'LocalDictionaryController@processImport', AUTH_MIDDLEWARE, 'POST');

    // Delete dictionary
    $router->registerWithMiddleware('/dictionaries/delete', 'LocalDictionaryController@delete', AUTH_MIDDLEWARE, 'POST');

    // Preview (AJAX)
    $router->registerWithMiddleware('/dictionaries/preview', 'LocalDictionaryController@preview', AUTH_MIDDLEWARE, 'POST');

    // ==================== ADMIN ROUTES (PROTECTED) ====================

    // Backup & Restore (Admin module)
    $router->registerWithMiddleware('/admin/backup', 'Lwt\\Modules\\Admin\\Http\\AdminController@backup', AUTH_MIDDLEWARE);

    // Database Wizard (Admin module)
    $router->registerWithMiddleware('/admin/wizard', 'Lwt\\Modules\\Admin\\Http\\AdminController@wizard', AUTH_MIDDLEWARE);

    // Statistics (Admin module)
    $router->registerWithMiddleware('/admin/statistics', 'Lwt\\Modules\\Admin\\Http\\AdminController@statistics', AUTH_MIDDLEWARE);

    // Install Demo (Admin module)
    $router->registerWithMiddleware('/admin/install-demo', 'Lwt\\Modules\\Admin\\Http\\AdminController@installDemo', AUTH_MIDDLEWARE);

    // Settings (Admin module)
    $router->registerWithMiddleware('/admin/settings', 'Lwt\\Modules\\Admin\\Http\\AdminController@settings', AUTH_MIDDLEWARE);

    // Settings hover - deprecated, use /vocabulary/term-hover instead
    // @deprecated 3.0.0 Use /vocabulary/term-hover instead
    $router->registerWithMiddleware('/admin/settings/hover', 'Lwt\\Modules\\Admin\\Http\\AdminController@settingsHover', AUTH_MIDDLEWARE);

    // Server data (Admin module)
    $router->registerWithMiddleware('/admin/server-data', 'Lwt\\Modules\\Admin\\Http\\AdminController@serverData', AUTH_MIDDLEWARE);

    // Save setting and redirect (Admin module)
    $router->registerWithMiddleware('/admin/save-setting', 'Lwt\\Modules\\Admin\\Http\\AdminController@saveSetting', AUTH_MIDDLEWARE);

    // ==================== AUTHENTICATION ROUTES (PUBLIC) ====================

    // Login - no auth required
    $router->register('/login', 'AuthController@loginForm', 'GET');
    $router->register('/login', 'AuthController@login', 'POST');

    // Registration - no auth required
    $router->register('/register', 'AuthController@registerForm', 'GET');
    $router->register('/register', 'AuthController@register', 'POST');

    // Logout - technically needs auth but handles gracefully if not
    $router->register('/logout', 'AuthController@logout');

    // ==================== WORDPRESS INTEGRATION (PUBLIC) ====================

    // WordPress routes are public - they handle their own auth via WP tokens
    $router->register('/wordpress/start', 'WordPressController@start');
    $router->register('/wordpress/stop', 'WordPressController@stop');

    // ==================== API ROUTES ====================

    // Main API - use prefix to catch all sub-paths
    // Note: API handles its own authentication internally via ApiV1::validateAuth()
    // This allows /api/v1/auth/* endpoints to be public while protecting others
    // Support both /api/v1 (new) and /api.php/v1 (legacy) paths
    $router->registerPrefix('/api/v1', 'ApiController@v1');
    $router->registerPrefix('/api.php/v1', 'ApiController@v1');

    // Translation APIs (PROTECTED) - used by authenticated users
    $router->registerWithMiddleware('/api/translate', 'ApiController@translate', AUTH_MIDDLEWARE);
    $router->registerWithMiddleware('/api/google', 'ApiController@google', AUTH_MIDDLEWARE);
    $router->registerWithMiddleware('/api/glosbe', 'ApiController@glosbe', AUTH_MIDDLEWARE);
}

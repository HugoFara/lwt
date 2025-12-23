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

    $router->registerWithMiddleware('/', 'HomeController@index', AUTH_MIDDLEWARE);
    $router->registerWithMiddleware('/index.php', 'HomeController@index', AUTH_MIDDLEWARE);

    // ==================== TEXT ROUTES (PROTECTED) ====================

    // Read text (Alpine.js - client-side rendering)
    $router->registerWithMiddleware('/text/read', 'TextController@read', AUTH_MIDDLEWARE);

    // Empty iframe placeholder (used in text read, test, and word pages)
    // These are static files, no auth needed
    $router->register('/empty.html', 'src/backend/Core/empty.html');
    $router->register('/text/empty.html', 'src/backend/Core/empty.html');
    $router->register('/test/empty.html', 'src/backend/Core/empty.html');
    $router->register('/word/empty.html', 'src/backend/Core/empty.html');

    // Edit texts
    $router->registerWithMiddleware('/text/edit', 'TextController@edit', AUTH_MIDDLEWARE);
    $router->registerWithMiddleware('/texts', 'TextController@edit', AUTH_MIDDLEWARE);

    // Display improved text
    $router->registerWithMiddleware('/text/display', 'TextController@display', AUTH_MIDDLEWARE);

    // Print text (TextPrintController)
    $router->registerWithMiddleware('/text/print', 'TextPrintController@printAnnotated', AUTH_MIDDLEWARE);
    $router->registerWithMiddleware('/text/print-plain', 'TextPrintController@printPlain', AUTH_MIDDLEWARE);

    // Import long text
    $router->registerWithMiddleware('/text/import-long', 'TextController@importLong', AUTH_MIDDLEWARE);

    // Set text mode
    $router->registerWithMiddleware('/text/set-mode', 'TextController@setMode', AUTH_MIDDLEWARE);

    // Check text
    $router->registerWithMiddleware('/text/check', 'TextController@check', AUTH_MIDDLEWARE);

    // Archived texts
    $router->registerWithMiddleware('/text/archived', 'TextController@archived', AUTH_MIDDLEWARE);

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

    // Set word status
    // @deprecated 3.0.0 Use PUT /api/v1/terms/{id}/status/{status} instead.
    //             Kept for backward compatibility with frame-based mode.
    $router->registerWithMiddleware('/word/set-status', 'WordController@setStatus', AUTH_MIDDLEWARE);

    // Set all words status (wellknown/ignore)
    $router->registerWithMiddleware('/word/set-all-status', 'WordController@all', AUTH_MIDDLEWARE);

    // Upload words
    $router->registerWithMiddleware('/word/upload', 'WordController@upload', AUTH_MIDDLEWARE);

    // ==================== TEST ROUTES (PROTECTED) ====================

    // Test interface
    $router->registerWithMiddleware('/test', 'TestController@index', AUTH_MIDDLEWARE);

    // ==================== LANGUAGE ROUTES (PROTECTED) ====================

    // Edit languages
    $router->registerWithMiddleware('/languages', 'LanguageController@index', AUTH_MIDDLEWARE);

    // ==================== TAG ROUTES (PROTECTED) ====================

    // Term tags (TermTagsController - extends AbstractCrudController)
    $router->registerWithMiddleware('/tags', 'TermTagsController@index', AUTH_MIDDLEWARE);

    // Text tags (TextTagsController - extends AbstractCrudController)
    $router->registerWithMiddleware('/tags/text', 'TextTagsController@index', AUTH_MIDDLEWARE);

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

    // Backup & Restore
    $router->registerWithMiddleware('/admin/backup', 'AdminController@backup', AUTH_MIDDLEWARE);

    // Database Wizard
    $router->registerWithMiddleware('/admin/wizard', 'AdminController@wizard', AUTH_MIDDLEWARE);

    // Statistics
    $router->registerWithMiddleware('/admin/statistics', 'AdminController@statistics', AUTH_MIDDLEWARE);

    // Install Demo
    $router->registerWithMiddleware('/admin/install-demo', 'AdminController@installDemo', AUTH_MIDDLEWARE);

    // Settings
    $router->registerWithMiddleware('/admin/settings', 'AdminController@settings', AUTH_MIDDLEWARE);
    $router->registerWithMiddleware('/admin/settings/hover', 'AdminController@settingsHover', AUTH_MIDDLEWARE);

    // Server data
    $router->registerWithMiddleware('/admin/server-data', 'AdminController@serverData', AUTH_MIDDLEWARE);

    // Save setting and redirect
    $router->registerWithMiddleware('/admin/save-setting', 'AdminController@saveSetting', AUTH_MIDDLEWARE);

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

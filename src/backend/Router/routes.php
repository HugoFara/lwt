<?php

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

declare(strict_types=1);

namespace Lwt\Router;

use Lwt\Router\Middleware\AuthMiddleware;
use Lwt\Router\Middleware\AdminMiddleware;
use Lwt\Router\Middleware\CsrfMiddleware;

/**
 * Auth middleware for protected routes.
 * Includes CSRF protection for state-changing requests (POST, PUT, DELETE).
 *
 * @var array<string>
 */
const AUTH_MIDDLEWARE = [AuthMiddleware::class, CsrfMiddleware::class];

/**
 * Admin middleware for admin-only routes.
 * Requires authentication AND admin role, plus CSRF protection.
 *
 * @var array<string>
 */
const ADMIN_MIDDLEWARE = [AdminMiddleware::class, CsrfMiddleware::class];

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
    // New RESTful route: /text/123/read
    $router->get('/text/{text:int}/read', 'Lwt\\Modules\\Text\\Http\\TextController@read', AUTH_MIDDLEWARE);
    // Legacy route: /text/read?text=123
    $router->get('/text/read', 'Lwt\\Modules\\Text\\Http\\TextController@read', AUTH_MIDDLEWARE);

    // New text form (RESTful route)
    $router->get('/texts/new', 'Lwt\\Modules\\Text\\Http\\TextController@new', AUTH_MIDDLEWARE);
    $router->post('/texts/new', 'Lwt\\Modules\\Text\\Http\\TextController@new', AUTH_MIDDLEWARE);

    // Edit text form (RESTful route): /texts/123/edit
    $router->get('/texts/{id:int}/edit', 'Lwt\\Modules\\Text\\Http\\TextController@editSingle', AUTH_MIDDLEWARE);
    $router->post('/texts/{id:int}/edit', 'Lwt\\Modules\\Text\\Http\\TextController@editSingle', AUTH_MIDDLEWARE);

    // Delete text (RESTful route): DELETE /texts/123
    $router->delete('/texts/{id:int}', 'Lwt\\Modules\\Text\\Http\\TextController@delete', AUTH_MIDDLEWARE);

    // Archive text (RESTful route): POST /texts/123/archive
    $router->post('/texts/{id:int}/archive', 'Lwt\\Modules\\Text\\Http\\TextController@archive', AUTH_MIDDLEWARE);

    // Unarchive text (RESTful route): POST /texts/123/unarchive
    $router->post('/texts/{id:int}/unarchive', 'Lwt\\Modules\\Text\\Http\\TextController@unarchive', AUTH_MIDDLEWARE);

    // Texts list and legacy edit routes
    $router->registerWithMiddleware('/text/edit', 'Lwt\\Modules\\Text\\Http\\TextController@edit', AUTH_MIDDLEWARE);
    $router->registerWithMiddleware('/texts', 'Lwt\\Modules\\Text\\Http\\TextController@edit', AUTH_MIDDLEWARE);

    // Display improved text
    // New RESTful route: /text/123/display
    $router->get('/text/{text:int}/display', 'Lwt\\Modules\\Text\\Http\\TextController@display', AUTH_MIDDLEWARE);
    // Legacy route: /text/display?text=123
    $router->get('/text/display', 'Lwt\\Modules\\Text\\Http\\TextController@display', AUTH_MIDDLEWARE);

    // Print text (TextPrintController from Text module)
    // RESTful route: /text/123/print
    $router->get('/text/{text:int}/print', 'Lwt\\Modules\\Text\\Http\\TextPrintController@printAnnotated', AUTH_MIDDLEWARE);
    // RESTful route: /text/123/print/edit
    $router->get('/text/{text:int}/print/edit', 'Lwt\\Modules\\Text\\Http\\TextPrintController@editAnnotation', AUTH_MIDDLEWARE);
    // RESTful route: DELETE /text/123/annotation
    $router->delete('/text/{text:int}/annotation', 'Lwt\\Modules\\Text\\Http\\TextPrintController@deleteAnnotation', AUTH_MIDDLEWARE);
    // RESTful route: /text/123/print-plain
    $router->get('/text/{text:int}/print-plain', 'Lwt\\Modules\\Text\\Http\\TextPrintController@printPlain', AUTH_MIDDLEWARE);
    // Legacy route: /text/print-plain?text=123
    $router->registerWithMiddleware(
        '/text/print-plain',
        'Lwt\\Modules\\Text\\Http\\TextPrintController@printPlain',
        AUTH_MIDDLEWARE
    );

    // Set text mode
    $router->registerWithMiddleware(
        '/text/set-mode',
        'Lwt\\Modules\\Text\\Http\\TextController@setMode',
        AUTH_MIDDLEWARE
    );

    // Check text
    $router->registerWithMiddleware('/text/check', 'Lwt\\Modules\\Text\\Http\\TextController@check', AUTH_MIDDLEWARE);

    // Archived texts
    $router->registerWithMiddleware(
        '/text/archived',
        'Lwt\\Modules\\Text\\Http\\TextController@archived',
        AUTH_MIDDLEWARE
    );

    // Edit archived text (RESTful route): /text/archived/123/edit
    $router->get('/text/archived/{id:int}/edit', 'Lwt\\Modules\\Text\\Http\\TextController@archivedEdit', AUTH_MIDDLEWARE);
    $router->post('/text/archived/{id:int}/edit', 'Lwt\\Modules\\Text\\Http\\TextController@archivedEdit', AUTH_MIDDLEWARE);

    // Delete archived text (RESTful route): DELETE /text/archived/123
    $router->delete('/text/archived/{id:int}', 'Lwt\\Modules\\Text\\Http\\TextController@deleteArchived', AUTH_MIDDLEWARE);

    // ==================== WORD/TERM ROUTES (PROTECTED) ====================
    // Split into focused controllers: TermEditController, TermDisplayController,
    // TermStatusController, TermApiController, TermImportController, MultiWordController

    // Edit word (TermEditController)
    $router->registerWithMiddleware(
        '/word/edit',
        'Lwt\\Modules\\Vocabulary\\Http\\TermEditController@editWord',
        AUTH_MIDDLEWARE
    );

    // Edit term while testing (TermEditController)
    $router->registerWithMiddleware(
        '/word/edit-term',
        'Lwt\\Modules\\Vocabulary\\Http\\TermEditController@editTerm',
        AUTH_MIDDLEWARE
    );

    // Edit single word form (RESTful route): /words/123/edit
    $router->get('/words/{id:int}/edit', 'Lwt\\Modules\\Vocabulary\\Http\\TermEditController@editWordById', AUTH_MIDDLEWARE);
    $router->post('/words/{id:int}/edit', 'Lwt\\Modules\\Vocabulary\\Http\\TermEditController@editWordById', AUTH_MIDDLEWARE);

    // Delete word (RESTful route): DELETE /words/123
    $router->delete('/words/{id:int}', 'Lwt\\Modules\\Vocabulary\\Http\\TermEditController@deleteWord', AUTH_MIDDLEWARE);

    // Words list - Alpine.js SPA version (TermDisplayController)
    $router->registerWithMiddleware(
        '/words/edit',
        'Lwt\\Modules\\Vocabulary\\Http\\TermDisplayController@listEditAlpine',
        AUTH_MIDDLEWARE
    );

    // Edit multi-word (MultiWordController)
    $router->registerWithMiddleware(
        '/word/edit-multi',
        'Lwt\\Modules\\Vocabulary\\Http\\MultiWordController@editMulti',
        AUTH_MIDDLEWARE
    );

    // All words (list view) - Alpine.js SPA version (TermDisplayController)
    $router->registerWithMiddleware(
        '/words',
        'Lwt\\Modules\\Vocabulary\\Http\\TermDisplayController@listEditAlpine',
        AUTH_MIDDLEWARE
    );

    // New word (TermEditController)
    // RESTful route: /words/new
    $router->get('/words/new', 'Lwt\\Modules\\Vocabulary\\Http\\TermEditController@createWord', AUTH_MIDDLEWARE);
    // Legacy route: /word/new
    $router->registerWithMiddleware(
        '/word/new',
        'Lwt\\Modules\\Vocabulary\\Http\\TermEditController@createWord',
        AUTH_MIDDLEWARE
    );

    // Show word - new RESTful route with typed parameter (TermDisplayController)
    $router->get('/word/{wid:int}', 'Lwt\\Modules\\Vocabulary\\Http\\TermDisplayController@showWord', AUTH_MIDDLEWARE);
    // Legacy route for backward compatibility
    $router->get('/word/show', 'Lwt\\Modules\\Vocabulary\\Http\\TermDisplayController@showWord', AUTH_MIDDLEWARE);

    // Inline edit (TermEditController)
    $router->registerWithMiddleware(
        '/word/inline-edit',
        'Lwt\\Modules\\Vocabulary\\Http\\TermEditController@inlineEdit',
        AUTH_MIDDLEWARE
    );

    // Bulk translate (TermImportController)
    $router->registerWithMiddleware(
        '/word/bulk-translate',
        'Lwt\\Modules\\Vocabulary\\Http\\TermImportController@bulkTranslate',
        AUTH_MIDDLEWARE
    );

    // Create term from hover (TermDisplayController)
    $router->registerWithMiddleware(
        '/vocabulary/term-hover',
        'Lwt\\Modules\\Vocabulary\\Http\\TermDisplayController@hoverCreate',
        AUTH_MIDDLEWARE
    );

    // Similar terms lookup (TermDisplayController)
    $router->registerWithMiddleware(
        '/vocabulary/similar-terms',
        'Lwt\\Modules\\Vocabulary\\Http\\TermDisplayController@similarTerms',
        AUTH_MIDDLEWARE
    );

    // Vocabulary JSON API routes (TermApiController)
    $router->registerWithMiddleware(
        '/vocabulary/term',
        'Lwt\\Modules\\Vocabulary\\Http\\TermApiController@getTermJson',
        AUTH_MIDDLEWARE,
        'GET'
    );
    $router->registerWithMiddleware(
        '/vocabulary/term',
        'Lwt\\Modules\\Vocabulary\\Http\\TermApiController@createJson',
        AUTH_MIDDLEWARE,
        'POST'
    );
    $router->registerWithMiddleware(
        '/vocabulary/term',
        'Lwt\\Modules\\Vocabulary\\Http\\TermApiController@updateJson',
        AUTH_MIDDLEWARE,
        'PUT'
    );
    // Update term status (TermStatusController)
    // New RESTful route: PUT /vocabulary/term/123/status
    $router->put(
        '/vocabulary/term/{wid:int}/status',
        'Lwt\\Modules\\Vocabulary\\Http\\TermStatusController@updateStatus',
        AUTH_MIDDLEWARE
    );
    // Legacy route: PUT /vocabulary/term/status?wid=123
    $router->put(
        '/vocabulary/term/status',
        'Lwt\\Modules\\Vocabulary\\Http\\TermStatusController@updateStatus',
        AUTH_MIDDLEWARE
    );
    // Delete term (TermApiController)
    $router->registerWithMiddleware(
        '/vocabulary/term/delete',
        'Lwt\\Modules\\Vocabulary\\Http\\TermApiController@delete',
        AUTH_MIDDLEWARE,
        'POST'
    );

    // Set all words status (wellknown/ignore) (TermStatusController)
    $router->registerWithMiddleware(
        '/word/set-all-status',
        'Lwt\\Modules\\Vocabulary\\Http\\TermStatusController@markAllWords',
        AUTH_MIDDLEWARE
    );

    // Upload words (TermImportController)
    $router->registerWithMiddleware(
        '/word/upload',
        'Lwt\\Modules\\Vocabulary\\Http\\TermImportController@upload',
        AUTH_MIDDLEWARE
    );

    // Legacy PHP endpoint replacements (iframe-based status changes)
    // These render HTML responses for display in iframes during text reading
    // Status controllers (TermStatusController)
    $router->registerWithMiddleware(
        '/word/set-status',
        'Lwt\\Modules\\Vocabulary\\Http\\TermStatusController@setWordStatusView',
        AUTH_MIDDLEWARE
    );
    $router->registerWithMiddleware(
        '/word/set-review-status',
        'Lwt\\Modules\\Vocabulary\\Http\\TermStatusController@setReviewStatusView',
        AUTH_MIDDLEWARE
    );
    // Delete controllers
    $router->registerWithMiddleware(
        '/word/delete-term',
        'Lwt\\Modules\\Vocabulary\\Http\\TermEditController@deleteWordView',
        AUTH_MIDDLEWARE
    );
    $router->registerWithMiddleware(
        '/word/delete-multi',
        'Lwt\\Modules\\Vocabulary\\Http\\MultiWordController@deleteMultiWordView',
        AUTH_MIDDLEWARE
    );
    // Insert with status (TermStatusController)
    $router->registerWithMiddleware(
        '/word/insert-wellknown',
        'Lwt\\Modules\\Vocabulary\\Http\\TermStatusController@insertWellknown',
        AUTH_MIDDLEWARE
    );
    $router->registerWithMiddleware(
        '/word/insert-ignore',
        'Lwt\\Modules\\Vocabulary\\Http\\TermStatusController@insertIgnore',
        AUTH_MIDDLEWARE
    );

    // ==================== REVIEW ROUTES (PROTECTED) ====================

    // Review interface (Review module)
    $router->registerWithMiddleware('/review', 'Lwt\\Modules\\Review\\Http\\ReviewController@index', AUTH_MIDDLEWARE);

    // ==================== LANGUAGE ROUTES (PROTECTED) ====================

    // New language form (RESTful route): GET/POST /languages/new
    $router->get('/languages/new', 'Lwt\\Modules\\Language\\Http\\LanguageController@new', AUTH_MIDDLEWARE);
    $router->post('/languages/new', 'Lwt\\Modules\\Language\\Http\\LanguageController@new', AUTH_MIDDLEWARE);

    // Edit language form (RESTful route): /languages/123/edit
    $router->get('/languages/{id:int}/edit', 'Lwt\\Modules\\Language\\Http\\LanguageController@edit', AUTH_MIDDLEWARE);
    $router->post('/languages/{id:int}/edit', 'Lwt\\Modules\\Language\\Http\\LanguageController@edit', AUTH_MIDDLEWARE);

    // Delete language (RESTful route): DELETE /languages/123
    $router->delete(
        '/languages/{id:int}',
        'Lwt\\Modules\\Language\\Http\\LanguageController@delete',
        AUTH_MIDDLEWARE
    );

    // Refresh (reparse) language texts (RESTful route): POST /languages/123/refresh
    $router->post(
        '/languages/{id:int}/refresh',
        'Lwt\\Modules\\Language\\Http\\LanguageController@refresh',
        AUTH_MIDDLEWARE
    );

    // Languages list (Language module)
    $router->registerWithMiddleware(
        '/languages',
        'Lwt\\Modules\\Language\\Http\\LanguageController@index',
        AUTH_MIDDLEWARE
    );

    // ==================== TAG ROUTES (PROTECTED) ====================

    // Term tags (Tags module)
    $router->get('/tags/new', 'Lwt\\Modules\\Tags\\Http\\TermTagController@new', AUTH_MIDDLEWARE);

    // Edit term tag (RESTful route): GET/POST /tags/123/edit
    $router->get('/tags/{id:int}/edit', 'Lwt\\Modules\\Tags\\Http\\TermTagController@edit', AUTH_MIDDLEWARE);
    $router->post('/tags/{id:int}/edit', 'Lwt\\Modules\\Tags\\Http\\TermTagController@edit', AUTH_MIDDLEWARE);

    // Delete term tag (RESTful route): DELETE /tags/123
    $router->delete('/tags/{id:int}', 'Lwt\\Modules\\Tags\\Http\\TermTagController@delete', AUTH_MIDDLEWARE);

    $router->registerWithMiddleware('/tags', 'Lwt\\Modules\\Tags\\Http\\TermTagController@index', AUTH_MIDDLEWARE);

    // Text tags (Tags module)
    $router->get('/tags/text/new', 'Lwt\\Modules\\Tags\\Http\\TextTagController@new', AUTH_MIDDLEWARE);

    // Edit text tag (RESTful route): GET/POST /tags/text/123/edit
    $router->get('/tags/text/{id:int}/edit', 'Lwt\\Modules\\Tags\\Http\\TextTagController@edit', AUTH_MIDDLEWARE);
    $router->post('/tags/text/{id:int}/edit', 'Lwt\\Modules\\Tags\\Http\\TextTagController@edit', AUTH_MIDDLEWARE);

    // Delete text tag (RESTful route): DELETE /tags/text/123
    $router->delete('/tags/text/{id:int}', 'Lwt\\Modules\\Tags\\Http\\TextTagController@delete', AUTH_MIDDLEWARE);

    $router->registerWithMiddleware('/tags/text', 'Lwt\\Modules\\Tags\\Http\\TextTagController@index', AUTH_MIDDLEWARE);

    // ==================== FEED ROUTES (PROTECTED) ====================

    // Feeds SPA (new Alpine.js single page application)
    $router->registerWithMiddleware('/feeds/manage', 'Lwt\\Modules\\Feed\\Http\\FeedController@spa', AUTH_MIDDLEWARE);

    // New feed form (RESTful route)
    $router->get('/feeds/new', 'Lwt\\Modules\\Feed\\Http\\FeedController@newFeed', AUTH_MIDDLEWARE);
    $router->post('/feeds/new', 'Lwt\\Modules\\Feed\\Http\\FeedController@newFeed', AUTH_MIDDLEWARE);

    // Edit feed form (RESTful route): /feeds/123/edit
    $router->get('/feeds/{id:int}/edit', 'Lwt\\Modules\\Feed\\Http\\FeedController@editFeed', AUTH_MIDDLEWARE);
    $router->post('/feeds/{id:int}/edit', 'Lwt\\Modules\\Feed\\Http\\FeedController@editFeed', AUTH_MIDDLEWARE);

    // Delete feed (RESTful route): DELETE /feeds/123
    $router->delete('/feeds/{id:int}', 'Lwt\\Modules\\Feed\\Http\\FeedController@deleteFeed', AUTH_MIDDLEWARE);

    // Load/refresh feed (RESTful route): POST /feeds/123/load
    $router->get('/feeds/{id:int}/load', 'Lwt\\Modules\\Feed\\Http\\FeedController@loadFeedRoute', AUTH_MIDDLEWARE);

    // Multi-load feeds interface (RESTful route)
    $router->get('/feeds/multi-load', 'Lwt\\Modules\\Feed\\Http\\FeedController@multiLoad', AUTH_MIDDLEWARE);

    // Feeds list
    $router->registerWithMiddleware('/feeds', 'Lwt\\Modules\\Feed\\Http\\FeedController@index', AUTH_MIDDLEWARE);

    // Edit feeds (legacy route - handles query params)
    $router->registerWithMiddleware('/feeds/edit', 'Lwt\\Modules\\Feed\\Http\\FeedController@edit', AUTH_MIDDLEWARE);

    // Feed wizard
    $router->registerWithMiddleware(
        '/feeds/wizard',
        'Lwt\\Modules\\Feed\\Http\\FeedWizardController@wizard',
        AUTH_MIDDLEWARE
    );

    // ==================== BOOK ROUTES (PROTECTED) ====================
    // Book module routes for EPUB import and book management

    // Books list
    $router->registerWithMiddleware('/books', 'Lwt\\Modules\\Book\\Http\\BookController@index', AUTH_MIDDLEWARE);

    // Book detail (chapters list)
    $router->get('/book/{id:int}', 'Lwt\\Modules\\Book\\Http\\BookController@show', AUTH_MIDDLEWARE);

    // Import EPUB form and processing
    $router->registerWithMiddleware('/book/import', 'Lwt\\Modules\\Book\\Http\\BookController@import', AUTH_MIDDLEWARE);

    // Delete book
    $router->post('/book/{id:int}/delete', 'Lwt\\Modules\\Book\\Http\\BookController@delete', AUTH_MIDDLEWARE);

    // ==================== LOCAL DICTIONARY ROUTES (PROTECTED) ====================
    // All dictionary routes use DictionaryController from the Dictionary module

    // RESTful routes: /languages/{id}/dictionaries
    $router->get(
        '/languages/{id:int}/dictionaries',
        'Lwt\\Modules\\Dictionary\\Http\\DictionaryController@index',
        AUTH_MIDDLEWARE
    );
    $router->post(
        '/languages/{id:int}/dictionaries',
        'Lwt\\Modules\\Dictionary\\Http\\DictionaryController@index',
        AUTH_MIDDLEWARE
    );
    $router->get(
        '/languages/{id:int}/dictionaries/import',
        'Lwt\\Modules\\Dictionary\\Http\\DictionaryController@import',
        AUTH_MIDDLEWARE
    );
    $router->post(
        '/languages/{id:int}/dictionaries/import',
        'Lwt\\Modules\\Dictionary\\Http\\DictionaryController@processImport',
        AUTH_MIDDLEWARE
    );

    // Legacy routes (with ?lang= query parameter)
    $router->registerWithMiddleware(
        '/dictionaries',
        'Lwt\\Modules\\Dictionary\\Http\\DictionaryController@index',
        AUTH_MIDDLEWARE
    );

    // Import wizard
    $router->registerWithMiddleware(
        '/dictionaries/import',
        'Lwt\\Modules\\Dictionary\\Http\\DictionaryController@import',
        AUTH_MIDDLEWARE,
        'GET'
    );
    $router->registerWithMiddleware(
        '/dictionaries/import',
        'Lwt\\Modules\\Dictionary\\Http\\DictionaryController@processImport',
        AUTH_MIDDLEWARE,
        'POST'
    );

    // Delete dictionary
    $router->registerWithMiddleware(
        '/dictionaries/delete',
        'Lwt\\Modules\\Dictionary\\Http\\DictionaryController@delete',
        AUTH_MIDDLEWARE,
        'POST'
    );

    // Preview (AJAX)
    $router->registerWithMiddleware(
        '/dictionaries/preview',
        'Lwt\\Modules\\Dictionary\\Http\\DictionaryController@preview',
        AUTH_MIDDLEWARE,
        'POST'
    );

    // ==================== ADMIN ROUTES (ADMIN ONLY) ====================
    // These routes require admin role, not just authentication

    // Backup & Restore (Admin module)
    $router->registerWithMiddleware(
        '/admin/backup',
        'Lwt\\Modules\\Admin\\Http\\AdminController@backup',
        ADMIN_MIDDLEWARE
    );

    // Database Wizard (Admin module)
    $router->registerWithMiddleware(
        '/admin/wizard',
        'Lwt\\Modules\\Admin\\Http\\AdminController@wizard',
        ADMIN_MIDDLEWARE
    );

    // Statistics (Admin module) - allow regular users to see statistics
    $router->registerWithMiddleware(
        '/admin/statistics',
        'Lwt\\Modules\\Admin\\Http\\AdminController@statistics',
        AUTH_MIDDLEWARE
    );

    // Install Demo (Admin module)
    $router->registerWithMiddleware(
        '/admin/install-demo',
        'Lwt\\Modules\\Admin\\Http\\AdminController@installDemo',
        ADMIN_MIDDLEWARE
    );

    // Settings (Admin module)
    $router->registerWithMiddleware(
        '/admin/settings',
        'Lwt\\Modules\\Admin\\Http\\AdminController@settings',
        ADMIN_MIDDLEWARE
    );

    // Server data (Admin module)
    $router->registerWithMiddleware(
        '/admin/server-data',
        'Lwt\\Modules\\Admin\\Http\\AdminController@serverData',
        ADMIN_MIDDLEWARE
    );

    // Save setting and redirect (Admin module)
    $router->registerWithMiddleware(
        '/admin/save-setting',
        'Lwt\\Modules\\Admin\\Http\\AdminController@saveSetting',
        ADMIN_MIDDLEWARE
    );

    // ==================== AUTHENTICATION ROUTES (PUBLIC) ====================
    // All auth routes use UserController from the User module

    // Login - no auth required
    $router->register('/login', 'Lwt\\Modules\\User\\Http\\UserController@loginForm', 'GET');
    $router->register('/login', 'Lwt\\Modules\\User\\Http\\UserController@login', 'POST');

    // Registration - no auth required
    $router->register('/register', 'Lwt\\Modules\\User\\Http\\UserController@registerForm', 'GET');
    $router->register('/register', 'Lwt\\Modules\\User\\Http\\UserController@register', 'POST');

    // Logout - technically needs auth but handles gracefully if not
    $router->register('/logout', 'Lwt\\Modules\\User\\Http\\UserController@logout');

    // Password Reset - no auth required
    $router->register('/password/forgot', 'Lwt\\Modules\\User\\Http\\UserController@forgotPasswordForm', 'GET');
    $router->register('/password/forgot', 'Lwt\\Modules\\User\\Http\\UserController@forgotPassword', 'POST');
    $router->register('/password/reset', 'Lwt\\Modules\\User\\Http\\UserController@resetPasswordForm', 'GET');
    $router->register('/password/reset', 'Lwt\\Modules\\User\\Http\\UserController@resetPassword', 'POST');

    // ==================== WORDPRESS INTEGRATION (PUBLIC) ====================

    // WordPress routes are public - they handle their own auth via WP tokens
    $router->register('/wordpress/start', 'Lwt\\Modules\\User\\Http\\WordPressController@start');
    $router->register('/wordpress/stop', 'Lwt\\Modules\\User\\Http\\WordPressController@stop');

    // ==================== GOOGLE OAUTH INTEGRATION (PUBLIC) ====================

    // Google OAuth routes are public - they handle their own auth via OAuth tokens
    $router->register('/google/start', 'Lwt\\Modules\\User\\Http\\GoogleController@start');
    $router->register('/google/callback', 'Lwt\\Modules\\User\\Http\\GoogleController@callback');
    $router->register('/google/link-confirm', 'Lwt\\Modules\\User\\Http\\GoogleController@linkConfirm', 'GET');
    $router->register('/google/link-confirm', 'Lwt\\Modules\\User\\Http\\GoogleController@processLinkConfirm', 'POST');

    // ==================== MICROSOFT OAUTH INTEGRATION (PUBLIC) ====================

    // Microsoft OAuth routes are public - they handle their own auth via OAuth tokens
    $router->register('/microsoft/start', 'Lwt\\Modules\\User\\Http\\MicrosoftController@start');
    $router->register('/microsoft/callback', 'Lwt\\Modules\\User\\Http\\MicrosoftController@callback');
    $router->register('/microsoft/link-confirm', 'Lwt\\Modules\\User\\Http\\MicrosoftController@linkConfirm', 'GET');
    $router->register(
        '/microsoft/link-confirm',
        'Lwt\\Modules\\User\\Http\\MicrosoftController@processLinkConfirm',
        'POST'
    );

    // ==================== API ROUTES ====================

    // Main API - use prefix to catch all sub-paths
    // Note: API handles its own authentication internally via ApiV1::validateAuth()
    // This allows /api/v1/auth/* endpoints to be public while protecting others
    // Support both /api/v1 (new) and /api.php/v1 (legacy) paths
    $router->registerPrefix('/api/v1', 'ApiController@v1');
    $router->registerPrefix('/api.php/v1', 'ApiController@v1');

    // Translation APIs (PROTECTED) - used by authenticated users
    $router->registerWithMiddleware(
        '/api/translate',
        'Lwt\\Modules\\Dictionary\\Http\\TranslationController@translate',
        AUTH_MIDDLEWARE
    );
    $router->registerWithMiddleware(
        '/api/google',
        'Lwt\\Modules\\Dictionary\\Http\\TranslationController@google',
        AUTH_MIDDLEWARE
    );
    $router->registerWithMiddleware(
        '/api/glosbe',
        'Lwt\\Modules\\Dictionary\\Http\\TranslationController@glosbe',
        AUTH_MIDDLEWARE
    );
}

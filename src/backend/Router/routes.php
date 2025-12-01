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

/**
 * Register all application routes.
 *
 * @param Router $router The router instance to register routes with
 *
 * @return void
 */
function registerRoutes(Router $router): void
{
    // ==================== HOME PAGE ====================

    $router->register('/', 'HomeController@index');
    $router->register('/index.php', 'HomeController@index');

    // ==================== TEXT ROUTES ====================

    // Read text
    $router->register('/text/read', 'TextController@read');

    // Empty iframe placeholder (used in text read, test, and word pages)
    $router->register('/empty.html', 'src/backend/Core/empty.html');
    $router->register('/text/empty.html', 'src/backend/Core/empty.html');
    $router->register('/test/empty.html', 'src/backend/Core/empty.html');
    $router->register('/word/empty.html', 'src/backend/Core/empty.html');

    // Edit texts
    $router->register('/text/edit', 'TextController@edit');
    $router->register('/texts', 'TextController@edit');

    // Display improved text
    $router->register('/text/display', 'TextController@display');

    // Print text (TextPrintController)
    $router->register('/text/print', 'TextPrintController@printAnnotated');
    $router->register('/text/print-plain', 'TextPrintController@printPlain');

    // Import long text
    $router->register('/text/import-long', 'TextController@importLong');

    // Set text mode
    $router->register('/text/set-mode', 'TextController@setMode');

    // Check text
    $router->register('/text/check', 'TextController@check');

    // Archived texts
    $router->register('/text/archived', 'TextController@archived');

    // ==================== WORD/TERM ROUTES ====================

    // Edit word
    $router->register('/word/edit', 'WordController@edit');

    // Edit term while testing
    $router->register('/word/edit-term', 'WordController@editTerm');

    // Edit words (list)
    $router->register('/words/edit', 'WordController@listEdit');

    // Edit multi-word
    $router->register('/word/edit-multi', 'WordController@editMulti');

    // Delete word
    $router->register('/word/delete', 'WordController@delete');

    // Delete multi-word
    $router->register('/word/delete-multi', 'WordController@deleteMulti');

    // All words (list view)
    $router->register('/words', 'WordController@listEdit');

    // New word
    $router->register('/word/new', 'WordController@create');

    // Show word
    $router->register('/word/show', 'WordController@show');

    // Insert word (wellknown/ignore)
    $router->register('/word/insert-wellknown', 'WordController@insertWellknown');
    $router->register('/word/insert-ignore', 'WordController@insertIgnore');

    // Inline edit
    $router->register('/word/inline-edit', 'WordController@inlineEdit');

    // Bulk translate
    $router->register('/word/bulk-translate', 'WordController@bulkTranslate');

    // Set word status
    $router->register('/word/set-status', 'WordController@setStatus');

    // Set all words status (wellknown/ignore)
    $router->register('/word/set-all-status', 'WordController@all');

    // Upload words
    $router->register('/word/upload', 'WordController@upload');

    // ==================== TEST ROUTES ====================

    // Test interface
    $router->register('/test', 'TestController@index');

    // Set test status
    $router->register('/test/set-status', 'TestController@setStatus');

    // ==================== LANGUAGE ROUTES ====================

    // Edit languages
    $router->register('/languages', 'LanguageController@index');

    // Select language pair
    $router->register('/languages/select-pair', 'LanguageController@selectPair');

    // ==================== TAG ROUTES ====================

    // Term tags (TagsController)
    $router->register('/tags', 'TagsController@index');

    // Text tags (TagsController)
    $router->register('/tags/text', 'TagsController@textTags');

    // ==================== FEED ROUTES ====================

    // Feeds list
    $router->register('/feeds', 'FeedsController@index');

    // Edit feeds
    $router->register('/feeds/edit', 'FeedsController@edit');

    // Feed wizard
    $router->register('/feeds/wizard', 'FeedsController@wizard');

    // ==================== ADMIN ROUTES ====================

    // Backup & Restore
    $router->register('/admin/backup', 'AdminController@backup');

    // Database Wizard
    $router->register('/admin/wizard', 'AdminController@wizard');

    // Statistics
    $router->register('/admin/statistics', 'AdminController@statistics');

    // Install Demo
    $router->register('/admin/install-demo', 'AdminController@installDemo');

    // Settings
    $router->register('/admin/settings', 'AdminController@settings');
    $router->register('/admin/settings/hover', 'AdminController@settingsHover');
    $router->register('/admin/settings/tts', 'AdminController@settingsTts');

    // Table management
    $router->register('/admin/tables', 'AdminController@tables');

    // Server data
    $router->register('/admin/server-data', 'AdminController@serverData');

    // Save setting and redirect
    $router->register('/admin/save-setting', 'AdminController@saveSetting');

    // ==================== WORDPRESS INTEGRATION ====================

    $router->register('/wordpress/start', 'WordPressController@start');
    $router->register('/wordpress/stop', 'WordPressController@stop');

    // ==================== API ROUTES ====================

    // Main API - use prefix to catch all sub-paths
    // Support both /api/v1 (new) and /api.php/v1 (legacy) paths
    $router->registerPrefix('/api/v1', 'ApiController@v1');
    $router->registerPrefix('/api.php/v1', 'ApiController@v1');

    // Translation APIs
    $router->register('/api/translate', 'ApiController@translate');
    $router->register('/api/google', 'ApiController@google');
    $router->register('/api/glosbe', 'ApiController@glosbe');
}

/**
 * Get the route registration closure.
 *
 * This function returns a closure for backward compatibility with code
 * that expects `require routes.php` to return a callable.
 *
 * @return \Closure
 *
 * @deprecated 3.0.0 Use \Lwt\Router\registerRoutes() directly instead
 */
function getRouteRegistrar(): \Closure
{
    return function (Router $router): void {
        registerRoutes($router);
    };
}

// Return closure for backward compatibility
return getRouteRegistrar();

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

use Lwt\Router\Router;

return function (Router $router) {
    // ==================== HOME PAGE ====================

    $router->register('/', 'HomeController@index');
    $router->register('/index.php', 'HomeController@index');

    // ==================== TEXT ROUTES ====================

    // Read text
    $router->register('/text/read', 'TextController@read');
    $router->registerLegacy('do_text.php', '/text/read');
    $router->registerLegacy('do_text_header.php', '/text/read');
    $router->registerLegacy('do_text_text.php', '/text/read');

    // Empty iframe placeholder (used in text read, test, and word pages)
    $router->register('/text/empty.html', 'src/backend/Core/empty.html');
    $router->register('/test/empty.html', 'src/backend/Core/empty.html');
    $router->register('/word/empty.html', 'src/backend/Core/empty.html');
    $router->registerLegacy('empty.html', '/text/empty.html');

    // Edit texts
    $router->register('/text/edit', 'TextController@edit');
    $router->register('/texts', 'TextController@edit');
    $router->registerLegacy('edit_texts.php', '/text/edit');

    // Display improved text
    $router->register('/text/display', 'TextController@display');
    $router->registerLegacy('display_impr_text.php', '/text/display');
    $router->registerLegacy('display_impr_text_header.php', '/text/display');
    $router->registerLegacy('display_impr_text_text.php', '/text/display');

    // Print text (TextPrintController)
    $router->register('/text/print', 'TextPrintController@printAnnotated');
    $router->registerLegacy('print_impr_text.php', '/text/print');

    $router->register('/text/print-plain', 'TextPrintController@printPlain');
    $router->registerLegacy('print_text.php', '/text/print-plain');

    // Import long text
    $router->register('/text/import-long', 'TextController@importLong');
    $router->registerLegacy('long_text_import.php', '/text/import-long');

    // Set text mode
    $router->register('/text/set-mode', 'TextController@setMode');
    $router->registerLegacy('set_text_mode.php', '/text/set-mode');

    // Check text
    $router->register('/text/check', 'TextController@check');
    $router->registerLegacy('check_text.php', '/text/check');

    // Archived texts
    $router->register('/text/archived', 'TextController@archived');
    $router->registerLegacy('edit_archivedtexts.php', '/text/archived');

    // ==================== WORD/TERM ROUTES ====================

    // Edit word
    $router->register('/word/edit', 'WordController@edit');
    $router->registerLegacy('edit_word.php', '/word/edit');
    $router->registerLegacy('edit_tword.php', '/word/edit');

    // Edit words (list)
    $router->register('/words/edit', 'WordController@listEdit');
    $router->registerLegacy('edit_words.php', '/words/edit');

    // Edit multi-word
    $router->register('/word/edit-multi', 'WordController@editMulti');
    $router->registerLegacy('edit_mword.php', '/word/edit-multi');

    // Delete word
    $router->register('/word/delete', 'WordController@delete');
    $router->registerLegacy('delete_word.php', '/word/delete');

    // Delete multi-word
    $router->register('/word/delete-multi', 'WordController@deleteMulti');
    $router->registerLegacy('delete_mword.php', '/word/delete-multi');

    // All words
    $router->register('/words', 'WordController@all');
    $router->registerLegacy('all_words_wellknown.php', '/words');

    // New word
    $router->register('/word/new', 'WordController@create');
    $router->registerLegacy('new_word.php', '/word/new');

    // Show word
    $router->register('/word/show', 'WordController@show');
    $router->registerLegacy('show_word.php', '/word/show');

    // Insert word (wellknown/ignore)
    $router->register('/word/insert-wellknown', 'WordController@insertWellknown');
    $router->registerLegacy('insert_word_wellknown.php', '/word/insert-wellknown');

    $router->register('/word/insert-ignore', 'WordController@insertIgnore');
    $router->registerLegacy('insert_word_ignore.php', '/word/insert-ignore');

    // Inline edit
    $router->register('/word/inline-edit', 'WordController@inlineEdit');
    $router->registerLegacy('inline_edit.php', '/word/inline-edit');

    // Bulk translate
    $router->register('/word/bulk-translate', 'WordController@bulkTranslate');
    $router->registerLegacy('bulk_translate_words.php', '/word/bulk-translate');

    // Set word status
    $router->register('/word/set-status', 'WordController@setStatus');
    $router->registerLegacy('set_word_status.php', '/word/set-status');

    // Upload words
    $router->register('/word/upload', 'WordController@upload');
    $router->registerLegacy('upload_words.php', '/word/upload');

    // ==================== TEST ROUTES ====================

    // Test interface
    $router->register('/test', 'TestController@index');
    $router->registerLegacy('do_test.php', '/test');
    $router->registerLegacy('do_test_header.php', '/test');
    $router->registerLegacy('do_test_table.php', '/test');
    $router->registerLegacy('do_test_test.php', '/test');

    // Set test status
    $router->register('/test/set-status', 'TestController@setStatus');
    $router->registerLegacy('set_test_status.php', '/test/set-status');

    // ==================== LANGUAGE ROUTES ====================

    // Edit languages
    $router->register('/languages', 'LanguageController@index');
    $router->registerLegacy('edit_languages.php', '/languages');

    // Select language pair
    $router->register('/languages/select-pair', 'LanguageController@selectPair');
    $router->registerLegacy('select_lang_pair.php', '/languages/select-pair');

    // ==================== TAG ROUTES ====================

    // Term tags (TagsController)
    $router->register('/tags', 'TagsController@index');
    $router->registerLegacy('edit_tags.php', '/tags');

    // Text tags (TagsController)
    $router->register('/tags/text', 'TagsController@textTags');
    $router->registerLegacy('edit_texttags.php', '/tags/text');

    // ==================== FEED ROUTES ====================

    // Feeds list
    $router->register('/feeds', 'FeedsController@index');
    $router->registerLegacy('do_feeds.php', '/feeds');

    // Edit feeds
    $router->register('/feeds/edit', 'FeedsController@edit');
    $router->registerLegacy('edit_feeds.php', '/feeds/edit');

    // Feed wizard
    $router->register('/feeds/wizard', 'FeedsController@wizard');
    $router->registerLegacy('feed_wizard.php', '/feeds/wizard');

    // ==================== ADMIN ROUTES ====================

    // Backup & Restore
    $router->register('/admin/backup', 'AdminController@backup');
    $router->registerLegacy('backup_restore.php', '/admin/backup');

    // Database Wizard
    $router->register('/admin/wizard', 'AdminController@wizard');
    $router->registerLegacy('database_wizard.php', '/admin/wizard');

    // Statistics
    $router->register('/admin/statistics', 'AdminController@statistics');
    $router->registerLegacy('statistics.php', '/admin/statistics');

    // Install Demo
    $router->register('/admin/install-demo', 'AdminController@installDemo');
    $router->registerLegacy('install_demo.php', '/admin/install-demo');

    // Settings
    $router->register('/admin/settings', 'AdminController@settings');
    $router->registerLegacy('settings.php', '/admin/settings');

    $router->register('/admin/settings/hover', 'AdminController@settingsHover');
    $router->registerLegacy('set_word_on_hover.php', '/admin/settings/hover');

    $router->register('/admin/settings/tts', 'AdminController@settingsTts');
    $router->registerLegacy('text_to_speech_settings.php', '/admin/settings/tts');

    // Table management
    $router->register('/admin/tables', 'AdminController@tables');
    $router->registerLegacy('table_set_management.php', '/admin/tables');

    // Server data
    $router->register('/admin/server-data', 'AdminController@serverData');
    $router->registerLegacy('server_data.php', '/admin/server-data');

    // ==================== MOBILE ROUTES ====================

    $router->register('/mobile', 'MobileController@index');
    $router->registerLegacy('mobile.php', '/mobile');

    $router->register('/mobile/start', 'MobileController@start');
    $router->registerLegacy('start.php', '/mobile/start');

    // ==================== WORDPRESS INTEGRATION ====================

    $router->register('/wordpress/start', 'WordPressController@start');
    $router->registerLegacy('wp_lwt_start.php', '/wordpress/start');

    $router->register('/wordpress/stop', 'WordPressController@stop');
    $router->registerLegacy('wp_lwt_stop.php', '/wordpress/stop');

    // ==================== API ROUTES ====================

    // Main API - use prefix to catch all sub-paths
    $router->registerPrefix('/api/v1', 'ApiController@v1');
    $router->registerLegacy('api.php', '/api/v1');

    // Translation APIs
    $router->register('/api/translate', 'ApiController@translate');
    $router->registerLegacy('trans.php', '/api/translate');

    $router->register('/api/google', 'ApiController@google');
    $router->registerLegacy('ggl.php', '/api/google');

    $router->register('/api/glosbe', 'ApiController@glosbe');
    $router->registerLegacy('glosbe_api.php', '/api/glosbe');
};

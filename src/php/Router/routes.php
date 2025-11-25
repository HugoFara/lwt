<?php

/**
 * LWT Route Configuration
 *
 * This file defines all routes for the application
 */

use Lwt\Router\Router;

return function (Router $router) {
    // Home page
    $router->register('/', 'src/php/Legacy/home.php');
    $router->register('/index.php', 'src/php/Legacy/home.php');

    // ==================== TEXT ROUTES ====================

    // Read text
    $router->register('/text/read', 'src/php/Legacy/text_read.php');
    $router->registerLegacy('do_text.php', '/text/read');
    $router->registerLegacy('do_text_header.php', '/text/read');
    $router->registerLegacy('do_text_text.php', '/text/read');

    // Empty iframe placeholder (used in text read, test, and word pages)
    $router->register('/text/empty.html', 'src/php/inc/empty.html');
    $router->register('/test/empty.html', 'src/php/inc/empty.html');
    $router->register('/word/empty.html', 'src/php/inc/empty.html');
    $router->registerLegacy('empty.html', '/text/empty.html');

    // Edit texts
    $router->register('/text/edit', 'src/php/Legacy/text_edit.php');
    $router->register('/texts', 'src/php/Legacy/text_edit.php');
    $router->registerLegacy('edit_texts.php', '/text/edit');

    // Display improved text
    $router->register('/text/display', 'src/php/Legacy/text_display.php');
    $router->registerLegacy('display_impr_text.php', '/text/display');
    $router->registerLegacy('display_impr_text_header.php', '/text/display');
    $router->registerLegacy('display_impr_text_text.php', '/text/display');

    // Print text
    $router->register('/text/print', 'src/php/Legacy/text_print.php');
    $router->registerLegacy('print_impr_text.php', '/text/print');

    $router->register('/text/print-plain', 'src/php/Legacy/text_print_plain.php');
    $router->registerLegacy('print_text.php', '/text/print-plain');

    // Import long text
    $router->register('/text/import-long', 'src/php/Legacy/text_import_long.php');
    $router->registerLegacy('long_text_import.php', '/text/import-long');

    // Set text mode
    $router->register('/text/set-mode', 'src/php/Legacy/text_set_mode.php');
    $router->registerLegacy('set_text_mode.php', '/text/set-mode');

    // Check text
    $router->register('/text/check', 'src/php/Legacy/text_check.php');
    $router->registerLegacy('check_text.php', '/text/check');

    // Archived texts
    $router->register('/text/archived', 'src/php/Legacy/text_archived.php');
    $router->registerLegacy('edit_archivedtexts.php', '/text/archived');

    // ==================== WORD/TERM ROUTES ====================

    // Edit word
    $router->register('/word/edit', 'src/php/Legacy/word_edit.php');
    $router->registerLegacy('edit_word.php', '/word/edit');
    $router->registerLegacy('edit_tword.php', '/word/edit');

    // Edit words (list)
    $router->register('/words/edit', 'src/php/Legacy/words_edit.php');
    $router->registerLegacy('edit_words.php', '/words/edit');

    // Edit multi-word
    $router->register('/word/edit-multi', 'src/php/Legacy/word_edit_multi.php');
    $router->registerLegacy('edit_mword.php', '/word/edit-multi');

    // Delete word
    $router->register('/word/delete', 'src/php/Legacy/word_delete.php');
    $router->registerLegacy('delete_word.php', '/word/delete');

    // Delete multi-word
    $router->register('/word/delete-multi', 'src/php/Legacy/word_delete_multi.php');
    $router->registerLegacy('delete_mword.php', '/word/delete-multi');

    // All words
    $router->register('/words', 'src/php/Legacy/words_all.php');
    $router->registerLegacy('all_words_wellknown.php', '/words');

    // New word
    $router->register('/word/new', 'src/php/Legacy/word_new.php');
    $router->registerLegacy('new_word.php', '/word/new');

    // Show word
    $router->register('/word/show', 'src/php/Legacy/word_show.php');
    $router->registerLegacy('show_word.php', '/word/show');

    // Insert word (wellknown/ignore)
    $router->register('/word/insert-wellknown', 'src/php/Legacy/word_insert_wellknown.php');
    $router->registerLegacy('insert_word_wellknown.php', '/word/insert-wellknown');

    $router->register('/word/insert-ignore', 'src/php/Legacy/word_insert_ignore.php');
    $router->registerLegacy('insert_word_ignore.php', '/word/insert-ignore');

    // Inline edit
    $router->register('/word/inline-edit', 'src/php/Legacy/word_inline_edit.php');
    $router->registerLegacy('inline_edit.php', '/word/inline-edit');

    // Bulk translate
    $router->register('/word/bulk-translate', 'src/php/Legacy/word_bulk_translate.php');
    $router->registerLegacy('bulk_translate_words.php', '/word/bulk-translate');

    // Set word status
    $router->register('/word/set-status', 'src/php/Legacy/word_set_status.php');
    $router->registerLegacy('set_word_status.php', '/word/set-status');

    // Upload words
    $router->register('/word/upload', 'src/php/Legacy/word_upload.php');
    $router->registerLegacy('upload_words.php', '/word/upload');

    // ==================== TEST ROUTES ====================

    // Test interface
    $router->register('/test', 'src/php/Legacy/test_index.php');
    $router->registerLegacy('do_test.php', '/test');
    $router->registerLegacy('do_test_header.php', '/test');
    $router->registerLegacy('do_test_table.php', '/test');
    $router->registerLegacy('do_test_test.php', '/test');

    // Set test status
    $router->register('/test/set-status', 'src/php/Legacy/test_set_status.php');
    $router->registerLegacy('set_test_status.php', '/test/set-status');

    // ==================== LANGUAGE ROUTES ====================

    // Edit languages
    $router->register('/languages', 'src/php/Legacy/language_edit.php');
    $router->registerLegacy('edit_languages.php', '/languages');

    // Select language pair
    $router->register('/languages/select-pair', 'src/php/Legacy/language_select_pair.php');
    $router->registerLegacy('select_lang_pair.php', '/languages/select-pair');

    // ==================== TAG ROUTES ====================

    // Edit tags
    $router->register('/tags', 'src/php/Legacy/tags_edit.php');
    $router->registerLegacy('edit_tags.php', '/tags');

    // Edit text tags
    $router->register('/tags/text', 'src/php/Legacy/tags_text_edit.php');
    $router->registerLegacy('edit_texttags.php', '/tags/text');

    // ==================== FEED ROUTES ====================

    // Feeds list
    $router->register('/feeds', 'src/php/Legacy/feeds_index.php');
    $router->registerLegacy('do_feeds.php', '/feeds');

    // Edit feeds
    $router->register('/feeds/edit', 'src/php/Legacy/feeds_edit.php');
    $router->registerLegacy('edit_feeds.php', '/feeds/edit');

    // Feed wizard
    $router->register('/feeds/wizard', 'src/php/Legacy/feeds_wizard.php');
    $router->registerLegacy('feed_wizard.php', '/feeds/wizard');

    // ==================== ADMIN ROUTES ====================

    // Backup & Restore
    $router->register('/admin/backup', 'src/php/Legacy/admin_backup.php');
    $router->registerLegacy('backup_restore.php', '/admin/backup');

    // Database Wizard
    $router->register('/admin/wizard', 'src/php/Legacy/admin_wizard.php');
    $router->registerLegacy('database_wizard.php', '/admin/wizard');

    // Statistics
    $router->register('/admin/statistics', 'src/php/Legacy/admin_statistics.php');
    $router->registerLegacy('statistics.php', '/admin/statistics');

    // Install Demo
    $router->register('/admin/install-demo', 'src/php/Legacy/admin_install_demo.php');
    $router->registerLegacy('install_demo.php', '/admin/install-demo');

    // Settings
    $router->register('/admin/settings', 'src/php/Legacy/admin_settings.php');
    $router->registerLegacy('settings.php', '/admin/settings');

    $router->register('/admin/settings/hover', 'src/php/Legacy/settings_hover.php');
    $router->registerLegacy('set_word_on_hover.php', '/admin/settings/hover');

    $router->register('/admin/settings/tts', 'src/php/Legacy/admin_tts_settings.php');
    $router->registerLegacy('text_to_speech_settings.php', '/admin/settings/tts');

    // Table management
    $router->register('/admin/tables', 'src/php/Legacy/admin_table_management.php');
    $router->registerLegacy('table_set_management.php', '/admin/tables');

    // Server data
    $router->register('/admin/server-data', 'src/php/Legacy/admin_server_data.php');
    $router->registerLegacy('server_data.php', '/admin/server-data');

    // ==================== MOBILE ROUTES ====================

    $router->register('/mobile', 'src/php/Legacy/mobile_index.php');
    $router->registerLegacy('mobile.php', '/mobile');

    $router->register('/mobile/start', 'src/php/Legacy/mobile_start.php');
    $router->registerLegacy('start.php', '/mobile/start');

    // ==================== WORDPRESS INTEGRATION ====================

    $router->register('/wordpress/start', 'src/php/Legacy/wordpress_start.php');
    $router->registerLegacy('wp_lwt_start.php', '/wordpress/start');

    $router->register('/wordpress/stop', 'src/php/Legacy/wordpress_stop.php');
    $router->registerLegacy('wp_lwt_stop.php', '/wordpress/stop');

    // ==================== API ROUTES ====================

    // Main API - use prefix to catch all sub-paths
    $router->registerPrefix('/api/v1', 'src/php/Legacy/api_v1.php');
    $router->registerLegacy('api.php', '/api/v1');

    // Translation APIs
    $router->register('/api/translate', 'src/php/Legacy/api_translate.php');
    $router->registerLegacy('trans.php', '/api/translate');

    $router->register('/api/google', 'src/php/Legacy/api_google.php');
    $router->registerLegacy('ggl.php', '/api/google');

    $router->register('/api/glosbe', 'src/php/Legacy/api_glosbe.php');
    $router->registerLegacy('glosbe_api.php', '/api/glosbe');
};

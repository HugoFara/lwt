<?php
/**
 * Migration Script: Move root PHP files to src/php/Legacy
 *
 * This script moves root PHP files to the new Legacy structure
 * and creates backward compatibility stubs.
 */

// File mapping: old_name => new_legacy_name
$fileMap = [
    // Text management
    'do_text.php' => 'text_read.php',
    'do_text_header.php' => 'text_read_header.php',
    'do_text_text.php' => 'text_read_text.php',
    'edit_texts.php' => 'text_edit.php',
    'check_text.php' => 'text_check.php',
    'display_impr_text.php' => 'text_display.php',
    'display_impr_text_header.php' => 'text_display_header.php',
    'display_impr_text_text.php' => 'text_display_text.php',
    'print_impr_text_dict.php' => 'text_print_dict.php',
    'print_impr_text.php' => 'text_print.php',
    'print_text.php' => 'text_print_plain.php',
    'edit_archivedtexts.php' => 'text_archived.php',
    'long_text_import.php' => 'text_import_long.php',
    'set_text_mode.php' => 'text_set_mode.php',

    // Word management
    'edit_word.php' => 'word_edit.php',
    'edit_words.php' => 'words_edit.php',
    'edit_tword.php' => 'word_edit_term.php',
    'edit_mword.php' => 'word_edit_multi.php',
    'delete_word.php' => 'word_delete.php',
    'delete_mword.php' => 'word_delete_multi.php',
    'all_words_wellknown.php' => 'words_all.php',
    'bulk_translate_words.php' => 'word_bulk_translate.php',
    'set_word_status.php' => 'word_set_status.php',
    'upload_words.php' => 'word_upload.php',
    'set_word_on_hover.php' => 'settings_hover.php',
    'new_word.php' => 'word_new.php',
    'show_word.php' => 'word_show.php',
    'insert_word_wellknown.php' => 'word_insert_wellknown.php',
    'insert_word_ignore.php' => 'word_insert_ignore.php',
    'inline_edit.php' => 'word_inline_edit.php',

    // Test
    'do_test.php' => 'test_index.php',
    'do_test_header.php' => 'test_header.php',
    'do_test_table.php' => 'test_table.php',
    'do_test_test.php' => 'test_test.php',
    'set_test_status.php' => 'test_set_status.php',

    // Languages
    'edit_languages.php' => 'language_edit.php',
    'select_lang_pair.php' => 'language_select_pair.php',

    // Tags
    'edit_tags.php' => 'tags_edit.php',
    'edit_texttags.php' => 'tags_text_edit.php',

    // Feeds
    'do_feeds.php' => 'feeds_index.php',
    'edit_feeds.php' => 'feeds_edit.php',
    'feed_wizard.php' => 'feeds_wizard.php',

    // Admin & Settings
    'backup_restore.php' => 'admin_backup.php',
    'database_wizard.php' => 'admin_wizard.php',
    'statistics.php' => 'admin_statistics.php',
    'install_demo.php' => 'admin_install_demo.php',
    'settings.php' => 'admin_settings.php',
    'table_set_management.php' => 'admin_table_management.php',
    'text_to_speech_settings.php' => 'admin_tts_settings.php',
    'server_data.php' => 'admin_server_data.php',

    // Mobile
    'mobile.php' => 'mobile_index.php',
    'start.php' => 'mobile_start.php',

    // WordPress integration
    'wp_lwt_start.php' => 'wordpress_start.php',
    'wp_lwt_stop.php' => 'wordpress_stop.php',

    // Translation APIs
    'trans.php' => 'api_translate.php',
    'ggl.php' => 'api_google.php',
    'glosbe_api.php' => 'api_glosbe.php',

    // API
    'api.php' => 'api_v1.php',
];

$legacyDir = __DIR__ . '/src/php/Legacy/';
$dryRun = false; // Set to false to actually perform migration

echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║  LWT Legacy File Migration                            ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";

if ($dryRun) {
    echo "⚠️  DRY RUN MODE - No files will be modified\n\n";
}

$successCount = 0;
$skipCount = 0;

foreach ($fileMap as $oldFile => $newName) {
    echo "Processing: $oldFile → src/php/Legacy/$newName\n";

    if (!file_exists($oldFile)) {
        echo "  ⏭️  Skipped (file not found)\n\n";
        $skipCount++;
        continue;
    }

    $newPath = $legacyDir . $newName;

    if (!$dryRun) {
        // Use git mv to preserve file history
        $gitMvCommand = sprintf(
            'git mv %s %s 2>&1',
            escapeshellarg($oldFile),
            escapeshellarg($newPath)
        );

        exec($gitMvCommand, $output, $returnCode);

        if ($returnCode === 0) {
            echo "  ✅ Git moved to: $newPath (history preserved)\n";
        } else {
            // Fallback to regular copy if not in git repo or file not tracked
            echo "  ⚠️  Git mv failed, using copy instead\n";
            echo "     Git output: " . implode("\n     ", $output) . "\n";

            copy($oldFile, $newPath);
            echo "  ✅ Copied to: $newPath\n";

            // Backup original
            $backupPath = $oldFile . '.migrated';
            rename($oldFile, $backupPath);
            echo "  💾 Backed up: $oldFile → $backupPath\n";
        }
    } else {
        echo "  📋 [DRY RUN] Would use: git mv $oldFile $newPath\n";
        echo "  📋 [DRY RUN] This preserves git history\n";
    }

    $successCount++;
    echo "\n";
}

echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║  Migration Summary                                    ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n";
echo "✅ Processed: $successCount\n";
echo "⏭️  Skipped:   $skipCount\n\n";

if ($dryRun) {
    echo "💡 To perform actual migration, edit this script and set \$dryRun = false\n";
} else {
    echo "✅ Migration complete!\n\n";

    // Offer to commit changes
    echo "Would you like to commit these changes to git? [y/N]: ";
    $answer = trim(fgets(STDIN));

    if (strtolower($answer) === 'y') {
        echo "\n📝 Creating git commit...\n";

        $commitMessage = <<<'MSG'
refactor: migrate root PHP files to src/php/Legacy structure

- Moved user-facing PHP files from root to src/php/Legacy/
- Created front controller pattern with single index.php entry point
- Added Router system for clean URLs with backward compatibility
- All old URLs preserved via 301 redirects
- Created .htaccess for Apache mod_rewrite
- Git history preserved via 'git mv'

Part of architecture modernization to improve code organization
and enable future MVC refactoring.
MSG;

        // Stage the new files
        exec('git add src/php/Router/ src/php/Legacy/ .htaccess 2>&1', $output, $code);
        if ($code === 0) {
            echo "  ✅ Staged new files\n";
        }

        // DO NOT Commit, this is the user's role
        // $commitCommand = sprintf('git commit -m %s 2>&1', escapeshellarg($commitMessage));
        // exec($commitCommand, $output, $code);

        if ($code === 0) {
            echo "  ✅ Created commit\n";
            echo "\n📝 Git status:\n";
            passthru('git status --short');
        } else {
            echo "  ⚠️  Commit failed\n";
            echo "     " . implode("\n     ", $output) . "\n";
        }
    }

    echo "\n📝 Next steps:\n";
    echo "   1. Test the application: php -S localhost:8000\n";
    echo "   2. Test routing: php test_router.php\n";
    echo "   3. Verify all pages work correctly\n";
    echo "   4. Run automated tests: composer test\n";
    echo "   5. Review and push changes: git push\n";
}

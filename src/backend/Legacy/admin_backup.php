<?php

/**
 * Backup/Restore/Empty LWT Database
 *
 * Call: /admin/backup
 *  ... restore=xxx ... do restore
 *  ... backup=xxx ... do backup
 *  ... empty=xxx ... do truncate
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/backup-restore.html
 * @since    1.0.3
 */

namespace Lwt\Interface\Backup;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Text/text_helpers.php';
require_once 'Core/Http/param_helpers.php';

use Lwt\Services\BackupService;

require_once __DIR__ . '/../Services/BackupService.php';

// Initialize service
$backupService = new BackupService();
$message = '';

// Handle operations
if (isset($_REQUEST['restore'])) {
    $message = $backupService->restoreFromUpload($_FILES);
} elseif (isset($_REQUEST['backup'])) {
    $backupService->downloadBackup();
    // downloadBackup exits, so we never reach here
} elseif (isset($_REQUEST['orig_backup'])) {
    $backupService->downloadOfficialBackup();
    // downloadOfficialBackup exits, so we never reach here
} elseif (isset($_REQUEST['empty'])) {
    $message = $backupService->emptyDatabase();
}

// Get view data (used by included view)
/** @psalm-suppress UnusedVariable - Variables used by included view */
$prefinfo = $backupService->getPrefixInfo();
/** @psalm-suppress UnusedVariable - Variables used by included view */
$dbname = $backupService->getDatabaseName();

// Render page
pagestart('Backup/Restore/Empty Database', true);

echo error_message_with_hide($message, true);

// Include the view
include __DIR__ . '/../Views/Admin/backup.php';

pageend();

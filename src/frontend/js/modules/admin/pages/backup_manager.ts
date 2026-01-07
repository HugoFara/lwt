/**
 * Backup Manager - Alpine.js component for database backup/restore operations.
 *
 * Handles file selection, loading states, and confirmation for
 * backup, restore, and empty database operations.
 *
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';

interface BackupManagerState {
  fileName: string;
  restoring: boolean;
  emptying: boolean;
  confirmEmpty: boolean;
}

/**
 * Alpine.js data component for the backup management page.
 * Manages file selection state and operation loading states.
 */
export function backupManager(): BackupManagerState {
  return {
    fileName: '',
    restoring: false,
    emptying: false,
    confirmEmpty: false
  };
}

/**
 * Register the Alpine.js component.
 */
export function initBackupManagerAlpine(): void {
  Alpine.data('backupManager', backupManager);
}

// Auto-register before Alpine.start() is called
initBackupManagerAlpine();

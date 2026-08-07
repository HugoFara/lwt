/**
 * Backup Manager - Alpine.js component for database backup/restore operations.
 *
 * Handles file selection, loading states, and confirmation for
 * backup, restore, and empty database operations.
 *
 * @author  HugoFara <git@hugofara.net>
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';

interface BackupManagerState {
  fileName: string;
  restoring: boolean;
  emptying: boolean;
  confirmEmpty: boolean;
  selectFile(event: Event): void;
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
    confirmEmpty: false,

    /**
     * Record the chosen backup file's name from a file-input change event.
     *
     * This lives here rather than inline in the view because @alpinejs/csp
     * cannot parse optional chaining: the previous inline handler,
     * `fileName = $event.target.files[0]?.name || ''`, threw a CSP parser
     * error on every change, so the filename never appeared and the submit
     * button stayed disabled forever (issue #249).
     */
    selectFile(event: Event): void {
      const input = event.target as HTMLInputElement | null;
      const file = input && input.files ? input.files[0] : null;
      this.fileName = file ? file.name : '';
    }
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

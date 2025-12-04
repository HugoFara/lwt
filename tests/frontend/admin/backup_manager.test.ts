/**
 * Tests for backup_manager.ts - Backup Manager Alpine.js component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { backupManager, initBackupManagerAlpine } from '../../../src/frontend/js/admin/backup_manager';

describe('backup_manager.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // backupManager Tests
  // ===========================================================================

  describe('backupManager', () => {
    it('returns initial state with empty fileName', () => {
      const state = backupManager();

      expect(state.fileName).toBe('');
    });

    it('returns initial state with restoring as false', () => {
      const state = backupManager();

      expect(state.restoring).toBe(false);
    });

    it('returns initial state with emptying as false', () => {
      const state = backupManager();

      expect(state.emptying).toBe(false);
    });

    it('returns initial state with confirmEmpty as false', () => {
      const state = backupManager();

      expect(state.confirmEmpty).toBe(false);
    });

    it('returns all expected properties', () => {
      const state = backupManager();

      expect(state).toHaveProperty('fileName');
      expect(state).toHaveProperty('restoring');
      expect(state).toHaveProperty('emptying');
      expect(state).toHaveProperty('confirmEmpty');
    });

    it('returns a fresh state on each call', () => {
      const state1 = backupManager();
      const state2 = backupManager();

      state1.fileName = 'test.sql';
      state1.restoring = true;

      expect(state2.fileName).toBe('');
      expect(state2.restoring).toBe(false);
    });
  });

  // ===========================================================================
  // initBackupManagerAlpine Tests
  // ===========================================================================

  describe('initBackupManagerAlpine', () => {
    it('does not throw when called', () => {
      expect(() => initBackupManagerAlpine()).not.toThrow();
    });

    it('can be called multiple times without error', () => {
      expect(() => {
        initBackupManagerAlpine();
        initBackupManagerAlpine();
      }).not.toThrow();
    });
  });

  // ===========================================================================
  // State Modification Tests
  // ===========================================================================

  describe('State Modification', () => {
    it('allows setting fileName', () => {
      const state = backupManager();

      state.fileName = 'backup_2024.sql';

      expect(state.fileName).toBe('backup_2024.sql');
    });

    it('allows setting restoring flag', () => {
      const state = backupManager();

      state.restoring = true;

      expect(state.restoring).toBe(true);
    });

    it('allows setting emptying flag', () => {
      const state = backupManager();

      state.emptying = true;

      expect(state.emptying).toBe(true);
    });

    it('allows setting confirmEmpty flag', () => {
      const state = backupManager();

      state.confirmEmpty = true;

      expect(state.confirmEmpty).toBe(true);
    });

    it('allows toggling confirmEmpty', () => {
      const state = backupManager();

      state.confirmEmpty = true;
      expect(state.confirmEmpty).toBe(true);

      state.confirmEmpty = false;
      expect(state.confirmEmpty).toBe(false);
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles special characters in fileName', () => {
      const state = backupManager();

      state.fileName = 'backup (1).sql';

      expect(state.fileName).toBe('backup (1).sql');
    });

    it('handles empty string fileName', () => {
      const state = backupManager();

      state.fileName = '';

      expect(state.fileName).toBe('');
    });

    it('handles unicode characters in fileName', () => {
      const state = backupManager();

      state.fileName = 'バックアップ.sql';

      expect(state.fileName).toBe('バックアップ.sql');
    });

    it('handles very long fileName', () => {
      const state = backupManager();
      const longName = 'a'.repeat(255) + '.sql';

      state.fileName = longName;

      expect(state.fileName).toBe(longName);
    });
  });
});

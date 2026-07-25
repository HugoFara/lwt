/**
 * Tests for backup_manager.ts - Backup Manager Alpine.js component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { backupManager, initBackupManagerAlpine } from '../../../src/frontend/js/modules/admin/pages/backup_manager';

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
      expect(state).toHaveProperty('selectFile');
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
  // selectFile Tests (issue #249)
  // ===========================================================================

  describe('selectFile', () => {
    /**
     * Build a file input whose `files` list holds the given names, the way a
     * browser presents it to a change handler.
     */
    function inputWithFiles(names: string[]): HTMLInputElement {
      const input = document.createElement('input');
      input.type = 'file';
      Object.defineProperty(input, 'files', {
        value: names.map((name) => new File(['x'], name)),
        configurable: true
      });
      document.body.appendChild(input);
      return input;
    }

    it('records the chosen file name', () => {
      const state = backupManager();
      const input = inputWithFiles(['LWT 7-8-26.gz']);

      state.selectFile({ target: input } as unknown as Event);

      expect(state.fileName).toBe('LWT 7-8-26.gz');
    });

    it('clears the name when the selection is emptied', () => {
      const state = backupManager();
      state.fileName = 'previous.sql';
      const input = inputWithFiles([]);

      state.selectFile({ target: input } as unknown as Event);

      expect(state.fileName).toBe('');
    });

    it('handles an event with no target without throwing', () => {
      const state = backupManager();

      expect(() => state.selectFile({ target: null } as unknown as Event)).not.toThrow();
      expect(state.fileName).toBe('');
    });

    it('handles an input with a null files list', () => {
      const state = backupManager();
      const input = document.createElement('input');
      input.type = 'file';
      Object.defineProperty(input, 'files', { value: null, configurable: true });

      state.selectFile({ target: input } as unknown as Event);

      expect(state.fileName).toBe('');
    });

    it('is evaluable by the CSP Alpine build, unlike an inline optional chain', async () => {
      // The regression this guards: `@change="fileName = $event.target
      // .files[0]?.name || ''"` throws "CSP Parser Error: Unexpected token"
      // in @alpinejs/csp, so the filename never appeared and the restore
      // button stayed disabled. A method call parses fine.
      const Alpine = (await import('alpinejs')).default;
      const captured: string[] = [];
      const origWarn = console.warn;
      const origError = console.error;
      console.warn = (...a: unknown[]) => { captured.push(a.map(String).join(' ')); };
      console.error = (...a: unknown[]) => { captured.push(a.map(String).join(' ')); };

      Alpine.data('backupManagerCspCheck', backupManager);
      document.body.innerHTML = `
        <div x-data="backupManagerCspCheck">
          <input id="csp-file" type="file" @change="selectFile($event)">
          <span id="csp-name" x-text="fileName || 'NO FILE'"></span>
        </div>`;
      Alpine.start();
      await new Promise((resolve) => setTimeout(resolve, 30));

      const input = document.getElementById('csp-file') as HTMLInputElement;
      Object.defineProperty(input, 'files', {
        value: [new File(['x'], 'backup.sql.gz')],
        configurable: true
      });
      input.dispatchEvent(new Event('change'));
      await new Promise((resolve) => setTimeout(resolve, 30));

      console.warn = origWarn;
      console.error = origError;

      expect(captured.join('\n')).not.toContain('CSP Parser Error');
      expect(document.getElementById('csp-name')?.textContent).toBe('backup.sql.gz');
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

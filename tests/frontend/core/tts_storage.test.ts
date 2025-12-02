/**
 * Tests for tts_storage.ts - TTS localStorage utilities
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  getTTSSettings,
  saveTTSSettings,
  updateTTSSettings,
  clearTTSSettings,
  getAllTTSLanguages,
  isMigrationComplete,
  migrateTTSCookies,
  getTTSSettingsWithMigration,
} from '../../../src/frontend/js/core/tts_storage';

describe('tts_storage.ts', () => {
  beforeEach(() => {
    // Clear localStorage before each test
    localStorage.clear();
    // Clear cookies
    document.cookie.split(';').forEach((c) => {
      document.cookie = c
        .replace(/^ +/, '')
        .replace(/=.*/, '=;expires=Thu, 01 Jan 1970 00:00:00 GMT');
    });
  });

  afterEach(() => {
    localStorage.clear();
  });

  // ===========================================================================
  // getTTSSettings Tests
  // ===========================================================================

  describe('getTTSSettings', () => {
    it('returns empty object when no settings stored', () => {
      const settings = getTTSSettings('en');
      expect(settings).toEqual({});
    });

    it('returns stored settings', () => {
      localStorage.setItem('lwt.tts.en', JSON.stringify({
        voice: 'Test Voice',
        rate: 1.5,
        pitch: 0.8,
      }));

      const settings = getTTSSettings('en');
      expect(settings).toEqual({
        voice: 'Test Voice',
        rate: 1.5,
        pitch: 0.8,
      });
    });

    it('returns empty object for invalid JSON', () => {
      localStorage.setItem('lwt.tts.fr', 'invalid-json');

      const settings = getTTSSettings('fr');
      expect(settings).toEqual({});
    });

    it('handles partial settings', () => {
      localStorage.setItem('lwt.tts.de', JSON.stringify({ rate: 1.2 }));

      const settings = getTTSSettings('de');
      expect(settings).toEqual({ rate: 1.2 });
      expect(settings.voice).toBeUndefined();
      expect(settings.pitch).toBeUndefined();
    });
  });

  // ===========================================================================
  // saveTTSSettings Tests
  // ===========================================================================

  describe('saveTTSSettings', () => {
    it('saves settings to localStorage', () => {
      saveTTSSettings('en', {
        voice: 'Google US English',
        rate: 1.0,
        pitch: 1.0,
      });

      const stored = localStorage.getItem('lwt.tts.en');
      expect(stored).not.toBeNull();
      expect(JSON.parse(stored!)).toEqual({
        voice: 'Google US English',
        rate: 1.0,
        pitch: 1.0,
      });
    });

    it('overwrites existing settings', () => {
      saveTTSSettings('fr', { rate: 1.0 });
      saveTTSSettings('fr', { rate: 2.0, voice: 'New Voice' });

      const settings = getTTSSettings('fr');
      expect(settings).toEqual({ rate: 2.0, voice: 'New Voice' });
    });

    it('handles empty settings object', () => {
      saveTTSSettings('es', {});

      const settings = getTTSSettings('es');
      expect(settings).toEqual({});
    });
  });

  // ===========================================================================
  // updateTTSSettings Tests
  // ===========================================================================

  describe('updateTTSSettings', () => {
    it('merges with existing settings', () => {
      saveTTSSettings('en', { voice: 'Original Voice', rate: 1.0 });
      updateTTSSettings('en', { pitch: 0.8 });

      const settings = getTTSSettings('en');
      expect(settings).toEqual({
        voice: 'Original Voice',
        rate: 1.0,
        pitch: 0.8,
      });
    });

    it('overwrites specific fields', () => {
      saveTTSSettings('en', { voice: 'Old Voice', rate: 1.0 });
      updateTTSSettings('en', { voice: 'New Voice' });

      const settings = getTTSSettings('en');
      expect(settings.voice).toBe('New Voice');
      expect(settings.rate).toBe(1.0);
    });

    it('creates settings if none exist', () => {
      updateTTSSettings('ja', { rate: 1.5 });

      const settings = getTTSSettings('ja');
      expect(settings).toEqual({ rate: 1.5 });
    });
  });

  // ===========================================================================
  // clearTTSSettings Tests
  // ===========================================================================

  describe('clearTTSSettings', () => {
    it('removes settings for a language', () => {
      saveTTSSettings('en', { rate: 1.0 });
      clearTTSSettings('en');

      const settings = getTTSSettings('en');
      expect(settings).toEqual({});
    });

    it('does not affect other languages', () => {
      saveTTSSettings('en', { rate: 1.0 });
      saveTTSSettings('fr', { rate: 1.5 });
      clearTTSSettings('en');

      expect(getTTSSettings('en')).toEqual({});
      expect(getTTSSettings('fr')).toEqual({ rate: 1.5 });
    });

    it('handles non-existent language', () => {
      // Should not throw
      expect(() => clearTTSSettings('xx')).not.toThrow();
    });
  });

  // ===========================================================================
  // getAllTTSLanguages Tests
  // ===========================================================================

  describe('getAllTTSLanguages', () => {
    it('returns empty array when no settings stored', () => {
      const languages = getAllTTSLanguages();
      expect(languages).toEqual([]);
    });

    it('returns all stored language codes', () => {
      saveTTSSettings('en', { rate: 1.0 });
      saveTTSSettings('fr', { rate: 1.0 });
      saveTTSSettings('de', { rate: 1.0 });

      const languages = getAllTTSLanguages();
      expect(languages).toHaveLength(3);
      expect(languages).toContain('en');
      expect(languages).toContain('fr');
      expect(languages).toContain('de');
    });

    it('does not include non-TTS localStorage items', () => {
      localStorage.setItem('other.key', 'value');
      saveTTSSettings('en', { rate: 1.0 });

      const languages = getAllTTSLanguages();
      expect(languages).toEqual(['en']);
    });
  });

  // ===========================================================================
  // Migration Tests
  // ===========================================================================

  describe('isMigrationComplete', () => {
    it('returns false when migration flag not set', () => {
      expect(isMigrationComplete()).toBe(false);
    });

    it('returns true when migration flag is set', () => {
      localStorage.setItem('lwt.tts.migrated', '1');
      expect(isMigrationComplete()).toBe(true);
    });
  });

  describe('migrateTTSCookies', () => {
    it('migrates voice from cookie', () => {
      document.cookie = 'tts[enVoice]=Google%20US%20English';

      const count = migrateTTSCookies();

      expect(count).toBe(1);
      const settings = getTTSSettings('en');
      expect(settings.voice).toBe('Google US English');
    });

    it('migrates rate from cookie', () => {
      document.cookie = 'tts[frRate]=1.5';

      migrateTTSCookies();

      const settings = getTTSSettings('fr');
      expect(settings.rate).toBe(1.5);
    });

    it('migrates pitch from cookie', () => {
      document.cookie = 'tts[dePitch]=0.8';

      migrateTTSCookies();

      const settings = getTTSSettings('de');
      expect(settings.pitch).toBe(0.8);
    });

    it('migrates RegName as voice', () => {
      document.cookie = 'tts[enRegName]=Microsoft%20David';

      migrateTTSCookies();

      const settings = getTTSSettings('en');
      expect(settings.voice).toBe('Microsoft David');
    });

    it('migrates multiple settings for same language', () => {
      document.cookie = 'tts[enVoice]=Test%20Voice';
      document.cookie = 'tts[enRate]=1.2';
      document.cookie = 'tts[enPitch]=0.9';

      migrateTTSCookies();

      const settings = getTTSSettings('en');
      expect(settings).toEqual({
        voice: 'Test Voice',
        rate: 1.2,
        pitch: 0.9,
      });
    });

    it('migrates multiple languages', () => {
      document.cookie = 'tts[enRate]=1.0';
      document.cookie = 'tts[frRate]=1.5';

      const count = migrateTTSCookies();

      expect(count).toBe(2);
      expect(getTTSSettings('en').rate).toBe(1.0);
      expect(getTTSSettings('fr').rate).toBe(1.5);
    });

    it('sets migration flag after completion', () => {
      migrateTTSCookies();

      expect(isMigrationComplete()).toBe(true);
    });

    it('does not migrate again if already complete', () => {
      document.cookie = 'tts[enRate]=1.0';
      migrateTTSCookies();

      // Clear localStorage but keep migration flag
      const migrated = localStorage.getItem('lwt.tts.migrated');
      localStorage.clear();
      localStorage.setItem('lwt.tts.migrated', migrated!);

      // Set new cookies
      document.cookie = 'tts[frRate]=2.0';

      const count = migrateTTSCookies();

      expect(count).toBe(0);
      expect(getTTSSettings('fr')).toEqual({});
    });

    it('handles cookies with no TTS data', () => {
      document.cookie = 'other_cookie=value';

      const count = migrateTTSCookies();

      expect(count).toBe(0);
    });

    it('handles invalid rate values', () => {
      document.cookie = 'tts[enRate]=not-a-number';

      migrateTTSCookies();

      const settings = getTTSSettings('en');
      // NaN values should not be stored
      expect(settings.rate).toBeUndefined();
    });
  });

  // ===========================================================================
  // getTTSSettingsWithMigration Tests
  // ===========================================================================

  describe('getTTSSettingsWithMigration', () => {
    it('triggers migration on first call', () => {
      document.cookie = 'tts[enRate]=1.3';

      const settings = getTTSSettingsWithMigration('en');

      expect(settings.rate).toBe(1.3);
      expect(isMigrationComplete()).toBe(true);
    });

    it('does not trigger migration on subsequent calls', () => {
      localStorage.setItem('lwt.tts.migrated', '1');
      localStorage.setItem('lwt.tts.en', JSON.stringify({ rate: 1.0 }));
      document.cookie = 'tts[enRate]=2.0'; // This should be ignored

      const settings = getTTSSettingsWithMigration('en');

      expect(settings.rate).toBe(1.0);
    });

    it('returns settings from localStorage after migration', () => {
      saveTTSSettings('fr', { voice: 'French Voice' });
      localStorage.setItem('lwt.tts.migrated', '1');

      const settings = getTTSSettingsWithMigration('fr');

      expect(settings.voice).toBe('French Voice');
    });
  });
});

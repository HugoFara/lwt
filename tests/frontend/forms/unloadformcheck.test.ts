/**
 * Tests for unloadformcheck.ts - Form dirty state tracking
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  lwtFormCheck,
} from '../../../src/frontend/js/forms/unloadformcheck';

describe('unloadformcheck.ts', () => {
  beforeEach(() => {
    // Reset dirty state before each test
    lwtFormCheck.dirty = false;
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // lwtFormCheck Object Tests
  // ===========================================================================

  describe('lwtFormCheck', () => {
    describe('dirty property', () => {
      it('initializes to false', () => {
        lwtFormCheck.dirty = false;
        expect(lwtFormCheck.dirty).toBe(false);
      });

      it('can be set to true', () => {
        lwtFormCheck.dirty = true;
        expect(lwtFormCheck.dirty).toBe(true);
      });
    });

    describe('isDirtyMessage', () => {
      it('returns undefined when not dirty', () => {
        lwtFormCheck.dirty = false;
        expect(lwtFormCheck.isDirtyMessage()).toBeUndefined();
      });

      it('returns warning message when dirty', () => {
        lwtFormCheck.dirty = true;
        const message = lwtFormCheck.isDirtyMessage();
        expect(message).toBe('** You have unsaved changes! **');
      });
    });

    describe('makeDirty', () => {
      it('sets dirty to true', () => {
        lwtFormCheck.dirty = false;
        lwtFormCheck.makeDirty();
        expect(lwtFormCheck.dirty).toBe(true);
      });

      it('keeps dirty as true if already dirty', () => {
        lwtFormCheck.dirty = true;
        lwtFormCheck.makeDirty();
        expect(lwtFormCheck.dirty).toBe(true);
      });
    });

    describe('resetDirty', () => {
      it('sets dirty to false', () => {
        lwtFormCheck.dirty = true;
        lwtFormCheck.resetDirty();
        expect(lwtFormCheck.dirty).toBe(false);
      });

      it('keeps dirty as false if already clean', () => {
        lwtFormCheck.dirty = false;
        lwtFormCheck.resetDirty();
        expect(lwtFormCheck.dirty).toBe(false);
      });
    });

    describe('tagChanged', () => {
      it('sets dirty to true when not during initialization', () => {
        lwtFormCheck.dirty = false;
        lwtFormCheck.tagChanged(false);
        expect(lwtFormCheck.dirty).toBe(true);
      });

      it('does not change dirty during initialization', () => {
        lwtFormCheck.dirty = false;
        lwtFormCheck.tagChanged(true);
        expect(lwtFormCheck.dirty).toBe(false);
      });

      it('returns void', () => {
        const result = lwtFormCheck.tagChanged(false);
        expect(result).toBeUndefined();
      });
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('dirty state workflow: clean -> dirty -> clean', () => {
      // Start clean
      lwtFormCheck.dirty = false;
      expect(lwtFormCheck.isDirtyMessage()).toBeUndefined();

      // Make dirty
      lwtFormCheck.makeDirty();
      expect(lwtFormCheck.isDirtyMessage()).toBe('** You have unsaved changes! **');

      // Reset to clean
      lwtFormCheck.resetDirty();
      expect(lwtFormCheck.isDirtyMessage()).toBeUndefined();
    });

    it('tagChanged during tag operations', () => {
      lwtFormCheck.dirty = false;

      // Simulate tag initialization (should not make dirty)
      lwtFormCheck.tagChanged(true);
      expect(lwtFormCheck.dirty).toBe(false);

      // Simulate user adding a tag (should make dirty)
      lwtFormCheck.tagChanged(false);
      expect(lwtFormCheck.dirty).toBe(true);

      // Reset
      lwtFormCheck.resetDirty();
      expect(lwtFormCheck.dirty).toBe(false);

      // Simulate user removing a tag (should make dirty)
      lwtFormCheck.tagChanged(false);
      expect(lwtFormCheck.dirty).toBe(true);
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('multiple makeDirty calls do not affect state negatively', () => {
      lwtFormCheck.dirty = false;
      lwtFormCheck.makeDirty();
      lwtFormCheck.makeDirty();
      lwtFormCheck.makeDirty();
      expect(lwtFormCheck.dirty).toBe(true);
    });

    it('multiple resetDirty calls do not affect state negatively', () => {
      lwtFormCheck.dirty = true;
      lwtFormCheck.resetDirty();
      lwtFormCheck.resetDirty();
      lwtFormCheck.resetDirty();
      expect(lwtFormCheck.dirty).toBe(false);
    });

    it('tagChanged handles false duringInit', () => {
      lwtFormCheck.dirty = false;
      // When duringInit is false, it should make the form dirty
      lwtFormCheck.tagChanged(false);
      expect(lwtFormCheck.dirty).toBe(true);
    });

    it('isDirtyMessage returns consistent message', () => {
      lwtFormCheck.dirty = true;
      const msg1 = lwtFormCheck.isDirtyMessage();
      const msg2 = lwtFormCheck.isDirtyMessage();
      expect(msg1).toBe(msg2);
    });
  });

  // ===========================================================================
  // Type Safety Tests
  // ===========================================================================

  describe('Type Safety', () => {
    it('dirty is a boolean', () => {
      expect(typeof lwtFormCheck.dirty).toBe('boolean');
    });

    it('isDirtyMessage returns string or undefined', () => {
      lwtFormCheck.dirty = false;
      const resultClean = lwtFormCheck.isDirtyMessage();
      expect(resultClean === undefined || typeof resultClean === 'string').toBe(true);

      lwtFormCheck.dirty = true;
      const resultDirty = lwtFormCheck.isDirtyMessage();
      expect(typeof resultDirty).toBe('string');
    });

    it('makeDirty returns void', () => {
      const result = lwtFormCheck.makeDirty();
      expect(result).toBeUndefined();
    });

    it('resetDirty returns void', () => {
      const result = lwtFormCheck.resetDirty();
      expect(result).toBeUndefined();
    });

    it('tagChanged returns void', () => {
      const result = lwtFormCheck.tagChanged(false);
      expect(result).toBeUndefined();
    });
  });
});

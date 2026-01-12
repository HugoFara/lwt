/**
 * Tests for auth/pages/reset_password_form.ts - Password reset form validation
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock Alpine.js before importing the module
vi.mock('alpinejs', () => {
  const registeredData: Record<string, unknown> = {};
  return {
    default: {
      data: vi.fn((name: string, fn: () => unknown) => {
        registeredData[name] = fn;
      }),
      _registeredData: registeredData
    }
  };
});

import Alpine from 'alpinejs';
import { resetPasswordFormData } from '../../../src/frontend/js/modules/auth/pages/reset_password_form';

describe('auth/pages/reset_password_form.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // resetPasswordFormData Tests
  // ===========================================================================

  describe('resetPasswordFormData', () => {
    it('returns form data object with correct initial state', () => {
      const formData = resetPasswordFormData();

      expect(formData.loading).toBe(false);
      expect(formData.password).toBe('');
      expect(formData.passwordConfirm).toBe('');
      expect(formData.errors).toEqual({
        password: '',
        passwordConfirm: ''
      });
    });

    it('hasErrors returns true when password is empty', () => {
      const formData = resetPasswordFormData();

      expect(formData.hasErrors).toBe(true);
    });

    it('hasErrors returns true when passwordConfirm is empty', () => {
      const formData = resetPasswordFormData();
      formData.password = 'ValidPass1';

      expect(formData.hasErrors).toBe(true);
    });

    it('hasErrors returns false when both passwords are filled and valid', () => {
      const formData = resetPasswordFormData();
      formData.password = 'ValidPass1';
      formData.passwordConfirm = 'ValidPass1';

      expect(formData.hasErrors).toBe(false);
    });

    it('hasErrors returns true when password error exists', () => {
      const formData = resetPasswordFormData();
      formData.password = 'ValidPass1';
      formData.passwordConfirm = 'ValidPass1';
      formData.errors.password = 'Some error';

      expect(formData.hasErrors).toBe(true);
    });

    it('hasErrors returns true when passwordConfirm error exists', () => {
      const formData = resetPasswordFormData();
      formData.password = 'ValidPass1';
      formData.passwordConfirm = 'ValidPass1';
      formData.errors.passwordConfirm = 'Some error';

      expect(formData.hasErrors).toBe(true);
    });
  });

  // ===========================================================================
  // validatePassword Tests
  // ===========================================================================

  describe('validatePassword', () => {
    it('sets error when password is too short', () => {
      const formData = resetPasswordFormData();
      formData.password = 'Ab1';

      formData.validatePassword();

      expect(formData.errors.password).toBe('Password must be at least 8 characters');
    });

    it('sets error when password exceeds 128 characters', () => {
      const formData = resetPasswordFormData();
      formData.password = 'Ab1' + 'a'.repeat(126);

      formData.validatePassword();

      expect(formData.errors.password).toBe('Password must not exceed 128 characters');
    });

    it('sets error when password has no letters', () => {
      const formData = resetPasswordFormData();
      formData.password = '12345678';

      formData.validatePassword();

      expect(formData.errors.password).toBe('Password must contain at least one letter');
    });

    it('sets error when password has no numbers', () => {
      const formData = resetPasswordFormData();
      formData.password = 'abcdefgh';

      formData.validatePassword();

      expect(formData.errors.password).toBe('Password must contain at least one number');
    });

    it('clears error for valid password', () => {
      const formData = resetPasswordFormData();
      formData.password = 'Password1';
      formData.errors.password = 'Previous error';

      formData.validatePassword();

      expect(formData.errors.password).toBe('');
    });

    it('accepts password at exactly 8 characters', () => {
      const formData = resetPasswordFormData();
      formData.password = 'Abcdef12';

      formData.validatePassword();

      expect(formData.errors.password).toBe('');
    });

    it('accepts password at exactly 128 characters', () => {
      const formData = resetPasswordFormData();
      formData.password = 'Abc1' + 'a'.repeat(124);

      formData.validatePassword();

      expect(formData.errors.password).toBe('');
    });

    it('triggers validatePasswordConfirm when passwordConfirm is set', () => {
      const formData = resetPasswordFormData();
      formData.password = 'ValidPass1';
      formData.passwordConfirm = 'DifferentPass1';

      formData.validatePassword();

      expect(formData.errors.passwordConfirm).toBe('Passwords do not match');
    });

    it('does not trigger validatePasswordConfirm when passwordConfirm is empty', () => {
      const formData = resetPasswordFormData();
      formData.password = 'ValidPass1';
      formData.passwordConfirm = '';

      formData.validatePassword();

      expect(formData.errors.passwordConfirm).toBe('');
    });
  });

  // ===========================================================================
  // validatePasswordConfirm Tests
  // ===========================================================================

  describe('validatePasswordConfirm', () => {
    it('sets error when passwords do not match', () => {
      const formData = resetPasswordFormData();
      formData.password = 'Password1';
      formData.passwordConfirm = 'Password2';

      formData.validatePasswordConfirm();

      expect(formData.errors.passwordConfirm).toBe('Passwords do not match');
    });

    it('clears error when passwords match', () => {
      const formData = resetPasswordFormData();
      formData.password = 'Password1';
      formData.passwordConfirm = 'Password1';
      formData.errors.passwordConfirm = 'Previous error';

      formData.validatePasswordConfirm();

      expect(formData.errors.passwordConfirm).toBe('');
    });

    it('does not set error when passwordConfirm is empty', () => {
      const formData = resetPasswordFormData();
      formData.password = 'Password1';
      formData.passwordConfirm = '';

      formData.validatePasswordConfirm();

      expect(formData.errors.passwordConfirm).toBe('');
    });
  });

  // ===========================================================================
  // submitForm Tests
  // ===========================================================================

  describe('submitForm', () => {
    it('prevents submission when there are errors', () => {
      const formData = resetPasswordFormData();
      formData.password = 'short';
      formData.passwordConfirm = 'short';
      const mockEvent = { preventDefault: vi.fn() };

      formData.submitForm(mockEvent as unknown as Event);

      expect(mockEvent.preventDefault).toHaveBeenCalled();
      expect(formData.loading).toBe(false);
    });

    it('prevents submission when passwords do not match', () => {
      const formData = resetPasswordFormData();
      formData.password = 'ValidPass1';
      formData.passwordConfirm = 'DifferentPass1';
      const mockEvent = { preventDefault: vi.fn() };

      formData.submitForm(mockEvent as unknown as Event);

      expect(mockEvent.preventDefault).toHaveBeenCalled();
      expect(formData.loading).toBe(false);
    });

    it('sets loading to true when form is valid', () => {
      const formData = resetPasswordFormData();
      formData.password = 'ValidPass1';
      formData.passwordConfirm = 'ValidPass1';
      const mockEvent = { preventDefault: vi.fn() };

      formData.submitForm(mockEvent as unknown as Event);

      expect(mockEvent.preventDefault).not.toHaveBeenCalled();
      expect(formData.loading).toBe(true);
    });

    it('validates password before submission', () => {
      const formData = resetPasswordFormData();
      formData.password = 'short';
      formData.passwordConfirm = 'short';
      const mockEvent = { preventDefault: vi.fn() };

      formData.submitForm(mockEvent as unknown as Event);

      expect(formData.errors.password).toBe('Password must be at least 8 characters');
    });

    it('validates password confirmation before submission', () => {
      const formData = resetPasswordFormData();
      formData.password = 'ValidPass1';
      formData.passwordConfirm = 'DifferentPass1';
      const mockEvent = { preventDefault: vi.fn() };

      formData.submitForm(mockEvent as unknown as Event);

      expect(formData.errors.passwordConfirm).toBe('Passwords do not match');
    });
  });

});

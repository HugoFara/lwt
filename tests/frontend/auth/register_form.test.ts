/**
 * Tests for auth/pages/register_form.ts - User registration form validation
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

import { registerFormData } from '../../../src/frontend/js/modules/auth/pages/register_form';

describe('auth/pages/register_form.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  // ===========================================================================
  // registerFormData Tests
  // ===========================================================================

  describe('registerFormData', () => {
    it('returns form data object with correct initial state', () => {
      const formData = registerFormData();

      expect(formData.loading).toBe(false);
      expect(formData.password).toBe('');
      expect(formData.passwordConfirm).toBe('');
      expect(formData.errors).toEqual({
        username: '',
        email: '',
        password: '',
        passwordConfirm: ''
      });
    });

    it('hasErrors returns false when no errors', () => {
      const formData = registerFormData();

      expect(formData.hasErrors).toBe(false);
    });

    it('hasErrors returns true when any error exists', () => {
      const formData = registerFormData();

      formData.errors.username = 'Username is required';

      expect(formData.hasErrors).toBe(true);
    });
  });

  // ===========================================================================
  // validateUsername Tests
  // ===========================================================================

  describe('validateUsername', () => {
    it('sets error when username is empty', () => {
      const formData = registerFormData();

      formData.validateUsername('');

      expect(formData.errors.username).toBe('Username is required');
    });

    it('sets error when username is too short', () => {
      const formData = registerFormData();

      formData.validateUsername('ab');

      expect(formData.errors.username).toBe('Username must be at least 3 characters');
    });

    it('sets error when username exceeds 100 characters', () => {
      const formData = registerFormData();
      const longUsername = 'a'.repeat(101);

      formData.validateUsername(longUsername);

      expect(formData.errors.username).toBe('Username cannot exceed 100 characters');
    });

    it('sets error for invalid characters in username', () => {
      const formData = registerFormData();

      formData.validateUsername('user@name');

      expect(formData.errors.username).toBe('Username can only contain letters, numbers, underscores, and hyphens');
    });

    it('clears error for valid username', () => {
      const formData = registerFormData();
      formData.errors.username = 'Previous error';

      formData.validateUsername('valid_user-123');

      expect(formData.errors.username).toBe('');
    });

    it('accepts username with underscores and hyphens', () => {
      const formData = registerFormData();

      formData.validateUsername('user_name-123');

      expect(formData.errors.username).toBe('');
    });

    it('accepts username at exactly 3 characters', () => {
      const formData = registerFormData();

      formData.validateUsername('abc');

      expect(formData.errors.username).toBe('');
    });

    it('accepts username at exactly 100 characters', () => {
      const formData = registerFormData();
      const maxUsername = 'a'.repeat(100);

      formData.validateUsername(maxUsername);

      expect(formData.errors.username).toBe('');
    });
  });

  // ===========================================================================
  // validateEmail Tests
  // ===========================================================================

  describe('validateEmail', () => {
    it('sets error when email is empty', () => {
      const formData = registerFormData();

      formData.validateEmail('');

      expect(formData.errors.email).toBe('Email is required');
    });

    it('sets error for invalid email format', () => {
      const formData = registerFormData();

      formData.validateEmail('notanemail');

      expect(formData.errors.email).toBe('Please enter a valid email address');
    });

    it('sets error for email without domain', () => {
      const formData = registerFormData();

      formData.validateEmail('user@');

      expect(formData.errors.email).toBe('Please enter a valid email address');
    });

    it('sets error for email without TLD', () => {
      const formData = registerFormData();

      formData.validateEmail('user@domain');

      expect(formData.errors.email).toBe('Please enter a valid email address');
    });

    it('clears error for valid email', () => {
      const formData = registerFormData();
      formData.errors.email = 'Previous error';

      formData.validateEmail('user@example.com');

      expect(formData.errors.email).toBe('');
    });

    it('accepts email with subdomain', () => {
      const formData = registerFormData();

      formData.validateEmail('user@mail.example.com');

      expect(formData.errors.email).toBe('');
    });

    it('accepts email with plus sign', () => {
      const formData = registerFormData();

      formData.validateEmail('user+tag@example.com');

      expect(formData.errors.email).toBe('');
    });
  });

  // ===========================================================================
  // validatePassword Tests
  // ===========================================================================

  describe('validatePassword', () => {
    it('sets error when password is empty', () => {
      const formData = registerFormData();

      formData.validatePassword();

      expect(formData.errors.password).toBe('Password is required');
    });

    it('sets error when password is too short', () => {
      const formData = registerFormData();
      formData.password = 'Ab1';

      formData.validatePassword();

      expect(formData.errors.password).toBe('Password must be at least 8 characters');
    });

    it('sets error when password exceeds 128 characters', () => {
      const formData = registerFormData();
      formData.password = 'Ab1' + 'a'.repeat(126);

      formData.validatePassword();

      expect(formData.errors.password).toBe('Password cannot exceed 128 characters');
    });

    it('sets error when password has no letters', () => {
      const formData = registerFormData();
      formData.password = '12345678';

      formData.validatePassword();

      expect(formData.errors.password).toBe('Password must contain at least one letter');
    });

    it('sets error when password has no numbers', () => {
      const formData = registerFormData();
      formData.password = 'abcdefgh';

      formData.validatePassword();

      expect(formData.errors.password).toBe('Password must contain at least one number');
    });

    it('clears error for valid password', () => {
      const formData = registerFormData();
      formData.password = 'Password1';
      formData.errors.password = 'Previous error';

      formData.validatePassword();

      expect(formData.errors.password).toBe('');
    });

    it('accepts password at exactly 8 characters', () => {
      const formData = registerFormData();
      formData.password = 'Abcdef12';

      formData.validatePassword();

      expect(formData.errors.password).toBe('');
    });

    it('accepts password at exactly 128 characters', () => {
      const formData = registerFormData();
      formData.password = 'Abc1' + 'a'.repeat(124);

      formData.validatePassword();

      expect(formData.errors.password).toBe('');
    });

    it('triggers validatePasswordConfirm when called', () => {
      const formData = registerFormData();
      formData.password = 'ValidPass1';
      formData.passwordConfirm = 'DifferentPass1';

      formData.validatePassword();

      expect(formData.errors.passwordConfirm).toBe('Passwords do not match');
    });
  });

  // ===========================================================================
  // validatePasswordConfirm Tests
  // ===========================================================================

  describe('validatePasswordConfirm', () => {
    it('sets error when passwords do not match', () => {
      const formData = registerFormData();
      formData.password = 'Password1';
      formData.passwordConfirm = 'Password2';

      formData.validatePasswordConfirm();

      expect(formData.errors.passwordConfirm).toBe('Passwords do not match');
    });

    it('clears error when passwords match', () => {
      const formData = registerFormData();
      formData.password = 'Password1';
      formData.passwordConfirm = 'Password1';
      formData.errors.passwordConfirm = 'Previous error';

      formData.validatePasswordConfirm();

      expect(formData.errors.passwordConfirm).toBe('');
    });

    it('does not set error when passwordConfirm is empty', () => {
      const formData = registerFormData();
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
      const formData = registerFormData();
      formData.password = 'short';
      const mockEvent = { preventDefault: vi.fn() };

      formData.submitForm(mockEvent as unknown as Event);

      expect(mockEvent.preventDefault).toHaveBeenCalled();
      expect(formData.loading).toBe(false);
    });

    it('sets loading to true when form is valid', () => {
      const formData = registerFormData();
      formData.password = 'ValidPass1';
      formData.passwordConfirm = 'ValidPass1';
      const mockEvent = { preventDefault: vi.fn() };

      formData.submitForm(mockEvent as unknown as Event);

      expect(mockEvent.preventDefault).not.toHaveBeenCalled();
      expect(formData.loading).toBe(true);
    });

    it('validates password before submission', () => {
      const formData = registerFormData();
      formData.password = 'short';
      formData.passwordConfirm = 'short';
      const mockEvent = { preventDefault: vi.fn() };

      formData.submitForm(mockEvent as unknown as Event);

      expect(formData.errors.password).toBe('Password must be at least 8 characters');
    });

    it('validates password confirmation before submission', () => {
      const formData = registerFormData();
      formData.password = 'ValidPass1';
      formData.passwordConfirm = 'DifferentPass1';
      const mockEvent = { preventDefault: vi.fn() };

      formData.submitForm(mockEvent as unknown as Event);

      expect(formData.errors.passwordConfirm).toBe('Passwords do not match');
    });
  });

});

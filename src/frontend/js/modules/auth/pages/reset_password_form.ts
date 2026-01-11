/**
 * Reset Password Form Alpine.js component.
 *
 * Provides client-side validation for the password reset form.
 * Validates password strength and confirmation match.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';

interface ResetPasswordFormErrors {
  password: string;
  passwordConfirm: string;
}

interface ResetPasswordFormData {
  password: string;
  passwordConfirm: string;
  loading: boolean;
  errors: ResetPasswordFormErrors;
  readonly hasErrors: boolean;
  validatePassword(): void;
  validatePasswordConfirm(): void;
  submitForm(event: Event): void;
}

/**
 * Alpine.js data component for the password reset form.
 * Provides client-side validation for password fields.
 */
export function resetPasswordFormData(): ResetPasswordFormData {
  return {
    password: '',
    passwordConfirm: '',
    loading: false,
    errors: {
      password: '',
      passwordConfirm: ''
    },

    get hasErrors(): boolean {
      return (
        this.errors.password !== '' ||
        this.errors.passwordConfirm !== '' ||
        !this.password ||
        !this.passwordConfirm
      );
    },

    validatePassword(): void {
      this.errors.password = '';

      if (this.password.length < 8) {
        this.errors.password = 'Password must be at least 8 characters';
        return;
      }

      if (this.password.length > 128) {
        this.errors.password = 'Password must not exceed 128 characters';
        return;
      }

      if (!/[a-zA-Z]/.test(this.password)) {
        this.errors.password = 'Password must contain at least one letter';
        return;
      }

      if (!/[0-9]/.test(this.password)) {
        this.errors.password = 'Password must contain at least one number';
        return;
      }

      // Revalidate confirm if already entered
      if (this.passwordConfirm) {
        this.validatePasswordConfirm();
      }
    },

    validatePasswordConfirm(): void {
      this.errors.passwordConfirm = '';

      if (this.passwordConfirm && this.password !== this.passwordConfirm) {
        this.errors.passwordConfirm = 'Passwords do not match';
      }
    },

    submitForm(event: Event): void {
      this.validatePassword();
      this.validatePasswordConfirm();

      if (this.hasErrors) {
        event.preventDefault();
        return;
      }

      this.loading = true;
    }
  };
}

/**
 * Initialize the reset password form Alpine.js component.
 * This must be called before Alpine.start().
 */
export function initResetPasswordFormAlpine(): void {
  Alpine.data('resetPasswordForm', resetPasswordFormData);
}

// Expose for global access if needed
declare global {
  interface Window {
    resetPasswordFormData: typeof resetPasswordFormData;
    initResetPasswordFormAlpine: typeof initResetPasswordFormAlpine;
  }
}

window.resetPasswordFormData = resetPasswordFormData;
window.initResetPasswordFormAlpine = initResetPasswordFormAlpine;

// Register Alpine data component immediately (before Alpine.start() in main.ts)
initResetPasswordFormAlpine();

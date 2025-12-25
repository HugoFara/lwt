/**
 * Register Form Alpine.js component.
 *
 * Provides client-side validation for the user registration form.
 * Validates username, email, password, and password confirmation.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';

interface RegisterFormErrors {
  username: string;
  email: string;
  password: string;
  passwordConfirm: string;
}

interface RegisterFormData {
  loading: boolean;
  password: string;
  passwordConfirm: string;
  errors: RegisterFormErrors;
  readonly hasErrors: boolean;
  validateUsername(value: string): void;
  validateEmail(value: string): void;
  validatePassword(): void;
  validatePasswordConfirm(): void;
  submitForm(event: Event): void;
}

/**
 * Alpine.js data component for the registration form.
 * Provides client-side validation for username, email, and password fields.
 */
export function registerFormData(): RegisterFormData {
  return {
    loading: false,
    password: '',
    passwordConfirm: '',
    errors: {
      username: '',
      email: '',
      password: '',
      passwordConfirm: ''
    },

    get hasErrors(): boolean {
      return Object.values(this.errors).some((e: string) => e !== '');
    },

    validateUsername(value: string): void {
      if (!value) {
        this.errors.username = 'Username is required';
      } else if (value.length < 3) {
        this.errors.username = 'Username must be at least 3 characters';
      } else if (value.length > 100) {
        this.errors.username = 'Username cannot exceed 100 characters';
      } else if (!/^[a-zA-Z0-9_-]+$/.test(value)) {
        this.errors.username = 'Username can only contain letters, numbers, underscores, and hyphens';
      } else {
        this.errors.username = '';
      }
    },

    validateEmail(value: string): void {
      if (!value) {
        this.errors.email = 'Email is required';
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        this.errors.email = 'Please enter a valid email address';
      } else {
        this.errors.email = '';
      }
    },

    validatePassword(): void {
      if (!this.password) {
        this.errors.password = 'Password is required';
      } else if (this.password.length < 8) {
        this.errors.password = 'Password must be at least 8 characters';
      } else if (this.password.length > 128) {
        this.errors.password = 'Password cannot exceed 128 characters';
      } else if (!/[a-zA-Z]/.test(this.password)) {
        this.errors.password = 'Password must contain at least one letter';
      } else if (!/[0-9]/.test(this.password)) {
        this.errors.password = 'Password must contain at least one number';
      } else {
        this.errors.password = '';
      }
      this.validatePasswordConfirm();
    },

    validatePasswordConfirm(): void {
      if (this.passwordConfirm && this.password !== this.passwordConfirm) {
        this.errors.passwordConfirm = 'Passwords do not match';
      } else {
        this.errors.passwordConfirm = '';
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
 * Initialize the register form Alpine.js component.
 * This must be called before Alpine.start().
 */
export function initRegisterFormAlpine(): void {
  Alpine.data('registerForm', registerFormData);
}

// Expose for global access if needed
declare global {
  interface Window {
    registerFormData: typeof registerFormData;
    initRegisterFormAlpine: typeof initRegisterFormAlpine;
  }
}

window.registerFormData = registerFormData;
window.initRegisterFormAlpine = initRegisterFormAlpine;

// Register Alpine data component immediately (before Alpine.start() in main.ts)
initRegisterFormAlpine();

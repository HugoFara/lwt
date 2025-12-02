/**
 * Alpine.js Navbar Component
 *
 * Provides a responsive navigation bar with mobile support.
 * Replaces the legacy quick menu dropdown with a modern navbar.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';

interface NavbarData {
  isOpen: boolean;
  activeDropdown: string | null;

  init(): void;
  toggle(): void;
  close(): void;
  toggleDropdown(name: string): void;
  closeDropdowns(): void;
  navigate(url: string): void;
}

/**
 * Alpine.js data component for the navbar.
 */
export function navbarData(): NavbarData {
  return {
    isOpen: false,
    activeDropdown: null,

    init() {
      // Close navbar when clicking outside
      document.addEventListener('click', (e) => {
        const navbar = document.querySelector('.navbar');
        if (navbar && !navbar.contains(e.target as Node)) {
          this.close();
        }
      });

      // Close navbar on escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          this.close();
        }
      });
    },

    toggle() {
      this.isOpen = !this.isOpen;
      if (!this.isOpen) {
        this.closeDropdowns();
      }
    },

    close() {
      this.isOpen = false;
      this.closeDropdowns();
    },

    toggleDropdown(name: string) {
      if (this.activeDropdown === name) {
        this.activeDropdown = null;
      } else {
        this.activeDropdown = name;
      }
    },

    closeDropdowns() {
      this.activeDropdown = null;
    },

    navigate(url: string) {
      this.close();
      window.location.href = url;
    }
  };
}

/**
 * Initialize the navbar Alpine.js component.
 * This must be called before Alpine.start().
 */
export function initNavbarAlpine(): void {
  Alpine.data('navbar', navbarData);
}

// Expose for global access
declare global {
  interface Window {
    navbarData: typeof navbarData;
    initNavbarAlpine: typeof initNavbarAlpine;
  }
}

window.navbarData = navbarData;
window.initNavbarAlpine = initNavbarAlpine;

// Register Alpine data component immediately
initNavbarAlpine();

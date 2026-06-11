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
import { setLangAsync } from '@modules/language/stores/language_settings';
import { getCsrfToken, apiGet } from '@shared/api/client';
import { renderNavbar, type NavbarData as NavbarChromeData } from '@shared/components/navbar_renderer';
import { initIconsIn } from '@shared/icons/lucide_icons';

interface NavbarData {
  isOpen: boolean;
  activeDropdown: string | null;

  init(): void;
  toggle(): void;
  open(): void;
  close(): void;
  toggleDropdown(name: string): void;
  closeDropdowns(): void;
  navigate(url: string): void;
  switchLanguage(event: Event): void;
  logout(): void;
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

      // Make the hardware/browser Back button close the mobile drawer instead
      // of leaving the page. Opening the drawer pushes a history entry; Back
      // pops it (popstate) and we just reflect that by closing. This is what
      // stops Back from exiting the Android app shell while the menu is open.
      window.addEventListener('popstate', () => {
        if (this.isOpen) {
          this.isOpen = false;
          this.closeDropdowns();
        }
      });
    },

    toggle() {
      if (this.isOpen) {
        this.close();
      } else {
        this.open();
      }
    },

    open() {
      this.isOpen = true;
      // Push a history entry so Back closes the drawer (see the popstate
      // handler in init). Guard against stacking duplicates.
      if (!(history.state && history.state.lwtNavbar)) {
        history.pushState({ lwtNavbar: true }, '');
      }
    },

    close() {
      if (this.isOpen) {
        this.isOpen = false;
        this.closeDropdowns();
        // Drop the history entry we pushed on open, if it's still current.
        if (history.state && history.state.lwtNavbar) {
          history.back();
        }
      } else {
        // Desktop dropdowns can be open without the mobile drawer.
        this.closeDropdowns();
      }
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
      // Full-page navigation discards our pushed history state, so close
      // directly rather than via close() (which would race history.back()
      // against the assignment below).
      this.isOpen = false;
      this.closeDropdowns();
      window.location.href = url;
    },

    switchLanguage(event: Event) {
      const select = event.target as HTMLSelectElement;
      const languageId = select.value;
      if (!languageId) return;

      setLangAsync(languageId).then(() => {
        // Strip filterlang from the URL so the new DB setting takes effect
        const url = new URL(window.location.href);
        if (url.searchParams.has('filterlang')) {
          url.searchParams.delete('filterlang');
          window.location.href = url.toString();
        } else {
          window.location.reload();
        }
      }).catch((error) => {
        console.error('Failed to change language:', error);
      });
    },

    logout() {
      // POST so `<img src=/logout>`-style cross-site GETs cannot log the user
      // out; include the CSRF token so the server's CsrfMiddleware accepts it.
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '/logout';

      const tokenField = document.createElement('input');
      tokenField.type = 'hidden';
      tokenField.name = '_csrf_token';
      tokenField.value = getCsrfToken();
      form.appendChild(tokenField);

      document.body.appendChild(form);
      form.submit();
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

/**
 * Render and hydrate the global navbar into its placeholder.
 *
 * PageLayoutHelper::buildNavbarPlaceholder() emits an empty
 * `<div id="navbar-root" data-current-page="…">`; this fetches the chrome data
 * from GET /api/v1/navbar, builds the markup (navbar_renderer.ts) and hydrates
 * it. Called from main.ts after Alpine.start() — so we hand the freshly injected
 * subtree to Alpine.initTree (the navbar/themeToggle/navbarStreak components are
 * already registered) and then realise its lucide icons.
 *
 * No-ops on pages without the placeholder (login, minimal headers).
 */
export async function mountNavbar(): Promise<void> {
  const root = document.getElementById('navbar-root');
  if (!root) {
    return;
  }
  const currentPage = root.getAttribute('data-current-page') ?? '';
  try {
    const res = await apiGet<NavbarChromeData>('/navbar');
    if (!res.data) {
      return;
    }
    root.innerHTML = renderNavbar(res.data, currentPage);
    Alpine.initTree(root);
    initIconsIn(root);
  } catch (error) {
    console.error('Failed to load navbar:', error);
  }
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

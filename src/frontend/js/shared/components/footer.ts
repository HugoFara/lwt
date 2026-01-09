/**
 * Footer Alpine.js component.
 *
 * Provides a reactive footer component for displaying license
 * and project information.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import Alpine from 'alpinejs';

interface FooterLink {
  href: string;
  text: string;
  external?: boolean;
}

interface FooterData {
  licenseUrl: string;
  licenseImageUrl: string;
  projectUrl: string;
  publicDomainUrl: string;

  links: {
    license: FooterLink;
    project: FooterLink;
    publicDomain: FooterLink;
    unlicense: FooterLink;
  };
}

/**
 * Get the application base path from meta tag.
 */
function getBasePath(): string {
  const meta = document.querySelector('meta[name="lwt-base-path"]');
  return meta ? meta.getAttribute('content') || '' : '';
}

/**
 * Alpine.js data component for the footer.
 */
export function footerData(): FooterData {
  const basePath = getBasePath();
  return {
    licenseUrl: 'http://unlicense.org/',
    licenseImageUrl: basePath + '/assets/images/public_domain.png',
    projectUrl: 'https://sourceforge.net/projects/learning-with-texts/',
    publicDomainUrl: 'https://en.wikipedia.org/wiki/Public_domain_software',

    links: {
      license: {
        href: 'http://unlicense.org/',
        text: 'More information and detailed Unlicense ...',
        external: true
      },
      project: {
        href: 'https://sourceforge.net/projects/learning-with-texts/',
        text: '"Learning with Texts" (LWT)',
        external: true
      },
      publicDomain: {
        href: 'https://en.wikipedia.org/wiki/Public_domain_software',
        text: 'PUBLIC DOMAIN',
        external: true
      },
      unlicense: {
        href: 'http://unlicense.org/',
        text: 'More information and detailed Unlicense ...',
        external: true
      }
    }
  };
}

/**
 * Initialize the footer Alpine.js component.
 * This must be called before Alpine.start().
 */
export function initFooterAlpine(): void {
  Alpine.data('footer', footerData);
}

// Expose for global access if needed
declare global {
  interface Window {
    footerData: typeof footerData;
    initFooterAlpine: typeof initFooterAlpine;
  }
}

window.footerData = footerData;
window.initFooterAlpine = initFooterAlpine;

// Register Alpine data component immediately (before Alpine.start() in main.ts)
initFooterAlpine();

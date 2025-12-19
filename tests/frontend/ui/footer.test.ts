/**
 * Tests for ui/footer.ts - Footer Alpine.js component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { footerData, initFooterAlpine } from '../../../src/frontend/js/ui/footer';

describe('ui/footer.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // footerData Tests
  // ===========================================================================

  describe('footerData', () => {
    it('returns an object with expected properties', () => {
      const data = footerData();

      expect(data).toHaveProperty('licenseUrl');
      expect(data).toHaveProperty('licenseImageUrl');
      expect(data).toHaveProperty('projectUrl');
      expect(data).toHaveProperty('publicDomainUrl');
      expect(data).toHaveProperty('links');
    });

    it('returns correct license URL', () => {
      const data = footerData();

      expect(data.licenseUrl).toBe('http://unlicense.org/');
    });

    it('returns correct license image URL', () => {
      const data = footerData();

      expect(data.licenseImageUrl).toBe('/assets/images/public_domain.png');
    });

    it('returns correct project URL', () => {
      const data = footerData();

      expect(data.projectUrl).toBe('https://sourceforge.net/projects/learning-with-texts/');
    });

    it('returns correct public domain URL', () => {
      const data = footerData();

      expect(data.publicDomainUrl).toBe('https://en.wikipedia.org/wiki/Public_domain_software');
    });

    it('returns links object with all required links', () => {
      const data = footerData();

      expect(data.links).toHaveProperty('license');
      expect(data.links).toHaveProperty('project');
      expect(data.links).toHaveProperty('publicDomain');
      expect(data.links).toHaveProperty('unlicense');
    });

    it('returns license link with correct properties', () => {
      const data = footerData();

      expect(data.links.license.href).toBe('http://unlicense.org/');
      expect(data.links.license.text).toBe('More information and detailed Unlicense ...');
      expect(data.links.license.external).toBe(true);
    });

    it('returns project link with correct properties', () => {
      const data = footerData();

      expect(data.links.project.href).toBe('https://sourceforge.net/projects/learning-with-texts/');
      expect(data.links.project.text).toBe('"Learning with Texts" (LWT)');
      expect(data.links.project.external).toBe(true);
    });

    it('returns publicDomain link with correct properties', () => {
      const data = footerData();

      expect(data.links.publicDomain.href).toBe('https://en.wikipedia.org/wiki/Public_domain_software');
      expect(data.links.publicDomain.text).toBe('PUBLIC DOMAIN');
      expect(data.links.publicDomain.external).toBe(true);
    });

    it('returns unlicense link with correct properties', () => {
      const data = footerData();

      expect(data.links.unlicense.href).toBe('http://unlicense.org/');
      expect(data.links.unlicense.text).toBe('More information and detailed Unlicense ...');
      expect(data.links.unlicense.external).toBe(true);
    });

    it('returns fresh data on each call', () => {
      const data1 = footerData();
      const data2 = footerData();

      expect(data1).not.toBe(data2);
      expect(data1.licenseUrl).toBe(data2.licenseUrl);
    });

    it('all external links have external property set to true', () => {
      const data = footerData();

      Object.values(data.links).forEach(link => {
        if (link.href.startsWith('http')) {
          expect(link.external).toBe(true);
        }
      });
    });
  });

  // ===========================================================================
  // initFooterAlpine Tests
  // ===========================================================================

  describe('initFooterAlpine', () => {
    it('does not throw when called', () => {
      expect(() => initFooterAlpine()).not.toThrow();
    });

    it('can be called multiple times without error', () => {
      expect(() => {
        initFooterAlpine();
        initFooterAlpine();
        initFooterAlpine();
      }).not.toThrow();
    });
  });

  // ===========================================================================
  // Window Exports Tests
  // ===========================================================================

  describe('Window Exports', () => {
    it('exposes footerData on window', () => {
      expect(window.footerData).toBeDefined();
      expect(typeof window.footerData).toBe('function');
    });

    it('exposes initFooterAlpine on window', () => {
      expect(window.initFooterAlpine).toBeDefined();
      expect(typeof window.initFooterAlpine).toBe('function');
    });

    it('window.footerData returns expected data structure', () => {
      const data = window.footerData();

      expect(data.licenseUrl).toBeDefined();
      expect(data.links).toBeDefined();
    });
  });

  // ===========================================================================
  // Data Consistency Tests
  // ===========================================================================

  describe('Data Consistency', () => {
    it('license URL matches links.license.href', () => {
      const data = footerData();

      expect(data.licenseUrl).toBe(data.links.license.href);
    });

    it('project URL matches links.project.href', () => {
      const data = footerData();

      expect(data.projectUrl).toBe(data.links.project.href);
    });

    it('publicDomain URL matches links.publicDomain.href', () => {
      const data = footerData();

      expect(data.publicDomainUrl).toBe(data.links.publicDomain.href);
    });

    it('all URLs are valid format', () => {
      const data = footerData();
      const urlPattern = /^(https?:\/\/|\/)/;

      expect(data.licenseUrl).toMatch(urlPattern);
      expect(data.licenseImageUrl).toMatch(urlPattern);
      expect(data.projectUrl).toMatch(urlPattern);
      expect(data.publicDomainUrl).toMatch(urlPattern);
    });

    it('all link texts are non-empty strings', () => {
      const data = footerData();

      Object.values(data.links).forEach(link => {
        expect(typeof link.text).toBe('string');
        expect(link.text.length).toBeGreaterThan(0);
      });
    });
  });
});

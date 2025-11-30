/**
 * Tests for feed_wizard_common.ts - Shared functionality for feed wizard steps
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  setupWizardHeader,
  initWizardStep1,
  initWizardCommon
} from '../../../src/frontend/js/feeds/feed_wizard_common';

describe('feed_wizard_common.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // setupWizardHeader Tests
  // ===========================================================================

  describe('setupWizardHeader', () => {
    beforeEach(() => {
      document.body.innerHTML = '<h1>Original Title</h1><h1>Second H1</h1>';
    });

    it('sets up wizard step header with title', () => {
      setupWizardHeader({
        step: 2,
        title: 'Select Article'
      });

      const lastH1 = $('h1').eq(-1);
      expect(lastH1.html()).toContain('Feed Wizard');
      expect(lastH1.html()).toContain('Step 2');
      expect(lastH1.html()).toContain('Select Article');
    });

    it('includes help link when provided', () => {
      setupWizardHeader({
        step: 1,
        title: 'Insert URI',
        helpLink: 'docs/info.html#feed_wizard'
      });

      const lastH1 = $('h1').eq(-1);
      expect(lastH1.html()).toContain('href="docs/info.html#feed_wizard"');
      expect(lastH1.html()).toContain('question-frame.png');
    });

    it('does not include help link when not provided', () => {
      setupWizardHeader({
        step: 3,
        title: 'Filter Text'
      });

      const lastH1 = $('h1').eq(-1);
      expect(lastH1.html()).not.toContain('href=');
      expect(lastH1.html()).not.toContain('question-frame.png');
    });

    it('centers the header text', () => {
      setupWizardHeader({
        step: 1,
        title: 'Test'
      });

      const lastH1 = $('h1').eq(-1);
      expect(lastH1.css('text-align')).toBe('center');
    });

    it('modifies only the last h1 element', () => {
      setupWizardHeader({
        step: 1,
        title: 'New Title'
      });

      expect($('h1').eq(0).text()).toBe('Original Title');
      expect($('h1').eq(1).html()).toContain('Feed Wizard');
    });
  });

  // ===========================================================================
  // initWizardStep1 Tests
  // ===========================================================================

  describe('initWizardStep1', () => {
    it('sets up header for step 1 when config element exists', () => {
      document.body.innerHTML = `
        <div id="wizard-step1-config"></div>
        <h1>Title</h1>
      `;

      initWizardStep1();

      const h1 = $('h1').eq(-1);
      expect(h1.html()).toContain('Step 1');
      expect(h1.html()).toContain('Insert Newsfeed URI');
      expect(h1.html()).toContain('docs/info.html#feed_wizard');
    });

    it('does nothing when config element is missing', () => {
      document.body.innerHTML = '<h1>Original Title</h1>';

      initWizardStep1();

      expect($('h1').text()).toBe('Original Title');
    });
  });

  // ===========================================================================
  // initWizardCommon Tests
  // ===========================================================================

  describe('initWizardCommon', () => {
    it('binds click handler to wizard-cancel buttons', () => {
      document.body.innerHTML = `
        <button data-action="wizard-cancel" data-url="/feeds/edit?del_wiz=1">Cancel</button>
      `;

      initWizardCommon();

      const button = document.querySelector<HTMLButtonElement>('[data-action="wizard-cancel"]')!;

      // Click should attempt to navigate
      expect(() => button.click()).not.toThrow();
    });

    it('prevents default button action', () => {
      document.body.innerHTML = `
        <button data-action="wizard-cancel">Cancel</button>
      `;

      initWizardCommon();

      const button = document.querySelector<HTMLButtonElement>('[data-action="wizard-cancel"]')!;
      const clickEvent = new MouseEvent('click', { cancelable: true, bubbles: true });
      button.dispatchEvent(clickEvent);

      expect(clickEvent.defaultPrevented).toBe(true);
    });

    it('uses default URL when data-url is not provided', () => {
      document.body.innerHTML = `
        <button data-action="wizard-cancel">Cancel</button>
      `;

      initWizardCommon();

      const button = document.querySelector<HTMLButtonElement>('[data-action="wizard-cancel"]')!;

      // Should not throw even without URL
      expect(() => button.click()).not.toThrow();
    });

    it('uses custom URL when data-url is provided', () => {
      document.body.innerHTML = `
        <button data-action="wizard-cancel" data-url="/custom/cancel?param=1">Cancel</button>
      `;

      initWizardCommon();

      const button = document.querySelector<HTMLButtonElement>('[data-action="wizard-cancel"]')!;

      expect(() => button.click()).not.toThrow();
    });

    it('works with dynamically added buttons (event delegation)', () => {
      document.body.innerHTML = '<div id="container"></div>';

      initWizardCommon();

      // Add button after init
      const newButton = document.createElement('button');
      newButton.setAttribute('data-action', 'wizard-cancel');
      document.getElementById('container')!.appendChild(newButton);

      const clickEvent = new MouseEvent('click', { cancelable: true, bubbles: true });
      newButton.dispatchEvent(clickEvent);

      expect(clickEvent.defaultPrevented).toBe(true);
    });
  });

  // ===========================================================================
  // Auto-Initialization Tests
  // ===========================================================================

  describe('auto-initialization', () => {
    it('does not throw when no wizard elements exist', () => {
      document.body.innerHTML = '<div>Regular page content</div>';

      // Simulate document ready
      expect(() => {
        initWizardCommon();
        initWizardStep1();
      }).not.toThrow();
    });

    it('initializes step 1 when config element is present', () => {
      document.body.innerHTML = `
        <div id="wizard-step1-config"></div>
        <h1>Page Title</h1>
      `;

      // Simulate initialization
      initWizardCommon();
      initWizardStep1();

      expect($('h1').html()).toContain('Feed Wizard');
    });
  });

  // ===========================================================================
  // Multiple Buttons Tests
  // ===========================================================================

  describe('multiple elements', () => {
    it('handles multiple cancel buttons', () => {
      document.body.innerHTML = `
        <button data-action="wizard-cancel" data-url="/url1">Cancel 1</button>
        <button data-action="wizard-cancel" data-url="/url2">Cancel 2</button>
      `;

      initWizardCommon();

      const buttons = document.querySelectorAll<HTMLButtonElement>('[data-action="wizard-cancel"]');
      expect(buttons.length).toBe(2);

      buttons.forEach((button) => {
        const clickEvent = new MouseEvent('click', { cancelable: true, bubbles: true });
        button.dispatchEvent(clickEvent);
        expect(clickEvent.defaultPrevented).toBe(true);
      });
    });

    it('handles multiple h1 elements correctly', () => {
      document.body.innerHTML = `
        <h1>First H1</h1>
        <h1>Second H1</h1>
        <h1>Third H1</h1>
      `;

      setupWizardHeader({
        step: 1,
        title: 'Test'
      });

      // Only the last h1 should be modified
      expect($('h1').eq(0).text()).toBe('First H1');
      expect($('h1').eq(1).text()).toBe('Second H1');
      expect($('h1').eq(2).html()).toContain('Feed Wizard');
    });
  });

  // ===========================================================================
  // Edge Cases Tests
  // ===========================================================================

  describe('edge cases', () => {
    it('handles empty h1 element', () => {
      document.body.innerHTML = '<h1></h1>';

      setupWizardHeader({
        step: 1,
        title: 'Test'
      });

      expect($('h1').html()).toContain('Feed Wizard');
    });

    it('handles special characters in title', () => {
      document.body.innerHTML = '<h1></h1>';

      setupWizardHeader({
        step: 1,
        title: 'Test & Filter <Items>'
      });

      // Note: The function uses .html() which does NOT escape HTML,
      // so & becomes &amp; but <Items> becomes an actual HTML element
      const html = $('h1').html();
      expect(html).toContain('Test &amp; Filter');
      expect(html).toContain('Feed Wizard');
    });

    it('handles no h1 elements gracefully', () => {
      document.body.innerHTML = '<div>No h1 here</div>';

      // Should not throw when there are no h1 elements
      expect(() => {
        setupWizardHeader({
          step: 1,
          title: 'Test'
        });
      }).not.toThrow();
    });
  });
});

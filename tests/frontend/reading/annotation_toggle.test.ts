/**
 * Tests for annotation_toggle.ts - Show/hide translations and annotations
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  doHideTranslations,
  doShowTranslations,
  doHideAnnotations,
  doShowAnnotations,
  closeWindow,
  initAnnotationToggles
} from '../../../src/frontend/js/reading/annotation_toggle';

describe('annotation_toggle.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // doHideTranslations Tests
  // ===========================================================================

  describe('doHideTranslations', () => {
    it('shows the show button and hides the hide button', () => {
      document.body.innerHTML = `
        <button id="showt" style="display: none">Show Translations</button>
        <button id="hidet">Hide Translations</button>
      `;

      doHideTranslations();

      expect($('#showt').css('display')).not.toBe('none');
      expect($('#hidet').css('display')).toBe('none');
    });

    it('sets translation ruby text to background color', () => {
      document.body.innerHTML = `
        <button id="showt" style="display: none"></button>
        <button id="hidet"></button>
        <ruby class="anntermruby">Translation</ruby>
        <ruby class="anntermruby">Another</ruby>
      `;

      doHideTranslations();

      $('.anntermruby').each(function() {
        expect($(this).css('color')).toBe('rgb(229, 228, 226)');  // #E5E4E2
        expect($(this).css('background-color')).toBe('rgb(229, 228, 226)');
      });
    });
  });

  // ===========================================================================
  // doShowTranslations Tests
  // ===========================================================================

  describe('doShowTranslations', () => {
    it('hides the show button and shows the hide button', () => {
      document.body.innerHTML = `
        <button id="showt">Show Translations</button>
        <button id="hidet" style="display: none">Hide Translations</button>
      `;

      doShowTranslations();

      expect($('#showt').css('display')).toBe('none');
      expect($('#hidet').css('display')).not.toBe('none');
    });

    it('restores translation ruby text to normal visibility', () => {
      document.body.innerHTML = `
        <button id="showt"></button>
        <button id="hidet" style="display: none"></button>
        <ruby class="anntermruby" style="color: #E5E4E2; background-color: #E5E4E2">Translation</ruby>
      `;

      doShowTranslations();

      const ruby = $('.anntermruby');
      // jQuery sets color to 'inherit' which browser may compute differently
      // The key is that it's no longer the hidden color #E5E4E2
      expect(ruby.css('color')).not.toBe('rgb(229, 228, 226)');
      // Background color is cleared (may be '' or 'rgba(0, 0, 0, 0)' in different environments)
      expect(['', 'rgba(0, 0, 0, 0)', 'transparent']).toContain(ruby.css('background-color'));
    });
  });

  // ===========================================================================
  // doHideAnnotations Tests
  // ===========================================================================

  describe('doHideAnnotations', () => {
    it('shows the show button and hides the hide button', () => {
      document.body.innerHTML = `
        <button id="show" style="display: none">Show Annotations</button>
        <button id="hide">Hide Annotations</button>
      `;

      doHideAnnotations();

      expect($('#show').css('display')).not.toBe('none');
      expect($('#hide').css('display')).toBe('none');
    });

    it('sets annotation ruby text to background color', () => {
      document.body.innerHTML = `
        <button id="show" style="display: none"></button>
        <button id="hide"></button>
        <ruby class="anntransruby2">Annotation</ruby>
        <ruby class="anntransruby2">Another</ruby>
      `;

      doHideAnnotations();

      $('.anntransruby2').each(function() {
        expect($(this).css('color')).toBe('rgb(200, 220, 240)');  // #C8DCF0
        expect($(this).css('background-color')).toBe('rgb(200, 220, 240)');
      });
    });
  });

  // ===========================================================================
  // doShowAnnotations Tests
  // ===========================================================================

  describe('doShowAnnotations', () => {
    it('hides the show button and shows the hide button', () => {
      document.body.innerHTML = `
        <button id="show">Show Annotations</button>
        <button id="hide" style="display: none">Hide Annotations</button>
      `;

      doShowAnnotations();

      expect($('#show').css('display')).toBe('none');
      expect($('#hide').css('display')).not.toBe('none');
    });

    it('restores annotation ruby text to normal visibility', () => {
      document.body.innerHTML = `
        <button id="show"></button>
        <button id="hide" style="display: none"></button>
        <ruby class="anntransruby2" style="color: #C8DCF0; background-color: #C8DCF0">Annotation</ruby>
      `;

      doShowAnnotations();

      const ruby = $('.anntransruby2');
      // jQuery sets color to '' which browser may compute as black or inherit
      // The key is that it's no longer the hidden color #C8DCF0
      expect(ruby.css('color')).not.toBe('rgb(200, 220, 240)');
      // Background color is cleared (may be '' or 'rgba(0, 0, 0, 0)' in different environments)
      expect(['', 'rgba(0, 0, 0, 0)', 'transparent']).toContain(ruby.css('background-color'));
    });
  });

  // ===========================================================================
  // closeWindow Tests
  // ===========================================================================

  describe('closeWindow', () => {
    it('calls window.top.close', () => {
      const closeSpy = vi.fn();
      Object.defineProperty(window, 'top', {
        value: { close: closeSpy },
        writable: true,
        configurable: true
      });

      closeWindow();

      expect(closeSpy).toHaveBeenCalled();
    });

    it('handles null window.top gracefully', () => {
      Object.defineProperty(window, 'top', {
        value: null,
        writable: true,
        configurable: true
      });

      expect(() => closeWindow()).not.toThrow();
    });
  });

  // ===========================================================================
  // initAnnotationToggles Tests
  // ===========================================================================

  describe('initAnnotationToggles', () => {
    it('sets up hide translations button handler', () => {
      document.body.innerHTML = `
        <button id="showt" style="display: none"></button>
        <button id="hidet"></button>
        <button data-action="hide-translations">Hide Translations</button>
        <ruby class="anntermruby">Test</ruby>
      `;

      initAnnotationToggles();

      const button = document.querySelector('[data-action="hide-translations"]')!;
      button.dispatchEvent(new Event('click'));

      expect($('#showt').css('display')).not.toBe('none');
    });

    it('sets up show translations button handler', () => {
      document.body.innerHTML = `
        <button id="showt"></button>
        <button id="hidet" style="display: none"></button>
        <button data-action="show-translations">Show Translations</button>
        <ruby class="anntermruby" style="color: #E5E4E2">Test</ruby>
      `;

      initAnnotationToggles();

      const button = document.querySelector('[data-action="show-translations"]')!;
      button.dispatchEvent(new Event('click'));

      expect($('#showt').css('display')).toBe('none');
    });

    it('sets up hide annotations button handler', () => {
      document.body.innerHTML = `
        <button id="show" style="display: none"></button>
        <button id="hide"></button>
        <button data-action="hide-annotations">Hide Annotations</button>
        <ruby class="anntransruby2">Test</ruby>
      `;

      initAnnotationToggles();

      const button = document.querySelector('[data-action="hide-annotations"]')!;
      button.dispatchEvent(new Event('click'));

      expect($('#show').css('display')).not.toBe('none');
    });

    it('sets up show annotations button handler', () => {
      document.body.innerHTML = `
        <button id="show"></button>
        <button id="hide" style="display: none"></button>
        <button data-action="show-annotations">Show Annotations</button>
        <ruby class="anntransruby2" style="color: #C8DCF0">Test</ruby>
      `;

      initAnnotationToggles();

      const button = document.querySelector('[data-action="show-annotations"]')!;
      button.dispatchEvent(new Event('click'));

      expect($('#show').css('display')).toBe('none');
    });

    it('sets up close window button handler', () => {
      document.body.innerHTML = `
        <button data-action="close-window">Close</button>
      `;

      const closeSpy = vi.fn();
      Object.defineProperty(window, 'top', {
        value: { close: closeSpy },
        writable: true,
        configurable: true
      });

      initAnnotationToggles();

      const button = document.querySelector('[data-action="close-window"]')!;
      button.dispatchEvent(new Event('click'));

      expect(closeSpy).toHaveBeenCalled();
    });

    it('handles missing elements gracefully', () => {
      document.body.innerHTML = '';

      expect(() => initAnnotationToggles()).not.toThrow();
    });
  });
});

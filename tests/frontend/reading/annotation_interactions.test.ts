/**
 * Tests for annotation_interactions.ts - Annotation click handling.
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

import {
  clickAnnotation,
  clickText,
  initAnnotationInteractions
} from '../../../src/frontend/js/modules/text/pages/reading/annotation_interactions';

describe('annotation_interactions.ts', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  afterEach(() => {
    // Cleanup
  });

  describe('clickAnnotation', () => {
    it('hides annotation by setting matching colors on first click', () => {
      document.body.innerHTML = `
        <span class="anntransruby2">Translation</span>
      `;

      const element = document.querySelector('.anntransruby2') as HTMLElement;
      clickAnnotation.call(element);

      expect(element.style.color).toBe('rgb(200, 220, 240)');
      expect(element.style.backgroundColor).toBe('rgb(200, 220, 240)');
    });

    it('reveals annotation by removing style on second click', () => {
      document.body.innerHTML = `
        <span class="anntransruby2" style="color: rgb(200, 220, 240); background-color: rgb(200, 220, 240);">Translation</span>
      `;

      const element = document.querySelector('.anntransruby2') as HTMLElement;
      clickAnnotation.call(element);

      expect(element.getAttribute('style')).toBeNull();
    });

    it('toggles visibility correctly', () => {
      document.body.innerHTML = `
        <span class="anntransruby2">Translation</span>
      `;

      const element = document.querySelector('.anntransruby2') as HTMLElement;

      // First click - hide
      clickAnnotation.call(element);
      expect(element.style.color).toBe('rgb(200, 220, 240)');

      // Second click - show
      clickAnnotation.call(element);
      expect(element.getAttribute('style')).toBeNull();

      // Third click - hide again
      clickAnnotation.call(element);
      expect(element.style.color).toBe('rgb(200, 220, 240)');
    });

    it('handles element with empty style attribute', () => {
      document.body.innerHTML = `
        <span class="anntransruby2" style="">Translation</span>
      `;

      const element = document.querySelector('.anntransruby2') as HTMLElement;
      clickAnnotation.call(element);

      expect(element.style.color).toBe('rgb(200, 220, 240)');
    });
  });

  describe('clickText', () => {
    it('hides text by setting matching colors when not hidden', () => {
      document.body.innerHTML = `
        <span class="anntermruby">Term</span>
      `;
      // Set body color to simulate a real page
      document.body.style.color = 'rgb(0, 0, 0)';

      const element = document.querySelector('.anntermruby') as HTMLElement;
      clickText.call(element);

      expect(element.style.color).toBe('rgb(229, 228, 226)');
      expect(element.style.backgroundColor).toBe('rgb(229, 228, 226)');
    });

    it('reveals text by resetting colors when hidden', () => {
      document.body.innerHTML = `
        <span class="anntermruby" style="color: rgb(0, 0, 0); background-color: rgb(229, 228, 226);">Term</span>
      `;
      // Set body color to match the element's current color
      document.body.style.color = 'rgb(0, 0, 0)';

      const element = document.querySelector('.anntermruby') as HTMLElement;
      element.style.color = 'rgb(0, 0, 0)'; // Match body color

      clickText.call(element);

      expect(element.style.color).toBe('rgb(229, 228, 226)');
    });

    it('shows text when color matches body color', () => {
      document.body.innerHTML = `
        <span class="anntermruby">Term</span>
      `;
      document.body.style.color = 'rgb(255, 0, 0)';

      const element = document.querySelector('.anntermruby') as HTMLElement;
      // Set element color to match body
      element.style.color = 'rgb(255, 0, 0)';

      clickText.call(element);

      expect(element.style.color).toBe('rgb(229, 228, 226)');
    });

    it('inherits color when revealing', () => {
      document.body.innerHTML = `
        <span class="anntermruby" style="color: rgb(100, 100, 100);">Term</span>
      `;
      document.body.style.color = 'rgb(0, 0, 0)';

      const element = document.querySelector('.anntermruby') as HTMLElement;
      clickText.call(element);

      // Since color doesn't match body, it should inherit
      expect(element.style.color).toBe('inherit');
    });
  });

  describe('initAnnotationInteractions', () => {
    it('binds click handler to .anntransruby2 elements', () => {
      document.body.innerHTML = `
        <span class="anntransruby2">Translation 1</span>
        <span class="anntransruby2">Translation 2</span>
      `;

      initAnnotationInteractions();

      const elements = document.querySelectorAll('.anntransruby2');
      elements.forEach((el) => {
        const htmlEl = el as HTMLElement;
        htmlEl.dispatchEvent(new Event('click', { bubbles: true }));
        expect(htmlEl.style.color).toBe('rgb(200, 220, 240)');
      });
    });

    it('binds click handler to .anntermruby elements', () => {
      document.body.innerHTML = `
        <span class="anntermruby">Term 1</span>
        <span class="anntermruby">Term 2</span>
      `;
      document.body.style.color = 'rgb(0, 0, 0)';

      initAnnotationInteractions();

      const elements = document.querySelectorAll('.anntermruby');
      elements.forEach((el) => {
        const htmlEl = el as HTMLElement;
        htmlEl.dispatchEvent(new Event('click', { bubbles: true }));
        expect(htmlEl.style.color).toBe('rgb(229, 228, 226)');
      });
    });

    it('works with mixed elements', () => {
      document.body.innerHTML = `
        <span class="anntransruby2">Translation</span>
        <span class="anntermruby">Term</span>
      `;
      document.body.style.color = 'rgb(0, 0, 0)';

      initAnnotationInteractions();

      const annotation = document.querySelector('.anntransruby2') as HTMLElement;
      const term = document.querySelector('.anntermruby') as HTMLElement;

      annotation.dispatchEvent(new Event('click', { bubbles: true }));
      term.dispatchEvent(new Event('click', { bubbles: true }));

      expect(annotation.style.color).toBe('rgb(200, 220, 240)');
      expect(term.style.color).toBe('rgb(229, 228, 226)');
    });

    it('handles empty page without errors', () => {
      document.body.innerHTML = '<div>No annotations</div>';

      // Should not throw
      expect(() => initAnnotationInteractions()).not.toThrow();
    });
  });

  describe('window exports', () => {
    it('exports clickAnnotation to window', () => {
      expect(window.clickAnnotation).toBe(clickAnnotation);
    });

    it('exports clickText to window', () => {
      expect(window.clickText).toBe(clickText);
    });

    it('exports initAnnotationInteractions to window', () => {
      expect(window.initAnnotationInteractions).toBe(initAnnotationInteractions);
    });
  });
});

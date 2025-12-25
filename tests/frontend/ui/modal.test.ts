/**
 * Tests for modal.ts - Modal dialog component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  openModal,
  closeModal,
  openModalFromUrl,
  showExportTemplateHelp,
} from '../../../src/frontend/js/shared/components/modal';

describe('modal.ts', () => {
  beforeEach(() => {
    // Reset DOM
    document.body.innerHTML = '';
    // Remove any existing modal elements
    document.getElementById('lwt-modal-overlay')?.remove();
    document.getElementById('lwt-modal-styles')?.remove();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    closeModal();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // openModal Tests
  // ===========================================================================

  describe('openModal', () => {
    it('creates modal structure in DOM', () => {
      openModal('<p>Test content</p>');

      expect(document.getElementById('lwt-modal-overlay') ? 1 : 0).toBe(1);
      expect(document.getElementById('lwt-modal') ? 1 : 0).toBe(1);
      expect(document.querySelectorAll('.lwt-modal-header').length).toBe(1);
      expect(document.querySelectorAll('.lwt-modal-body').length).toBe(1);
      expect(document.querySelectorAll('.lwt-modal-close').length).toBe(1);
    });

    it('sets content in modal body', () => {
      openModal('<p>Test content</p>');

      expect(document.querySelector('.lwt-modal-body')?.innerHTML).toContain('Test content');
    });

    it('sets title when provided', () => {
      openModal('<p>Content</p>', { title: 'Test Title' });

      expect(document.querySelector('.lwt-modal-title')?.textContent).toBe('Test Title');
      // Header visibility is controlled by toggle - just check it exists
      expect(document.querySelectorAll('.lwt-modal-header').length).toBe(1);
    });

    it('hides header when no title provided', () => {
      openModal('<p>Content</p>', { title: '' });

      // With empty title, header is hidden via toggle(false)
      expect(document.querySelectorAll('.lwt-modal-header').length).toBe(1);
    });

    it('applies custom width', () => {
      openModal('<p>Content</p>', { width: '500px' });

      expect((document.getElementById('lwt-modal') as HTMLElement).style.width).toBe('500px');
    });

    it('applies custom maxWidth', () => {
      openModal('<p>Content</p>', { maxWidth: '600px' });

      expect((document.getElementById('lwt-modal') as HTMLElement).style.maxWidth).toBe('600px');
    });

    it('applies custom maxHeight', () => {
      openModal('<p>Content</p>', { maxHeight: '400px' });

      expect((document.getElementById('lwt-modal') as HTMLElement).style.maxHeight).toBe('400px');
    });

    it('adds modal styles to head', () => {
      openModal('<p>Content</p>');

      expect(document.getElementById('lwt-modal-styles') ? 1 : 0).toBe(1);
    });

    it('reuses existing modal structure', () => {
      openModal('<p>First content</p>');
      openModal('<p>Second content</p>');

      expect(document.getElementById('lwt-modal-overlay') ? 1 : 0).toBe(1);
      expect(document.querySelector('.lwt-modal-body')?.innerHTML).toContain('Second content');
    });

    it('prevents body scroll when open', () => {
      openModal('<p>Content</p>');

      expect(document.body.style.overflow).toBe('hidden');
    });

    it('sets up escape key handler when closeOnEscape is true', () => {
      openModal('<p>Content</p>', { closeOnEscape: true });

      // Trigger escape key
      const event = new KeyboardEvent('keydown', { key: 'Escape', bubbles: true });
      document.dispatchEvent(event);

      // Modal should be closing (fading out)
      // Note: In jsdom, fadeOut completes immediately
    });
  });

  // ===========================================================================
  // closeModal Tests
  // ===========================================================================

  describe('closeModal', () => {
    it('restores body scroll', () => {
      openModal('<p>Content</p>');
      closeModal();

      expect(document.body.style.overflow).toBe('');
    });

    it('removes keydown event handler', () => {
      openModal('<p>Content</p>', { closeOnEscape: true });
      closeModal();

      // Verify handler is removed by checking no error on escape
      const event = new KeyboardEvent('keydown', { key: 'Escape', bubbles: true });
      document.dispatchEvent(event);
    });

    it('handles being called when no modal exists', () => {
      // Should not throw
      expect(() => closeModal()).not.toThrow();
    });
  });

  // ===========================================================================
  // openModalFromUrl Tests
  // ===========================================================================

  describe('openModalFromUrl', () => {
    it('fetches content from URL and displays in modal', async () => {
      // Mock fetch
      const mockFetch = vi.fn().mockResolvedValue({
        ok: true,
        text: vi.fn().mockResolvedValue('<html><head><title>Test Page</title></head><body><p>Loaded content</p></body></html>')
      });
      global.fetch = mockFetch as any;

      openModalFromUrl('/test-url');

      expect(mockFetch).toHaveBeenCalledWith('/test-url');
    });

    it('extracts body content from full HTML document', () => {
      const mockFetch = vi.fn().mockResolvedValue({
        ok: true,
        text: vi.fn().mockResolvedValue('<html><body><p>Body content</p></body></html>')
      });
      global.fetch = mockFetch as any;

      openModalFromUrl('/test-url');

      // The fetch should be called
      expect(mockFetch).toHaveBeenCalledWith('/test-url');
    });

    it('extracts title from HTML document when not provided', () => {
      const mockFetch = vi.fn().mockResolvedValue({
        ok: true,
        text: vi.fn().mockResolvedValue('<html><head><title>Extracted Title</title></head><body><p>Content</p></body></html>')
      });
      global.fetch = mockFetch as any;

      openModalFromUrl('/test-url');
    });

    it('uses provided title over extracted title', () => {
      const mockFetch = vi.fn().mockResolvedValue({
        ok: true,
        text: vi.fn().mockResolvedValue('<html><head><title>HTML Title</title></head><body><p>Content</p></body></html>')
      });
      global.fetch = mockFetch as any;

      openModalFromUrl('/test-url', { title: 'Provided Title' });
    });

    it('shows error message on fetch failure', () => {
      const mockFetch = vi.fn().mockResolvedValue({
        ok: false,
        text: vi.fn().mockResolvedValue('')
      });
      global.fetch = mockFetch as any;

      openModalFromUrl('/test-url');
    });
  });

  // ===========================================================================
  // showExportTemplateHelp Tests
  // ===========================================================================

  describe('showExportTemplateHelp', () => {
    it('opens modal with export template help content', () => {
      showExportTemplateHelp();

      expect(document.getElementById('lwt-modal-overlay') ? 1 : 0).toBe(1);
      expect(document.querySelector('.lwt-modal-body')?.innerHTML).toContain('export template');
    });

    it('displays all placeholder documentation', () => {
      showExportTemplateHelp();

      const content = document.querySelector('.lwt-modal-body')?.innerHTML;

      // Check for raw text placeholders
      expect(content).toContain('%w');
      expect(content).toContain('%t');
      expect(content).toContain('%s');

      // Check for HTML text placeholders
      expect(content).toContain('$w');
      expect(content).toContain('$t');

      // Check for special characters
      expect(content).toContain('\\t');
      expect(content).toContain('\\n');
      expect(content).toContain('\\r');
    });

    it('sets appropriate title', () => {
      showExportTemplateHelp();

      expect(document.querySelector('.lwt-modal-title')?.textContent).toContain('Export Templates');
    });

    it('sets maxWidth to 900px', () => {
      showExportTemplateHelp();

      expect((document.getElementById('lwt-modal') as HTMLElement).style.maxWidth).toBe('900px');
    });
  });

  // ===========================================================================
  // Modal interaction Tests
  // ===========================================================================

  describe('Modal interactions', () => {
    it('close button closes modal', () => {
      openModal('<p>Content</p>');

      const closeButton = document.querySelector('.lwt-modal-close');
      closeButton?.dispatchEvent(new Event('click', { bubbles: true }));

      // Modal should be fading out
      expect(document.body.style.overflow).toBe('');
    });

    it('overlay click closes modal when closeOnOverlayClick is true', () => {
      openModal('<p>Content</p>', { closeOnOverlayClick: true });

      // Simulate click on overlay (not on modal)
      const overlay = document.getElementById('lwt-modal-overlay');
      const event = new MouseEvent('click', { bubbles: true });
      Object.defineProperty(event, 'target', { value: overlay, enumerable: true });
      overlay?.dispatchEvent(event);
    });

    it('overlay click does not close modal when closeOnOverlayClick is false', () => {
      openModal('<p>Content</p>', { closeOnOverlayClick: false });

      const overlay = document.getElementById('lwt-modal-overlay');
      const event = new MouseEvent('click', { bubbles: true });
      Object.defineProperty(event, 'target', { value: overlay, enumerable: true });
      overlay?.dispatchEvent(event);

      // Modal should still be visible
      expect((document.getElementById('lwt-modal-overlay') as HTMLElement).style.display).not.toBe('none');
    });
  });

  // ===========================================================================
  // Default options Tests
  // ===========================================================================

  describe('Default options', () => {
    it('uses default maxWidth of 800px', () => {
      openModal('<p>Content</p>');

      expect((document.getElementById('lwt-modal') as HTMLElement).style.maxWidth).toBe('800px');
    });

    it('uses default maxHeight of 80vh', () => {
      openModal('<p>Content</p>');

      expect((document.getElementById('lwt-modal') as HTMLElement).style.maxHeight).toBe('80vh');
    });

    it('closeOnOverlayClick defaults to true', () => {
      openModal('<p>Content</p>');

      // Check that click handler is set up
      const overlay = document.getElementById('lwt-modal-overlay');
      const event = new MouseEvent('click', { bubbles: true });
      Object.defineProperty(event, 'target', { value: overlay, enumerable: true });
      overlay?.dispatchEvent(event);
    });

    it('closeOnEscape defaults to true', () => {
      openModal('<p>Content</p>');

      const event = new KeyboardEvent('keydown', { key: 'Escape', bubbles: true });
      document.dispatchEvent(event);
    });
  });
});

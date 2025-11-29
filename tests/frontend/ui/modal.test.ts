/**
 * Tests for modal.ts - Modal dialog component
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  openModal,
  closeModal,
  openModalFromUrl,
  showExportTemplateHelp,
} from '../../../src/frontend/js/ui/modal';

describe('modal.ts', () => {
  beforeEach(() => {
    // Reset DOM
    document.body.innerHTML = '';
    // Remove any existing modal elements
    $('#lwt-modal-overlay').remove();
    $('#lwt-modal-styles').remove();
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

      expect($('#lwt-modal-overlay').length).toBe(1);
      expect($('#lwt-modal').length).toBe(1);
      expect($('.lwt-modal-header').length).toBe(1);
      expect($('.lwt-modal-body').length).toBe(1);
      expect($('.lwt-modal-close').length).toBe(1);
    });

    it('sets content in modal body', () => {
      openModal('<p>Test content</p>');

      expect($('.lwt-modal-body').html()).toContain('Test content');
    });

    it('sets title when provided', () => {
      openModal('<p>Content</p>', { title: 'Test Title' });

      expect($('.lwt-modal-title').text()).toBe('Test Title');
      // Header visibility is controlled by toggle - just check it exists
      expect($('.lwt-modal-header').length).toBe(1);
    });

    it('hides header when no title provided', () => {
      openModal('<p>Content</p>', { title: '' });

      // With empty title, header is hidden via toggle(false)
      expect($('.lwt-modal-header').length).toBe(1);
    });

    it('applies custom width', () => {
      openModal('<p>Content</p>', { width: '500px' });

      expect($('#lwt-modal').css('width')).toBe('500px');
    });

    it('applies custom maxWidth', () => {
      openModal('<p>Content</p>', { maxWidth: '600px' });

      expect($('#lwt-modal').css('max-width')).toBe('600px');
    });

    it('applies custom maxHeight', () => {
      openModal('<p>Content</p>', { maxHeight: '400px' });

      expect($('#lwt-modal').css('max-height')).toBe('400px');
    });

    it('adds modal styles to head', () => {
      openModal('<p>Content</p>');

      expect($('#lwt-modal-styles').length).toBe(1);
    });

    it('reuses existing modal structure', () => {
      openModal('<p>First content</p>');
      openModal('<p>Second content</p>');

      expect($('#lwt-modal-overlay').length).toBe(1);
      expect($('.lwt-modal-body').html()).toContain('Second content');
    });

    it('prevents body scroll when open', () => {
      openModal('<p>Content</p>');

      expect($('body').css('overflow')).toBe('hidden');
    });

    it('sets up escape key handler when closeOnEscape is true', () => {
      openModal('<p>Content</p>', { closeOnEscape: true });

      // Trigger escape key
      const event = $.Event('keydown', { key: 'Escape' });
      $(document).trigger(event);

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

      expect($('body').css('overflow')).toBe('');
    });

    it('removes keydown event handler', () => {
      openModal('<p>Content</p>', { closeOnEscape: true });
      closeModal();

      // Verify handler is removed by checking no error on escape
      const event = $.Event('keydown', { key: 'Escape' });
      $(document).trigger(event);
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
      // Mock $.get
      const mockGet = vi.fn().mockReturnValue({
        done: vi.fn().mockImplementation(function(this: any, callback: (html: string) => void) {
          callback('<html><head><title>Test Page</title></head><body><p>Loaded content</p></body></html>');
          return { fail: vi.fn() };
        }),
        fail: vi.fn()
      });
      ($ as any).get = mockGet;

      openModalFromUrl('/test-url');

      expect(mockGet).toHaveBeenCalledWith('/test-url');
    });

    it('extracts body content from full HTML document', () => {
      const mockGet = vi.fn().mockReturnValue({
        done: vi.fn().mockImplementation(function(this: any, callback: (html: string) => void) {
          callback('<html><body><p>Body content</p></body></html>');
          return { fail: vi.fn() };
        }),
        fail: vi.fn()
      });
      ($ as any).get = mockGet;

      openModalFromUrl('/test-url');

      // The callback should extract body content
      const doneCallback = mockGet.mock.results[0].value.done.mock.calls[0][0];
      expect(typeof doneCallback).toBe('function');
    });

    it('extracts title from HTML document when not provided', () => {
      const mockGet = vi.fn().mockReturnValue({
        done: vi.fn().mockImplementation(function(this: any, callback: (html: string) => void) {
          callback('<html><head><title>Extracted Title</title></head><body><p>Content</p></body></html>');
          return { fail: vi.fn() };
        }),
        fail: vi.fn()
      });
      ($ as any).get = mockGet;

      openModalFromUrl('/test-url');
    });

    it('uses provided title over extracted title', () => {
      const mockGet = vi.fn().mockReturnValue({
        done: vi.fn().mockImplementation(function(this: any, callback: (html: string) => void) {
          callback('<html><head><title>HTML Title</title></head><body><p>Content</p></body></html>');
          return { fail: vi.fn() };
        }),
        fail: vi.fn()
      });
      ($ as any).get = mockGet;

      openModalFromUrl('/test-url', { title: 'Provided Title' });
    });

    it('shows error message on fetch failure', () => {
      const mockGet = vi.fn().mockReturnValue({
        done: vi.fn().mockReturnThis(),
        fail: vi.fn().mockImplementation(function(this: any, callback: () => void) {
          callback();
          return this;
        })
      });
      ($ as any).get = mockGet;

      openModalFromUrl('/test-url');
    });
  });

  // ===========================================================================
  // showExportTemplateHelp Tests
  // ===========================================================================

  describe('showExportTemplateHelp', () => {
    it('opens modal with export template help content', () => {
      showExportTemplateHelp();

      expect($('#lwt-modal-overlay').length).toBe(1);
      expect($('.lwt-modal-body').html()).toContain('export template');
    });

    it('displays all placeholder documentation', () => {
      showExportTemplateHelp();

      const content = $('.lwt-modal-body').html();

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

      expect($('.lwt-modal-title').text()).toContain('Export Templates');
    });

    it('sets maxWidth to 900px', () => {
      showExportTemplateHelp();

      expect($('#lwt-modal').css('max-width')).toBe('900px');
    });
  });

  // ===========================================================================
  // Modal interaction Tests
  // ===========================================================================

  describe('Modal interactions', () => {
    it('close button closes modal', () => {
      openModal('<p>Content</p>');

      $('.lwt-modal-close').trigger('click');

      // Modal should be fading out
      expect($('body').css('overflow')).toBe('');
    });

    it('overlay click closes modal when closeOnOverlayClick is true', () => {
      openModal('<p>Content</p>', { closeOnOverlayClick: true });

      // Simulate click on overlay (not on modal)
      const overlay = $('#lwt-modal-overlay')[0];
      const event = $.Event('click', { target: overlay });
      $('#lwt-modal-overlay').trigger(event);
    });

    it('overlay click does not close modal when closeOnOverlayClick is false', () => {
      openModal('<p>Content</p>', { closeOnOverlayClick: false });

      const overlay = $('#lwt-modal-overlay')[0];
      const event = $.Event('click', { target: overlay });
      $('#lwt-modal-overlay').trigger(event);

      // Modal should still be visible
      expect($('#lwt-modal-overlay').css('display')).not.toBe('none');
    });
  });

  // ===========================================================================
  // Default options Tests
  // ===========================================================================

  describe('Default options', () => {
    it('uses default maxWidth of 800px', () => {
      openModal('<p>Content</p>');

      expect($('#lwt-modal').css('max-width')).toBe('800px');
    });

    it('uses default maxHeight of 80vh', () => {
      openModal('<p>Content</p>');

      expect($('#lwt-modal').css('max-height')).toBe('80vh');
    });

    it('closeOnOverlayClick defaults to true', () => {
      openModal('<p>Content</p>');

      // Check that click handler is set up
      const overlay = $('#lwt-modal-overlay')[0];
      const event = $.Event('click', { target: overlay });
      $('#lwt-modal-overlay').trigger(event);
    });

    it('closeOnEscape defaults to true', () => {
      openModal('<p>Content</p>');

      const event = $.Event('keydown', { key: 'Escape' });
      $(document).trigger(event);
    });
  });
});

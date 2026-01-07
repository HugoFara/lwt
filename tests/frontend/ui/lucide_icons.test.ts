/**
 * Tests for ui/lucide_icons.ts - Lucide icons integration
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock lucide before importing the module
// The actual module imports specific icons and passes them to createIcons
vi.mock('lucide', () => ({
  createIcons: vi.fn(),
  // Mock the specific icons that are imported
  Check: ['svg', {}, []],
  X: ['svg', {}, []],
  Plus: ['svg', {}, []],
  ChevronDown: ['svg', {}, []],
  Loader2: ['svg', {}, []],
  AlertCircle: ['svg', {}, []],
  Archive: ['svg', {}, []],
  ArchiveX: ['svg', {}, []],
  ArrowLeft: ['svg', {}, []],
  ArrowRight: ['svg', {}, []],
  Asterisk: ['svg', {}, []],
  BarChart2: ['svg', {}, []],
  BookMarked: ['svg', {}, []],
  BookOpen: ['svg', {}, []],
  BookOpenCheck: ['svg', {}, []],
  BookOpenText: ['svg', {}, []],
  Brush: ['svg', {}, []],
  Calculator: ['svg', {}, []],
  ChevronLeft: ['svg', {}, []],
  ChevronRight: ['svg', {}, []],
  ChevronsLeft: ['svg', {}, []],
  ChevronsRight: ['svg', {}, []],
  Circle: ['svg', {}, []],
  CircleAlert: ['svg', {}, []],
  CircleCheck: ['svg', {}, []],
  CircleChevronLeft: ['svg', {}, []],
  CircleChevronRight: ['svg', {}, []],
  CircleDot: ['svg', {}, []],
  CircleHelp: ['svg', {}, []],
  CircleMinus: ['svg', {}, []],
  CirclePlus: ['svg', {}, []],
  CircleX: ['svg', {}, []],
  Clock: ['svg', {}, []],
  Database: ['svg', {}, []],
  Download: ['svg', {}, []],
  Eraser: ['svg', {}, []],
  ExternalLink: ['svg', {}, []],
  Eye: ['svg', {}, []],
  FastForward: ['svg', {}, []],
  FileDown: ['svg', {}, []],
  FilePen: ['svg', {}, []],
  FilePenLine: ['svg', {}, []],
  FileStack: ['svg', {}, []],
  FileText: ['svg', {}, []],
  Filter: ['svg', {}, []],
  FilterX: ['svg', {}, []],
  Frown: ['svg', {}, []],
  HelpCircle: ['svg', {}, []],
  Image: ['svg', {}, []],
  Info: ['svg', {}, []],
  Languages: ['svg', {}, []],
  Layers: ['svg', {}, []],
  Lightbulb: ['svg', {}, []],
  LightbulbOff: ['svg', {}, []],
  Link: ['svg', {}, []],
  List: ['svg', {}, []],
  Loader: ['svg', {}, []],
  Lock: ['svg', {}, []],
  LogIn: ['svg', {}, []],
  Mail: ['svg', {}, []],
  Minus: ['svg', {}, []],
  Newspaper: ['svg', {}, []],
  NotepadText: ['svg', {}, []],
  NotepadTextDashed: ['svg', {}, []],
  Notebook: ['svg', {}, []],
  NotebookPen: ['svg', {}, []],
  Palette: ['svg', {}, []],
  Pencil: ['svg', {}, []],
  Printer: ['svg', {}, []],
  RefreshCw: ['svg', {}, []],
  Repeat: ['svg', {}, []],
  Rewind: ['svg', {}, []],
  Rocket: ['svg', {}, []],
  Rss: ['svg', {}, []],
  Server: ['svg', {}, []],
  Settings: ['svg', {}, []],
  Sliders: ['svg', {}, []],
  Smile: ['svg', {}, []],
  Square: ['svg', {}, []],
  SquareMinus: ['svg', {}, []],
  SquarePen: ['svg', {}, []],
  SquarePlus: ['svg', {}, []],
  Star: ['svg', {}, []],
  StickyNote: ['svg', {}, []],
  Sun: ['svg', {}, []],
  Tag: ['svg', {}, []],
  Tags: ['svg', {}, []],
  ThumbsUp: ['svg', {}, []],
  Upload: ['svg', {}, []],
  User: ['svg', {}, []],
  UserPlus: ['svg', {}, []],
  Volume2: ['svg', {}, []],
  VolumeX: ['svg', {}, []],
  Wand2: ['svg', {}, []],
  WrapText: ['svg', {}, []],
  XCircle: ['svg', {}, []],
  Zap: ['svg', {}, []]
}));

import {
  initIcons,
  initIconsIn,
  createIcon,
  replaceWithLucide
} from '../../../src/frontend/js/shared/icons/lucide_icons';
import { createIcons } from 'lucide';

describe('ui/lucide_icons.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // initIcons Tests
  // ===========================================================================

  describe('initIcons', () => {
    it('calls createIcons with icons object', () => {
      initIcons();

      expect(createIcons).toHaveBeenCalledWith(
        expect.objectContaining({ icons: expect.any(Object) })
      );
    });

    it('can be called multiple times', () => {
      initIcons();
      initIcons();
      initIcons();

      expect(createIcons).toHaveBeenCalledTimes(3);
    });
  });

  // ===========================================================================
  // initIconsIn Tests
  // ===========================================================================

  describe('initIconsIn', () => {
    it('calls createIcons when container has icons', () => {
      document.body.innerHTML = `
        <div id="container">
          <i data-lucide="check"></i>
        </div>
      `;
      const container = document.getElementById('container')!;

      initIconsIn(container);

      expect(createIcons).toHaveBeenCalledWith(
        expect.objectContaining({ icons: expect.any(Object) })
      );
    });

    it('does not call createIcons when container has no icons', () => {
      document.body.innerHTML = `
        <div id="container">
          <span>No icons here</span>
        </div>
      `;
      const container = document.getElementById('container')!;

      initIconsIn(container);

      expect(createIcons).not.toHaveBeenCalled();
    });

    it('finds nested icons in container', () => {
      document.body.innerHTML = `
        <div id="container">
          <div class="nested">
            <span>
              <i data-lucide="check"></i>
            </span>
          </div>
        </div>
      `;
      const container = document.getElementById('container')!;

      initIconsIn(container);

      expect(createIcons).toHaveBeenCalled();
    });

    it('finds multiple icons in container', () => {
      document.body.innerHTML = `
        <div id="container">
          <i data-lucide="check"></i>
          <i data-lucide="x"></i>
          <i data-lucide="plus"></i>
        </div>
      `;
      const container = document.getElementById('container')!;

      initIconsIn(container);

      expect(createIcons).toHaveBeenCalledTimes(1);
    });

    it('handles empty container', () => {
      document.body.innerHTML = '<div id="container"></div>';
      const container = document.getElementById('container')!;

      initIconsIn(container);

      expect(createIcons).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // createIcon Tests
  // ===========================================================================

  describe('createIcon', () => {
    it('creates temporary element with data-lucide attribute', () => {
      createIcon('check');

      expect(createIcons).toHaveBeenCalled();
    });

    it('uses default size of 16', () => {
      createIcon('check');

      const call = vi.mocked(createIcons).mock.calls[0][0];
      expect(call.attrs?.width).toBe(16);
      expect(call.attrs?.height).toBe(16);
    });

    it('uses custom size when provided', () => {
      createIcon('check', { size: 24 });

      const call = vi.mocked(createIcons).mock.calls[0][0];
      expect(call.attrs?.width).toBe(24);
      expect(call.attrs?.height).toBe(24);
    });

    it('uses default stroke width of 2', () => {
      createIcon('check');

      const call = vi.mocked(createIcons).mock.calls[0][0];
      expect(call.attrs?.['stroke-width']).toBe(2);
    });

    it('uses custom stroke width when provided', () => {
      createIcon('check', { strokeWidth: 3 });

      const call = vi.mocked(createIcons).mock.calls[0][0];
      expect(call.attrs?.['stroke-width']).toBe(3);
    });

    it('uses currentColor as default stroke', () => {
      createIcon('check');

      const call = vi.mocked(createIcons).mock.calls[0][0];
      expect(call.attrs?.stroke).toBe('currentColor');
    });

    it('uses custom color when provided', () => {
      createIcon('check', { color: '#ff0000' });

      const call = vi.mocked(createIcons).mock.calls[0][0];
      expect(call.attrs?.stroke).toBe('#ff0000');
    });

    it('returns null when SVG is not created', () => {
      const result = createIcon('check');

      // Since createIcons is mocked and doesn't actually create SVG
      expect(result).toBeNull();
    });

    it('cleans up temporary element from DOM', () => {
      const initialChildCount = document.body.children.length;

      createIcon('check');

      expect(document.body.children.length).toBe(initialChildCount);
    });

    it('handles multiple options at once', () => {
      createIcon('check', {
        size: 32,
        strokeWidth: 1.5,
        color: 'blue',
        class: 'custom-icon'
      });

      const call = vi.mocked(createIcons).mock.calls[0][0];
      expect(call.attrs?.width).toBe(32);
      expect(call.attrs?.height).toBe(32);
      expect(call.attrs?.['stroke-width']).toBe(1.5);
      expect(call.attrs?.stroke).toBe('blue');
    });
  });

  // ===========================================================================
  // replaceWithLucide Tests
  // ===========================================================================

  describe('replaceWithLucide', () => {
    it('replaces img element with icon placeholder', () => {
      document.body.innerHTML = `
        <img id="old-icon" src="check.png" />
      `;
      const img = document.getElementById('old-icon') as HTMLImageElement;

      replaceWithLucide(img, 'check');

      expect(document.querySelector('img')).toBeNull();
      expect(document.querySelector('[data-lucide="check"]')).not.toBeNull();
    });

    it('preserves title attribute', () => {
      document.body.innerHTML = `
        <img id="old-icon" src="check.png" title="Check Mark" />
      `;
      const img = document.getElementById('old-icon') as HTMLImageElement;

      replaceWithLucide(img, 'check');

      const icon = document.querySelector('[data-lucide="check"]');
      expect(icon?.getAttribute('title')).toBe('Check Mark');
    });

    it('sets aria-label from alt text', () => {
      document.body.innerHTML = `
        <img id="old-icon" src="check.png" alt="Checkmark icon" />
      `;
      const img = document.getElementById('old-icon') as HTMLImageElement;

      replaceWithLucide(img, 'check');

      const icon = document.querySelector('[data-lucide="check"]');
      expect(icon?.getAttribute('aria-label')).toBe('Checkmark icon');
    });

    it('falls back to title for aria-label', () => {
      document.body.innerHTML = `
        <img id="old-icon" src="check.png" title="Check" />
      `;
      const img = document.getElementById('old-icon') as HTMLImageElement;

      replaceWithLucide(img, 'check');

      const icon = document.querySelector('[data-lucide="check"]');
      expect(icon?.getAttribute('aria-label')).toBe('Check');
    });

    it('preserves CSS class', () => {
      document.body.innerHTML = `
        <img id="old-icon" src="check.png" class="small-icon action-icon" />
      `;
      const img = document.getElementById('old-icon') as HTMLImageElement;

      replaceWithLucide(img, 'check');

      const icon = document.querySelector('[data-lucide="check"]');
      expect(icon?.className).toContain('small-icon');
      expect(icon?.className).toContain('action-icon');
      expect(icon?.className).toContain('icon');
    });

    it('copies data attributes', () => {
      document.body.innerHTML = `
        <img id="old-icon" src="check.png" data-action="toggle" data-id="123" />
      `;
      const img = document.getElementById('old-icon') as HTMLImageElement;

      replaceWithLucide(img, 'check');

      const icon = document.querySelector('[data-lucide="check"]');
      expect(icon?.getAttribute('data-action')).toBe('toggle');
      expect(icon?.getAttribute('data-id')).toBe('123');
    });

    it('does not copy data-lucide attribute from old element', () => {
      document.body.innerHTML = `
        <img id="old-icon" src="check.png" data-lucide="old-value" />
      `;
      const img = document.getElementById('old-icon') as HTMLImageElement;

      replaceWithLucide(img, 'new-icon');

      const icon = document.querySelector('[data-lucide]');
      expect(icon?.getAttribute('data-lucide')).toBe('new-icon');
    });

    it('calls createIcons after replacement', () => {
      document.body.innerHTML = `
        <img id="old-icon" src="check.png" />
      `;
      const img = document.getElementById('old-icon') as HTMLImageElement;

      replaceWithLucide(img, 'check');

      expect(createIcons).toHaveBeenCalled();
    });

    it('sets default size of 16px', () => {
      document.body.innerHTML = `
        <img id="old-icon" src="check.png" />
      `;
      const img = document.getElementById('old-icon') as HTMLImageElement;

      replaceWithLucide(img, 'check');

      const icon = document.querySelector('[data-lucide="check"]') as HTMLElement;
      expect(icon.style.width).toBe('16px');
      expect(icon.style.height).toBe('16px');
    });

    it('handles missing title and alt', () => {
      document.body.innerHTML = `
        <img id="old-icon" src="check.png" />
      `;
      const img = document.getElementById('old-icon') as HTMLImageElement;

      replaceWithLucide(img, 'check');

      const icon = document.querySelector('[data-lucide="check"]');
      // When both are missing, neither title nor aria-label should be set
      expect(icon?.hasAttribute('title')).toBe(false);
      expect(icon?.hasAttribute('aria-label')).toBe(false);
    });
  });

  // ===========================================================================
  // Window Global Tests
  // ===========================================================================

  describe('Window Global', () => {
    it('exposes LWT_Icons on window', () => {
      expect(window.LWT_Icons).toBeDefined();
    });

    it('exposes init function', () => {
      expect(window.LWT_Icons.init).toBe(initIcons);
    });

    it('exposes initIn function', () => {
      expect(window.LWT_Icons.initIn).toBe(initIconsIn);
    });

    it('exposes create function', () => {
      expect(window.LWT_Icons.create).toBe(createIcon);
    });

    it('exposes replace function', () => {
      expect(window.LWT_Icons.replace).toBe(replaceWithLucide);
    });
  });

  // ===========================================================================
  // Event Listeners Tests
  // ===========================================================================

  describe('Event Listeners', () => {
    it('responds to lwt:contentLoaded event', () => {
      vi.clearAllMocks();

      document.dispatchEvent(new Event('lwt:contentLoaded'));

      expect(createIcons).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles icon names with special characters', () => {
      createIcon('arrow-up-right');

      const call = vi.mocked(createIcons).mock.calls[0][0];
      expect(call).toBeDefined();
    });

    it('handles very small size', () => {
      createIcon('check', { size: 1 });

      const call = vi.mocked(createIcons).mock.calls[0][0];
      expect(call.attrs?.width).toBe(1);
    });

    it('handles very large size', () => {
      createIcon('check', { size: 1000 });

      const call = vi.mocked(createIcons).mock.calls[0][0];
      expect(call.attrs?.width).toBe(1000);
    });

    it('handles decimal stroke width', () => {
      createIcon('check', { strokeWidth: 0.5 });

      const call = vi.mocked(createIcons).mock.calls[0][0];
      expect(call.attrs?.['stroke-width']).toBe(0.5);
    });

    it('handles CSS color values', () => {
      createIcon('check', { color: 'rgba(255, 0, 0, 0.5)' });

      const call = vi.mocked(createIcons).mock.calls[0][0];
      expect(call.attrs?.stroke).toBe('rgba(255, 0, 0, 0.5)');
    });

    it('handles empty class option', () => {
      createIcon('check', { class: '' });

      // Should not throw
      expect(createIcons).toHaveBeenCalled();
    });

    it('initIconsIn handles detached element', () => {
      const detached = document.createElement('div');
      detached.innerHTML = '<i data-lucide="check"></i>';

      initIconsIn(detached);

      expect(createIcons).toHaveBeenCalled();
    });

    it('replaceWithLucide handles img with many data attributes', () => {
      document.body.innerHTML = `
        <img id="old-icon" src="check.png"
             data-a="1" data-b="2" data-c="3" data-d="4" data-e="5" />
      `;
      const img = document.getElementById('old-icon') as HTMLImageElement;

      replaceWithLucide(img, 'check');

      const icon = document.querySelector('[data-lucide="check"]');
      expect(icon?.getAttribute('data-a')).toBe('1');
      expect(icon?.getAttribute('data-e')).toBe('5');
    });

    it('replaceWithLucide handles img with no class', () => {
      document.body.innerHTML = `
        <img id="old-icon" src="check.png" />
      `;
      const img = document.getElementById('old-icon') as HTMLImageElement;

      replaceWithLucide(img, 'check');

      const icon = document.querySelector('[data-lucide="check"]');
      expect(icon?.className).toContain('icon');
    });
  });
});

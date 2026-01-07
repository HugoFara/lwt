/**
 * Lucide Icons integration for LWT.
 *
 * This module initializes Lucide SVG icons throughout the application,
 * replacing legacy PNG icons with modern, scalable vector icons.
 *
 * Only specific icons used in the application are imported to minimize
 * bundle size (tree-shaking).
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { createIcons, type IconNode } from 'lucide';

// Import only the icons we actually use
import {
  AlertCircle,
  Archive,
  ArchiveX,
  ArrowLeft,
  ArrowRight,
  Asterisk,
  BarChart2,
  BookMarked,
  BookOpen,
  BookOpenCheck,
  BookOpenText,
  Brush,
  Calculator,
  Check,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
  Circle,
  CircleAlert,
  CircleCheck,
  CircleChevronLeft,
  CircleChevronRight,
  CircleDot,
  CircleHelp,
  CircleMinus,
  CirclePlus,
  CircleX,
  Clock,
  Database,
  Download,
  Eraser,
  ExternalLink,
  Eye,
  FastForward,
  FileDown,
  FilePen,
  FilePenLine,
  FileStack,
  FileText,
  Filter,
  FilterX,
  Frown,
  HelpCircle,
  Image,
  Info,
  Languages,
  Layers,
  Lightbulb,
  LightbulbOff,
  Link,
  List,
  Loader,
  Loader2,
  Lock,
  LogIn,
  Mail,
  Minus,
  Newspaper,
  NotepadText,
  NotepadTextDashed,
  Notebook,
  NotebookPen,
  Palette,
  Pencil,
  Plus,
  Printer,
  RefreshCw,
  Repeat,
  Rewind,
  Rocket,
  Rss,
  Server,
  Settings,
  Sliders,
  Smile,
  Square,
  SquareMinus,
  SquarePen,
  SquarePlus,
  Star,
  StickyNote,
  Sun,
  Tag,
  Tags,
  ThumbsUp,
  Upload,
  User,
  UserPlus,
  Volume2,
  VolumeX,
  Wand2,
  WrapText,
  X,
  XCircle,
  Zap
} from 'lucide';

/**
 * Map of icon names to icon definitions.
 * Keys are kebab-case names used in data-lucide attributes.
 */
const usedIcons: Record<string, IconNode> = {
  'alert-circle': AlertCircle,
  archive: Archive,
  'archive-x': ArchiveX,
  'arrow-left': ArrowLeft,
  'arrow-right': ArrowRight,
  asterisk: Asterisk,
  'bar-chart-2': BarChart2,
  'book-marked': BookMarked,
  'book-open': BookOpen,
  'book-open-check': BookOpenCheck,
  'book-open-text': BookOpenText,
  brush: Brush,
  calculator: Calculator,
  check: Check,
  'chevron-down': ChevronDown,
  'chevron-left': ChevronLeft,
  'chevron-right': ChevronRight,
  'chevrons-left': ChevronsLeft,
  'chevrons-right': ChevronsRight,
  circle: Circle,
  'circle-alert': CircleAlert,
  'circle-check': CircleCheck,
  'circle-chevron-left': CircleChevronLeft,
  'circle-chevron-right': CircleChevronRight,
  'circle-dot': CircleDot,
  'circle-help': CircleHelp,
  'circle-minus': CircleMinus,
  'circle-plus': CirclePlus,
  'circle-x': CircleX,
  clock: Clock,
  database: Database,
  download: Download,
  eraser: Eraser,
  'external-link': ExternalLink,
  eye: Eye,
  'fast-forward': FastForward,
  'file-down': FileDown,
  'file-pen': FilePen,
  'file-pen-line': FilePenLine,
  'file-stack': FileStack,
  'file-text': FileText,
  filter: Filter,
  'filter-x': FilterX,
  frown: Frown,
  'help-circle': HelpCircle,
  image: Image,
  info: Info,
  languages: Languages,
  layers: Layers,
  lightbulb: Lightbulb,
  'lightbulb-off': LightbulbOff,
  link: Link,
  list: List,
  loader: Loader,
  'loader-2': Loader2,
  lock: Lock,
  'log-in': LogIn,
  mail: Mail,
  minus: Minus,
  newspaper: Newspaper,
  'notepad-text': NotepadText,
  'notepad-text-dashed': NotepadTextDashed,
  notebook: Notebook,
  'notebook-pen': NotebookPen,
  palette: Palette,
  pencil: Pencil,
  plus: Plus,
  printer: Printer,
  'refresh-cw': RefreshCw,
  repeat: Repeat,
  rewind: Rewind,
  rocket: Rocket,
  rss: Rss,
  server: Server,
  settings: Settings,
  sliders: Sliders,
  smile: Smile,
  square: Square,
  'square-minus': SquareMinus,
  'square-pen': SquarePen,
  'square-plus': SquarePlus,
  star: Star,
  'sticky-note': StickyNote,
  sun: Sun,
  tag: Tag,
  tags: Tags,
  'thumbs-up': ThumbsUp,
  upload: Upload,
  user: User,
  'user-plus': UserPlus,
  'volume-2': Volume2,
  'volume-x': VolumeX,
  'wand-2': Wand2,
  'wrap-text': WrapText,
  x: X,
  'x-circle': XCircle,
  zap: Zap
};

/**
 * Initialize all Lucide icons in the document.
 *
 * This finds all elements with `data-lucide` attributes and replaces
 * them with the corresponding SVG icons.
 */
export function initIcons(): void {
  createIcons({ icons: usedIcons });
}

/**
 * Initialize icons within a specific container element.
 *
 * Useful for dynamically loaded content where icons need to be
 * initialized after the content is added to the DOM.
 *
 * @param container - The container element to search for icons
 */
export function initIconsIn(container: Element): void {
  // Find all elements with data-lucide attribute within container
  const iconElements = container.querySelectorAll('[data-lucide]');

  if (iconElements.length > 0) {
    // Re-run createIcons to process new elements
    createIcons({ icons: usedIcons });
  }
}

/**
 * Create a single icon element programmatically.
 *
 * @param name - The Lucide icon name (e.g., 'check', 'x', 'plus')
 * @param options - Optional configuration for the icon
 * @returns The created SVG element, or null if icon not found
 */
export function createIcon(
  name: string,
  options: {
    size?: number;
    class?: string;
    strokeWidth?: number;
    color?: string;
  } = {}
): SVGElement | null {
  const size = options.size ?? 16;
  const strokeWidth = options.strokeWidth ?? 2;
  const className = options.class ?? 'icon';
  const color = options.color ?? 'currentColor';

  // Create a temporary container
  const temp = document.createElement('i');
  temp.setAttribute('data-lucide', name);
  temp.style.width = `${size}px`;
  temp.style.height = `${size}px`;
  temp.className = className;

  // Add to DOM temporarily (required for createIcons to work)
  temp.style.display = 'none';
  document.body.appendChild(temp);

  // Process the icon
  createIcons({
    icons: usedIcons,
    attrs: {
      width: size,
      height: size,
      'stroke-width': strokeWidth,
      stroke: color
    }
  });

  // Get the created SVG
  const svg = temp.querySelector('svg');

  // Clean up
  document.body.removeChild(temp);

  if (svg) {
    svg.classList.add(...className.split(' '));
    return svg;
  }

  return null;
}

/**
 * Replace a legacy PNG icon with a Lucide icon.
 *
 * This is useful for gradual migration - it can replace an existing
 * <img> element with the new Lucide icon.
 *
 * @param imgElement - The img element to replace
 * @param lucideName - The Lucide icon name to use
 */
export function replaceWithLucide(
  imgElement: HTMLImageElement,
  lucideName: string
): void {
  const title = imgElement.getAttribute('title') ?? '';
  const alt = imgElement.getAttribute('alt') ?? title;
  const className = imgElement.className;

  // Create the icon placeholder
  const iconEl = document.createElement('i');
  iconEl.setAttribute('data-lucide', lucideName);
  iconEl.className = `icon ${className}`;
  iconEl.style.width = '16px';
  iconEl.style.height = '16px';

  if (title) {
    iconEl.setAttribute('title', title);
  }
  if (alt) {
    iconEl.setAttribute('aria-label', alt);
  }

  // Copy data attributes
  for (const attr of Array.from(imgElement.attributes)) {
    if (attr.name.startsWith('data-') && attr.name !== 'data-lucide') {
      iconEl.setAttribute(attr.name, attr.value);
    }
  }

  // Replace the element
  imgElement.replaceWith(iconEl);

  // Initialize the new icon
  createIcons({ icons: usedIcons });
}

// Initialize icons when DOM is ready
function init(): void {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initIcons);
  } else {
    initIcons();
  }
}

// Re-initialize after Alpine.js has finished initial render
// Alpine dispatches 'alpine:initialized' after starting
document.addEventListener('alpine:initialized', () => {
  // Use requestAnimationFrame to ensure Alpine has rendered templates
  requestAnimationFrame(() => {
    initIcons();
  });
});

// Also re-initialize after AJAX content loads
// Listen for custom event that can be triggered after dynamic content loads
document.addEventListener('lwt:contentLoaded', () => {
  initIcons();
});

// Expose to window for use in inline scripts
declare global {
  interface Window {
    LWT_Icons: {
      init: typeof initIcons;
      initIn: typeof initIconsIn;
      create: typeof createIcon;
      replace: typeof replaceWithLucide;
    };
  }
}

window.LWT_Icons = {
  init: initIcons,
  initIn: initIconsIn,
  create: createIcon,
  replace: replaceWithLucide
};

// Auto-initialize
init();

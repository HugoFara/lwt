/**
 * Modal dialog component for LWT.
 *
 * Provides a reusable modal dialog that can display HTML content
 * as a modern replacement for popup windows (oewin/owin).
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import $ from 'jquery';

interface ModalOptions {
  title?: string;
  width?: string;
  maxWidth?: string;
  maxHeight?: string;
  closeOnOverlayClick?: boolean;
  closeOnEscape?: boolean;
}

const defaultOptions: ModalOptions = {
  title: '',
  width: 'auto',
  maxWidth: '800px',
  maxHeight: '80vh',
  closeOnOverlayClick: true,
  closeOnEscape: true
};

let modalInstance: JQuery | null = null;
let overlayInstance: JQuery | null = null;

/**
 * Create the modal HTML structure if it doesn't exist.
 */
function ensureModalExists(): void {
  if ($('#lwt-modal-overlay').length === 0) {
    const modalHtml = `
      <div id="lwt-modal-overlay" class="lwt-modal-overlay" style="display:none;">
        <div id="lwt-modal" class="lwt-modal">
          <div class="lwt-modal-header">
            <h2 class="lwt-modal-title"></h2>
            <button type="button" class="lwt-modal-close" aria-label="Close">&times;</button>
          </div>
          <div class="lwt-modal-body"></div>
        </div>
      </div>
    `;
    $('body').append(modalHtml);

    // Add CSS if not already present
    if ($('#lwt-modal-styles').length === 0) {
      const styles = `
        <style id="lwt-modal-styles">
          .lwt-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            display: flex;
            justify-content: center;
            align-items: center;
          }
          .lwt-modal {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
          }
          .lwt-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            background: #f5f5f5;
          }
          .lwt-modal-title {
            margin: 0;
            font-size: 1.25em;
            font-weight: bold;
            color: #333;
          }
          .lwt-modal-close {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: #666;
            padding: 0;
            line-height: 1;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
          }
          .lwt-modal-close:hover {
            background: #ddd;
            color: #333;
          }
          .lwt-modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
          }
          .lwt-modal-body table {
            width: 100%;
            border-collapse: collapse;
          }
          .lwt-modal-body th,
          .lwt-modal-body td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: left;
          }
          .lwt-modal-body th {
            background: #f0f0f0;
          }
          .lwt-modal-body tr:nth-child(even) {
            background: #fafafa;
          }
          .lwt-modal-body code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
          }
          .lwt-modal-body .center {
            text-align: center;
          }

          /* Dark theme support */
          @media (prefers-color-scheme: dark) {
            .lwt-modal {
              background: #2d2d2d;
              color: #e0e0e0;
            }
            .lwt-modal-header {
              background: #333;
              border-bottom-color: #444;
            }
            .lwt-modal-title {
              color: #e0e0e0;
            }
            .lwt-modal-close {
              color: #aaa;
            }
            .lwt-modal-close:hover {
              background: #444;
              color: #fff;
            }
            .lwt-modal-body th,
            .lwt-modal-body td {
              border-color: #444;
            }
            .lwt-modal-body th {
              background: #383838;
            }
            .lwt-modal-body tr:nth-child(even) {
              background: #333;
            }
            .lwt-modal-body code {
              background: #383838;
            }
          }
        </style>
      `;
      $('head').append(styles);
    }

    overlayInstance = $('#lwt-modal-overlay');
    modalInstance = $('#lwt-modal');

    // Close button click
    overlayInstance.on('click', '.lwt-modal-close', closeModal);
  } else {
    overlayInstance = $('#lwt-modal-overlay');
    modalInstance = $('#lwt-modal');
  }
}

/**
 * Open a modal dialog with the given content.
 *
 * @param content HTML content to display in the modal
 * @param options Modal configuration options
 */
export function openModal(content: string, options: ModalOptions = {}): void {
  const opts = { ...defaultOptions, ...options };

  ensureModalExists();

  if (!modalInstance || !overlayInstance) return;

  // Set title
  modalInstance.find('.lwt-modal-title').text(opts.title || '');
  modalInstance.find('.lwt-modal-header').toggle(!!opts.title);

  // Set content
  modalInstance.find('.lwt-modal-body').html(content);

  // Apply styles
  if (opts.width) {
    modalInstance.css('width', opts.width);
  }
  if (opts.maxWidth) {
    modalInstance.css('max-width', opts.maxWidth);
  }
  if (opts.maxHeight) {
    modalInstance.css('max-height', opts.maxHeight);
  }

  // Show modal
  overlayInstance.fadeIn(200);

  // Close on overlay click
  if (opts.closeOnOverlayClick) {
    overlayInstance.off('click.modal').on('click.modal', function (e) {
      if (e.target === this) {
        closeModal();
      }
    });
  }

  // Close on Escape key
  if (opts.closeOnEscape) {
    $(document).off('keydown.modal').on('keydown.modal', function (e) {
      if (e.key === 'Escape') {
        closeModal();
      }
    });
  }

  // Prevent body scroll
  $('body').css('overflow', 'hidden');
}

/**
 * Close the currently open modal.
 */
export function closeModal(): void {
  if (overlayInstance) {
    overlayInstance.fadeOut(200);
  }
  $(document).off('keydown.modal');
  $('body').css('overflow', '');
}

/**
 * Open a modal by fetching content from a URL.
 *
 * @param url URL to fetch content from
 * @param options Modal configuration options
 */
export function openModalFromUrl(url: string, options: ModalOptions = {}): void {
  $.get(url)
    .done(function (html) {
      // Extract body content if it's a full HTML document
      const bodyMatch = html.match(/<body[^>]*>([\s\S]*)<\/body>/i);
      const content = bodyMatch ? bodyMatch[1] : html;

      // Extract title if not provided
      if (!options.title) {
        const titleMatch = html.match(/<title>([^<]*)<\/title>/i);
        if (titleMatch) {
          options.title = titleMatch[1];
        }
      }

      openModal(content, options);
    })
    .fail(function () {
      openModal('<p class="error">Failed to load content.</p>', options);
    });
}

/**
 * Show the export template help modal.
 * This is a convenience function replacing oewin('export_template.html').
 */
export function showExportTemplateHelp(): void {
  const content = `
    <p>An export template consists of a string of characters. Some parts of this string are <b>placeholders</b> (beginning with <b>"%", "$" or "\\"</b>) that are <b>replaced</b> by the actual term data, <b>see the following table</b>. For each term (word or expression), that has been selected for export, the placeholders of the export template will be replaced by the term data and the string will be written to the export file.</p>

    <p><b>A template must end with</b> either <b>"\\n"</b> (UNIX, Mac) or <b>"\\r\\n"</b> (Windows). <b>If you omit this, the whole export will be one single line!</b></p>

    <p>If the export template is <b>empty, no terms of this language</b> will be exported.</p>

    <table>
      <tr><th colspan="2">Placeholders: <code>%...</code> = Raw Text</th></tr>
      <tr><td class="center"><code>%w</code></td><td>Term (Word/Expression) - as <b>raw text</b>.</td></tr>
      <tr><td class="center"><code>%t</code></td><td>Translation - as <b>raw text</b>.</td></tr>
      <tr><td class="center"><code>%s</code></td><td>Sentence, curly braces removed - as <b>raw text</b>.</td></tr>
      <tr><td class="center"><code>%c</code></td><td>The sentence, but the "{xxx}" parts are replaced by "[...]" (cloze test question) - as <b>raw text</b>.</td></tr>
      <tr><td class="center"><code>%d</code></td><td>The sentence, but the "{xxx}" parts are replaced by "[xxx]" (cloze test solution) - as <b>raw text</b>.</td></tr>
      <tr><td class="center"><code>%r</code></td><td>Romanization - as <b>raw text</b>.</td></tr>
      <tr><td class="center"><code>%a</code></td><td>Status (1..5, 98, 99) - as <b>raw text</b>.</td></tr>
      <tr><td class="center"><code>%k</code></td><td>Term in lowercase (key) - as <b>raw text</b>.</td></tr>
      <tr><td class="center"><code>%z</code></td><td>Tag List - as <b>raw text</b>.</td></tr>
      <tr><td class="center"><code>%l</code></td><td>Language - as <b>raw text</b>.</td></tr>
      <tr><td class="center"><code>%n</code></td><td>Word Number in LWT (key in table "words") - as <b>raw text</b>.</td></tr>
      <tr><td class="center"><code>%%</code></td><td>Just one percent sign "%".</td></tr>

      <tr><th colspan="2">Placeholders: <code>$...</code> = HTML Text (escaped: &lt; &gt; &amp; &quot;)</th></tr>
      <tr><td class="center"><code>$w</code></td><td>Term (Word/Expression) - as <b>HTML text</b>.</td></tr>
      <tr><td class="center"><code>$t</code></td><td>Translation - as <b>HTML text</b>.</td></tr>
      <tr><td class="center"><code>$s</code></td><td>Sentence, curly braces removed - as <b>HTML text</b>.</td></tr>
      <tr><td class="center"><code>$c</code></td><td>The sentence, but the "{xxx}" parts are replaced by "[...]" (cloze test question) - as <b>HTML text</b>.</td></tr>
      <tr><td class="center"><code>$d</code></td><td>The sentence, but the "{xxx}" parts are replaced by "[xxx]" (cloze test solution) - as <b>HTML text</b>.</td></tr>
      <tr><td class="center"><code>$x</code></td><td>The sentence in Anki2 cloze test notation: the "{xxx}" parts are replaced by "{{c1::xxx}}" - as <b>HTML text</b>.</td></tr>
      <tr><td class="center"><code>$y</code></td><td>The sentence in Anki2 cloze test notation, with translation: the "{xxx}" parts are replaced by "{{c1::xxx::translation}}" - as <b>HTML text</b>.</td></tr>
      <tr><td class="center"><code>$r</code></td><td>Romanization - as <b>HTML text</b>.</td></tr>
      <tr><td class="center"><code>$k</code></td><td>Term in lowercase (key) - as <b>HTML text</b>.</td></tr>
      <tr><td class="center"><code>$z</code></td><td>Tag List - as <b>HTML text</b>.</td></tr>
      <tr><td class="center"><code>$l</code></td><td>Language - as <b>HTML text</b>.</td></tr>
      <tr><td class="center"><code>$$</code></td><td>Just one dollar sign "$".</td></tr>

      <tr><th colspan="2">Special Characters: <code>\\...</code></th></tr>
      <tr><td class="center"><code>\\t</code></td><td>TAB character (HEX 9).</td></tr>
      <tr><td class="center"><code>\\n</code></td><td>NEWLINE character (HEX 10).</td></tr>
      <tr><td class="center"><code>\\r</code></td><td>CARRIAGE RETURN character (HEX 13).</td></tr>
      <tr><td class="center"><code>\\\\</code></td><td>Just one backslash "\\".</td></tr>
    </table>
  `;

  openModal(content, {
    title: 'About LWT Export Templates for "Flexible Exports"',
    maxWidth: '900px'
  });
}

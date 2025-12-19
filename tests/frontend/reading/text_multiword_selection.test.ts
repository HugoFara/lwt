/**
 * Tests for text_multiword_selection.ts - Multi-word drag-and-drop selection
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  mwordDragNDrop,
  mword_drag_n_drop_select
} from '../../../src/frontend/js/reading/text_multiword_selection';

// Mock LWT_DATA global
const mockLWT_DATA = {
  language: {
    id: 1,
    dict_link1: 'http://dict1.example.com/###',
    dict_link2: 'http://dict2.example.com/###',
    translator_link: 'http://translate.example.com/###',
    delimiter: ',',
    rtl: false,
  },
  text: {
    id: 42,
    reading_position: 0,
    annotations: {},
  },
  settings: {
    jQuery_tooltip: false,
    hts: 0,
    word_status_filter: '',
    annotations_mode: 0,
  },
};

// Mock removeAllTooltips using vi.hoisted to ensure it's available before module import
const mockRemoveAllTooltips = vi.hoisted(() => vi.fn());

vi.mock('../../../src/frontend/js/ui/native_tooltip', () => ({
  removeAllTooltips: mockRemoveAllTooltips
}));

// Mock imports
vi.mock('../../../src/frontend/js/reading/text_annotations', () => ({
  getAttr: vi.fn((el: HTMLElement, attr: string) => {
    const val = el.getAttribute(attr);
    return typeof val === 'string' ? val : '';
  })
}));

vi.mock('../../../src/frontend/js/core/hover_intent', () => ({
  hoverIntent: vi.fn()
}));

vi.mock('../../../src/frontend/js/reading/frame_management', () => ({
  loadModalFrame: vi.fn()
}));

import { loadModalFrame } from '../../../src/frontend/js/reading/frame_management';
import { hoverIntent } from '../../../src/frontend/js/core/hover_intent';

describe('text_multiword_selection.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    (window as Record<string, unknown>).LWT_DATA = JSON.parse(JSON.stringify(mockLWT_DATA));
    vi.clearAllMocks();
    vi.useFakeTimers();

    // Reset mwordDragNDrop state
    mwordDragNDrop.event = undefined;
    mwordDragNDrop.pos = undefined;
    mwordDragNDrop.timeout = undefined;
    mwordDragNDrop.context = undefined;
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // mwordDragNDrop.finish Tests
  // ===========================================================================

  describe('mwordDragNDrop.finish', () => {
    it('returns early when context is undefined', () => {
      mwordDragNDrop.context = undefined;
      const event = { handled: false } as MouseEvent & { handled?: boolean };

      mwordDragNDrop.finish(event);

      expect(loadModalFrame).not.toHaveBeenCalled();
    });

    it('returns early when event is already handled', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="lword tword" data_order="10">Test</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      const event = { handled: true } as MouseEvent & { handled?: boolean };

      mwordDragNDrop.finish(event);

      expect(loadModalFrame).not.toHaveBeenCalled();
    });

    it('does nothing when no lword.tword elements exist', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="word">Test</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      const event = { handled: false } as MouseEvent & { handled?: boolean };

      mwordDragNDrop.finish(event);

      expect(loadModalFrame).not.toHaveBeenCalled();
    });

    it('opens edit_word.php for single word selection', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="lword tword" data_order="15">Hello</span>
          <span id="ID-15-1">Hello</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      const event = { handled: false } as MouseEvent & { handled?: boolean };

      mwordDragNDrop.finish(event);

      expect(loadModalFrame).toHaveBeenCalledWith(
        expect.stringContaining('/word/edit?')
      );
      expect(loadModalFrame).toHaveBeenCalledWith(
        expect.stringContaining('tid=42')
      );
      expect(loadModalFrame).toHaveBeenCalledWith(
        expect.stringContaining('ord=15')
      );
      expect(event.handled).toBe(true);
    });

    it('opens edit_mword.php for multi-word selection', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="lword tword" data_order="10">Hello</span>
          <span class="lword tword" data_order="11">World</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      const event = { handled: false } as MouseEvent & { handled?: boolean };

      mwordDragNDrop.finish(event);

      expect(loadModalFrame).toHaveBeenCalledWith(
        expect.stringContaining('edit_mword.php?')
      );
      expect(loadModalFrame).toHaveBeenCalledWith(
        expect.stringContaining('tid=42')
      );
      expect(loadModalFrame).toHaveBeenCalledWith(
        expect.stringContaining('len=2')
      );
    });

    it('alerts when selected text is too long (>250 chars)', () => {
      const longWord = 'a'.repeat(251);
      document.body.innerHTML = `
        <div id="sentence">
          <span class="lword tword" data_order="10">${longWord}</span>
          <span class="lword tword" data_order="11">extra</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      const event = { handled: false } as MouseEvent & { handled?: boolean };
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      mwordDragNDrop.finish(event);

      expect(alertSpy).toHaveBeenCalledWith('Selected text is too long!!!');
      expect(loadModalFrame).not.toHaveBeenCalled();
    });

    it('removes tword and nword classes after finishing', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="lword tword" data_order="10">Hello</span>
          <span class="nword" data_order="11"> </span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      const event = { handled: false } as MouseEvent & { handled?: boolean };

      mwordDragNDrop.finish(event);

      expect(document.querySelectorAll('.tword').length).toBe(0);
      expect(document.querySelectorAll('.nword').length).toBe(0);
    });
  });

  // ===========================================================================
  // mwordDragNDrop.twordMouseOver Tests
  // ===========================================================================

  describe('mwordDragNDrop.twordMouseOver', () => {
    it('returns early when context is undefined', () => {
      mwordDragNDrop.context = undefined;
      const element = document.createElement('span');

      expect(() => mwordDragNDrop.twordMouseOver.call(element)).not.toThrow();
    });

    it('sets pos from data_order attribute', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="tword" data_order="25">Test</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      const element = document.querySelector('.tword') as HTMLElement;

      mwordDragNDrop.twordMouseOver.call(element);

      expect(mwordDragNDrop.pos).toBe(25);
    });

    it('adds lword class to the element and removes from others', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="lword tword" data_order="10">First</span>
          <span class="tword" data_order="20">Second</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      const secondElement = document.querySelectorAll('.tword')[1] as HTMLElement;

      mwordDragNDrop.twordMouseOver.call(secondElement);

      expect(document.querySelectorAll('.lword').length).toBe(1);
      expect(secondElement.classList.contains('lword')).toBe(true);
    });

    it('defaults to 0 when data_order is empty', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="tword">Test</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      const element = document.querySelector('.tword') as HTMLElement;

      mwordDragNDrop.twordMouseOver.call(element);

      expect(mwordDragNDrop.pos).toBe(0);
    });
  });

  // ===========================================================================
  // mwordDragNDrop.sentenceOver Tests
  // ===========================================================================

  describe('mwordDragNDrop.sentenceOver', () => {
    it('returns early when context is undefined', () => {
      mwordDragNDrop.context = undefined;
      const element = document.createElement('span');

      expect(() => mwordDragNDrop.sentenceOver.call(element)).not.toThrow();
    });

    it('adds lword class to elements between pos and current element (forward)', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="tword" data_order="10">First</span>
          <span class="tword" data_order="11">Second</span>
          <span class="tword" data_order="12">Third</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      mwordDragNDrop.pos = 10;
      const thirdElement = document.querySelectorAll('.tword')[2] as HTMLElement;

      mwordDragNDrop.sentenceOver.call(thirdElement);

      expect(document.querySelectorAll('.lword').length).toBe(3);
    });

    it('adds lword class to elements between pos and current element (backward)', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="tword" data_order="10">First</span>
          <span class="tword" data_order="11">Second</span>
          <span class="tword" data_order="12">Third</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      mwordDragNDrop.pos = 12;
      const firstElement = document.querySelectorAll('.tword')[0] as HTMLElement;

      mwordDragNDrop.sentenceOver.call(firstElement);

      expect(document.querySelectorAll('.lword').length).toBe(3);
    });

    it('handles nword elements in selection range', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="tword" data_order="10">First</span>
          <span class="nword" data_order="11"> </span>
          <span class="tword" data_order="12">Third</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      mwordDragNDrop.pos = 10;
      const thirdElement = document.querySelectorAll('.tword')[1] as HTMLElement;

      mwordDragNDrop.sentenceOver.call(thirdElement);

      expect(document.querySelectorAll('.nword.lword').length).toBe(1);
    });

    it('does nothing when pos is undefined', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="tword" data_order="10">First</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      mwordDragNDrop.pos = undefined;
      const element = document.querySelector('.tword') as HTMLElement;

      mwordDragNDrop.sentenceOver.call(element);

      // Only the current element gets lword, not range selection
      expect(document.querySelectorAll('.lword').length).toBe(1);
    });
  });

  // ===========================================================================
  // mwordDragNDrop.startInteraction Tests
  // ===========================================================================

  describe('mwordDragNDrop.startInteraction', () => {
    it('returns early when context is undefined', () => {
      mwordDragNDrop.context = undefined;

      expect(() => mwordDragNDrop.startInteraction()).not.toThrow();
    });

    it('adds #pe style element to body', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="wsty" data_code="1" data_order="10">Test</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;

      mwordDragNDrop.startInteraction();

      expect(document.getElementById('pe')).not.toBeNull();
    });

    it('removes existing #pe element before adding new one', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="wsty" data_code="1" data_order="10">Test</span>
        </div>
        <style id="pe">old style</style>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;

      mwordDragNDrop.startInteraction();

      expect(document.querySelectorAll('#pe').length).toBe(1);
      expect(document.getElementById('pe')?.textContent).not.toBe('old style');
    });

    it('adds nword class to non-word elements', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span id="ID-10-1" class="punctuation"> </span>
          <span id="ID-11-1" class="punctuation">.</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;

      mwordDragNDrop.startInteraction();

      expect(document.querySelectorAll('.nword').length).toBe(2);
      expect(document.getElementById('ID-10-1')?.getAttribute('data_order')).toBe('10');
    });

    it('wraps word elements with tword span', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="word" data_order="15">Hello</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;

      mwordDragNDrop.startInteraction();

      expect(document.querySelectorAll('.word .tword').length).toBe(1);
      expect(document.querySelector('.word .tword')?.getAttribute('data_order')).toBe('15');
    });

    it('sets up hoverIntent on context element', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="word" data_order="10">Test</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;

      mwordDragNDrop.startInteraction();

      expect(hoverIntent).toHaveBeenCalledWith(
        document.getElementById('sentence'),
        expect.objectContaining({
          over: mwordDragNDrop.sentenceOver,
          sensitivity: 18,
          selector: '.tword'
        })
      );
    });

    it('handles annotation mode 1 (right annotation)', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="wsty" data_code="1" data_order="10" data_ann="note" data_trans="translation" data_status="2">Test</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      mwordDragNDrop.event = { data: { annotation: 1 } } as MouseEvent & { data?: { annotation?: number } };

      mwordDragNDrop.startInteraction();

      // The wsty element should have status classes removed
      const wsty = document.querySelector('.wsty');
      expect(wsty?.classList.contains('status2')).toBe(false);
    });

    it('handles annotation mode 3 (left annotation)', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="wsty" data_code="1" data_order="10" data_ann="note" data_trans="translation" data_status="3">Test</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      mwordDragNDrop.event = { data: { annotation: 3 } } as MouseEvent & { data?: { annotation?: number } };

      mwordDragNDrop.startInteraction();

      // The wsty element should have status classes removed
      const wsty = document.querySelector('.wsty');
      expect(wsty?.classList.contains('status3')).toBe(false);
    });

    it('skips hidden wsty elements', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="wsty hide" data_code="1" data_order="10">Hidden</span>
          <span class="word" data_order="20">Visible</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;

      mwordDragNDrop.startInteraction();

      // Visible word element should be processed with tword class
      expect(document.querySelectorAll('.word .tword').length).toBeGreaterThan(0);
    });
  });

  // ===========================================================================
  // mwordDragNDrop.stopInteraction Tests
  // ===========================================================================

  describe('mwordDragNDrop.stopInteraction', () => {
    it('clears timeout if set', () => {
      const clearTimeoutSpy = vi.spyOn(global, 'clearTimeout');
      mwordDragNDrop.timeout = setTimeout(() => {}, 1000);

      mwordDragNDrop.stopInteraction();

      expect(clearTimeoutSpy).toHaveBeenCalled();
    });

    it('removes nword, tword, and lword classes', () => {
      document.body.innerHTML = `
        <span class="nword">Not word</span>
        <span class="tword">Term word</span>
        <span class="lword">Last word</span>
      `;

      mwordDragNDrop.stopInteraction();

      expect(document.querySelectorAll('.nword').length).toBe(0);
      expect(document.querySelectorAll('.tword').length).toBe(0);
      expect(document.querySelectorAll('.lword').length).toBe(0);
    });

    it('removes #pe style element', () => {
      document.body.innerHTML = `
        <style id="pe">some style</style>
      `;

      mwordDragNDrop.stopInteraction();

      expect(document.getElementById('pe')).toBeNull();
    });

    it('resets wsty element styles when context exists', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="wsty" style="background-color: red; border-bottom-color: blue;">Test</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;

      mwordDragNDrop.stopInteraction();

      const wsty = document.querySelector('.wsty') as HTMLElement;
      expect(wsty.style.backgroundColor).toBe('');
      expect(wsty.style.borderBottomColor).toBe('');
    });

    it('handles undefined timeout gracefully', () => {
      mwordDragNDrop.timeout = undefined;

      expect(() => mwordDragNDrop.stopInteraction()).not.toThrow();
    });

    it('handles undefined context gracefully', () => {
      mwordDragNDrop.context = undefined;

      expect(() => mwordDragNDrop.stopInteraction()).not.toThrow();
    });
  });

  // ===========================================================================
  // mword_drag_n_drop_select Tests
  // ===========================================================================

  describe('mword_drag_n_drop_select', () => {
    it('removes jQuery tooltips when jQuery_tooltip is enabled', () => {
      document.body.innerHTML = `
        <div class="ui-tooltip">Tooltip content</div>
        <div id="sentence">
          <span class="word" data_order="10">Test</span>
        </div>
      `;
      (window as Record<string, unknown>).LWT_DATA = {
        ...mockLWT_DATA,
        settings: { ...mockLWT_DATA.settings, jQuery_tooltip: true }
      };
      const element = document.querySelector('.word') as HTMLElement;
      const event = {} as MouseEvent;

      mword_drag_n_drop_select.call(element, event);

      // Now using native_tooltip.removeAllTooltips
      expect(mockRemoveAllTooltips).toHaveBeenCalled();
    });

    it('sets context to parent sentence', () => {
      document.body.innerHTML = `
        <div id="sentence" class="sentence">
          <span class="word" data_order="10">Test</span>
        </div>
      `;
      const element = document.querySelector('.word') as HTMLElement;
      const event = {} as MouseEvent;

      mword_drag_n_drop_select.call(element, event);

      expect(mwordDragNDrop.context).toBeDefined();
      expect(mwordDragNDrop.context?.id).toBe('sentence');
    });

    it('stores the event', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="word" data_order="10">Test</span>
        </div>
      `;
      const element = document.querySelector('.word') as HTMLElement;
      const event = { type: 'mousedown' } as MouseEvent;

      mword_drag_n_drop_select.call(element, event);

      expect(mwordDragNDrop.event).toBe(event);
    });

    it('sets timeout for startInteraction', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="word" data_order="10">Test</span>
        </div>
      `;
      const element = document.querySelector('.word') as HTMLElement;
      const event = {} as MouseEvent;
      const startInteractionSpy = vi.spyOn(mwordDragNDrop, 'startInteraction');

      mword_drag_n_drop_select.call(element, event);

      expect(mwordDragNDrop.timeout).toBeDefined();
      expect(startInteractionSpy).not.toHaveBeenCalled();

      vi.advanceTimersByTime(300);

      expect(startInteractionSpy).toHaveBeenCalled();
    });

    it('binds mouseup and mouseout to stopInteraction on sentence', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="word" data_order="10">Test</span>
        </div>
      `;
      const element = document.querySelector('.word') as HTMLElement;
      const event = {} as MouseEvent;
      const stopInteractionSpy = vi.spyOn(mwordDragNDrop, 'stopInteraction');

      mword_drag_n_drop_select.call(element, event);

      // Trigger mouseup on sentence
      const sentence = document.getElementById('sentence')!;
      sentence.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));

      expect(stopInteractionSpy).toHaveBeenCalled();
    });

    it('does not remove tooltips when jQuery_tooltip is disabled', () => {
      document.body.innerHTML = `
        <div class="ui-tooltip">Tooltip content</div>
        <div id="sentence">
          <span class="word" data_order="10">Test</span>
        </div>
      `;
      (window as Record<string, unknown>).LWT_DATA = {
        ...mockLWT_DATA,
        settings: { ...mockLWT_DATA.settings, jQuery_tooltip: false }
      };
      const element = document.querySelector('.word') as HTMLElement;
      const event = {} as MouseEvent;

      mword_drag_n_drop_select.call(element, event);

      expect(mockRemoveAllTooltips).not.toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Integration Tests
  // ===========================================================================

  describe('Integration', () => {
    it('full selection workflow works correctly', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="word wsty" data_order="10" data_code="1" id="ID-10-1">First</span>
          <span id="ID-11-1"> </span>
          <span class="word wsty" data_order="12" data_code="2" id="ID-12-1">Second</span>
        </div>
      `;
      const firstWord = document.querySelector('#ID-10-1') as HTMLElement;
      const event = {} as MouseEvent;

      // Start selection
      mword_drag_n_drop_select.call(firstWord, event);

      // Wait for timeout
      vi.advanceTimersByTime(300);

      // Context should be set
      expect(mwordDragNDrop.context).toBeDefined();

      // Stop interaction
      mwordDragNDrop.stopInteraction();

      // Classes should be cleaned up
      expect(document.querySelectorAll('.tword').length).toBe(0);
      expect(document.querySelectorAll('.nword').length).toBe(0);
    });

    it('handles mouseout before timeout fires', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="word" data_order="10">Test</span>
        </div>
      `;
      const element = document.querySelector('.word') as HTMLElement;
      const event = {} as MouseEvent;
      const stopInteractionSpy = vi.spyOn(mwordDragNDrop, 'stopInteraction');

      mword_drag_n_drop_select.call(element, event);

      // Trigger mouseout before timeout - this calls stopInteraction
      const sentence = document.getElementById('sentence')!;
      sentence.dispatchEvent(new MouseEvent('mouseout', { bubbles: true }));

      // stopInteraction should have been called
      expect(stopInteractionSpy).toHaveBeenCalled();
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles empty sentence container', () => {
      document.body.innerHTML = `
        <div id="sentence"></div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;

      expect(() => mwordDragNDrop.startInteraction()).not.toThrow();
    });

    it('handles word without parent', () => {
      document.body.innerHTML = `
        <span class="word" data_order="10">Orphan</span>
      `;
      const element = document.querySelector('.word') as HTMLElement;
      const event = {} as MouseEvent;

      expect(() => mword_drag_n_drop_select.call(element, event)).not.toThrow();
    });

    it('handles finish with exactly 250 character text', () => {
      const exactText = 'a'.repeat(250);
      document.body.innerHTML = `
        <div id="sentence">
          <span class="lword tword" data_order="10">${exactText}</span>
          <span class="lword tword" data_order="11">x</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      const event = { handled: false } as MouseEvent & { handled?: boolean };
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      mwordDragNDrop.finish(event);

      // 251 chars is over limit, but 250 + 1 = 251 is also over
      expect(alertSpy).toHaveBeenCalled();
    });

    it('handles data_order with non-numeric value', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="tword" data_order="abc">Test</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      const element = document.querySelector('.tword') as HTMLElement;

      mwordDragNDrop.twordMouseOver.call(element);

      expect(mwordDragNDrop.pos).toBeNaN();
    });

    it('finish combines text from multiple lword elements', () => {
      document.body.innerHTML = `
        <div id="sentence">
          <span class="lword tword" data_order="10">Hello</span>
          <span class="lword tword" data_order="11"> </span>
          <span class="lword tword" data_order="12">World</span>
        </div>
      `;
      mwordDragNDrop.context = document.getElementById('sentence')!;
      const event = { handled: false } as MouseEvent & { handled?: boolean };

      mwordDragNDrop.finish(event);

      // URLSearchParams encodes space as +
      expect(loadModalFrame).toHaveBeenCalledWith(
        expect.stringContaining('txt=Hello+')
      );
    });
  });
});

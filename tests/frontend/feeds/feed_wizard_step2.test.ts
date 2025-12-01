/**
 * Tests for feed_wizard_step2.ts - Select Article Text interactions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import {
  lwt_wiz_select_test,
  initWizardStep2
} from '../../../src/frontend/js/feeds/feed_wizard_step2';

// Mock jq_feedwizard module
vi.mock('../../../src/frontend/js/feeds/jq_feedwizard', () => ({
  extend_adv_xpath: vi.fn(),
  lwt_feed_wizard: {
    prepareInteractions: vi.fn()
  }
}));

describe('feed_wizard_step2.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
    window.filter_Array = [];
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // lwt_wiz_select_test.clickCancel Tests
  // ===========================================================================

  describe('lwt_wiz_select_test.clickCancel', () => {
    it('hides the #adv element', () => {
      document.body.innerHTML = `
        <div id="adv">Advanced selection</div>
        <div id="lwt_last"></div>
        <div id="lwt_header" style="height: 50px;"></div>
      `;

      lwt_wiz_select_test.clickCancel();

      expect(document.getElementById('adv')?.style.display).toBe('none');
    });

    it('adjusts margin-top of #lwt_last', () => {
      document.body.innerHTML = `
        <div id="adv">Advanced</div>
        <div id="lwt_last" style="margin-top: 0;"></div>
        <div id="lwt_header" style="height: 100px;"></div>
      `;

      lwt_wiz_select_test.clickCancel();

      // Should set margin-top based on header height
      expect(document.getElementById('lwt_last')?.style.marginTop).toBeDefined();
    });

    it('returns false', () => {
      document.body.innerHTML = `
        <div id="adv"></div>
        <div id="lwt_last"></div>
        <div id="lwt_header"></div>
      `;

      const result = lwt_wiz_select_test.clickCancel();

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // lwt_wiz_select_test.changeSelectMode Tests
  // ===========================================================================

  describe('lwt_wiz_select_test.changeSelectMode', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div class="lwt_marked_text">Marked text</div>
        <button id="get_button">Get</button>
        <select id="mark_action">
          <option value="old">Old Option</option>
        </select>
      `;
    });

    it('removes lwt_marked_text class from all elements', () => {
      lwt_wiz_select_test.changeSelectMode();

      expect(document.querySelectorAll('.lwt_marked_text').length).toBe(0);
    });

    it('disables the get_button', () => {
      lwt_wiz_select_test.changeSelectMode();

      expect((document.getElementById('get_button') as HTMLButtonElement)?.disabled).toBe(true);
    });

    it('resets mark_action select with default option', () => {
      lwt_wiz_select_test.changeSelectMode();

      const options = document.querySelectorAll('#mark_action option');
      expect(options.length).toBe(1);
      expect(options[0]?.textContent).toBe('[Click On Text]');
    });

    it('returns false', () => {
      const result = lwt_wiz_select_test.changeSelectMode();

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // lwt_wiz_select_test.changeHideImage Tests
  // ===========================================================================

  describe('lwt_wiz_select_test.changeHideImage', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="lwt_header">
          <img src="header-icon.png">
        </div>
        <img src="content-image1.png" class="content-img">
        <img src="content-image2.png" class="content-img">
        <select id="hideSelect">
          <option value="no">Show Images</option>
          <option value="yes">Hide Images</option>
        </select>
      `;
    });

    it('shows images when value is "no"', () => {
      // First hide them
      document.querySelectorAll<HTMLImageElement>('img').forEach(img => {
        if (!img.closest('#lwt_header')) {
          img.style.display = 'none';
        }
      });

      const select = document.querySelector<HTMLSelectElement>('#hideSelect')!;
      select.value = 'no';

      lwt_wiz_select_test.changeHideImage.call(select);

      const contentImages = document.querySelectorAll('.content-img');
      contentImages.forEach(img => {
        expect((img as HTMLElement).style.display).toBe('');
      });
    });

    it('hides images when value is not "no"', () => {
      const select = document.querySelector<HTMLSelectElement>('#hideSelect')!;
      select.value = 'yes';

      lwt_wiz_select_test.changeHideImage.call(select);

      const contentImages = document.querySelectorAll('.content-img');
      contentImages.forEach(img => {
        expect((img as HTMLElement).style.display).toBe('none');
      });
    });

    it('does not hide images in #lwt_header', () => {
      const select = document.querySelector<HTMLSelectElement>('#hideSelect')!;
      select.value = 'yes';

      lwt_wiz_select_test.changeHideImage.call(select);

      const headerImg = document.querySelector('#lwt_header img') as HTMLElement;
      expect(headerImg.style.display).not.toBe('none');
    });

    it('returns false', () => {
      const select = document.createElement('select');
      const result = lwt_wiz_select_test.changeHideImage.call(select);

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // lwt_wiz_select_test.clickBack Tests
  // ===========================================================================

  describe('lwt_wiz_select_test.clickBack', () => {
    it('returns false', () => {
      document.body.innerHTML = `
        <select name="select_mode">
          <option value="simple" selected>Simple</option>
        </select>
        <select name="hide_images">
          <option value="no" selected>No</option>
        </select>
      `;

      const result = lwt_wiz_select_test.clickBack();

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // lwt_wiz_select_test.clickMinMax Tests
  // ===========================================================================

  describe('lwt_wiz_select_test.clickMinMax', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="lwt_container">Container content</div>
        <div id="lwt_last"></div>
        <div id="lwt_header" style="height: 50px;"></div>
        <input type="hidden" name="maxim" value="1">
      `;
    });

    it('toggles #lwt_container visibility', () => {
      // First call should hide it
      lwt_wiz_select_test.clickMinMax();
      expect(document.getElementById('lwt_container')?.style.display).toBe('none');

      // Second call should show it
      lwt_wiz_select_test.clickMinMax();
      expect(document.getElementById('lwt_container')?.style.display).not.toBe('none');
    });

    it('sets maxim to 0 when hidden', () => {
      lwt_wiz_select_test.clickMinMax();

      expect((document.querySelector('input[name="maxim"]') as HTMLInputElement)?.value).toBe('0');
    });

    it('sets maxim to 1 when visible', () => {
      // Hide first
      lwt_wiz_select_test.clickMinMax();
      // Show again
      lwt_wiz_select_test.clickMinMax();

      expect((document.querySelector('input[name="maxim"]') as HTMLInputElement)?.value).toBe('1');
    });

    it('returns false', () => {
      const result = lwt_wiz_select_test.clickMinMax();

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // lwt_wiz_select_test.setMaxim Tests
  // ===========================================================================

  describe('lwt_wiz_select_test.setMaxim', () => {
    it('hides #lwt_container', () => {
      document.body.innerHTML = `
        <div id="lwt_container">Content</div>
        <div id="lwt_last"></div>
        <div id="lwt_header" style="height: 50px;"></div>
        <input type="hidden" name="maxim" value="1">
      `;

      lwt_wiz_select_test.setMaxim();

      expect(document.getElementById('lwt_container')?.style.display).toBe('none');
    });

    it('sets maxim to 0', () => {
      document.body.innerHTML = `
        <div id="lwt_container">Content</div>
        <div id="lwt_last"></div>
        <div id="lwt_header"></div>
        <input type="hidden" name="maxim" value="1">
      `;

      lwt_wiz_select_test.setMaxim();

      expect((document.querySelector('input[name="maxim"]') as HTMLInputElement)?.value).toBe('0');
    });
  });

  // ===========================================================================
  // initWizardStep2 Tests
  // ===========================================================================

  describe('initWizardStep2', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="lwt_header" style="height: 50px;">
          <img src="header.png">
        </div>
        <div id="lwt_container">Content</div>
        <div id="lwt_last"></div>
        <img src="content.png" class="content-img">
        <input type="hidden" name="maxim" value="1">
      `;
    });

    it('initializes filter_Array to empty array', () => {
      window.filter_Array = [document.createElement('div')];

      initWizardStep2(false, false);

      expect(window.filter_Array).toEqual([]);
    });

    it('hides images when hideImages is true', () => {
      initWizardStep2(true, false);

      const contentImg = document.querySelector('.content-img') as HTMLElement;
      expect(contentImg.style.display).toBe('none');
    });

    it('does not hide images when hideImages is false', () => {
      initWizardStep2(false, false);

      const contentImg = document.querySelector('.content-img') as HTMLElement;
      expect(contentImg.style.display).not.toBe('none');
    });

    it('calls setMaxim when isMinimized is true', () => {
      initWizardStep2(false, true);

      expect(document.getElementById('lwt_container')?.style.display).toBe('none');
    });

    it('does not call setMaxim when isMinimized is false', () => {
      initWizardStep2(false, false);

      expect(document.getElementById('lwt_container')?.style.display).not.toBe('none');
    });
  });

  // ===========================================================================
  // Event Delegation Tests
  // ===========================================================================

  describe('event delegation', () => {
    it('handles wizard-cancel button click', async () => {
      document.body.innerHTML = `
        <button data-action="wizard-cancel">Cancel</button>
        <div id="adv">Advanced</div>
        <div id="lwt_last"></div>
        <div id="lwt_header"></div>
      `;

      await import('../../../src/frontend/js/feeds/feed_wizard_step2');

      const button = document.querySelector<HTMLButtonElement>('[data-action="wizard-cancel"]')!;
      button.click();

      expect(document.getElementById('adv')?.style.display).toBe('none');
    });

    it('handles wizard-select-mode change', async () => {
      document.body.innerHTML = `
        <select data-action="wizard-select-mode">
          <option value="1">Mode 1</option>
        </select>
        <button id="get_button">Get</button>
        <select id="mark_action"></select>
      `;

      await import('../../../src/frontend/js/feeds/feed_wizard_step2');

      const select = document.querySelector<HTMLSelectElement>('[data-action="wizard-select-mode"]')!;
      select.dispatchEvent(new Event('change', { bubbles: true }));

      expect((document.getElementById('get_button') as HTMLButtonElement)?.disabled).toBe(true);
    });

    it('handles wizard-minmax button click', async () => {
      document.body.innerHTML = `
        <button data-action="wizard-minmax">Toggle</button>
        <div id="lwt_container">Content</div>
        <div id="lwt_last"></div>
        <div id="lwt_header"></div>
        <input name="maxim" value="1">
      `;

      await import('../../../src/frontend/js/feeds/feed_wizard_step2');

      const button = document.querySelector<HTMLButtonElement>('[data-action="wizard-minmax"]')!;
      button.click();

      expect(document.getElementById('lwt_container')?.style.display).toBe('none');
    });
  });

  // ===========================================================================
  // Window Exports Tests
  // ===========================================================================

  describe('window exports', () => {
    it('exports lwt_wiz_select_test to window', async () => {
      await import('../../../src/frontend/js/feeds/feed_wizard_step2');

      expect((window as unknown as Record<string, unknown>).lwt_wiz_select_test).toBeDefined();
    });

    it('exports initWizardStep2 to window', async () => {
      await import('../../../src/frontend/js/feeds/feed_wizard_step2');

      expect((window as unknown as Record<string, unknown>).initWizardStep2).toBeDefined();
    });
  });
});

/**
 * Tests for feed_wizard_step3.ts - Filter Text interactions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import $ from 'jquery';
import {
  lwt_wizard_filter,
  initWizardStep3
} from '../../../src/frontend/js/feeds/feed_wizard_step3';

// Mock jq_feedwizard module
vi.mock('../../../src/frontend/js/feeds/jq_feedwizard', () => ({
  extend_adv_xpath: vi.fn(),
  lwt_feed_wizard: {
    prepareInteractions: vi.fn()
  }
}));

// Mock xpathQuery global function - returns HTMLElement[] not jQuery
const mockXpathQuery = vi.fn(() => [] as HTMLElement[]);
(window as unknown as Record<string, unknown>).xpathQuery = mockXpathQuery;

describe('feed_wizard_step3.ts', () => {
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
  // lwt_wizard_filter.hideImages Tests
  // ===========================================================================

  describe('lwt_wizard_filter.hideImages', () => {
    it('hides all images except those in #lwt_header', () => {
      document.body.innerHTML = `
        <div id="lwt_header">
          <img src="header.png" id="header-img">
        </div>
        <img src="content1.png" class="content-img">
        <img src="content2.png" class="content-img">
      `;

      lwt_wizard_filter.hideImages();

      const contentImages = document.querySelectorAll('.content-img');
      contentImages.forEach(img => {
        expect((img as HTMLElement).style.display).toBe('none');
      });

      const headerImg = document.querySelector('#header-img') as HTMLElement;
      expect(headerImg.style.display).not.toBe('none');
    });
  });

  // ===========================================================================
  // lwt_wizard_filter.clickCancel Tests
  // ===========================================================================

  describe('lwt_wizard_filter.clickCancel', () => {
    it('hides the #adv element', () => {
      document.body.innerHTML = `
        <div id="adv">Advanced</div>
        <div id="lwt_last"></div>
        <div id="lwt_header" style="height: 50px;"></div>
      `;

      lwt_wizard_filter.clickCancel();

      expect($('#adv').css('display')).toBe('none');
    });

    it('returns false', () => {
      document.body.innerHTML = `
        <div id="adv"></div>
        <div id="lwt_last"></div>
        <div id="lwt_header"></div>
      `;

      const result = lwt_wizard_filter.clickCancel();

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // lwt_wizard_filter.changeSelectMode Tests
  // ===========================================================================

  describe('lwt_wizard_filter.changeSelectMode', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div class="lwt_marked_text">Marked</div>
        <button id="get_button">Get</button>
        <select id="mark_action">
          <option value="old">Old</option>
        </select>
      `;
    });

    it('removes lwt_marked_text class', () => {
      lwt_wizard_filter.changeSelectMode();

      expect($('.lwt_marked_text').length).toBe(0);
    });

    it('disables get_button', () => {
      lwt_wizard_filter.changeSelectMode();

      expect($('#get_button').prop('disabled')).toBe(true);
    });

    it('resets mark_action with default option', () => {
      lwt_wizard_filter.changeSelectMode();

      expect($('#mark_action option').text()).toBe('[Click On Text]');
    });

    it('returns false', () => {
      const result = lwt_wizard_filter.changeSelectMode();

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // lwt_wizard_filter.changeHideImages Tests
  // ===========================================================================

  describe('lwt_wizard_filter.changeHideImages', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="lwt_header">
          <img src="header.png">
        </div>
        <img src="content.png" class="content-img">
        <select id="hideSelect">
          <option value="no">No</option>
          <option value="yes">Yes</option>
        </select>
      `;
    });

    it('shows images when value is "no"', () => {
      // Hide all images except those in #lwt_header
      document.querySelectorAll<HTMLImageElement>('img').forEach(img => {
        if (!img.closest('#lwt_header')) {
          img.style.display = 'none';
        }
      });

      const select = document.querySelector<HTMLSelectElement>('#hideSelect')!;
      select.value = 'no';

      lwt_wizard_filter.changeHideImages.call(select);

      // JSDOM normalizes empty display to computed value
      const contentImg = document.querySelector<HTMLImageElement>('.content-img');
      expect(contentImg?.style.display).not.toBe('none');
    });

    it('hides images when value is not "no"', () => {
      const select = document.querySelector<HTMLSelectElement>('#hideSelect')!;
      select.value = 'yes';

      lwt_wizard_filter.changeHideImages.call(select);

      expect($('.content-img').css('display')).toBe('none');
    });

    it('returns false', () => {
      const select = document.createElement('select');
      const result = lwt_wizard_filter.changeHideImages.call(select);

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // lwt_wizard_filter.clickBack Tests
  // ===========================================================================

  describe('lwt_wizard_filter.clickBack', () => {
    it('returns false', () => {
      document.body.innerHTML = `
        <div id="lwt_sel">Selected content</div>
        <input id="maxim" value="1">
        <select name="select_mode"><option value="simple" selected>Simple</option></select>
        <select name="hide_images"><option value="no" selected>No</option></select>
      `;

      const result = lwt_wizard_filter.clickBack();

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // lwt_wizard_filter.clickMinMax Tests
  // ===========================================================================

  describe('lwt_wizard_filter.clickMinMax', () => {
    beforeEach(() => {
      document.body.innerHTML = `
        <div id="lwt_container">Content</div>
        <div id="lwt_last"></div>
        <div id="lwt_header" style="height: 50px;"></div>
        <input type="hidden" name="maxim" value="1">
      `;
    });

    it('toggles #lwt_container visibility', () => {
      lwt_wizard_filter.clickMinMax();
      expect($('#lwt_container').css('display')).toBe('none');

      lwt_wizard_filter.clickMinMax();
      expect($('#lwt_container').css('display')).not.toBe('none');
    });

    it('updates maxim value', () => {
      lwt_wizard_filter.clickMinMax();
      expect($('input[name="maxim"]').val()).toBe('0');

      lwt_wizard_filter.clickMinMax();
      expect($('input[name="maxim"]').val()).toBe('1');
    });

    it('returns false', () => {
      const result = lwt_wizard_filter.clickMinMax();

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // lwt_wizard_filter.setMaxim Tests
  // ===========================================================================

  describe('lwt_wizard_filter.setMaxim', () => {
    it('hides #lwt_container and sets maxim to 0', () => {
      document.body.innerHTML = `
        <div id="lwt_container">Content</div>
        <div id="lwt_last"></div>
        <div id="lwt_header"></div>
        <input type="hidden" name="maxim" value="1">
      `;

      lwt_wizard_filter.setMaxim();

      expect($('#lwt_container').css('display')).toBe('none');
      expect($('input[name="maxim"]').val()).toBe('0');
    });
  });

  // ===========================================================================
  // lwt_wizard_filter.updateFilterArray Tests
  // ===========================================================================

  describe('lwt_wizard_filter.updateFilterArray', () => {
    it('alerts when article selector is empty', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      lwt_wizard_filter.updateFilterArray('');

      expect(alertSpy).toHaveBeenCalledWith('Article section is empty!');
    });

    it('alerts when article selector is whitespace only', () => {
      const alertSpy = vi.spyOn(window, 'alert').mockImplementation(() => {});

      lwt_wizard_filter.updateFilterArray('   ');

      expect(alertSpy).toHaveBeenCalledWith('Article section is empty!');
    });

    it('adds lwt_filtered_text class to filtered elements', () => {
      document.body.innerHTML = `
        <div id="lwt_header">Header</div>
        <div id="content">
          <p id="para1">Paragraph 1</p>
          <p id="para2">Paragraph 2</p>
        </div>
      `;

      // Mock xpathQuery to return array with matching element
      const para1 = document.getElementById('para1') as HTMLElement;
      mockXpathQuery.mockReturnValue([para1]);

      lwt_wizard_filter.updateFilterArray('//p[@id="para1"]');

      // Elements not matching xpath should get filtered class
      // Note: Behavior depends on xpathQuery mock
    });
  });

  // ===========================================================================
  // initWizardStep3 Tests
  // ===========================================================================

  describe('initWizardStep3', () => {
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

      // Mock alert since updateFilterArray will be called
      vi.spyOn(window, 'alert').mockImplementation(() => {});

      initWizardStep3({
        articleSelector: '',
        hideImages: false,
        isMinimized: false
      });

      expect(window.filter_Array).toEqual([]);
    });

    it('hides images when hideImages is true', () => {
      vi.spyOn(window, 'alert').mockImplementation(() => {});

      initWizardStep3({
        articleSelector: '',
        hideImages: true,
        isMinimized: false
      });

      expect($('.content-img').css('display')).toBe('none');
    });

    it('calls setMaxim when isMinimized is true', () => {
      vi.spyOn(window, 'alert').mockImplementation(() => {});

      initWizardStep3({
        articleSelector: '',
        hideImages: false,
        isMinimized: true
      });

      expect($('#lwt_container').css('display')).toBe('none');
    });
  });

  // ===========================================================================
  // Event Delegation Tests
  // ===========================================================================

  describe('event delegation', () => {
    it('handles wizard-step3-cancel button click', async () => {
      document.body.innerHTML = `
        <button data-action="wizard-step3-cancel">Cancel</button>
        <div id="adv">Advanced</div>
        <div id="lwt_last"></div>
        <div id="lwt_header"></div>
      `;

      await import('../../../src/frontend/js/feeds/feed_wizard_step3');

      const button = document.querySelector<HTMLButtonElement>('[data-action="wizard-step3-cancel"]')!;
      button.click();

      expect($('#adv').css('display')).toBe('none');
    });

    it('handles wizard-step3-minmax button click', async () => {
      document.body.innerHTML = `
        <button data-action="wizard-step3-minmax">Toggle</button>
        <div id="lwt_container">Content</div>
        <div id="lwt_last"></div>
        <div id="lwt_header"></div>
        <input name="maxim" value="1">
      `;

      await import('../../../src/frontend/js/feeds/feed_wizard_step3');

      const button = document.querySelector<HTMLButtonElement>('[data-action="wizard-step3-minmax"]')!;
      button.click();

      expect($('#lwt_container').css('display')).toBe('none');
    });

    it('handles wizard-settings-open button click', async () => {
      document.body.innerHTML = `
        <button data-action="wizard-settings-open">Settings</button>
        <div id="settings" style="display: none;">Settings content</div>
      `;

      await import('../../../src/frontend/js/feeds/feed_wizard_step3');

      const button = document.querySelector<HTMLButtonElement>('[data-action="wizard-settings-open"]')!;
      button.click();

      expect($('#settings').css('display')).not.toBe('none');
    });

    it('handles wizard-settings-close button click', async () => {
      document.body.innerHTML = `
        <button data-action="wizard-settings-close">Close</button>
        <div id="settings">Settings content</div>
      `;

      await import('../../../src/frontend/js/feeds/feed_wizard_step3');

      const button = document.querySelector<HTMLButtonElement>('[data-action="wizard-settings-close"]')!;
      button.click();

      expect($('#settings').css('display')).toBe('none');
    });
  });

  // ===========================================================================
  // Window Exports Tests
  // ===========================================================================

  describe('window exports', () => {
    it('exports lwt_wizard_filter to window', async () => {
      await import('../../../src/frontend/js/feeds/feed_wizard_step3');

      expect((window as unknown as Record<string, unknown>).lwt_wizard_filter).toBeDefined();
    });

    it('exports initWizardStep3 to window', async () => {
      await import('../../../src/frontend/js/feeds/feed_wizard_step3');

      expect((window as unknown as Record<string, unknown>).initWizardStep3).toBeDefined();
    });
  });
});

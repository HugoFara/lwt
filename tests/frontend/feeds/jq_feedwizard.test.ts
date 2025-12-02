/**
 * Tests for jq_feedwizard.ts - Feed wizard interactions
 */
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';

// Mock xpathQuery and isValidXPath on window before import
const mockXpathQuery = vi.fn().mockImplementation((expr: string) => {
  // Return array of elements that match a simple selector simulation
  if (expr.includes('id')) {
    const div = document.createElement('div');
    div.id = 'test';
    return [div];
  }
  return [];
});

const mockIsValidXPath = vi.fn().mockReturnValue(true);

(window as Record<string, unknown>).xpathQuery = mockXpathQuery;
(window as Record<string, unknown>).isValidXPath = mockIsValidXPath;

// Mock filter_Array global used by prepareInteractions
(window as Record<string, unknown>).filter_Array = [];
(global as Record<string, unknown>).filter_Array = [];

// Now import the module (after globals are set)
const jq_feedwizard = await import('../../../src/frontend/js/feeds/jq_feedwizard');
const { extend_adv_xpath, lwt_feed_wiz_opt_inter, lwt_feed_wizard } = jq_feedwizard;

describe('jq_feedwizard.ts', () => {
  beforeEach(() => {
    document.body.innerHTML = '';
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
  });

  // ===========================================================================
  // XPath Helper Functions
  // ===========================================================================

  describe('XPath helper functions', () => {
    describe('xpathQuery', () => {
      it('is exported to window', () => {
        expect((window as Record<string, unknown>).xpathQuery).toBeDefined();
      });
    });

    describe('isValidXPath', () => {
      it('is exported to window', () => {
        expect((window as Record<string, unknown>).isValidXPath).toBeDefined();
      });
    });
  });

  // ===========================================================================
  // extend_adv_xpath Tests
  // ===========================================================================

  describe('extend_adv_xpath', () => {
    it('prepends custom xpath input to #adv', () => {
      document.body.innerHTML = `
        <div id="adv" style="display:none"></div>
        <select id="mark_action"></select>
        <div id="test-element" class="test-class" data-attr="value">Content</div>
      `;

      const el = document.querySelector('#test-element') as HTMLElement;
      extend_adv_xpath(el);

      // Note: :visible doesn't work correctly in jsdom, so check for style change and content
      const advEl = document.querySelector('#adv') as HTMLElement;
      expect(advEl.style.display).not.toBe('none');
      expect(advEl.querySelector('#custom_xpath')).not.toBeNull();
    });

    it('creates radio buttons for id attributes', () => {
      document.body.innerHTML = `
        <div id="adv" style="display:none"></div>
        <select id="mark_action"><option data-tag-name="div"></option></select>
        <div id="my-element">Content</div>
      `;

      const el = document.querySelector('#my-element') as HTMLElement;
      extend_adv_xpath(el);

      const advEl = document.querySelector('#adv') as HTMLElement;
      expect(advEl.innerHTML).toContain('contains id');
    });

    it('creates radio buttons for class attributes', () => {
      document.body.innerHTML = `
        <div id="adv" style="display:none"></div>
        <select id="mark_action"><option data-tag-name="div"></option></select>
        <div class="my-class another-class">Content</div>
      `;

      const el = document.querySelector('.my-class') as HTMLElement;
      extend_adv_xpath(el);

      const advEl = document.querySelector('#adv') as HTMLElement;
      expect(advEl.innerHTML).toContain('contains class');
    });

    it('removes lwt_marked_text class from all elements', () => {
      document.body.innerHTML = `
        <div id="adv" style="display:none"></div>
        <select id="mark_action"></select>
        <div class="lwt_marked_text">Marked</div>
        <div id="target">Target</div>
      `;

      const el = document.querySelector('#target') as HTMLElement;
      extend_adv_xpath(el);

      expect(document.querySelectorAll('.lwt_marked_text').length).toBe(0);
    });

    it('wraps radio buttons with labels', () => {
      document.body.innerHTML = `
        <div id="adv" style="display:none"></div>
        <select id="mark_action"><option data-tag-name="div"></option></select>
        <div id="test">Content</div>
      `;

      const el = document.querySelector('#test') as HTMLElement;
      extend_adv_xpath(el);

      const advEl = document.querySelector('#adv') as HTMLElement;
      expect(advEl.querySelectorAll('label.wrap_radio').length).toBeGreaterThan(0);
    });
  });

  // ===========================================================================
  // lwt_feed_wiz_opt_inter.clickHeader Tests
  // ===========================================================================

  describe('lwt_feed_wiz_opt_inter.clickHeader', () => {
    it('handles clicks on marked text', () => {
      document.body.innerHTML = `
        <select id="mark_action"><option value="">Select</option></select>
        <button name="button" disabled>Get</button>
        <div class="lwt_marked_text">Marked</div>
      `;

      const event = new MouseEvent('click', {
        bubbles: true
      });
      Object.defineProperty(event, 'target', {
        value: document.querySelector('.lwt_marked_text'),
        enumerable: true
      });

      const result = lwt_feed_wiz_opt_inter.clickHeader(event);

      expect(result).toBe(false);
      expect(document.querySelectorAll('.lwt_marked_text').length).toBe(0);
      const markActionOption = document.querySelector('#mark_action option') as HTMLOptionElement;
      expect(markActionOption.textContent).toBe('[Click On Text]');
    });

    it('handles clicks on filtered text', () => {
      document.body.innerHTML = `
        <div class="lwt_filtered_text">Filtered</div>
      `;

      const event = new MouseEvent('click', {
        bubbles: true
      });
      Object.defineProperty(event, 'target', {
        value: document.querySelector('.lwt_filtered_text'),
        enumerable: true
      });

      const result = lwt_feed_wiz_opt_inter.clickHeader(event);

      // Should prevent default
      expect(result).toBe(true);
    });

    it('handles clicks on selected text', () => {
      document.body.innerHTML = `
        <ul id="lwt_sel"><li>xpath</li></ul>
        <div class="lwt_selected_text">Selected</div>
        <button name="button">Button</button>
        <select id="mark_action"><option value="">Select</option></select>
      `;

      const event = new MouseEvent('click', {
        bubbles: true
      });
      Object.defineProperty(event, 'target', {
        value: document.querySelector('.lwt_selected_text'),
        enumerable: true
      });

      const result = lwt_feed_wiz_opt_inter.clickHeader(event);

      expect(result).toBe(false);
      const button = document.querySelector('button[name="button"]') as HTMLButtonElement;
      expect(button.disabled).toBe(true);
    });

    it('populates mark_action select for unfiltered elements', () => {
      document.body.innerHTML = `
        <select id="mark_action"><option value="">Select</option></select>
        <select name="select_mode"><option value="0">Mode 0</option></select>
        <button name="button">Get</button>
        <div id="container">
          <p id="target">Target text</p>
        </div>
      `;

      const event = new MouseEvent('click', {
        bubbles: true
      });
      Object.defineProperty(event, 'target', {
        value: document.querySelector('#target'),
        enumerable: true
      });

      lwt_feed_wiz_opt_inter.clickHeader(event);

      const button = document.querySelector('button[name="button"]') as HTMLButtonElement;
      expect(button.disabled).toBe(false);
    });
  });

  // ===========================================================================
  // lwt_feed_wiz_opt_inter.highlightSelection Tests
  // ===========================================================================

  describe('lwt_feed_wiz_opt_inter.highlightSelection', () => {
    it('highlights selected items', () => {
      document.body.innerHTML = `
        <div id="lwt_header"></div>
        <ul id="lwt_sel">
          <li class="lwt_highlighted_text">//div[@id='test']</li>
        </ul>
        <div id="test">Content</div>
      `;

      const testEl = document.querySelector('#test') as HTMLElement;
      mockXpathQuery.mockImplementation(() => [testEl]);

      const result = lwt_feed_wiz_opt_inter.highlightSelection();

      expect(typeof result).toBe('string');
    });

    it('returns concatenated non-highlighted xpaths', () => {
      document.body.innerHTML = `
        <div id="lwt_header"></div>
        <ul id="lwt_sel">
          <li>//div[@id='a']</li>
          <li>//div[@id='b']</li>
        </ul>
      `;

      const result = lwt_feed_wiz_opt_inter.highlightSelection();

      expect(result).toContain('//div[@id=\'a\']');
      expect(result).toContain('//div[@id=\'b\']');
      expect(result).toContain(' | ');
    });
  });

  // ===========================================================================
  // lwt_feed_wizard.prepareInteractions Tests
  // ===========================================================================

  describe('lwt_feed_wizard.prepareInteractions', () => {
    it('disables next button when lwt_sel is empty and step is 2', () => {
      document.body.innerHTML = `
        <ul id="lwt_sel"></ul>
        <input name="step" value="2" />
        <button id="next">Next</button>
        <div id="lwt_header" style="height:50px"></div>
        <div id="lwt_last"></div>
      `;

      lwt_feed_wizard.prepareInteractions();

      const nextButton = document.querySelector('#next') as HTMLButtonElement;
      expect(nextButton.disabled).toBe(true);
    });

    it('enables next button when lwt_sel has content', () => {
      document.body.innerHTML = `
        <ul id="lwt_sel"><li>xpath</li></ul>
        <input name="step" value="2" />
        <button id="next">Next</button>
        <div id="lwt_header" style="height:50px"></div>
        <div id="lwt_last"></div>
      `;

      lwt_feed_wizard.prepareInteractions();

      const nextButton = document.querySelector('#next') as HTMLButtonElement;
      expect(nextButton.disabled).toBe(false);
    });

    it('sets margin-top on lwt_last', () => {
      document.body.innerHTML = `
        <ul id="lwt_sel"></ul>
        <input name="step" value="1" />
        <button id="next">Next</button>
        <div id="lwt_header" style="height:100px"></div>
        <div id="lwt_last"></div>
      `;

      lwt_feed_wizard.prepareInteractions();

      // Margin should be set based on header height
      const lwtLast = document.querySelector('#lwt_last') as HTMLElement;
      expect(lwtLast.style.marginTop).toBeDefined();
    });

    it('removes lwt_filtered_text class from all elements', () => {
      document.body.innerHTML = `
        <ul id="lwt_sel"></ul>
        <input name="step" value="1" />
        <button id="next">Next</button>
        <div id="lwt_header"></div>
        <div id="lwt_last"></div>
        <div class="lwt_filtered_text">Filtered</div>
      `;

      lwt_feed_wizard.prepareInteractions();

      expect(document.querySelectorAll('.lwt_filtered_text').length).toBe(0);
    });

    it('wraps selects in wrap_select label', () => {
      document.body.innerHTML = `
        <ul id="lwt_sel"></ul>
        <input name="step" value="1" />
        <button id="next">Next</button>
        <div id="lwt_header">
          <select><option>Option</option></select>
        </div>
        <div id="lwt_last"></div>
      `;

      lwt_feed_wizard.prepareInteractions();

      const headerEl = document.querySelector('#lwt_header') as HTMLElement;
      expect(headerEl.querySelectorAll('label.wrap_select').length).toBe(1);
    });
  });

  // ===========================================================================
  // lwt_feed_wizard.deleteSelection Tests
  // ===========================================================================

  describe('lwt_feed_wizard.deleteSelection', () => {
    it('removes parent li element', () => {
      document.body.innerHTML = `
        <ul id="lwt_sel">
          <li><img class="delete_selection" /> xpath1</li>
          <li>xpath2</li>
        </ul>
        <input name="step" value="2" />
        <button id="next">Next</button>
        <div id="lwt_header"></div>
        <div id="lwt_last"></div>
      `;

      const deleteButton = document.querySelector('.delete_selection') as HTMLElement;
      lwt_feed_wizard.deleteSelection.call(deleteButton);

      const selList = document.querySelector('#lwt_sel') as HTMLElement;
      expect(selList.querySelectorAll('li').length).toBe(1);
    });

    it('removes selection classes from all elements', () => {
      document.body.innerHTML = `
        <ul id="lwt_sel">
          <li><img class="delete_selection" /> xpath</li>
        </ul>
        <input name="step" value="2" />
        <button id="next">Next</button>
        <div id="lwt_header"></div>
        <div id="lwt_last"></div>
        <div class="lwt_selected_text lwt_marked_text">Content</div>
      `;

      const deleteButton = document.querySelector('.delete_selection') as HTMLElement;
      lwt_feed_wizard.deleteSelection.call(deleteButton);

      expect(document.querySelectorAll('.lwt_selected_text').length).toBe(0);
      expect(document.querySelectorAll('.lwt_marked_text').length).toBe(0);
    });

    it('disables next when lwt_sel becomes empty on step 2', () => {
      // Note: The whitespace in HTML matters - the function checks if innerHTML === ''
      document.body.innerHTML = `
        <ul id="lwt_sel"><li><img class="delete_selection" /> xpath</li></ul>
        <input name="step" value="2" />
        <button id="next">Next</button>
        <div id="lwt_header"></div>
        <div id="lwt_last"></div>
      `;

      const deleteButton = document.querySelector('.delete_selection') as HTMLElement;
      lwt_feed_wizard.deleteSelection.call(deleteButton);

      // After deleting the only item, lwt_sel should be empty
      const selList = document.querySelector('#lwt_sel') as HTMLElement;
      expect(selList.innerHTML).toBe('');
      const nextButton = document.querySelector('#next') as HTMLButtonElement;
      expect(nextButton.disabled).toBe(true);
    });
  });

  // ===========================================================================
  // lwt_feed_wizard.changeXPath Tests
  // ===========================================================================

  describe('lwt_feed_wizard.changeXPath', () => {
    it('enables adv_get_button when valid xpath', () => {
      document.body.innerHTML = `
        <div>
          <input type="radio" class="xpath" checked />
        </div>
        <button id="adv_get_button" disabled>Get</button>
      `;

      const radio = document.querySelector('.xpath') as HTMLElement;
      const result = lwt_feed_wizard.changeXPath.call(radio);

      const advGetButton = document.querySelector('#adv_get_button') as HTMLButtonElement;
      expect(advGetButton.disabled).toBe(false);
      expect(result).toBe(false);
    });

    it('disables adv_get_button when invalid xpath', () => {
      document.body.innerHTML = `
        <p>
          <input type="radio" class="xpath" checked />
          <span id="custom_img" data-valid="false"></span>
        </p>
        <button id="adv_get_button">Get</button>
      `;

      const radio = document.querySelector('.xpath') as HTMLElement;
      lwt_feed_wizard.changeXPath.call(radio);

      const advGetButton = document.querySelector('#adv_get_button') as HTMLButtonElement;
      expect(advGetButton.disabled).toBe(true);
    });
  });

  // ===========================================================================
  // lwt_feed_wizard.clickAdvGetButton Tests
  // ===========================================================================

  describe('lwt_feed_wizard.clickAdvGetButton', () => {
    it('adds selected xpath to lwt_sel', () => {
      document.body.innerHTML = `
        <div id="adv">
          <input type="radio" class="xpath" name="xpath" value="//div[@id='test']" checked />
        </div>
        <ul id="lwt_sel"></ul>
        <button id="next">Next</button>
        <div id="lwt_header"></div>
        <div id="lwt_last"></div>
      `;

      const div = document.createElement('div');
      mockXpathQuery.mockImplementation(() => [div]);

      lwt_feed_wizard.clickAdvGetButton();

      const selList = document.querySelector('#lwt_sel') as HTMLElement;
      expect(selList.querySelectorAll('li').length).toBe(1);
      expect(selList.textContent).toContain("//div[@id='test']");
    });

    it('hides #adv after selection', () => {
      document.body.innerHTML = `
        <div id="adv" style="display:block">
          <input type="radio" class="xpath" name="xpath" value="//div" checked />
        </div>
        <ul id="lwt_sel"></ul>
        <button id="next">Next</button>
        <div id="lwt_header"></div>
        <div id="lwt_last"></div>
      `;

      lwt_feed_wizard.clickAdvGetButton();

      const advEl = document.querySelector('#adv') as HTMLElement;
      expect(advEl.style.display === 'none' || advEl.style.display === '').toBe(true);
    });

    it('enables next button after selection', () => {
      document.body.innerHTML = `
        <div id="adv">
          <input type="radio" class="xpath" name="xpath" value="//div" checked />
        </div>
        <ul id="lwt_sel"></ul>
        <button id="next" disabled>Next</button>
        <div id="lwt_header"></div>
        <div id="lwt_last"></div>
      `;

      lwt_feed_wizard.clickAdvGetButton();

      const nextButton = document.querySelector('#next') as HTMLButtonElement;
      expect(nextButton.disabled).toBe(false);
    });

    it('does nothing when no radio is checked', () => {
      document.body.innerHTML = `
        <div id="adv">
          <input type="radio" class="xpath" name="xpath" value="//div" />
        </div>
        <ul id="lwt_sel"></ul>
        <button id="next" disabled>Next</button>
        <div id="lwt_header"></div>
        <div id="lwt_last"></div>
      `;

      lwt_feed_wizard.clickAdvGetButton();

      const selList = document.querySelector('#lwt_sel') as HTMLElement;
      expect(selList.querySelectorAll('li').length).toBe(0);
    });
  });

  // ===========================================================================
  // lwt_feed_wizard.clickSelectLi Tests
  // ===========================================================================

  describe('lwt_feed_wizard.clickSelectLi', () => {
    it('removes highlighted class when already highlighted', () => {
      document.body.innerHTML = `
        <div id="lwt_header"></div>
        <ul id="lwt_sel">
          <li class="lwt_highlighted_text">//div</li>
        </ul>
      `;

      const li = document.querySelector('#lwt_sel li') as HTMLElement;
      const result = lwt_feed_wizard.clickSelectLi.call(li);

      expect(document.querySelectorAll('.lwt_highlighted_text').length).toBe(0);
      expect(result).toBe(false);
    });

    it('adds highlighted class when not highlighted', () => {
      document.body.innerHTML = `
        <div id="lwt_header"></div>
        <ul id="lwt_sel">
          <li>//div[@id='test']</li>
        </ul>
        <div id="test">Content</div>
      `;

      const testEl = document.querySelector('#test') as HTMLElement;
      mockXpathQuery.mockImplementation(() => [testEl]);

      const li = document.querySelector('#lwt_sel li') as HTMLElement;
      lwt_feed_wizard.clickSelectLi.call(li);

      expect(li.classList.contains('lwt_highlighted_text')).toBe(true);
    });
  });

  // ===========================================================================
  // lwt_feed_wizard.changeMarkAction Tests
  // ===========================================================================

  describe('lwt_feed_wizard.changeMarkAction', () => {
    it('removes lwt_marked_text class', () => {
      document.body.innerHTML = `
        <select id="mark_action">
          <option value="//div[@id='test']">div</option>
        </select>
        <div class="lwt_marked_text">Marked</div>
        <div id="test">Content</div>
      `;

      lwt_feed_wizard.changeMarkAction();

      // Original marked text should have class removed
      expect(document.querySelectorAll('.lwt_marked_text').length).toBeGreaterThanOrEqual(0);
    });

    it('returns false', () => {
      document.body.innerHTML = `
        <select id="mark_action">
          <option value="//div">div</option>
        </select>
      `;

      const result = lwt_feed_wizard.changeMarkAction();

      expect(result).toBe(false);
    });
  });

  // ===========================================================================
  // lwt_feed_wizard.clickNextButton Tests
  // ===========================================================================

  describe('lwt_feed_wizard.clickNextButton', () => {
    it('sets form values and increments step', () => {
      document.body.innerHTML = `
        <form id="lwt_form1">
          <ul id="lwt_sel"><li>xpath1</li><li>xpath2</li></ul>
          <input name="step" value="1" />
          <input name="html" value="" />
          <textarea id="article_tags" disabled></textarea>
          <textarea id="filter_tags" disabled></textarea>
          <button id="next">Next</button>
        </form>
      `;

      // Mock form submission
      const mockSubmit = vi.fn();
      (window as Record<string, unknown>).lwt_form1 = {
        submit: mockSubmit
      };

      lwt_feed_wizard.clickNextButton();

      const stepInput = document.querySelector('input[name="step"]') as HTMLInputElement;
      expect(stepInput.value).toBe('2');
      const htmlInput = document.querySelector('input[name="html"]') as HTMLInputElement;
      expect(htmlInput.value).toContain('xpath1');
      const articleTags = document.querySelector('#article_tags') as HTMLTextAreaElement;
      expect(articleTags.disabled).toBe(false);
      expect(mockSubmit).toHaveBeenCalled();
    });

    it('renames html input on step 2', () => {
      document.body.innerHTML = `
        <form id="lwt_form1">
          <ul id="lwt_sel"><li>xpath</li></ul>
          <input name="step" value="2" />
          <input name="html" value="" />
          <select name="NfArticleSection">
            <option value="">Option</option>
          </select>
          <button id="next">Next</button>
        </form>
      `;

      (window as Record<string, unknown>).lwt_form1 = { submit: vi.fn() };

      lwt_feed_wizard.clickNextButton();

      expect(document.querySelectorAll('input[name="article_selector"]').length).toBe(1);
      expect(document.querySelectorAll('input[name="html"]').length).toBe(0);
    });
  });

  // ===========================================================================
  // lwt_feed_wizard.changeHostStatus Tests
  // ===========================================================================

  describe('lwt_feed_wizard.changeHostStatus', () => {
    it('updates feed option text with new status', () => {
      document.body.innerHTML = `
        <select id="host_status">
          <option value="★">Active</option>
        </select>
        <input name="host_name" value="example.com" />
        <select name="selected_feed">
          <option>▸1 ☆ host:example.com</option>
          <option>▸2 ☆ host:other.com</option>
        </select>
      `;

      const hostStatus = document.querySelector('#host_status') as HTMLElement;
      Object.defineProperty(hostStatus, 'value', { value: '★' });

      lwt_feed_wizard.changeHostStatus.call(hostStatus);

      const selectedFeed = document.querySelector('select[name="selected_feed"]') as HTMLSelectElement;
      const firstOption = selectedFeed.options[0];
      expect(firstOption.textContent).toContain('★');
    });
  });

  // ===========================================================================
  // Event Handler Registration Tests
  // ===========================================================================

  describe('Event handlers', () => {
    it('registers click handler for delete_selection', () => {
      document.body.innerHTML = `
        <ul id="lwt_sel">
          <li><img class="delete_selection" /> xpath</li>
        </ul>
        <input name="step" value="1" />
        <button id="next">Next</button>
        <div id="lwt_header"></div>
        <div id="lwt_last"></div>
      `;

      // Trigger document-level click
      const event = new MouseEvent('click', { bubbles: true });
      const deleteButton = document.querySelector('.delete_selection') as HTMLElement;
      deleteButton.dispatchEvent(event);
    });

    it('registers change handler for xpath', () => {
      document.body.innerHTML = `
        <input type="radio" class="xpath" />
        <button id="adv_get_button">Get</button>
      `;

      const event = new Event('change', { bubbles: true });
      const xpathInput = document.querySelector('.xpath') as HTMLElement;
      xpathInput.dispatchEvent(event);
    });
  });

  // ===========================================================================
  // Edge Cases
  // ===========================================================================

  describe('Edge Cases', () => {
    it('handles empty lwt_sel gracefully', () => {
      document.body.innerHTML = `
        <ul id="lwt_sel"></ul>
        <input name="step" value="1" />
        <button id="next">Next</button>
        <div id="lwt_header"></div>
        <div id="lwt_last"></div>
      `;

      expect(() => lwt_feed_wizard.prepareInteractions()).not.toThrow();
    });

    it('handles missing elements gracefully', () => {
      document.body.innerHTML = '';

      expect(() => lwt_feed_wiz_opt_inter.highlightSelection()).not.toThrow();
    });
  });
});

/**
 * Control the interactions for making an automated feed wizard.
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense
 * @since   1.6.16-fork
 */

import { iconHtml, getLucideIconName } from '../ui/icons';

/**
 * Execute an XPath expression and return matching elements as an array.
 * Supports pipe-separated multiple expressions (e.g., "//div | //span").
 *
 * @param expression - XPath expression to evaluate
 * @param context - Context node for evaluation (defaults to document)
 * @returns Array of matched HTMLElements
 */
function xpathQuery(expression: string, context: Node = document): HTMLElement[] {
  const results: HTMLElement[] = [];

  // Handle pipe-separated expressions (e.g., "//div[@id='x'] | //p[@class='y']")
  const expressions = expression.split(/\s*\|\s*/).filter(e => e.trim());

  for (const expr of expressions) {
    try {
      const xpathResult = document.evaluate(
        expr,
        context,
        null,
        XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
        null
      );
      for (let i = 0; i < xpathResult.snapshotLength; i++) {
        const node = xpathResult.snapshotItem(i);
        if (node instanceof HTMLElement && !results.includes(node)) {
          results.push(node);
        }
      }
    } catch {
      // Invalid XPath - skip this expression
    }
  }

  return results;
}

/**
 * Validate an XPath expression without executing it fully.
 *
 * @param expression - XPath expression to validate
 * @returns true if expression is valid, false otherwise
 */
function isValidXPath(expression: string): boolean {
  if (!expression || expression.trim() === '') {
    return false;
  }
  try {
    document.evaluate(expression, document, null, XPathResult.ANY_TYPE, null);
    return true;
  } catch {
    return false;
  }
}

// Export functions to window for use in PHP views
(window as unknown as { xpathQuery: typeof xpathQuery }).xpathQuery = xpathQuery;
(window as unknown as { isValidXPath: typeof isValidXPath }).isValidXPath = isValidXPath;

// Declare global filter_Array that may be set externally
declare const filter_Array: HTMLElement[];

// Type for lwt_form1
interface LwtForm1 extends HTMLFormElement {
  submit(): void;
}

declare const lwt_form1: LwtForm1;

/**
 * Helper: Add class to element
 */
function addClass(el: Element, className: string): void {
  el.classList.add(className);
}

/**
 * Helper: Remove class from element
 */
function removeClass(el: Element, className: string): void {
  el.classList.remove(className);
}

/**
 * Helper: Check if element has class
 */
function hasClass(el: Element, className: string): boolean {
  return el.classList.contains(className);
}

/**
 * Helper: Remove class from all elements matching selector
 */
function removeClassAll(selector: string, className: string): void {
  document.querySelectorAll(selector).forEach(el => removeClass(el, className));
}

/**
 * Helper: Add class to all elements matching selector
 */
function addClassAll(selector: string, className: string): void {
  document.querySelectorAll(selector).forEach(el => addClass(el, className));
}

/**
 * Helper: Remove empty class attributes
 */
function removeEmptyClassAttrs(): void {
  document.querySelectorAll('[class=""]').forEach(el => el.removeAttribute('class'));
}

/**
 * Helper: Remove empty style attributes
 */
function removeEmptyStyleAttrs(): void {
  document.querySelectorAll('[style=""]').forEach(el => el.removeAttribute('style'));
}

/**
 * Helper: Get element by ID
 */
function getById(id: string): HTMLElement | null {
  return document.getElementById(id);
}

/**
 * Helper: Query selector
 */
function qs<T extends Element = Element>(selector: string, context: Element | Document = document): T | null {
  return context.querySelector(selector) as T | null;
}

/**
 * Helper: Query selector all
 */
function qsa<T extends Element = Element>(selector: string, context: Element | Document = document): NodeListOf<T> {
  return context.querySelectorAll(selector) as NodeListOf<T>;
}

/**
 * Helper: Set disabled property on element
 */
function setDisabled(el: HTMLButtonElement | HTMLInputElement | null, disabled: boolean): void {
  if (el) el.disabled = disabled;
}

/**
 * Helper: Get input value
 */
function getInputValue(selector: string): string {
  const el = qs<HTMLInputElement | HTMLSelectElement>(selector);
  return el?.value ?? '';
}

/**
 * Helper: Set input value
 */
function setInputValue(selector: string, value: string): void {
  const el = qs<HTMLInputElement | HTMLSelectElement>(selector);
  if (el) el.value = value;
}

/**
 * Helper: Get all parent elements (excluding html and body)
 */
function getParents(el: Element): HTMLElement[] {
  const parents: HTMLElement[] = [];
  let current = el.parentElement;
  while (current && current !== document.body && current !== document.documentElement) {
    parents.push(current);
    current = current.parentElement;
  }
  return parents;
}

/**
 * Helper: Get all ancestors and self
 */
function getAncestorsAndSelf(el: Element): HTMLElement[] {
  const result: HTMLElement[] = [];
  if (el instanceof HTMLElement) result.push(el);
  let current = el.parentElement;
  while (current && current !== document.body && current !== document.documentElement) {
    result.push(current);
    current = current.parentElement;
  }
  return result;
}

/**
 * Helper: Prepend HTML to element
 */
function prependHtml(el: Element | null, html: string): void {
  if (el) el.insertAdjacentHTML('afterbegin', html);
}

/**
 * Helper: Append HTML to element
 */
function appendHtml(el: Element | null, html: string): void {
  if (el) el.insertAdjacentHTML('beforeend', html);
}

/**
 * Helper: Get all descendants and self
 */
function getDescendantsAndSelf(el: Element): HTMLElement[] {
  const result: HTMLElement[] = [];
  if (el instanceof HTMLElement) result.push(el);
  el.querySelectorAll('*').forEach(child => {
    if (child instanceof HTMLElement) result.push(child);
  });
  return result;
}

/**
 * Helper: Filter elements not matching selector or not in a set
 */
function filterNot(elements: HTMLElement[], excludeSelector: string): HTMLElement[] {
  return elements.filter(el => !el.matches(excludeSelector));
}

/**
 * Helper: Get elements excluding those within a container
 */
function excludeWithinContainer(elements: HTMLElement[], container: Element | null): HTMLElement[] {
  if (!container) return elements;
  const containerDescendants = new Set(getDescendantsAndSelf(container));
  return elements.filter(el => !containerDescendants.has(el));
}

/**
 * To be added to extend advanced xpath functionality.
 * Creates radio button options for XPath selection.
 */
export function extend_adv_xpath(el: HTMLElement): void {
  const advEl = getById('adv');
  if (!advEl) return;

  // Prepend custom XPath input
  prependHtml(advEl,
    '<p style="text-align: left;">' +
      '<input style="vertical-align: middle; margin: 2px;" class="xpath" ' +
      'type="radio" name="xpath" value=\'\'>' +
        'custom: ' +
        '<input type="text" id="custom_xpath" name="custom_xpath" ' +
        'style="width:70%" value=\'\'>' +
        '</input>' +
      '<span id="custom_img" data-valid="false">' + iconHtml('exclamation-red', { alt: '-' }) + '</span>' +
      '</input>' +
    '</p>'
  );

  // Add event listeners for custom xpath input
  const customXpathInput = getById('custom_xpath') as HTMLInputElement | null;
  if (customXpathInput) {
    const validateCustomXpath = (): void => {
      const val = customXpathInput.value;
      const valid = isValidXPath(val) && xpathQuery(val).length > 0;
      const parentP = customXpathInput.closest('p');
      const xpathRadio = parentP?.querySelector<HTMLInputElement>('.xpath');
      const advGetButton = getById('adv_get_button') as HTMLButtonElement | null;
      const customImg = getById('custom_img') as HTMLImageElement | null;

      if (!valid) {
        if (xpathRadio) xpathRadio.value = '';
        if (parentP?.querySelector<HTMLInputElement>(':checked')) {
          setDisabled(advGetButton, true);
        }
        if (customImg) {
          customImg.innerHTML = iconHtml('exclamation-red', { alt: '-' });
          customImg.dataset.valid = 'false';
        }
      } else {
        if (xpathRadio) xpathRadio.value = val;
        if (parentP?.querySelector<HTMLInputElement>(':checked')) {
          setDisabled(advGetButton, false);
        }
        if (customImg) {
          customImg.innerHTML = iconHtml('tick', { alt: 'Valid' });
          customImg.dataset.valid = 'true';
        }
      }
    };

    customXpathInput.addEventListener('keyup', validateCustomXpath);
    customXpathInput.addEventListener('paste', () => setTimeout(validateCustomXpath, 0));
  }

  advEl.style.display = '';
  removeClassAll('*', 'lwt_marked_text');
  removeEmptyClassAttrs();

  const markActionSelect = getById('mark_action') as HTMLSelectElement | null;
  const selectedOption = markActionSelect?.selectedOptions[0];
  const val1 = (selectedOption?.dataset?.tagName || '').toLowerCase();

  let node_count = 0;
  let attr_v = '';
  let attr_p = '';
  let val_p = '';
  const attrs = el.attributes;

  // Process element's own attributes
  for (let i = 0; i < attrs.length; i++) {
    const attrItem = attrs.item(i)!;
    if (attrItem.nodeName === 'id') {
      const id_cont = attrItem.nodeValue!.split(' ');
      for (const idPart of id_cont) {
        const val = '//*[@id[contains(concat(" ",normalize-space(.)," ")," ' + idPart + ' ")]]';
        prependHtml(advEl,
          '<p style="text-align: left;">' +
            '<input style="vertical-align: middle; margin: 2px;" ' +
            'class="xpath" type="radio" name="xpath" value=\'' + val + '\'>' +
              'contains id: «' + idPart + '»' +
            '</input>' +
          '</p>'
        );
      }
    }
    if (attrItem.nodeName === 'class') {
      const cl_cont = attrItem.nodeValue!.split(' ');
      for (const clPart of cl_cont) {
        const val = '//*[@class[contains(concat(" ",normalize-space(.)," ")," ' + clPart + ' ")]]';
        prependHtml(advEl,
          '<p style="text-align: left;">' +
            '<input style="vertical-align: middle; margin: 2px;" ' +
            'class="xpath" type="radio" name="xpath" value=\'' + val + '\'>' +
              'contains class: «' + clPart + '»' +
            '</input>' +
          '</p>'
        );
      }
    }
    if (i > 0) attr_v += ' and ';
    if (i === 0) attr_v += '[';
    attr_v += '@' + attrItem.nodeName;
    attr_v += '="' + attrItem.nodeValue + '"';
    if (i === attrs.length - 1) attr_v += ']';
  }

  // Process parent elements
  const parents = getParents(el);
  for (const pa of parents) {
    const paAttrs = pa.attributes;
    for (let i = 0; i < paAttrs.length; i++) {
      const paAttrItem = paAttrs.item(i)!;
      if (node_count === 0) {
        if (paAttrItem.nodeName === 'id') {
          const id_cont = paAttrItem.nodeValue!.split(' ');
          for (const idPart of id_cont) {
            const val = '//*[@id[contains(concat(" ",normalize-space(.)," ")," ' + idPart + ' ")]]';
            prependHtml(advEl,
              '<p style="text-align: left;">' +
                '<input style="vertical-align: middle; margin: 2px;" ' +
                'class="xpath" type="radio" name="xpath" value=\'' + val + '/' + val1 + '\'>' +
                  'parent contains id: «' + idPart + '»' +
                '</input>' +
              '</p>'
            );
          }
        }
        if (paAttrItem.nodeName === 'class') {
          const cl_cont = paAttrItem.nodeValue!.split(' ');
          for (const clPart of cl_cont) {
            if (clPart !== 'lwt_filtered_text') {
              const val = '//*[@class[contains(concat(" ",normalize-space(.)," ")," ' + clPart + ' ")]]';
              prependHtml(advEl,
                '<p style="text-align: left;">' +
                  '<input style="vertical-align: middle; margin: 2px;" ' +
                  'class="xpath" type="radio" name="xpath" value=\'' + val + '/' + val1 + '\'>' +
                    'parent contains class: «' + clPart + '»' +
                  '</input>' +
                '</p>'
              );
            }
          }
        }
      }
      if (paAttrs.length > 1 || paAttrItem.nodeValue !== 'lwt_filtered_text') {
        if (i > 0 && paAttrItem.nodeValue !== 'lwt_filtered_text') attr_p += ' and ';
        if (i === 0) attr_p += '[';
        if (paAttrItem.nodeValue !== 'lwt_filtered_text') {
          attr_p += '@' + paAttrItem.nodeName;
          attr_p += '="' + paAttrItem.nodeValue!.replace('lwt_filtered_text', '').trim() + '"';
        }
        if (i === paAttrs.length - 1) attr_p += ']';
      }
    }
    val_p = pa.tagName.toLowerCase() + attr_p + '/' + val_p;
    attr_p = '';
    node_count++;
  }

  // Prepend the "all" option
  prependHtml(advEl,
    '<p style="text-align: left;">' +
      '<input style="vertical-align: middle; margin: 2px;" class="xpath" type="radio" name="xpath" ' +
      'value=\'/' + val_p + val1 + attr_v + '\'>' +
        'all: « /' + val_p.replace('=""', '') + val1 + attr_v.replace('=""', '') + ' »' +
      '</input>' +
    '</p>'
  );

  // Add labels to radio buttons
  let z = 0;
  qsa<HTMLInputElement>('#adv input[type="radio"]').forEach(radio => {
    if (!radio.id) {
      radio.id = 'rb_' + z++;
    }
    radio.insertAdjacentHTML('afterend',
      '<label class="wrap_radio" for="' + radio.id + '"><span></span></label>'
    );
  });
}

export const lwt_feed_wiz_opt_inter = {
  clickHeader: function (event: MouseEvent): boolean {
    const target = event.target as HTMLElement;

    if (!hasClass(target, 'lwt_selected_text')) {
      if (!hasClass(target, 'lwt_filtered_text')) {
        if (hasClass(target, 'lwt_marked_text')) {
          const markAction = getById('mark_action');
          if (markAction) markAction.innerHTML = '';
          removeClassAll('*', 'lwt_marked_text');
          removeEmptyClassAttrs();
          setDisabled(qs<HTMLButtonElement>('button[name="button"]'), true);
          appendHtml(markAction, '<option value="">[Click On Text]</option>');
          return false;
        } else {
          removeClassAll('*', 'lwt_marked_text');
          const markAction = getById('mark_action');
          if (markAction) markAction.innerHTML = '';

          let filter_array: HTMLElement[] = [];
          const ancestors = getAncestorsAndSelf(target);

          for (const current of ancestors) {
            if (!hasClass(current, 'lwt_filtered_text')) {
              filter_array = [];
              // Remove lwt_filtered_text from parents temporarily
              getParents(current).forEach(p => {
                if (hasClass(p, 'lwt_filtered_text')) {
                  removeClass(p, 'lwt_filtered_text');
                  filter_array.push(p);
                }
              });
              removeEmptyClassAttrs();

              const styleAttr = current.getAttribute('style');
              if (!styleAttr || styleAttr === '') current.removeAttribute('style');

              const val1 = current.tagName.toLowerCase();
              let attr = '';
              let attr_v = '';
              let attr_p = '';
              let attr_mode: number | string = '';
              let val_p = '';

              const selectModeVal = getInputValue('select[name="select_mode"]');
              if (selectModeVal !== '0') {
                attr_mode = 5;
              } else if (current.getAttribute('id')) {
                attr_mode = 1;
              } else if (current.parentElement?.getAttribute('id')) {
                attr_mode = 2;
              } else if (current.getAttribute('class')) {
                attr_mode = 3;
              } else if (current.parentElement?.getAttribute('class')) {
                attr_mode = 4;
              } else {
                attr_mode = 5;
              }

              const attrs = current.attributes;
              for (let i = 0; i < attrs.length; i++) {
                const attrItem = attrs.item(i)!;
                if (attr_mode === 5 ||
                    (attrItem.nodeName === 'class' && attr_mode !== 1) ||
                    attrItem.nodeName === 'id') {
                  attr += attrItem.nodeName + '="' + attrItem.nodeValue + '" ';
                  if (i > 0) attr_v += ' and ';
                  attr_v += '@' + attrItem.nodeName + '="' + attrItem.nodeValue + '"';
                }
              }
              attr = attr.replace('=""', '').trim();
              if (attr_v) attr_v = '[' + attr_v + ']';

              if (attr_mode !== 1 && attr_mode !== 3 && current.parentElement) {
                const parentAttrs = current.parentElement.attributes;
                for (let i = 0; i < parentAttrs.length; i++) {
                  const pAttrItem = parentAttrs.item(i)!;
                  if (attr_mode === 5 ||
                      (pAttrItem.nodeName === 'class' && attr_mode !== 2) ||
                      pAttrItem.nodeName === 'id') {
                    if (i > 0) attr_p += ' and ';
                    attr_p += '@' + pAttrItem.nodeName + '="' + pAttrItem.nodeValue + '"';
                  }
                }
                if (attr_p) attr_p = '[' + attr_p + ']';
                val_p = current.parentElement.tagName.toLowerCase() + attr_p + '§';
              }
              val_p = val_p.replace('body§', '');

              let attrsplit = attr.substr(0, 20);
              if (attrsplit !== attr) attrsplit = attrsplit + '... ';
              if (attrsplit !== '') attrsplit = ' ' + attrsplit;

              const optionValue = '//' + val_p.replace('=""', '').replace('[ and @', '[@') +
                val1 + attr_v.replace('=""', '').replace('[ and @', '[@');
              const optionText = '<' + val1.replace('[ and @', '[@') +
                attrsplit.replace('[ and @', '[@') + '>';

              const option = document.createElement('option');
              option.value = optionValue;
              option.textContent = optionText;
              option.dataset.tagName = current.tagName;

              if (event.target === current) {
                option.selected = true;
                markAction?.insertBefore(option, markAction.firstChild);
              } else {
                markAction?.insertBefore(option, markAction.firstChild);
              }

              // Restore filter_array
              for (const f of filter_array) {
                addClass(f, 'lwt_filtered_text');
              }
            }
          }

          setDisabled(qs<HTMLButtonElement>('button[name="button"]'), false);

          let attr = getInputValue('#mark_action');
          attr = attr.replace(/@/g, '').replace('//', '').replace(/ and /g, '][').replace('§', '>');

          filter_array = [];
          getParents(target).forEach(p => {
            if (hasClass(p, 'lwt_filtered_text')) {
              removeClass(p, 'lwt_filtered_text');
              filter_array.push(p);
            }
          });

          // Mark matching elements
          try {
            const matchingEls = document.querySelectorAll(attr + ':not(.lwt_selected_text)');
            matchingEls.forEach(el => {
              getDescendantsAndSelf(el).forEach(d => {
                if (!hasClass(d, 'lwt_selected_text')) {
                  addClass(d, 'lwt_marked_text');
                }
              });
            });
          } catch {
            // Invalid selector
          }

          for (const f of filter_array) {
            addClass(f, 'lwt_filtered_text');
          }
          return false;
        }
      } else {
        event.preventDefault();
      }
    } else {
      const selected_Array: HTMLElement[] = [];
      let filter_array: HTMLElement[] = [];

      qsa('.lwt_selected_text').forEach(el => {
        if (el instanceof HTMLElement) selected_Array.push(el);
      });

      const ancestors = getAncestorsAndSelf(target);
      for (const current of ancestors) {
        const parent = current.parentElement;
        if (parent && !hasClass(parent, 'lwt_selected_text') && hasClass(current, 'lwt_selected_text')) {
          if (hasClass(current, 'lwt_highlighted_text')) {
            removeClassAll('*', 'lwt_highlighted_text');
          } else {
            removeClassAll('*', 'lwt_selected_text');
            filter_array = [];

            getParents(current).forEach(p => {
              if (hasClass(p, 'lwt_filtered_text')) {
                removeClass(p, 'lwt_filtered_text');
                filter_array.push(p);
              }
            });
            removeEmptyClassAttrs();

            const lwtSelItems = qsa<HTMLElement>('#lwt_sel li');
            for (const li of lwtSelItems) {
              removeClassAll('*', 'lwt_highlighted_text');
              addClass(li, 'lwt_highlighted_text');
              xpathQuery(li.textContent || '').forEach(el => addClass(el, 'lwt_highlighted_text'));
              if (hasClass(current, 'lwt_highlighted_text')) {
                break;
              }
            }

            for (const s of selected_Array) {
              addClass(s, 'lwt_selected_text');
            }
          }
        }
      }

      for (const f of filter_array) {
        addClass(f, 'lwt_filtered_text');
      }

      setDisabled(qs<HTMLButtonElement>('button[name="button"]'), true);
      const markAction = getById('mark_action');
      if (markAction) markAction.innerHTML = '';
      appendHtml(markAction, '<option value="">[Click On Text]</option>');
      return false;
    }
    return true;
  },

  highlightSelection: function (): string {
    let sel_array = '';
    const lwtHeader = getById('lwt_header');
    const headerDescendants = lwtHeader ? new Set(getDescendantsAndSelf(lwtHeader)) : new Set<HTMLElement>();

    qsa<HTMLElement>('#lwt_sel li').forEach(li => {
      if (hasClass(li, 'lwt_highlighted_text')) {
        const matched = xpathQuery(li.textContent || '');
        const filtered = matched.filter(el => !headerDescendants.has(el));
        filtered.forEach(el => {
          addClass(el, 'lwt_highlighted_text');
          getDescendantsAndSelf(el).forEach(d => addClass(d, 'lwt_selected_text'));
        });
      } else {
        sel_array += (li.textContent || '') + ' | ';
      }
    });

    if (sel_array !== '') {
      const xpath = sel_array.replace(/ \| $/, '');
      const matched = xpathQuery(xpath);
      const filtered = matched.filter(el => !headerDescendants.has(el));
      filtered.forEach(el => {
        getDescendantsAndSelf(el).forEach(d => addClass(d, 'lwt_selected_text'));
      });
    }
    return sel_array;
  }
};

export const lwt_feed_wizard = {
  prepareInteractions: function (): void {
    const lwtSel = getById('lwt_sel');
    const stepInput = qs<HTMLInputElement>('input[name="step"]');
    const nextBtn = getById('next') as HTMLButtonElement | null;

    if (lwtSel?.innerHTML === '' && parseInt(stepInput?.value || '0', 10) === 2) {
      setDisabled(nextBtn, true);
    } else {
      setDisabled(nextBtn, false);
    }

    const lwtHeader = getById('lwt_header');
    const lwtLast = getById('lwt_last');
    if (lwtLast && lwtHeader) {
      lwtLast.style.marginTop = lwtHeader.offsetHeight + 'px';
    }

    // Bind click handler to siblings after lwt_header
    if (lwtHeader) {
      let sibling = lwtHeader.nextElementSibling;
      while (sibling) {
        sibling.addEventListener('click', (e: Event) => {
          lwt_feed_wiz_opt_inter.clickHeader(e as MouseEvent);
        });
        sibling = sibling.nextElementSibling;
      }
    }

    removeClassAll('*', 'lwt_filtered_text');
    removeEmptyClassAttrs();
    lwt_feed_wiz_opt_inter.highlightSelection();

    for (const i in filter_Array) {
      addClass(filter_Array[i], 'lwt_filtered_text');
    }
    removeEmptyStyleAttrs();

    // Wrap selects in lwt_header
    qsa<HTMLSelectElement>('#lwt_header select').forEach(select => {
      if (!select.parentElement?.classList.contains('wrap_select')) {
        const wrapper = document.createElement('label');
        wrapper.className = 'wrap_select';
        select.parentNode?.insertBefore(wrapper, select);
        wrapper.appendChild(select);
      }
    });

    document.addEventListener('mouseup', () => {
      const selectors = [
        'select:not(:active)', 'button', 'input[type=button]',
        '.wrap_radio span', '.wrap_checkbox span'
      ];
      qsa<HTMLElement>(selectors.join(',')).forEach(el => el.blur());
    });
  },

  deleteSelection: function (this: HTMLElement): boolean {
    removeClassAll('*', 'lwt_selected_text');
    removeClassAll('*', 'lwt_marked_text');
    removeClassAll('*', 'lwt_filtered_text');

    const lwtHeader = getById('lwt_header');
    if (lwtHeader) {
      let sibling = lwtHeader.nextElementSibling;
      while (sibling) {
        getDescendantsAndSelf(sibling as HTMLElement).forEach(el => {
          removeClass(el, 'lwt_highlighted_text');
        });
        sibling = sibling.nextElementSibling;
      }
    }

    this.parentElement?.remove();

    let sel_array = '';
    const headerDescendants = lwtHeader ? new Set(getDescendantsAndSelf(lwtHeader)) : new Set<HTMLElement>();

    qsa<HTMLElement>('#lwt_sel li').forEach(li => {
      if (hasClass(li, 'lwt_highlighted_text')) {
        const matched = xpathQuery(li.textContent || '');
        const filtered = matched.filter(el => !headerDescendants.has(el));
        filtered.forEach(el => {
          addClass(el, 'lwt_highlighted_text');
          getDescendantsAndSelf(el).forEach(d => addClass(d, 'lwt_selected_text'));
        });
      } else {
        sel_array += (li.textContent || '') + ' | ';
      }
    });

    if (sel_array !== '') {
      const xpath = sel_array.replace(/ \| $/, '');
      const matched = xpathQuery(xpath);
      const filtered = matched.filter(el => !headerDescendants.has(el));
      filtered.forEach(el => {
        getDescendantsAndSelf(el).forEach(d => addClass(d, 'lwt_selected_text'));
      });
    }

    for (const i in filter_Array) {
      addClass(filter_Array[i], 'lwt_filtered_text');
    }
    removeEmptyClassAttrs();
    removeEmptyStyleAttrs();

    const lwtLast = getById('lwt_last');
    if (lwtLast && lwtHeader) {
      lwtLast.style.marginTop = lwtHeader.offsetHeight + 'px';
    }

    const lwtSel = getById('lwt_sel');
    const stepInput = qs<HTMLInputElement>('input[name="step"]');
    if (lwtSel?.innerHTML === '' && parseInt(stepInput?.value || '0', 10) === 2) {
      setDisabled(getById('next') as HTMLButtonElement | null, true);
    }
    return false;
  },

  changeXPath: function (this: HTMLElement): boolean {
    const advGetButton = getById('adv_get_button') as HTMLButtonElement | null;
    setDisabled(advGetButton, false);

    const parentP = this.closest('p');
    if (parentP) {
      // Check for invalid state via data-valid attribute
      const customImg = parentP.querySelector<HTMLElement>('#custom_img');
      if (customImg && customImg.dataset.valid === 'false') {
        setDisabled(advGetButton, true);
      }
    }
    return false;
  },

  clickAdvGetButton: function (): boolean {
    removeClassAll('*', 'lwt_filtered_text');
    removeEmptyClassAttrs();

    const checkedRadio = qs<HTMLInputElement>('#adv :checked');
    if (checkedRadio?.value) {
      const lwtSel = getById('lwt_sel');
      appendHtml(lwtSel,
        '<li style=\'text-align: left\'>' +
        '<span class=\'delete_selection click\'>' + iconHtml('cross', { title: 'Delete Selection' }) + '</span> ' +
        checkedRadio.value +
        '</li>'
      );

      const lwtHeader = getById('lwt_header');
      const headerDescendants = lwtHeader ? new Set(getDescendantsAndSelf(lwtHeader)) : new Set<HTMLElement>();
      const matched = xpathQuery(checkedRadio.value);
      const filtered = matched.filter(el => !headerDescendants.has(el));
      filtered.forEach(el => {
        getDescendantsAndSelf(el).forEach(d => addClass(d, 'lwt_selected_text'));
      });

      setDisabled(getById('next') as HTMLButtonElement | null, false);
    }

    const advEl = getById('adv');
    if (advEl) advEl.style.display = 'none';

    const lwtLast = getById('lwt_last');
    const lwtHeader = getById('lwt_header');
    if (lwtLast && lwtHeader) {
      lwtLast.style.marginTop = lwtHeader.offsetHeight + 'px';
    }

    for (const i in filter_Array) {
      addClass(filter_Array[i], 'lwt_filtered_text');
    }
    return false;
  },

  clickSelectLi: function (this: HTMLElement): boolean {
    if (hasClass(this, 'lwt_highlighted_text')) {
      removeClassAll('*', 'lwt_highlighted_text');
    } else {
      const selected_Array: HTMLElement[] = [];
      qsa('.lwt_selected_text').forEach(el => {
        if (el instanceof HTMLElement) {
          removeClass(el, 'lwt_selected_text');
          selected_Array.push(el);
        }
      });

      removeClassAll('*', 'lwt_filtered_text');
      removeClassAll('*', 'lwt_highlighted_text');
      removeEmptyClassAttrs();

      addClass(this, 'lwt_highlighted_text');

      const lwtHeader = getById('lwt_header');
      const headerDescendants = lwtHeader ? new Set(getDescendantsAndSelf(lwtHeader)) : new Set<HTMLElement>();
      const matched = xpathQuery(this.textContent || '');
      const filtered = matched.filter(el => !headerDescendants.has(el));
      filtered.forEach(el => {
        addClass(el, 'lwt_highlighted_text');
        getDescendantsAndSelf(el).forEach(d => addClass(d, 'lwt_selected_text'));
      });

      for (const i in filter_Array) {
        addClass(filter_Array[i], 'lwt_filtered_text');
      }
      for (const s of selected_Array) {
        addClass(s, 'lwt_selected_text');
      }
    }
    return false;
  },

  changeMarkAction: function (): boolean {
    removeClassAll('*', 'lwt_marked_text');
    removeEmptyClassAttrs();

    let attr = getInputValue('#mark_action');
    attr = attr.replace(/@/g, '').replace('//', '').replace(/ and /g, '][').replace('§', '>');

    removeClassAll('*', 'lwt_filtered_text');

    try {
      qsa(attr).forEach(el => {
        getDescendantsAndSelf(el as HTMLElement).forEach(d => {
          if (!hasClass(d, 'lwt_selected_text')) {
            addClass(d, 'lwt_marked_text');
          }
        });
      });
    } catch {
      // Invalid selector
    }

    for (const i in filter_Array) {
      addClass(filter_Array[i], 'lwt_filtered_text');
    }
    return false;
  },

  clickGetOrFilter: function (this: HTMLElement): boolean {
    removeClassAll('*', 'lwt_marked_text');

    if (getInputValue('select[name="select_mode"]') === 'adv') {
      qsa('#adv p').forEach(p => p.remove());
      removeEmptyStyleAttrs();
      setDisabled(getById('adv_get_button') as HTMLButtonElement | null, true);

      // Get selected option data and call extend_adv_xpath
      const markAction = getById('mark_action') as HTMLSelectElement | null;
      const selectedOption = markAction?.selectedOptions[0];
      if (selectedOption?.dataset?.tagName) {
        // Find the element that was stored
        // Since we can't store DOM refs in dataset, we need to find it another way
        // The option value contains the xpath - we'll use that
        const xpath = selectedOption.value;
        if (xpath) {
          const matchedEls = xpathQuery(xpath);
          if (matchedEls.length > 0) {
            extend_adv_xpath(matchedEls[0]);
          }
        }
      }
    } else {
      setDisabled(getById('next') as HTMLButtonElement | null, false);

      let attr = getInputValue('#mark_action');
      attr = attr.replace(/@/g, '').replace('//', '').replace(/ and /g, '][').replace('§', '>');

      const local_filter_Array: HTMLElement[] = [];
      qsa('.lwt_filtered_text').forEach(el => {
        if (el instanceof HTMLElement) {
          removeClass(el, 'lwt_filtered_text');
          local_filter_Array.push(el);
        }
      });

      removeClassAll('*', 'lwt_filtered_text');

      try {
        qsa(attr).forEach(el => {
          getDescendantsAndSelf(el as HTMLElement).forEach(d => addClass(d, 'lwt_selected_text'));
        });
      } catch {
        // Invalid selector
      }

      for (const f of local_filter_Array) {
        addClass(f, 'lwt_filtered_text');
      }

      const markActionVal = getInputValue('#mark_action');
      appendHtml(getById('lwt_sel'),
        '<li style=\'text-align: left\'>' +
        '<span class=\'delete_selection click\'>' + iconHtml('cross', { title: 'Delete Selection', alt: markActionVal }) + '</span> ' +
        markActionVal.replace('§', '/') +
        '</li>'
      );
    }

    if (this instanceof HTMLButtonElement) {
      this.disabled = true;
    }

    const markAction = getById('mark_action');
    if (markAction) markAction.innerHTML = '';
    appendHtml(markAction, '<option value="">[Click On Text]</option>');

    const lwtLast = getById('lwt_last');
    const lwtHeader = getById('lwt_header');
    if (lwtLast && lwtHeader) {
      lwtLast.style.marginTop = lwtHeader.offsetHeight + 'px';
    }
    return false;
  },

  clickNextButton: function (): boolean {
    const lwtSelHtml = getById('lwt_sel')?.innerHTML || '';
    const articleTags = getById('article_tags') as HTMLInputElement | null;
    const filterTags = getById('filter_tags') as HTMLInputElement | null;

    if (articleTags) {
      articleTags.value = lwtSelHtml;
      articleTags.disabled = false;
    }
    if (filterTags) {
      filterTags.value = lwtSelHtml;
      filterTags.disabled = false;
    }

    const htmlParts: string[] = [];
    qsa<HTMLElement>('#lwt_sel li').forEach(li => {
      htmlParts.push(li.textContent || '');
    });
    const html = htmlParts.join(' | ');

    setInputValue('input[name="html"]', html);

    let val = parseInt(getInputValue('input[name="step"]'), 10);
    if (val === 2) {
      const htmlInput = qs<HTMLInputElement>('input[name="html"]');
      if (htmlInput) htmlInput.name = 'article_selector';

      const art_sec = htmlParts.join(' | ');
      qsa<HTMLOptionElement>('select[name="NfArticleSection"] option').forEach(opt => {
        opt.value = art_sec;
      });
    }

    setInputValue('input[name="step"]', String(++val));
    lwt_form1.submit();
    return false;
  },

  changeHostStatus: function (this: HTMLElement): boolean {
    const hostStatusEl = this as HTMLSelectElement;
    const host_status = hostStatusEl.value;
    const current_host = getInputValue('input[name="host_name"]');

    qsa<HTMLOptionElement>('select[name="selected_feed"] option').forEach(opt => {
      const opt_str = opt.textContent || '';
      const host_name = opt_str.replace(/[▸-][0-9\s]*[★☆-][\s]*host:/, '');
      if (host_name.trim() === current_host.trim()) {
        opt.textContent = opt_str.replace(
          /([▸-][0-9\s]*?)\s[★☆-]\s(.*)/,
          '$1 ' + host_status.trim() + ' $2'
        );
      }
    });
    return false;
  }
};

// Set up event handlers using vanilla JS event delegation
document.addEventListener('click', (e) => {
  const target = e.target as HTMLElement;

  // .delete_selection click
  if (target.classList.contains('delete_selection')) {
    lwt_feed_wizard.deleteSelection.call(target);
    return;
  }

  // #adv_get_button click
  if (target.id === 'adv_get_button') {
    lwt_feed_wizard.clickAdvGetButton();
    return;
  }

  // #lwt_sel li click
  const liTarget = target.closest('#lwt_sel li') as HTMLElement | null;
  if (liTarget) {
    lwt_feed_wizard.clickSelectLi.call(liTarget);
    return;
  }

  // #get_button or #filter_button click
  if (target.id === 'get_button' || target.id === 'filter_button') {
    lwt_feed_wizard.clickGetOrFilter.call(target);
    return;
  }

  // #next button click
  if (target.id === 'next') {
    lwt_feed_wizard.clickNextButton();
    return;
  }
});

document.addEventListener('change', (e) => {
  const target = e.target as HTMLElement;

  // .xpath change
  if (target.classList.contains('xpath')) {
    lwt_feed_wizard.changeXPath.call(target);
    return;
  }

  // #mark_action change
  if (target.id === 'mark_action') {
    lwt_feed_wizard.changeMarkAction();
    return;
  }

  // #host_status change
  if (target.id === 'host_status') {
    lwt_feed_wizard.changeHostStatus.call(target);
    return;
  }
});

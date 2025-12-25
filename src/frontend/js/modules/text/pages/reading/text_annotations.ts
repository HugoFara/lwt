/**
 * Annotation processing for text reading.
 * Handles adding annotations to words and multi-words during text display.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { make_tooltip } from '../terms/word_status';
import { getAnnotation } from '../core/text_config';
import { getDelimiter } from '../core/language_config';

/**
 * Helper to safely get an HTML attribute value as a string.
 *
 * @param el HTML element to get attribute from
 * @param attr Name of the attribute to retrieve
 * @returns Attribute value as string, or empty string if null
 */
export function getAttr(el: HTMLElement, attr: string): string {
  return el.getAttribute(attr) || '';
}

/**
 * Helper to safely get an HTML attribute value as a string from a native element.
 * (Alias for getAttr for backward compatibility)
 *
 * @param el HTML element to get attribute from
 * @param attr Name of the attribute to retrieve
 * @returns Attribute value as string, or empty string if null
 */
export function getAttrElement(el: HTMLElement, attr: string): string {
  return el.getAttribute(attr) || '';
}

/**
 * Add annotations to a word.
 */
export function word_each_do_text_text(
  this: HTMLElement
): void {
  const wid = getAttr(this, 'data_wid');
  if (wid !== '') {
    const order = getAttr(this, 'data_order');
    const annotation = getAnnotation(order);
    if (annotation) {
      if (wid === annotation[1]) {
        const ann = annotation[2];
        const delimiter = getDelimiter();
        const re = new RegExp(
          '([' + delimiter + '][ ]{0,1}|^)(' +
            ann.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' +
            delimiter + '])',
          ''
        );
        const dataTrans = getAttr(this, 'data_trans');
        if (!re.test(dataTrans.replace(/ \[.*$/, ''))) {
          const trans = ann + ' / ' + dataTrans;
          this.setAttribute('data_trans', trans.replace(' / *', ''));
        }
        this.setAttribute('data_ann', ann);
      }
    }
  }
  // Native tooltips are always used (jQuery tooltips removed)
  this.title = make_tooltip(
    this.textContent || '',
    getAttr(this, 'data_trans'),
    getAttr(this, 'data_rom'),
    getAttr(this, 'data_status') || '0'
  );
}

/**
 * Process multi-word expressions in text and update their annotations.
 * Checks for matching word IDs in nearby annotations and combines translations.
 *
 * @param this The HTML element being processed (word span)
 */
export function mword_each_do_text_text(
  this: HTMLElement
): void {
  if (getAttr(this, 'data_status') !== '') {
    const wid = getAttr(this, 'data_wid');
    if (wid !== '') {
      const order = parseInt(getAttr(this, 'data_order') || '0', 10);
      const delimiter = getDelimiter();
      for (let j = 2; j <= 16; j = j + 2) {
        const index = (order + j).toString();
        const annotation = getAnnotation(index);
        if (annotation) {
          if (wid === annotation[1]) {
            const ann = annotation[2];
            const re = new RegExp(
              '([' + delimiter + '][ ]{0,1}|^)(' +
                ann.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' +
                delimiter + '])',
              ''
            );
            const dataTrans = getAttr(this, 'data_trans');
            if (!re.test(dataTrans.replace(/ \[.*$/, ''))) {
              const trans = ann + ' / ' + dataTrans;
              this.setAttribute('data_trans', trans.replace(' / *', ''));
            }
            this.setAttribute('data_ann', ann);
            break;
          }
        }
      }
    }
    // Native tooltips are always used (jQuery tooltips removed)
    this.title = make_tooltip(
      getAttr(this, 'data_text'),
      getAttr(this, 'data_trans'),
      getAttr(this, 'data_rom'),
      getAttr(this, 'data_status') || '0'
    );
  }
}


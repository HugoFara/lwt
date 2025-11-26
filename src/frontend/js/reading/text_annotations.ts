/**
 * Annotation processing for text reading.
 * Handles adding annotations to words and multi-words during text display.
 *
 * @license Unlicense <http://unlicense.org/>
 */

import { make_tooltip } from '../terms/word_status';

/**
 * Helper to safely get an HTML attribute value as a string.
 *
 * @param $el jQuery element to get attribute from
 * @param attr Name of the attribute to retrieve
 * @returns Attribute value as string, or empty string if undefined
 */
export function getAttr($el: JQuery, attr: string): string {
  const val = $el.attr(attr);
  return typeof val === 'string' ? val : '';
}

// Type definitions
interface LwtLanguage {
  id: number;
  dict_link1: string;
  dict_link2: string;
  translator_link: string;
  delimiter: string;
  rtl: boolean;
}

interface LwtText {
  id: number;
  reading_position: number;
  annotations: Record<string, [unknown, string, string]>;
}

interface LwtSettings {
  jQuery_tooltip: boolean;
  hts: number;
  word_status_filter: string;
  annotations_mode: number;
}

interface LwtDataGlobal {
  language: LwtLanguage;
  text: LwtText;
  settings: LwtSettings;
}

declare const LWT_DATA: LwtDataGlobal;

/**
 * Add annotations to a word.
 *
 * @param _ Unused, usually word number
 */
export function word_each_do_text_text(this: HTMLElement, _: number): void {
  const $this = $(this);
  const wid = getAttr($this, 'data_wid');
  if (wid !== '') {
    const order = getAttr($this, 'data_order');
    if (order in LWT_DATA.text.annotations) {
      if (wid === LWT_DATA.text.annotations[order][1]) {
        const ann = LWT_DATA.text.annotations[order][2];
        const re = new RegExp(
          '([' + LWT_DATA.language.delimiter + '][ ]{0,1}|^)(' +
            ann.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' +
            LWT_DATA.language.delimiter + '])',
          ''
        );
        const dataTrans = getAttr($this, 'data_trans');
        if (!re.test(dataTrans.replace(/ \[.*$/, ''))) {
          const trans = ann + ' / ' + dataTrans;
          $this.attr('data_trans', trans.replace(' / *', ''));
        }
        $this.attr('data_ann', ann);
      }
    }
  }
  if (!LWT_DATA.settings.jQuery_tooltip) {
    $this.prop(
      'title',
      make_tooltip(
        $this.text(),
        getAttr($this, 'data_trans'),
        getAttr($this, 'data_rom'),
        getAttr($this, 'data_status') || '0'
      )
    );
  }
}

/**
 * Process multi-word expressions in text and update their annotations.
 * Checks for matching word IDs in nearby annotations and combines translations.
 *
 * @param this The HTML element being processed (word span)
 * @param _ Unused iteration index parameter
 */
export function mword_each_do_text_text(this: HTMLElement, _: number): void {
  const $this = $(this);
  if (getAttr($this, 'data_status') !== '') {
    const wid = getAttr($this, 'data_wid');
    if (wid !== '') {
      const order = parseInt(getAttr($this, 'data_order') || '0', 10);
      for (let j = 2; j <= 16; j = j + 2) {
        const index = (order + j).toString();
        if (index in LWT_DATA.text.annotations) {
          if (wid === LWT_DATA.text.annotations[index][1]) {
            const ann = LWT_DATA.text.annotations[index][2];
            const re = new RegExp(
              '([' + LWT_DATA.language.delimiter + '][ ]{0,1}|^)(' +
                ann.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&') + ')($|[ ]{0,1}[' +
                LWT_DATA.language.delimiter + '])',
              ''
            );
            const dataTrans = getAttr($this, 'data_trans');
            if (!re.test(dataTrans.replace(/ \[.*$/, ''))) {
              const trans = ann + ' / ' + dataTrans;
              $this.attr('data_trans', trans.replace(' / *', ''));
            }
            $this.attr('data_ann', ann);
            break;
          }
        }
      }
    }
    if (!LWT_DATA.settings.jQuery_tooltip) {
      $this.prop(
        'title',
        make_tooltip(
          getAttr($this, 'data_text'),
          getAttr($this, 'data_trans'),
          getAttr($this, 'data_rom'),
          getAttr($this, 'data_status') || '0'
        )
      );
    }
  }
}


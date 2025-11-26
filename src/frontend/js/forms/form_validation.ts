/**
 * Form Validation - Input validation and form checking utilities
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

import $ from 'jquery';

/**
 * Helper to safely get an HTML attribute value as a string.
 *
 * @param $el jQuery element to get attribute from
 * @param attr Name of the attribute to retrieve
 * @returns Attribute value as string, or empty string if undefined
 */
function getAttr($el: JQuery, attr: string): string {
  const val = $el.attr(attr);
  return typeof val === 'string' ? val : '';
}

/**
 * Helper to safely get jQuery element value as a string.
 *
 * @param $el jQuery element to get value from
 * @returns Element value as string, or empty string if undefined
 */
function getVal($el: JQuery): string {
  const val = $el.val();
  return typeof val === 'string' ? val : '';
}

/**
 * Return whether characters are outside the multilingual plane.
 *
 * @param s Input string
 * @returns true is some characters are outside the plane
 */
export function containsCharacterOutsideBasicMultilingualPlane(s: string): boolean {
  return /[\uD800-\uDFFF]/.test(s);
}

/**
 * Alert if characters are outside the multilingual plane.
 *
 * @param s Input string
 * @param info Info about the field
 * @returns 1 if characters are outside the plane, 0 otherwise
 */
export function alertFirstCharacterOutsideBasicMultilingualPlane(s: string, info: string): number {
  if (!containsCharacterOutsideBasicMultilingualPlane(s)) {
    return 0;
  }
  const match = /[\uD800-\uDFFF]/.exec(s);
  if (!match) return 0;
  alert(
    'ERROR\n\nText "' + info + '" contains invalid character(s) ' +
    '(in the Unicode Supplementary Multilingual Planes, > U+FFFF) like emojis ' +
    'or very rare characters.\n\nFirst invalid character: "' +
    s.substring(match.index, match.index + 2) + '" at position ' +
    (match.index + 1) + '.\n\n' +
    'More info: https://en.wikipedia.org/wiki/Plane_(Unicode)\n\n' +
    'Please remove this/these character(s) and try again.'
  );
  return 1;
}

/**
 * Return the memory size of an UTF8 string.
 *
 * @param s String to evaluate
 * @returns Size in bytes
 */
export function getUTF8Length(s: string): number {
  return (new Blob([String(s)])).size;
}

/**
 * Check if a string represents a valid integer.
 *
 * @param value String value to check
 * @returns true if the value is a valid integer, false otherwise
 */
export function isInt(value: string): boolean {
  for (let i = 0; i < value.length; i++) {
    if ((value.charAt(i) < '0') || (value.charAt(i) > '9')) {
      return false;
    }
  }
  return true;
}

/**
 * Check if there is no problem with the text.
 *
 * @returns true if all checks were successfull
 */
export function check(): boolean {
  let count = 0;
  $('.notempty').each(function () {
    if (($(this).val() as string).trim() === '') count++;
  });
  if (count > 0) {
    alert('ERROR\n\n' + count + ' field(s) - marked with * - must not be empty!');
    return false;
  }
  count = 0;
  $('input.checkurl').each(function () {
    if (($(this).val() as string).trim().length > 0) {
      const val = ($(this).val() as string).trim();
      if ((val.indexOf('http://') !== 0) &&
      (val.indexOf('https://') !== 0) &&
      (val.indexOf('#') !== 0)) {
        alert(
          'ERROR\n\nField "' + $(this).attr('data_info') +
          '" must start with "http://" or "https://" if not empty.'
        );
        count++;
      }
    }
  });
  // Note: as of LWT 2.9.0, no field with "checkregexp" property is found in the code base
  $('input.checkregexp').each(function () {
    const regexp = ($(this).val() as string).trim();
    if (regexp.length > 0) {
      $.ajax({
        type: 'POST',
        url: 'inc/ajax.php',
        data: {
          action: '',
          action_type: 'check_regexp',
          regex: regexp
        },
        async: false
      }).always(function (data: string) {
        if (data !== '') {
          alert(data);
          count++;
        }
      });
    }
  });
  // To enable limits of custom feed texts/articl.
  // change the following «input[class*="max_int_"]» into «input[class*="maxint_"]»
  $('input[class*="max_int_"]').each(function () {
    const classAttr = getAttr($(this), 'class');
    const maxvalue = parseInt(classAttr.replace(/.*maxint_([0-9]+).*/, '$1'), 10);
    if (($(this).val() as string).trim().length > 0) {
      if (parseInt($(this).val() as string, 10) > maxvalue) {
        alert(
          'ERROR\n\n Max Value of Field "' + $(this).attr('data_info') +
          '" is ' + maxvalue
        );
        count++;
      }
    }
  });
  // Check that the Google Translate field is of good type
  $('input.checkdicturl').each(function () {
    const translate_input = ($(this).val() as string).trim();
    if (translate_input.length > 0) {
      let refinned = translate_input;
      if (translate_input.startsWith('*')) {
        refinned = translate_input.substring(1);
      }
      if (!/^https?:\/\//.test(refinned)) {
        refinned = 'http://' + refinned;
      }
      try {
        new URL(refinned);
      } catch (err) {
        if (err instanceof TypeError) {
          alert(
            'ERROR\n\nField "' + $(this).attr('data_info') +
            '" should be an URL if not empty.'
          );
          count++;
        }
      }
    }
  });
  $('input.posintnumber').each(function () {
    if (($(this).val() as string).trim().length > 0) {
      const val = ($(this).val() as string).trim();
      if (!(isInt(val) && (parseInt(val, 10) > 0))) {
        alert(
          'ERROR\n\nField "' + $(this).attr('data_info') +
          '" must be an integer number > 0.'
        );
        count++;
      }
    }
  });
  $('input.zeroposintnumber').each(function () {
    if (($(this).val() as string).trim().length > 0) {
      const val = ($(this).val() as string).trim();
      if (!(isInt(val) && (parseInt(val, 10) >= 0))) {
        alert(
          'ERROR\n\nField "' + $(this).attr('data_info') +
          '" must be an integer number >= 0.'
        );
        count++;
      }
    }
  });
  $('input.checkoutsidebmp').each(function () {
    const val = getVal($(this));
    if (val.trim().length > 0) {
      if (containsCharacterOutsideBasicMultilingualPlane(val)) {
        count += alertFirstCharacterOutsideBasicMultilingualPlane(
          val, getAttr($(this), 'data_info')
        );
      }
    }
  });
  $('textarea.checklength').each(function () {
    const $el = $(this);
    const maxLength = parseInt(getAttr($el, 'data_maxlength') || '0', 10);
    const val = getVal($el);
    if (val.trim().length > maxLength) {
      alert(
        'ERROR\n\nText is too long in field "' + getAttr($el, 'data_info') +
        '", please make it shorter! (Maximum length: ' +
        getAttr($el, 'data_maxlength') + ' char.)'
      );
      count++;
    }
  });
  $('textarea.checkoutsidebmp').each(function () {
    const val = getVal($(this));
    if (containsCharacterOutsideBasicMultilingualPlane(val)) {
      count += alertFirstCharacterOutsideBasicMultilingualPlane(
        val, getAttr($(this), 'data_info')
      );
    }
  });
  $('textarea.checkbytes').each(function () {
    const $el = $(this);
    const maxLength = parseInt(getAttr($el, 'data_maxlength') || '0', 10);
    const val = getVal($el);
    if (getUTF8Length(val.trim()) > maxLength) {
      alert(
        'ERROR\n\nText is too long in field "' + getAttr($el, 'data_info') +
        '", please make it shorter! (Maximum length: ' +
        getAttr($el, 'data_maxlength') + ' bytes.)'
      );
      count++;
    }
  });
  $('input.noblanksnocomma').each(function () {
    const val = $(this).val() as string;
    if (val.indexOf(' ') > 0 || val.indexOf(',') > 0) {
      alert(
        'ERROR\n\nNo spaces or commas allowed in field "' +
        $(this).attr('data_info') + '", please remove!'
      );
      count++;
    }
  });
  return (count === 0);
}

/**
 * Handle Enter key press in textareas to trigger form submission.
 *
 * @param event jQuery keyboard event
 * @returns false to prevent default behavior if Enter was pressed and form is valid, true otherwise
 */
export function textareaKeydown(event: JQuery.KeyDownEvent): boolean {
  if (event.keyCode && event.keyCode === 13) {
    if (check()) { $('input:submit').last().trigger('click'); }
    return false;
  } else {
    return true;
  }
}

// Expose globally for backward compatibility with PHP templates
if (typeof window !== 'undefined') {
  const w = window as unknown as Record<string, unknown>;
  w.containsCharacterOutsideBasicMultilingualPlane = containsCharacterOutsideBasicMultilingualPlane;
  w.alertFirstCharacterOutsideBasicMultilingualPlane = alertFirstCharacterOutsideBasicMultilingualPlane;
  w.getUTF8Length = getUTF8Length;
  w.isInt = isInt;
  w.check = check;
  w.textareaKeydown = textareaKeydown;
}

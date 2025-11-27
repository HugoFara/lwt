/**
 * HTML escaping utility functions for LWT.
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.10.0-fork Extracted from pgm.ts
 */

/**
 * Replace html characters with encodings
 *
 * See https://stackoverflow.com/questions/1787322/what-is-the-htmlspecialchars-equivalent-in-javascript
 *
 * @param s String to be escaped
 * @returns Escaped string
 */
export function escape_html_chars(s: string): string {
  const map: Record<string, string> = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
    '\x0d': '<br />' // This one inserts HTML, delete? (2.9.0)
  };

  // eslint-disable-next-line no-control-regex -- intentionally matching carriage return
  return s.replace(/[&<>"'\x0d]/g, function (m) { return map[m]; });
}

/**
 * Escape the HTML characters, with an eventual annotation
 *
 * @param title String to be escaped
 * @param ann   An annotation to show in red
 * @returns Escaped string
 */
export function escape_html_chars_2(title: string, ann: string): string {
  if (ann !== '') {
    const ann2 = escape_html_chars(ann);
    return escape_html_chars(title).replace(ann2,
      '<span style="color:red">' + ann2 + '</span>');
  }
  return escape_html_chars(title);
}

/**
 * Escape only single apostrophe ("'") from string
 *
 * @param s String to be escaped
 * @returns Escaped string
 */
export function escape_apostrophes(s: string): string {
  return s.replace(/'/g, "\\'");
}


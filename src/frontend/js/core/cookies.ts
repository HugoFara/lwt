/**
 * Cookie utility functions for LWT.
 *
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   2.10.0-fork Extracted from pgm.ts
 */

/**
 * Get a specific cookie by its name.
 *
 * @param check_name Cookie name
 * @returns Value of the cookie if found, null otherwise
 *
 * @since 2.6.0-fork Use decodeURIComponent instead of deprecated unescape
 */
export function getCookie(check_name: string): string | null {
  const a_all_cookies = document.cookie.split(';');
  let a_temp_cookie: string[];
  let cookie_name = '';
  let cookie_value = '';

  for (let i = 0; i < a_all_cookies.length; i++) {
    a_temp_cookie = a_all_cookies[i].split('=');
    cookie_name = a_temp_cookie[0].replace(/^\s+|\s+$/g, '');
    if (cookie_name === check_name) {
      if (a_temp_cookie.length > 1) {
        cookie_value = decodeURIComponent(
          a_temp_cookie[1].replace(/^\s+|\s+$/g, '')
        );
      }
      return cookie_value;
    }
  }
  return null;
}

/**
 * Set a new cookie.
 *
 * @param name    Name of the cookie
 * @param value   Cookie value
 * @param expires Number of DAYS before the cookie expires.
 * @param path    Cookie path
 * @param domain  Cookie domain
 * @param secure  If it should only be sent through secure connection
 *
 * @since 2.6.0-fork Use encodeURIComponent instead of deprecated escape
 */
export function setCookie(
  name: string,
  value: string,
  expires: number,
  path: string,
  domain: string,
  secure: boolean
): void {
  const today = new Date();
  today.setTime(today.getTime());
  let expiresMs = 0;
  if (expires) {
    expiresMs = expires * 1000 * 60 * 60 * 24;
  }
  const expires_date = new Date(today.getTime() + expiresMs);
  document.cookie = name + '=' + encodeURIComponent(value) +
    (expires ? ';expires=' + expires_date.toUTCString() : '') +
    (path ? ';path=' + path : '') +
    (domain ? ';domain=' + domain : '') +
    (secure ? ';secure' : '');
}

/**
 * Delete a cookie.
 *
 * @param name   Cookie name
 * @param path   Cookie path
 * @param domain Cookie domain
 */
export function deleteCookie(name: string, path: string, domain: string): void {
  if (getCookie(name)) {
    document.cookie = name + '=' +
      (path ? ';path=' + path : '') +
      (domain ? ';domain=' + domain : '') +
      ';expires=Thu, 01-Jan-1970 00:00:01 GMT';
  }
}

/**
 * Check if cookies are enabled by setting a cookie.
 *
 * @returns true if cookies are enabled, false otherwise
 */
export function areCookiesEnabled(): boolean {
  setCookie('test', 'none', 0, '/', '', false);
  let cookie_set: boolean;
  if (getCookie('test')) {
    cookie_set = true;
    deleteCookie('test', '/', '');
  } else {
    cookie_set = false;
  }
  return cookie_set;
}

// Expose functions globally for inline scripts
declare global {
  interface Window {
    getCookie: typeof getCookie;
    setCookie: typeof setCookie;
    deleteCookie: typeof deleteCookie;
    areCookiesEnabled: typeof areCookiesEnabled;
  }
}

window.getCookie = getCookie;
window.setCookie = setCookie;
window.deleteCookie = deleteCookie;
window.areCookiesEnabled = areCookiesEnabled;


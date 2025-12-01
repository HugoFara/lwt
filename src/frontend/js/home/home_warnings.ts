/**
 * Home page warnings and version checking.
 *
 * Handles cookie checking, PHP version validation, and LWT version updates.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { areCookiesEnabled } from '../core/cookies';

/**
 * Compare two semantic version strings.
 *
 * @param fromVersion - Version to compare from (e.g., "2.5.1")
 * @param toVersion - Version to compare to (e.g., "2.6.0")
 * @returns true if fromVersion < toVersion, false if fromVersion > toVersion, null if equal
 */
export function shouldUpdate(fromVersion: string, toVersion: string): boolean | null {
  const regex = /^(\d+)\.(\d+)\.(\d+)(?:-[\w.-]+)?/;
  const match1 = fromVersion.match(regex);
  const match2 = toVersion.match(regex);

  if (!match1 || !match2) {
    return null;
  }

  for (let i = 1; i < 4; i++) {
    const level1 = parseInt(match1[i], 10);
    const level2 = parseInt(match2[i], 10);
    if (level1 < level2) {
      return true;
    } else if (level1 > level2) {
      return false;
    }
  }

  return null;
}

/**
 * Show a notification box by removing its hidden style.
 *
 * @param boxId - The ID of the notification box element
 */
function showNotificationBox(boxId: string): void {
  const box = document.getElementById(boxId);
  if (box) {
    box.style.display = '';
  }
}

/**
 * Check if cookies are disabled and display a warning.
 */
export function checkCookiesDisabled(): void {
  if (!areCookiesEnabled()) {
    const el = document.getElementById('cookies_disabled');
    if (el) {
      el.innerHTML = 'Cookies are not enabled! Please enable them for LWT to work properly.';
      showNotificationBox('cookies_disabled_box');
    }
  }
}

/**
 * Check if PHP version is outdated and display a warning.
 *
 * @param phpVersion - Current PHP version string
 */
export function checkOutdatedPHP(phpVersion: string): void {
  const phpMinVersion = '8.0.0';
  if (shouldUpdate(phpVersion, phpMinVersion)) {
    const el = document.getElementById('php_update_required');
    if (el) {
      el.innerHTML = 'Your PHP version is <strong>' + phpVersion + '</strong>, but version <strong>' +
        phpMinVersion + '</strong> is required. Please update PHP.';
      showNotificationBox('php_update_required_box');
    }
  }
}

/**
 * Check for LWT updates from GitHub and display a notification.
 *
 * @param lwtVersion - Current LWT version string
 */
export function checkLWTUpdate(lwtVersion: string): void {
  fetch('https://api.github.com/repos/hugofara/lwt/releases/latest')
    .then(response => response.json())
    .then((data: { tag_name: string }) => {
      const latestVersion = data.tag_name;
      if (shouldUpdate(lwtVersion, latestVersion)) {
        const el = document.getElementById('lwt_new_version');
        if (el) {
          el.innerHTML = 'An update for LWT is available: <strong>' +
            latestVersion + '</strong> (your version: ' + lwtVersion +
            '). <a href="https://github.com/HugoFara/lwt/releases/tag/' +
            latestVersion + '" class="button is-small is-info is-outlined ml-2">Download</a>';
          showNotificationBox('lwt_new_version_box');
        }
      }
    })
    .catch(() => {
      // Silently fail if GitHub API is unreachable
    });
}

/**
 * Configuration for home warnings initialization.
 */
export interface HomeWarningsConfig {
  phpVersion: string;
  lwtVersion: string;
}

/**
 * Initialize all home page warnings.
 *
 * @param config - Configuration object with PHP and LWT versions
 */
export function initHomeWarnings(config: HomeWarningsConfig): void {
  checkCookiesDisabled();
  checkOutdatedPHP(config.phpVersion);
  checkLWTUpdate(config.lwtVersion);
}

/**
 * Initialize home warnings from a JSON config element.
 *
 * Looks for a script element with id="home-warnings-config" containing JSON data.
 */
export function initHomeWarningsFromConfig(): void {
  const configElement = document.getElementById('home-warnings-config');
  if (!configElement) {
    return;
  }

  try {
    const config: HomeWarningsConfig = JSON.parse(configElement.textContent || '{}');
    initHomeWarnings(config);
  } catch (e) {
    console.error('Failed to parse home warnings config:', e);
  }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  initHomeWarningsFromConfig();
});

// Expose functions globally for backward compatibility
declare global {
  interface Window {
    shouldUpdate: typeof shouldUpdate;
    checkCookiesDisabled: typeof checkCookiesDisabled;
    checkOutdatedPHP: typeof checkOutdatedPHP;
    checkLWTUpdate: typeof checkLWTUpdate;
    initHomeWarnings: typeof initHomeWarnings;
  }
}

window.shouldUpdate = shouldUpdate;
window.checkCookiesDisabled = checkCookiesDisabled;
window.checkOutdatedPHP = checkOutdatedPHP;
window.checkLWTUpdate = checkLWTUpdate;
window.initHomeWarnings = initHomeWarnings;

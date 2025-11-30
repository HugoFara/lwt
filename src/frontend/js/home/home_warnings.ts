/**
 * Home page warnings and version checking.
 *
 * Handles cookie checking, PHP version validation, and LWT version updates.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import $ from 'jquery';
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
 * Check if cookies are disabled and display a warning.
 */
export function checkCookiesDisabled(): void {
  if (!areCookiesEnabled()) {
    $('#cookies_disabled')
      .html('*** Cookies are not enabled! Please enable them! ***');
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
    $('#php_update_required').html(
      '*** Your PHP version is ' + phpVersion + ', but version ' +
      phpMinVersion + ' is required. Please update it. ***'
    );
  }
}

/**
 * Check for LWT updates from GitHub and display a notification.
 *
 * @param lwtVersion - Current LWT version string
 */
export function checkLWTUpdate(lwtVersion: string): void {
  $.getJSON(
    'https://api.github.com/repos/hugofara/lwt/releases/latest'
  ).done(function (data: { tag_name: string }) {
    const latestVersion = data.tag_name;
    if (shouldUpdate(lwtVersion, latestVersion)) {
      $('#lwt_new_version').html(
        '*** An update for LWT is available: ' +
        latestVersion + ', your version is ' + lwtVersion +
        '. <a href="https://github.com/HugoFara/lwt/releases/tag/' +
        latestVersion + '">Download</a>.***'
      );
    }
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
$(document).ready(function () {
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

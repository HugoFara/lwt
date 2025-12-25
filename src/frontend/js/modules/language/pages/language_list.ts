/**
 * Language List - Handles the Manage Languages page interactions.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

import { SettingsApi } from '../api/settings';
import { initIcons } from '../ui/lucide_icons';

/**
 * Show a notification message on the languages page.
 *
 * @param message  The message to display
 * @param isSuccess Whether this is a success (true) or error (false) message
 */
function showNotification(message: string, isSuccess = true): void {
  const notification = document.getElementById('language-notification');
  const textEl = document.getElementById('language-notification-text');

  if (!notification || !textEl) return;

  // Update notification style
  notification.classList.remove('is-success', 'is-danger');
  notification.classList.add(isSuccess ? 'is-success' : 'is-danger');

  // Set message and show
  textEl.textContent = message;
  notification.style.display = 'block';

  // Auto-hide after 4 seconds
  setTimeout(() => {
    notification.style.display = 'none';
  }, 4000);
}

/**
 * Create an icon HTML string for Lucide icons.
 *
 * @param name Icon name
 * @param size Icon size in pixels
 * @param title Optional title attribute
 * @returns HTML string for the icon
 */
function iconHtml(name: string, size = 16, title = ''): string {
  const titleAttr = title ? ` title="${title}"` : '';
  return `<i data-lucide="${name}" class="icon" style="width:${size}px;height:${size}px"${titleAttr}></i>`;
}

/**
 * Update the UI to reflect the new current language.
 *
 * @param newLangId The ID of the newly selected language
 */
function updateLanguageCards(newLangId: string): void {
  const allCards = document.querySelectorAll<HTMLElement>('.language-card');

  allCards.forEach(card => {
    const cardLangId = card.dataset.langId;
    const headerTitle = card.querySelector('.card-header-title');
    const headerIcon = card.querySelector('.card-header-icon');

    if (!headerTitle || !headerIcon) return;

    if (cardLangId === newLangId) {
      // This is the new current language
      card.classList.add('is-current');

      // Add the indicator icon before the name if not present
      const existingIcon = headerTitle.querySelector('[data-lucide="circle-alert"]');
      if (!existingIcon) {
        const langName = headerTitle.textContent?.trim() || '';
        headerTitle.innerHTML = iconHtml('circle-alert', 18, 'Current Language') + ' ' + langName;
      }

      // Remove the "Set as Default" button
      headerIcon.innerHTML = '';
    } else {
      // This is not the current language
      card.classList.remove('is-current');

      // Remove the indicator icon from the title
      const indicatorIcon = headerTitle.querySelector('[data-lucide="circle-alert"]');
      if (indicatorIcon) {
        indicatorIcon.remove();
      }

      // Add the "Set as Default" button if not present
      const existingBtn = headerIcon.querySelector('.set-current-language-btn');
      if (!existingBtn) {
        const cardName = card.querySelector('.card-header-title')?.textContent?.trim() || '';
        headerIcon.innerHTML = `
          <button
            type="button"
            class="button is-small is-primary is-outlined set-current-language-btn"
            data-action="set-current-language"
            data-lang-id="${cardLangId}"
            data-lang-name="${cardName}"
            title="Set as Current Language"
          >
            ${iconHtml('circle-check', 14)}
            <span>Set as Default</span>
          </button>
        `;
      }
    }
  });

  // Re-initialize Lucide icons for the updated cards
  initIcons();
}

/**
 * Handle click on "Set as Default" button.
 *
 * @param button The clicked button element
 */
async function handleSetCurrentLanguage(button: HTMLElement): Promise<void> {
  const langId = button.dataset.langId;
  const langName = button.dataset.langName;

  if (!langId) return;

  // Show loading state
  button.classList.add('is-loading');
  const buttonEl = button as HTMLButtonElement;
  buttonEl.disabled = true;

  try {
    // Save the setting via API
    const response = await SettingsApi.save('currentlanguage', langId);

    if (!response.error) {
      // Update UI reactively
      updateLanguageCards(langId);
      showNotification(`"${langName}" is now the default language.`);
    } else {
      showNotification('Failed to set default language. Please try again.', false);
      // Restore button state
      button.classList.remove('is-loading');
      buttonEl.disabled = false;
    }
  } catch {
    showNotification('An error occurred. Please try again.', false);
    // Restore button state
    button.classList.remove('is-loading');
    buttonEl.disabled = false;
  }
}

/**
 * Initialize the language list page interactions.
 */
function initLanguageList(): void {
  // Use event delegation for "Set as Default" buttons
  document.addEventListener('click', (e) => {
    const target = e.target as HTMLElement;
    const button = target.closest<HTMLElement>('[data-action="set-current-language"]');

    if (button) {
      e.preventDefault();
      handleSetCurrentLanguage(button);
    }
  });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', initLanguageList);

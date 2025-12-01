/**
 * Feed Wizard Common - Shared functionality for feed wizard steps.
 *
 * Handles common patterns across all wizard steps including:
 * - Page header modification for wizard steps
 * - Cancel button navigation
 * - Common wizard interactions
 *
 * @license unlicense
 * @since   3.0.0
 */

/**
 * Configuration for wizard step headers.
 */
interface WizardStepConfig {
  step: number;
  title: string;
  helpLink?: string;
}

/**
 * Set up the wizard step header with proper title and help link.
 *
 * @param config - Configuration for the wizard step
 */
export function setupWizardHeader(config: WizardStepConfig): void {
  const helpHtml = config.helpLink
    ? ` <a href="${config.helpLink}" target="_blank">` +
      '<img alt="Help" title="Help" src="/assets/icons/question-frame.png"></a>'
    : '';

  // Get the last h1 element
  const h1Elements = document.querySelectorAll<HTMLElement>('h1');
  const lastH1 = h1Elements[h1Elements.length - 1];
  if (lastH1) {
    lastH1.innerHTML = `Feed Wizard | Step ${config.step} - ${config.title}${helpHtml}`;
    lastH1.style.textAlign = 'center';
  }
}

/**
 * Initialize wizard step 1 (Insert Feed URI).
 * Sets up the header with appropriate title and help link.
 */
export function initWizardStep1(): void {
  const configEl = document.getElementById('wizard-step1-config');
  if (!configEl) {
    return;
  }

  setupWizardHeader({
    step: 1,
    title: 'Insert Newsfeed URI',
    helpLink: 'docs/info.html#feed_wizard'
  });
}

/**
 * Initialize common wizard event handlers.
 * Binds cancel button and other common wizard actions.
 */
export function initWizardCommon(): void {
  // Handle wizard cancel buttons using event delegation
  document.addEventListener('click', function (e) {
    const target = e.target as HTMLElement;
    const actionEl = target.closest('[data-action="wizard-cancel"]') as HTMLElement | null;
    if (actionEl) {
      e.preventDefault();
      const url = actionEl.dataset.url || '/feeds/edit?del_wiz=1';
      location.href = url;
    }
  });
}

// Auto-initialize on document ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function () {
    initWizardCommon();

    // Initialize specific wizard steps based on config elements present
    if (document.getElementById('wizard-step1-config')) {
      initWizardStep1();
    }
  });
} else {
  initWizardCommon();

  // Initialize specific wizard steps based on config elements present
  if (document.getElementById('wizard-step1-config')) {
    initWizardStep1();
  }
}

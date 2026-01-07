/**
 * Feed Wizard Module Entry Point.
 *
 * Registers all Alpine.js components and stores for the feed wizard feature.
 * Import this file to initialize the feed wizard functionality.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

// Types (for re-export)
export * from './types/feed_wizard_types';

// Utilities
export * from './utils/xpath_utils';

// Services
export { HighlightService, getHighlightService, initHighlightService } from './services/highlight_service';

// Store
export { initFeedWizardStore, getFeedWizardStore } from './stores/feed_wizard_store';

// Components
export { feedWizardStep1Data, initFeedWizardStep1Alpine } from './components/feed_wizard_step1';
export { feedWizardStep2Data, initFeedWizardStep2Alpine } from './components/feed_wizard_step2';
export { feedWizardStep3Data, initFeedWizardStep3Alpine } from './components/feed_wizard_step3';
export { feedWizardStep4Data, initFeedWizardStep4Alpine } from './components/feed_wizard_step4';

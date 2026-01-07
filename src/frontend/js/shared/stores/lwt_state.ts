/**
 * LWT State Management - Core data structures and state modules
 *
 * This module re-exports the focused state modules:
 *
 * - reading_state.ts - Reading position state
 * - language_config.ts - Language configuration
 * - text_config.ts - Text configuration
 * - settings_config.ts - Application settings
 * - test_state.ts - Test mode state
 *
 * @license unlicense
 * @author  andreask7 <andreasks7@users.noreply.github.com>
 * @since   1.6.16-fork
 */

// Import types from globals.d.ts to ensure consistency
import type { LwtLanguage, LwtText, LwtWord, LwtTest, LwtSettings } from '@/types/globals.d';

// Re-export new state modules for easier migration
export * from '@modules/text/stores/reading_state';
export * from '@modules/language/stores/language_config';
export * from '@modules/text/stores/text_config';
export * from '../utils/settings_config';
export * from '@modules/review/stores/test_state';

// Re-export types for backward compatibility
export type { LwtLanguage, LwtText, LwtWord, LwtTest, LwtSettings };


/**
 * Admin Module - Application settings, backup, and statistics.
 *
 * @license Unlicense <http://unlicense.org/>
 * @since   3.0.0
 */

// API
export * from './api/settings_api';

// Side-effect imports (pages)
import './pages/backup_manager';
import './pages/settings_form';
import './pages/statistics_charts';
import '@modules/activity';
import './pages/table_management';
import './pages/tts_settings';
import './pages/server_data';
import './pages/user_management';

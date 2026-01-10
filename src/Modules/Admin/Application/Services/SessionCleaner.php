<?php declare(strict_types=1);
/**
 * Session Cleaner Service
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Admin\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Admin\Application\Services;

use Lwt\Shared\Infrastructure\Database\Settings;

/**
 * Service for clearing session filters.
 *
 * Called when changing the current language to reset all filters.
 *
 * @since 3.0.0
 */
class SessionCleaner
{
    /**
     * Clear settings when changing the current language.
     *
     * Note: Pagination/filter state is now stored in URL parameters,
     * so session clearing is no longer needed. This method only clears
     * database settings that should reset on language change.
     *
     * @return void
     */
    public function clearAllFilters(): void
    {
        // Clear current text setting (database-stored)
        Settings::save('currenttext', '');
    }

    /**
     * Clear text-related filters.
     *
     * @return void
     *
     * @deprecated No longer needed - pagination state is now in URL params
     */
    public function clearTextFilters(): void
    {
        // No-op: pagination state is now in URL parameters
    }

    /**
     * Clear word-related filters.
     *
     * @return void
     *
     * @deprecated No longer needed - pagination state is now in URL params
     */
    public function clearWordFilters(): void
    {
        // No-op: pagination state is now in URL parameters
    }
}

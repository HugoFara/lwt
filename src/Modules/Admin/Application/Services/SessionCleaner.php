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

use Lwt\Database\Settings;

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
     * Clear all session filters.
     *
     * Called when changing the current language.
     *
     * @return void
     */
    public function clearAllFilters(): void
    {
        // Text filters
        unset($_SESSION['currenttextpage']);
        unset($_SESSION['currenttextquery']);
        unset($_SESSION['currenttextquerymode']);
        unset($_SESSION['currenttexttag1']);
        unset($_SESSION['currenttexttag2']);
        unset($_SESSION['currenttexttag12']);

        // Word filters
        unset($_SESSION['currentwordpage']);
        unset($_SESSION['currentwordquery']);
        unset($_SESSION['currentwordquerymode']);
        unset($_SESSION['currentwordstatus']);
        unset($_SESSION['currentwordtext']);
        unset($_SESSION['currentwordtag1']);
        unset($_SESSION['currentwordtag2']);
        unset($_SESSION['currentwordtag12']);
        unset($_SESSION['currentwordtextmode']);
        unset($_SESSION['currentwordtexttag']);

        // Archive filters
        unset($_SESSION['currentarchivepage']);
        unset($_SESSION['currentarchivequery']);
        unset($_SESSION['currentarchivequerymode']);
        unset($_SESSION['currentarchivetexttag1']);
        unset($_SESSION['currentarchivetexttag2']);
        unset($_SESSION['currentarchivetexttag12']);

        // RSS filters
        unset($_SESSION['currentrsspage']);
        unset($_SESSION['currentrssfeed']);
        unset($_SESSION['currentrssquery']);
        unset($_SESSION['currentrssquerymode']);

        // Feed filters
        unset($_SESSION['currentfeedspage']);
        unset($_SESSION['currentmanagefeedsquery']);

        // Clear current text setting
        Settings::save('currenttext', '');
    }

    /**
     * Clear text-related session filters only.
     *
     * @return void
     */
    public function clearTextFilters(): void
    {
        unset($_SESSION['currenttextpage']);
        unset($_SESSION['currenttextquery']);
        unset($_SESSION['currenttextquerymode']);
        unset($_SESSION['currenttexttag1']);
        unset($_SESSION['currenttexttag2']);
        unset($_SESSION['currenttexttag12']);
    }

    /**
     * Clear word-related session filters only.
     *
     * @return void
     */
    public function clearWordFilters(): void
    {
        unset($_SESSION['currentwordpage']);
        unset($_SESSION['currentwordquery']);
        unset($_SESSION['currentwordquerymode']);
        unset($_SESSION['currentwordstatus']);
        unset($_SESSION['currentwordtext']);
        unset($_SESSION['currentwordtag1']);
        unset($_SESSION['currentwordtag2']);
        unset($_SESSION['currentwordtag12']);
        unset($_SESSION['currentwordtextmode']);
        unset($_SESSION['currentwordtexttag']);
    }
}

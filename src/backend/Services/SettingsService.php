<?php declare(strict_types=1);
/**
 * Settings Service - Business logic for application settings
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Services;

use Lwt\Core\Globals;
use Lwt\Core\Http\InputValidator;
use Lwt\Database\Connection;
use Lwt\Database\Settings;

require_once __DIR__ . '/../Core/Http/InputValidator.php';

/**
 * Service class for managing application settings.
 *
 * Handles saving, loading, and resetting user preferences.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class SettingsService
{
    /**
     * Settings definitions with defaults and validation rules.
     *
     * Each setting has:
     * - dft: Default value
     * - num: Whether it's numeric (0 = no, 1 = yes)
     * - min: Minimum value (if numeric)
     * - max: Maximum value (if numeric)
     *
     * @var array<string, array{dft: string, num: int, min?: int, max?: int}>
     */
    private const SETTING_DEFINITIONS = [
        'set-text-h-frameheight-no-audio' => [
            "dft" => '140', "num" => 1, "min" => 10, "max" => 999
        ],
        'set-text-h-frameheight-with-audio' => [
            "dft" => '200', "num" => 1, "min" => 10, "max" => 999
        ],
        'set-text-l-framewidth-percent' => [
            "dft" => '60', "num" => 1, "min" => 5, "max" => 95
        ],
        'set-text-r-frameheight-percent' => [
            "dft" => '37', "num" => 1, "min" => 5, "max" => 95
        ],
        'set-test-h-frameheight' => [
            "dft" => '140', "num" => 1, "min" => 10, "max" => 999
        ],
        'set-test-l-framewidth-percent' => [
            "dft" => '50', "num" => 1, "min" => 5, "max" => 95
        ],
        'set-test-r-frameheight-percent' => [
            "dft" => '50', "num" => 1, "min" => 5, "max" => 95
        ],
        'set-words-to-do-buttons' => [
            "dft" => '1', "num" => 0
        ],
        'set-tooltip-mode' => [
            "dft" => '2', "num" => 0
        ],
        'set-display-text-frame-term-translation' => [
            "dft" => '1', "num" => 0
        ],
        'set-text-frame-annotation-position' => [
            "dft" => '2', "num" => 0
        ],
        'set-test-main-frame-waiting-time' => [
            "dft" => '0', "num" => 1, "min" => 0, "max" => 9999
        ],
        'set-test-edit-frame-waiting-time' => [
            "dft" => '500', "num" => 1, "min" => 0, "max" => 99999999
        ],
        'set-test-sentence-count' => [
            "dft" => '1', "num" => 0
        ],
        'set-tts' => [
            "dft" => '1', "num" => 0
        ],
        'set-hts' => [
            "dft" => '1', "num" => 0
        ],
        'set-term-sentence-count' => [
            "dft" => '1', "num" => 0
        ],
        'set-archivedtexts-per-page' => [
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ],
        'set-texts-per-page' => [
            "dft" => '10', "num" => 1, "min" => 1, "max" => 9999
        ],
        'set-terms-per-page' => [
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ],
        'set-tags-per-page' => [
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ],
        'set-articles-per-page' => [
            "dft" => '10', "num" => 1, "min" => 1, "max" => 9999
        ],
        'set-feeds-per-page' => [
            "dft" => '50', "num" => 1, "min" => 1, "max" => 9999
        ],
        'set-max-articles-with-text' => [
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ],
        'set-max-articles-without-text' => [
            "dft" => '250', "num" => 1, "min" => 1, "max" => 9999
        ],
        'set-max-texts-per-feed' => [
            "dft" => '20', "num" => 1, "min" => 1, "max" => 9999
        ],
        'set-ggl-translation-per-page' => [
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ],
        'set-regex-mode' => [
            "dft" => '', "num" => 0
        ],
        'set-theme_dir' => [
            "dft" => 'themes/default/', "num" => 0
        ],
        'set-text-visit-statuses-via-key' => [
            "dft" => '', "num" => 0
        ],
        'set-term-translation-delimiters' => [
            "dft" => '/;|', "num" => 0
        ],
        'set-mobile-display-mode' => [
            "dft" => '0', "num" => 0
        ],
        'set-similar-terms-count' => [
            "dft" => '0', "num" => 1, "min" => 0, "max" => 9
        ],
        'set-show-text-word-counts' => [
            "dft" => '1', "num" => 0
        ]
    ];

    /**
     * All setting keys that can be saved.
     *
     * @var string[]
     */
    private const SETTING_KEYS = [
        'set-theme-dir',
        'set-text-h-frameheight-no-audio',
        'set-text-h-frameheight-with-audio',
        'set-text-l-framewidth-percent',
        'set-text-r-frameheight-percent',
        'set-test-h-frameheight',
        'set-test-l-framewidth-percent',
        'set-test-r-frameheight-percent',
        'set-words-to-do-buttons',
        'set-tooltip-mode',
        'set-ggl-translation-per-page',
        'set-test-main-frame-waiting-time',
        'set-test-edit-frame-waiting-time',
        'set-test-sentence-count',
        'set-term-sentence-count',
        'set-tts',
        'set-hts',
        'set-archivedtexts-per-page',
        'set-texts-per-page',
        'set-terms-per-page',
        'set-regex-mode',
        'set-tags-per-page',
        'set-articles-per-page',
        'set-feeds-per-page',
        'set-max-articles-with-text',
        'set-max-articles-without-text',
        'set-max-texts-per-feed',
        'set-text-visit-statuses-via-key',
        'set-display-text-frame-term-translation',
        'set-text-frame-annotation-position',
        'set-term-translation-delimiters',
        'set-mobile-display-mode',
        'set-similar-terms-count',
    ];

    /**
     * Save all settings from request data.
     *
     * @return string Success message
     */
    public function saveAll(): string
    {
        foreach (self::SETTING_KEYS as $key) {
            if ($key === 'set-tts') {
                // Handle checkbox - convert to 0/1
                $value = InputValidator::getBool($key, false) ? '1' : '0';
            } elseif (InputValidator::has($key)) {
                $value = InputValidator::getString($key);
            } else {
                continue;
            }
            Settings::save($key, $value);
        }
        return 'Settings saved';
    }

    /**
     * Reset all settings to default values.
     *
     * @return string Success message
     */
    public function resetAll(): string
    {
        $tbpref = Globals::getTablePrefix();
        Connection::execute("DELETE FROM {$tbpref}settings WHERE StKey LIKE 'set-%'");
        return 'All Settings reset to default values';
    }

    /**
     * Get a setting value with its default.
     *
     * @param string $key Setting key
     *
     * @return string Setting value
     */
    public function get(string $key): string
    {
        return Settings::getWithDefault($key);
    }

    /**
     * Get all current settings values.
     *
     * @return array<string, string> Associative array of setting key => value
     */
    public function getAll(): array
    {
        $settings = [];
        foreach (self::SETTING_KEYS as $key) {
            $settings[$key] = Settings::getWithDefault($key);
        }
        return $settings;
    }

    /**
     * Get all setting definitions with defaults and validation rules.
     *
     * @return array<string, array{dft: string, num: int, min?: int, max?: int}>
     */
    public static function getDefinitions(): array
    {
        return self::SETTING_DEFINITIONS;
    }

    /**
     * Save a setting and redirect to a URL.
     *
     * This is used by the save_setting_redirect endpoint.
     *
     * @param string $key   Setting key
     * @param string $value Setting value
     *
     * @return void
     */
    public function saveAndClearSession(string $key, string $value): void
    {
        if ($key === 'currentlanguage') {
            $this->clearSessionSettings();
        }

        Settings::save($key, $value);
    }

    /**
     * Clear all session settings.
     *
     * Called when changing the current language to reset filters.
     *
     * @return void
     */
    private function clearSessionSettings(): void
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
}

<?php

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
use Lwt\Database\Connection;
use Lwt\Database\Settings;

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
     * @param array $requestData The $_REQUEST data
     *
     * @return string Success message
     */
    public function saveAll(array $requestData): string
    {
        foreach (self::SETTING_KEYS as $key) {
            if ($key === 'set-tts') {
                // Handle checkbox - convert to 0/1
                $value = (string)(
                    array_key_exists('set-tts', $requestData) &&
                    (int)$requestData['set-tts'] ? 1 : 0
                );
            } elseif (array_key_exists($key, $requestData)) {
                $value = $requestData[$key];
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
}

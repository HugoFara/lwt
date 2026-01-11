<?php

declare(strict_types=1);

/**
 * Get All Settings Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Admin\Application\UseCases\Settings
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Admin\Application\UseCases\Settings;

use Lwt\Shared\Infrastructure\Database\Settings;

/**
 * Use case for getting all application settings.
 *
 * @since 3.0.0
 */
class GetAllSettings
{
    /**
     * All setting keys that can be retrieved.
     *
     * @var string[]
     */
    private const SETTING_KEYS = [
        'set-theme-dir',
        'set-words-to-do-buttons',
        'set-tooltip-mode',
        'set-ggl-translation-per-page',
        'set-test-main-frame-waiting-time',
        'set-test-edit-frame-waiting-time',
        'set-test-sentence-count',
        'set-term-sentence-count',
        'set-tts',
        'set-hts',
        'set-archived_texts-per-page',
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
        'set-similar-terms-count',
    ];

    /**
     * Execute the use case.
     *
     * @return array<string, string> All settings with their values
     */
    public function execute(): array
    {
        $settings = [];
        foreach (self::SETTING_KEYS as $key) {
            $settings[$key] = Settings::getWithDefault($key);
        }
        return $settings;
    }

    /**
     * Get all setting keys.
     *
     * @return string[] Setting keys
     */
    public static function getSettingKeys(): array
    {
        return self::SETTING_KEYS;
    }
}

<?php declare(strict_types=1);
/**
 * Save All Settings Use Case
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

use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\Infrastructure\Database\Settings;

/**
 * Use case for saving all application settings from form data.
 *
 * @since 3.0.0
 */
class SaveAllSettings
{
    /**
     * All setting keys that can be saved.
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
        'set-similar-terms-count',
    ];

    /**
     * Execute the use case - save all settings from request.
     *
     * @return string Status message
     */
    public function execute(): string
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
     * Execute with explicit data array (for API/testing).
     *
     * @param array<string, string> $data Settings data
     *
     * @return string Status message
     */
    public function executeWithData(array $data): string
    {
        foreach (self::SETTING_KEYS as $key) {
            if (isset($data[$key])) {
                $value = $data[$key];
                if ($key === 'set-tts') {
                    $value = ($value === '1' || $value === 'true') ? '1' : '0';
                }
                Settings::save($key, $value);
            }
        }

        return 'Settings saved';
    }
}

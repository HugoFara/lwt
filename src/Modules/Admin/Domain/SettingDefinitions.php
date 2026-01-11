<?php

declare(strict_types=1);

/**
 * Setting Definitions
 *
 * Contains all application setting definitions with defaults and validation rules.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Admin\Domain
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Admin\Domain;

/**
 * Application setting definitions.
 *
 * Provides default values and validation rules for all application settings.
 *
 * @since 3.0.0
 */
final class SettingDefinitions
{
    /**
     * Setting definitions with defaults and validation rules.
     *
     * Each setting has:
     * - dft: Default value (string)
     * - num: Whether it's numeric (0 = no, 1 = yes)
     * - min: Minimum value (for numeric settings)
     * - max: Maximum value (for numeric settings)
     *
     * @var array<string, array{dft: string, num: int, min?: int, max?: int}>
     */
    private const DEFINITIONS = [
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
        'set-archived_texts-per-page' => [
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
        'set-similar-terms-count' => [
            "dft" => '0', "num" => 1, "min" => 0, "max" => 9
        ],
        'set-show-text-word-counts' => [
            "dft" => '1', "num" => 0
        ]
    ];

    /**
     * Get all setting definitions.
     *
     * @return array<string, array{dft: string, num: int, min?: int, max?: int}>
     */
    public static function getAll(): array
    {
        return self::DEFINITIONS;
    }

    /**
     * Get a specific setting definition.
     *
     * @param string $key Setting key
     *
     * @return array{dft: string, num: int, min?: int, max?: int}|null
     */
    public static function get(string $key): ?array
    {
        return self::DEFINITIONS[$key] ?? null;
    }

    /**
     * Get the default value for a setting.
     *
     * @param string $key Setting key
     *
     * @return string|null Default value or null if not defined
     */
    public static function getDefault(string $key): ?string
    {
        return self::DEFINITIONS[$key]['dft'] ?? null;
    }

    /**
     * Check if a setting is defined.
     *
     * @param string $key Setting key
     *
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset(self::DEFINITIONS[$key]);
    }

    /**
     * Get all setting keys.
     *
     * @return string[]
     */
    public static function getKeys(): array
    {
        return array_keys(self::DEFINITIONS);
    }
}

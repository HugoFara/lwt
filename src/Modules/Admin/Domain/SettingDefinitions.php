<?php

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
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

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
     * Setting scope: admin-only (server-wide).
     */
    public const SCOPE_ADMIN = 'admin';

    /**
     * Setting scope: user preference (per-user in multi-user mode).
     */
    public const SCOPE_USER = 'user';

    /**
     * Setting definitions with defaults, validation rules, and scope.
     *
     * Each setting has:
     * - dft: Default value (string)
     * - num: Whether it's numeric (0 = no, 1 = yes)
     * - min: Minimum value (for numeric settings)
     * - max: Maximum value (for numeric settings)
     * - scope: 'admin' or 'user' (defaults to 'user' if not set)
     *
     * @var array<string, array{dft: string, num: int, min?: int, max?: int, scope: string}>
     */
    private const DEFINITIONS = [
        // User preferences: reading
        'set-words-to-do-buttons' => [
            "dft" => '1', "num" => 0, "scope" => self::SCOPE_USER
        ],
        'set-tooltip-mode' => [
            "dft" => '2', "num" => 0, "scope" => self::SCOPE_USER
        ],
        'set-display-text-frame-term-translation' => [
            "dft" => '1', "num" => 0, "scope" => self::SCOPE_USER
        ],
        'set-text-frame-annotation-position' => [
            "dft" => '2', "num" => 0, "scope" => self::SCOPE_USER
        ],
        'set-text-visit-statuses-via-key' => [
            "dft" => '', "num" => 0, "scope" => self::SCOPE_USER
        ],
        'set-show-text-word-counts' => [
            "dft" => '1', "num" => 0, "scope" => self::SCOPE_USER
        ],

        // User preferences: review
        'set-test-main-frame-waiting-time' => [
            "dft" => '0', "num" => 1, "min" => 0, "max" => 9999, "scope" => self::SCOPE_USER
        ],
        'set-test-edit-frame-waiting-time' => [
            "dft" => '500', "num" => 1, "min" => 0, "max" => 99999999, "scope" => self::SCOPE_USER
        ],
        'set-test-sentence-count' => [
            "dft" => '1', "num" => 0, "scope" => self::SCOPE_USER
        ],
        'set-term-sentence-count' => [
            "dft" => '1', "num" => 0, "scope" => self::SCOPE_USER
        ],
        'set-similar-terms-count' => [
            "dft" => '0', "num" => 1, "min" => 0, "max" => 9, "scope" => self::SCOPE_USER
        ],
        'set-term-translation-delimiters' => [
            "dft" => '/;|', "num" => 0, "scope" => self::SCOPE_USER
        ],

        // User preferences: TTS
        'set-tts' => [
            "dft" => '1', "num" => 0, "scope" => self::SCOPE_USER
        ],
        'set-hts' => [
            "dft" => '1', "num" => 0, "scope" => self::SCOPE_USER
        ],

        // User preferences: pagination
        'set-archived_texts-per-page' => [
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999, "scope" => self::SCOPE_USER
        ],
        'set-texts-per-page' => [
            "dft" => '10', "num" => 1, "min" => 1, "max" => 9999, "scope" => self::SCOPE_USER
        ],
        'set-terms-per-page' => [
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999, "scope" => self::SCOPE_USER
        ],
        'set-tags-per-page' => [
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999, "scope" => self::SCOPE_USER
        ],
        'set-articles-per-page' => [
            "dft" => '10', "num" => 1, "min" => 1, "max" => 9999, "scope" => self::SCOPE_USER
        ],
        'set-feeds-per-page' => [
            "dft" => '50', "num" => 1, "min" => 1, "max" => 9999, "scope" => self::SCOPE_USER
        ],
        'set-ggl-translation-per-page' => [
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999, "scope" => self::SCOPE_USER
        ],
        'set-regex-mode' => [
            "dft" => '', "num" => 0, "scope" => self::SCOPE_USER
        ],

        // User preferences: reading layout
        'set-reader-width' => [
            "dft" => '100', "num" => 1, "min" => 40, "max" => 100,
            "scope" => self::SCOPE_USER
        ],
        'set-reader-text-size' => [
            "dft" => '0', "num" => 1, "min" => 0, "max" => 300,
            "scope" => self::SCOPE_USER
        ],

        // User preferences: appearance
        'set-theme-dir' => [
            "dft" => '', "num" => 0, "scope" => self::SCOPE_USER
        ],

        // Admin settings: feed limits
        'set-max-articles-with-text' => [
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999, "scope" => self::SCOPE_ADMIN
        ],
        'set-max-articles-without-text' => [
            "dft" => '250', "num" => 1, "min" => 1, "max" => 9999, "scope" => self::SCOPE_ADMIN
        ],
        'set-max-texts-per-feed' => [
            "dft" => '20', "num" => 1, "min" => 1, "max" => 9999, "scope" => self::SCOPE_ADMIN
        ],

        // Admin settings: multi-user
        'set-allow-registration' => [
            "dft" => '1', "num" => 0, "scope" => self::SCOPE_ADMIN
        ]
    ];

    /**
     * Get all setting definitions.
     *
     * @return array<string, array{dft: string, num: int, min?: int, max?: int, scope: string}>
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
     * @return array{dft: string, num: int, min?: int, max?: int, scope: string}|null
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

    /**
     * Get the scope of a setting.
     *
     * @param string $key Setting key
     *
     * @return string Scope ('admin' or 'user')
     */
    public static function getScope(string $key): string
    {
        return self::DEFINITIONS[$key]['scope'] ?? self::SCOPE_USER;
    }

    /**
     * Get all admin-scoped setting keys.
     *
     * @return string[]
     */
    public static function getAdminKeys(): array
    {
        return array_keys(
            array_filter(
                self::DEFINITIONS,
                static fn(array $def): bool => ($def['scope'] ?? self::SCOPE_USER) === self::SCOPE_ADMIN
            )
        );
    }

    /**
     * Get all user-scoped setting keys.
     *
     * @return string[]
     */
    public static function getUserKeys(): array
    {
        return array_keys(
            array_filter(
                self::DEFINITIONS,
                static fn(array $def): bool => ($def['scope'] ?? self::SCOPE_USER) === self::SCOPE_USER
            )
        );
    }
}

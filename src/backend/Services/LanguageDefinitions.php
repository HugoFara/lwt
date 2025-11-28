<?php

/**
 * Language Definitions Service - Predefined language configurations
 *
 * This class replaces the legacy langdefs.php file and provides
 * access to predefined language configurations loaded from JSON.
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

/**
 * Service class for predefined language definitions.
 *
 * Provides access to default language configurations including
 * ISO codes, regex patterns, and display settings.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class LanguageDefinitions
{
    /**
     * Cached language definitions.
     *
     * @var array<string, array{0: string, 1: string, 2: bool, 3: string, 4: string, 5: bool, 6: bool, 7: bool}>|null
     */
    private static ?array $definitions = null;

    /**
     * Path to JSON definitions file.
     *
     * @var string
     */
    private static string $jsonPath = __DIR__ . '/../Core/Language/langdefs.json';

    /**
     * Get all language definitions in legacy array format.
     *
     * Returns an associative array where keys are language names and values
     * are indexed arrays with the following structure:
     * - 0: glosbeIso (string) - Glosbe dictionary ISO code
     * - 1: googleIso (string) - Google Translate ISO code
     * - 2: biggerFont (bool) - Whether to use larger font
     * - 3: wordCharRegExp (string) - Regex for word characters
     * - 4: sentSplRegExp (string) - Regex for sentence splitting
     * - 5: makeCharacterWord (bool) - Whether to split characters as words
     * - 6: removeSpaces (bool) - Whether to remove spaces
     * - 7: rightToLeft (bool) - Whether language is RTL
     *
     * @return array<string, array{0: string, 1: string, 2: bool, 3: string, 4: string, 5: bool, 6: bool, 7: bool}>
     *
     * @throws \RuntimeException If JSON file cannot be read or parsed
     */
    public static function getAll(): array
    {
        if (self::$definitions !== null) {
            return self::$definitions;
        }

        self::$definitions = self::loadFromJson();
        return self::$definitions;
    }

    /**
     * Load language definitions from JSON file.
     *
     * @return array<string, array{0: string, 1: string, 2: bool, 3: string, 4: string, 5: bool, 6: bool, 7: bool}>
     *
     * @throws \RuntimeException If file cannot be read or JSON is invalid
     */
    private static function loadFromJson(): array
    {
        $jsonContent = file_get_contents(self::$jsonPath);
        if ($jsonContent === false) {
            throw new \RuntimeException(
                "Could not read language definitions from " . self::$jsonPath
            );
        }

        $languages = json_decode($jsonContent, true);
        if ($languages === null) {
            throw new \RuntimeException("Invalid JSON in language definitions file");
        }

        // Convert from object format to legacy indexed array format
        $result = [];
        foreach ($languages as $name => $props) {
            $result[$name] = [
                $props['glosbeIso'],
                $props['googleIso'],
                $props['biggerFont'],
                $props['wordCharRegExp'],
                $props['sentSplRegExp'],
                $props['makeCharacterWord'],
                $props['removeSpaces'],
                $props['rightToLeft']
            ];
        }

        return $result;
    }

    /**
     * Get definition for a specific language.
     *
     * @param string $name Language name (e.g., "English", "Japanese")
     *
     * @return array{0: string, 1: string, 2: bool, 3: string, 4: string, 5: bool, 6: bool, 7: bool}|null
     */
    public static function get(string $name): ?array
    {
        $definitions = self::getAll();
        return $definitions[$name] ?? null;
    }

    /**
     * Check if a language is defined.
     *
     * @param string $name Language name
     *
     * @return bool
     */
    public static function exists(string $name): bool
    {
        $definitions = self::getAll();
        return isset($definitions[$name]);
    }

    /**
     * Get all language names.
     *
     * @return string[]
     */
    public static function getNames(): array
    {
        return array_keys(self::getAll());
    }

    /**
     * Get Google ISO code for a language.
     *
     * @param string $name Language name
     *
     * @return string|null Google ISO code or null if not found
     */
    public static function getGoogleIso(string $name): ?string
    {
        $def = self::get($name);
        return $def !== null ? $def[1] : null;
    }

    /**
     * Get Glosbe ISO code for a language.
     *
     * @param string $name Language name
     *
     * @return string|null Glosbe ISO code or null if not found
     */
    public static function getGlosbeIso(string $name): ?string
    {
        $def = self::get($name);
        return $def !== null ? $def[0] : null;
    }

    /**
     * Check if a language uses right-to-left script.
     *
     * @param string $name Language name
     *
     * @return bool
     */
    public static function isRightToLeft(string $name): bool
    {
        $def = self::get($name);
        return $def !== null ? $def[7] : false;
    }

    /**
     * Check if a language requires bigger font.
     *
     * @param string $name Language name
     *
     * @return bool
     */
    public static function usesBiggerFont(string $name): bool
    {
        $def = self::get($name);
        return $def !== null ? $def[2] : false;
    }

    /**
     * Clear cached definitions (for testing).
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$definitions = null;
    }
}

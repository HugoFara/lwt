<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services;

/**
 * Maps LWT language names to external frequency/dictionary source codes.
 *
 * Provides lookups for:
 * - FrequencyWords project (GitHub) language codes
 * - Kaikki.org (Wiktextract) language names
 * - Wiktionary edition codes
 */
class FrequencyLanguageMap
{
    /** @var array<string, array{freqCode: string, kaikkiName: string, wiktCode: string}>|null */
    private static ?array $map = null;

    /**
     * Load the language map from the JSON data file.
     *
     * @return array<string, array{freqCode: string, kaikkiName: string, wiktCode: string}>
     */
    private static function load(): array
    {
        if (self::$map === null) {
            $path = __DIR__ . '/../../Infrastructure/Data/frequency_language_map.json';
            $json = file_get_contents($path);
            if ($json === false) {
                self::$map = [];
                return self::$map;
            }
            /** @var mixed $decoded */
            $decoded = json_decode($json, true);
            /** @var array<string, array{freqCode: string, kaikkiName: string, wiktCode: string}> */
            self::$map = is_array($decoded) ? $decoded : [];
        }
        return self::$map;
    }

    /**
     * Check if starter vocabulary is available for a language.
     */
    public static function isSupported(string $languageName): bool
    {
        return isset(self::load()[$languageName]);
    }

    /**
     * Get the FrequencyWords project code (e.g., "es" for Spanish).
     */
    public static function getFrequencyCode(string $languageName): ?string
    {
        return self::load()[$languageName]['freqCode'] ?? null;
    }

    /**
     * Get the kaikki.org language name (e.g., "Spanish").
     */
    public static function getKaikkiLanguageName(string $languageName): ?string
    {
        return self::load()[$languageName]['kaikkiName'] ?? null;
    }

    /**
     * Get the Wiktionary edition code (e.g., "es" for Spanish Wiktionary).
     */
    public static function getWiktionaryCode(string $languageName): ?string
    {
        return self::load()[$languageName]['wiktCode'] ?? null;
    }

    /**
     * Get all supported language names.
     *
     * @return list<string>
     */
    public static function getSupportedLanguages(): array
    {
        return array_keys(self::load());
    }

    /**
     * Reset the cached map (for testing).
     */
    public static function reset(): void
    {
        self::$map = null;
    }
}

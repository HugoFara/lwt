<?php declare(strict_types=1);
/**
 * \file
 * \brief Theme Service - Theme discovery and management.
 *
 * PHP version 8.1
 *
 * @category Services
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/src-backend-Services-ThemeService.html
 * @since    3.0.0
 */

namespace Lwt\Services;

/**
 * Service class for theme management.
 *
 * Handles theme discovery from the filesystem.
 *
 * @since 3.0.0
 */
class ThemeService
{
    /**
     * Default theme metadata for the base theme.
     */
    private const DEFAULT_THEME_METADATA = [
        'name' => 'Default',
        'description' => 'Standard light theme with background color highlighting.',
        'mode' => 'light',
        'highlighting' => 'Background color highlighting',
        'wordBreaking' => 'Standard'
    ];

    /**
     * Get available themes for select dropdown.
     *
     * Scans the assets/themes directory for available themes.
     *
     * @return array<int, array{
     *     path: string,
     *     name: string,
     *     description: string,
     *     mode: string,
     *     highlighting: string,
     *     wordBreaking: string
     * }> Array of theme data with metadata
     */
    public function getAvailableThemes(): array
    {
        $themes = [];
        $themeDirs = glob('assets/themes/*', GLOB_ONLYDIR) ?: [];

        // Add Default first (uses base CSS)
        $themes[] = array_merge(
            ['path' => 'assets/themes/Default/'],
            self::DEFAULT_THEME_METADATA
        );

        foreach ($themeDirs as $theme) {
            if ($theme !== 'assets/themes/Default') {
                $metadata = $this->loadThemeMetadata($theme);
                $themes[] = array_merge(['path' => $theme . '/'], $metadata);
            }
        }

        return $themes;
    }

    /**
     * Load theme metadata from theme.json file.
     *
     * @param string $themePath Path to the theme directory
     *
     * @return array{
     *     name: string,
     *     description: string,
     *     mode: string,
     *     highlighting: string,
     *     wordBreaking: string
     * } Theme metadata
     */
    private function loadThemeMetadata(string $themePath): array
    {
        $jsonPath = $themePath . '/theme.json';
        $fallbackName = str_replace(['assets/themes/', '_'], ['', ' '], $themePath);

        $defaults = [
            'name' => $fallbackName,
            'description' => '',
            'mode' => 'light',
            'highlighting' => '',
            'wordBreaking' => ''
        ];

        if (!file_exists($jsonPath)) {
            return $defaults;
        }

        $content = file_get_contents($jsonPath);
        if ($content === false) {
            return $defaults;
        }

        $metadata = json_decode($content, true);
        if (!is_array($metadata)) {
            return $defaults;
        }

        return [
            'name' => isset($metadata['name']) && is_string($metadata['name'])
                ? $metadata['name'] : $defaults['name'],
            'description' => isset($metadata['description']) && is_string($metadata['description'])
                ? $metadata['description'] : $defaults['description'],
            'mode' => isset($metadata['mode']) && is_string($metadata['mode'])
                ? $metadata['mode'] : $defaults['mode'],
            'highlighting' => isset($metadata['highlighting']) && is_string($metadata['highlighting'])
                ? $metadata['highlighting'] : $defaults['highlighting'],
            'wordBreaking' => isset($metadata['wordBreaking']) && is_string($metadata['wordBreaking'])
                ? $metadata['wordBreaking'] : $defaults['wordBreaking'],
        ];
    }
}

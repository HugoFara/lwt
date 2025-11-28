<?php

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
     * Get available themes for select dropdown.
     *
     * Scans the assets/themes directory for available themes.
     *
     * @return array<int, array{path: string, name: string}> Array of theme data
     */
    public function getAvailableThemes(): array
    {
        $themes = [];
        $themeDirs = glob('assets/themes/*', GLOB_ONLYDIR) ?: [];

        // Add Default first
        $themes[] = ['path' => 'assets/themes/Default/', 'name' => 'Default'];

        foreach ($themeDirs as $theme) {
            if ($theme !== 'assets/themes/Default') {
                $name = str_replace(['assets/themes/', '_'], ['', ' '], $theme);
                $themes[] = ['path' => $theme . '/', 'name' => $name];
            }
        }

        return $themes;
    }
}

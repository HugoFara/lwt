<?php

namespace Lwt\Api\V1\Handlers;

use Lwt\Database\Settings;

/**
 * Handler for settings-related API operations.
 *
 * Extracted from api_v1.php.
 */
class SettingsHandler
{
    /**
     * Save a setting to the database.
     *
     * @param string $key   Setting name
     * @param string $value Setting value
     *
     * @return array{error?: string, message?: string}
     */
    public function saveSetting(string $key, string $value): array
    {
        $status = Settings::save($key, $value);
        if (str_starts_with($status, "OK: ")) {
            return ["message" => substr($status, 4)];
        }
        return ["error" => $status];
    }

    /**
     * Get the file path using the current theme.
     *
     * @param string $path Relative filepath using theme
     *
     * @return array{theme_path: string}
     */
    public function getThemePath(string $path): array
    {
        return ["theme_path" => \get_file_path($path)];
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for saving a setting.
     *
     * @param string $key   Setting key
     * @param string $value Setting value
     *
     * @return array{error?: string, message?: string}
     */
    public function formatSaveSetting(string $key, string $value): array
    {
        return $this->saveSetting($key, $value);
    }

    /**
     * Format response for getting theme path.
     *
     * @param string $path Relative path
     *
     * @return array{theme_path: string}
     */
    public function formatThemePath(string $path): array
    {
        return $this->getThemePath($path);
    }
}

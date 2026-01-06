<?php declare(strict_types=1);
/**
 * \file
 * \brief Vite asset helper for development and production modes.
 *
 * This file provides static methods to load Vite-built assets in PHP,
 * supporting both development mode (with HMR) and production mode
 * (with manifest-based asset loading).
 *
 * PHP version 8.1
 *
 * @category View
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/src-backend-View-Helper-ViteHelper.html
 * @since    3.0.0
 */

namespace Lwt\Shared\UI\Assets;

/**
 * Helper class for Vite asset loading.
 *
 * Provides methods for loading Vite-bundled assets in both
 * development mode (with HMR) and production mode (manifest-based).
 *
 * @since 3.0.0
 */
class ViteHelper
{
    /**
     * Cached manifest data.
     *
     * @var array<string, mixed>|null
     */
    private static ?array $manifest = null;

    /**
     * Check if Vite development server is running.
     *
     * @return bool True if dev server is detected and responding
     */
    public static function isDevServerRunning(): bool
    {
        if (getenv('VITE_DEV_MODE') === false || getenv('VITE_DEV_MODE') === '') {
            return false;
        }

        $ch = curl_init('http://localhost:5173/@vite/client');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Get the Vite manifest file contents.
     *
     * @return array<string, mixed>|null Manifest array or null if not found
     */
    public static function getManifest(): ?array
    {
        if (self::$manifest === null) {
            $path = __DIR__ . '/../../../../assets/.vite/manifest.json';
            if (file_exists($path)) {
                $content = file_get_contents($path);
                if ($content !== false) {
                    self::$manifest = json_decode($content, true);
                }
            }
        }

        return self::$manifest;
    }

    /**
     * Generate HTML tags for Vite assets.
     *
     * In development mode, loads assets from Vite dev server with HMR.
     * In production mode, loads assets from the manifest file.
     *
     * @param string $entry The entry point path (e.g., 'js/main.ts')
     *
     * @return string HTML script and link tags
     */
    public static function assets(string $entry = 'js/main.ts'): string
    {
        if (self::isDevServerRunning()) {
            return <<<HTML
<script type="module" src="http://localhost:5173/@vite/client"></script>
<script type="module" src="http://localhost:5173/{$entry}"></script>
HTML;
        }

        $manifest = self::getManifest();
        if ($manifest === null || !isset($manifest[$entry])) {
            return '<!-- Vite manifest not found or entry missing -->';
        }

        $entryData = $manifest[$entry];
        $html = '';

        // Load CSS files
        if (isset($entryData['css']) && is_array($entryData['css'])) {
            foreach ($entryData['css'] as $cssFile) {
                $html .= '<link rel="stylesheet" href="/assets/' . htmlspecialchars($cssFile) . '">' . "\n";
            }
        }

        // Load JS module
        if (isset($entryData['file'])) {
            $html .= '<script type="module" src="/assets/' . htmlspecialchars($entryData['file']) . '"></script>' . "\n";
        }

        return $html;
    }

    /**
     * Determine whether to use Vite assets or legacy assets.
     *
     * Checks the LWT_ASSET_MODE environment variable:
     * - 'vite': Always use Vite assets
     * - 'legacy': Always use legacy PHP-minified assets
     * - 'auto' or unset: Use Vite if manifest exists, otherwise legacy
     *
     * @return bool True if Vite assets should be used
     */
    public static function shouldUse(): bool
    {
        $envMode = getenv('LWT_ASSET_MODE');
        $mode = ($envMode === false || $envMode === '') ? 'auto' : $envMode;

        if ($mode === 'legacy') {
            return false;
        }
        if ($mode === 'vite') {
            return true;
        }

        // Auto mode: use Vite if manifest exists
        return self::getManifest() !== null;
    }

    /**
     * Reset the cached manifest (useful for testing).
     *
     * @return void
     */
    public static function resetCache(): void
    {
        self::$manifest = null;
    }
}

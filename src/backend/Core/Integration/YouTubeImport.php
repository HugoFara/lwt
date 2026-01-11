<?php

/**
 * \file
 * \brief Form to import a file from YouTube.
 *
 * You need a personal YouTube API key. Set YT_API_KEY in your .env file.
 *
 * JavaScript functionality moved to src/frontend/js/texts/youtube_import.ts
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Core\Integration;

use Lwt\Core\Bootstrap\EnvLoader;

/**
 * YouTube integration helper class.
 */
class YouTubeImport
{
    /**
     * Get the YouTube API key from environment.
     *
     * @return string|null The API key, or null if not configured
     */
    public static function getApiKey(): ?string
    {
        return EnvLoader::get('YT_API_KEY');
    }

    /**
     * Check if the YouTube API is configured.
     *
     * @return bool True if the API key is set
     */
    public static function isConfigured(): bool
    {
        $key = self::getApiKey();
        return $key !== null && $key !== '';
    }

    /**
     * Output the YouTube import form fragment.
     *
     * The API key is no longer exposed to the client.
     * Instead, requests go through the /api/v1/youtube/video endpoint.
     *
     * @return void
     */
    public static function renderFormFragment(): void
    {
        ?>
<tr>
  <td class="has-text-right">YouTube Video Id:</td>
  <td class="">
    <input type="text" id="ytVideoId" />
    <input type="button" value="Fetch Text from Youtube" data-action="fetch-youtube" />
    <p id="ytDataStatus"></p>
  </td>
</tr>
        <?php
    }
}

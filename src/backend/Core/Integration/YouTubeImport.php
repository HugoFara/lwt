<?php declare(strict_types=1);
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
 */

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
     * @return void
     */
    public static function renderFormFragment(): void
    {
        $apiKey = self::getApiKey() ?? '';
        ?>
<tr>
  <td class="td1 right">YouTube Video Id:</td>
  <td class="td1">
    <input type="text" id="ytVideoId" />
    <input type="button" value="Fetch Text from Youtube" data-action="fetch-youtube" />
    <input type="hidden" id="ytApiKey" value="<?php echo htmlspecialchars($apiKey, ENT_QUOTES, 'UTF-8'); ?>" />
    <p id="ytDataStatus"></p>
  </td>
</tr>
        <?php
    }
}

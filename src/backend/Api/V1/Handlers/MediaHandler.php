<?php

namespace Lwt\Api\V1\Handlers;

/**
 * Handler for media-related API operations.
 *
 * Extracted from api_v1.php.
 */
class MediaHandler
{
    /**
     * List the audio and video files in the media folder.
     *
     * @return string[] Path of media files
     */
    public function getMediaFiles(): array
    {
        return \get_media_paths();
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for media files list.
     *
     * @return string[] Path of media files
     */
    public function formatMediaFiles(): array
    {
        return $this->getMediaFiles();
    }
}

<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Services\MediaService;

/**
 * Handler for media-related API operations.
 *
 * Extracted from api_v1.php.
 */
class MediaHandler
{
    private MediaService $mediaService;

    public function __construct()
    {
        $this->mediaService = new MediaService();
    }

    /**
     * List the audio and video files in the media folder.
     *
     * @return array{base_path: string, paths?: string[], folders?: string[], error?: string}
     */
    public function getMediaFiles(): array
    {
        return $this->mediaService->getMediaPaths();
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for media files list.
     *
     * @return array{base_path: string, paths?: string[], folders?: string[], error?: string}
     */
    public function formatMediaFiles(): array
    {
        return $this->getMediaFiles();
    }
}

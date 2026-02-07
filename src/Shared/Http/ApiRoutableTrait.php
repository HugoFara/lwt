<?php

declare(strict_types=1);

namespace Lwt\Shared\Http;

use Lwt\Api\V1\Response;
use Lwt\Shared\Infrastructure\Http\JsonResponse;

/**
 * Default implementations for ApiRoutableInterface methods.
 *
 * Returns 405 Method Not Allowed for unimplemented HTTP methods.
 * Handlers should override only the methods they support.
 *
 * @since 3.0.0
 */
trait ApiRoutableTrait
{
    public function routeGet(array $fragments, array $params): JsonResponse
    {
        return Response::error('Method Not Allowed', 405);
    }

    public function routePost(array $fragments, array $params): JsonResponse
    {
        return Response::error('Method Not Allowed', 405);
    }

    public function routePut(array $fragments, array $params): JsonResponse
    {
        return Response::error('Method Not Allowed', 405);
    }

    public function routeDelete(array $fragments, array $params): JsonResponse
    {
        return Response::error('Method Not Allowed', 405);
    }

    /**
     * Extract a fragment from the fragments array.
     *
     * @param list<string> $fragments The URL path fragments
     * @param int          $index     The index to extract
     *
     * @return string The fragment at the index, or empty string if not present
     */
    protected function frag(array $fragments, int $index): string
    {
        return $fragments[$index] ?? '';
    }
}

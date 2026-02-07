<?php

declare(strict_types=1);

namespace Lwt\Shared\Http;

use Lwt\Shared\Infrastructure\Http\JsonResponse;

/**
 * Interface for API handlers that participate in route dispatch.
 *
 * Handlers implementing this interface can be dispatched by ApiV1's
 * route map. Each method receives the parsed URL fragments and request
 * parameters, and returns a JsonResponse.
 *
 * Not all handlers need all methods — the default should return 405.
 *
 * @since 3.0.0
 */
interface ApiRoutableInterface
{
    /**
     * Handle a GET request for this resource.
     *
     * @param list<string>         $fragments URL path segments (resource name already consumed)
     * @param array<string, mixed> $params    Query parameters
     */
    public function routeGet(array $fragments, array $params): JsonResponse;

    /**
     * Handle a POST request for this resource.
     *
     * @param list<string>         $fragments URL path segments (resource name already consumed)
     * @param array<string, mixed> $params    POST/JSON body parameters
     */
    public function routePost(array $fragments, array $params): JsonResponse;

    /**
     * Handle a PUT request for this resource.
     *
     * @param list<string>         $fragments URL path segments (resource name already consumed)
     * @param array<string, mixed> $params    JSON body parameters
     */
    public function routePut(array $fragments, array $params): JsonResponse;

    /**
     * Handle a DELETE request for this resource.
     *
     * @param list<string>         $fragments URL path segments (resource name already consumed)
     * @param array<string, mixed> $params    Query/body parameters
     */
    public function routeDelete(array $fragments, array $params): JsonResponse;
}

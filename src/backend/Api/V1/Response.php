<?php

declare(strict_types=1);

namespace Lwt\Api\V1;

use Lwt\Shared\Infrastructure\Http\JsonResponse;

/**
 * Standardized JSON response helper for API V1.
 *
 * Returns JsonResponse objects that can be sent by the caller.
 */
class Response
{
    /**
     * Create JSON response.
     *
     * @param int   $status HTTP status code
     * @param mixed $data   Response data
     *
     * @return JsonResponse
     */
    public static function send(int $status, mixed $data): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    /**
     * Create success response.
     *
     * @param mixed $data   Response data
     * @param int   $status HTTP status code (default 200)
     *
     * @return JsonResponse
     */
    public static function success(mixed $data, int $status = 200): JsonResponse
    {
        return self::send($status, $data);
    }

    /**
     * Create error response.
     *
     * @param string $message Error message
     * @param int    $status  HTTP status code (default 400)
     *
     * @return JsonResponse
     */
    public static function error(string $message, int $status = 400): JsonResponse
    {
        return self::send($status, ['error' => $message]);
    }

    /**
     * Create not found response.
     *
     * @param string $message Error message (default: "Not found")
     *
     * @return JsonResponse
     */
    public static function notFound(string $message = 'Not found'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * Create created response (201).
     *
     * @param mixed $data Response data
     *
     * @return JsonResponse
     */
    public static function created(mixed $data): JsonResponse
    {
        return self::send(201, $data);
    }
}

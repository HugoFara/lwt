<?php

namespace Lwt\Api\V1;

/**
 * Standardized JSON response helper for API V1.
 */
class Response
{
    /**
     * Send JSON response and exit.
     *
     * @param int   $status HTTP status code
     * @param mixed $data   Response data
     *
     * @return never
     */
    public static function send(int $status, mixed $data): never
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    /**
     * Send success response.
     *
     * @param mixed $data Response data
     * @param int   $status HTTP status code (default 200)
     *
     * @return never
     */
    public static function success(mixed $data, int $status = 200): never
    {
        self::send($status, $data);
    }

    /**
     * Send error response.
     *
     * @param string $message Error message
     * @param int    $status  HTTP status code (default 400)
     *
     * @return never
     */
    public static function error(string $message, int $status = 400): never
    {
        self::send($status, ['error' => $message]);
    }
}

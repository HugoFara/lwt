<?php declare(strict_types=1);
/**
 * Middleware Interface
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Router\Middleware
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Router\Middleware;

/**
 * Interface for route middleware.
 *
 * Middleware can inspect and modify requests before they reach controllers,
 * and can halt request processing (e.g., for authentication failures).
 *
 * @category Lwt
 * @package  Lwt\Router\Middleware
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
interface MiddlewareInterface
{
    /**
     * Handle the incoming request.
     *
     * Return true to continue to the next middleware/controller.
     * Return false to halt request processing (middleware should handle response).
     *
     * @return bool True if the request should continue, false to halt
     */
    public function handle(): bool;
}

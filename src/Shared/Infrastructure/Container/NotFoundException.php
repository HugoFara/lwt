<?php

declare(strict_types=1);

/**
 * Not Found Exception
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Shared\Infrastructure\Container
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Shared\Infrastructure\Container;

/**
 * Exception thrown when a requested service is not found in the container.
 *
 * @since 3.0.0
 */
class NotFoundException extends ContainerException
{
}

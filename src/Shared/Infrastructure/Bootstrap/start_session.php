<?php

/**
 * \file
 * \brief Start a PHP session.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Shared\Infrastructure\Bootstrap
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-start-session.html
 * @since   2.0.3-fork
 */

declare(strict_types=1);

namespace Lwt\Shared\Infrastructure\Bootstrap;

// Core utilities (replaces kernel_utility.php)
require_once __DIR__ . '/../Globals.php';
require_once __DIR__ . '/../Utilities/ErrorHandler.php';
require_once __DIR__ . '/SessionBootstrap.php';

use Lwt\Shared\Infrastructure\Globals;

// Initialize globals (this was previously done in settings.php)
Globals::initialize();

SessionBootstrap::bootstrap();

<?php

/**
 * PHPUnit bootstrap file
 *
 * This file ensures proper environment setup for all tests.
 */

declare(strict_types=1);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load EnvLoader and load the .env file
require_once __DIR__ . '/../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;

// Load the .env configuration
EnvLoader::load(__DIR__ . '/../.env');

// Register shutdown function to close database connections
// This prevents zombie connections from holding locks after test interruptions
register_shutdown_function(function () {
    // Close the global database connection if it exists
    if (class_exists('Lwt\Core\Globals', false)) {
        $conn = \Lwt\Core\Globals::getDbConnection();
        if ($conn instanceof \mysqli) {
            @mysqli_close($conn);
        }
    }
});

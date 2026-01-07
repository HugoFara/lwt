<?php

/**
 * PHPUnit bootstrap file
 *
 * This file ensures proper environment setup for all tests.
 *
 * PHP version 8.1
 *
 * @category Testing
 * @package  Lwt\Tests
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Tests;

use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load EnvLoader and Globals
require_once __DIR__ . '/../src/backend/Core/Bootstrap/EnvLoader.php';
require_once __DIR__ . '/../src/backend/Core/Globals.php';

// Load the .env configuration
EnvLoader::load(__DIR__ . '/../.env');

// Set up test database name BEFORE any db_bootstrap.php includes
// This ensures all tests use the test database, not the production one
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

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

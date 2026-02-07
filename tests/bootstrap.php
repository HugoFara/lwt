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

use Lwt\Shared\Infrastructure\Bootstrap\DatabaseBootstrap;
use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load EnvLoader and Globals
require_once __DIR__ . '/../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';
require_once __DIR__ . '/../src/Shared/Infrastructure/Globals.php';

// Initialize Globals
Globals::initialize();

// Load the .env configuration
EnvLoader::load(__DIR__ . '/../.env');

// Set up test database name BEFORE any connection attempts
// This ensures all tests use the test database, not the production one
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

// Attempt to establish database connection
// If this fails (e.g., in CI without a database), tests will skip gracefully
try {
    DatabaseBootstrap::bootstrap();
    define('LWT_TEST_DB_AVAILABLE', true);
} catch (\Throwable $e) {
    // Database not available - tests requiring DB will skip
    define('LWT_TEST_DB_AVAILABLE', false);
}

// Register shutdown function to close database connections
// This prevents zombie connections from holding locks after test interruptions
register_shutdown_function(function () {
    // Close the global database connection if it exists
    if (class_exists('Lwt\Shared\Infrastructure\Globals', false)) {
        $conn = \Lwt\Shared\Infrastructure\Globals::getDbConnection();
        if ($conn instanceof \mysqli) {
            @mysqli_close($conn);
        }
    }
});

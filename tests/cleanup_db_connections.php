<?php

/**
 * Cleanup stale test database connections.
 *
 * This script kills MySQL connections to the test database that have been
 * idle for more than 30 seconds. Run this before tests to prevent blocking
 * from zombie connections left by interrupted test runs.
 *
 * PHP version 8.1
 *
 * @category Testing
 * @package  Lwt\Tests
 * @author   Claude <noreply@anthropic.com>
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Tests;

use Lwt\Core\Bootstrap\EnvLoader;

// Skip if mysqli extension is not available
if (!extension_loaded('mysqli')) {
    exit(0);
}

// Load environment configuration
require_once __DIR__ . '/../src/backend/Core/Bootstrap/EnvLoader.php';

EnvLoader::load(__DIR__ . '/../.env');
$config = EnvLoader::getDatabaseConfig();

$testDbName = 'test_' . $config['dbname'];

// Suppress warnings for connection attempts
$conn = @\mysqli_connect(
    $config['server'],
    $config['userid'],
    $config['passwd'],
    $testDbName,
    socket: $config['socket'] ?? ''
);

if (!$conn) {
    // Test database may not exist yet, which is fine
    exit(0);
}

// Find connections idle for more than 30 seconds
$result = \mysqli_query(
    $conn,
    "SELECT id FROM information_schema.processlist
     WHERE db = '$testDbName'
     AND TIME > 30
     AND id != CONNECTION_ID()"
);

if ($result) {
    $killed = 0;
    while ($row = \mysqli_fetch_row($result)) {
        @\mysqli_query($conn, "KILL " . $row[0]);
        $killed++;
    }
    \mysqli_free_result($result);

    if ($killed > 0) {
        echo "Cleaned up $killed stale database connection(s)\n";
    }
}

\mysqli_close($conn);

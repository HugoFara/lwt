<?php declare(strict_types=1);
/**
 * \file
 * \brief Database bootstrap - establishes connection and initializes globals.
 *
 * This file should be included by any PHP file that needs database access.
 * It loads configuration, establishes the database connection, and sets up
 * the required global state.
 *
 * For new code, prefer using the Lwt\Database\DB class directly after including
 * this bootstrap.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt\Core\Bootstrap
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-database-bootstrap.html
 * @since    3.0.0
 */

namespace Lwt\Core\Bootstrap;

// Core utilities
require_once __DIR__ . '/../Globals.php';
require_once __DIR__ . '/../version.php';

use Lwt\Core\Globals;
use Lwt\Core\EnvLoader;
use Lwt\Database\Configuration;

// Initialize globals (this was previously done in settings.php)
Globals::initialize();
require_once __DIR__ . '/../Utils/string_utilities.php';
require_once __DIR__ . '/../Utils/debug_utilities.php';
require_once __DIR__ . '/../Utils/error_handling.php';

// Database classes
require_once __DIR__ . '/EnvLoader.php';
require_once __DIR__ . '/../Database/PreparedStatement.php';
require_once __DIR__ . '/../Database/Connection.php';
require_once __DIR__ . '/../Database/QueryBuilder.php';
require_once __DIR__ . '/../Database/DB.php';
require_once __DIR__ . '/../Database/Escaping.php';
require_once __DIR__ . '/../Database/Configuration.php';
require_once __DIR__ . '/../Database/Settings.php';
require_once __DIR__ . '/../Database/Validation.php';
require_once __DIR__ . '/../Database/Maintenance.php';
require_once __DIR__ . '/../Database/TextParsing.php';
require_once __DIR__ . '/../Database/SqlFileParser.php';
require_once __DIR__ . '/../Database/Migrations.php';
require_once __DIR__ . '/../Database/Restore.php';

/**
 * Load database configuration from .env file.
 *
 * @return array{
 *     server: string,
 *     userid: string,
 *     passwd: string,
 *     dbname: string,
 *     socket: string
 * }
 *
 * @since 3.0.0
 */
function loadDbConfiguration(): array
{
    $envPath = __DIR__ . '/../../../../.env';

    // Load .env file
    if (EnvLoader::load($envPath)) {
        return EnvLoader::getDatabaseConfig();
    }

    // No configuration found - use defaults
    return [
        'server' => 'localhost',
        'userid' => 'root',
        'passwd' => '',
        'dbname' => 'learning-with-texts',
        'socket' => '',
    ];
}

/**
 * Bootstrap the database connection.
 *
 * This function:
 * 1. Loads configuration from .env
 * 2. Establishes the database connection
 * 3. Determines the table prefix
 * 4. Registers everything with Globals
 * 5. Runs database migrations if needed
 *
 * @return void
 *
 * @since 3.0.0
 */
function bootstrapDatabase(): void
{
    // Skip if already initialized
    if (Globals::getDbConnection() !== null) {
        return;
    }

    // Load configuration
    $config = loadDbConfiguration();


    // Connect to database
    $connection = Configuration::connect(
        $config['server'],
        $config['userid'],
        $config['passwd'],
        $config['dbname'],
        $config['socket']
    );

    // Register connection with Globals
    Globals::setDbConnection($connection);

    // Get table prefix (from .env or database)
    list($prefix, $isFixed) = Configuration::getPrefix($connection);

    // Register prefix with Globals
    Globals::setTablePrefix($prefix, $isFixed);
    Globals::setDatabaseName($config['dbname']);

    // Start timer if needed
    if (Globals::shouldDisplayTime()) {
        \Lwt\Core\Utils\getExecutionTime();
    }

    // Run database migrations
    \Lwt\Database\Migrations::checkAndUpdate();
}

// Run bootstrap
bootstrapDatabase();

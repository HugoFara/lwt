<?php declare(strict_types=1);
/**
 * \file
 * \brief Database bootstrap class - establishes connection and initializes globals.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt\Core\Bootstrap
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Core\Bootstrap;

use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;

/**
 * Database bootstrap utility class.
 *
 * Provides static methods for establishing the database connection
 * and initializing the required global state.
 *
 * @since 3.0.0
 */
class DatabaseBootstrap
{
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
     */
    public static function loadConfiguration(): array
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
     * This method:
     * 1. Loads configuration from .env
     * 2. Establishes the database connection
     * 3. Registers connection with Globals
     * 4. Runs database migrations if needed
     *
     * @return void
     */
    public static function bootstrap(): void
    {
        // Skip if already initialized
        if (Globals::getDbConnection() !== null) {
            return;
        }

        // Load configuration
        $config = self::loadConfiguration();

        // Allow tests to override database name via Globals::setDatabaseName()
        $dbname = Globals::getDatabaseName() ?: $config['dbname'];

        // Connect to database
        $connection = Configuration::connect(
            $config['server'],
            $config['userid'],
            $config['passwd'],
            $dbname,
            $config['socket']
        );

        // Register connection with Globals
        Globals::setDbConnection($connection);
        Globals::setDatabaseName($dbname);

        // Run database migrations
        \Lwt\Shared\Infrastructure\Database\Migrations::checkAndUpdate();

        // Configure multi-user mode from environment
        $multiUserEnabled = EnvLoader::getBool('MULTI_USER_ENABLED', false);
        Globals::setMultiUserEnabled($multiUserEnabled);

        // Configure backup restore (only if explicitly set in env)
        if (EnvLoader::has('BACKUP_RESTORE_ENABLED')) {
            Globals::setBackupRestoreEnabled(EnvLoader::getBool('BACKUP_RESTORE_ENABLED', true));
        }
    }
}

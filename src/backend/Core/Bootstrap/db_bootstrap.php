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
require_once __DIR__ . '/../StringUtils.php';
require_once __DIR__ . '/../Utils/error_handling.php';

use Lwt\Core\Globals;

// Initialize globals (this was previously done in settings.php)
Globals::initialize();

// Database classes
require_once __DIR__ . '/EnvLoader.php';
require_once __DIR__ . '/DatabaseBootstrap.php';
require_once __DIR__ . '/../Database/PreparedStatement.php';
require_once __DIR__ . '/../Database/Connection.php';
require_once __DIR__ . '/../Database/QueryBuilder.php';
require_once __DIR__ . '/../Database/UserScopedQuery.php';
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

// Run bootstrap
DatabaseBootstrap::bootstrap();

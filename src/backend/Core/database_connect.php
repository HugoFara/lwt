<?php

/**
 * \file
 * \brief Connects to the database and check its state.
 *
 * This file is a facade that loads the database bootstrap and deprecated
 * functions for backwards compatibility. New code should use the Bootstrap
 * and Database classes directly.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-database-connect.html
 *
 * @deprecated 3.0.0 This file is a facade for backwards compatibility.
 *             New code should use:
 *             - Core/Bootstrap/db_bootstrap.php for database initialization
 *             - Lwt\Database\DB for queries
 *             - Lwt\Database\Escaping for string escaping
 *             - Lwt\Database\Settings for settings
 *             - etc.
 */

// Load the database bootstrap (establishes connection, sets globals, runs migrations)
// The bootstrap also loads deprecated_functions.php which is needed during migration
require_once __DIR__ . '/Bootstrap/db_bootstrap.php';

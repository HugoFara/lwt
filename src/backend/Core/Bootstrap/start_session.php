<?php

/**
 * \file
 * \brief Start a PHP session.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-start-session.html
 * @since   2.0.3-fork
 */

// Core utilities (replaces kernel_utility.php)
require_once __DIR__ . '/../Globals.php';
require_once __DIR__ . '/../Utils/error_handling.php';

use Lwt\Core\Globals;

// Initialize globals (this was previously done in settings.php)
Globals::initialize();

/**
 * Starts or not the error reporting.
 *
 * @param bool $displayErrors True to start error reporting for ALL errors
 *
 * @return void
 */
function set_error_reporting(bool $displayErrors): void
{
    if ($displayErrors) {
        @error_reporting(E_ALL);
        @ini_set('display_errors', '1');
        @ini_set('display_startup_errors', '1');
    } else {
        @error_reporting(0);
        @ini_set('display_errors', '0');
        @ini_set('display_startup_errors', '0');
    }
}

/**
 * Set configuration values as script limit time and such...
 *
 * @return void
 */
function set_configuration_options(): void
{
    // Set script time limit
    @ini_set('max_execution_time', '600');  // 10 min.
    @set_time_limit(600);  // 10 min.

    @ini_set('memory_limit', '999M');
}

/**
 * Start the session and checks for its sanity.
 *
 * @return void
 */
function start_session(): void
{
    // session isn't started
    $err = @session_start();
    if ($err === false) {
        my_die('SESSION error (Impossible to start a PHP session)');
    }
    if (session_id() == '') {
        my_die('SESSION ID empty (Impossible to start a PHP session)');
    }
    if (!isset($_SESSION)) {
        my_die('SESSION array not set (Impossible to start a PHP session)');
    }
}

/**
 * Launch a new session for WordPress.
 *
 * @return void
 */
function start_session_main(): void
{
    set_error_reporting(Globals::shouldDisplayErrors());
    set_configuration_options();
    // Start a PHP session if not one already exists
    if (session_id() == '') {
        start_session();
    }
}

start_session_main();

<?php

/**
 * LWT Front Controller
 *
 * This file serves as the single entry point for all requests.
 * It routes requests to appropriate handlers while maintaining
 * backward compatibility with the old URL structure.
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/index.html
 * @since   3.0.0
 *
 * "Learning with Texts" (LWT) is free and unencumbered software
 * released into the PUBLIC DOMAIN.
 */

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define base path constant
define('LWT_BASE_PATH', __DIR__);

// Set include path so legacy files can use their original relative paths
// This allows 'inc/session_utility.php' to work from any location
set_include_path(get_include_path() . PATH_SEPARATOR . LWT_BASE_PATH);

// Change to base directory so relative paths work correctly
chdir(LWT_BASE_PATH);

// Autoloader for new classes
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    // Lwt\Router\Router -> src/php/Router/Router.php
    $prefix = 'Lwt\\';
    $baseDir = LWT_BASE_PATH . '/src/php/';

    // Check if class uses our namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get relative class name
    $relativeClass = substr($class, $len);

    // Convert to file path
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // Include if exists
    if (file_exists($file)) {
        require $file;
    }
});

// Check for connect.inc.php
if (!file_exists(LWT_BASE_PATH . '/connect.inc.php')) {
    // Special handling for database wizard
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_contains($requestUri, 'database_wizard') || str_contains($requestUri, 'admin/wizard')) {
        // Allow wizard to run without database connection
        include LWT_BASE_PATH . '/database_wizard.php';
        exit;
    }

    // Show error page
    no_connectinc_error_page();
    exit;
}

/**
 * Echo an error page if connect.inc.php was not found.
 *
 * @return void
 */
function no_connectinc_error_page()
{
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>LWT - Configuration Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
            .container { max-width: 800px; margin: 50px auto; background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
            h1 { color: #d32f2f; border-bottom: 2px solid #d32f2f; padding-bottom: 10px; }
            ul { line-height: 1.8; }
            a { color: #1976d2; text-decoration: none; }
            a:hover { text-decoration: underline; }
            .btn { display: inline-block; padding: 10px 20px; background: #1976d2; color: white; border-radius: 3px; margin: 10px 5px; }
            .btn:hover { background: #1565c0; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚠️ Configuration Required</h1>
            <p class="error">
                <strong>Cannot find file: "connect.inc.php"</strong>
            </p>
            <p>Please do one of the following:</p>
            <ul>
                <li>
                    Rename the correct file <code>connect_[servertype].inc.php</code> to <code>connect.inc.php</code><br>
                    <small>([servertype] is the name of your server: xampp, mamp, or easyphp)</small>
                </li>
                <li>
                    <a href="/admin/wizard" class="btn">Use the Database Setup Wizard</a>
                </li>
            </ul>
            <p>
                <strong>Documentation:</strong>
                <a href="https://hugofara.github.io/lwt/README.md" target="_blank">
                    https://hugofara.github.io/lwt/README.md
                </a>
            </p>
        </div>
    </body>
    </html>
    <?php
}

// Initialize router
use Lwt\Router\Router;

$router = new Router();

// Load route configuration
$registerRoutes = require LWT_BASE_PATH . '/src/php/Router/routes.php';
$registerRoutes($router);

// Resolve and execute the request
$resolution = $router->resolve();
$router->execute($resolution);

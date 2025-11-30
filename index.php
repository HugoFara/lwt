<?php declare(strict_types=1);
/**
 * LWT Front Controller
 *
 * This file serves as the single entry point for all requests.
 * It bootstraps the application and delegates to the Application class.
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

// Define base path constant
define('LWT_BASE_PATH', __DIR__);

// Load the Application class
require_once LWT_BASE_PATH . '/src/backend/Application.php';

// Create and run the application
$app = new \Lwt\Application(LWT_BASE_PATH);
$app->bootstrap();
$app->run();

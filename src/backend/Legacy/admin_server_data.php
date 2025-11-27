<?php

/**
 * Useful software data.
 *
 * Call: /admin/server-data
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/index.html
 * @since    2.7.0
 */

namespace Lwt\Interface\ServerData;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';

use Lwt\Services\ServerDataService;

require_once __DIR__ . '/../Services/ServerDataService.php';

// Initialize service and get data (used by included view)
$serverDataService = new ServerDataService();
/** @psalm-suppress UnusedVariable - Variables used by included view */
$data = $serverDataService->getServerData();

// Render page
pagestart("Server Data", true);

// Include the view
include __DIR__ . '/../Views/Admin/server_data.php';

pageend();

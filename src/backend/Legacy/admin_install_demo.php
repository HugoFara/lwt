<?php

/**
 * Install LWT Demo Database
 *
 * Call: /admin/install-demo
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 */

namespace Lwt\Interface\InstallDemo;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Text/text_helpers.php';

use Lwt\Services\DemoService;

require_once __DIR__ . '/../Services/DemoService.php';

// Initialize service
$demoService = new DemoService();
$message = '';

// Handle install request
if (isset($_REQUEST['install'])) {
    $message = $demoService->installDemo();
}

// Get view data (used by included view)
/** @psalm-suppress UnusedVariable - Variables used by included view */
$prefinfo = $demoService->getPrefixInfo();
/** @psalm-suppress UnusedVariable - Variables used by included view */
$dbname = $demoService->getDatabaseName();
/** @psalm-suppress UnusedVariable - Variables used by included view */
$langcnt = $demoService->getLanguageCount();

// Render page
pagestart('Install LWT Demo Database', true);

echo error_message_with_hide($message, true);

// Include the view
include __DIR__ . '/../Views/Admin/install_demo.php';

pageend();

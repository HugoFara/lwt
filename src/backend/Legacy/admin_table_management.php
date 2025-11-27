<?php

/**
 * Analyse DB tables, and manage Table Sets
 *
 * Call: /admin/tables
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 */

namespace Lwt\Interface\TableManagement;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';

use Lwt\Services\TableSetService;

require_once __DIR__ . '/../Services/TableSetService.php';

// Initialize service
$tableSetService = new TableSetService();
$message = "";

// Handle operations
if (isset($_REQUEST['delpref'])) {
    $message = $tableSetService->deleteTableSet($_REQUEST['delpref']);
} elseif (isset($_REQUEST['newpref'])) {
    $result = $tableSetService->createTableSet($_REQUEST['newpref']);
    if ($result['redirect']) {
        header("Location: /");
        exit();
    }
    $message = $result['message'];
} elseif (isset($_REQUEST['prefix'])) {
    $result = $tableSetService->selectTableSet($_REQUEST['prefix']);
    if ($result['redirect']) {
        header("Location: /");
        exit();
    }
}

// Get view data (used by included view)
/** @psalm-suppress UnusedVariable - Variables used by included view */
$fixedTbpref = $tableSetService->isFixedPrefix();
/** @psalm-suppress UnusedVariable - Variables used by included view */
$tbpref = $tableSetService->getCurrentPrefix();
/** @psalm-suppress UnusedVariable - Variables used by included view */
$prefixes = $tableSetService->getPrefixes();

// Render page
pagestart('Select, Create or Delete a Table Set', false);
echo error_message_with_hide($message, false);

// Include the view
include __DIR__ . '/../Views/Admin/table_management.php';

pageend();

<?php

/**
 * Create / Edit database connection
 *
 * Call: /admin/wizard
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/database-wizard.html
 * @since    2.5.0-fork
 */

namespace Lwt\Interface\DatabaseWizard;

require_once 'Core/Http/param_helpers.php';
require_once 'Core/UI/ui_helpers.php';

use Lwt\Services\DatabaseWizardService;

require_once __DIR__ . '/../Services/DatabaseWizardService.php';

// Initialize service
$wizardService = new DatabaseWizardService();

/**
 * @psalm-suppress UnusedVariable - Variables used by included view
 * @var \Lwt\Services\DatabaseConnection|null $conn
 */
$conn = null;

/**
 * @psalm-suppress UnusedVariable - Variables used by included view
 * @var string|null $errorMessage
 */
$errorMessage = null;

// Handle operations
$op = getreq('op');
if ($op != '') {
    if ($op == "Autocomplete") {
        $conn = $wizardService->autocompleteConnection();
    } elseif ($op == "Check") {
        $conn = $wizardService->createConnectionFromForm($_REQUEST);
        $errorMessage = $wizardService->testConnection($conn);
    } elseif ($op == "Change") {
        $conn = $wizardService->createConnectionFromForm($_REQUEST);
        $wizardService->saveConnection($conn);
    }
} elseif ($wizardService->envFileExists()) {
    $conn = $wizardService->loadConnection();
} else {
    $conn = $wizardService->createEmptyConnection();
}

// Render page
pagestart_kernel_nobody("Database Connection Wizard");

// Include the view
include __DIR__ . '/../Views/Admin/wizard.php';

pageend();

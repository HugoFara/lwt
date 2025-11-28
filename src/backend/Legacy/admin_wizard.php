<?php

/**
 * Create / Edit database connection
 *
 * This file is a standalone wizard that can run without a database connection.
 * It's called directly from index.php when no .env file exists.
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

// Minimal includes - no database required
require_once __DIR__ . '/../Services/DatabaseWizardService.php';

use Lwt\Services\DatabaseWizardService;

/**
 * Simple getreq function for wizard (no db_bootstrap dependency).
 *
 * @param string $s Request key
 *
 * @return string Value or empty string
 */
function getreq_wizard(string $s): string
{
    if (isset($_REQUEST[$s])) {
        $val = $_REQUEST[$s];
        return is_string($val) ? trim($val) : '';
    }
    return '';
}

// Initialize service
$wizardService = new DatabaseWizardService();

/** @var \Lwt\Services\DatabaseConnection|null $conn */
$conn = null;

/** @var string|null $errorMessage */
$errorMessage = null;

// Handle operations
$op = getreq_wizard('op');
if ($op != '') {
    if ($op == "Autocomplete") {
        $conn = $wizardService->autocompleteConnection();
    } elseif ($op == "Check") {
        $conn = $wizardService->createConnectionFromForm($_REQUEST);
        $errorMessage = $wizardService->testConnection($conn);
    } elseif ($op == "Change") {
        $conn = $wizardService->createConnectionFromForm($_REQUEST);
        $wizardService->saveConnection($conn);
        // Redirect to home after saving
        header("Location: /");
        exit;
    }
} elseif ($wizardService->envFileExists()) {
    $conn = $wizardService->loadConnection();
} else {
    $conn = $wizardService->createEmptyConnection();
}

// Simple page output without requiring ui_helpers
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=900" />
    <title>LWT - Database Connection Wizard</title>
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .container { max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #1976d2; padding-bottom: 10px; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 3px; }
        input[type="submit"] { margin-top: 20px; padding: 10px 20px; background: #1976d2; color: white; border: none; border-radius: 3px; cursor: pointer; }
        input[type="submit"]:hover { background: #1565c0; }
        .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 3px; margin-bottom: 20px; }
        .success { color: #388e3c; background: #e8f5e9; padding: 10px; border-radius: 3px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Database Connection Wizard</h1>

    <?php if ($errorMessage !== null): ?>
    <p class="<?php echo str_contains($errorMessage, 'Success') ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($errorMessage); ?>
    </p>
    <?php endif; ?>

    <form name="database_connect" action="" method="post">
        <p>
            <label for="server">Server address:</label>
            <input type="text" name="server" id="server"
            value="<?php echo htmlspecialchars($conn->server ?? ''); ?>"
            placeholder="localhost">
        </p>
        <p>
            <label for="userid">Database User Name:</label>
            <input type="text" name="userid" id="userid"
            value="<?php echo htmlspecialchars($conn->userid ?? ''); ?>"
            placeholder="root">
        </p>
        <p>
            <label for="passwd">Password:</label>
            <input type="password" name="passwd" id="passwd"
            value="<?php echo htmlspecialchars($conn->passwd ?? ''); ?>"
            placeholder="">
        </p>
        <p>
            <label for="dbname">Database Name:</label>
            <input type="text" name="dbname" id="dbname"
            value="<?php echo htmlspecialchars($conn->dbname ?? ''); ?>"
            placeholder="learning-with-texts">
        </p>
        <p>
            <label for="socket">Socket Name (optional):</label>
            <input type="text" name="socket" id="socket"
            value="<?php echo htmlspecialchars($conn->socket ?? ''); ?>"
            placeholder="/var/run/mysql.sock">
        </p>
        <input type="submit" name="op" value="Autocomplete" />
        <input type="submit" name="op" value="Check" />
        <input type="submit" name="op" value="Change" />
    </form>
</div>
</body>
</html>

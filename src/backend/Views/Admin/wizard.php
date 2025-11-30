<?php

/**
 * Database Wizard View (Standalone)
 *
 * This view is standalone and can run without database connection.
 * It includes its own HTML structure (not using standard LWT layout).
 *
 * Variables expected:
 * - $conn: DatabaseConnection object with current values
 * - $errorMessage: string|null Error message to display
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Admin;

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=900" />
    <title>LWT - Database Connection Wizard</title>
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>
    <link rel="stylesheet" href="/assets/css/standalone.css" type="text/css"/>
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

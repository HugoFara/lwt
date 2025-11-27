<?php

/**
 * Database Wizard View
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

if ($errorMessage !== null): ?>
<p><?php echo htmlspecialchars($errorMessage); ?></p>
<?php endif; ?>

<form name="database_connect" action="<?php echo $_SERVER['PHP_SELF']; ?>"
method="post">
    <p>
        <label for="server">Server address:</label>
        <input type="text" name="server" id="server"
        value="<?php echo htmlspecialchars($conn->server) ?>" required
        placeholder="localhost">
    </p>
    <p>
        <label for="userid">Database User Name:</label>
        <input type="text" name="userid" id="userid"
        value="<?php echo htmlspecialchars($conn->userid); ?>" required
        placeholder="root">
    </p>
    <p>
        <label for="passwd">Password:</label>
        <input type="password" name="passwd" id="passwd"
        value="<?php echo htmlspecialchars($conn->passwd); ?>"
        placeholder="abcxyz">
    </p>
    <p>
        <label for="dbname">Database Name:</label>
        <input type="text" name="dbname" id="dbname"
        value="<?php echo htmlspecialchars($conn->dbname); ?>" required
        placeholder="lwt">
    </p>
    <p>
        <label for="socket">Socket Name:</label>
        <input type="text" name="socket" id="socket"
        value="<?php echo htmlspecialchars($conn->socket); ?>" required
        placeholder="/var/run/mysql.sock">
    </p>
    <input type="submit" name="op" value="Autocomplete" />
    <input type="submit" name="op" value="Check" />
    <input type="submit" name="op" value="Change" />
</form>

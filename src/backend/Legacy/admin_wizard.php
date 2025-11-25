<?php

/**
 * \file
 * \brief Create / Edit database connection
 *
 * Call: database_wizard.php
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

namespace Lwt\Interface\Database_Wizard;

require_once 'Core/kernel_utility.php';

/**
 * A connection to database stored as an object.
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/database-wizard.html
 */
class Database_Connection
{
    /**
     * @var string Server name
     */
    public string $server;
    /**
     * @var string $userid User ID
     */
    public string $userid;
    /**
     * @var string $passwd Password for this user
     */
    public string $passwd;
    /**
     * @var string $dbname Database name
     */
    public string $dbname;
    /**
     * @var string $socket Socket to use
     */
    public string $socket;

    /**
     * Build a new connection object.
     *
     * @param string $server Server name
     * @param string $userid User ID
     * @param string $passwd Password for this user
     * @param string $dbname Database name
     * @param string $socket Socket to use
     */
    public function __construct(
        $server = "",
        $userid = "",
        $passwd = "",
        $dbname = "",
        $socket = ""
    ) {
        $this->server = $server;
        $this->userid = $userid;
        $this->passwd = $passwd;
        $this->dbname = $dbname;
        $this->socket = $socket;
    }

    /**
     * Load data from a PHP file.
     *
     * The file is usually connect.inc.php or equivalent.
     *
     * @param string $file_name PHP file to load data from.
     *
     * @return void
     */
    public function loadFile(string $file_name)
    {
        include $file_name;
        $this->server = $server;
        $this->userid = $userid;
        $this->passwd = $passwd;
        $this->dbname = $dbname;
        $this->socket = $socket;
    }

    /**
     * Connection a PHP formatted string.
     *
     * @return string PHP string representing connection details
     */
    public function getAsText(): string
    {
        return '<?php

        /**
         * \file
         * \brief DB variables for MAMP
         */

        $server = "' . $this->server . '";
        $userid = "' . $this->userid . '";
        $passwd = "' . $this->passwd . '";
        $dbname = "' . $this->dbname . '";
        $socket = "' . $this->socket . '";

        ?>
        ';
    }

}

/**
 * Save the connection to the file connect.inc.php.
 *
 * @param Database_Connection $conn Connection object.
 *
 * @return void
 */
function writeToFile($conn)
{
    $handle = fopen(__DIR__ . "/connect.inc.php", 'w');
    fwrite($handle, $conn->getAsText());
    fclose($handle);
}

/**
 * Execute operation.
 *
 * @param string $op Operation to execute.
 *
 * @return void
 */
function doOperation($op)
{
    $message = null;
    $server = null;
    $userid = null;
    $passwd = null;
    $dbname = null;
    $socket = null;
    if ($op == "Autocomplete") {
        $server = (string) $_SERVER['SERVER_ADDR'];
        $userid = "";
        $passwd = "";
        $dbname = (string) $_SERVER['SERVER_NAME'];
        $socket = "";
    } elseif ($op == "Check") {
        $server = getreq("server");
        $userid = getreq("userid");
        $passwd = getreq("passwd");
        $dbname = getreq("dbname");
        $socket = getreq("socket");
        $conn = mysqli_init();
        if ($conn === false) {
            $message = "MySQL is not accessible!";
        } else {
            try {
                if ($socket != "") {
                    $success = mysqli_real_connect(
                        $conn,
                        $server,
                        $userid,
                        $passwd,
                        $dbname,
                        socket: $socket
                    );
                } else {
                    $success = mysqli_real_connect(
                        $conn,
                        $server,
                        $userid,
                        $passwd,
                        $dbname
                    );
                }
                if (!$success) {
                    $message = "Can't connect!";
                } elseif (mysqli_errno($conn) != 0) {
                    $message = "ERROR: " . mysqli_error($conn) .
                    " error number: " . mysqli_errno($conn);
                } else {
                    $message = "Connection established with success!";
                }
            } catch (\Exception $exept) {
                $message = (string)$exept;
            }
        }
    } elseif ($op == "Change") {
        $server = getreq("server");
        $userid = getreq("userid");
        $passwd = getreq("passwd");
        $dbname = getreq("dbname");
        $socket = getreq("socket");
    }
    $conn = new Database_Connection(
        $server,
        $userid,
        $passwd,
        $dbname,
        $socket
    );
    if ($op == "Change") {
        writeToFile($conn);
    }
    displayForm($conn, $message);
}

/**
 * Generate a form to edit the connection.
 *
 * @param Database_Connection $conn          Database connection object
 * @param string|null         $error_message Error message to display
 *
 * @return void
 */
function displayForm($conn, $error_message = null)
{
    pagestart_kernel_nobody("Database Connection Wizard");
    if ($error_message != null) {
        echo $error_message;
    }
    ?>
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
    <?php
    pageend();
}

/**
 * Display the main form, filled with data from an existing connection file.
 *
 * @return void
 */
function editConnection()
{
    $conn = new Database_Connection();
    // May be dangerous to expose passwords in clear
    $conn->loadFile('connect.inc.php');
    displayForm($conn);
}

/**
 * Display the main form blank.
 *
 * @return void
 */
function createNewConnection()
{
    displayForm(new Database_Connection());
}

if (getreq('op') != '') {
    doOperation(getreq('op'));
} elseif (file_exists('connect.inc.php')) {
    editConnection();
} else {
    createNewConnection();
}

?>

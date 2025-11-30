<?php declare(strict_types=1);
/**
 * \file
 * \brief Base Controller for MVC architecture
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-basecontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Core\Http\InputValidator;
use Lwt\Core\Http\ParamHelpers;
use Lwt\Database\Connection;
use Lwt\Database\DB;
use Lwt\Database\Escaping;
use Lwt\View\Helper\PageLayoutHelper;

require_once __DIR__ . '/../Core/Http/param_helpers.php';
require_once __DIR__ . '/../Core/Http/InputValidator.php';
require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';

/**
 * Abstract base controller providing common functionality for all controllers.
 *
 * Controllers should extend this class to access database connections,
 * utility functions, and rendering helpers.
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
abstract class BaseController
{
    /**
     * Database table prefix (from LWT settings)
     *
     * @var string
     */
    protected string $tbpref;

    /**
     * Database connection (may be null if session_utility.php not yet loaded)
     *
     * @var \mysqli|null
     */
    protected ?\mysqli $db = null;

    /**
     * Initialize controller with database connection and table prefix.
     *
     * Note: The database connection may not be available until Globals are
     * loaded by the controller action.
     */
    public function __construct()
    {
        $this->tbpref = \Lwt\Core\Globals::getTablePrefix();
        $this->db = \Lwt\Core\Globals::getDbConnection();
    }

    /**
     * Start page rendering with standard LWT header.
     *
     * @param string $title    Page title
     * @param bool   $showMenu Whether to show navigation menu (default: true)
     *
     * @return void
     */
    protected function render(string $title, bool $showMenu = true): void
    {
        PageLayoutHelper::renderPageStart($title, $showMenu);
    }

    /**
     * End page rendering with standard LWT footer.
     *
     * @return void
     */
    protected function endRender(): void
    {
        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Display a message (success/error) to the user.
     *
     * @param string $message  The message to display
     * @param bool   $autoHide Whether to auto-hide the message (default: true)
     *
     * @return void
     */
    protected function message(string $message, bool $autoHide = true): void
    {
        if (trim($message) == '') {
            return;
        }
        if (substr($message, 0, 5) == "Error") {
            echo '<p class="red">*** ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . ' ***' .
                ($autoHide ?
                '' :
                '<br /><input type="button" value="&lt;&lt; Go back and correct &lt;&lt;" data-action="history-back" />' ) .
                '</p>';
        } else {
            echo '<p id="hide3" class="msgblue">+++ ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . ' +++</p>';
        }
    }

    /**
     * Get a string request parameter (GET, POST, or REQUEST).
     *
     * @param string $key     Parameter name
     * @param string $default Default value if not set
     *
     * @return string Parameter value or default
     */
    protected function param(string $key, string $default = ''): string
    {
        return InputValidator::getString($key, $default);
    }

    /**
     * Check if a parameter exists in the request.
     *
     * @param string $key Parameter name
     *
     * @return bool True if the parameter exists
     */
    protected function hasParam(string $key): bool
    {
        return InputValidator::has($key);
    }

    /**
     * Get a string GET parameter.
     *
     * @param string $key     Parameter name
     * @param string $default Default value if not set
     *
     * @return string Parameter value or default
     */
    protected function get(string $key, string $default = ''): string
    {
        return InputValidator::getStringFromGet($key, $default);
    }

    /**
     * Get a string POST parameter.
     *
     * @param string $key     Parameter name
     * @param string $default Default value if not set
     *
     * @return string Parameter value or default
     */
    protected function post(string $key, string $default = ''): string
    {
        return InputValidator::getStringFromPost($key, $default);
    }

    /**
     * Get an integer request parameter.
     *
     * @param string   $key     Parameter name
     * @param int|null $default Default value if not set
     * @param int|null $min     Minimum allowed value
     * @param int|null $max     Maximum allowed value
     *
     * @return int|null Parameter value or default
     */
    protected function paramInt(string $key, ?int $default = null, ?int $min = null, ?int $max = null): ?int
    {
        return InputValidator::getInt($key, $default, $min, $max);
    }

    /**
     * Get a required integer request parameter.
     *
     * @param string   $key Parameter name
     * @param int|null $min Minimum allowed value
     * @param int|null $max Maximum allowed value
     *
     * @return int Parameter value
     *
     * @throws \InvalidArgumentException If parameter is missing or invalid
     */
    protected function requireInt(string $key, ?int $min = null, ?int $max = null): int
    {
        return InputValidator::requireInt($key, $min, $max);
    }

    /**
     * Get an array request parameter.
     *
     * @param string $key     Parameter name
     * @param array  $default Default value if not set
     *
     * @return array Parameter value or default
     */
    protected function paramArray(string $key, array $default = []): array
    {
        return InputValidator::getArray($key, $default);
    }

    /**
     * Check if the request is a POST request.
     *
     * @return bool
     */
    protected function isPost(): bool
    {
        return InputValidator::isPost();
    }

    /**
     * Check if the request is a GET request.
     *
     * @return bool
     */
    protected function isGet(): bool
    {
        return InputValidator::isGet();
    }

    /**
     * Redirect to another URL.
     *
     * @param string $url        URL to redirect to
     * @param int    $statusCode HTTP status code (default: 302)
     *
     * @return never
     */
    protected function redirect(string $url, int $statusCode = 302)
    {
        header("Location: $url", true, $statusCode);
        exit;
    }

    /**
     * Execute a database query using the LWT query wrapper.
     *
     * @param string $sql SQL query
     *
     * @return \mysqli_result|bool Query result
     */
    protected function query(string $sql): \mysqli_result|bool
    {
        return Connection::query($sql);
    }

    /**
     * Execute an INSERT/UPDATE/DELETE query.
     *
     * @param string $sql SQL query
     *
     * @return int Number of affected rows
     */
    protected function execute(string $sql): int
    {
        return DB::execute($sql);
    }

    /**
     * Get a single value from the database.
     *
     * @param string $sql SQL query (should return single value as 'value')
     *
     * @return mixed The value or null
     */
    protected function getValue(string $sql): mixed
    {
        return Connection::fetchValue($sql);
    }

    /**
     * Get the table name with prefix.
     *
     * @param string $table Table name without prefix
     *
     * @return string Table name with prefix
     */
    protected function table(string $table): string
    {
        return $this->tbpref . $table;
    }

    /**
     * Escape a string for safe SQL insertion.
     *
     * @param string $value String to escape
     *
     * @return string Escaped string suitable for SQL (with quotes)
     */
    protected function escape(string $value): string
    {
        return Escaping::toSqlSyntax($value);
    }

    /**
     * Escape a string for safe SQL insertion (returns empty string instead of NULL).
     *
     * @param string $value String to escape
     *
     * @return string Escaped string suitable for SQL (with quotes)
     */
    protected function escapeNonNull(string $value): string
    {
        return Escaping::toSqlSyntaxNoNull($value);
    }

    /**
     * Return JSON response and exit.
     *
     * @param mixed $data   Data to encode as JSON
     * @param int   $status HTTP status code (default: 200)
     *
     * @return never
     */
    protected function json(mixed $data, int $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Get IDs from marked checkboxes.
     *
     * @param string|array $marked The 'marked' request parameter value
     *
     * @return int[] Array of integer IDs
     *
     * @psalm-return array<int>
     */
    protected function getMarkedIds(string|array $marked): array
    {
        if (!is_array($marked)) {
            return [];
        }
        return array_map('intval', $marked);
    }

    /**
     * Process a session parameter using LWT utility.
     *
     * @param string $reqKey    Request parameter key
     * @param string $sessKey   Session key
     * @param mixed  $default   Default value
     * @param bool   $isNumeric Whether the value is numeric
     *
     * @return mixed The processed value
     */
    protected function sessionParam(
        string $reqKey,
        string $sessKey,
        mixed $default,
        bool $isNumeric = false
    ): mixed {
        return ParamHelpers::processSessParam($reqKey, $sessKey, $default, $isNumeric);
    }

    /**
     * Process a database setting parameter using LWT utility.
     *
     * @param string $reqKey    Request parameter key
     * @param string $dbKey     Database setting key
     * @param mixed  $default   Default value
     * @param bool   $isNumeric Whether the value is numeric
     *
     * @return mixed The processed value
     */
    protected function dbParam(
        string $reqKey,
        string $dbKey,
        mixed $default,
        bool $isNumeric = false
    ): mixed {
        return ParamHelpers::processDBParam($reqKey, $dbKey, $default, $isNumeric);
    }
}

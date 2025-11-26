<?php

/**
 * \file
 * \brief Useful software data.
 *
 * Call: server_data.php
 *
 * PHP version 8.1.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/index.html
 * @since    2.7.0
 */

namespace Lwt\Interface\Server\Data;

require_once 'Core/session_utility.php';

/**
 * Return a lot of different server state variables.
 *
 * @return (false|float|string)[]
 *
 * @global string $tbpref Database table prefix
 * @global string $dbname Database name
 *
 * @psalm-return array{db_name: string, db_prefix: string, db_size: float, server_soft: string, apache: string, php: false|string, mysql: string}
 */
function get_server_data_table(): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $dbname = \Lwt\Core\LWT_Globals::getDatabaseName();
    $dbaccess_format = convert_string_to_sqlsyntax($dbname);
    $data_table = array();
    $data_table["db_name"] = $dbname;
    $data_table["db_prefix"] = $tbpref;
    $temp_size = get_first_value(
        "SELECT ROUND(SUM(data_length+index_length)/1024/1024, 1) AS value
        FROM information_schema.TABLES
        WHERE table_schema = $dbaccess_format
        AND table_name IN (
            '{$tbpref}archivedtexts', '{$tbpref}archtexttags',
            '{$tbpref}feedlinks', '{$tbpref}languages',
            '{$tbpref}newsfeeds', '{$tbpref}sentences', '{$tbpref}settings',
            '{$tbpref}tags', '{$tbpref}tags2',
            '{$tbpref}textitems2', '{$tbpref}texts', '{$tbpref}texttags',
            '{$tbpref}words', '{$tbpref}wordtags'
        )"
    );
    if ($temp_size === null) {
        $data_table["db_size"] = 0.0;
    } else {
        $data_table["db_size"] = floatval($temp_size);
    }

    $data_table["server_soft"] = $_SERVER['SERVER_SOFTWARE'];
    $data_table["apache"] = "Apache/?";
    if (str_starts_with($data_table["server_soft"], "Apache/")) {
        $temp_soft = explode(' ', $data_table["server_soft"]);
        $data_table["apache"] = $temp_soft[0];
    }
    $data_table["php"] = phpversion();
    $data_table["mysql"] = (string)get_first_value("SELECT VERSION() AS value");
    return $data_table;
}

/**
 * Display the main content for the page.
 *
 * @return void
 */
function display_content(): void
{
    $data = get_server_data_table();

    pagestart("Server Data", true);
    ?>
<h2>Server</h2>
<table>
    <thead>
        <tr>
            <th>Data</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>LWT version</td>
            <td><?php echo get_version_number(); ?></td>
        </tr>
        <tr>
            <td>
                <a href="https://en.wikipedia.org/wiki/Web_server" target="_blank">
                    Web Server
                </a>
            </td>
            <td><i><?php echo $data["server_soft"]; ?></i></td>
        </tr>
        <tr>
            <td>Server Software</td>
            <td>
                <a href="https://en.wikipedia.org/wiki/Apache_HTTP_Server"
                target="_blank">
                    <?php echo $data["apache"]; ?>
                </a>
            </td>
        </tr>
        <tr>
            <td>Server Location</td>
            <td><i><?php echo $_SERVER['HTTP_HOST']; ?></i></td>
        </tr>
        <tr>
            <td>
                <a href="https://en.wikipedia.org/wiki/PHP" target="_blank">
                    PHP
                </a> Version
            </td>
            <td><?php echo $data["php"]; ?></td>
        </tr>
    </tbody>
</table>
<h2>Database</h2>
<table>
    <thead>
        <tr>
            <th>Data</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <a href="https://en.wikipedia.org/wiki/Database" target="_blank">
                    Database
                </a> name</td>
            <td><i><?php echo $data["db_name"]; ?></i></td>
        </tr>
        <tr>
            <td>Database prefix (surrounded by "")</td>
            <td>"<?php echo $data["db_prefix"]; ?>"</td>
        </tr>
        <tr>
            <td>Database Size</td>
            <td><?php echo $data["db_size"]; ?> MB</td>
        </tr>
        <tr>
            <td>
                <a href="https://en.wikipedia.org/wiki/MySQL" target="_blank">
                    MySQL
                </a> Version
            </td>
            <td><?php echo $data["mysql"]; ?></td>
        </tr>
    </tbody>
</table>
<h2>Client API</h2>
<table>
    <thead>
        <tr>
            <th>Data</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <a href="https://en.wikipedia.org/wiki/REST">
                    REST API
                </a> Version
            </td>
            <td id="rest-api-version"><!-- JS inserts version here --></td>
        </tr>
        <tr>
            <td>
                <a href="https://en.wikipedia.org/wiki/REST">
                    REST API
                </a> Release date
            </td>
            <td id="rest-api-release-date"><!-- JS acts here --></td>
        </tr>
    </tbody>
</table>
<script type="text/javascript">
    function handle_api_version_answer(data) {
        if ("error" in data) {
            $("#rest-api-version").text(
                "Error while getting data from the REST API!" +
                "\nMessage: " + data.error
            );
            $("#rest-api-release-date").empty();
        } else {
            $("#rest-api-version").text(data.version);
            $("#rest-api-release-date").text(data.release_date);
        }
    }

    $.getJSON(
        "api.php/v1/version",
        {},
        handle_api_version_answer
    );
</script>
    <?php
    pageend();
}

display_content();

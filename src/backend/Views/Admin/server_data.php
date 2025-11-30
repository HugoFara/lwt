<?php declare(strict_types=1);
/**
 * Server Data View
 *
 * Variables expected:
 * - $data: array Server data from ServerDataService::getServerData()
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
            <td><?php echo $data["lwt_version"]; ?></td>
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
            <td><i><?php echo $data["server_location"]; ?></i></td>
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
<!-- API version is fetched by admin/server_data.ts module -->

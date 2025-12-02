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
<p>This page shows server information useful for debugging and issue reports.</p>

<h2>Server</h2>
<table class="tab3" cellspacing="0" cellpadding="5">
    <tbody>
        <tr>
            <th class="th1">LWT version</th>
            <td class="td1"><?php echo $data["lwt_version"]; ?></td>
        </tr>
        <tr>
            <th class="th1">
                <a href="https://en.wikipedia.org/wiki/Web_server" target="_blank">
                    Web Server
                </a>
            </th>
            <td class="td1"><?php echo $data["server_soft"]; ?></td>
        </tr>
        <tr>
            <th class="th1">Server Software</th>
            <td class="td1">
                <a href="https://en.wikipedia.org/wiki/Apache_HTTP_Server"
                target="_blank">
                    <?php echo $data["apache"]; ?>
                </a>
            </td>
        </tr>
        <tr>
            <th class="th1">Server Location</th>
            <td class="td1"><?php echo $data["server_location"]; ?></td>
        </tr>
        <tr>
            <th class="th1">
                <a href="https://en.wikipedia.org/wiki/PHP" target="_blank">
                    PHP
                </a> Version
            </th>
            <td class="td1"><?php echo $data["php"]; ?></td>
        </tr>
    </tbody>
</table>

<h2>Database</h2>
<table class="tab3" cellspacing="0" cellpadding="5">
    <tbody>
        <tr>
            <th class="th1">
                <a href="https://en.wikipedia.org/wiki/Database" target="_blank">
                    Database
                </a> name
            </th>
            <td class="td1"><?php echo $data["db_name"]; ?></td>
        </tr>
        <tr>
            <th class="th1">Database prefix</th>
            <td class="td1">"<?php echo $data["db_prefix"]; ?>"</td>
        </tr>
        <tr>
            <th class="th1">Database Size</th>
            <td class="td1"><?php echo $data["db_size"]; ?> MB</td>
        </tr>
        <tr>
            <th class="th1">
                <a href="https://en.wikipedia.org/wiki/MySQL" target="_blank">
                    MySQL
                </a> Version
            </th>
            <td class="td1"><?php echo $data["mysql"]; ?></td>
        </tr>
    </tbody>
</table>

<h2>Client API</h2>
<table class="tab3" cellspacing="0" cellpadding="5">
    <tbody>
        <tr>
            <th class="th1">
                <a href="https://en.wikipedia.org/wiki/REST" target="_blank">
                    REST API
                </a> Version
            </th>
            <td class="td1" id="rest-api-version">Loading...</td>
        </tr>
        <tr>
            <th class="th1">
                <a href="https://en.wikipedia.org/wiki/REST" target="_blank">
                    REST API
                </a> Release date
            </th>
            <td class="td1" id="rest-api-release-date">Loading...</td>
        </tr>
    </tbody>
</table>

<p style="margin-top: 20px;">
    <input type="button" value="&lt;&lt; Back" data-action="navigate" data-url="/" />
</p>
<!-- API version is fetched by admin/server_data.ts module -->

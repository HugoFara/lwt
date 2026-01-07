<?php declare(strict_types=1);
/**
 * Server Data View
 *
 * Modern Bulma + Alpine.js version of the server data page.
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

use Lwt\Shared\UI\Helpers\IconHelper;

/**
 * @var array{
 *     lwt_version?: string,
 *     server_soft?: string,
 *     apache?: string,
 *     server_location?: string,
 *     php?: string,
 *     db_name?: string,
 *     db_size?: float|int|string,
 *     mysql?: string
 * } $data Server data from ServerDataService::getServerData()
 */
$data = is_array($data ?? null) ? $data : [];

?>
<div class="container" x-data="serverDataApp()">
    <p class="mb-4">This page shows server information useful for debugging and issue reports.</p>

    <!-- Server Section -->
    <div class="box mb-4">
        <h2 class="title is-4">
            <span class="icon-text">
                <span class="icon has-text-info">
                    <?php echo IconHelper::render('server', ['class' => 'icon']); ?>
                </span>
                <span>Server</span>
            </span>
        </h2>
        <table class="table is-striped is-fullwidth">
            <tbody>
                <tr>
                    <th style="width: 200px;">LWT version</th>
                    <td><?php echo htmlspecialchars($data["lwt_version"] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <th>
                        <a href="https://en.wikipedia.org/wiki/Web_server" target="_blank" rel="noopener">
                            Web Server
                        </a>
                    </th>
                    <td><?php echo htmlspecialchars($data["server_soft"] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <tr>
                    <th>Server Software</th>
                    <td>
                        <a href="https://en.wikipedia.org/wiki/Apache_HTTP_Server" target="_blank" rel="noopener">
                            <?php echo htmlspecialchars($data["apache"] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th>Server Location</th>
                    <td><code><?php echo htmlspecialchars($data["server_location"] ?? '', ENT_QUOTES, 'UTF-8'); ?></code></td>
                </tr>
                <tr>
                    <th>
                        <a href="https://en.wikipedia.org/wiki/PHP" target="_blank" rel="noopener">
                            PHP
                        </a> Version
                    </th>
                    <td><?php echo htmlspecialchars($data["php"] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Database Section -->
    <div class="box mb-4">
        <h2 class="title is-4">
            <span class="icon-text">
                <span class="icon has-text-success">
                    <?php echo IconHelper::render('database', ['class' => 'icon']); ?>
                </span>
                <span>Database</span>
            </span>
        </h2>
        <table class="table is-striped is-fullwidth">
            <tbody>
                <tr>
                    <th style="width: 200px;">
                        <a href="https://en.wikipedia.org/wiki/Database" target="_blank" rel="noopener">
                            Database
                        </a> name
                    </th>
                    <td><code><?php echo htmlspecialchars($data["db_name"] ?? '', ENT_QUOTES, 'UTF-8'); ?></code></td>
                </tr>
                <tr>
                    <th>Database Size</th>
                    <td><?php echo htmlspecialchars((string)($data["db_size"] ?? ''), ENT_QUOTES, 'UTF-8'); ?> MB</td>
                </tr>
                <tr>
                    <th>
                        <a href="https://en.wikipedia.org/wiki/MySQL" target="_blank" rel="noopener">
                            MySQL
                        </a> Version
                    </th>
                    <td><?php echo htmlspecialchars($data["mysql"] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Client API Section -->
    <div class="box mb-4">
        <h2 class="title is-4">
            <span class="icon-text">
                <span class="icon has-text-warning">
                    <?php echo IconHelper::render('cloud', ['class' => 'icon']); ?>
                </span>
                <span>Client API</span>
            </span>
        </h2>

        <!-- Loading State -->
        <div x-show="isLoading" class="has-text-centered py-4">
            <span class="icon is-medium">
                <span class="loader"></span>
            </span>
            <span class="ml-2">Loading API information...</span>
        </div>

        <!-- Error State -->
        <div x-show="error && !isLoading" x-cloak class="notification is-danger is-light">
            <p><strong>Error loading API information</strong></p>
            <p x-text="error"></p>
        </div>

        <!-- Success State -->
        <div x-show="!isLoading && !error" x-cloak>
            <table class="table is-striped is-fullwidth">
                <tbody>
                    <tr>
                        <th style="width: 200px;">
                            <a href="https://en.wikipedia.org/wiki/REST" target="_blank" rel="noopener">
                                REST API
                            </a> Version
                        </th>
                        <td x-text="apiVersion"></td>
                    </tr>
                    <tr>
                        <th>
                            <a href="https://en.wikipedia.org/wiki/REST" target="_blank" rel="noopener">
                                REST API
                            </a> Release date
                        </th>
                        <td x-text="apiReleaseDate"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Back Button -->
    <div class="field">
        <div class="control">
            <a href="/" class="button is-light">
                <?php echo IconHelper::render('arrow-left'); ?>
                <span>Back to Main Menu</span>
            </a>
        </div>
    </div>
</div>

<style>
/* Simple CSS loader animation */
.loader {
    display: inline-block;
    width: 1.5em;
    height: 1.5em;
    border: 3px solid #dbdbdb;
    border-radius: 50%;
    border-top-color: #3273dc;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

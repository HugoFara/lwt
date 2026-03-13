<?php

/**
 * Admin Dashboard View
 *
 * Landing page for /admin with links to all admin subpages.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Admin\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Views\Admin;

?>

<section class="section">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-10-tablet is-8-desktop">

                <div class="columns is-multiline">

                    <!-- Settings -->
                    <div class="column is-half">
                        <div class="box">
                            <h2 class="title is-5">
                                <span class="icon-text">
                                    <span class="icon"><i data-lucide="settings"></i></span>
                                    <span>Settings</span>
                                </span>
                            </h2>
                            <p class="mb-3">Configure newsfeeds and server-wide settings.</p>
                            <a href="/admin/settings" class="button is-primary is-outlined">
                                <span class="icon"><i data-lucide="arrow-right"></i></span>
                                <span>Open Settings</span>
                            </a>
                        </div>
                    </div>

                    <!-- Backup & Restore -->
                    <div class="column is-half">
                        <div class="box">
                            <h2 class="title is-5">
                                <span class="icon-text">
                                    <span class="icon"><i data-lucide="database"></i></span>
                                    <span>Backup &amp; Restore</span>
                                </span>
                            </h2>
                            <p class="mb-3">Download or restore database backups.</p>
                            <a href="/admin/backup" class="button is-primary is-outlined">
                                <span class="icon"><i data-lucide="arrow-right"></i></span>
                                <span>Open Backup</span>
                            </a>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="column is-half">
                        <div class="box">
                            <h2 class="title is-5">
                                <span class="icon-text">
                                    <span class="icon"><i data-lucide="bar-chart-3"></i></span>
                                    <span>Statistics</span>
                                </span>
                            </h2>
                            <p class="mb-3">View vocabulary and reading statistics.</p>
                            <a href="/admin/statistics" class="button is-primary is-outlined">
                                <span class="icon"><i data-lucide="arrow-right"></i></span>
                                <span>View Statistics</span>
                            </a>
                        </div>
                    </div>

                    <!-- Database Wizard -->
                    <div class="column is-half">
                        <div class="box">
                            <h2 class="title is-5">
                                <span class="icon-text">
                                    <span class="icon"><i data-lucide="wand-2"></i></span>
                                    <span>Database Wizard</span>
                                </span>
                            </h2>
                            <p class="mb-3">Configure database connection settings.</p>
                            <a href="/admin/wizard" class="button is-primary is-outlined">
                                <span class="icon"><i data-lucide="arrow-right"></i></span>
                                <span>Open Wizard</span>
                            </a>
                        </div>
                    </div>

                    <!-- Install Demo -->
                    <div class="column is-half">
                        <div class="box">
                            <h2 class="title is-5">
                                <span class="icon-text">
                                    <span class="icon"><i data-lucide="download"></i></span>
                                    <span>Install Demo</span>
                                </span>
                            </h2>
                            <p class="mb-3">Install the demo database with sample content.</p>
                            <a href="/admin/install-demo" class="button is-primary is-outlined">
                                <span class="icon"><i data-lucide="arrow-right"></i></span>
                                <span>Install Demo</span>
                            </a>
                        </div>
                    </div>

                    <!-- Server Data -->
                    <div class="column is-half">
                        <div class="box">
                            <h2 class="title is-5">
                                <span class="icon-text">
                                    <span class="icon"><i data-lucide="server"></i></span>
                                    <span>Server Data</span>
                                </span>
                            </h2>
                            <p class="mb-3">View server and PHP configuration details.</p>
                            <a href="/admin/server-data" class="button is-primary is-outlined">
                                <span class="icon"><i data-lucide="arrow-right"></i></span>
                                <span>View Server Data</span>
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</section>

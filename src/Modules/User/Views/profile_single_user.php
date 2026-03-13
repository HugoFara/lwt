<?php

/**
 * Single-User Profile View
 *
 * Shown when multi-user mode is disabled. Provides navigation to
 * preferences and admin settings.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\User\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\User\Views;

?>

<section class="section">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-10-tablet is-8-desktop">

                <!-- Single-user info -->
                <div class="box">
                    <h2 class="title is-4">
                        <span class="icon-text">
                            <span class="icon"><i data-lucide="user"></i></span>
                            <span>Profile</span>
                        </span>
                    </h2>
                    <div class="notification is-info is-light">
                        You are using LWT in <strong>single-user mode</strong>.
                        Account management is available when multi-user mode is enabled.
                    </div>
                </div>

                <!-- Preferences Link -->
                <div class="box">
                    <h2 class="title is-4">
                        <span class="icon-text">
                            <span class="icon"><i data-lucide="sliders-horizontal"></i></span>
                            <span>Preferences</span>
                        </span>
                    </h2>
                    <p class="mb-3">
                        Configure your reading, review, appearance, text-to-speech, and pagination settings.
                    </p>
                    <a href="/profile/preferences" class="button is-primary is-outlined">
                        <span class="icon"><i data-lucide="settings"></i></span>
                        <span>Edit Preferences</span>
                    </a>
                </div>

                <!-- Admin Settings Link -->
                <div class="box">
                    <h2 class="title is-4">
                        <span class="icon-text">
                            <span class="icon"><i data-lucide="shield"></i></span>
                            <span>Admin Settings</span>
                        </span>
                    </h2>
                    <p class="mb-3">
                        Configure newsfeeds and server-wide settings.
                    </p>
                    <a href="/admin/settings" class="button is-primary is-outlined">
                        <span class="icon"><i data-lucide="settings"></i></span>
                        <span>Admin Settings</span>
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>

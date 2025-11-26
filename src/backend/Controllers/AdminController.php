<?php

/**
 * \file
 * \brief Admin Controller - Administrative functions
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-admincontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

/**
 * Controller for administrative functions.
 *
 * Handles:
 * - Backup and restore
 * - Database wizard
 * - Statistics
 * - Settings
 * - Install demo
 * - Table management
 * - Server data
 * - TTS settings
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class AdminController extends BaseController
{
    /**
     * Backup and restore page (replaces admin_backup.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function backup(array $params): void
    {
        include __DIR__ . '/../Legacy/admin_backup.php';
    }

    /**
     * Database wizard page (replaces admin_wizard.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function wizard(array $params): void
    {
        include __DIR__ . '/../Legacy/admin_wizard.php';
    }

    /**
     * Statistics page (replaces admin_statistics.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function statistics(array $params): void
    {
        include __DIR__ . '/../Legacy/admin_statistics.php';
    }

    /**
     * Settings page (replaces admin_settings.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function settings(array $params): void
    {
        include __DIR__ . '/../Legacy/admin_settings.php';
    }

    /**
     * Hover settings page (replaces settings_hover.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function settingsHover(array $params): void
    {
        include __DIR__ . '/../Legacy/settings_hover.php';
    }

    /**
     * TTS settings page (replaces admin_tts_settings.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function settingsTts(array $params): void
    {
        include __DIR__ . '/../Legacy/admin_tts_settings.php';
    }

    /**
     * Install demo page (replaces admin_install_demo.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function installDemo(array $params): void
    {
        include __DIR__ . '/../Legacy/admin_install_demo.php';
    }

    /**
     * Table management page (replaces admin_table_management.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function tables(array $params): void
    {
        include __DIR__ . '/../Legacy/admin_table_management.php';
    }

    /**
     * Server data page (replaces admin_server_data.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function serverData(array $params): void
    {
        include __DIR__ . '/../Legacy/admin_server_data.php';
    }
}

<?php

/**
 * \file
 * \brief Proceed to the general settings.
 *
 * This file now delegates to SettingsService for setting definitions.
 * The get_setting_data() function is kept for backward compatibility.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-settings.html
 * @since   2.0.3-fork
 */

require_once __DIR__ . '/Globals.php';
require_once __DIR__ . '/../Services/SettingsService.php';

use Lwt\Core\Globals;
use Lwt\Services\SettingsService;

// Initialize the Globals class with default values
Globals::initialize();

/**
 * Reload $setting_data if necessary.
 *
 * @return array<string, array{dft: string, num: int, min?: int, max?: int}> Setting definitions
 *
 * @deprecated 3.0.0 Use SettingsService::getDefinitions() instead
 */
function get_setting_data(): array
{
    return SettingsService::getDefinitions();
}

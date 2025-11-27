<?php

/**
 * Preferences / Settings
 *
 * Call: /admin/settings?....
 *      ... op=Save ... do save
 *      ... op=Reset ... do reset to defaults
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 */

namespace Lwt\Interface\Settings;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Http/param_helpers.php';
require_once 'Core/Media/media_helpers.php';

use Lwt\Services\SettingsService;

require_once __DIR__ . '/../Services/SettingsService.php';

// Initialize service
$settingsService = new SettingsService();
$message = '';

// Handle form submission
if (isset($_REQUEST['op'])) {
    if ($_REQUEST['op'] == 'Save') {
        $message = $settingsService->saveAll($_REQUEST);
    } else {
        $message = $settingsService->resetAll();
    }
}

// Load current settings for the form (used by included view)
/** @psalm-suppress UnusedVariable - Variables used by included view */
$settings = $settingsService->getAll();

// Render page
pagestart('Settings/Preferences', true);

echo error_message_with_hide($message, true);

// Include the view
include __DIR__ . '/../Views/Admin/settings_form.php';

pageend();

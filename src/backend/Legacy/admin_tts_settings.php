<?php

/**
 * Utility for calling system speech synthesizer
 *
 * Call: /admin/settings/tts
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   chaosarium <leonluleonlu@gmail.com>
 * @author   HugoFara <Hugo.Farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/text-to-speech-settings.html
 * @since    2.2.2-fork
 */

namespace Lwt\Interface\TtsSettings;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Language/language_utilities.php';
require_once 'Core/Language/langdefs.php';

use Lwt\Services\TtsService;

require_once __DIR__ . '/../Services/TtsService.php';

// Initialize service
$ttsService = new TtsService();
$message = '';

// Handle save request
if (array_key_exists('op', $_REQUEST) && $_REQUEST['op'] == 'Save') {
    $ttsService->saveSettings($_REQUEST);
    $message = "Settings saved!";
}

// Get view data (used by included view)
/** @psalm-suppress UnusedVariable - Variables used by included view */
$languageOptions = $ttsService->getLanguageOptions(LWT_LANGUAGES_ARRAY);
/** @psalm-suppress UnusedVariable - Variables used by included view */
$currentLanguageCode = json_encode($ttsService->getCurrentLanguageCode(LWT_LANGUAGES_ARRAY));

// Render page
pagestart('Text-to-Speech Settings', true);

if ($message != '') {
    echo error_message_with_hide($message, false);
}

// Include the view
include __DIR__ . '/../Views/Admin/tts_settings.php';

pageend();

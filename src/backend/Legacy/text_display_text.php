<?php

/**
 * Display an improved annotated text (text frame)
 *
 * Call: /text/display?text=[textid] (text component)
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/display-impr-text-text.html
 * @since    1.5.0
 */

namespace Lwt\Interface\TextDisplay;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Text/text_helpers.php';
require_once 'Core/Language/language_utilities.php';
require_once 'Core/Http/param_helpers.php';

use Lwt\Services\TextDisplayService;

require_once __DIR__ . '/../Services/TextDisplayService.php';

/**
 * Main function to do a complete printed text text content.
 *
 * @param int|null $textId Text ID, we will use page request if not provided.
 *
 * @return void
 *
 * @psalm-suppress UnusedVariable Variables are used in included view file
 */
function do_display_impr_text_text_main($textId = null)
{
    if ($textId === null) {
        $textId = (int)getreq('text');
    }

    $service = new TextDisplayService();

    // Get annotated text
    $annotatedText = $service->getAnnotatedText($textId);

    if ($textId == 0 || strlen($annotatedText) <= 0) {
        header("Location: /text/edit");
        exit();
    }

    // Get display settings
    $settings = $service->getTextDisplaySettings($textId);

    if ($settings === null) {
        header("Location: /text/edit");
        exit();
    }

    // Prepare view variables (used in included view)
    $textSize = $settings['textSize'];
    $rtlScript = $settings['rtlScript'];

    // Parse annotations (used in included view)
    $annotations = $service->parseAnnotations($annotatedText);

    // Save current text
    $service->saveCurrentText($textId);

    // Include the view
    include __DIR__ . '/../Views/Text/display_text.php';
}

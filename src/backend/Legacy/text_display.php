<?php

/**
 * Display an improved annotated text (frame set)
 *
 * Call: /text/display?text=[textid]
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/display-impr-text.html
 * @since    1.5.0
 */

namespace Lwt\Interface\TextDisplay;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Text/text_helpers.php';
require_once 'Core/Http/param_helpers.php';
require_once 'Core/Language/language_utilities.php';
require_once 'Core/Mobile/mobile_interactions.php';
require_once 'text_display_header.php';
require_once 'text_display_text.php';

use Lwt\Services\TextDisplayService;

require_once __DIR__ . '/../Services/TextDisplayService.php';

/**
 * Make the page content to display printed texts on mobile.
 *
 * @param int    $textId Text ID
 * @param string $audio  Media URI
 *
 * @return     void
 * @deprecated
 * @since      2.2.0 This function should not longer be used, and should cause issues. Use
 * do_desktop_display_impr_text instead.
 */
function do_mobile_display_impr_text($textId, $audio)
{
    do_frameset_mobile_css();
    do_frameset_mobile_js($audio);
    do_frameset_mobile_page_content(
        "display_impr_text_header.php?text=" . $textId,
        "display_impr_text_text.php?text=" . $textId,
        false
    );
}

/**
 * Make the main page content to display printed texts for desktop.
 *
 * @param int         $textId Text ID
 * @param string|null $audio  Media URI (unused, kept for compatibility)
 *
 * @return void
 *
 * @psalm-suppress UnusedParam Parameters kept for compatibility
 * @psalm-suppress UnusedVariable $textId is used in included view file
 */
function do_desktop_display_impr_text($textId, $audio)
{
    // $textId is used in the included view
    include __DIR__ . '/../Views/Text/display_main.php';
}

/**
 * Do the page to display printed text.
 *
 * @param int $textId Text ID
 *
 * @return void
 */
function do_display_impr_text_page($textId)
{
    $service = new TextDisplayService();
    $audio = $service->getAudioUri($textId);

    pagestart_nobody('Display');

    if (is_mobile()) {
        do_mobile_display_impr_text($textId, $audio);
    } else {
        do_desktop_display_impr_text($textId, $audio);
    }

    pageend();
}

// Main entry point
if (isset($_REQUEST['text'])) {
    do_display_impr_text_page((int) getreq('text'));
} else {
    header("Location: /text/edit");
    exit();
}

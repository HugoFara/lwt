<?php

/**
 * Display an improved annotated text
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
require_once 'text_display_header.php';
require_once 'text_display_text.php';

use Lwt\Services\TextDisplayService;

require_once __DIR__ . '/../Services/TextDisplayService.php';

/**
 * Make the main page content to display printed texts.
 *
 * @param int $textId Text ID
 *
 * @return void
 *
 * @psalm-suppress UnusedVariable $textId is used in included view file
 */
function do_display_impr_text_content($textId)
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
    pagestart_nobody('Display');
    do_display_impr_text_content($textId);
    pageend();
}

// Main entry point
if (isset($_REQUEST['text'])) {
    do_display_impr_text_page((int) getreq('text'));
} else {
    header("Location: /text/edit");
    exit();
}

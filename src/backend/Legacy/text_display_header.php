<?php

/**
 * Display an improved annotated text (top frame)
 *
 * Call: /text/display?text=[textid] (header component)
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/display-impr-text-header.html
 * @since    1.5.0
 */

namespace Lwt\Interface\TextDisplay;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Tag/tags.php';
require_once 'Core/Text/text_helpers.php';
require_once 'Core/Http/param_helpers.php';
require_once 'Core/Language/language_utilities.php';
require_once 'Core/Media/media_helpers.php';

use Lwt\Services\TextDisplayService;

require_once __DIR__ . '/../Services/TextDisplayService.php';

/**
 * Main function to generate a complete header for a specific text.
 *
 * @param int $textId Text ID.
 *
 * @return void
 *
 * @psalm-suppress UnusedVariable Variables are used in included view file
 */
function do_diplay_impr_text_header_main($textId)
{
    $service = new TextDisplayService();

    // Get header data from service
    $headerData = $service->getHeaderData($textId);

    if ($headerData === null) {
        return;
    }

    // Prepare view variables (used in included view)
    $title = $headerData['title'];
    $audio = $headerData['audio'];
    $sourceUri = $headerData['sourceUri'];

    // Get navigation links (used in included view)
    $textLinks = getPreviousAndNextTextLinks(
        $textId,
        'display_impr_text.php?text=',
        true,
        ' &nbsp; &nbsp; '
    );

    // Include the view
    include __DIR__ . '/../Views/Text/display_header.php';
}

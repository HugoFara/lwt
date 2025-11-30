<?php

/**
 * Mobile Text Reading Layout View
 *
 * Variables expected:
 * - $textId: int - Text ID
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

namespace Lwt\Views\Text;

?>
<div class="mobile-read-container">
    <div id="frame-h">
        <?php include __DIR__ . '/read_header.php'; ?>
    </div>
    <hr />
    <div id="frame-l">
        <?php include __DIR__ . '/read_text.php'; ?>
    </div>
</div>
<div id="frames-r" class="mobile-frames-right" data-action="hide-right-frames">
    <!-- iFrames wrapper for events -->
    <div class="mobile-frames-right-inner">
        <iframe src="empty.html" scrolling="auto" name="ro">
            Your browser doesn't support iFrames, update it!
        </iframe>
        <iframe src="empty.html" scrolling="auto" name="ru">
            Your browser doesn't support iFrames, update it!
        </iframe>
    </div>
</div>

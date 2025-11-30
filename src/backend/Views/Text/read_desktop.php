<?php

/**
 * Desktop Text Reading Layout View
 *
 * Variables expected:
 * - $textId: int - Text ID
 * - $frameLWidth: int - Left frame width percentage
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
<div style="width: <?php echo $frameLWidth; ?>%;" id="frames-l">
    <div id="frame-h">
        <?php include __DIR__ . '/read_header.php'; ?>
    </div>
    <hr />
    <div id="frame-l">
        <?php include __DIR__ . '/read_text.php'; ?>
    </div>
</div>
<div id="frames-r"
style="position: fixed; top: 2%; right: 0; height: 95%;
width: <?php echo 97 - $frameLWidth; ?>%;">
    <!-- iFrames wrapper for events -->
    <iframe src="empty.html" scrolling="auto" name="ro"
    style="height: 50%; width: 100%;">
        Your browser doesn't support iFrames, update it!
    </iframe>
    <iframe src="empty.html" scrolling="auto" name="ru"
    style="height: 50%; width: 100%;">
        Your browser doesn't support iFrames, update it!
    </iframe>
</div>

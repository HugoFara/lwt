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

?>
<div style="width: 95%; height: 100%;">
    <div id="frame-h">
        <?php include __DIR__ . '/read_header.php'; ?>
    </div>
    <hr />
    <div id="frame-l">
        <?php include __DIR__ . '/read_text.php'; ?>
    </div>
</div>
<div id="frames-r"
style="position: fixed; top: 0; right: -100%; width: 100%; height: 100%;"
onclick="hideRightFrames();">
    <!-- iFrames wrapper for events -->
    <div style="margin-left: 50%; height: 99%;">
        <iframe src="empty.html" scrolling="auto" name="ro"
        style="height: 50%; width: 100%;">
            Your browser doesn't support iFrames, update it!
        </iframe>
        <iframe src="empty.html" scrolling="auto" name="ru"
        style="height: 50%; width: 100%;">
            Your browser doesn't support iFrames, update it!
        </iframe>
    </div>
</div>

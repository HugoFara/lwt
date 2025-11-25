<?php

/**
 * \file
 * \brief Start Reading a text (frameset)
 *
 * Call: do_text.php?text=[textid]
 *      Create the main window when reading texts.
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/do-text.html
 * @since   1.0.3
 */

require_once 'Core/session_utility.php';
require_once 'Core/mobile_interactions.php';
require_once 'text_read_header.php';
require_once 'text_read_text.php';

/**
 * Get text ID (if possible).
 *
 * Text ID if first looked at int the 'text' parameter. If not found, then look at 'start'.
 *
 * @return int|null Text ID or null
 */
function get_text_id()
{
    if (isset($_REQUEST['text']) && is_numeric($_REQUEST['text'])) {
        return (int)$_REQUEST['text'];
    }
    if (isset($_REQUEST['start']) && is_numeric($_REQUEST['start'])) {
        return (int)$_REQUEST['start'];
    }
    return null;
}

/**
 * Echo the page content for the mobile version of do_text.
 *
 * @param int   $textid Text ID
 * @param mixed $audio  Audio URI, unnused, null by default
 *
 * @return void
 *
 * @since 2.2.1 It also calls do_frameset_mobile_css and do_frameset_mobile_js
 * @since 2.6.0-fork Rewrote to match the desktop version (between 2.2.1 and 2.6.0)
 */
function do_text_mobile_content($textid, $audio = null)
{
    ?>
<div style="width: 95%; height: 100%;">
    <div id="frame-h">
        <?php do_text_header_content($textid, true); ?>
    </div>
    <hr />
    <div id="frame-l">
        <?php do_text_text_content($textid, true); ?>
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

    <?php
}

/**
 * Echo the page content for the desktop version of do_text.
 *
 * @param int   $textid Text ID
 * @param mixed $audio  Audio URI, unnused, null by default
 *
 * @return void
 */
function do_text_desktop_content($textid, $audio = null)
{
    $frame_l_width = (int)getSettingWithDefault('set-text-l-framewidth-percent');
    ?>
<div style="width: <?php echo $frame_l_width; ?>%;" id="frames-l">
    <div id="frame-h">
        <?php do_text_header_content($textid, true); ?>
    </div>
    <hr />
    <div id="frame-l">
        <?php do_text_text_content($textid, true); ?>
    </div>
</div>
<div id="frames-r"
style="position: fixed; top: 2%; right: 0; height: 95%;
width: <?php echo 97 - $frame_l_width; ?>%;">
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

    <?php
}

/**
 * Echo the text page.
 *
 * @param int  $textit Text ID
 * @param bool $mobile Set to true if you want the mobile version of the page.
 *
 * @since 2.2.1 The $mobile parameter is no longer required.
 * @since 2.7.0 Adds a CSS rule to auto-enlarge the body.
 *
 * @return void
 */
function do_text_page($textid)
{
    pagestart_nobody(
        'Read',
        "body {
            margin: 20px;
            max-width: 100%;
        }"
    );

    if (is_mobile()) {
        do_text_mobile_content($textid);
    } else {
        do_text_desktop_content($textid);
    }
    pageend();
}

if (get_text_id() !== null) {
    do_text_page(get_text_id());
} else {
    // Document not ready
    header("Location: /text/edit");
    exit();
}

?>
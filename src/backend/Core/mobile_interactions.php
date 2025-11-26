<?php

/**
 * \file
 * \brief Handle interactions with mobile platforms
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-mobile-interactions.html
 * @since   2.2.0
 * @since   2.2.1 You should not longer use this library as mobile detect is removed.
 *                However, this interface is unchanged for backward compatibility.
 * @since   2.6.0 Mobile detect is back and does no longer use external libraries.
 */

require_once __DIR__ . '/database_connect.php';

/**
 * Return false. Before 2.2.1, return true if we should use mobile mode.
 *
 * @return bool Mobile mode shoud be activated or not
 *
 * @since 2.2.1 You should not use this function since mobiledetect is no longer used.
 * @since 2.6.0-fork No longer deprecated and use no external library.
 */
function is_mobile(): bool
{
    $mobileDisplayMode = (int)getSettingWithDefault('set-mobile-display-mode');
    if ($mobileDisplayMode == 2) {
        return true;
    }
    $mobile_detect = preg_match(
        "/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini" .
        "|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i",
        $_SERVER["HTTP_USER_AGENT"] ?? ''
    );
    if ($mobileDisplayMode == 0 && $mobile_detect) {
        return true;
    }

    return false;
}

/**
 * Echo frameset content for a mobile page.
 *
 * @param string $frame_h_uri  URI for the frame-h-2 iframe.
 * @param string $frame_l_uri  URI for the frame-l-2 iframe.
 * @param bool   $right_frames Create or not a space for right frames.
 *
 * @return void
 *
 * @deprecated Since 2.2.1-fork, Do not use frames
 */
function do_frameset_mobile_page_content($frame_h_uri, $frame_l_uri, $right_frames)
{
    ?>
<div id="frame-h">
    <iframe id="frame-h-2" src="<?php echo $frame_h_uri; ?>" scrolling="yes" name="h">
    </iframe>
</div>
    <?php
    if ($right_frames) {
        ?>
<div id="frame-ro">
    <iframe id="frame-ro-2" src="empty.html" scrolling="yes" name="ro"></iframe>
</div>
        <?php
    }
    ?>
<div id="frame-l">
    <iframe id="frame-l-2" src="<?php echo $frame_l_uri; ?>" scrolling="yes" name="l">
    </iframe>
</div>
    <?php
    if ($right_frames) {
        ?>
<div id="frame-ru">
    <iframe id="frame-ru-2" src="empty.html" scrolling="yes" name="ru"></iframe>
</div>
        <?php
    }
}

/**
 * Echo the CSS content for mobile frameset page.
 *
 * @return void
 *
 * @deprecated Since 2.6.0-fork, the display for mobile changed, making this code
 *             useless
 */
function do_frameset_mobile_css()
{
    ?>
<style type="text/css">
    body {
        background-color: #cccccc;
        margin: 0;
        overflow: hidden;
    }
    #frame-h, #frame-l, #frame-ro, #frame-ru {
        position: absolute;
        overflow: scroll;
        -webkit-overflow-scrolling: touch;
    }
    #frame-h-2, #frame-l-2, #frame-ro-2, #frame-ru-2 {
        display: inline-block;
    }
</style>
    <?php
}


/**
 * Echo the JS code for the mobile version of a frameset page.
 *
 * @param string|null $audio Audio URI
 *
 * @return void
 *
 * @deprecated Since 2.2.1-fork, we do no longer use frameset
 */
function do_frameset_mobile_js($audio = null)
{

    ?>
    <script type="text/javascript" src="/assets/js/jquery.js" charset="utf-8"></script>

    <script type="text/javascript">
       //<![CDATA[
       function rsizeIframes() {
            const h_height = <?php
            if (isset($audio)) {
                getSettingWithDefault('set-text-h-frameheight-with-audio');
            } else {
                getSettingWithDefault('set-text-h-frameheight-no-audio');
            } ?> + 10;
            const lr_perc = <?php echo getSettingWithDefault('set-text-l-framewidth-percent'); ?>;
            const r_perc = <?php echo getSettingWithDefault('set-text-r-frameheight-percent'); ?>;
            const w = $(window).width();
            const h = $(window).height();
            const l_width = w * lr_perc / 100;
            const r_width = w - l_width;
            const l_height = h - h_height;
            const ro_height = h * r_perc / 100;
            const ru_height = h - ro_height;
            $('#frame-h').width(l_width-5).height(h_height-5)
            .css('top',0).css('left',0);
            $('#frame-h-2').width('100%').height('100%')
            .css('top',0).css('left',0);
            $('#frame-l').width(l_width-5).height(l_height-5)
            .css('top',h_height).css('left',0);
            $('#frame-l-2').width('100%').height('100%')
            .css('top',0).css('left',0);
            $('#frame-ro').width(r_width-5).height(ro_height-5)
            .css('top',0).css('left',l_width);
            $('#frame-ro-2').width('100%').height('100%')
            .css('top',0).css('left',0);
            $('#frame-ru').width(r_width-5).height(ru_height-5)
            .css('top',ro_height).css('left',l_width);
            $('#frame-ru-2').width('100%').height('100%')
            .css('top',0).css('left',0);
        }

        function init() {
            rsizeIframes();
            $(window).resize(rsizeIframes);
        }

        $(document).ready(init);
        //]]>
    </script>

    <?php
}

?>
<?php

/**
 * \file
 * \brief Media player functions (audio and video).
 *
 * This file contains functions for creating HTML audio and video players
 * with support for local files and various streaming platforms.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since   3.0.0 Split from text_helpers.php
 */

use Lwt\Database\Escaping;
use Lwt\Database\Settings;

require_once __DIR__ . '/../UI/vite_helper.php';

/**
 * Create an HTML media player, audio or video.
 *
 * @param string $path   URL or local file path
 * @param int    $offset Offset from the beginning of the video
 *
 * @return void
 */
function makeMediaPlayer($path, $offset = 0)
{
    if ($path == '') {
        return;
    }
    /**
    * File extension (if exists)
    */
    $extension = substr($path, -4);
    if ($extension == '.mp3' || $extension == '.wav' || $extension == '.ogg') {
        makeAudioPlayer($path, $offset);
    } else {
        makeVideoPlayer($path, $offset);
    }
}


/**
 * Create an embed video player
 *
 * @param string $path   URL or local file path
 * @param int    $offset Offset from the beginning of the video
 */
function makeVideoPlayer($path, $offset = 0): void
{
    $online = false;
    $url = null;
    if (
        preg_match(
            "/(?:https:\/\/)?www\.youtube\.com\/watch\?v=([\d\w]+)/iu",
            $path,
            $matches
        )
    ) {
        // Youtube video
        $domain = "https://www.youtube.com/embed/";
        $id = $matches[1];
        $url = $domain . $id . "?t=" . $offset;
        $online = true;
    } elseif (
        preg_match(
            "/(?:https:\/\/)?youtu\.be\/([\d\w]+)/iu",
            $path,
            $matches
        )
    ) {
        // Youtube video
        $domain = "https://www.youtube.com/embed/";
        $id = $matches[1];
        $url = $domain . $id . "?t=" . $offset;
        $online = true;
    } elseif (
        preg_match(
            "/(?:https:\/\/)?dai\.ly\/([^\?]+)/iu",
            $path,
            $matches
        )
    ) {
        // Dailymotion
        $domain = "https://www.dailymotion.com/embed/video/";
        $id = $matches[1];
        $url = $domain . $id;
        $online = true;
    } elseif (
        preg_match(
            "/(?:https:\/\/)?vimeo\.com\/(\d+)/iu",
            // Vimeo
            $path,
            $matches
        )
    ) {
        $domain = "https://player.vimeo.com/video/";
        $id = $matches[1];
        $url = $domain . $id . "#t=" . $offset . "s";
        $online = true;
    }

    if ($online) {
        // Online video player in iframe
        ?>
<iframe style="width: 100%; height: 30%;"
src="<?php echo $url ?>"
title="Video player"
frameborder="0"
allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
allowfullscreen type="text/html">
</iframe>
        <?php
    } else {
        // Local video player
        // makeAudioPlayer($path, $offset);
        $type = "video/" . pathinfo($path, PATHINFO_EXTENSION);
        $title = pathinfo($path, PATHINFO_FILENAME);
        ?>
<video preload="auto" controls title="<?php echo $title ?>"
style="width: 100%; height: 300px; display: block; margin-left: auto; margin-right: auto;">
    <source src="<?php echo $path; ?>" type="<?php echo $type; ?>">
    <p>Your browser does not support video tags.</p>
</video>
        <?php
    }
}


/**
 * Create an HTML audio player.
 *
 * @param string $audio  Audio URL
 * @param int    $offset Offset from the beginning of the video
 *
 * @return void
 */
function makeAudioPlayer($audio, $offset = 0)
{
    if ($audio == '') {
        return;
    }
    $audio = trim($audio);
    $repeatMode = (bool) Settings::getZeroOrOne('currentplayerrepeatmode', 0);
    $currentplayerseconds = Settings::get('currentplayerseconds');
    if ($currentplayerseconds == '') {
        $currentplayerseconds = 5;
    }
    $currentplaybackrate = Settings::get('currentplaybackrate');
    if ($currentplaybackrate == '') {
        $currentplaybackrate = 10;
    }

    // Use HTML5 audio player for Vite mode, jPlayer for legacy mode
    if (should_use_vite()) {
        makeHtml5AudioPlayer($audio, $offset, $repeatMode, $currentplayerseconds, $currentplaybackrate);
    } else {
        makeLegacyAudioPlayer($audio, $offset, $repeatMode, $currentplayerseconds, $currentplaybackrate);
    }
}

/**
 * Create an HTML5 native audio player (Vite mode).
 *
 * @param string $audio               Audio URL
 * @param int    $offset              Offset from the beginning
 * @param bool   $repeatMode          Whether to repeat
 * @param int    $currentplayerseconds Seconds to skip
 * @param int    $currentplaybackrate  Playback rate (10 = 1.0x)
 *
 * @return void
 * @since 3.0.0
 */
function makeHtml5AudioPlayer($audio, $offset, $repeatMode, $currentplayerseconds, $currentplaybackrate)
{
    ?>
<table class="lwt-audio-wrapper" style="margin-top: 5px; margin-left: auto; margin-right: auto;" cellspacing="0" cellpadding="0">
    <tr>
        <td class="center borderleft" style="padding-left:10px;">
            <span id="do-single" class="click<?php echo ($repeatMode ? '' : ' hide'); ?>"
                style="color:#09F;font-weight: bold;" title="Toggle Repeat (Now ON)">
                <img src="/assets/icons/arrow-repeat.png" alt="Toggle Repeat (Now ON)" title="Toggle Repeat (Now ON)" style="width:24px;height:24px;">
            </span>
            <span id="do-repeat" class="click<?php echo ($repeatMode ? ' hide' : ''); ?>"
                style="color:grey;font-weight: bold;" title="Toggle Repeat (Now OFF)">
                <img src="/assets/icons/arrow-norepeat.png" alt="Toggle Repeat (Now OFF)" title="Toggle Repeat (Now OFF)" style="width:24px;height:24px;">
            </span>
        </td>
        <td class="center bordermiddle">&nbsp;</td>
        <td class="bordermiddle">
            <!-- HTML5 Audio Player -->
            <div id="lwt-audio-player" class="lwt-audio-player">
                <audio preload="auto">
                    <source src="<?php echo htmlspecialchars($audio, ENT_QUOTES, 'UTF-8'); ?>">
                    Your browser does not support the audio element.
                </audio>
                <div class="lwt-audio-controls">
                    <button type="button" class="lwt-audio-play" title="Play">
                        <span class="sr-only">Play</span>
                    </button>
                    <button type="button" class="lwt-audio-pause hide" title="Pause">
                        <span class="sr-only">Pause</span>
                    </button>
                    <button type="button" class="lwt-audio-stop" title="Stop">
                        <span class="sr-only">Stop</span>
                    </button>
                </div>
                <div class="lwt-audio-progress-section">
                    <div class="lwt-audio-progress-container">
                        <div class="lwt-audio-progress-bar"></div>
                    </div>
                    <div class="lwt-audio-time">
                        <span class="lwt-audio-current-time">0:00</span>
                        <span class="lwt-audio-duration">0:00</span>
                    </div>
                </div>
                <div class="lwt-audio-volume-section">
                    <button type="button" class="lwt-audio-volume-btn lwt-audio-mute" title="Mute">
                        <img src="/assets/icons/speaker-volume.png" alt="Mute">
                    </button>
                    <button type="button" class="lwt-audio-volume-btn lwt-audio-unmute hide" title="Unmute">
                        <img src="/assets/icons/speaker.png" alt="Unmute">
                    </button>
                    <div class="lwt-audio-volume-container">
                        <div class="lwt-audio-volume-bar"></div>
                    </div>
                </div>
            </div>
        </td>
        <td class="center bordermiddle">&nbsp;</td>
        <td class="center bordermiddle">
            <select id="backtime" name="backtime">
                <?php echo get_seconds_selectoptions($currentplayerseconds); ?>
            </select>
            <br />
            <span id="backbutt" class="click">
                <img src="/assets/icons/arrow-circle-225-left.png" alt="Rewind n seconds" title="Rewind n seconds" />
            </span>&nbsp;&nbsp;
            <span id="forwbutt" class="click">
                <img src="/assets/icons/arrow-circle-315.png" alt="Forward n seconds" title="Forward n seconds" />
            </span>
            <span id="playTime" class="hide"></span>
        </td>
        <td class="center bordermiddle">&nbsp;</td>
        <td class="center borderright" style="padding-right:10px;">
            <select id="playbackrate" name="playbackrate">
                <?php echo get_playbackrate_selectoptions($currentplaybackrate); ?>
            </select>
            <br />
            <span id="slower" class="click">
                <img src="/assets/icons/minus.png" alt="Slower" title="Slower" style="margin-top:3px" />
            </span>
            &nbsp;
            <span id="stdspeed" class="click">
                <img src="/assets/icons/status-away.png" alt="Normal" title="Normal" style="margin-top:3px" />
            </span>
            &nbsp;
            <span id="faster" class="click">
                <img src="/assets/icons/plus.png" alt="Faster" title="Faster" style="margin-top:3px" />
            </span>
        </td>
    </tr>
</table>
<!-- HTML5 Audio initialization -->
<script type="text/javascript">
    //<![CDATA[
    $(document).ready(function() {
        // Initialize the HTML5 audio player
        setupAudioPlayer(
            'lwt-audio-player',
            <?php echo Escaping::prepareTextdataJs(encodeURI($audio)); ?>,
            <?php echo (int) $offset; ?>,
            <?php echo json_encode($repeatMode); ?>
        );

        // Setup play/pause/stop button handlers
        $('.lwt-audio-play').on('click', function() {
            const player = getAudioPlayer();
            if (player) player.play();
        });
        $('.lwt-audio-pause').on('click', function() {
            const player = getAudioPlayer();
            if (player) player.pause();
        });
        $('.lwt-audio-stop').on('click', function() {
            const player = getAudioPlayer();
            if (player) player.stop();
        });
        $('.lwt-audio-mute').on('click', function() {
            const player = getAudioPlayer();
            if (player) player.mute();
        });
        $('.lwt-audio-unmute').on('click', function() {
            const player = getAudioPlayer();
            if (player) player.unmute();
        });
    });
    //]]>
</script>
    <?php
}

/**
 * Create a legacy jPlayer audio player (non-Vite mode).
 *
 * @param string $audio               Audio URL
 * @param int    $offset              Offset from the beginning
 * @param bool   $repeatMode          Whether to repeat
 * @param int    $currentplayerseconds Seconds to skip
 * @param int    $currentplaybackrate  Playback rate (10 = 1.0x)
 *
 * @return void
 */
function makeLegacyAudioPlayer($audio, $offset, $repeatMode, $currentplayerseconds, $currentplaybackrate)
{
    ?>
<link type="text/css" href="<?php print_file_path('css/jplayer.css');?>" rel="stylesheet" />
<script type="text/javascript" src="/assets/js/jquery.jplayer.js"></script>
<table style="margin-top: 5px; margin-left: auto; margin-right: auto;" cellspacing="0" cellpadding="0">
    <tr>
        <td class="center borderleft" style="padding-left:10px;">
            <span id="do-single" class="click<?php echo ($repeatMode ? '' : ' hide'); ?>"
                style="color:#09F;font-weight: bold;" title="Toggle Repeat (Now ON)">
                <img src="/assets/icons/arrow-repeat.png" alt="Toggle Repeat (Now ON)" title="Toogle Repeat (Now ON)" style="width:24px;height:24px;">
            </span>
            <span id="do-repeat" class="click<?php echo ($repeatMode ? ' hide' : ''); ?>"
                style="color:grey;font-weight: bold;" title="Toggle Repeat (Now OFF)">
                <img src="/assets/icons/arrow-norepeat.png" alt="Toggle Repeat (Now OFF)" title="Toggle Repeat (Now OFF)" style="width:24px;height:24px;">
            </span>
        </td>
        <td class="center bordermiddle">&nbsp;</td>
        <td class="bordermiddle">
            <div id="jquery_jplayer_1" class="jp-jplayer"></div>
            <div class="jp-audio-container">
                <div id="jp_container_1" class="jp-audio">
                    <div class="jp-type-single">
                        <div id="jp_interface_1" class="jp-interface">
                            <ul class="jp-controls">
                                <li><a href="#" class="jp-play">play</a></li>
                                <li><a href="#" class="jp-pause">pause</a></li>
                                <li><a href="#" class="jp-stop">stop</a></li>
                                <li><a href="#" class="jp-mute">mute</a></li>
                                <li><a href="#" class="jp-unmute">unmute</a></li>
                            </ul>
                            <div class="jp-progress-container">
                                <div class="jp-progress">
                                    <div class="jp-seek-bar">
                                        <div class="jp-play-bar">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="jp-volume-bar-container">
                                <div class="jp-volume-bar">
                                    <div class="jp-volume-bar-value">
                                    </div>
                                </div>
                            </div>
                            <div class="jp-current-time">
                            </div>
                            <div class="jp-duration">
                            </div>
                        </div>
                        <div id="jp_playlist_1" class="jp-playlist">
                        </div>
                    </div>
                </div>
            </div>
        </td>
        <td class="center bordermiddle">&nbsp;</td>
        <td class="center bordermiddle">
            <select id="backtime" name="backtime" onchange="{do_ajax_save_setting('currentplayerseconds',document.getElementById('backtime').options[document.getElementById('backtime').selectedIndex].value);}">
                <?php echo get_seconds_selectoptions($currentplayerseconds); ?>
            </select>
            <br />
            <span id="backbutt" class="click">
                <img src="/assets/icons/arrow-circle-225-left.png" alt="Rewind n seconds" title="Rewind n seconds" />
            </span>&nbsp;&nbsp;
            <span id="forwbutt" class="click">
                <img src="/assets/icons/arrow-circle-315.png" alt="Forward n seconds" title="Forward n seconds" />
            </span>
            <span id="playTime" class="hide"></span>
        </td>
        <td class="center bordermiddle">&nbsp;</td>
        <td class="center borderright" style="padding-right:10px;">
            <select id="playbackrate" name="playbackrate">
                <?php echo get_playbackrate_selectoptions($currentplaybackrate); ?>
            </select>
            <br />
            <span id="slower" class="click">
                <img src="/assets/icons/minus.png" alt="Slower" title="Slower" style="margin-top:3px" />
            </span>
            &nbsp;
            <span id="stdspeed" class="click">
                <img src="/assets/icons/status-away.png" alt="Normal" title="Normal" style="margin-top:3px" />
            </span>
            &nbsp;
            <span id="faster" class="click">
                <img src="/assets/icons/plus.png" alt="Faster" title="Faster" style="margin-top:3px" />
            </span>
        </td>
    </tr>
</table>
<!-- Audio controls once that page was loaded -->
<script type="text/javascript">
    //<![CDATA[

    const MEDIA = <?php echo Escaping::prepareTextdataJs(encodeURI($audio)); ?>;
    const MEDIA_OFFSET = <?php echo $offset; ?>;

    /**
     * Get the extension of a file.
     *
     * @param {string} file File path
     *
     * @returns {string} File extension
     */
    function get_extension(file) {
        return file.split('.').pop();
    }

    /**
     * Import audio data when jPlayer is ready.
     *
     * @returns {undefined}
     */
    function addjPlayerMedia () {
        const ext = get_extension(MEDIA);
        let media_obj = {};
        if (ext == 'mp3') {
            media_obj['mp3'] = MEDIA;
        } else if (ext == 'ogg') {
            media_obj['oga'] = media_obj['ogv'] = media_obj['mp3'] = MEDIA;
        } else if (ext == 'wav') {
            media_obj['wav'] = media_obj['mp3'] = MEDIA;
        } else if (ext == 'mp4') {
            media_obj['mp4'] = MEDIA;
        } else if (ext == 'webm') {
            media_obj['webma'] = media_obj['webmv'] = MEDIA;
        } else {
            media_obj['mp3'] = MEDIA;
        }
        $(this)
        .jPlayer("setMedia", media_obj)
        .jPlayer("pause", MEDIA_OFFSET);
    }

    /**
     * Prepare media interactions with jPlayer.
     *
     * @returns {void}
     */
    function prepareMediaInteractions() {

        $("#jquery_jplayer_1").jPlayer({
            ready: addjPlayerMedia,
            swfPath: "js",
            noVolume: {
                ipad: /^no$/, iphone: /^no$/, ipod: /^no$/,
                android_pad: /^no$/, android_phone: /^no$/,
                blackberry: /^no$/, windows_ce: /^no$/, iemobile: /^no$/, webos: /^no$/,
                playbook: /^no$/
            }
        });

        $("#jquery_jplayer_1")
        .on($.jPlayer.event.timeupdate, function(event) {
            $("#playTime").text(Math.floor(event.jPlayer.status.currentTime));
        });

        $("#jquery_jplayer_1")
        .on($.jPlayer.event.play, function(event) {
            lwt_audio_controller.setCurrentPlaybackRate();
        });

        $("#slower").on('click', lwt_audio_controller.setSlower);
        $("#faster").on('click', lwt_audio_controller.setFaster);
        $("#stdspeed").on('click', lwt_audio_controller.setStdSpeed);
        $("#backbutt").on('click', lwt_audio_controller.clickBackward);
        $("#forwbutt").on('click', lwt_audio_controller.clickForward);
        $("#do-single").on('click', lwt_audio_controller.clickSingle);
        $("#do-repeat").on('click', lwt_audio_controller.clickRepeat);
        $("#playbackrate").on('change', lwt_audio_controller.setNewPlaybackRate);
        $("#backtime").on('change', lwt_audio_controller.setNewPlayerSeconds);

        if (<?php echo json_encode($repeatMode); ?>) {
            lwt_audio_controller.clickRepeat();
        }
    }

    $(document).ready(prepareMediaInteractions);
    //]]>
</script>
    <?php
}

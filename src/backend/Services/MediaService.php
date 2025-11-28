<?php

/**
 * Media Service - Business logic for media file handling and player generation
 *
 * This service handles:
 * - Media file discovery (audio/video in media folder)
 * - HTML media player generation (audio/video)
 * - Support for local files and streaming platforms (YouTube, Vimeo, Dailymotion)
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Services {

use Lwt\Database\Escaping;
use Lwt\Database\Settings;

/**
 * Service class for media file handling and player generation.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class MediaService
{
    /**
     * Supported media file formats.
     *
     * @var string[]
     */
    private const SUPPORTED_FORMATS = ['mp3', 'mp4', 'ogg', 'wav', 'webm'];

    /**
     * Audio-only formats.
     *
     * @var string[]
     */
    private const AUDIO_FORMATS = ['.mp3', '.wav', '.ogg'];

    // =========================================================================
    // Media File Discovery
    // =========================================================================

    /**
     * Return the list of media files found in folder, recursively.
     *
     * @param string $dir Directory to search into.
     *
     * @return array{paths: string[], folders: string[]}
     */
    public function searchMediaPaths(string $dir): array
    {
        $isWindows = str_starts_with(strtoupper(PHP_OS), "WIN");
        $mediadir = scandir($dir);
        $paths = [
            "paths" => [$dir],
            "folders" => [$dir]
        ];

        // For each item in directory - add files to paths
        foreach ($mediadir as $path) {
            if (str_starts_with($path, ".") || is_dir($dir . '/' . $path)) {
                continue;
            }
            // Encode path for Windows
            if ($isWindows) {
                $encoded = mb_convert_encoding($path, 'UTF-8', 'Windows-1252');
            } else {
                $encoded = $path;
            }
            $ex = strtolower(pathinfo($encoded, PATHINFO_EXTENSION));
            if (in_array($ex, self::SUPPORTED_FORMATS)) {
                $paths["paths"][] = $dir . '/' . $encoded;
            }
        }

        // Do the folder in a second time to get a better ordering
        foreach ($mediadir as $path) {
            if (str_starts_with($path, ".") || !is_dir($dir . '/' . $path)) {
                continue;
            }
            // For each folder, recursive search
            $subfolderPaths = $this->searchMediaPaths($dir . '/' . $path);
            $paths["folders"] = array_merge($paths["folders"], $subfolderPaths["folders"]);
            $paths["paths"] = array_merge($paths["paths"], $subfolderPaths["paths"]);
        }

        return $paths;
    }

    /**
     * Return the paths for all media files.
     *
     * @return array{base_path: string, paths?: string[], folders?: string[], error?: string}
     */
    public function getMediaPaths(): array
    {
        $answer = [
            "base_path" => basename(getcwd())
        ];

        if (!file_exists('media')) {
            $answer["error"] = "does_not_exist";
        } elseif (!is_dir('media')) {
            $answer["error"] = "not_a_directory";
        } else {
            $paths = $this->searchMediaPaths('media');
            $answer["paths"] = $paths["paths"];
            $answer["folders"] = $paths["folders"];
        }

        return $answer;
    }

    /**
     * Get the different options to display as acceptable media files.
     *
     * @param string $dir Directory containing files
     *
     * @return string HTML-formatted OPTION tags
     */
    public function getMediaPathOptions(string $dir): string
    {
        $r = "";
        $options = $this->searchMediaPaths($dir);
        foreach ($options["paths"] as $op) {
            if (in_array($op, $options["folders"])) {
                $r .= '<option disabled="disabled">-- Directory: ' . tohtml($op) . '--</option>';
            } else {
                $r .= '<option value="' . tohtml($op) . '">' . tohtml($op) . '</option>';
            }
        }
        return $r;
    }

    /**
     * Generate HTML for media path selection UI.
     *
     * @param string $fieldName HTML field name for media string in form.
     *                          Will be used as this.form.[$fieldName] in JS.
     *
     * @return string HTML-formatted string for media selection
     */
    public function getMediaPathSelector(string $fieldName): string
    {
        $media = $this->getMediaPaths();
        $r = '<p>
            YouTube, Dailymotion, Vimeo or choose a file in "../' . $media["base_path"] . '/media"
            <br />
            (only mp3, mp4, ogg, wav, webm files shown):
        </p>
        <p style="display: none;" id="mediaSelectErrorMessage"></p>
        <img style="float: right; display: none;" id="mediaSelectLoadingImg" src="/assets/icons/waiting2.gif" />
        <select name="Dir" style="display: none; width: 200px;"
        onchange="{val=this.form.Dir.options[this.form.Dir.selectedIndex].value; if (val != \'\') this.form.'
            . $fieldName . '.value = val; this.form.Dir.value=\'\';}">
        </select>
        <span class="click" onclick="do_ajax_update_media_select();" style="margin-left: 16px;">
            <img src="/assets/icons/arrow-circle-135.png" title="Refresh Media Selection" alt="Refresh Media Selection" />
            Refresh
        </span>
        <script type="text/javascript">
            // Populate fields with data
            media_select_receive_data(' . json_encode($media) . ');
        </script>';
        return $r;
    }

    // =========================================================================
    // Media Player Generation
    // =========================================================================

    /**
     * Create an HTML media player, audio or video.
     *
     * @param string $path   URL or local file path
     * @param int    $offset Offset from the beginning of the video
     *
     * @return void
     */
    public function renderMediaPlayer(string $path, int $offset = 0): void
    {
        if ($path === '') {
            return;
        }

        $extension = substr($path, -4);
        if (in_array($extension, self::AUDIO_FORMATS)) {
            $this->renderAudioPlayer($path, $offset);
        } else {
            $this->renderVideoPlayer($path, $offset);
        }
    }

    /**
     * Create an embed video player.
     *
     * @param string $path   URL or local file path
     * @param int    $offset Offset from the beginning of the video
     *
     * @return void
     */
    public function renderVideoPlayer(string $path, int $offset = 0): void
    {
        $online = false;
        $url = null;

        // Check for YouTube (youtube.com/watch?v=)
        if (
            preg_match(
                "/(?:https:\/\/)?www\.youtube\.com\/watch\?v=([\d\w]+)/iu",
                $path,
                $matches
            )
        ) {
            $url = "https://www.youtube.com/embed/" . $matches[1] . "?t=" . $offset;
            $online = true;
        }
        // Check for YouTube short URL (youtu.be/)
        elseif (
            preg_match(
                "/(?:https:\/\/)?youtu\.be\/([\d\w]+)/iu",
                $path,
                $matches
            )
        ) {
            $url = "https://www.youtube.com/embed/" . $matches[1] . "?t=" . $offset;
            $online = true;
        }
        // Check for Dailymotion
        elseif (
            preg_match(
                "/(?:https:\/\/)?dai\.ly\/([^\?]+)/iu",
                $path,
                $matches
            )
        ) {
            $url = "https://www.dailymotion.com/embed/video/" . $matches[1];
            $online = true;
        }
        // Check for Vimeo
        elseif (
            preg_match(
                "/(?:https:\/\/)?vimeo\.com\/(\d+)/iu",
                $path,
                $matches
            )
        ) {
            $url = "https://player.vimeo.com/video/" . $matches[1] . "#t=" . $offset . "s";
            $online = true;
        }

        if ($online) {
            $this->renderOnlineVideoPlayer($url);
        } else {
            $this->renderLocalVideoPlayer($path);
        }
    }

    /**
     * Render an online video player in an iframe.
     *
     * @param string $url Video embed URL
     *
     * @return void
     */
    private function renderOnlineVideoPlayer(string $url): void
    {
        ?>
<iframe style="width: 100%; height: 30%;"
src="<?php echo $url ?>"
title="Video player"
frameborder="0"
allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
allowfullscreen type="text/html">
</iframe>
        <?php
    }

    /**
     * Render a local video player.
     *
     * @param string $path Local file path
     *
     * @return void
     */
    private function renderLocalVideoPlayer(string $path): void
    {
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

    /**
     * Create an HTML audio player.
     *
     * @param string $audio  Audio URL
     * @param int    $offset Offset from the beginning of the audio
     *
     * @return void
     */
    public function renderAudioPlayer(string $audio, int $offset = 0): void
    {
        if ($audio === '') {
            return;
        }
        $audio = trim($audio);
        $repeatMode = (bool) Settings::getZeroOrOne('currentplayerrepeatmode', 0);
        $currentplayerseconds = Settings::get('currentplayerseconds');
        if ($currentplayerseconds === '') {
            $currentplayerseconds = 5;
        }
        $currentplaybackrate = Settings::get('currentplaybackrate');
        if ($currentplaybackrate === '') {
            $currentplaybackrate = 10;
        }

        // Use HTML5 audio player for Vite mode, jPlayer for legacy mode
        if (\should_use_vite()) {
            $this->renderHtml5AudioPlayer(
                $audio,
                $offset,
                $repeatMode,
                (int) $currentplayerseconds,
                (int) $currentplaybackrate
            );
        } else {
            $this->renderLegacyAudioPlayer(
                $audio,
                $offset,
                $repeatMode,
                (int) $currentplayerseconds,
                (int) $currentplaybackrate
            );
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
     */
    public function renderHtml5AudioPlayer(
        string $audio,
        int $offset,
        bool $repeatMode,
        int $currentplayerseconds,
        int $currentplaybackrate
    ): void {
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
            <?php echo $offset; ?>,
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
    public function renderLegacyAudioPlayer(
        string $audio,
        int $offset,
        bool $repeatMode,
        int $currentplayerseconds,
        int $currentplaybackrate
    ): void {
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
}

} // End of Lwt\Services namespace

// =============================================================================
// Legacy Global Functions (Backward Compatibility)
// These must be in the global namespace for backward compatibility
// =============================================================================

namespace {

use Lwt\Services\MediaService;

/**
 * Return the list of media files found in folder, recursively.
 *
 * @param string $dir Directory to search into.
 *
 * @return array{paths: string[], folders: string[]}
 *
 * @deprecated 3.0.0 Use MediaService::searchMediaPaths() instead
 */
function media_paths_search(string $dir): array
{
    $service = new MediaService();
    return $service->searchMediaPaths($dir);
}

/**
 * Return the paths for all media files.
 *
 * @return array{base_path: string, paths?: string[], folders?: string[], error?: string}
 *
 * @deprecated 3.0.0 Use MediaService::getMediaPaths() instead
 */
function get_media_paths(): array
{
    $service = new MediaService();
    return $service->getMediaPaths();
}

/**
 * Get the different options to display as acceptable media files.
 *
 * @param string $dir Directory containing files
 *
 * @return string HTML-formatted OPTION tags
 *
 * @deprecated 3.0.0 Use MediaService::getMediaPathOptions() instead
 */
function selectmediapathoptions(string $dir): string
{
    $service = new MediaService();
    return $service->getMediaPathOptions($dir);
}

/**
 * Select the path for a media (audio or video).
 *
 * @param string $f HTML field name for media string in form.
 *
 * @return string HTML-formatted string for media selection
 *
 * @deprecated 3.0.0 Use MediaService::getMediaPathSelector() instead
 */
function selectmediapath(string $f): string
{
    $service = new MediaService();
    return $service->getMediaPathSelector($f);
}

/**
 * Create an HTML media player, audio or video.
 *
 * @param string $path   URL or local file path
 * @param int    $offset Offset from the beginning of the video
 *
 * @return void
 *
 * @deprecated 3.0.0 Use MediaService::renderMediaPlayer() instead
 */
function makeMediaPlayer(string $path, int $offset = 0): void
{
    $service = new MediaService();
    $service->renderMediaPlayer($path, $offset);
}

/**
 * Create an embed video player.
 *
 * @param string $path   URL or local file path
 * @param int    $offset Offset from the beginning of the video
 *
 * @return void
 *
 * @deprecated 3.0.0 Use MediaService::renderVideoPlayer() instead
 */
function makeVideoPlayer(string $path, int $offset = 0): void
{
    $service = new MediaService();
    $service->renderVideoPlayer($path, $offset);
}

/**
 * Create an HTML audio player.
 *
 * @param string $audio  Audio URL
 * @param int    $offset Offset from the beginning of the audio
 *
 * @return void
 *
 * @deprecated 3.0.0 Use MediaService::renderAudioPlayer() instead
 */
function makeAudioPlayer(string $audio, int $offset = 0): void
{
    $service = new MediaService();
    $service->renderAudioPlayer($audio, $offset);
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
 *
 * @deprecated 3.0.0 Use MediaService::renderHtml5AudioPlayer() instead
 */
function makeHtml5AudioPlayer(
    string $audio,
    int $offset,
    bool $repeatMode,
    int $currentplayerseconds,
    int $currentplaybackrate
): void {
    $service = new MediaService();
    $service->renderHtml5AudioPlayer(
        $audio,
        $offset,
        $repeatMode,
        $currentplayerseconds,
        $currentplaybackrate
    );
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
 *
 * @deprecated 3.0.0 Use MediaService::renderLegacyAudioPlayer() instead
 */
function makeLegacyAudioPlayer(
    string $audio,
    int $offset,
    bool $repeatMode,
    int $currentplayerseconds,
    int $currentplaybackrate
): void {
    $service = new MediaService();
    $service->renderLegacyAudioPlayer(
        $audio,
        $offset,
        $repeatMode,
        $currentplayerseconds,
        $currentplaybackrate
    );
}

} // End of global namespace

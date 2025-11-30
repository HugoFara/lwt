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
        data-action="media-dir-select" data-target-field="' . htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') . '">
        </select>
        <span class="click" data-action="refresh-media-select" style="margin-left: 16px;">
            <img src="/assets/icons/arrow-circle-135.png" title="Refresh Media Selection" alt="Refresh Media Selection" />
            Refresh
        </span>
        <script type="application/json" data-lwt-media-select-config>' . json_encode($media) . '</script>';
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

        // Always use HTML5 audio player (jPlayer removed)
        $this->renderHtml5AudioPlayer(
            $audio,
            $offset,
            $repeatMode,
            (int) $currentplayerseconds,
            (int) $currentplaybackrate
        );
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
<!-- HTML5 Audio initialization config -->
<script type="application/json" data-lwt-audio-player-config>
<?php echo json_encode([
    'containerId' => 'lwt-audio-player',
    'mediaUrl' => encodeURI($audio),
    'offset' => $offset,
    'repeatMode' => $repeatMode
]); ?>
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

} // End of global namespace

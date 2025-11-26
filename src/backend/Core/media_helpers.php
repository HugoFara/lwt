<?php

/**
 * \file
 * \brief Media file handling utilities.
 *
 * Functions for searching and selecting media files (audio/video).
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-media-helpers.html
 * @since    2.10.0-fork
 */

/**
 * Return the list of media files found in folder, recursively.
 *
 * @param string $dir Directory to search into.
 *
 * @return (mixed|string)[][]
 *
 * @psalm-return array{paths: list{0: string, 1?: mixed|string,...}, folders: list{0: string, 1?: mixed|string,...}}
 */
function media_paths_search($dir): array
{
    $is_windows = str_starts_with(strtoupper(PHP_OS), "WIN");
    $mediadir = scandir($dir);
    $formats = array('mp3', 'mp4', 'ogg', 'wav', 'webm');
    $paths = array(
        "paths" => array($dir),
        "folders" => array($dir)
    );
    // For each item in directory
    foreach ($mediadir as $path) {
        if (str_starts_with($path, ".") || is_dir($dir . '/' . $path)) {
            continue;
        }
        // Add files to paths
        if ($is_windows) {
            $encoded = mb_convert_encoding($path, 'UTF-8', 'Windows-1252');
        } else {
            $encoded = $path;
        }
        $ex = strtolower(pathinfo($encoded, PATHINFO_EXTENSION));
        if (in_array($ex, $formats)) {
            $paths["paths"][] = $dir . '/' . $encoded;
        }
    }
    // Do the folder in a second time to get a better ordering
    foreach ($mediadir as $path) {
        if (str_starts_with($path, ".") || !is_dir($dir . '/' . $path)) {
            continue;
        }
        // For each folder, recursive search
        $subfolder_paths = media_paths_search($dir . '/' . $path);
        $paths["folders"] = array_merge($paths["folders"], $subfolder_paths["folders"]);
        $paths["paths"] = array_merge($paths["paths"], $subfolder_paths["paths"]);
    }
    return $paths;
}

/**
 * Return the paths for all media files.
 *
 * @return ((mixed|string)[]|string)[]
 *
 * @psalm-return array{base_path: string, paths?: list{0: string, 1?: mixed|string,...}, folders?: list{0: string, 1?: mixed|string,...}, error?: 'does_not_exist'|'not_a_directory'}
 */
function get_media_paths(): array
{
    $answer = array(
        "base_path" => basename(getcwd())
    );
    if (!file_exists('media')) {
        $answer["error"] = "does_not_exist";
    } elseif (!is_dir('media')) {
        $answer["error"] = "not_a_directory";
    } else {
        $paths = media_paths_search('media');
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
function selectmediapathoptions($dir): string
{
    $r = "";
    //$r = '<option disabled="disabled">-- Directory: ' . tohtml($dir) . ' --</option>';
    $options = media_paths_search($dir);
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
 * Select the path for a media (audio or video).
 *
 * @param string $f HTML field name for media string in form. Will be used as this.form.[$f] in JS.
 *
 * @return string HTML-formatted string for media selection
 */
function selectmediapath($f): string
{
    $media = get_media_paths();
    $r = '<p>
        YouTube, Dailymotion, Vimeo or choose a file in "../' . $media["base_path"] . '/media"
        <br />
        (only mp3, mp4, ogg, wav, webm files shown):
    </p>
    <p style="display: none;" id="mediaSelectErrorMessage"></p>
    <img style="float: right; display: none;" id="mediaSelectLoadingImg" src="/assets/icons/waiting2.gif" />
    <select name="Dir" style="display: none; width: 200px;"
    onchange="{val=this.form.Dir.options[this.form.Dir.selectedIndex].value; if (val != \'\') this.form.'
        . $f . '.value = val; this.form.Dir.value=\'\';}">
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

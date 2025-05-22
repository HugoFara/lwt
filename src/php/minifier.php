<?php
/**
 * \file
 * \brief JS and CSS minifier.
 *
 * Use this script to minify JS and CSS files from src/js and src/css to js/ and
 * css/.
 *
 * PHP version 8.1
 *
 * @category Documentation
 * @package Lwt_Documentation
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-minifier.html
 * @since   2.0.3-fork
 * @since   2.9.1-fork $jsFiles was replaced by LWT_JS_FILES and $cssFiles by
 *                      LWT_CSS_FILES
 */
require __DIR__ . '/../../vendor/autoload.php';
use MatthiasMullie\Minify;


/**
 * @var string[] LWT_JS_FILES All the paths of JS files to be minified.
 *
 * @since 2.10.0-fork Adds src/js/overlib_interface.js and src/js/text_events.js
 */
define(
    'LWT_JS_FILES',
    array(
        'src/js/audio_controller.js', 'src/js/third_party/countuptimer.js',
        'src/js/jq_feedwizard.js', 'src/js/text_events.js',
        'src/js/jq_pgm.js', 'src/js/overlib_interface.js', 'src/js/pgm.js',
        'src/js/translation_api.js', 'src/js/unloadformcheck.js',
        'src/js/third_party/sorttable.js', 'src/js/user_interactions.js',
    )
);

/**
 * @var string[] LWT_CSS_FILES All the paths of CSS files to be minified.
 */
define(
    'LWT_CSS_FILES',
    array(
        'src/css/css_charts.css', 'src/css/feed_wizard.css', 'src/css/gallery.css',
        'src/css/jplayer.css', 'src/css/jquery-ui.css', 'src/css/jquery.tagit.css',
        'src/css/styles.css',
    )
);


/**
 * Minify a JavaScript file and outputs the result to js/
 *
 * @param string $path       Input file path with extension.
 * @param string $outputPath Output file path with extension
 *
 * @return string Minified content
 *
 * @since 2.2.2-fork Relative paths in the returned content is the same as the saved content.
 */
function minifyJS($path, $outputPath)
{
    $minifier = new Minify\JS();
    $minifier->add($path);
    // Save minified file to disk
    return $minifier->minify($outputPath);
}

/**
 * Minify a JavaScript file and outputs the result to css/
 *
 * @param string $path       Input file path with extension.
 * @param string $outputPath Output file path with extension
 *
 * @return string Minified content
 *
 * @since 2.2.2-fork Relative paths in the returned content is the same as the saved content.
 */
function minifyCSS($path, $outputPath)
{
    $minifier = new Minify\CSS();
    $minifier->add($path);
    // Save minified file to disk
    return $minifier->minify($outputPath);
}

/**
 * Minify all JavaScript files
 *
 * @return string Minified code
 *
 * @since 2.0.3-fork
 * @since 2.3.0-fork JS code is "combined" above being minified: only one file is outputted.
 */
function minifyAllJS(): string
{
    $minifier = new Minify\JS();
    foreach (LWT_JS_FILES as $path) {
        if (file_exists($path)) {
            $minifier->add($path);
        }
    }
    // Save minified file to disk
    return $minifier->minify("js/pgm.js");
}

/**
 * Minify all Cascading-Style Sheet (CSS) files
 *
 * @return void
 *
 * @since 2.0.3-fork
 */
function minifyAllCSS()
{
    foreach (LWT_CSS_FILES as $path) {
        $name = basename($path);
        if (file_exists($path)) {
            minifyCSS($path, 'css/' . $name);
        }
    }
}

/**
 * Regenerate a single theme.
 *
 * @param string $parent_folder Path to the parent folder (I. E. src/themes/)
 * @param string $theme_folder  Name of the theme folder
 *
 * @return void
 */
function regenerateSingleTheme($parent_folder, $theme_folder)
{
    if (!is_dir('themes/' . $theme_folder)) {
        mkdir('themes/' . $theme_folder);
    }
    $file_scan = scandir($parent_folder . $theme_folder);
    foreach ($file_scan as $file) {
        if (!is_dir($file) && $file != '.' && $file != '..') {
            $filepath = $parent_folder . $theme_folder . '/' . $file;
            $outputpath = 'themes/' . $theme_folder . '/' . $file;
            if (str_ends_with($filepath, '.css')) {
                minifyCSS($filepath, $outputpath);
            } else {
                copy($filepath, $outputpath);
            }
        }
    }
}

/**
 * Find and regenerate all themes. CSS is minified while other files are copied.
 *
 * Nested folders are ignored.
 *
 * @return void
 */
function regenerateThemes()
{
    $folder = 'src/themes/';
    $folder_scan = scandir($folder);
    foreach ($folder_scan as $parent_file) {
        if (is_dir($folder . $parent_file)
            && $parent_file != '.' && $parent_file != '..'
        ) {
            regenerateSingleTheme($folder, $parent_file);
        }
    }
}

/**
 * One-do-all command to minify all your JS, CSS, and regenerate themes.
 *
 * @return void
 */
function minify_everything()
{
    echo "Minifying CSS...\n";
    minifyAllCSS();
    echo "Minifying JS...\n";
    minifyAllJS();
    echo "Regenerating themes...\n";
    regenerateThemes();
}

?>
<?php

/**
 * \file
 * \brief Theme CSS minifier.
 *
 * Use this script to regenerate theme CSS files from src/frontend/themes to assets/themes/.
 *
 * Note: JS and main CSS are now built with Vite (npm run build).
 *
 * PHP version 8.1
 *
 * @category Build
 * @package Lwt_Build
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-minifier.html
 * @since   2.0.3-fork
 * @since   3.0.0-fork JS and CSS now built with Vite, this file only handles themes
 */

require __DIR__ . '/../../vendor/autoload.php';
use MatthiasMullie\Minify;


/**
 * Minify a CSS file and output to the specified path.
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
 * Regenerate a single theme.
 *
 * @param string $parent_folder Path to the parent folder (I. E. src/themes/)
 * @param string $theme_folder  Name of the theme folder
 *
 * @return void
 */
function regenerateSingleTheme($parent_folder, $theme_folder)
{
    if (!is_dir('assets/themes/' . $theme_folder)) {
        mkdir('assets/themes/' . $theme_folder);
    }
    $file_scan = scandir($parent_folder . $theme_folder);
    foreach ($file_scan as $file) {
        if (!is_dir($file) && $file != '.' && $file != '..') {
            $filepath = $parent_folder . $theme_folder . '/' . $file;
            $outputpath = 'assets/themes/' . $theme_folder . '/' . $file;
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
    echo "Regenerating themes...\n";
    $folder = 'src/frontend/themes/';
    $folder_scan = scandir($folder);
    foreach ($folder_scan as $parent_file) {
        if (
            is_dir($folder . $parent_file)
            && $parent_file != '.' && $parent_file != '..'
        ) {
            echo "  - $parent_file\n";
            regenerateSingleTheme($folder, $parent_file);
        }
    }
    echo "Done.\n";
}

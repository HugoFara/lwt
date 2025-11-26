<?php

/**
 * \file
 * \brief Proceed to the general settings.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-settings.html
 * @since   2.0.3-fork
 */

require_once __DIR__ . '/Globals.php';

use Lwt\Core\Globals;

// Initialize the Globals class with default values
Globals::initialize();

/**
 * Reload $setting_data if necessary
 *
 * @return array $setting_data
 */
function get_setting_data()
{
    static $setting_data;
    if (!$setting_data) {
        $setting_data = array(
        'set-text-h-frameheight-no-audio' => array(
            "dft" => '140', "num" => 1, "min" => 10, "max" => 999
        ),
        'set-text-h-frameheight-with-audio' => array(
            "dft" => '200', "num" => 1, "min" => 10, "max" => 999
        ),
        'set-text-l-framewidth-percent' => array(
            "dft" => '60', "num" => 1, "min" => 5, "max" => 95
        ),
        'set-text-r-frameheight-percent' => array(
            "dft" => '37', "num" => 1, "min" => 5, "max" => 95
        ),
        'set-test-h-frameheight' => array(
            "dft" => '140', "num" => 1, "min" => 10, "max" => 999
        ),
        'set-test-l-framewidth-percent' => array(
            "dft" => '50', "num" => 1, "min" => 5, "max" => 95
        ),
        'set-test-r-frameheight-percent' => array(
            "dft" => '50', "num" => 1, "min" => 5, "max" => 95
        ),
        'set-words-to-do-buttons' => array(
            "dft" => '1', "num" => 0
        ),
        'set-tooltip-mode' => array(
            "dft" => '2', "num" => 0
        ),
        'set-display-text-frame-term-translation' => array(
            "dft" => '1', "num" => 0
        ),
        'set-text-frame-annotation-position' => array(
            "dft" => '2', "num" => 0
        ),
        'set-test-main-frame-waiting-time' => array(
            "dft" => '0', "num" => 1, "min" => 0, "max" => 9999
        ),
        'set-test-edit-frame-waiting-time' => array(
            "dft" => '500', "num" => 1, "min" => 0, "max" => 99999999
        ),
        'set-test-sentence-count' => array(
            "dft" => '1', "num" => 0
        ),
        'set-tts' => array(
            "dft" => '1', "num" => 0
        ),
        'set-hts' => array(
            "dft" => '1', "num" => 0
        ),
        'set-term-sentence-count' => array(
            "dft" => '1', "num" => 0
        ),
        'set-archivedtexts-per-page' => array(
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-texts-per-page' => array(
            "dft" => '10', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-terms-per-page' => array(
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-tags-per-page' => array(
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-articles-per-page' => array(
            "dft" => '10', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-feeds-per-page' => array(
            "dft" => '50', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-max-articles-with-text' => array(
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-max-articles-without-text' => array(
            "dft" => '250', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-max-texts-per-feed' => array(
            "dft" => '20', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-ggl-translation-per-page' => array(
            "dft" => '100', "num" => 1, "min" => 1, "max" => 9999
        ),
        'set-regex-mode' => array(
            "dft" => '', "num" => 0
        ),
        'set-theme_dir' => array(
            "dft" => 'themes/default/', "num" => 0
        ),
        'set-text-visit-statuses-via-key' => array(
            "dft" => '', "num" => 0
        ),
        'set-term-translation-delimiters' => array(
            "dft" => '/;|', "num" => 0
        ),
        'set-mobile-display-mode' => array(
            "dft" => '0', "num" => 0
        ),
        'set-similar-terms-count' => array(
            "dft" => '0', "num" => 1, "min" => 0, "max" => 9
        ),
        'set-show-text-word-counts' => array(
            "dft" => '1', "num" => 0
        )
        );
    }
    return $setting_data;
}

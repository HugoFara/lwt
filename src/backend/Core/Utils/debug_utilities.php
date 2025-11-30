<?php declare(strict_types=1);
/**
 * \file
 * \brief Debug utility functions.
 *
 * Functions for debugging, timing, and development diagnostics.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core\Utils
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-debug-utilities.html
 * @since    3.0.0 Split from kernel_utility.php
 */

namespace Lwt\Core\Utils;

/**
 * Echo debugging informations.
 */
function showRequest(): void
{
    $olderr = error_reporting(0);
    echo "<pre>** DEBUGGING **********************************\n";
    echo '$GLOBALS...';
    print_r($GLOBALS);
    echo 'get_version_number()...';
    echo \Lwt\Core\get_version_number() . "\n";
    echo 'get_magic_quotes_gpc()...';
    echo "NOT EXISTS (FALSE)\n";
    echo "********************************** DEBUGGING **</pre>";
    error_reporting($olderr);
}

/**
 * Get the time since the last call
 *
 * @return float Time since last call
 */
function get_execution_time()
{
    static $microtime_start = null;
    if ($microtime_start === null) {
        $microtime_start = microtime(true);
        return 0.0;
    }
    return microtime(true) - $microtime_start;
}

/**
 * Debug function only.
 *
 * @param mixed  $var  A printable variable to debug
 * @param string $text Echoed text in HTML page
 *
 * @global bool $debug This functions doesn't do anything is $debug is false.
 */
function echodebug($var, $text): void
{
    if (\Lwt\Core\Globals::isDebug()) {
        echo "<pre> **DEBUGGING** " . tohtml($text) . ' = [[[';
        print_r($var);
        echo "]]]\n--------------</pre>";
    }
}

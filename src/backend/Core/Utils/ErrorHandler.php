<?php

declare(strict_types=1);

/**
 * \file
 * \brief Error handling utilities.
 *
 * Functions for displaying errors and handling fatal conditions.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-error-handling.html
 * @since    3.0.0 Split from kernel_utility.php
 */

namespace Lwt\Core\Utils;

/**
 * Error handling utilities.
 *
 * Provides methods for displaying errors and handling fatal conditions.
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class ErrorHandler
{
    /**
     * Make the script crash and prints an error message
     *
     * @param string $text Error text to output
     *
     * @return never
     *
     * @since 2.5.3-fork Add a link to the Discord community
     */
    public static function die(string $text): never
    {
        // In testing environment (PHPUnit), throw exception instead of dying
        if (class_exists('PHPUnit\Framework\TestCase', false)) {
            throw new \RuntimeException("Fatal Error: " . $text);
        }

        // In production, output HTML error and die (legacy behavior)
        echo '</select></p></div><div style="padding: 1em; color:red; font-size:120%; background-color:#CEECF5;">' .
        '<p><b>Fatal Error:</b> ' .
        htmlspecialchars($text, ENT_QUOTES, 'UTF-8') .
        "</p></div><hr /><pre>Backtrace:\n\n";
        debug_print_backtrace();
        echo '</pre><hr />
        <p>Signal this issue on
        <a href="https://github.com/HugoFara/lwt/issues/new/choose">GitHub</a> or
        <a href="https://discord.gg/xrkRZR2jtt">Discord</a>.</p>';
        exit('</body></html>');
    }
}

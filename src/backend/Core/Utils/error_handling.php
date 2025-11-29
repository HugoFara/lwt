<?php

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

/**
 * Make the script crash and prints an error message
 *
 * @param string $text Error text to output
 *
 * @return never
 *
 * @since 2.5.3-fork Add a link to the Discord community
 */
function my_die($text)
{
    // In testing environment (PHPUnit), throw exception instead of dying
    if (class_exists('PHPUnit\Framework\TestCase', false)) {
        throw new \RuntimeException("Fatal Error: " . $text);
    }

    // In production, output HTML error and die (legacy behavior)
    echo '</select></p></div><div style="padding: 1em; color:red; font-size:120%; background-color:#CEECF5;">' .
    '<p><b>Fatal Error:</b> ' .
    tohtml($text) .
    "</p></div><hr /><pre>Backtrace:\n\n";
    debug_print_backtrace();
    echo '</pre><hr />
    <p>Signal this issue on
    <a href="https://github.com/HugoFara/lwt/issues/new/choose">GitHub</a> or
    <a href="https://discord.gg/xrkRZR2jtt">Discord</a>.</p>';
    die('</body></html>');
}

/**
 * Display a error message vanishing after a few seconds.
 *
 * @param string $msg    Message to display.
 * @param bool   $noback If true, don't display a button to go back
 *
 * @return string HTML-formatted string for an automating vanishing message.
 */
function error_message_with_hide($msg, $noback): string
{
    if (trim($msg) == '') {
        return '';
    }
    if (substr($msg, 0, 5) == "Error") {
        return '<p class="red">*** ' . tohtml($msg) . ' ***' .
        ($noback ?
        '' :
        '<br /><input type="button" value="&lt;&lt; Go back and correct &lt;&lt;" data-action="back" />' ) .
        '</p>';
    }
    return '<p id="hide3" class="msgblue">+++ ' . tohtml($msg) . ' +++</p>';
}

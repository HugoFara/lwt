<?php

/**
 * \file
 * \brief Application version information.
 *
 * Contains version constants and functions for displaying version information.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-version.html
 * @since    2.10.0-fork Split from kernel_utility.php
 */

/**
 * Version of this current LWT application
 *
 * @var string
 */
define('LWT_APP_VERSION', '2.10.0-fork');

/**
 * Date of the lastest published release of LWT
 *
 * @var string
 */
define('LWT_RELEASE_DATE', "2024-04-01");

/**
 * Return LWT version for humans
 *
 * Version is hardcoded in this function.
 * For instance 1.6.31 (October 03 2016)
 *
 * @global bool $debug If true adds a red "DEBUG"
 *
 * @return string Version number HTML-formatted
 *
 * @psalm-return '2.9.1-fork (December 29 2023) <span class="red">DEBUG</span>'|'2.9.1-fork (December 29 2023)'
 */
function get_version(): string
{
    $formattedDate = date("F d Y", strtotime(LWT_RELEASE_DATE));
    $version = LWT_APP_VERSION . " ($formattedDate)";
    if (\Lwt\Core\Globals::isDebug()) {
        $version .= ' <span class="red">DEBUG</span>';
    }
    return $version;
}

/**
 * Return a machine readable version number.
 *
 * @return string Machine-readable version, for instance v001.006.031 for version 1.6.31.
 */
function get_version_number(): string
{
    $r = 'v';
    $v = get_version();
    // Escape any detail like "-fork"
    $v = preg_replace('/-\w+\d*/', '', $v);
    $pos = strpos($v, ' ', 0);
    if ($pos === false) {
        my_die('Wrong version: ' . $v);
    }
    $vn = preg_split("/[.]/", substr($v, 0, $pos));
    if (count($vn) < 3) {
        my_die('Wrong version: ' . $v);
    }
    for ($i = 0; $i < 3; $i++) {
        $r .= substr('000' . $vn[$i], -3);
    }
    return $r;
}

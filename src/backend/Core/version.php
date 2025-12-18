<?php declare(strict_types=1);
/**
 * \file
 * \brief Application version information.
 *
 * Contains version constants and functions for displaying version information.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-version.html
 * @since    3.0.0 Split from kernel_utility.php
 */

namespace Lwt\Core;

use Lwt\Core\Utils\ErrorHandler;

/**
 * Version of this current LWT application
 *
 * @var string
 */
const LWT_APP_VERSION = '2.10.0-fork';

/**
 * Date of the lastest published release of LWT
 *
 * @var string
 */
const LWT_RELEASE_DATE = "2024-04-01";

/**
 * Return LWT version for humans
 *
 * Version is hardcoded in this function.
 * For instance 1.6.31 (October 03 2016)
 *
 * @return string Version number HTML-formatted
 */
function getVersion(): string
{
    $formattedDate = \date("F d Y", \strtotime(LWT_RELEASE_DATE));
    return LWT_APP_VERSION . " ($formattedDate)";
}

/**
 * Return a machine readable version number.
 *
 * @return string Machine-readable version, for instance v001.006.031 for version 1.6.31.
 */
function getVersionNumber(): string
{
    $r = 'v';
    $v = getVersion();
    // Escape any detail like "-fork"
    $v = \preg_replace('/-\w+\d*/', '', $v);
    $pos = \strpos($v, ' ', 0);
    if ($pos === false) {
        ErrorHandler::die('Wrong version: ' . $v);
    }
    $vn = \preg_split("/[.]/", \substr($v, 0, $pos));
    if (\count($vn) < 3) {
        ErrorHandler::die('Wrong version: ' . $v);
    }
    for ($i = 0; $i < 3; $i++) {
        $r .= \substr('000' . $vn[$i], -3);
    }
    return $r;
}

// Define global constants for backward compatibility
if (!\defined('LWT_APP_VERSION')) {
    \define('LWT_APP_VERSION', LWT_APP_VERSION);
}
if (!\defined('LWT_RELEASE_DATE')) {
    \define('LWT_RELEASE_DATE', LWT_RELEASE_DATE);
}

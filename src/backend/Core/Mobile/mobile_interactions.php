<?php

/**
 * \file
 * \brief Handle interactions with mobile platforms
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-mobile-interactions.html
 * @since   2.2.0
 * @since   2.2.1 You should not longer use this library as mobile detect is removed.
 *                However, this interface is unchanged for backward compatibility.
 * @since   2.6.0 Mobile detect is back and does no longer use external libraries.
 */

require_once __DIR__ . '/../Bootstrap/db_bootstrap.php';

use Lwt\Database\Settings;

/**
 * Detect if the current request is from a mobile device.
 *
 * @return bool True if mobile mode should be activated
 *
 * @since 2.6.0-fork Uses user-agent detection without external library.
 */
function is_mobile(): bool
{
    $mobileDisplayMode = (int)Settings::getWithDefault('set-mobile-display-mode');
    if ($mobileDisplayMode == 2) {
        return true;
    }
    $mobile_detect = preg_match(
        "/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini" .
        "|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i",
        $_SERVER["HTTP_USER_AGENT"] ?? ''
    );
    if ($mobileDisplayMode == 0 && $mobile_detect) {
        return true;
    }

    return false;
}
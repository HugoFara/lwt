<?php
/**
 * \file
 * \brief Proceed to the general settings.
 *
 * PHP version 8.1
 *
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-settings.html
 * @since   2.0.3-fork
 */


/**
 * @var int $debug
 *
 * Debug switch / Display PHP error settings
 *
 * 1 = debugging on, 0 = .. off
 */
$GLOBALS['debug'] = 0;
$debug = &$GLOBALS['debug'];
/**
 * @var int $dsplerrors
 * 1 = display all errors on, 0 = .. off
 */
$GLOBALS['dsplerrors'] = 0;
$dsplerrors = &$GLOBALS['dsplerrors'];
/**
 * @var int $dspltime
 * 1 = display time on, 0 = .. off
 */
$GLOBALS['dspltime'] = 0;
$dspltime = &$GLOBALS['dspltime'];

?>
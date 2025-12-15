<?php declare(strict_types=1);
/**
 * \file
 * \brief WordPress Login Check
 *
 * WordPress login check integration for LWT
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-wp-logincheck.html
 * @since   2.0.3-fork
 */

namespace Lwt\Integration;

require_once __DIR__ . '/../Bootstrap/start_session.php';

if (isset($_SESSION['LWT-WP-User'])) {
    // WordPress user is authenticated
    // Note: Table prefix feature has been removed in 3.0.0
    // WordPress integration may need to be updated for multi-user mode
} else {
    $url = '';
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') == 'GET') {
        $url = $_SERVER['REQUEST_URI'] ?? '/';
    } elseif (isset($_SERVER['HTTP_REFERER'])) {
        $url = $_SERVER['HTTP_REFERER'];
    }
    if (strpos($url, "/") !== false) {
        $url = substr($url, strrpos($url, '/') + 1);
    }
    header("Location: ./wp_lwt_start.php?rd=" . urlencode($url));
    exit();
}

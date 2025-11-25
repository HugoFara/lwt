<?php

/**
 * \file
 * \brief Start LWT with WordPress
 *
 * To start LWT (and to login into WordPress), use this URL:
 * http://...path-to-wp-blog.../lwt/wp_lwt_start.php
 * Cookies must be enabled. A session cookie will be set.
 * The lwt installation must be in sub directory "lwt" under
 * the WordPress main drectory.
 * In the "lwt" directory, "connect.inc.php" must contain
 *            include "wp_logincheck.inc.php";
 * at the end!
 * To properly log out from both WordPress and LWT, use:
 * http://...path-to-wp-blog.../lwt/wp_lwt_stop.php
 *
 * PHP version 8.1
 *
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/wp-lwt-start.html
 * @since   1.5.5
 */

require_once 'Core/kernel_utility.php';
require_once '../wp-load.php' ;

if (is_user_logged_in()) {
    global $current_user;

    get_currentuserinfo();
    $wpuser = $current_user->ID;

    $err = @session_start();
    if ($err === false) {
        my_die('SESSION error (Impossible to start a PHP session)');
    }
    if (session_id() == '') {
        my_die('SESSION ID empty (Impossible to start a PHP session)');
    }
    if (!isset($_SESSION)) {
        my_die('SESSION array not set (Impossible to start a PHP session)');
    }

    $_SESSION['LWT-WP-User'] = $wpuser;
    $url = 'index.php';
    if (!empty($_REQUEST["rd"])) {
        $rd = $_REQUEST["rd"];
        // Check for multiple redirections?
        if (file_exists(preg_replace('/^([^?]+).*/', './$1', $rd))) {
            $url = $rd;
        }
    }
    header("Location: ./" . $url);
    exit();
} else {
    header("Location: ../wp-login.php?redirect_to=./lwt/wp_lwt_start.php");
    exit();
}

?>
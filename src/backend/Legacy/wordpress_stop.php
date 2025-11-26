<?php

/**
 * \file wp_lwt_stop.php
 * ---------------------
 * To properly log out from both WordPress and LWT, use:
 * http://...path-to-wp-blog.../lwt/wp_lwt_stop.php
 * (such a link is also provided on the LWT home page 'index.php')
 *
 * PHP version 8.1
 *
 * @since 1.5.5
 */

require_once '../wp-load.php' ;

wp_logout();

session_start();
session_unset();
session_destroy();
session_write_close();
setcookie(session_name(), '', 0, '/');
session_regenerate_id(true);

header("Location: ../wp-login.php?redirect_to=./lwt/wp_lwt_start.php");
exit;

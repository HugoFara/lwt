<?php

/**
 * Save a Setting (k/v) and redirect to URI u
 *
 * This file is a backward-compatibility shim. New code should use
 * the /admin/save-setting route instead.
 *
 * Call: save_setting_redirect.php?k=[key]&v=[value]&u=[RedirURI]
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since   2.6.0-fork You can omit either u, or (k, v).
 * @deprecated 3.0.0 Use /admin/save-setting route instead
 */

// Build the redirect URL with the same query parameters
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$redirectUrl = '/admin/save-setting';
if ($queryString !== '') {
    $redirectUrl .= '?' . $queryString;
}

header("Location: " . $redirectUrl);
exit();

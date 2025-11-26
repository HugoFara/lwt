<?php
/**
 * PHP Built-in Server Router
 *
 * This script enables clean URLs and legacy URL redirects when using
 * PHP's built-in web server (php -S localhost:8000 router.php)
 *
 * Usage: php -S localhost:8000 router.php
 */

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);

// Serve static files directly
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf', 'eot', 'map'];
$ext = pathinfo($path, PATHINFO_EXTENSION);
if (in_array(strtolower($ext), $staticExtensions) && file_exists(__DIR__ . $path)) {
    return false; // Let PHP's built-in server handle static files
}

// Route everything through index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';

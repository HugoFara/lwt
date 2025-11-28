<?php

/**
 * Google Translate interface
 *
 * Call: ggl.php?text=[text]&sl=[source language]&tl=[target language] ... translate text
 *     ... sent=[int] ... single sentence mode.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/ggl.html
 * @since    1.6.0
 * @since    2.7.0 Refactored with functional paradigm
 *
 * @deprecated 3.0.0 Use TranslationController::google() instead
 * @see \Lwt\Controllers\TranslationController::google()
 */

namespace Lwt\Legacy;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../Controllers/TranslationController.php';

use Lwt\Controllers\TranslationController;

// Delegate to the controller
$controller = new TranslationController();
$controller->google([]);

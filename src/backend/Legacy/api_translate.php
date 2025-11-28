<?php

/**
 * \file
 * \brief Get a translation from Web Dictionary
 *
 * Call 1: trans.php?x=1&t=[textid]&i=[textpos]
 *         Display translator for sentence in Text t, Pos i
 * Call 2: trans.php?x=2&t=[text]&i=[dictURI]
 *         translates text t with dict via dict-url i
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/trans.html
 * @since    1.0.3
 *
 * @deprecated 3.0.0 Use TranslationController::translate() instead
 * @see \Lwt\Controllers\TranslationController::translate()
 */

namespace Lwt\Legacy;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../Controllers/TranslationController.php';

use Lwt\Controllers\TranslationController;

// Delegate to the controller
$controller = new TranslationController();
$controller->translate([]);

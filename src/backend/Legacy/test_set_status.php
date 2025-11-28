<?php

/**
 * \file
 * \brief Change status of term while testing (Legacy wrapper)
 *
 * Call: set_test_status.php?wid=[wordid]&stchange=+1/-1&[ajax=1]
 *       set_test_status.php?wid=[wordid]&status=1..5/98/99&[ajax=1]
 *
 * This file now delegates to TestController for MVC architecture.
 * Maintained for backward compatibility with existing routes.
 *
 * PHP version 8.1
 *
 * @category Helper_Frame
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/do-test-header.html
 * @since    1.0.3
 *
 * @deprecated 3.0.0 Use TestController::setStatus() instead
 */

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';

use Lwt\Controllers\TestController;

require_once __DIR__ . '/../Controllers/TestController.php';

// Delegate to controller
$controller = new TestController();
$controller->setStatus([]);

<?php

/**
 * \file
 * \brief Start a test (frameset) - Legacy wrapper
 *
 * Call: do_test.php?lang=[langid]
 * Call: do_test.php?text=[textid]
 * Call: do_test.php?selection=1  (SQL via $_SESSION['testsql'])
 * Call: do_test.php?type=table for a table of words
 * Call: do_test.php?type=[1-5] for a test of words.
 *
 * This file now delegates to TestController for MVC architecture.
 * Maintained for backward compatibility with existing routes.
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/do-test.html
 * @since    1.0.3
 *
 * @deprecated 3.0.0 Use TestController::index() instead
 */

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';

use Lwt\Controllers\TestController;

require_once __DIR__ . '/../Controllers/TestController.php';

// Delegate to controller
$controller = new TestController();
$controller->index([]);

<?php

/**
 * Call Glosbe Translation API, analyze and present JSON results
 * for easily filling the "new word form"
 *
 * Call: glosbe_api.php?from=...&dest=...&phrase=...
 *  ... from=L2 language code (see Glosbe)
 *  ... dest=L1 language code (see Glosbe)
 *  ... phrase=... word or expression to be translated by
 *                  Glosbe API (see http://glosbe.com/a-api)
 *
 * PHP version 8.1
 *
 * @category Lwt
 *
 * @deprecated 3.0.0 Use TranslationController::glosbe() instead
 * @see \Lwt\Controllers\TranslationController::glosbe()
 */

namespace Lwt\Legacy;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../Controllers/TranslationController.php';

use Lwt\Controllers\TranslationController;

// Delegate to the controller
$controller = new TranslationController();
$controller->glosbe([]);

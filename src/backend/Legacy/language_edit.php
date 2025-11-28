<?php

/**
 * \file
 * \brief Manage languages - Legacy file
 *
 * This file is kept for backwards compatibility.
 * All logic has been moved to LanguageController.
 *
 * Call: /languages?....
 *      ... refresh=[langid] ... reparse all texts in lang
 *      ... del=[langid] ... do delete
 *      ... op=Save ... do insert new
 *      ... op=Change ... do update
 *      ... new=1 ... display new lang. screen
 *      ... chg=[langid] ... display edit screen
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/edit-languages.html
 * @since    1.0.3
 * @since    2.4.0 Refactored with functional paradigm
 * @since    3.0.0 Refactored with MVC pattern - delegates to LanguageController
 */

require_once __DIR__ . '/../Controllers/LanguageController.php';

$controller = new \Lwt\Controllers\LanguageController();
$controller->index([]);

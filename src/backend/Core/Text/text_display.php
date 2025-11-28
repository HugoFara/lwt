<?php

/**
 * \file
 * \brief Text display functions for rendering words in reading view.
 *
 * Functions for displaying text with word statuses, translations, and annotations.
 *
 * This file now imports from TextReadingService and provides backward compatibility.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-text-display.html
 * @since    3.0.0
 * @since    3.0.0 Migrated to TextReadingService
 */

require_once __DIR__ . '/../Globals.php';
require_once __DIR__ . '/../Database/Connection.php';
require_once __DIR__ . '/../UI/ui_helpers.php';
require_once __DIR__ . '/../../Services/TextReadingService.php';

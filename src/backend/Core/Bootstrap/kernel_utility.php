<?php

/**
 * \file
 * \brief Core utility functions that do not require a complete session.
 *
 * This file serves as a loader for all kernel utility modules.
 * Each module has been split into focused files for better maintainability.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-kernel-utility.html
 * @since    2.0.3-fork
 */

// Load settings first (initializes Globals)
require_once __DIR__ . '/../settings.php';

// Load string utilities (tohtml is used by many modules)
require_once __DIR__ . '/../Utils/string_utilities.php';

// Load error handling early (my_die is used by many modules)
require_once __DIR__ . '/../Utils/error_handling.php';

// Load version information
require_once __DIR__ . '/../version.php';

// Load debug utilities
require_once __DIR__ . '/../Utils/debug_utilities.php';

// Load text parsing utilities
require_once __DIR__ . '/../Text/text_parsing.php';

// Load word status definitions
require_once __DIR__ . '/../Word/word_status.php';

// Load URL utilities
require_once __DIR__ . '/../Http/url_utilities.php';

// Load word scoring SQL formulas
require_once __DIR__ . '/../Word/word_scoring.php';

// Load SQL file parser
require_once __DIR__ . '/../Utils/sql_file_parser.php';

// Load parameter helpers (getreq, getsess)
require_once __DIR__ . '/../Http/param_helpers.php';

// Load annotation utilities
require_once __DIR__ . '/../Text/annotation_management.php';

// Load UI helpers (quickMenu, pagestart_kernel_nobody, pageend)
require_once __DIR__ . '/../UI/ui_helpers.php';
